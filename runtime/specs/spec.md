Doc Status: normative


See `../../specs/spec_map.md` for document hierarchy, authority, and v1 conflict-resolution rules.

# Prism++ Runtime/Generation Split - v1 Proposal

> Transitional implementation note: see `../../specs/mixed_boundary_transitional.md`.


## Operator Matrix Synchronization

The derived operator-matrix subsystem consumes runtime semantic rules and
machine-readable operator support data to build a normalized matrix for:
- visualization
- edge-case enumeration
- automated test generation

Any change that affects operator, cast, condition-truthiness, `isset`, `empty`,
`count`, or `unset` behavior must be reviewed for synchronization with:
- `specs/operator_matrix/`
- `runtime/specs/config.json`

The operator-matrix documents are derived coordination specs. They do not define
new runtime semantics, but they must be kept consistent with current runtime
contracts and supported-combination configuration.

## 1. Scope

This document defines the non-redundant, human-readable specification for the **Prism++ runtime/library** and its relationship to the **runtime configuration**.

It does **not** duplicate machine-defined lists of:
- types
- headers
- stable API members
- casts
- overloads
- generation toggles
- JSON field structure

Those belong to `scpp_runtime_config_v1.json`.

This file is normative for semantics, invariants, ownership intent, and generation principles.

---

## 2. Authority model

Two artifacts exist:

1. **Markdown specification**
   - human-readable
   - normative for semantics, invariants, naming intent, and generation principles
   - explains why rules exist

2. **JSON configuration**
   - machine-readable
   - canonical input to generators
   - defines concrete types, casts, overloads, helpers, and generation switches

### Rule
The Markdown explains.
The JSON decides.

If a fact is intended to be consumed directly by tooling, it must live in JSON.
The Markdown may describe the category of that fact, but must not restate the concrete data.

---

## 2a. Operator-surface generation model

The runtime operator surface is moving to a generated canonical free-operator model.

Under this model:

- `config.json` remains the machine authority for which operator families and operand-kind combinations exist
- handwritten concepts remain small, stable compile-time category labels
- a PHP tool provisions generated operator definitions into `include/scpp/generated/operators.hpp`
- `include/scpp/runtime.hpp` is the mandatory public entry point for the generated operator surface
- individual wrapper headers are not required to remain operator-complete once a family has migrated
- internal `detail::...` helpers remain the implementation targets for the generated operators

The procedural flow for this is defined in `operator_generation_flow.md`.

This split is deliberate. The concepts do not become a second semantic authority. They are a stable compile-time vocabulary used by the generated operator surface.

## 2b. Runtime layering and umbrella-header model

The runtime is no longer treated as one language-coupled umbrella.

Layering intent:

- `scpp/runtime.hpp` is the non-language runtime umbrella
- each language gets its own explicit umbrella, starting with `scpp/lang/php.hpp`
- reusable subsystems should move into runtime-owned `core/` or `modules/` code
- language layers should wrap reusable runtime functionality thinly instead of owning its primary implementation

Required dependency direction:

- non-language runtime code must not depend on `lang/*`
- runtime modules must not depend on `lang/*`
- language runtime layers may depend on non-language runtime code and runtime modules

This rule exists to support future `scpp build` composition from selected language targets and selected runtime modules. See `../../specs/architecture/runtime_layering.md` for the dedicated architecture rule.

## 2c. Shared semantic families and language adapters

The current semantic-ownership model has two layers:

1. shared Prism++ semantic families
   - namespace root: `scpp::`
   - current intended roots include `runtime/include/operators/` and `runtime/include/casts/`

2. language adapters
   - namespace roots such as `scpp::php::`
   - current intended roots include `runtime/include/lang/php/operators/` and `runtime/include/lang/php/casts/`

Current rule:
- shared Prism++ families are the real semantic authorities by default
- language adapters expose stable frontend-facing entrypoints
- language adapters forward by default when language semantics match Prism++ semantics
- language-specific divergence is allowed later, but must be explicit

## 3. Runtime design goals

