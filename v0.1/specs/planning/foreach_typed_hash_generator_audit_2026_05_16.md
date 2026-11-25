# `foreach` Typed `hash<>` Generator Audit
Doc Status: planning

Date: 2026-05-16

Purpose:
- audit the current causes behind typed-`hash<>` `foreach` fragility
- identify whether the issue belongs to runtime or generator code
- list viable solution directions before implementation

This note is planning only.
It does not override normative specs.

## Summary

The current mismatch family is primarily generator-owned, not runtime-owned.

The runtime `foreach` surface already exposes a good iterator contract:

- `foreach_range(...)`
- entry `key()`
- entry `value_copy()`
- entry `value_ref()`

See [runtime/include/scpp/foreach.hpp](/home/alexv/__AI/simple_cpp/simple_cpp_01/runtime/include/scpp/foreach.hpp:1).

The main issue is that the generator still decides too much from guessed source-expression types before and after entering that runtime iterator path.

In particular:

- `renderForeachStatement()` assigns loop-local types from inferred source type
- unknown or partially-known sources can degrade key/value locals too early
- downstream lowering for dim access, property access, property writes, and helper-argument wrapping branches on those degraded local types
- that is what creates the observed bad-family symptoms:
  - direct object property reads lowered as dynamic `.get(...)`
  - object field writes lowered as keyed table writes
  - synthetic `cast<nullable<string_t>>(...)` repairs at helper boundaries

## Main Owning Code Paths

### 1. `foreach` lowering itself

Primary owner:

- [generators/php/src/Generator/Generator.php](/home/alexv/__AI/simple_cpp/simple_cpp_01/generators/php/src/Generator/Generator.php:4041)

Key behavior:

- source type comes from `inferExprTypeWithNamespace(...)`
- vector/hash detection is performed before generating loop locals
- value type is set to:
  - vector element type
  - typed-hash value type
  - otherwise `mixed_t` for non-vector-like sources
- key type is set to:
  - `int_t` for vector-like
  - typed-hash key type when parsed
  - otherwise `mixed_t`

Important consequence:

- once a typed-hash source is no longer recognized as `hash_t<...>`, loop locals are stamped as `mixed_t`
- after that point, many downstream generator branches switch from typed-object/typed-hash lowering into dynamic fallbacks

### 2. Source-type inference for composed expressions

Primary owner:

- [generators/php/src/Generator/Generator.php](/home/alexv/__AI/simple_cpp/simple_cpp_01/generators/php/src/Generator/Generator.php:6981)

Current behavior:

- `inferExprTypeWithNamespace(...)` can recover return types for:
  - named function calls
  - static calls
  - method calls on `this`
- it does not recover return types for arbitrary method calls on other typed objects

Why that matters:

- a source like `$this->assemble_path(...)` can sometimes be understood
- a source like `$loader->load()` or a fresh local initialized from a non-`this` method-return path can fall back to `auto`
- once the `foreach` source type becomes `auto`, typed hash entry semantics are no longer explicitly preserved by the local-type bookkeeping

This matches a planning note anti-pattern directly:

- non-`this` method-call sources should not fall off the typed path merely because the generator cannot recover richer semantics from arbitrary expressions

### 3. Downstream dim/property branching on inferred local types

Primary owners:

- dim read: [generators/php/src/Generator/Generator.php](/home/alexv/__AI/simple_cpp/simple_cpp_01/generators/php/src/Generator/Generator.php:4373)
- dim write: [generators/php/src/Generator/Generator.php](/home/alexv/__AI/simple_cpp/simple_cpp_01/generators/php/src/Generator/Generator.php:4409)
- property read: [generators/php/src/Generator/Generator.php](/home/alexv/__AI/simple_cpp/simple_cpp_01/generators/php/src/Generator/Generator.php:6091)
- property write target: [generators/php/src/Generator/Generator.php](/home/alexv/__AI/simple_cpp/simple_cpp_01/generators/php/src/Generator/Generator.php:4624)

Current pattern:

- these lowerings inspect `inferExprType(...)` on the base expression
- if the base type is `mixed_t` or dynamic-table-like, they use dynamic `.get(...)` or keyed `[...]` behavior
- if typed carrier information survives, they use typed object or typed hash behavior

Important consequence:

- an upstream loop-local downgrade to `mixed_t` is not local damage
- it changes the meaning of later reads and writes

