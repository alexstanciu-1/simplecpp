# 05. Null, mixed, wrappers and conversions
Doc Status: planning

> **Important: LLVM is deferred by default. Work on the direct LLVM backend only when the user explicitly requests it. The v0.2 default scope is Frontend + C++ S2S.**

[Catalog and workflow](README.md) · [Source merge ledger](source_merge.json)

Requires typed values and callable contracts. Review each conversion boundary and its runtime failure behavior.

Order is a discussion sequence, not a claim that every row is a prerequisite. Split combined examples before implementation.

## Handoff from chapter 04 — 2026-10-02

[Chapter 04's bounded first pass is complete](04_functions.md#positive-pass-conclusion--2026-10-02).
Reuse explicit signature preparation, parameter storage, argument/return conversions,
value/reference boundaries and default ownership. Nullable syntax/values and wrapper
boundaries are not implied by successful scalar or variadic tests. Chapter 04's
[native compiler checkpoint](../portability/conversion_review.md) now passes,
including PHP/native C++ parity and generated-program execution. LLVM and legacy
STAN remain excluded; broader nullable/wrapper behavior is not certified.

Proposed discussion groups (not implementation approval or new semantic decisions):

1. **Typed null and nullable foundation:** LIT-NULL-001, TYPE-VAR-002,
   FUNC-NULLABLE-001, NULL-CHECK-001/002 and relevant NOTE-036/037 rules. Agree
   contextual null typing, present/absent values and callable conversions together.
2. **Presence and fallback:** VAR-ISSET-001, VAR-EMPTY-001, EXPR-COALESCE-001 and
   NOTE-045. Keep null checks distinct from truthiness; scalar Elvis already works.
3. **Wrapper/mixed boundaries:** TYPE-VAR-006B, NOTE-005/052/063 and applicable
   NOTE-010 rules. Review nesting, extraction and failure behavior from owning specs;
   do not assume all wrapper families belong in one implementation slice.
4. **Conversion reconciliation:** retain proved scalar CAST-INT/FLOAT/BOOL/STRING
   behavior, then discuss CAST-OBJECT-001 and remaining NOTE-015/010 mappings with
   the relevant type owners. No automatic expansion of scalar conversion rules.

Start discussion with group 1. The imported `$a = null` example is not an instruction
to infer a nullable type from an untyped null. No Chapter 05 code or new support claim
is introduced by this handoff.

## Nullable model foundation — 2026-10-03

Implemented the agreed model-only slice: `abstract_template_type_definition` owns
the shared recipe; final `template_type_definition` remains the ordinary case and
final `nullable_type_definition` implements `nullable_semantics`. Its payload query
requires an application of that same definition. Registration, template preparation,
arity/capability validation and canonical application interning share the abstract
path. Source spelling alone grants no nullable role; backend bindings are unchanged.

The five focused PHP suites (`type_catalog`, `constructed_type`, `conversions`,
`incremental_cpp`, `preparation_recovery`) and the portable type proof pass; the
proof's emitted C++ also compiles and executes successfully. Checks use
the shared runner with CLI fallback after sandbox FPM socket startup failed.
Evidence: `/tmp/my-try-nullable-model-20261003/` and
`/tmp/my-try-nullable-model-proof-20261003/`.
The portable proof is retained for future explicitly requested native checkpoints;
the compiler itself has not been rebuilt natively for this refactor.

That checkpoint was model-only; the following slice adds value operations.

## Nullable value boundaries — 2026-10-03

The agreed first pass supports scalar payloads (`bool`, integer families, `float`,
`string`). `?T` and `nullable<T>` resolve to the same canonical application.
An internal, non-storable null sentinel supplies literal identity; it has no source
type exposure or storage binding. `$x = null` still rejects without a destination.

- Typed empty construction/reset and exact-payload wrapping use destination conversions.
- Same-payload copying preserves presence and independent value state.
- Required assignment/argument/return extraction checks presence and throws
  `invalid_nullable_unwrap_empty`; failed assignment preserves its target.
- Explicit same-payload casts retain the runtime contract, including empty nullable
  string to `""`. Required string extraction still throws on absence.
- `=== null` / `!== null` work in either order, evaluate the nullable operand once,
  and test presence rather than payload truthiness. Reference parameters retain
  exact-storage rules; extraction never manufactures a reference.

Owners: `conversions/nullable.php`, `operators/nullable.php`, shared type/expression
preparation and prepared decisions consumed by C++ emission. Tests: `tests/nullable.py`
and the `nullable_*` exports in `tests/s2s.php`; incremental recovery and syntax/fact
cleanup are covered by `tests/nullable.php`. This is PHP-hosted compiler plus generated
C++ execution evidence, not a native compiler rebuild.

Focused checkpoint: nine PHP suites and 33 generated programs/probes pass in
`/tmp/my-try-nullable-values-20261003-final/` (12 tasks, CLI fallback because sandbox
FPM socket creation was unavailable).

Debt/non-goals: cross-payload lifting/conversion and wrapper-valued coalescing fallback,
keyed/property-path probes, nullable parameter defaults, flow narrowing, mixed and object/container
payload operations. Existing generic type formation does not imply those operations.
The runtime generic identity helper currently fails to instantiate nullable/null
comparison (its fallback attempts `null_t == null_t`). This slice emits the equivalent
prepared presence test; runtime-helper repair belongs to a separate owner/task.

## Nullable truthiness and coalescing — 2026-10-03

`Condition_Truthiness` now admits nullable scalar values for every existing condition
consumer: empty is false, otherwise the runtime applies payload truthiness. Thus
present false, zero, `""` and `"0"` are falsey without being absent. Explicit boolean
casts and required extraction are unchanged; there is no flow narrowing or new
nullable arithmetic/logical-operator admission.

Dedicated `coalesce_expression_node` syntax owns `left` and `fallback`. Shared
`Coalesce_Expression_Preparation` selects exact scalar result/conversion facts:
`nullable<T> ?? T -> T` and `T ?? T -> T`. The expression is an owned value, not a
reference. Parsing is right-associative, stronger than ternary and weaker than `||`.
Both operands are prepared and validated; execution evaluates the left once and
evaluates fallback only when the nullable left is absent. Present falsey payloads
are selected, never replaced. `$a ?? $b ?? 7` works when the inner expression yields
the required nonnullable scalar fallback.

The C++ backend renders explicit lazy control flow from these facts and reuses
checked extraction. Nullable conditions reuse the runtime condition helper with
its cast-header dependency. No runtime or legacy-generator changes are required.
Focused proofs live in `tests/nullable.php`, `tests/s2s.php` and `tests/nullable.py`:
condition consumers, falsey values, effect counts, lazy failures, precedence,
owned results, rejected fallback types, cleanup and incremental fallback changes.

Checkpoint: ten focused PHP suites and 62 generated C++ programs/probes pass in
`/tmp/my-try-coalesce-fourth-20261003/`, using the shared 12-slot runner with CLI
fallback after sandbox FPM startup failed. This is not a native compiler rebuild.

Still deferred: nullable fallback operands, cross-type joins, bare-null coalescing,
mixed/other wrapper families and `??=`.

## Nullable conditional results — 2026-10-03

Full ternary and Elvis share the existing conditional node and prepared branch
conversions. Conditional preparation joins both alternatives symmetrically: `T/T`
stays `T`; `nullable<T>/T`, `T/nullable<T>` and `nullable<T>/nullable<T>` yield
`nullable<T>` for exact supported scalar payloads. Selected scalar values are wrapped;
selected nullable values are copied with their presence intact. Empty selected results
do not throw until consumed at a required-value boundary.

Elvis captures the original condition type once, tests its truthiness, and converts
only the selected branch. This also covers scalar-left/nullable-fallback expressions.
All results are owned values, not references. `match` and `??` admission remain
unchanged. Bare-null branches, cross-payload joins and other wrappers/mixed remain debt.

Proofs in `tests/nullable.php` and `tests/s2s.php` cover both branch orders, exact result
types, independent copies, empty results, wrapping, scalar-left Elvis, lazy exceptions,
effect counts, downstream required extraction and incremental join failure/recovery.

Checkpoint: ten focused PHP suites and 81 generated C++ programs/probes pass in
`/tmp/my-try-nullable-joins-20261003-final/` using the shared 12-slot runner (CLI
fallback after sandbox FPM startup failed). The compiler was not rebuilt natively.

## Single-value probes — 2026-10-03

`value_probe_node` owns one operand and an `isset`/`empty` kind; these are language
expressions, not ordinary function-name lookups. `Value_Probe_Preparation` admits
supported scalar values, nullable scalars and literal null, retaining the selected
operation and boolean result. C++ calls the corresponding PHP runtime probe helper.

`isset` is false for null/empty nullable and true for present scalar values, including
false, zero and empty strings. `empty` is true for null/empty nullable, false, numeric
zero and `""`; **`empty("0")` is false**, the documented Simple C++ deviation from PHP.
Present nullable payloads use those same emptiness rules, never negated truthiness.
The operand is evaluated once; probes introduce no mutation, but explicit operand
side effects and exceptions retain normal expression behavior. No blanket exception
catching or implicit extraction is added.

Zero/multiple arguments and undeclared names reject. Direct keyed/property-path
operands, containers, mixed and other wrappers remain deferred; ordinary value
expressions do not acquire missing-path suppression. Supporting those families later
must follow the normative non-mutating probe contract, not ordinary required reads.
Tests cover the scalar matrix, sentinel states, one evaluation, non-mutation, runtime
error propagation, arity, syntax traversal/cleanup and incremental admission recovery.

Checkpoint: eleven focused PHP suites and 99 generated C++ programs/probes pass in
`/tmp/my-try-probes-20261003-final/` (shared 12-slot runner, CLI fallback after sandbox
FPM startup failed). No native compiler rebuild was performed.

## Progress

Edit these rows as work proceeds. Imported source support is recorded below, independently of this progress.

| Entry | Status | PHP input example | Frontend | C++ S2S | LLVM | Proof / blocker |
| --- | --- | --- | --- | --- | --- | --- |
| [LIT-NULL-001](#lit-null-001) | agreed | `$a = null;` | proved | proved | deferred | Untyped null rejects; typed nullable destination works |
| [VAR-ISSET-001](#var-isset-001) | agreed | `$a = isset($b);` | proved | proved | deferred | Single scalar/nullable/null value; path probes deferred |
| [VAR-EMPTY-001](#var-empty-001) | agreed | `$a = empty($b);` | proved | proved | deferred | Single scalar/nullable/null value; `"0"` is not empty |
| [EXPR-COALESCE-001](#expr-coalesce-001) | agreed | `$a = $b ?? $c;` | proved | proved | deferred | Exact scalar `T` or `nullable<T>` left; required scalar `T` fallback |
| [EXPR-ELVIS-001](#expr-elvis-001) | agreed | `$a = $b ?: $c;` | proved | proved | deferred | Matching scalar and nullable-scalar joins; other wrappers/mixed deferred |
| [CAST-INT-001](#cast-int-001) | agreed | `$a = (int)$b;` | proved | proved | deferred | [Prepared scalar matrix](../../tests/conversions.php); [PHP/native bytes and Clang execution](../portability/conversion_review.md) |
| [CAST-FLOAT-001](#cast-float-001) | agreed | `$a = (float)$b;` | proved | proved | deferred | [Prepared scalar matrix](../../tests/conversions.php); [PHP/native bytes and Clang execution](../portability/conversion_review.md) |
| [CAST-BOOL-001](#cast-bool-001) | agreed | `$a = (bool)$b;` | proved | proved | deferred | [Prepared scalar matrix](../../tests/conversions.php); [PHP/native bytes and Clang execution](../portability/conversion_review.md) |
| [CAST-STRING-001](#cast-string-001) | agreed | `$a = (string)$b;` | proved | proved | deferred | [Prepared scalar matrix](../../tests/conversions.php); [PHP/native bytes and Clang execution](../portability/conversion_review.md) |
| [CAST-OBJECT-001](#cast-object-001) | pending-discussion | `$a = (object)$b;` | unverified | unverified | deferred | — |
| [FUNC-NULLABLE-001](#func-nullable-001) | agreed | `function f(?int $a): ?int { return $a; }` | proved | proved | deferred | Same scalar payload; nullable defaults deferred |
| [TYPE-VAR-002](#type-var-002) | agreed | `$x ?string = null;` | proved | proved | deferred | Scalar nullable value boundary slice |
| [NULL-CHECK-001](#null-check-001) | agreed | `$x === null` | proved | proved | deferred | Both orders; single evaluation and presence |
| [NULL-CHECK-002](#null-check-002) | agreed | `$x !== null` | proved | proved | deferred | Both orders; false/zero/empty-string remain present |
| [TYPE-VAR-006B](#type-var-006b) | pending-discussion | Strict example to define (legacy spelling retained below) | unverified | unverified | deferred | Strict source adaptation; imported legacy form retained |
| [NOTE-005](#note-005) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-010](#note-010) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-015](#note-015) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-036](#note-036) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-037](#note-037) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-045](#note-045) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-052](#note-052) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-063](#note-063) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
## LIT-NULL-001

**v0.2 decision / target C++:** Non-storable null sentinel; an explicit scalar nullable
destination constructs an empty value. Untyped direct null assignment rejects.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:34](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = null;
```

**Existing C++ lowering / result**

`ERROR` for untyped direct null assignment

**Category:** Literal

**Rule kind:** rejection-with-exception

**Source support status:** supported-with-precondition

**Preconditions:** null must appear in a context whose emitted type supports null

**Normalized pattern:** `<var> = null`

**General rule:** Untyped direct `null` assignment remains rejected. Typed nullable value contexts and object-handle contexts may emit runtime `null` according to the explicit typing rules.

**Diagnostics:** Emit an error when `null` is used without an explicit nullable/value-supporting or object-handle typing context.

**Notes:** Null support is runtime-defined; this row covers the untyped direct-assignment case only.


## VAR-ISSET-001

**v0.2 decision / target C++:** One-value probe node; prepared scalar/nullable/null
admission and runtime `php::isset` semantics. Missing-name/path behavior is not added.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:51](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = isset($b);
```

**Existing C++ lowering / result**

```cpp
auto a = php::isset(b);
```

**Category:** Variable

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** valid expression in scope

**Normalized pattern:** `<var> = isset(<expr>)`

**General rule:** `isset(expr)` must be mapped to the PHP runtime via `php::isset(...)`, and assigned using `auto` on first assignment in scope.

**Diagnostics:** Behavior delegated to runtime.


## VAR-EMPTY-001

**v0.2 decision / target C++:** One-value probe node; prepared scalar/nullable/null
admission and runtime `php::empty` semantics. `empty("0")` is false, unlike PHP.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:52](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = empty($b);
```

**Existing C++ lowering / result**

```cpp
auto a = php::empty(b);
```

**Category:** Variable

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** valid expression

**Normalized pattern:** `<var> = empty(<expr>)`

**General rule:** `empty(expr)` must be mapped to the PHP runtime via `php::empty(...)`, and assigned using `auto` on first assignment in scope.

**Diagnostics:** Behavior delegated to runtime.


## EXPR-COALESCE-001

**v0.2 decision / target C++:** Dedicated lazy expression with prepared exact scalar
result/conversions: `nullable<T> ?? T` and `T ?? T` yield owned `T`. Broader imported
wrapper/mixed combinations remain deferred.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:89](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = $b ?? $c;
```

**Existing C++ lowering / result**

```cpp
auto a = php::coalesce_eval([&]() -> decltype(auto) { return b; }, [&]() -> decltype(auto) { return c; });
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** left/right pair must be covered by the runtime coalesce matrix

**Normalized pattern:** `<var> = <expr> ?? <expr>`

**General rule:** Null coalescing lowers through `php::coalesce_eval(...)` so wrapper unboxing and fallback normalization are resolved centrally in the runtime. The current matrix explicitly supports `mixed_t ?? T`, `T ?? mixed_t`, and `nullable<T> ?? mixed_t`, all normalized to a `mixed_t` result.

**Diagnostics:** Error if the runtime coalesce matrix does not define the operand pair.

**Notes:** Uses `isset`-style guard semantics inside the runtime helper.


## EXPR-ELVIS-001

**v0.2 decision / target C++:** The matching scalar/nullable-scalar subset shares
`conditional_expression_node` with full ternary; its true branch is absent.
A typed lambda saves the original left value once, tests shared truthiness, and
converts only the selected branch to the joined result type. Results are owned values.
See the [Chapter 03 slice](03_control_flow.md#agreed-conditional-expression-slice--2026-10-02)
for parser bounds. Other wrapper/mixed joins remain deferred; this does not close
the wider dynamic-boundary work. Imported lowering below is historical evidence.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:90](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = $b ?: $c;
```

**Existing C++ lowering / result**

```cpp
auto a = ([&]() -> auto { auto __scpp_cond_value = b; return php::ternary_eval([&]() -> decltype(auto) { return __scpp_cond_value; }, [&]() -> decltype(auto) { return __scpp_cond_value; }, [&]() -> decltype(auto) { return c; }); }());
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** condition/branch combination must be covered by the runtime ternary matrix

**Normalized pattern:** `<var> = <expr> ?: <expr>`

**General rule:** The elvis operator lowers through a temporary plus `php::ternary_eval(...)` so the left operand is evaluated once and branch normalization is centralized in the runtime.

**Diagnostics:** Error if the runtime ternary matrix does not define the resulting branch pair.

**Notes:** This removes the old duplicate-evaluation restriction for the current supported helper matrix.


## CAST-INT-001

**v0.2 decision / target C++:** `(int)` is one use of the general explicit-cast
expression, not an integer-specific AST node. Preparation resolves `int` through
the canonical type registry and asks the integer conversion policy for an explicit
decision after preparing the operand. Exact `int` identity emits only the operand;
other supported scalar sources emit:

```cpp
scpp::cast<scpp::int_t<>>(operand)
```

The runtime owns parsing, truncation, range and failure behavior. Unsupported
source families are rejected during semantic preparation. Fixed-width registered
integer targets use this same path.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:97](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = (int)$b;
```

**Existing C++ lowering / result**

```cpp
auto a = cast<int_t>(b);
```

**Category:** Cast

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** expression must be convertible to `int_t`

**Normalized pattern:** `<var> = (int)<expr>`

**General rule:** PHP `(int)` cast must be mapped to `cast<int_t>(...)`. C-style casts are not allowed.

**Diagnostics:** Error if generator emits C-style cast or conversion is not allowed.


## CAST-FLOAT-001

**v0.2 decision / target C++:** `(float)` uses the common explicit-cast expression,
canonical registry lookup and the floating-point target-family policy. Exact
floating identity emits only the operand; another supported scalar source emits:

```cpp
scpp::cast<scpp::float_t>(operand)
```

This intentionally resolves the imported row's stale `static_cast` example in
favor of its own stated runtime-cast rule. The runtime owns conversion behavior;
unsupported source families are rejected during preparation.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:98](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = (float)$b;
```

**Existing C++ lowering / result**

```cpp
auto a = static_cast<float_t>(b);
```

**Category:** Cast

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** expression must be convertible to `float_t`

**Normalized pattern:** `<var> = (float)<expr>`

**General rule:** PHP `(float)` cast must be mapped to `cast<float_t>(...)`.

**Diagnostics:** Error if cast is dropped or conversion is not allowed.


## CAST-BOOL-001

**v0.2 decision / target C++:** `(bool)` uses the common explicit-cast expression,
canonical registry lookup and the boolean target-family policy. Exact boolean
identity emits only the operand; another supported scalar source emits:

```cpp
scpp::cast<scpp::bool_t>(operand)
```

This intentionally resolves the imported row's stale `static_cast` example in
favor of its own stated runtime-cast rule. Truth conversion behavior belongs to
the runtime; unsupported source families are rejected during preparation.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:99](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = (bool)$b;
```

**Existing C++ lowering / result**

```cpp
auto a = static_cast<bool_t>(b);
```

**Category:** Cast

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** expression must be convertible to `bool_t`

**Normalized pattern:** `<var> = (bool)<expr>`

**General rule:** PHP `(bool)` cast must be mapped to `cast<bool_t>(...)`.

**Diagnostics:** Error if syntax is invalid or conversion is not valid.


## CAST-STRING-001

**v0.2 decision / target C++:** `(string)` uses the common explicit-cast expression,
canonical registry lookup and the string target-family policy. Exact string
identity emits only the operand; another supported scalar source emits:

```cpp
scpp::cast<scpp::string_t>(operand)
```

Formatting and failure behavior belong to the runtime. No implicit constructor
fallback is permitted, and unsupported source families are rejected during
semantic preparation.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:100](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = (string)$b;
```

**Existing C++ lowering / result**

```cpp
auto a = cast<string_t>(b);
```

**Category:** Cast

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** expression must use a runtime-supported explicit string cast pair

**Normalized pattern:** `<var> = (string)<expr>`

**General rule:** PHP `(string)` cast must be mapped to explicit `cast<string_t>(expr)`. The runtime owns the supported conversion pairs. Unsupported pairs must fail rather than falling back to implicit construction.

**Diagnostics:** Error if implicit `string_t(...)` construction is used for non-string inputs or conversion is unsupported.

**Notes:** Type-system exception rule.


## CAST-OBJECT-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:102](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = (object)$b;
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Cast

**Rule kind:** rejection

**Source support status:** rejected

**Normalized pattern:** `<var> = (object)<expr>`

**General rule:** Only `(object)[...]` is supported and lowers to runtime `dynamic_t` storage inside `mixed_t`. Other object casts remain unsupported.

**Diagnostics:** Emit `mixed_t{dynamic_(...)}` only for `(object)[...]`; reject all other `(object)` casts.

**Notes:** Source-language `stdClass` lowering is supported only through `new stdClass()` and `(object)[...]`.


## FUNC-NULLABLE-001

**v0.2 decision / target C++:** Explicit nullable scalar parameters/returns use the
shared exact-payload conversion path. Required extraction is checked; defaults deferred.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:144](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(?int $a): ?int { return $a; }
```

**Existing C++ lowering / result**

```cpp
nullable<int_t> f(nullable<int_t> a) { return a; }
```

**Category:** Function

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** inner type supported

**Normalized pattern:** `function <name>(?<type> <param>): ?<type> <block>`

**General rule:** PHP nullable types map to `nullable<T>` in Prism++. Parameter and return nullable types must be transformed consistently.

**Diagnostics:** Error if inner type unsupported or nullable mapping inconsistent between signature and body.

**Notes:** Variable names still follow `$` removal.


## TYPE-VAR-002

**Strict-mode PHP input example:** `$x ?string = null;`

This spelling is proved for scalar payloads and is canonically equivalent to
`nullable<string>`. Imported examples below remain reference material.

**v0.2 decision / target C++:** Explicit nullable scalar storage with destination-typed
empty construction, exact-payload wrapping and value copying.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:323](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$x /** ?string */ = null;
```

**Existing C++ lowering / result**

```cpp
nullable<string_t> x = null;
```

**Category:** Type system

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** explicit nullable value-like PHPDoc variable type present

**Normalized pattern:** `typed nullable value local variable`

**General rule:** Nullable value-like local variables lower to `nullable<T>` and may be initialized with runtime `null`.

**Diagnostics:** Error if a nullable wrapper is omitted for a nullable value-like type.

**Notes:** Canonical null form is runtime `null`.


## NULL-CHECK-001

**v0.2 decision / target C++:** Prepared nullable presence test, negated for `=== null`;
either operand order evaluates the nullable expression once.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:344](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$x === null
```

**Existing C++ lowering / result**

```cpp
php::is_null(x)
```

**Category:** Null

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** comparison is strict-null check

**Normalized pattern:** `<expr> === null`

**General rule:** Strict null checks must lower to the configured runtime helper rather than direct C++ comparison syntax.

**Diagnostics:** Error if the generator emits `== null` or `== nullptr` for this normalized form.

**Notes:** Applies to object handles and nullable value-like wrappers.


## NULL-CHECK-002

**v0.2 decision / target C++:** Prepared nullable presence test for `!== null`;
present false/zero/empty-string payloads are not null.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:345](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$x !== null
```

**Existing C++ lowering / result**

```cpp
php::not_null(x)
```

**Category:** Null

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** comparison is strict-not-null check

**Normalized pattern:** `<expr> !== null`

**General rule:** Strict non-null checks must lower to the configured runtime helper rather than direct C++ comparison syntax.

**Diagnostics:** Error if the generator emits `!= null` or `!= nullptr` for this normalized form.

**Notes:** Mirrors the strict-null check rule.


## TYPE-VAR-006B

**Strict-first scope:** Define the strict source form and verify its type/ownership contract when this feature is discussed. The imported annotation spelling below is legacy reference only.

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:357](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$p /** weird<Box> */ = null;
```

**Existing C++ lowering / result**

generator diagnostic

**Category:** Type system

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** explicit local type intent is present but unsupported or malformed

**Normalized pattern:** `explicit typed local rejection`

**General rule:** Explicit local type intent must either lower exactly according to the supported type grammar or fail generation. The generator must not silently fall back to `mixed_t` when the user wrote an explicit wrapper or annotated type form it does not understand.

**Diagnostics:** Error if unsupported explicit type syntax is accepted by degrading to `mixed_t` or `auto`.

**Notes:** The only remaining permissive null fallback is an untyped assignment such as `$x = null;`.


## NOTE-005

**Source:** [generators/php/specs/rules_catalog.md:369](../../../../generators/php/specs/rules_catalog.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## Wrapper nesting constraints
>
> - Ownership/value wrappers may not be nested. The following are invalid and must fail generation: `value<value<T>>`, `value<?value<T>>`, `shared<shared<T>>`, `unique<unique<T>>`, and any mixed wrapper-inside-wrapper form such as `value<shared<T>>` or `value<?shared<T>>`.

## NOTE-010

**Source:** [generators/php/specs/rules.md:58](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## 2. Type System
>
> ### Mandatory
> - function and method parameters must be typed explicitly
> - parameter typing must come from exactly one source: native PHP type or supported doc-comment type
> - native PHP type plus supported doc-comment type on the same parameter/property is an error
> - class properties must be typed explicitly
> - return types must be explicit
>
> ### Mapping
> - `int` â†’ `int_t`
> - `float` â†’ `float_t`
> - `bool` â†’ `bool_t`
> - `string` â†’ `string_t`
> - `?T` â†’ `nullable<T>` for value-like types
> - class / interface / abstract object types â†’ `shared_p<T>`
> - `?ClassType` / `?InterfaceType` / `?AbstractType` â†’ `shared_p<T>`
> - object nullability does not currently change the emitted C++ type; `A` and `?A` both emit `shared_p<A>` for now
>
> ### Returns
> - non-void functions must return a value on all paths
> - void functions cannot return a value
>
> ### Closures and callable locals
> - Closure expressions are concrete callable values and lower to native C++ lambdas.
> - Explicit strict callable locals such as `$f function<int()> = function () use ($a) { return $a; };` lower to `std::function<int_t()>` storage and provide the expected closure signature when the initializer omits a return type.
> - A direct local assignment from a closure with a complete syntactic signature may synthesize the callable storage type, for example `$f = function (int $x): int { return $x; };` lowers to `std::function<int_t(int_t)>`.
> - Closure `use ($a)` captures the current value at closure creation time; `use (&$a)` captures by reference.
> - Closure return annotations owned by the scanner, including forms such as `: vector<int>`, are valid closure return types and must not depend on php-ast doc-comment ownership.
> - When a closure return type maps to `vector_t<T>`, a returned positional array literal lowers as a typed `vector_t<T>{...}` literal.
> - Untyped closure assignment with a value return is rejected unless the closure has an explicit return type or the target provides an expected callable signature.
> - Closures cannot be stored in dynamic/untyped containers or array slots; assign them to concrete callable locals instead.
> - A mismatch between an explicit callable local signature and an explicit closure signature is a STAN-owned diagnostic before build; the S2S generator should not perform broad semantic callable compatibility inference.
>
>
> ### Variable Typing
> - explicit scanner-owned inline slot type annotations are authoritative when present
> - accepted inline comment forms are limited to recognized typed slots, not generic PHPDoc tags
> - local variables keep the strict immediate-after-variable form only
> - valid local form example: `$x /** string */ = "test";`
> - parameters and properties additionally support the leading attached form such as `function f(/** vector<int> */ $list): void {}` and `public /** int */ $x;`
> - properties also support the immediate trailing form such as `public $items /** vector<int> */ = [];`
> - function-like returns support the immediate post-signature form such as `function build() /** vector<int> */ { ... }` and `fn(/** int */ $x) /** function<int(int)> */ => ...`
> - detached forms such as `/** @var vector<int> */ $items = [];` are not accepted as typed-slot metadata
> - class constants support the leading attached form such as `const /** int */ X = 1;`
> - constant declarations fall back to initializer-based type deduction in emitted C++ (`const auto ... = ...`)
> - detached or non-adjacent type comments remain invalid
> - `$x /** string */ = "test";` â†’ `string_t x("test");`
> - `$x /** ?string */ = "test";` â†’ `nullable<string_t> x("test");`
> - `$x /** ?string */ = null;` â†’ `nullable<string_t> x = null;`
> - `$x /** A */ = new A();` â†’ `shared_p<A> x = create<A>();`
> - `$x /** ?A */ = null;` â†’ `shared_p<A> x = null;`
> - `$x /** value<Point> */ = new Point(1, 2);` â†’ `value_p<Point> x = value<Point>(static_cast<int_t>(1), static_cast<int_t>(2));`
> - `$x /** weak<A> */ = null;` â†’ `weak_p<A> x = null;`
> - `$x /** weakref<A> */ = null;` â†’ `weak_p<A> x = null;`
> - `$x /** unique<A> */ = null;` â†’ `unique_p<A> x = null;`
> - `$x /** shared<A> */ = null;` â†’ `shared_p<A> x = null;`
> - `$x /** ref int */ = &$y;` â†’ `int_t& x = y;`
> - `/** ref Point */` locals lower directly to `shared_p<Point>&` when `Point` lowers to an object handle
> - `ref` lowering is intentionally a reduced write-through alias feature built on native C++ references; rebinding-through-alias and PHP-style alias-preserving `unset` are out of scope
>
> - explicit inline object/value storage is opt-in only through the PHPDoc forms `value<T>` and `value<?T>`
> - object-handle local wrappers are expressed canonically as `shared<T>` and `unique<T>`
> - `value<T>`, `value<?T>`, `shared<T>`, and `unique<T>` are currently supported for typed local variables only
> - legacy `value T` is still accepted temporarily for compatibility, but `value<T>` and `value<?T>` are the canonical forms going forward
> - strict local wrapper shortcuts are supported only for direct constructor assignment: `/** value */`, `/** shared */`, and `/** unique */` must appear on a typed local whose initializer is exactly `new ClassName(...)`; the generator must immediately normalize them to `value<ClassName>`, `shared<ClassName>`, or `unique<ClassName>`. After normalization, explicit wrapper forms such as `value<T>`, `shared<T>`, and `unique<T>` initialized from `new U(...)` must validate that `T` and `U` match exactly.
> - bare local wrapper shortcuts must be rejected when the initializer is not a direct `new ClassName(...)` expression, when the class target is not statically known, or when the assignment shape is not a normal direct local assignment
> - when a `value<T>` local is initialized from `new T(...)`, generation must use `value<T>(...)` instead of `create<T>(...)`
> - when a `value<?T>` local is initialized from `new T(...)`, generation must lower to `nullable<T>{T(...)}`; this is the canonical nullable inline-object lowering and must not silently degrade to `mixed_t`
> - when a `unique<T>` local is initialized from `new T(...)`, generation must use `::scpp::unique<T>(...)` instead of `create<T>(...)`
> - explicit wrapper locals initialized from `new ...` must reject constructor-target mismatches; for example, `/** value<A> */ = new B()` is a generator error and must not silently default-initialize `A`
> - `value<T>` locals remain object-like at the usage surface: property and method access must continue to lower through `->`, for example `$x /** value<MyClass> */ = new MyClass(); $x->property_1 = 10;` lowers conceptually to `value_p<MyClass> x = value<MyClass>(); x->property_1 = static_cast<int_t>(10);`
> - `value<?T>` locals remain object-like at the usage surface through nullable dereference: for example `$x /** value<?MyClass> */ = null; $y /** value<?MyClass> */ = new MyClass();` lowers conceptually to `nullable<MyClass> x = null; nullable<MyClass> y{MyClass()};`, and `$x->prop` must fail at runtime while `$y->prop` must behave like `MyClass`
> - `shared_p<MyClass> x = null` is not a valid nullable-wrapper test because it exercises handle-null semantics, not `nullable<T>` semantics
> - explicit type intent must never silently fall back to `mixed_t`; unsupported or malformed explicit type syntax such as an unknown wrapper form must fail generation with a diagnostic
> - explicit reference lowering over handle-like wrappers must emit a native handle reference (`shared_p<T>&`, `unique_p<T>&`, `weak_p<T>&`) instead of creating nested pointer/reference layers
>
> ### Untyped Variable Initialization
> - untyped variables may still lower to explicit runtime-wrapped expressions
> - `$x = "test";` â†’ `auto x = string_t("test");`
> - constructor selection, conversion resolution, and overload resolution remain the C++ compiler's responsibility
>
> ### Passing and Return Conventions
> - `int_t`, `float_t`, and `bool_t` use normal value semantics for parameters and returns unless explicit `&` is present
> - `string_t` and `vector_t` default to `const &` for parameters and return by value
> - explicit PHP `&` disables the default `const &` convention and must be emitted as a mutable reference
> - class/interface/abstract object types are emitted as `shared_p<T>` handles and are passed and returned by handle value
> - object nullability intent (`T` vs `?T`) does not currently change the emitted object-handle type
> - class/interface object types remain pointer-like in use (`->`)
> - user PHP classes must not be stored by value in generated code
> - runtime nullability enforcement for non-nullable object parameters/properties is deferred; current code generation keeps `T` and `?T` identical for object-handle types and relies on future injected checks
> - raw `&`, `&&`, and `*` must not appear inside source type definitions or PHPDoc type comments; explicit references are represented only by PHP reference syntax and typed local `ref T` annotations
>
> ---

## NOTE-015

**Source:** [generators/php/specs/rules.md:225](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## 7. Casting
>
> - scalar casts use `static_cast<T>(...)`
> - string conversion uses explicit `cast<string_t>(...)` only for supported pairs
> - in all other cases string conversion uses `string_t(...)`
> - C-style casts are allowed only for non-literals when required as a temporary form
>
> ---

## NOTE-036

**Source:** [generators/php/specs/rules.md:708](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## 7. Null and nullable rules
>
> ### 7.1 Typed local predeclaration
>
> Safe v1 supports explicit local declarations without initialization when the local has an explicit type annotation.
>
> Example:
>
> ```php
> $f /** function<int(int)> */;
> ```
>
> This form exists so an outer block can predeclare a local that will later be assigned inside child blocks while still respecting block-local visibility. Bare `callable` is not sufficient; use a concrete `function<return_type(arg_types)>` annotation.
>
> The same concrete `function<return_type(arg_types)>` annotation form is also accepted on closure parameters when PHP syntax cannot express a native callable signature directly, for example `function (/** function<int(int)> */ $fn, int $x): int { ... }`.
>
> Safe v1 also accepts scanner-owned shorthand type sites that are normalized before `php-ast` parsing, for example:
>
> ```php
> $count int = 0;
> public $items vector<string> = [];
> function build($items vector<string>): vector<string> { ... }
> $make = fn($x int) function<function<int(int)>(int)> =>
> 	fn($y int): int => $x + $y;
> ```
>
> The pre-tokenizer normalizes those surfaces into parseable PHP source while separately preserving explicit site metadata for locals, properties, params, and function-like return slots. Because return-site ownership is scanner-owned, nested closure or arrow return annotations no longer rely on accidental raw `php-ast` doc-comment attachment. Arrow functions (`fn (...) => expr`) are also supported in Safe v1. php-ast exposes them as `AST_ARROW_FUNC` without an explicit `use (...)` list, so the generator infers implicit by-value captures from referenced outer locals and lowers them to native C++ lambdas with value captures.
>
> ### 7.2 Untyped null assignment
> Direct untyped `null` assignment is not allowed:
> ```php
> $a = null;
> ```
> -> error
>
> ### 7.2 Nullable mapping
> - `?T` -> `nullable<T>`
>
> Examples:
> - `?int` -> `nullable<int_t>`
> - `?string` -> `nullable<string_t>`
>
> ### 7.3 Typed null
> Allowed:
> ```cpp
> nullable<int_t> a = null;
> ```
>
> ### 7.4 Nullable return of null
> Allowed.

## NOTE-037

**Source:** [generators/php/specs/rules.md:759](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## 8. Type mapping
>
> - `int` -> `int_t`
> - `float` -> `float_t`
> - `bool` -> `bool_t`
> - `string` -> `string_t`
> - `vector` -> `vector_t`
> - `void` -> `void`
>
> Not implemented yet:
> - PHP `array`

## NOTE-045

**Source:** [generators/php/specs/rules.md:997](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## 16. Null coalescing and ternary-family lowering
>
> Accepted lowering for null coalescing:
> ```php
> $b ?? 1
> ```
>
> becomes:
> ```cpp
> php::coalesce_eval(
> 	[&]() -> decltype(auto) { return b; },
> 	[&]() -> decltype(auto) { return static_cast<int_t>(1); }
> )
> ```
>
> Accepted lowering for ternary:
> ```php
> $a ? $b : 0
> ```
>
> becomes:
> ```cpp
> php::ternary_eval(
> 	[&]() -> decltype(auto) { return a; },
> 	[&]() -> decltype(auto) { return b; },
> 	[&]() -> decltype(auto) { return static_cast<int_t>(0); }
> )
> ```
>
> Accepted lowering for elvis:
> ```php
> $a ?: 0
> ```
>
> becomes:
> ```cpp
> ([&]() -> auto {
> 	auto __scpp_cond_value = a;
> 	return php::ternary_eval(
> 		[&]() -> decltype(auto) { return __scpp_cond_value; },
> 		[&]() -> decltype(auto) { return __scpp_cond_value; },
> 		[&]() -> decltype(auto) { return static_cast<int_t>(0); }
> 	);
> }())
> ```
>
> Rules:
> - current lowering emits helper calls here rather than solving branch/result typing inline
> - `??` and `?:` use different runtime result matrices, but they do share one wrapper-normalization rule for PHP-visible null / bool / dynamic semantics
> - the current `??` matrix includes explicit dynamic-carrier entries for `mixed_t ?? T`, `T ?? mixed_t`, and `nullable<T> ?? mixed_t`; these normalize to `mixed_t` rather than attempting a typed payload result
> - `??` auto-unpacks only the approved wrapper families (`nullable<T>`, `result<T>`, and `result_or_false<T>`) to their usable value domain; in the current version `result_or_bool<T>` is rejected by the runtime helper on either side of coalesce because current lowering does not resolve that row statically here, while `?:` follows its own truthiness-based wrapper policy.
> - helper lambdas preserve lazy right/branch evaluation
> - elvis lowering must evaluate the left operand exactly once
> - unsupported operand/branch combinations must fail deterministically at compile time in the runtime helper layer
> - fallback literals still follow normal literal conversion rules
>
> See also: `specs/conditional_expression_matrix.md`.

## NOTE-052

**Source:** [generators/php/specs/rules.md:1196](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## Wrapper nesting constraints
>
> - Ownership/value wrappers may not be nested. The following are invalid and must fail generation: `value<value<T>>`, `shared<shared<T>>`, `unique<unique<T>>`, and any mixed wrapper-inside-wrapper form such as `value<shared<T>>`.
>
>
> ### String interpolation limitations
>
> Inside interpolated strings ("..."), only simple expressions are allowed.
>
> Supported:
> - `$var`
> - `$obj->prop`
> - `$arr[index]`
> - `$obj->method()`
>
> Not supported:
> - arithmetic expressions (`{$a + $b}`)
> - ternary expressions (`{$a ? $b : $c}`)
> - null coalescing expressions (`{$a ?? $b}`)
>
> This matches PHP behavior.

## NOTE-063

**Source:** [generators/php/specs/rules.md:1317](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## Explicit result_or_false<T> and result<T> type intent
>
> - `result_or_false<T>`, `result_or_bool<T>`, and `result<T>` are explicit type-intent wrappers only; the generator must not infer them from PHP unions automatically in this pass.
> - For `result_or_false<bool_t>`, plain `false` remains a wrapped payload value; the explicit false-sentinel forms are `false_sentinel`, `null`, and `nullopt`. The generator must not rewrite a typed `false` payload into the sentinel for this specialization.
> - explicit type intent must never silently degrade to `mixed_t`; unsupported or malformed `result_or_false<T>` / `result_or_bool<T>` / `result<T>` syntax is a generator error.
> - wrapper lowering uses the canonical mapped inner runtime type, so `result_or_false<MyBox>` lowers to `result_or_false<shared_p<MyBox>>`, `result_or_bool<MyBox>` lowers to `result_or_bool<shared_p<MyBox>>`, while `result<int>` lowers to `result<int_t>`.
> - `$result->error()->...` is the supported error-access surface for `result<T>` and must lower to the wrapper method rather than a payload property named `error`.
> - `take(...)` is a reserved runtime helper name in the PHP-facing source subset. It lowers to `php::take(...)` when no user-defined function named `take` is resolved.
> - `take($value, $source)` is valid only for source expressions typed as `nullable<T>` or `result_or_false<T>`.
> - `take($value, $error, $source)` is valid only for source expressions typed as `result<T>`.
> - `take($value, $bool, $source)` is valid only for source expressions typed as `result_or_bool<T>`.
> - `take(...)` output arguments must be simple local variables in v1. Wrong arity, wrong output type, or a non-wrapper source is a compile-time generator error when the source or output type is known.
> - `take(...)` evaluates its source expression exactly once and returns `bool_t`; for `result_or_bool<T>`, the helper returns `true` for both wrapped-value and bool-true states so mysqli-style APIs remain representable.
> - `take(...)` is the preferred explicit payload-extraction form for `result*<T>` wrappers because the generator does not perform symbol-resolution-driven wrapper inference.