The runtime exists to give generated Prism++ code a **closed semantic surface** inside C++.

### Core goals
- avoid interference with native C++ overloads and implicit conversions
- reduce ambiguity by centralizing migrated operator families into one generated public surface
- keep all semantic types under `namespace scpp`
- keep the surface deterministic and generator-friendly
- allow casts and overloads to be changed through configuration
- make forbidden behavior unavailable where practical
- separate ownership semantics from value optionality
- ensure one semantic family has one real authority

---

## 3a. Canonical operator-route rule

For operator families that have been migrated to the generated surface, the intended public route is:

- one generated free-operator family
- no overlapping public member/free bridge operators elsewhere
- internal helpers allowed behind that surface

For current migration work, this means:

- native-wrapper combinations stay on a compile-time-selected helper path
- combinations involving `mixed_t` use the dynamic mixed runtime path when the config says `mixed_t` participates for that operator kind
- invalid native-wrapper combinations should fail at compile time rather than silently falling into dynamic behavior
- invalid dynamic combinations remain runtime-handled behavior
- operator overloads must not become competing semantic authorities; if present, they must delegate to the family authority

This rule exists to make overload participation deliberate and reviewable.

## 4. Stable API philosophy

Each runtime wrapper has two conceptual layers:

1. **stable core API**
   - structurally stable across generator revisions
   - intended to remain small and predictable
   - used as the anchor for generated code

2. **generated semantic API**
   - emitted from configuration
   - includes generated constructors, operators, helpers, and deleted operations
   - may change without redesigning the wrapper family itself

This split is mandatory.
The runtime class families should remain structurally stable even when cast and overload policy changes.

---

## 5. Runtime semantic families

The runtime is organized into five semantic families:

### 5.1 Scalar semantic types
These represent Prism++ scalar values rather than native C++ primitives.
Their purpose is semantic isolation.

Included initially:
- `null_t`
- `nullopt_t`
- `nullptr_t`
- `bool_t`
- `int_t`
- `float_t`
- `string_t`

### 5.2 Container semantic types
These wrap standard library containers but keep the exposed surface under `scpp`.

Included initially:
- `vector_t<T>`

### 5.3 Ownership semantic types
These model managed references with ownership semantics distinct from native direct use of STL smart pointers.

Included initially:
- `shared_p<T>`
- `unique_p<T>`
- `weak_p<T>`

### 5.4 Inline-storage semantic types
These model explicit inline object/value storage that must not allocate.

Included initially:
- `value_p<T>`

### 5.4a Callable lowering
Closures currently lower to native C++ callable forms rather than a dedicated `scpp` wrapper type.

Included initially:
- native lambda expressions
- `std::function<R(Args...)>` where a concrete callable signature is required

Safe v1 also uses block-local local-variable visibility during generation:
- a variable is visible only in the block where it is introduced and in child blocks
- the first write in a block declares a new variable only when no visible outer variable with the same name exists
- variables declared inside nested statement blocks do not escape those blocks
- out-of-block local use must fail in the generator with an explicit error rather than leaking into a later C++ compile error

### 5.5 Reference lowering strategy
Explicit source references lower directly to native C++ lvalue references.

Included initially:
- native `T&`

### 5.6 Optionality semantic types
These model presence/absence of a value.

Included initially:
- `nullable<T>`

---

## 6. Semantic invariants by family

### 6.1 `null_t`
- `null_t` is a semantic null sentinel
- it is a value-tag, not an owning object
- it must remain distinct from `nullable<T>`
- it may participate in construction or comparison only where configuration allows

### 6.1a `nullopt_t`
- `nullopt_t` is the semantic empty-optional sentinel
- it is intended for optionality construction/reset semantics, not as a general null value
- it must remain distinct from both `null_t` and `nullable<T>`
- it may participate only in optionality-related APIs where configuration allows

### 6.1b `nullptr_t`
- `nullptr_t` is the semantic empty-pointer sentinel
- it is intended for pointer-like construction/comparison semantics, not optional-value semantics
- it must remain distinct from both `null_t` and `nullopt_t`
- in C++, a variable named `nullptr` is not legal because `nullptr` is a keyword; the runtime therefore exposes the constant `scpp::null_ptr` of type `scpp::nullptr_t`