### 4. Helper-boundary repair casts

Primary owners:

- arg rendering: [generators/php/src/Generator/Generator.php](/home/alexv/__AI/simple_cpp/simple_cpp_01/generators/php/src/Generator/Generator.php:721)
- wrapping rule: [generators/php/src/Generator/Generator.php](/home/alexv/__AI/simple_cpp/simple_cpp_01/generators/php/src/Generator/Generator.php:669)

Current pattern:

- `renderArgForParam(...)` calls `wrapExprForExpectedType(...)`
- `wrapExprForExpectedType(...)` injects casts when the source expression type is:
  - `mixed_t`
  - `nullable<T>` into `T`
  - `result*<T>` into `T`

Important consequence:

- the notorious nullable-string repair cast is not the first bug
- it is a symptom of the key local already being typed as `mixed_t`
- if the key local stayed as concrete `string_t`, `nullable<string_t>` helper boundaries would pass naturally

## Cause Breakdown By Symptom

### Symptom A: direct property reads become dynamic `.get(...)`

Likely cause:

- loop value local was recorded as `mixed_t`
- property read lowering saw `mixed_t` base and chose dynamic access

### Symptom B: object field writes become keyed table writes

Likely cause:

- loop value local was recorded as `mixed_t`
- assignment target lowering saw `mixed_t` base and emitted `base["field"]`

### Symptom C: `cast<nullable<string_t>>(...)` appears at helper boundaries

Likely cause:

- loop key local was recorded as `mixed_t`
- argument wrapping attempted to satisfy `?string` or `string` expectation with a generated cast

### Symptom D: method-return or non-`this` sources are fragile

Likely cause:

- `inferExprTypeWithNamespace(...)` only recognizes a narrow method-call subset
- typed iterable sources expressed through arbitrary composed expressions fall back to `auto`
- `foreach` local bookkeeping becomes weaker or incorrect

## What Looks Healthy Already

The runtime iterator contract itself looks well-shaped:

- `vector_t<T>` exposes `key()`, `value_copy()`, `value_ref()`
- `hash_t<T, K>` exposes entry iterators through `begin_entries()` / `end_entries()`
- result-wrapper and mixed-wrapper overloads for `foreach_range(...)` already exist

So the first implementation bias should remain:

- fix generator local typing and fallback policy first
- do not start by redesigning runtime iteration

## Solution Options

## Option 1. Extend method-call return-type recovery beyond `this`

Scope:

- improve `inferExprTypeWithNamespace(...)` so method calls on known typed carriers can recover declared return type, not only method calls on `this`

Why it helps:

- fixes one of the clearest fragility sources for composed `foreach` inputs
- directly addresses the planning-note anti-pattern around non-`this` method-call sources

Risks:

- requires careful lookup from mapped base type to class declaration
- still only helps when the source expression can be understood as a known class carrier

Assessment:

- high-value and still aligned with the project’s type-blind posture, because it relies on declared method signatures already present in the parsed code model

## Option 2. Stop defaulting unknown `foreach` locals to `mixed_t`

Scope:

- in `renderForeachStatement()`, distinguish:
  - explicitly dynamic iterable sources
  - truly unknown sources
- use `mixed_t` only for explicitly dynamic paths such as `mixed_t`
- leave unknown loop locals as unforced `auto` instead of `mixed_t`

Why it helps:

- avoids turning uncertainty into dynamic semantics
- reduces accidental `.get(...)`, keyed-table writes, and repair casts

Risks:

- some downstream validation may become compile-time-later rather than generator-eager
- unknown cases may still need a documented fallback policy

Assessment:

- likely the safest local mitigation
- matches the anti-pattern note that unknown should not silently downgrade into dynamic object-model behavior

## Option 3. Introduce a dedicated `foreach` binding-type resolver

Scope:

- add a small helper that computes:
  - iterable kind
  - key type when known
  - value type when known
  - whether the source is explicitly dynamic versus only unknown

Why it helps:

- centralizes `foreach`-specific binding decisions
- keeps `renderForeachStatement()` simpler
- gives one place to enforce “prefer runtime iterator semantics over ad hoc heuristics”

Risks:

- mostly refactoring overhead
- still depends on the quality of the source-type signals it receives

Assessment:

- strong medium-term cleanup
- pairs well with Options 1 and 2

