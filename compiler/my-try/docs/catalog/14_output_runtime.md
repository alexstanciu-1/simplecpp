# 14. C++ artifacts, runtime integration and diagnostics
Doc Status: planning

> **Important: LLVM is deferred by default. Work on the direct LLVM backend only when the user explicitly requests it. The v0.2 default scope is Frontend + C++ S2S.**

[Catalog and workflow](README.md) · [Source merge ledger](source_merge.json)

Cross-cutting: use a minimal output skeleton from the first runnable example; grow file splitting and incremental publication as dependencies appear.

Order is a discussion sequence, not a claim that every row is a prerequisite. Split combined examples before implementation.

## Progress

Edit these rows as work proceeds. Imported source support is recorded below, independently of this progress.

| Entry | Status | PHP input example | Frontend | C++ S2S | LLVM | Proof / blocker |
| --- | --- | --- | --- | --- | --- | --- |
| [CLASS-SPLIT-001](#class-split-001) | pending-discussion | `class A { function f(): int { return 1; } }` | unverified | unverified | deferred | Source variants need reconciliation |
| [GEN-QUAL-001](#gen-qual-001) | pending-discussion | `$x = ["a"]; echo $x[0];` | unverified | unverified | deferred | — |
| [RUNTIME-REF-001](#runtime-ref-001) | pending-discussion | future Prism++ `&x` where `x` is `shared_p<T>`, `unique_p<T>`, or `weak_p<T>` | unverified | unverified | deferred | — |
| [EMIT-FILE-001](#emit-file-001) | pending-discussion | `input.phs` | unverified | unverified | deferred | — |
| [EMIT-FILE-002](#emit-file-002) | pending-discussion | generated header/source for one PHP++ input file | unverified | unverified | deferred | — |
| [EMIT-FILE-003](#emit-file-003) | pending-discussion | `shared_p<B> b;` in a header declaration | unverified | unverified | deferred | — |
| [EXPR-EMIT-001](#expr-emit-001) | pending-discussion | `$x = ($a + 1) * f($b);` | unverified | unverified | deferred | — |
| [META-INTENT-001](#meta-intent-001) | pending-discussion | `function f(A $arg) {}` and `function f(?A $arg) {}` | unverified | unverified | deferred | — |
| [NOTE-001](#note-001) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-002](#note-002) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-003](#note-003) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-004](#note-004) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-007](#note-007) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-008](#note-008) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-009](#note-009) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-014](#note-014) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-020](#note-020) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-023](#note-023) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-024](#note-024) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-026](#note-026) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-028](#note-028) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-030](#note-030) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-032](#note-032) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-041](#note-041) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-042](#note-042) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-046](#note-046) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-048](#note-048) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-049](#note-049) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-057](#note-057) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-061](#note-061) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-062](#note-062) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-064](#note-064) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
## CLASS-SPLIT-001

**v0.2 decision / target C++:** Pending discussion.

Different source versions share this ID. Preserve both until their differences are discussed; neither is silently selected as the v0.2 decision.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:23](../../../../generators/php/specs/catalog.md)

**PHP input example**

```php
class A { function f(): int { return 1; } }
```

**Existing C++ lowering / result**

`A.hpp` contains declarations; `A.cpp` contains out-of-line bodies

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** class is user-defined PHP class

**Normalized pattern:** `class <name> { <members> }`

**General rule:** Each PHP class lowers to two generated files: header for declarations and source for method / constructor / destructor bodies.

**Diagnostics:** Error if bodies are emitted inline contrary to the split policy.

**Notes:** This is the approved class generation model.

### Imported version 2

**Source:** [generators/php/specs/rules_catalog.md:186](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
class A { function f(): int { return 1; } }
```

**Existing C++ lowering / result**

`A.hpp` contains declarations; `A.cpp` contains out-of-line bodies

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** class is a user-defined PHP class

**Normalized pattern:** `class <name> { <members> }`

**General rule:** Each user-defined PHP class lowers to two generated files: header for declarations and source for constructor, destructor, and method bodies.

**Diagnostics:** Error if bodies are emitted inline contrary to the split policy.

**Notes:** Preserves the approved header/source class model.


## GEN-QUAL-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:25](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$x = ["a"]; echo $x[0];
```

**Existing C++ lowering / result**

```cpp
mixed_t x = table_(table_item_(string_t("a"))); echo x.get(static_cast<int_t>(0));
```

**Category:** Generator

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** emitted code is inside a generated `.cpp` namespace block

**Normalized pattern:** `runtime/helper emission inside generated source`

**General rule:** Generator MUST NOT emit fully-qualified names like `::scpp` or `::scpp::php` inside generated expression/type code because the generated source block already injects `using namespace ::scpp;``.

**Diagnostics:** Error if generated expression/type code contains rooted `::scpp` / `::scpp::php` helper references.

**Notes:** Allowed exceptions are the generated using-directives themselves and explicit import-lowering forms such as `using ::scpp::A::B::f;`.


## RUNTIME-REF-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:361](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

future Prism++ `&x` where `x` is `shared_p<T>`, `unique_p<T>`, or `weak_p<T>`

**Existing C++ lowering / result**

native handle reference lowering such as `shared_p<T>&`

**Category:** Runtime

**Rule kind:** general rule

**Source support status:** supported

**Preconditions:** operand is already handle-like

**Normalized pattern:** `reference to handle-like wrapper`

**General rule:** An explicit reference applied to a handle-like wrapper must lower to a native reference over that existing handle category instead of creating an extra wrapper layer.

**Diagnostics:** Error if generation introduces wrapper-of-wrapper pointer/reference layering or emits `&&` / `*` in a type definition.

**Notes:** This is a runtime/generation contract for the native-reference lowering path.


## EMIT-FILE-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:362](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
input.phs
```

**Existing C++ lowering / result**

`input.hpp` + `input.cpp`

**Category:** Emission

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** one PHP++ input file is being transpiled

**Normalized pattern:** one-input-file emission pair

**General rule:** One PHP++ input file generates one header file and one source file. Emission is organized per input file, not per class.

**Diagnostics:** Error only if the configured output pair cannot be materialized.

**Notes:** Keeps file emission simple and predictable.


## EMIT-FILE-002

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:363](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

generated header/source for one PHP++ input file

**Existing C++ lowering / result**

broad runtime/project header included

**Category:** Emission

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** normal file emission

**Normalized pattern:** broad runtime include

**General rule:** Generated files may always include a broad runtime/project header; include minimization is not required.

**Diagnostics:** No generator-side optimization pass is required.

**Notes:** Clean architecture is preferred over include micro-optimization.


## EMIT-FILE-003

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:364](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

`shared_p<B> b;` in a header declaration

**Existing C++ lowering / result**

`class B;` may be emitted instead of a heavier include

**Category:** Emission

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** the referenced type is used only in a trivial obvious declaration context through `shared_p<T>`

**Normalized pattern:** trivial forward declaration case

**General rule:** Forward declarations may be used only in trivial obvious declaration contexts; the generator must not build a dependency solver for include optimization.

**Diagnostics:** Fall back to the simpler include-based path when the case is not trivially safe.

**Notes:** This keeps dependency handling intentionally simple.


## EXPR-EMIT-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:365](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$x = ($a + 1) * f($b);
```

**Existing C++ lowering / result**

structural C++ emission preserving grouping

**Category:** Expression

**Rule kind:** general rule

**Source support status:** supported

**Preconditions:** source form is otherwise supported

**Normalized pattern:** structural expression emission

**General rule:** Expression lowering remains structural. The generator must not try to behave like a semantic expression compiler.

**Diagnostics:** Reject only when a generation rule explicitly marks the source form unsupported.

**Notes:** Runtime/operator behavior is delegated.


## META-INTENT-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:366](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

`function f(A $arg) {}` and `function f(?A $arg) {}`

**Existing C++ lowering / result**

same emitted handle type, different recorded intent metadata

**Category:** Metadata

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** object type nullability intent appears in source

**Normalized pattern:** deferred intent metadata

**General rule:** Source-level intent that is not yet enforced at generation time may be recorded as metadata without changing the current emitted C++ form.

**Diagnostics:** No emitted type difference is required while enforcement remains deferred.

**Notes:** Example: object nullability intent may be recorded for future runtime check injection.


## NOTE-001

**Source:** [generators/php/specs/catalog.md:1](../../../../generators/php/specs/catalog.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> # Prism++ â€“ Rule Catalog
> Doc Status: normative
> This catalog reflects the rules approved in the current session and is aligned with the current project direction.
>
> Columns:
> - **ID**: stable catalog identifier
> - **Category**: major area
> - **Rule kind**: generation / rejection / generation-with-precondition
> - **PHP input example**: representative source form
> - **Expected Prism++ / Result**: expected lowering or rejection
> - **Status**: supported / rejected / supported-with-precondition
> - **Preconditions**: explicit conditions required for the rule
> - **Normalized pattern**: abstract source pattern
> - **General rule**: canonical rule statement
> - **Diagnostics**: generator behavior on failure
> - **Notes**: clarifications
>
> - Untyped PHP array literals are a deliberate declaration-time exception: first assignment must declare them as `mixed_t`, not `auto`, so the resulting local uses the fat-value runtime surface immediately.

## NOTE-002

**Source:** [generators/php/specs/catalog.md:107](../../../../generators/php/specs/catalog.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## Notes
>
> - This catalog intentionally reflects only the rules explicitly approved in the current session.
> - The generator remains local and syntactic: no cross-file semantic checking, no hierarchy validation, no inference of overrides or virtual dispatch beyond explicit source cues.
> - Class generation assumes a handle-like object model for user PHP classes: instance access uses `->`, and object creation uses `create<T>(...)`.
>
>
> ## Array rules added in the current stage
>
> The current catalog now keeps generalized array rules instead of one row per trivial permutation.
>
> Core rule families:
> - untyped literals lower through `table_`, `table_item_`, and `table_kv_`, producing `mixed_t`-based PHP-array values
> - typed `vector<T>` literals lower to `vector_t<T>{...}`
> - typed `hash<T>` literals lower to typed `hash_t<T>` construction/update code
> - PHP array reads lower directly to `operator[]` on `hash_t` / `mixed_t`
> - keyed writes lower to direct `operator[]` assignment
> - append writes lower to `append(...)`; simple right-hand sides inline directly, while non-trivial right-hand sides may spill into a temporary
> - `unset($a[k])` lowers to `remove(k)` and remains a no-op on missing keys
> - `isset($a[k])` lowers through the runtime `isset(...)` helper with null-sensitive behavior
> - `empty($a[k])` lowers through the runtime `empty(...)` helper for the resulting value under the reduced Prism++ emptiness rule
> - the normative cross-runtime contract is defined in `specs/count_empty_isset_contract.md`
>
>
> ## Nested table dim support
>
> - Nested table dim reads chain through `get(...)` / `_find_val(...)` so `$x["inner"][0]` stays non-mutating on the read path.
> - Nested table dim writes keep the full lvalue chain on mutating `operator[]` access, so `$x[0]["name"] = "first";` does not route intermediate segments through `get(...)`.
> - Nested append on a table-valued slot is supported through chained mutating access plus `append(...)` on `mixed_t` / `hash_t<mixed_t>`; the prefix of a nested append target stays on the mutating path (for example `$x["users"][] = $v;` â†’ `x[string_t("users")].append(...)`), while read-only nested access lowers through `get(...)` / `_find_val(...)`.
> - Table-valued assignments into table slots now use direct `mixed_t` assignment through the returned `operator[]` reference.
>
> ## Assignment-expression lambda fallback
>
> - Default rule: do not emit a helper lambda for ordinary assignment statements or simple assignment expressions.
> - Fallback rule: emit a helper lambda only in complex expression contexts where the generator must preserve PHP assignment-value semantics while also guaranteeing single evaluation, especially append expressions or larger composed expressions.
>
> - `FUNC-ARG-001`: direct DIM call arguments use the direct slot path `[]`; computed call arguments keep the normal expression/read path. By-value array args rely on runtime detach-on-write rather than generator-side `table_copy(...)`.
> - `REF-BIND-001`: `=&` binding from a direct DIM slot uses the mutable slot path `[]`, not the read path `.get(...)`, so the binding targets the real slot storage.
>
> ## Historical note â€” typed scalar by-reference proxy lowering
>
> Legacy helper/proxy infrastructure may still exist in the runtime, but it is not part of the supported safe subset. The current design direction is the native-reference safety rule documented in `specs/native_reference_safety.md`.

## NOTE-003

**Source:** [generators/php/specs/rules_catalog.md:1](../../../../generators/php/specs/rules_catalog.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> Doc Status: normative
>
>
> See `../../specs/spec_map.md` for document hierarchy, authority, and v1 conflict-resolution rules.
>
> Priority note for dynamic typed-destination bridging:
> - The authoritative v1 rule is `../../specs/dynamic_types.md` sections 1.2 and 1.3.
> - If a row here reaches a typed destination from `mixed_t`, current v1 behavior must preserve that bridge until generator parity exists for explicit cast insertion.
>
> # Prism++ â€“ Rule Catalog (Rebuild)
>
> This catalog is pre-seeded with common PHP constructs.
> The rows below were updated with the decisions made so far.
>
> Columns that remain intentionally incomplete for future collaborative work:
> - `Expected Prism++ / Result` for untouched rows
> - `Status` for untouched rows
> - `Preconditions` for untouched rows
> - `General rule` for untouched rows
> - `Diagnostics` for untouched rows
> - `Notes` for untouched rows

## NOTE-004

**Source:** [generators/php/specs/rules_catalog.md:346](../../../../generators/php/specs/rules_catalog.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## Notes
>
> - This file collapses old catalog permutations into normalized patterns.
> - The rows above are updated only where we made explicit decisions in the session.
> - Untouched rows remain as seed placeholders for future completion.

## NOTE-007

**Source:** [generators/php/specs/rules.md:1](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> Doc Status: normative
>
>
> See `../../specs/spec_map.md` for document hierarchy, authority, and v1 conflict-resolution rules.
>
> # Prism++ â€“ General Rules (Authoritative, Normalized)
>
> > Transitional implementation note: see `../../specs/mixed_boundary_transitional.md`.
>
> This document is the single source of truth for the supported subset.
>
> ---

## NOTE-008

**Source:** [generators/php/specs/rules.md:14](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## 0. Generator Responsibility Boundary
>
> The S2S generator is a deterministic structured code generator, not a semantic compiler.
>
> It performs only the checks required to emit configured C++ output reliably. Symbol resolution, type validation, inheritance validation, override validation, and other semantic compile-time checks are delegated to the C++ compiler unless a generation rule explicitly requires a local structural check.
>
> The generator must prefer deterministic syntactic lowering over semantic interpretation. If a supported source form can be lowered locally, it should be emitted. If the resulting C++ is semantically invalid, that failure belongs to the C++ compiler unless the generation rules state otherwise.
>
> ---

## NOTE-009

**Source:** [generators/php/specs/rules.md:24](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## 1. Runtime Contract
>
> All generated code targets the `scpp` runtime.
>
> Object construction and ownership helpers are runtime concepts. Current generation rules use `create<T>(...)` for user PHP class construction, while explicit runtime forms such as `shared(new MyClass)`, `weak($object)`, and `unique(new MyClass)` remain runtime-level constructs when they are later brought into the supported subset.
>
> ### Core Types
> - `int_t`
> - `float_t`
> - `bool_t`
> - `string_t`
> - `nullable<T>`
> - `shared_p<T>`
> - `unique_p<T>`
> - `weak_p<T>`
> - `value_p<T>`
> - `vector_t`
> - runtime `null` / `nullopt` support via the runtime helpers
>
> ### Rules
> - `string_t` uses constructor form, not `static_cast`
> - `nullable<T>` is the null carrier for nullable value types
> - object/class/interface handle types use `shared_p<T>` and are inherently nullable
> - explicit runtime handle annotations `shared<T>`, `unique<T>`, `weak<T>`, and `weakref<T>` lower directly to `shared_p<T>`, `unique_p<T>`, and `weak_p<T>`
> - `value_p<T>` is opt-in inline storage and is never the default lowering for PHP object types
> - runtime `null` is the canonical null literal for generated code where null is supported
> - null comparisons/checks must use the configured runtime helpers such as `php::is_null(...)` and `php::not_null(...)`
> - generated/frontend-facing semantic calls should follow the active PHP profile surface
> - legacy-profile generated calls target `scpp::php::*` entrypoints
> - strict-profile generated calls may lower directly to shared `scpp::*` runtime families only through symbols declared by the active strict profile registry
> - `scpp::php::*` may forward to shared `scpp::*` authorities when PHP semantics match the shared Prism++ semantics
>
> ---

## NOTE-014

**Source:** [generators/php/specs/rules.md:205](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## 6. Runtime Delegation
>
> PHP-specific behavior must go through runtime helpers when required.
>
> Examples:
> - `php::isset`
> - `php::empty`
> - predefined/runtime constants through `::scpp::php` (classified from `get_defined_constants()`)
> - `php::identical`
> - `php::not_identical`
> - `scpp::pow`
> - `scpp::cmp`
>
> Current architectural rule:
> - legacy PHP-facing lowering targets `scpp::php::*`
> - strict PHP-facing lowering may target shared `scpp::*` runtime families when the active strict profile registry declares those symbols
> - language entrypoints remain the stable generator-facing surface for legacy and PHP-owned semantics
>
> ---

## NOTE-020

**Source:** [generators/php/specs/rules.md:285](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## 11. Rejected Features
>
> - reduced PHP array subset (see catalog rows `ARR-*`)
> - `stdClass` / object iteration
> - `foreach` by value is supported for typed `vector<T>` / `hash<T>` surfaces, for the current packed `hash_t<mixed_t>` dynamic-array surface, and for approved wrappers that delegate an iterable success payload through the runtime iterable surface
> - foreach key/value variables are always emitted as fresh loop-local variables in the generated C++; they shadow outer locals of the same PHP name inside the loop body
> - by-reference foreach is currently lowered through source-slot rewriting rather than a standalone alias local
> - value-only form synthesizes a hidden key local such as `_<value>_key_`
> - explicit-key form preserves the PHP key variable and rewrites the foreach value variable through the source slot keyed by that variable
> - this lowering is provisional and subject to future improvement
> - for boxed-array foreach over `mixed_t`, indexed loop lowering uses the generator-facing `mixed_t::size()` / `mixed_t::at(...)` surface instead of reaching through to raw table internals
> - for wrapper-carried iterable payloads, the generator remains type-blind and simply lowers against the runtime iterable surface exposed by the wrapper
> - explicit function/method reference returns require an explicit declared PHP return type and must still satisfy the native-reference safety rule; dynamic interior slot/property returns are not allowed
> - `include`, `include_once`, and `require`
> - `and` / `or` / `xor`
> - untyped parameters
> - function or method overloading
> - untyped raw `null` assignment
>
> ---

## NOTE-023

**Source:** [generators/php/specs/rules.md:364](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## 12. Incompatibilities
>
> See `incompatibilities.md`.
>
> Known items include:
> - division semantics
> - `switch` behavior differences
> - spaceship operator
>
> ---

## NOTE-024

**Source:** [generators/php/specs/rules.md:375](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## 13. Compilation Constraints
>
> All generated C++ code must compile with `-Wshadow` enabled.
>
> ### Implications
> - generated symbol access must remain explicit and unambiguous under C++ shadowing semantics
> - generation must not rely on unstable lookup behavior
> - use-before-declare remains an error
>
> ---

## NOTE-026

**Source:** [generators/php/specs/rules.md:446](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## 15. File Emission Model
>
> - one PHP++ input file generates one `.hpp` file and one `.cpp` file
> - generation is organized per input file, not per class
> - the generated header contains declarations and the generated source contains out-of-line definitions
> - generated files may always include a broad runtime/project header
> - include minimization is not required for the generator
>
> ### Forward Declarations
> - forward declarations may be used only in trivial obvious cases where a class type is referenced through `shared_p<T>` in declarations
> - the generator must not build a dependency solver for include optimization
> - if a case is not trivially safe for forward declaration, the generator may use the simpler include-based path instead

## NOTE-028

**Source:** [generators/php/specs/rules.md:468](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## 17. Deferred Intent Metadata
>
> - source-level intent that is not yet enforced at generation time may be recorded as metadata
> - this includes, for example, non-null object intent where `T` and `?T` currently emit the same object-handle type
> - recording intent metadata must not change the current emitted C++ form unless a generation rule explicitly requires it
> - namespace-scope assignments that participate in executable bootstrap code are allowed and are lowered inside the synthetic namespace execution function
> - namespace-scope static variables remain rejected
>
> ### 14.7 Namespace-Scope Executable Code
> Executable statements must not be emitted directly at namespace scope.
>
> Executable statements inside the same namespace body are consolidated into a single synthetic namespace `main()`, even when declarations appear between them.
>
> Declarations remain at namespace scope and do not split execution into separate synthetic functions.
>
> Source order of executable statements must be preserved when consolidating them into the synthetic namespace `main()`.
>
> This consolidation is valid only when all executable statements belong to the same namespace body and can be merged into a single generated code block for that namespace.
>
> If execution reaches the end of the synthetic namespace `main()` without an explicit return, the generator must append `return 0;`.
>
> The generated global `int main()` must return the result of the selected synthetic namespace `main()` call.
>
> ### 14.8 Cross-Namespace Execution Restriction
> Executable statement consolidation applies only within a single namespace body.
>
> Executable code in a parent namespace and executable code in a nested namespace create different execution flows and are not allowed together.
>
> A nested namespace may appear inside a parent namespace execution region only when the nested namespace contributes declarations only.
>
> ### 14.9 Multiple Namespace Blocks
> Multiple braced namespace blocks in one file are supported when they lower into ordinary declarations and, at most, one selected synthetic execution entry point.
>
> Supported forms include:
> - `namespace A { ... } namespace B { ... }`
> - `namespace A\B { ... } namespace { ... }`
> - multiple braced namespace blocks containing declarations only
> - multiple braced namespace blocks followed by a braced global namespace block `namespace { ... }`
>
> Lowering rules:
> - each PHP namespace block lowers independently under the `scpp::...` root
> - a braced global namespace block `namespace { ... }` lowers to `namespace scpp { ... }`
> - rooted calls from the global block must lower without an empty namespace segment, for example `::scpp::__scpp_main()`
> - declarations remain in their own generated namespace blocks and are not merged by name just because they appear in the same source file
>
> Restriction:
> - executable-statement consolidation still applies only within one namespace body at a time
> - cross-namespace execution merging remains forbidden
> - this section currently covers braced namespace blocks; semicolon-form multi-block behavior remains governed by the existing execution restrictions and file-structure rules
>
> ---

## NOTE-030

**Source:** [generators/php/specs/rules.md:562](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## 1. Scope and precedence
>
> - General rules in this document have precedence over per-example decisions.
> - Concrete examples may be corrected to comply with these rules.
> - The catalog is for coverage and traceability; this file defines the normative behavior.

## NOTE-032

**Source:** [generators/php/specs/rules.md:578](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## 3. Runtime assumptions
>
> ### 3.1 Provided runtime types
> Primitive-like types:
> - `int_t` -> signed 8-byte integer
> - `bool_t` -> C++ `bool`
> - `float_t` -> signed 8-byte floating point
>
> Wrapper / heavy types:
> - `string_t` -> wrapper around `std::string`
> - `vector_t` -> wrapper around `std::vector`
>
> Null support:
> - `null_t` -> custom type
> - `null` -> `inline constexpr null_t null {};`
>
> Nullable support:
> - `nullable<T>`
> - `shared_p<T>`
> - `unique_p<T>`
> - `weak_p<T>`
> - `value_p<T>`
> - `vector_t`
> - runtime `null` / `nullopt` support via the runtime helpers
>
> ### 3.2 Provided runtime helpers
> - `create<T>()`
> - `shared<T>()`
> - `weak<T>()`
> - `unique<T>()`
>
> ### 3.3 Runtime boundary
> The generator does **not** validate whether operator overloads or conversions exist in the runtime.
>
> If generated C++ later fails because of:
> - operator overload gaps
> - unsupported runtime conversions
> - stream operator gaps
> - missing runtime helpers
>
> that is outside the current generator scope and may fail at C++ compile time.
>
> ### 3.4 Allowed assumed runtime/operator surface
> The generator is allowed to emit code that assumes support for:
> - arithmetic operators
> - comparison operators
> - logical operators
> - `std::cout <<`
> - string concatenation through `+`
> - comparisons against `null`

## NOTE-041

**Source:** [generators/php/specs/rules.md:842](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## 12. PHP runtime boundary rules
>
> These PHP semantics must go through the `php::` layer:
>
> - `unset($a)` -> `php::unset(a);` only when the lowered target type is nullable / pointer-like / handle-like and supports an empty state
> - for non-nullable value/container-like targets, use `clean($a)` -> `php::clean(a);` as the current project direction instead of lowering to `php::unset(a);`
> - `isset($b)` -> `php::isset(b)`
> - when the exporter normalizes multi-operand forms, generation must follow the exported tree instead of reconstructing surface syntax
> - `empty($b)` -> `php::empty(b)`
> - strict equality `===` -> `php::identical(...)`
> - strict inequality `!==` -> `php::not_identical(...)`
> - both helpers return `bool_t`, not native `bool`, because they are PHP-semantic runtime operations
> - predefined/runtime constants discovered from `get_defined_constants()` -> unqualified `...` inside generated source namespace blocks
> - user-defined non-class constants -> generated user namespace path (no `::scpp::php` remapping)

## NOTE-042

**Source:** [generators/php/specs/rules.md:857](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## 13. Prism++ runtime/helper boundary rules
>
> Helpers that are not plain PHP semantic primitives may go through the `scpp::` layer.
>
> Current accepted case:
> - exponentiation `**` -> `scpp::pow(...)`
>
> ### 13.1 Rooted runtime qualification ban
> Generator MUST NOT emit fully-qualified names like `::scpp` or `::scpp::php` in generated expression/type code because generated source namespace blocks already inject:
> - `using namespace ::scpp;`
>
> Examples:
> ```cpp
> table_(table_item_(string_t("x")))
> expect_array_argument(x, false, "x")
> create<MyClass>()
> class_t<decltype(obj)>::make()
> A::B::LIMIT
> ```
>
> Never emit these rooted runtime/helper forms inside generated expression/type code:
> ```cpp
> ::scpp::table_(...)
> php::expect_array_argument(...)
> ::scpp::create<MyClass>()
> ::scpp::class_t<decltype(obj)>::make()
> ::scpp::A::B::LIMIT
> ```
>
> Allowed exception:
> - generated using-directives/import-lowering lines may still use rooted forms, for example `using namespace ::scpp;` or `using ::scpp::A::B::f;`
>
> Example:
> ```cpp
> auto a = scpp::pow(static_cast<int_t>(2), static_cast<int_t>(3));
> ```

## NOTE-046

**Source:** [generators/php/specs/rules.md:1055](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## 17. Output rules
>
> - generated code currently routes output through direct `echo_one(...)` calls
> - lowering must preserve the exporter shape while preserving left-to-right echo operand evaluation
> - for the current exporter:
> 	- each `AST_ECHO` node carries one operand
> 	- `echo a, b, c;` is exported as multiple sibling `AST_ECHO` nodes
> 	- adjacent echo nodes from the same lowered statement stream are emitted as sequential `echo_one(...)` calls
> - each emitted operand is evaluated and printed in statement order
>
> Examples:
> ```cpp
> echo_one(a);
> echo_one(b);
> echo_one(c);
> ```

## NOTE-048

**Source:** [generators/php/specs/rules.md:1079](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## 19. Formatting
>
> Current target:
> - compact
> - readable
> - tabs for indentation

## NOTE-049

**Source:** [generators/php/specs/rules.md:1086](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## 20. Notes on known open incompatibilities
>
> These are known and not yet fully resolved in rules:
>
> ### 20.1 Division semantics
> PHP `/` produces a floating-point result; C++ `/` depends on operand types.
> A later normalization/promotion rule is required.
>
> ### 20.2 Loose comparison semantics
> PHP `==` and `!=` are not fully equivalent to native C++ `==` and `!=`.
> A later decision must either:
> - route them through runtime helpers, or
> - formally restrict supported operand/type combinations.

## NOTE-057

**Source:** [generators/php/specs/rules.md:1257](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## Runtime language target
>
> PHP-target array-key normalization is a runtime concern. The generator must not duplicate numeric-string key normalization logic. Builds used by the project test harness are expected to compile the runtime and generated samples with `-DSCPP_LANGUAGE_TARGET_PHP=1`.

## NOTE-061

**Source:** [generators/php/specs/rules.md:1282](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## PHP runtime relative symbol registry
>
> Generator-emitted calls that are known Prism++ runtime intrinsics may be emitted through a runtime-symbol registry inside `namespace scpp { ... }`. The registry is profile-specific and is stored in `generators/php/specs/php_runtime_symbols_legacy.json` or `generators/php/specs/php_runtime_symbols_strict.json`. Entries are recorded as relative symbol paths under `scpp`, or as visible-to-target mappings relative to `scpp` for strict profile flat names such as `fs_is_file -> fs::is_file`. User-defined functions must not be rewritten through this registry when the generator has already resolved them as user declarations.
>
> Architecture note:
>
> - strict-profile direct emission to shared runtime families is allowed only for symbols declared by the active profile registry
> - the registry is the approved bridge between visible/source strict names and shared runtime-family targets

## NOTE-062

**Source:** [generators/php/specs/rules.md:1292](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## Runtime Symbol Registry (relative to scpp)
>
> Any runtime function intended to be callable from transpiled PHP code through the registry **must be registered** in the active profile file:
>
> `generators/php/specs/php_runtime_symbols_legacy.json`
>
> `generators/php/specs/php_runtime_symbols_strict.json`
>
> The S2S generator uses this registry to emit the registered relative path directly. For example:
>
>     php::function_name(...)
>
> Or, for strict flat visible names:
>
>     fs_is_file(...)  ->  fs::is_file(...)
>
> ### Precedence
> User-defined PHP functions take precedence over runtime symbols with the same name.\
> The registry is only applied when no user-defined function is resolved. Bare source calls may resolve through the registry by unique tail-name match.
>
> ### Important
> If a symbol is not present in the registry, the generator will **not** rewrite it through the runtime-symbol registry, even if it exists in the runtime.

## NOTE-064

**Source:** [generators/php/specs/catalog_rebuild_workflow.md:1](../../../../generators/php/specs/catalog_rebuild_workflow.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> # Prism++  Catalog Rebuild Workflow
> Doc Status: planning
> 1. Assistant provides a PHP example from the catalog.
> 2. User provides the expected Prism++ target code, or marks the case as `ERROR`.
> 3. Assistant corrects the proposed target to comply with the general rules.
> 4. Assistant derives the generalized rule, not just the concrete instance.
> 5. The catalog stores one row per generalized rule, not one row per trivial permutation.
>
> ## Constraints
>
> - General rules have precedence.
> - Concrete examples may be corrected to fit the general rules.
> - The goal is to avoid combinatorial explosion and preserve only reusable rule knowledge.