### 6.2 `bool_t`
- `bool_t` is the semantic boolean type of the runtime
- runtime comparisons produce the configured semantic comparison type, not native `bool`
- generated C++ control-flow must bridge explicitly from semantic boolean representation to native C++ condition evaluation
- the approved control-flow bridge is an explicit native-bool conversion from the shared condition helper result (`static_cast<bool>(::scpp::condition_truthy(...))` for general condition sites, or `static_cast<bool>(...)` for values already known to be `bool_t`), not `.native_value()` in generated conditions
- `bool_t` must not provide uncontrolled truthiness; any native-bool bridge must remain explicit

### 6.3 `int_t` and `float_t`
- these are semantic numeric wrappers, not aliases
- numeric behavior must come from configuration, except for minimal entry/native boundary construction defined by the runtime family design
- translated PHP exceptions must stay in-process whenever possible: runtime/helper layers throw native C++ exceptions and only the outermost binary/CLI boundary converts uncaught failures into a non-zero exit status
- translated PHP `throw` / `try` / `catch` / `finally` must not depend on `exit()` / `abort()` style control transfer in normal error flow
- native numeric widening/narrowing semantics must not leak implicitly unless configuration says so

### 6.4 `string_t`
- `string_t` is a semantic string wrapper
- textual behavior is independent of numeric behavior
- numeric/string interop is configuration-controlled, not assumed

### 6.5 `vector_t<T>`
- `vector_t<T>` is the semantic vector family
- v1 should remain intentionally small
- iterator-surface expansion should be deferred until required by the language design

### 6.6 `shared_p<T>`, `unique_p<T>`, `weak_p<T>`
- these wrappers have the same role as `std::shared_ptr`, `std::unique_ptr`, and `std::weak_ptr`, but remain part of the project semantic API rather than raw STL leakage
- ownership semantics must be explicit and predictable
- `shared_p<T>` is the shared-owning handle family
- `unique_p<T>` is the move-only exclusive-owning handle family
- `weak_p<T>` is observational/non-owning, must not dereference directly, and must be observed through `lock()` / helper semantics
- the runtime may expose temporary lifetime-audit helpers such as `debug_use_count()` / `php::debug_use_count()` to prove whether hidden strong owners still exist while debugging weak/shared lifetime issues
- wrapper APIs should stay pointer-parity compatible where practical: null construction/reset, bool conversion, `get`, `reset`, `swap`, and family-appropriate observer/ownership helpers
- ownership-changing behavior must never be inferred by ad hoc runtime rules; it must be explicitly modeled in configuration or helper semantics

### 6.7 `value_p<T>`
- `value_p<T>` stores its payload inline
- `value_p<T>` is the explicit runtime marker for non-heap object/value storage
- `value_p<T>` must not wrap ownership wrappers or reference wrappers
- `value_p<T>` exists only by explicit opt-in; it is not the default lowering for PHP objects
- `value_p<T>` remains object-like at the usage surface and must support member access through `->` so an explicit inline-storage local such as `$x /** value MyClass */ = new MyClass();` can continue to lower member writes like `$x->property_1 = 10;` without switching to direct `.` syntax on the wrapper

### 6.8 Native callable lowering
- anonymous functions lower to native C++ lambdas
- when a concrete callable signature is required, the generator may emit `std::function<R(Args...)>` initialized from a lambda
- the first implementation targets closure capture-by-value only
- direct invocation of closure-valued variables is supported through the native C++ call surface
- PHP `use (&$x)` semantics are intentionally out of scope for this first pass

### 6.9 Native reference lowering
- explicit reference lowering uses native C++ lvalue references (`T&`) only for directly stable objects
- references must remain flat; the generator must never emit `&&`, `*`, or wrapper-of-wrapper reference shapes
- object-like explicit references lower to references over the lowered handle type (for example `shared_p<T>&`) only when the handle object itself is directly stable
- no API may expose a native C++ reference or pointer to heap-backed interior storage whose lifetime or stability is owned by another object
- the feature is a reduced alias/reference model, not full PHP reference semantics
- rebinding, alias-preserving `unset`, and other PHP `&` edge semantics remain intentionally out of scope

