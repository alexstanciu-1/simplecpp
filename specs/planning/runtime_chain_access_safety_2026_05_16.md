# Runtime Chain Access Safety Plan
Doc Status: planning

Date: 2026-05-16

Purpose:
- define a general runtime-oriented safety model for chained access over nullable paths
- prevent native crashes when a chained access encounters `null`
- establish `isset(...)` as the safe probe form for nullable access chains
- keep generator changes simple and structural where possible

## Current Verified Status

As of 2026-05-16, the compact nullable-chain repro corresponding to GitHub issue `#110` is reproducible locally.

Verified local repro project:

- `../../open_m3/open_m3_01/tools/null_guard_compact_repro`

Observed behavior during verification:

- `scpp build` succeeds
- running the produced executable terminates with `SIGSEGV` / signal `11`
- no project-shaped runtime exception is surfaced before the process dies

This confirms that the current problem is not only semantic uncertainty. It is an active native-crash class for plain chained access over nullable paths.

## Current Repro Root-Cause Finding

The verified compact repro has now been inspected at the generated C++ level.

Generated structure for the local repro:

```cpp
class child_state {
public:
    nullable<string_t> name = null;
};

class root_state {
public:
    shared_p<child_state> child = null;
};
```

Generated condition shape:

```cpp
return ((php::identical(root, null) || php::identical(root->child, null)) || php::identical(root->child->name, null));
```

Important consequence:

- the failing middle hop is not the final `nullable<string_t> name`
- the failing hop is `shared_p<child_state>::operator->()` when `child` is null

So the currently verified crash path is:

1. `$root` lowers to `shared_p<root_state>`
2. `$root->child` lowers to `shared_p<child_state>`
3. `$root->child->name` requires `shared_p<child_state>::operator->()`
4. `shared_p<T>::operator->()` currently returns a raw pointer directly
5. when `child` is null, plain chained access can escape into native null dereference instead of a project-shaped runtime exception

This is a concrete confirmation that at least one active crash path currently sits in unchecked shared-pointer wrapper dereference, not only in nullable wrapper handling.

## Motivation

Current repros show that some compact chained access forms can build successfully and then crash at runtime when a nullable path is traversed.

Representative cases include:

```php
$root->child->name
$var["asdads"]
some_func()->blabla
$var->prop["key"]->bla
```

The immediate user-facing requirement is:

- Simple C++ must not crash the process when a chain hits `null`
- ordinary chained access should fail in a controlled way
- `isset(...)` should probe such chains safely

The currently verified compact repro is:

```php
$match = ($root === null || $root->child === null || $root->child->name === null) ? "no" : "yes";
```

where:

- `$root` is `?root_state`
- `$root->child` is `?child_state`
- `$root->child->name` is `?string`

The current observed failure mode is a native crash, not a project-shaped runtime exception.

## Scope

This planning note applies to chained access expressions composed of:

- object property hops such as `->prop`
- dim/index hops such as `["key"]` or `[$k]`
- call-result bases later used for access such as `some_func()->prop`
- mixed combinations of the above

Examples in scope:

```php
$root->child->name
$var["asdads"]
some_func()->blabla
$var->prop["key"]->bla
isset($var->prop["key"]->bla)
```

## Proposed General Rule

Chain access must never crash the process due to a `null` value on the path.

If any intermediate base in a chained access is `null`, the system must do one of:

- ordinary access: throw a controlled runtime exception
- probe access under `isset(...)`: return `false`

This rule is about null-path safety specifically. It must not silently replace existing semantics for unrelated errors such as missing-key handling unless a higher authority spec later decides that explicitly.

## Two Evaluation Modes

### 1. Strict Access Mode

Strict access mode applies to ordinary expressions, for example:

```php
$root->child->name
$var["asdads"]
some_func()->blabla
$var->prop["key"]->bla
```

Rule:

- evaluate the chain left to right
- before each hop, the current base must be valid for that hop
- if the current base is `null`, throw a controlled runtime exception
- never permit a native crash or invalid dereference

Expected examples:

```php
$var["asdads"]          // if $var === null => runtime exception
some_func()->blabla     // if some_func() returns null => runtime exception
$var->prop["key"]->bla  // if any intermediate base is null => runtime exception
```

### 2. Probe Access Mode

Probe access mode applies inside `isset(...)`, for example:

```php
isset($root->child->name)
isset($var["asdads"])
isset(some_func()->blabla)
isset($var->prop["key"]->bla)
```

Rule:

- evaluate the chain left to right
- if any intermediate base is `null`, return `false`
- if the final value is `null`, return `false`
- otherwise return `true`

This is the preferred safe probe form for nullable chains.

## Recommended Authoring Guidance

When author intent is to probe whether a chain is unavailable or resolves to `null`, prefer:

```php
!isset($root->child->name)
!isset($var->prop["key"]->bla)
```

instead of longer manual guard chains such as:

```php
$root === null || $root->child === null || $root->child->name === null
```

This note does not propose making `EXPR === null` globally synonymous with `!isset(EXPR)`.

Instead, the proposed model is:

- plain chained access remains strict access
- `isset(...)` is the safe probe form
- docs should steer nullable-path probing toward `isset(...)`

## Current Lowering Notes

Current generator behavior for `isset(...)` is split rather than unified.

Verified current lowering shape:

- plain one-value `isset($x)` lowers to `php::isset(x)`
- keyed `isset($a["k"])` lowers to `php::isset(a, k)`
- there is no single generalized `isset_eval(lambda)` lowering for all `isset(...)` forms today

This matters because the current shape does not provide one centralized probe-evaluation boundary for arbitrary chained expressions such as:

```php
isset($root->child->name)
isset($var->prop["key"]->bla)
```

Planned direction from current discussion:

- simple `isset(...)` forms may still lower through simple inline helper calls when that is obviously sufficient
- general or chain-shaped `isset(...)` forms should lower through a unified lambda-based runtime entry
- the runtime should own chain-probe semantics rather than the generator encoding many operand-form-specific branches

Candidate generalized lowering shape:

```cpp
php::isset_eval([&]() -> decltype(auto) {
    return EXPR;
})
```

## General Hop Model

For a chain shaped like:

```text
base -> hop1 -> hop2 -> hop3
```

each hop uses the previous hop result as its base.

Before any hop executes:

- strict mode:
  - if current base is `null`, throw runtime exception
- probe mode:
  - if current base is `null`, return `false`

This applies uniformly to:

- property reads
- dim/index reads
- access after function-call results
- mixed chains

## Runtime Exception Requirement

A null-path failure in strict access mode should become a controlled runtime exception with useful context.

Minimum expectation:

- identify hop kind when practical
- identify target when practical, such as property name or key summary
- never degrade into a native crash

Illustrative message shapes:

- `null base while reading property 'child'`
- `null base while indexing key 'asdads'`

Exact wording can vary. The important contract is controlled, debuggable failure.

## Current Wrapper Audit Notes

### `nullable<T>`

Verified current runtime behavior:

- `nullable<T>` already has checked `operator->()`
- empty nullable dereference fails through centralized `require_value(...)` / runtime error logic
- `nullable<T>` does not currently expose `operator[]`

Implication:

- plain nullable property access already has a central runtime choke point
- nullable dim access for cases such as `?hash<T>` still needs dedicated support

### `shared_p<T>`

Verified current runtime behavior:

- `operator*()` / `deref()` throw on null
- `operator->()` currently returns the raw pointer directly and does not throw

Implication:

- shared wrapper property chains can still escape into native null dereference instead of a project-shaped runtime exception
- this is now verified concretely in the current compact repro because nullable PHP object field `$child` lowered to `shared_p<child_state>`

### `unique_p<T>`

Verified current runtime behavior:

- `operator*()` / `deref()` throw on null
- `operator->()` currently returns the raw pointer directly and does not throw

Implication:

- unique wrapper property chains can still escape into native null dereference instead of a project-shaped runtime exception

### `weak_p<T>`

Verified current runtime behavior:

- no direct `operator->()` today
- access is mediated through `lock()`

Implication:

- less directly implicated in the current compact repro, but still part of the broader chain-safety surface

## Identified Plain-Access Null-Check Surface

The following plain-access cases should be treated as part of the null-check audit surface for controlled runtime exception behavior:

| Case | Example | Expected plain-access behavior | Current note |
|---|---|---|---|
| `nullable<T>::operator->()` | `$root->child` where `$root` is nullable | Throw runtime exception on empty nullable | Mostly already present |
| `nullable<T>::operator[]` | `$var["key"]` where `$var` is `?hash<T>` or `?vector<T>` | Throw runtime exception on empty nullable | Missing |
| `shared_p<T>::operator->()` | `$obj->prop` through shared wrapper | Throw runtime exception on null shared pointer | Currently unchecked |
| `unique_p<T>::operator->()` | `$obj->prop` through unique wrapper | Throw runtime exception on null unique pointer | Currently unchecked |
| `shared_p<T>::operator[]` if supported | `$obj["key"]` through shared wrapper | Throw runtime exception on null shared pointer before forwarding | Needs audit |
| `unique_p<T>::operator[]` if supported | `$obj["key"]` through unique wrapper | Throw runtime exception on null unique pointer before forwarding | Needs audit |
| call-result property access | `some_func()->blabla` | Throw runtime exception if returned base is null | Depends on returned wrapper |
| call-result dim access | `some_func()["key"]` | Throw runtime exception if returned base is null | Depends on returned wrapper |
| mixed property/dim chain | `$var->prop["key"]->bla` | First null hop throws runtime exception | Needs chain audit |
| nullable nested object chain | `$root->child->name` | Throw runtime exception when any nullable hop is empty | Current `#110` family |

## Generator and Runtime Direction

Preferred implementation posture:

- keep the S2S generator structural where possible
- avoid expanding chains into large nested `if` trees unless that becomes unavoidable
- route chain hops through strict/probe-aware helper paths

Conceptual helper families:

- strict property hop helper
- strict dim hop helper
- probe property hop helper
- probe dim hop helper

This keeps the model general while placing null-path safety close to the access operation itself.

Current implementation discussion has also identified an additional simplification direction:

- simplify `isset(...)` lowering in the S2S generator so the generator no longer has to branch heavily on operand shape
- prefer one runtime-owned generalized probe path, especially for chain expressions
- preserve very simple inline helper lowering only where it clearly remains sufficient and does not fragment semantics again

## Relationship To Current Issue 110

This planning note is intended to support the issue:

- compact chained nullable access must not crash at runtime

Under this model:

- the process must not crash when compact chained access reaches a null hop
- plain chained access may still throw a controlled runtime exception
- `isset(...)` becomes the recommended probe form for nullable chains

This may not by itself decide every source-level comparison pattern, but it gives a general safety contract that covers the current crash class.

## Open Questions

Questions to resolve during implementation/spec work:

- where the owning runtime helper boundary should live for property versus dim access
- whether any existing `isset(...)` lowering already partially implements probe semantics for chains
- how null-path safety should interact with current array/table missing-key semantics
- what exact diagnostics wording should be standardized for null-path runtime exceptions
- whether `shared_p<T>::operator->()` and `unique_p<T>::operator->()` should be changed directly or wrapped indirectly through a broader access-helper design
- how generalized `isset_eval(...)` should be implemented under the hood without overcomplicating either the generator or the runtime

## Intended Next Steps

1. inspect current lowering/runtime ownership for property access, dim access, and `isset(...)`
2. identify the smallest runtime/helper change that guarantees no native crash on null path
3. add focused regression coverage for:
   - `$root->child->name`
   - `$var["asdads"]` with `$var === null`
   - `some_func()->blabla` when call result is null
   - `$var->prop["key"]->bla`
   - `isset(...)` probe forms over the same chains
4. decide what strict docs should recommend while implementation is in progress
5. inspect generated C++ for the verified compact repro and pinpoint the exact unchecked access hop that leads to native crash