## Option 4. Preserve typed entry semantics through local bookkeeping only when proven

Scope:

- only stamp `$k` and `$v` declared local types when the iterable source is concretely known
- otherwise keep the emitted C++ local declarations direct and let native `auto` preserve entry type without forcing generator-side `mixed_t`

Why it helps:

- reduces the chance that declared-local bookkeeping lies about the actual runtime iterator entry type

Risks:

- downstream generator features that depend on `declaredLocalTypes` may lose some convenience

Assessment:

- good companion to Option 2
- likely better than inventing `mixed_t` where the runtime entry is actually concrete

## Option 5. Patch downstream property/dim fallbacks only

Scope:

- add local heuristics in property and dim lowering to avoid dynamic fallback in more cases

Why it helps:

- may unblock a narrow symptom quickly

Risks:

- papers over root cause
- increases scattered heuristics
- directly conflicts with the planning-note warning against encoding policy in many local guesses

Assessment:

- not recommended as the main fix

## Recommended Order

The best first-pass strategy appears to be:

1. extend method-return inference for known typed method-call sources
2. stop coercing unknown `foreach` loop locals to `mixed_t`
3. centralize `foreach` binding-type resolution in a helper if the first two changes still leave scattered logic

That sequence keeps the fix generator-owned, narrow, and aligned with the existing runtime iterator design.

## Validation Targets

Primary repro set:

- [tests/php/classes/level_02/classes_013_hash_sharedp_foreach_typing.phs](/home/alexv/__AI/simple_cpp/simple_cpp_01/tests/php/classes/level_02/classes_013_hash_sharedp_foreach_typing.phs:1)
- [tests/php/classes/level_02/classes_014_hash_sharedp_foreach_value_only.phs](/home/alexv/__AI/simple_cpp/simple_cpp_01/tests/php/classes/level_02/classes_014_hash_sharedp_foreach_value_only.phs:1)
- [tests/php/classes/level_02/classes_016_hash_sharedp_method_source_direct_key_usage.phs](/home/alexv/__AI/simple_cpp/simple_cpp_01/tests/php/classes/level_02/classes_016_hash_sharedp_method_source_direct_key_usage.phs:1)
- [tests/php/classes/level_02/classes_017_hash_sharedp_direct_key_helper_and_reindex.phs](/home/alexv/__AI/simple_cpp/simple_cpp_01/tests/php/classes/level_02/classes_017_hash_sharedp_direct_key_helper_and_reindex.phs:1)
- [tests/php/classes/level_02/classes_018_hash_sharedp_method_nullable_key_boundary.phs](/home/alexv/__AI/simple_cpp/simple_cpp_01/tests/php/classes/level_02/classes_018_hash_sharedp_method_nullable_key_boundary.phs:1)
- [tests/php/classes/level_02/classes_019_hash_method_return_local_foreach_nullable_key.phs](/home/alexv/__AI/simple_cpp/simple_cpp_01/tests/php/classes/level_02/classes_019_hash_method_return_local_foreach_nullable_key.phs:1)

Regression guards:

- [tests/php/control_flow/level_01/control_flow_027_foreach_hash_key_value_basic.phs](/home/alexv/__AI/simple_cpp/simple_cpp_01/tests/php/control_flow/level_01/control_flow_027_foreach_hash_key_value_basic.phs:1)
- [tests/php/control_flow/level_01/control_flow_031_foreach_hash_value_basic.phs](/home/alexv/__AI/simple_cpp/simple_cpp_01/tests/php/control_flow/level_01/control_flow_031_foreach_hash_value_basic.phs:1)
- [tests/php/control_flow/level_02/control_flow_029_foreach_hash_ref_value.phs](/home/alexv/__AI/simple_cpp/simple_cpp_01/tests/php/control_flow/level_02/control_flow_029_foreach_hash_ref_value.phs:1)
- [tests/php/control_flow/level_02/control_flow_030_foreach_hash_key_ref_value.phs](/home/alexv/__AI/simple_cpp/simple_cpp_01/tests/php/control_flow/level_02/control_flow_030_foreach_hash_key_ref_value.phs:1)
- [tests/php/classes/level_02/classes_015_hash_sharedp_key_stabilization.phs](/home/alexv/__AI/simple_cpp/simple_cpp_01/tests/php/classes/level_02/classes_015_hash_sharedp_key_stabilization.phs:1)