### 6.10 `nullable<T>`
- `nullable<T>` models value optionality
- it is not a substitute for pointer ownership
- pointer wrappers and `nullable<T>` must remain semantically distinct even if both can represent absence
- `nullopt_t` is the canonical semantic sentinel for constructing or resetting an empty `nullable<T>` state
- `nullptr_t` is the canonical semantic sentinel for constructing or comparing empty pointer-like wrappers
- explicit unwrap/cast remains `cast<T>(nullable<T>)` when generated code can emit it directly
- the centralized cast surface may also lift `nullable<U>` into any configured explicit target `T` by first requiring a present value and then delegating to `cast<T>(U)`; `string_t` remains the configured PHP-style exception where empty nullable stringifies to `""`
- the centralized cast surface may also lift `mixed_t` into `nullable<T>`: runtime `null` becomes empty nullable, and any non-null runtime kind must satisfy the configured `mixed_t -> T` cast for the wrapped target
- until the current symbol/type-blind generator reaches typed-boundary parity, `nullable<T>` may also provide a temporary implicit bridge to wrapped `T` only for explicit typed destinations such as typed by-value argument passing and typed return
- the temporary typed-boundary bridge must throw a runtime error when the nullable is empty; for centralized cast lifting the required wording is `cast<To>(nullable) cannot convert an empty nullable to a required value`
- the centralized generated operator surface is the authoritative way `nullable<T>` participates in unary, binary, logical, relational, mutation, and compound-assignment families; each participating operator must require present wrapped values and then delegate to the wrapped-value operator family
- `nullable<T>::operator->()` is part of the stable nullable runtime surface for object-like use; it must require a present wrapped value and then either forward to wrapped `T::operator->()` when `T` already exposes it or return the address of the wrapped object when direct-object member access is needed
- empty nullable dereference through `operator->()` must throw a project-shaped runtime error with explicit arrow-context wording so the user can quickly identify null object/property or method access
- equality/inequality remain the narrow null-aware exception: `nullable<T> == null_t/nullopt_t` is allowed explicitly, `nullable<T> == value` returns false when empty, and relational/arithmetic/logical use of an empty nullable remains a runtime error
- the temporary typed-boundary bridge is not a general operator-resolution escape hatch and must not be relied on to define operator participation outside the centralized operator surface

### 6.11 Reset/cleanup semantics
- `unset` is restricted to types that can represent an empty/null state
- in practice, `unset` is for nullable / pointer-like families, not for plain non-nullable value types
- `unset` must not be used for native references
- `unset` must not be used as a fake delete for stack values
- for non-nullable value/container-like types, the current project direction is to use `clean(x)` to reset to a default/empty state instead of modeling PHP variable removal
- `clean(x)` is a reset/cleanup operation, not a PHP-compatible symbol-table `unset`


### 6.12 `hash_t` and `mixed_t` dynamic subsystem
- `hash_t` and `mixed_t` form the runtime dynamic fallback subsystem for quick code
- explicit structs/classes remain the preferred programming model
- using `hash_t` reduces performance, increases memory usage, reduces compile-time issue detection and static-analysis capability, and becomes less readable as surrounding code grows
- once runtime types are established, operations must follow normal Prism++ rules
- any deviation from normal Prism++ semantics must be documented explicitly as an exception
- conversions are explicit by default; dynamic loose coercion is not part of the model
- the runtime does not infer source-level explicit typed boundaries from `mixed_t` alone; language/S2S compromises are documented in `../../specs/dynamic_types.md`
- for current v1 behavior, `../../specs/dynamic_types.md` sections **1.2 Explicit Typed Boundaries** and **1.3 Technical Compromises to Preserve Explicit Typed Boundaries in v1** take precedence over stricter long-term runtime cleanup choices
- if a type combination is not covered by the config, it is not defined

