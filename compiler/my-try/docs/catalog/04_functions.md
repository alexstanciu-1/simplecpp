# 04. Functions, parameters and calls
Doc Status: planning

> **Important: LLVM is deferred by default. Work on the direct LLVM backend only when the user explicitly requests it. The v0.2 default scope is Frontend + C++ S2S.**

[Catalog and workflow](README.md) · [Source merge ledger](source_merge.json)

Requires locals and expressions. Begin with a no-argument function, then returns, scalar parameters and calls; split composite examples.

Order is a discussion sequence, not a claim that every row is a prerequisite. Split combined examples before implementation.

## Handoff from chapter 03 — 2026-10-02

[Chapter 03's bounded first pass is complete](03_control_flow.md#positive-pass-conclusion--2026-10-02).
Functions use `function_body_node` over the common `statement_body_node`; ordinary
blocks share statement storage/processing without owning function work identity.
Preparation now rejects non-void fallthrough under the documented structured-control
rules. Branches and arithmetic needed for terminating recursion are available; the
old prerequisite limitation below is historical, not a current blocker.

Next, reconcile existing functionality before adding features: FUNC-DECL-004 (string
parameters), FUNC-RETURN-003 (expression returns), FUNC-CALL-003 (multiple arguments),
and FUNC-RECURSION-001 (replace its nonterminating example with a bounded working case).
Existing `tests/s2s.php` and Chapter 03 proofs are starting evidence; leave unverified
rows unverified until their agreed examples are checked. This handoff adds no test run.
The omitted-type examples FUNC-DECL-001/005 now reject under the explicit-signature
agreement below. Defaults are covered by their bounded slice; ordinary function
overloading is forbidden. Variadics remain a later discussion.

### Opening verification batch

Chapter 03's [CF-01–CF-09 debt register](03_control_flow.md#remaining-debt-and-scope-boundaries)
is deferred, not a prerequisite queue for this chapter. Start with the following
bounded examples using the existing function/signature/body/call owners:

| Entry | Working proof to establish |
| --- | --- |
| FUNC-DECL-004 | Pass an int and a string to a typed function; observe the string's value in the result |
| FUNC-RETURN-003 | Return an arithmetic expression from a typed function and check its value |
| FUNC-CALL-003 | Pass several scalar arguments and check their positions and values; retain existing left-to-right argument snapshot proofs |
| FUNC-RECURSION-001 | Execute recursion with an explicit base case and check the result, rather than merely compiling mutually recursive signatures |

No new retained structure or semantic extension is proposed for this batch. If a proof
reveals a missing capability, identify its owner and discuss any semantic expansion
before implementing it. Keep defaults, variadics, overloads and omitted-type policies
out of this reconciliation batch. Progress rows remain unverified until the corresponding
focused proof is recorded. This opening batch is now complete; see the two
verification checkpoints below.

### Typed-signature verification — 2026-10-02

Verified FUNC-DECL-004, FUNC-RETURN-003 and FUNC-CALL-003 through the existing
`function_node` / ordered `parameter_node` / `function_body_node` model. Signature
preparation owns parameter types, passing modes and return type; body preparation
introduces parameter storage; call preparation owns positional argument checks and
conversions. Strings need no special parameter node. Value parameters remain local
copies, while explicit `&` preserves caller storage.

Legacy review: `Generator::analyzeParamPassModes` distinguishes read-only versus
written string parameters; `tests/php/functions/level_02/functions_004_multi_param_basic.phs`
and the strict multi-int return case cover ordinary positional calls. This slice
checks observable value semantics, not adoption of the legacy const-reference
optimization. Current S2S emits ordinary value parameters by value.

Eight new `function_typed_*` fixtures in `tests/s2s.php` observe string contents,
distinct positional weights, both boolean branches, float input, local int/string
reassignment without caller mutation, string expression returns, explicit string
references, forward signatures and left-to-right string snapshots. Five rejection
cases cover incompatible arguments/returns and excess arguments through the existing
failure cleanup/purity checks. No compiler structure or semantics changed.

Evidence: `tests/functions.py` with results retained at
`/tmp/my-try-functions-signatures-20261002` passed six PHP suites and 22 generated C++
programs using 12 slots. FPM socket startup was unavailable in the sandbox, so the
documented CLI fallback ran. This is PHP compiler plus generated-program evidence,
not a new native compiler checkpoint. The cases are also exported for future
requested native validation. Terminating recursion is covered by the next checkpoint;
defaults were outside that checkpoint and are covered below. Variadics, omitted types
and container-specific ownership remain deferred. String ownership is reconciled below.

### Terminating-recursion verification — 2026-10-02

FUNC-RECURSION-001 uses ordinary calls, not a special recursive-call node. Shared
preparation publishes signatures before bodies; C++ emits prototypes before bodies.
Each invocation has independent value parameters/locals, while explicit reference
arguments continue to name their original storage.

Seven `function_recursive_*` execution fixtures in `tests/s2s.php` prove immediate
base-case return, factorial(5) = 120, direct recursive return, preserved caller and
per-invocation locals, two recursive calls in one invocation (fib(7) = 13), mutual
recursion through a forward declaration, and reference forwarding across five calls.
Recursive results are bound to locals before arithmetic, respecting the existing
eager-operator effect boundary rather than expanding expression support.

Legacy intake: the imported FUNC-RECURSION-001 example has no base case and remains
historical input below, not an executable acceptance test. The legacy
`StanExpressionTypeResolver::collectReturnDiagnosticsForOwner` also has a separate
direct-self-recursion diagnostic; this slice does not import that heuristic or resume
STAN work. Existing my-try recursive-signature coverage only compiled uncalled bodies;
the new cases execute them with bounded inputs.

`tests/functions.py` passed six PHP suites and 29 generated C++ programs; evidence:
`/tmp/my-try-functions-recursion-20261002/summary.json`. The shared runner used 12
slots and CLI fallback after sandbox FPM socket startup failed. No compiler code
changed, and no native compiler rebuild was performed. These fixtures participate
in future requested native validation through the existing S2S source manifest.

Non-goals: proving arbitrary recursion terminates, stack-depth limits, tail-call
optimization, general call-effect composition, or new return/type-inference rules.

### Scalar parameter defaults — 2026-10-02

Implemented FUNC-DEFAULT-001 for trailing by-value scalar parameters: bool, integer,
float and string literals, with an optional single sign on numeric literals.
Parameter types remain explicit, and defaults use the existing argument-conversion
contract (including the existing integer-family conversions, not implicit scalar
coercion). Parentheses already normalize away in the expression parser.

`parameter_node` owns an optional `default_value` expression. Absence means required;
there is no duplicate flag. Signature preparation checks every default even when the
function is uncalled or a caller supplies that argument. `Parameter_Defaults` owns
bounded literal normalization and immutable prepared value/conversion snapshots.
Signed float literals do not extend the general unary-operator matrix. Inspection,
maintenance and cleanup traverse the new syntax edge.

Call preparation validates the omitted trailing suffix through the prepared signature;
source argument lists stay unchanged. Explicit arguments retain their left-to-right
snapshot/reference handling. The body sees ordinary parameters. C++ defaults appear
only on prototypes: the signature fragment retains separate prototype and definition
spellings. Default presence/value participates in signature equivalence, so changes
invalidate callers and regenerate output rather than reusing stale facts.

Legacy intake: `Generator::renderCanonicalParamsForExec` and
`renderValueRefOverloadParams` gate defaults with `includeDefaults`; the strict
`strict_functions_005_default_int_arg_return.phs` and
`strict_functions_008_default_two_int_args_return.phs` cases supply the starting
examples. This slice preserves the declaration-only rule without importing legacy
overload machinery.

Proofs: 17 new `function_default_*` generated-program fixtures, 16 rejection cases,
and incremental signature/caller identity, prototype-only placement, no-op/token
movement, default edit/removal/repair and fresh-output equivalence checks in
`tests/incremental_cpp.php`. The focused runner covers ten PHP suites and 46 generated
C++ programs. Evidence: `/tmp/my-try-function-defaults-20261002-2/summary.json`;
12 slots, documented CLI fallback after sandbox FPM socket startup failed.
Native compiler rebuild and LLVM remain untested for this slice.

Deferred scope: reference defaults, named constants/general constant expressions,
calls or parameter-dependent expressions, composite/wrapper values, named arguments
and variadics. Required parameters after optional ones reject. Existing literal
range/spelling restrictions still apply; this slice does not expand integer bounds.

### Declaration and scope contract — 2026-10-02

Ordinary named functions require explicit parameter and return types, including
`: void`. Defaults do not infer parameter types. FUNC-DECL-001's implicit void and
NOTE-039's implicit auto are conflicting imported guidance, not the v0.2 contract.
The parser already required both types; missing return types now have an explicit
diagnostic. Constructors, closures and expected-callable typing are not decided here.

Function names are unique in their visible declaration scope regardless of parameter
types or whether the function is called. Signature preparation observes the existing
function-name lookup pool before publishing facts, so adding/removing an unused
duplicate invalidates the original declaration too. Candidate identities stay intact
for diagnostics and recovery. Parameter names must be unique within a signature;
the same parameter name in different functions is valid. No overload selection or
new symbol registry is introduced.

Function locals/parameters remain isolated from entry-body variables. Outer reads
and read-modify-writes do not implicitly capture them; pass values/references
explicitly. A first plain assignment inside a function still establishes a local.
The separate explicit-capture proposal below remains deferred.

Legacy intake: the mandatory-type and no-overloading rules in `generators/php/specs/rules.md`,
the duplicate diagnostics in `StanDiagnosticCollector::duplicateMessage`, and existing
my-try local/reference and NOTE-033.a capture proofs. Validation here belongs to shared
preparation, not the type-blind legacy generator or C++ compilation.

Proof: four parser rejection cases, seven additional semantic rejection cases, and
cross-file unused duplicate introduction/no-edit retry/removal plus parameter repair
in `tests/preparation_recovery.php`. The focused runner passed ten PHP suites and
46 generated C++ programs; evidence: `/tmp/my-try-function-declarations-20261002-1/summary.json`
(12 slots, CLI fallback after sandbox FPM socket startup failed). Native compiler
rebuild and LLVM were not run. PE-03 is closed for ordinary function/parameter names;
duplicate struct fields remain debt. NOTE-016/039/040 retain unrelated deferred forms.

### String parameter and return ownership — 2026-10-02

By-value string parameters are local values: assignment and `.=` cannot change the
caller's string. Explicit `&` aliases caller storage, so the same writes are visible
to the caller. A read-only value parameter remains a snapshot even when another
reference parameter aliases and changes the original during the call.

Ordinary returns are by value, including returns of parameters, locals, and values
read through reference parameters. The compiler does not infer `const &` return
types or read-only parameter borrowing from the function body. The legacy
`Generator::analyzeParamPassModes` optimization and NOTE-039/040 reference-return
examples are not adopted for this first pass. Any later optimization must preserve
the established value behavior, alias isolation and lifetime guarantees.

No compiler or AST changes were needed. Six `function_ownership_*` cases prove local
append versus reference append, independent source/result mutation after value and
reference-parameter returns, a returned local surviving its function, and read-only
snapshot isolation during aliased mutation. Six accompanying C++ static assertions
check exact parameter and return types, so copying at the call site cannot hide an
accidentally inferred reference-return signature. Existing tests cover reassignment,
literal/computed string results and argument sequencing.

Evidence: `tests/functions.py`, `/tmp/my-try-function-ownership-20261002/summary.json`:
ten PHP suites and 58 generated C++ programs/probes, using 12 slots and CLI fallback
after sandbox FPM socket startup failed. No native compiler rebuild or LLVM run.
The fixture sources and probes are included in future requested native validation.

Explicit reference returns require a separate lifetime contract and remain deferred.
Containers/wrappers/shared-object handles retain their own copy semantics; string
independence is not a general deep-copy rule. TYPE-PARAM-003D is closed for its string
example only; its imported vector extension remains for Chapter 06.

## Ordinary-function foundation (2026-09-27)

Implemented through shared signature/body preparation and specialization-attached
facts: explicit scalar/record return types, `void`, positional value/reference
parameters, forward/nested calls, isolated function locals and record value copies.
C++ emits struct definitions in dependency order, then all function prototypes,
function bodies and the entry body. Calls snapshot value arguments left-to-right;
reference arguments keep their original stable storage. PHP preparation and
emitted C++ execution proofs live in `../../tests/s2s.php`. The user-requested
native compiler build also passed these declaration cases; see
[current native status](../portability/conversion_review.md).

Legacy intake review: `generators/php/src/Generator/Generator.php` declaration,
struct and typed-initializer lowering; `Lowering/TypeMapper.php` fixed integer
representations; `Analysis/StructFieldTypePolicy.php` field eligibility; catalog
parameter/value rules; and the existing call/reference regression scenarios.
Owning contracts: `specs/compact_layout_types.md` and
`specs/native_reference_safety.md`. Fixed-width integers retain `int_t<Rep>`
representations, structs copy values, and explicit `&` preserves storage identity.
Direct stable struct fields may be reference arguments; dynamic/indexed storage
is not introduced. LLVM's experimental scalar type choices are not inherited.

Scope: retain the current single-source S2S boundary and existing frontend grammar.
Templates await review. No defaults, variadics, overload selection, arithmetic,
control-flow expansion, new construction syntax or general validation pass is added.
At this checkpoint, recursive signatures compiled but terminating recursive programs
and missing-return analysis still needed control-flow work. Chapter 03 now supplies
that bounded foundation; see the current handoff above.

## Deferred v0.2 planning: explicit captures for functions and methods

Track `use` captures for ordinary functions and methods as a v0.2 planning item.
Implementation is deferred until a later language-development discussion; it may
be deferred beyond v0.2 explicitly, but must remain tracked here.

The intended direction is alignment with lambda `use` captures, including
value/reference intent. Exact syntax, capture sources, binding time and lifetime
rules require review before implementation. This is a proposed language addition,
not a claim of current frontend or S2S support. Coordinate with
[closures](12_closures.md) and the originating
[incremental planning debt](../planning/incremental_strategy.md#agreed-debts).

## Progress

Edit these rows as work proceeds. Imported source support is recorded below, independently of this progress.

| Entry | Status | PHP input example | Frontend | C++ S2S | LLVM | Proof / blocker |
| --- | --- | --- | --- | --- | --- | --- |
| [FUNC-DECL-001](#func-decl-001) | agreed-rejection | `function f() { }` | proved | rejected before emission | deferred | Explicit return type required; `tests/ast.php` |
| [FUNC-VOID-001](#func-void-001) | agreed | `function f(): void { return; }` | proved | proved | deferred | `tests/s2s.php` |
| [FUNC-DECL-002](#func-decl-002) | agreed | `function f(): int { return 1; }` | proved | proved | deferred | `tests/s2s.php` |
| [FUNC-RETURN-001](#func-return-001) | agreed | `return;` | proved | proved | deferred | `tests/s2s.php` |
| [FUNC-DECL-003](#func-decl-003) | agreed | `function f(int $a): int { return $a; }` | proved | proved | deferred | `tests/s2s.php` |
| [TYPE-PARAM-001](#type-param-001) | agreed | `function f(int $a, float $b, bool $c): void {}` | proved | proved | deferred | `tests/s2s.php` |
| [FUNC-CALL-001](#func-call-001) | agreed | `f();` | proved | proved | deferred | `tests/s2s.php` |
| [FUNC-CALL-002](#func-call-002) | agreed | `f($a);` | proved | proved | deferred | `tests/s2s.php` |
| [FUNC-DECL-004](#func-decl-004) | agreed | `function f(int $a, string $b): int { return $a; }` | proved | proved | deferred | Typed-signature verification; `tests/functions.py` |
| [FUNC-DEFAULT-001](#func-default-001) | agreed | `function f(int $a = 1): int { return $a; }` | proved | proved | deferred | Scalar defaults; `tests/functions.py`, `tests/incremental_cpp.php` |
| [FUNC-RECURSION-001](#func-recursion-001) | agreed | Base case plus recursive call; see working example below | proved | proved | deferred | Terminating-recursion verification; `tests/functions.py` |
| [FUNC-OVERLOAD-001](#func-overload-001) | agreed-rejection | Same-name functions, including unused declarations | proved | rejected before emission | deferred | `tests/s2s.php`, `tests/preparation_recovery.php` |
| [FUNC-DECL-005](#func-decl-005) | agreed-rejection | `function f($a): int { return $a; }` | proved | rejected before emission | deferred | Explicit parameter type required; `tests/ast.php` |
| [FUNC-RETURN-002](#func-return-002) | agreed | `return $a;` | proved | proved | deferred | `tests/s2s.php` |
| [FUNC-RETURN-003](#func-return-003) | agreed | `return $a + 1;` | proved | proved | deferred | Typed-signature verification; `tests/functions.py` |
| [FUNC-CALL-003](#func-call-003) | agreed | `f($a, 1, "x", true);` | proved | proved | deferred | Typed-signature verification; `tests/functions.py` |
| [FUNC-CALL-004](#func-call-004) | pending-discussion | `sum_all(1, 2, 3);` | unverified | unverified | deferred | — |
| [SCOPE-GLOBAL-001](#scope-global-001) | agreed-rejection | `$a = 1; function f(): int { return $a; }` | proved | rejected before emission | deferred | No implicit capture; `tests/s2s.php` |
| [SCOPE-LOCAL-001](#scope-local-001) | agreed | `function f(): void { $a = 1; $b = $a; }` | proved | proved | deferred | `tests/s2s.php` |
| [SCOPE-SHADOW-001](#scope-shadow-001) | agreed | `$a = 1; function f(): void { $a = 2; }` | proved | proved | deferred | `tests/s2s.php` |
| [TYPE-PARAM-003D](#type-param-003d) | agreed (string slice) | `function f(string $s): void { $s .= "x"; }` | proved | proved | deferred | Ownership proofs in `tests/s2s.php`; vectors deferred |
| [NOTE-016](#note-016) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-039](#note-039) | agreed bounded subset | Explicit typed value return | proved for current slice | proved for current slice | deferred | No implicit auto/reference returns; wrapper extensions deferred |
| [NOTE-040](#note-040) | agreed bounded subset | Explicit parameters and value returns | proved for current slice | proved for current slice | deferred | Legacy annotation conflicts and explicit reference returns deferred |
## FUNC-DECL-001

**v0.2 decision / target C++:** Reject omitted return types, including empty bodies.
Write `function f(): void {}` explicitly. The imported implicit-void rule below is
not adopted.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:137](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f() { }
```

**Existing C++ lowering / result**

```cpp
void f() { }
```

**Category:** Function

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** function has no declared return type

**Normalized pattern:** `function <name>() <block>`

**General rule:** If no return type is specified in PHP, the function must be generated as `void` in C++.

**Diagnostics:** Error if a `return <expr>` exists inside a function without a declared return type; `return;` is allowed.

**Notes:** This is stricter than PHP.


## FUNC-VOID-001

**v0.2 decision / target C++:** Implemented within the ordinary-function slice above; explicit signatures and prepared value/reference boundaries drive emission.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:145](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(): void { return; }
```

**Existing C++ lowering / result**

```cpp
void f() { return; }
```

**Category:** Function

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** function return type is `void`

**Normalized pattern:** `function <name>(): void <block>`

**General rule:** PHP `void` functions map directly to C++ `void` functions. A bare `return;` is emitted directly.

**Diagnostics:** Error if a `void` function returns a value.


## FUNC-DECL-002

**v0.2 decision / target C++:** Implemented within the ordinary-function slice above; explicit signatures and prepared value/reference boundaries drive emission.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:138](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(): int { return 1; }
```

**Existing C++ lowering / result**

```cpp
int_t f() { return static_cast<int_t>(1); }
```

**Category:** Function

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** supported return type; all paths return value

**Normalized pattern:** `function <name>(): <type> <block>`

**General rule:** PHP typed return functions must be mapped to the corresponding `_t` type in C++. All returned expressions must be normalized.

**Diagnostics:** Error if return expression is incompatible, literal not normalized, or function does not return a value on all paths.


## FUNC-RETURN-001

**v0.2 decision / target C++:** Implemented within the ordinary-function slice above; explicit signatures and prepared value/reference boundaries drive emission.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:146](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
return;
```

**Existing C++ lowering / result**

```cpp
return;
```

**Category:** Function

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** inside function; valid in current return context

**Normalized pattern:** `return`

**General rule:** `return;` is emitted directly. It represents a void return.

**Diagnostics:** Error if used outside a function or in a non-void function where a value is required.


## FUNC-DECL-003

**v0.2 decision / target C++:** Implemented within the ordinary-function slice above; explicit signatures and prepared value/reference boundaries drive emission.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:139](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(int $a): int { return $a; }
```

**Existing C++ lowering / result**

```cpp
int_t f(int_t a) { return a; }
```

**Category:** Function

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** parameter and return type supported; returned expression compatible

**Normalized pattern:** `function <name>(<typed-param>): <type> <block>`

**General rule:** PHP typed parameters and typed returns map to their Prism++ runtime types. Returning a variable of the declared type is emitted directly.

**Diagnostics:** Error if parameter/return type unsupported or returned expression incompatible.


## TYPE-PARAM-001

**v0.2 decision / target C++:** Implemented within the ordinary-function slice above; explicit signatures and prepared value/reference boundaries drive emission.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:329](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(int $a, float $b, bool $c): void {}
```

**Existing C++ lowering / result**

```cpp
void_t f(int_t a, float_t b, bool_t c) {}
```

**Category:** Type system

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** parameter types are explicit and supported

**Normalized pattern:** `primitive parameters`

**General rule:** `int_t`, `float_t`, and `bool_t` parameters are emitted by value unless explicit `&` is present.

**Diagnostics:** Error if an unsupported primitive parameter form is requested.

**Notes:** The same value rule applies to returns unless explicit `&` is present.


## FUNC-CALL-001

**v0.2 decision / target C++:** Implemented within the ordinary-function slice above; explicit signatures and prepared value/reference boundaries drive emission.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:149](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
f();
```

**Existing C++ lowering / result**

```cpp
f();
```

**Category:** Function

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** function declared or available in scope

**Normalized pattern:** `<name>()`

**General rule:** Function calls without arguments are emitted directly.

**Diagnostics:** Error if function is not declared.


## FUNC-CALL-002

**v0.2 decision / target C++:** Implemented within the ordinary-function slice above; explicit signatures and prepared value/reference boundaries drive emission.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:150](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
f($a);
```

**Existing C++ lowering / result**

```cpp
f(a);
```

**Category:** Function

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** function declared; argument expression valid and in scope

**Normalized pattern:** `<name>(<expr>)`

**General rule:** Function calls with arguments are emitted directly, with all arguments normalized beforehand.

**Diagnostics:** Error if function not declared, argument uses undeclared variables, or argument type incompatible.


## FUNC-DECL-004

**v0.2 decision / target C++:** Verified with the existing ordered typed signature and value/reference call preparation; see typed-signature verification above.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:141](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(int $a, string $b): int { return $a; }
```

**Existing C++ lowering / result**

```cpp
int_t f(int_t a, string_t b) { return a; }
```

**Category:** Function

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** all parameter types supported; return type supported

**Normalized pattern:** `function <name>(<typed-param>, <typed-param>): <type> <block>`

**General rule:** Multiple typed parameters are mapped positionally to their corresponding `_t` types in C++. Return type mapping follows the same rules.

**Diagnostics:** Error if any parameter type unsupported, return type unsupported, or return expression incompatible.


## FUNC-DEFAULT-001

**v0.2 decision / target C++:** Implemented trailing by-value scalar literal defaults;
signature preparation owns validation and conversion, calls may omit the trailing
optional suffix, and only C++ prototypes include the default expressions. See the
bounded scalar-default slice above for proofs and deferred forms.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:143](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(int $a = 1): int { return $a; }
```

**Existing C++ lowering / result**

```cpp
int_t f(int_t a = static_cast<int_t>(1)) { return a; }
```

**Category:** Function

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** parameter type supported; default expression compatible

**Normalized pattern:** `function <name>(<param> = <expr>): <type> <block>`

**General rule:** Default parameter values must be preserved and normalized according to literal rules. The parameter type must be explicit.

**Diagnostics:** Error if default value incompatible with parameter type or default literal not normalized.


## FUNC-RECURSION-001

**v0.2 decision / target C++:** Verified through ordinary prepared signatures and
calls, with prototypes preceding bodies. No recursion-specific retained structure.

Working acceptance example (result: 120):

```php
function factorial(int $n): int {
	if ($n <= 1) { return 1; }
	$previous = factorial($n - 1);
	return $n * $previous;
}
return factorial(5);
```

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:161](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(int $a): int { return f($a - 1); }
```

**Existing C++ lowering / result**

_Not supplied in source._

**Category:** Function

**Rule kind:** generation

**Normalized pattern:** `recursive function call`


## FUNC-OVERLOAD-001

**v0.2 decision / target C++:** Shared signature preparation rejects duplicate
function names even without callers and regardless of parameter differences.
Incremental lookup observation preserves invalidation and recovery.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:106](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:269](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(int $a): int {} function f(string $a): int {}
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Function

**Rule kind:** rejection

**Source support status:** rejected

**Preconditions:** same function namespace/scope

**Normalized pattern:** same-name multiple free functions

**General rule:** Function overloading is forbidden by Prism++ design.

**Diagnostics:** Emit an error on duplicate function names regardless of parameter differences.

**Notes:** Mirrors the method rule.


## FUNC-DECL-005

**v0.2 decision / target C++:** Reject missing parameter types; defaults do not infer
them. Omitted return types independently reject under FUNC-DECL-001.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:142](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f($a) { return $a; }
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Function

**Rule kind:** rejection

**Source support status:** rejected

**Normalized pattern:** `function <name>(<untyped-param>) <block>`

**General rule:** Function parameters must have explicit types. Untyped parameters are not allowed.

**Diagnostics:** Error if any parameter is missing a type.

**Notes:** No implicit parameter typing.


## FUNC-RETURN-002

**v0.2 decision / target C++:** Implemented within the ordinary-function slice above; explicit signatures and prepared value/reference boundaries drive emission.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:147](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
return $a;
```

**Existing C++ lowering / result**

```cpp
return a;
```

**Category:** Function

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** inside non-void function; `a` declared; type-compatible

**Normalized pattern:** `return <expr>`

**General rule:** Returning a variable is emitted directly, provided it is already normalized and type-compatible with the functionâ€™s declared return type.

**Diagnostics:** Error if used outside function, in `void` function, variable undeclared, or type incompatible.


## FUNC-RETURN-003

**v0.2 decision / target C++:** Verified through shared expression/return preparation and typed C++ emission; no return-specific expression representation is needed.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:148](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
return $a + 1;
```

**Existing C++ lowering / result**

```cpp
return a + static_cast<int_t>(1);
```

**Category:** Function

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** inside non-void function; expression type-compatible

**Normalized pattern:** `return <expr>`

**General rule:** Return expressions must follow full expression normalization rules. Any literal inside the expression must be converted using the appropriate `static_cast<..._t>(...)`.

**Diagnostics:** Error if literal not normalized, expression type incompatible, or used in a `void` function.


## FUNC-CALL-003

**v0.2 decision / target C++:** Verified positional scalar arguments, including strings; value arguments snapshot left-to-right and explicit references preserve storage.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:151](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
f($a, 1, "x", true);
```

**Existing C++ lowering / result**

```cpp
f(a, static_cast<int_t>(1), string_t("x"), static_cast<bool_t>(true));
```

**Category:** Function

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** function declared; all arguments valid expressions

**Normalized pattern:** `<name>(<expr>, <expr>, ...)`

**General rule:** All function call arguments must be individually normalized before emission. Literals must follow their respective conversion rules.

**Diagnostics:** Error if any literal not normalized, argument type incompatible, or function not declared.


## FUNC-CALL-004

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:152](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
sum_all(1, 2, 3);
```

**Existing C++ lowering / result**

```cpp
sum_all(::scpp::vector_t<int_t>{static_cast<int_t>(1), static_cast<int_t>(2), static_cast<int_t>(3)});
```

**Category:** Function

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** callee signature is known; trailing variadic parameter type is explicit and supported

**Normalized pattern:** call to typed trailing variadic

**General rule:** Calls to typed trailing variadic functions/methods are lowered by packing the variadic tail into a `vector_t<T>` temporary.

**Diagnostics:** Error if the callee variadic element type is unknown/unsupported, or if an untyped variadic would be required.

**Notes:** Non-variadic leading parameters keep their normal positional lowering.


## SCOPE-GLOBAL-001

**v0.2 decision / target C++:** Reject implicit access to entry-body variables from
ordinary function bodies. Pass them as explicit parameters; captures remain deferred.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:163](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = 1; function f(): int { return $a; }
```

**Existing C++ lowering / result**

_Not supplied in source._

**Category:** Scope

**Rule kind:** generation

**Normalized pattern:** `global scope + function scope`


## SCOPE-LOCAL-001

**v0.2 decision / target C++:** Implemented within the ordinary-function slice above; explicit signatures and prepared value/reference boundaries drive emission.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:164](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(): void { $a = 1; $b = $a; }
```

**Existing C++ lowering / result**

_Not supplied in source._

**Category:** Scope

**Rule kind:** generation

**Normalized pattern:** `local scope variable declaration and use`


## SCOPE-SHADOW-001

**v0.2 decision / target C++:** Implemented within the ordinary-function slice above; explicit signatures and prepared value/reference boundaries drive emission.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:165](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = 1; function f(): void { $a = 2; }
```

**Existing C++ lowering / result**

_Not supplied in source._

**Category:** Scope

**Rule kind:** rejection-or-generation

**Normalized pattern:** `name reuse across scopes`


## TYPE-PARAM-003D

**v0.2 decision / target C++:** By-value string parameters remain local values,
including when modified with `.=`. Explicit `&` retains aliasing. No body-inferred
`const &` parameter mode; vector behavior remains deferred to Chapter 06.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:335](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(string $s): void { $s .= "x"; }
```

**Existing C++ lowering / result**

```cpp
void_t f(string_t s) { s += string_t("x"); }
```

**Category:** Type system

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** parameter type is by-value `string`/`vector_t` and the body proves a write or reassignment on the parameter root

**Normalized pattern:** `string/vector parameter owned-local convention`

**General rule:** The same two-mode rule applies to `string_t` and `vector_t`: proven writes or reassignment require owning pass-by-value `T x`; otherwise read-only params stay `const T&`.

**Diagnostics:** Error if the generator keeps such a param as `const &`.

**Notes:** Reassignment is supported but can be expensive for large values.


## NOTE-016

**Agreed subset (2026-10-02):** ordinary named functions require explicit parameter
and return types; scalar defaults and explicit parameter references follow their
implemented contracts above. Nullable/union signatures, methods and reference
returns are not completed by this agreement.

**Source:** [generators/php/specs/rules.md:234](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## 8. Functions
>
> - typed parameters are mandatory
> - explicit return types are mandatory
> - default values must be normalized
> - nullable types must be emitted as `nullable<T>` for nullable value types
> - references are supported for functions and methods when explicit in source
> - reference semantics are emitted literally and are never inferred
> - default parameter values are allowed and belong to declarations only
> - primary-type normalized PHP union parameters are supported by design; see `primary_type_normalized_parameters.md`
> - for a union parameter, the first listed type is the primary type and later types are secondary source types
> - current generator lowering extracts the primary type as the emitted callable-body type while annotation parsing and validation are handled centrally
>
> ---

## NOTE-039

**Agreed correction (2026-10-02):** no implicit `auto` return type for ordinary named
functions. Their declared return type is required. Ordinary returns remain by value;
returning a local, parameter or reference-parameter value does not infer a reference.
The imported reference-return heuristics below are not adopted. Explicit reference
returns and wrapper-specific extensions remain deferred.

**Source:** [generators/php/specs/rules.md:788](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** The agreed value-return subset above is proved;
wrapper extensions and explicit reference returns remain deferred. Imported prose
follows; its authority labels and v1 boundaries are not this catalog's status.

> ## 10. Return rules
>
> ### 10.1 Missing declared return type
> - Missing return type -> `auto`
>
> ### 10.2 Primitive-like returns
> Return by value for:
> - `int_t`
> - `float_t`
> - `bool_t`
> - `nullable<int_t>`
> - `nullable<float_t>`
> - `nullable<bool_t>`
>
> ### 10.3 Heavy / wrapper returns
> Return by `const &` only when the returned expression is clearly an existing stable object/reference.
>
> Examples:
> ```cpp
> const string_t& f(const string_t& a) { return a; }
> const nullable<string_t>& f(const nullable<string_t>& a) { return a; }
> ```
>
> Return by value for:
> - literals
> - temporary objects
> - computed expressions
> - concatenations
> - function call results
> - any return expression whose lifetime safety is not explicitly known
>
> Examples:
> ```cpp
> string_t f() { return string_t("x"); }
> string_t f(const string_t& a) { return a + string_t("x"); }
> string_t f() { return func_in_another_file(); }
> nullable<string_t> f(const nullable<string_t>& a) { return null; }
> ```

## NOTE-040

**Agreed subset (2026-10-02):** explicit parameter types are mandatory and have no
`auto` fallback. Named functions also require explicit return types. Legacy annotation
conflicts and explicit reference returns remain separate review items. The imported
inferred `const string_t&` return example is not adopted; ordinary string returns are values.

**Source:** [generators/php/specs/rules.md:827](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Explicit signatures and ordinary value returns are
proved for the current slice. Legacy annotation conflicts and explicit reference
returns remain deferred. Imported prose follows, not current implementation status.

> ## 11. Function declaration rules
>
> ### 11.1 Parameters
> - Function and method parameters must have explicit types
> - Missing parameter type -> error
> - Parameter type fallback to `auto` is forbidden
> - If both a native PHP type and a supported doc-comment type are present, emit an error
>
> ### 11.2 Representative forms
> ```cpp
> int_t f(int_t a) { return a; }
> const string_t& f(const string_t& a) { return a; }
> string_t f() { return string_t("x"); }
> ```