### 6.13 `mixed_t`
- `mixed_t` is the runtime semantic boxed value used by `hash_t`
- current stored runtime kinds are `null_v`, `bool_v`, `int_v`, `float_v`, `string_v`, `table_v`, `shared_table_v`, `dynamic_v`, and `weak_table_v`
- table presentation should be documented publicly as the three internal table forms: owned table, shared table, weak table
- current exact scalar accessors are `bool_value()`, `int_value()`, and `float_value()`
- these exact scalar accessors require matching runtime kind and fail at runtime otherwise
- `string_if()`, `table_if()`, `shared_table_if()`, and `weak_table_if()` are the current probe-style accessors
- `cast<T>(mixed_t)` is the central typed bridge for dynamic-to-typed use
- project-level explicit casts should normalize through `cast<T>(...)` rather than direct wrapper-to-wrapper `static_cast` chains
- strict string explicit casts are part of the current policy: `string_t -> bool_t` accepts only `""`, `"0"`, `"1"`, `"true"`, and `"false"`; any other literal runtime-errors, while `string_t -> int_t` and `string_t -> float_t` require whole-string successful parses with no trailing characters
- long-term runtime intent is explicit bridge use at typed boundaries; current v1 non-explicit acceptance at some language/S2S sites is documented in `../../specs/dynamic_types.md` under Explicit Typed Boundaries and Technical Compromises
- until generator parity exists, runtime/operator/cast surface must preserve those v1-visible typed-destination bridges instead of removing them for API purity alone
- `mixed_t::operator[]` is the primary mutating chained dynamic array access helper
- mutable `mixed_t::operator[]` autovivifies `null` into an owned `hash_t<mixed_t>`
- `mixed_t::get(...)` is the primary non-mutating read helper and returns a null-kind `mixed_t` on missing key or non-array receiver
- `mixed_t::empty()` and `mixed_t::isset(...)` are convenience methods only; they delegate to the shared `scpp::empty(...)` and `scpp::isset(...)` authorities rather than owning those semantics
- `_find_val()` remains available as the non-inserting `hash_t<mixed_t>` helper used by generator read paths
- dynamic arithmetic, comparison, logical operators, mutation, compound assignment, and increment/decrement on `mixed_t` are enabled through runtime-kind dispatch that delegates to the native wrapper rules already defined elsewhere in the config
- the delegation format is semantic-tuple based: once runtime kinds are established, the runtime resolves the operation as the corresponding native rule such as `int_t + int_t`, `int_t + float_t`, `float_t++`, `string_t += string_t`, or `bool_t && bool_t`
- `mixed_t` does not define an independent concat operator family; PHP `.` / `.=` must be lowered by the generator into explicit text conversion plus primitive `string_t` concat
- implicit `mixed_t -> native` extraction is temporarily accepted only at v1 explicit typed boundaries (typed initialization/assignment, typed property write, typed by-value arg passing, typed return); operator resolution must not use that bridge to create extra candidates
- compound assignment on `mixed_t` is allowed only when the delegated native binary operator exists and assignment back into the stored lhs kind remains valid
- table carriers are excluded from arithmetic dispatch; table comparison currently supports only identity-style `==` / `!=` for shared/weak carriers as documented in the runtime config, while owned `table_v` direct comparison is currently an error
- expired weak-table `_find_val` returns null-kind `mixed_t`, `find` returns not-found, and `at` / write-through access are runtime errors
- callable dispatch and method dispatch on `mixed_t` are still deferred

### 6.14 `hash_t`
- typed reads/writes/calls originating from dynamic table/value access must follow the compromise notes in `../../specs/dynamic_types.md` when current v1 behavior accepts non-explicit conversion at explicit typed boundary sites
- `hash_t` remains the underlying ordered-table container, while generator-facing PHP `array` lowering now targets `mixed_t` for the fat-variable path
- implementation is adapted from the donor `mem_container` storage design, but generated code must target `hash_t` only
- `find()` is the non-inserting lookup API and returns `maybe_value_t`
- `at()` is checked non-inserting access and follows throw-style semantics on miss
- generator-facing non-assignment dim access is direct (`hash_t::operator[]` / `mixed_t::operator[]`) with mutable autovivification and const null-on-miss behavior
- echo/text coercion for slot-based dim reads must dispatch through a non-materializing `mixed_t` read before normal `to_string(...)` handling
- keys are strict: `123` and `"123"` are different
- append uses `max_existing_int_key + 1`
- current dynamic indexing policy rejects `string_t`, `int_t`, `float_t`, `bool_t`, and `null_t` receivers for `[]`; only table receivers are valid
- string indexing is reserved for later explicit `char_t` / `byte_t` work and is not currently defined


---

## 7. Memory helper semantics

The runtime exposes helper functions for managed allocation/reference creation.

### Required semantic rules
- `create()` is the default generated allocation helper
- in the current v1 policy, `create()` lowers to shared ownership by default
- `create()` is reserved as a future policy abstraction point, but its active lowering must remain deterministic at any given version
- `shared()` is explicit shared allocation
- `unique()` is explicit unique allocation
- `weak()` derives a weak reference from shared ownership
- `weak()` must not allocate
- weak observation remains non-owning: `weak_p<T>` observations become usable objects only through `lock()` or helper paths built on top of `lock()`
- `value()` constructs explicit inline-storage wrappers
- `value()` must not allocate on behalf of the wrapper itself
- explicit references lower directly in the generator; the runtime does not expose a separate `ref()` helper

### Constraint
Policy flexibility is allowed only through configuration/version changes, not through context-sensitive ambiguity in generated code.

---


## 7a. Shared strict-identity semantics with PHP adapter exposure

- strict identity is owned by the shared runtime semantic family, with PHP exposing the stable adapter surface in `scpp::php`
- strict identity is defined over **PHP-visible values**, not raw wrapper/storage types
- wrapper carriers normalize before comparison:
	- `nullable<T>` normalizes to either PHP `null` or wrapped `T`
	- `result_or_false<T>` normalizes to either PHP `false` or wrapped `T`
	- `result_or_bool<T>` normalizes to PHP `false`, PHP `true`, or wrapped `T`
	- `result<T>` compares success payloads by wrapped-value identity and error states by structured `error_t` payload equality
	- `mixed_t` compares by active runtime kind first, then by the exact payload of that kind
- `null_t`, `nullopt_t`, and `nullptr_t` all normalize to PHP `null`
- `shared_p<T> === shared_p<T>` compares managed object identity, not deep value equality
- `unique_p<T> === unique_p<T>` compares managed object identity
- native C++ references are an emission strategy, not a distinct runtime wrapper family
- after wrapper normalization, differing PHP-visible kinds remain non-identical

---

## 8. Cast-policy principles

The cast system must be **data-driven**.

### Required principles
- cast policy lives in configuration
- the runtime must not embed a hidden cast matrix beyond the minimal wrapper/native entry surface required for construction and interop
- cast categories must remain explicit
- forbidden casts should be deleted or otherwise made unavailable where practical
- ownership conversions require stricter scrutiny than value conversions
- casts must be modifiable without redesigning runtime wrapper families

### Special constraint
`weak_p<T> -> shared_p<T>` is not ordinary cast behavior; semantically it is an observation/lock operation and should remain modeled as such.

---

## 9. Overload-generation principles

The overload surface must also be **data-driven**.

### Required principles
- overload availability is defined by configuration
- generated operators must be reproducible from configuration with no hidden rules
- unsupported operations should be absent or explicitly deleted according to generation policy
- cross-type operator behavior must be intentional, not inferred from native C++ conversions
- the comparison result type must remain the configured semantic comparison type

### Practical recommendation
Value families should only receive operator families that correspond to the language semantics actually needed.
Do not expose a broad C++-like operator surface â€œjust in caseâ€.

### Current operator-phase decision
The current runtime phase uses a C++-first operator policy for the newly added numeric mutation and integer-bitwise surface.

Required interpretation for this phase:
- `int_t` arithmetic uses native C++ integer semantics, including truncating `/`
- `float_t` arithmetic uses native C++ floating-point semantics, including IEEE-style `/`
- integer-only operators such as `%`, `&`, `|`, `^`, `~`, `<<`, and `>>` follow native C++ behavior
- compound assignment operators such as `+=`, `/=`, and `<<=` follow the corresponding native C++ base operator
- approved integer wrapper carriers such as `nullable<int_t>`, `result<int_t>`, `result_or_false<int_t>`, and `result_or_bool<int_t>` participate in integer bitwise and shift families by guarded wrapper lifting: present/success payloads unwrap and delegate to the corresponding `int_t` operator
- empty/error/sentinel wrapper states in those lifted integer bitwise and shift families remain compile-valid but must runtime-error during guarded unwrap rather than silently normalizing to `0`
- increment and decrement follow native C++ prefix/postfix behavior
- strict identity and concatenation-assignment use explicit runtime semantic helpers rather than pretending to be ordinary C++ operator overloads
- string/bitwise/coercion combinations not already representable by the runtime surface should remain unsupported for now and fail in earlier phases or at compile time

---

## 10. Generated code model

The code generator should target the runtime as a semantic backend, not as a thin aliasing layer.

### Required rules
- generated code should use `scpp` wrappers as the semantic boundary
- conditions in generated C++ must bridge explicitly from the semantic boolean representation to native control-flow evaluation, using the configured explicit bool bridge rather than `.native_value()`
- generator output must not rely on accidental native implicit conversions
- all generated behavior that depends on casts or overloads must be derivable from configuration

### Layering constraint
- the runtime specification and runtime API must not depend on the existence of a generator
- the runtime may expose only semantic types, helpers, and invariants that stand on their own as a C++ library surface
- source-language legality is not a runtime concern; unsupported PHP-in-subset constructs must be rejected before runtime semantics are involved
- the runtime may defend internal invariants, but it must not become a policy gatekeeper for frontend or lowering decisions
- generator-facing concerns must be expressed as constraints on emitted code shape, not as hidden runtime awareness of frontend phases

## 10a. Current PHP builtin/string helper organization

- the current project still keeps PHP builtin/string implementation helpers in `runtime/include/lang/php/support/`
- this includes areas such as `php_string.hpp` and helper support from `php_common.hpp`
- for the current PHP-only phase, the priority is matching PHP-facing behavior and keeping the runtime usable
- broader cross-language builtin/intrinsic restructuring is deferred until a second language exists or a concrete shared builtin family is intentionally promoted

---

## 11. Generation invariants

The following invariants should hold for every generated runtime revision:

- one canonical configuration file is the single machine source of truth
- Markdown must not restate concrete machine-owned tables
- changing cast policy must not require redesigning wrapper families
- changing overload policy must not require redesigning wrapper families
- ownership and optionality remain separate concepts
- `create()` has one deterministic meaning per version
- generated code remains valid even when forbidden operations are emitted as deleted declarations

---

## 12. Main design recommendation

The correct long-term structure is:

- keep **semantics and invariants** in the Markdown specification
- keep **concrete runtime data** in the JSON configuration
- generate the runtime/library surface from JSON
- treat the runtime as a stable semantic platform and the configuration as the policy layer

This keeps the system editable without letting the specification and generator drift apart.

- `vector_t::at(...)` is retained for ordinary runtime access, but it is not an approved native-reference escape hatch in the current safe subset
- `vector_t::try_ref(...)` is the restricted escape hatch and currently succeeds only for `shared_p<T>` elements, returning a copied handle


## hash_t removal invariant

For `hash_t`, removing an entry must preserve the visible keys of all remaining entries.

This is especially important for numeric keys:
- removing key `0` must not cause former key `1` to become key `0`
- later `append()` continues from `max_existing_int_key + 1`

Packed-mode optimizations must not violate this invariant.



- `php::count(const hash_t<mixed_t>&)` is supported and returns the logical size of a lowered PHP array.

## Compile-time language target

The runtime may be built with `-DSCPP_LANGUAGE_TARGET_PHP=1`. Under that target, PHP-target array-key normalization is performed in the runtime layer, not in the generator. Decimal integer strings normalize to integer keys using PHP-style rules (`"10" -> 10`, `"-3" -> -3`, `"08"` stays string, leading/trailing whitespace stays string, `"+10"` stays string). Append uses the maximum integer key plus one.



See: module_inclusion_model.md


## result_or_false<T>, result_or_bool<T>, and result<T>

- `result_or_false<T>` is the explicit PHP-compatibility false-able wrapper.
- For `result_or_false<bool_t>`, plain `bool` / `bool_t` construction remains a wrapped payload value. The false sentinel stays explicit through `false_sentinel`, `null`, or `nullopt`; code generation must not silently rewrite a typed payload `false` into the sentinel for this specialization. It shares the centralized guarded-value operator/cast lifting model with `nullable<T>`, but its empty state represents the PHP `false` sentinel rather than `null`, and PHP helper operators must normalize that state as the visible PHP `false` value.
- `result<T>` is the explicit structured-failure wrapper. Value-required casts and typed-boundary conversions unwrap only the success value; PHP helper identity may compare either the success payload or the structured `error_t` payload, but the error state is never silently converted into a usable value.
- `result<T>` error inspection is explicit through `error()`. The generator must lower `$result->error()->...` to the wrapper method rather than treating `error` as a real payload property.
- `result_or_bool<T>` is the explicit PHP-compatibility bool-able wrapper for contracts such as `T|bool`. It shares the same guarded-value lifting surface, and its non-value states must normalize to the visible PHP boolean sentinels `false` and `true` for helper-owned operators and conditions.
- Policy lock for `result_or_bool<bool_t>`: plain `bool` / `bool_t` construction and assignment create a wrapped payload value, not a bool-state sentinel. The false sentinel remains explicit through `false_sentinel`, `null`, or `nullopt`; the true sentinel is explicit through `true_sentinel`. This avoids ambiguity between payload `bool_t` and the wrapper's bool-state branch.
- `php::take(...)` is the unified guarded extraction helper for `nullable<T>`, `result_or_false<T>`, `result_or_bool<T>`, and `result<T>`. It returns `bool_t`, evaluates the source expression once, and only assigns the outputs that correspond to the active wrapper branch.
- `php::take(value_out, source)` extracts the present / success payload for `nullable<T>` and `result_or_false<T>`, preserves the output type, and leaves `value_out` unchanged on the empty / false branch.
- `php::take(value_out, error_out, source)` extracts the success payload for `result<T>` and copies the structured error payload only on the error branch.
- In the current version, `scpp::coalesce_eval(...)` rejects `result_or_bool<T>` on either side at runtime with a deterministic error rather than relying on generator-side semantic rejection. The PHP-facing adapter forwards to that shared authority.
- For `result_or_bool<T>`, `php::take(value_out, bool_out, source)` returns `true` for both wrapped-value and boolean-true states so PHP-style APIs such as `mysqli::query()` can treat `true` as a successful non-row result. The `bool_out` slot receives the active boolean state only on bool branches.
- Success-state wrapper delegation for iterable payloads is runtime-owned. When the carried payload exposes the iterable-by-value runtime surface used by PHP `foreach` lowering, approved wrappers may delegate that surface through guarded unwrap.
- `foreach ($wrapper as $value)` is therefore allowed for approved wrappers whose carried success payload is iterable. Sentinel / empty / error states fail at runtime through guarded unwrap rather than silently acting as an empty iteration.

## PHP false-sentinel exposure rule

At the PHP exposure layer, functions documented as PHP `T|false` must surface `result_or_false<T>` rather than substituting `null`. Functions documented as PHP `T|bool` should surface `result_or_bool<T>` when the success contract includes both `true` and `T`. Functions documented as PHP `bool` success/failure APIs must return plain `bool_t`, not `nullable<bool_t>`.
