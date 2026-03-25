# Simple C++ Runtime/Generation Split — v1 Proposal

## 1. Scope

This document defines the non-redundant, human-readable specification for the **Simple C++ runtime/library** and its relationship to the **runtime configuration**.

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

## 3. Runtime design goals

The runtime exists to give generated Simple C++ code a **closed semantic surface** inside C++.

### Core goals
- avoid interference with native C++ overloads and implicit conversions
- keep all semantic types under `namespace scpp`
- keep the surface deterministic and generator-friendly
- allow casts and overloads to be changed through configuration
- make forbidden behavior unavailable where practical
- separate ownership semantics from value optionality

---

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
These represent Simple C++ scalar values rather than native C++ primitives.
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
- the approved control-flow bridge is an explicit native-bool conversion (`static_cast<bool>(...)` or `cast<bool>(...)`), not `.native_value()` in generated conditions
- `bool_t` must not provide uncontrolled truthiness; any native-bool bridge must remain explicit

### 6.3 `int_t` and `float_t`
- these are semantic numeric wrappers, not aliases
- numeric behavior must come from configuration, except for minimal entry/native boundary construction defined by the runtime family design
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

### 6.8 Native reference lowering
- explicit reference lowering uses native C++ lvalue references (`T&`)
- references must remain flat; the generator must never emit `&&`, `*`, or wrapper-of-wrapper reference shapes
- object-like explicit references lower to references over the lowered handle type (for example `shared_p<T>&`)
- the feature is a reduced alias/reference model, not full PHP reference semantics
- rebinding, alias-preserving `unset`, and other PHP `&` edge semantics remain intentionally out of scope

### 6.9 `nullable<T>`
- `nullable<T>` models value optionality
- it is not a substitute for pointer ownership
- pointer wrappers and `nullable<T>` must remain semantically distinct even if both can represent absence
- `nullopt_t` is the canonical semantic sentinel for constructing or resetting an empty `nullable<T>` state
- `nullptr_t` is the canonical semantic sentinel for constructing or comparing empty pointer-like wrappers

### 6.10 Reset/cleanup semantics
- `unset` is restricted to types that can represent an empty/null state
- in practice, `unset` is for nullable / pointer-like families, not for plain non-nullable value types
- `unset` must not be used for native references
- `unset` must not be used as a fake delete for stack values
- for non-nullable value/container-like types, the current project direction is to use `clean(x)` to reset to a default/empty state instead of modeling PHP variable removal
- `clean(x)` is a reset/cleanup operation, not a PHP-compatible symbol-table `unset`

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


## 7a. PHP helper identity semantics

- `===` and `!==` remain helper-based in `scpp::php`
- the current rule is exact-type identity, not family identity
- `null_t === null_t` is true
- `null_t === nullable<T>` is true only when the nullable is empty
- `nullable<T> === null_t` is true only when the nullable is empty
- `nullable<T> === nullable<T>` compares null-state first, then recurses into contained-value identity
- `shared_p<T> === shared_p<T>` compares managed object identity, not deep value equality
- `unique_p<T> === unique_p<T>` compares managed object identity
- native C++ references are an emission strategy, not a distinct runtime wrapper family
- all other differing exact types are non-identical

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
Do not expose a broad C++-like operator surface “just in case”.

### Current operator-phase decision
The current runtime phase uses a C++-first operator policy for the newly added numeric mutation and integer-bitwise surface.

Required interpretation for this phase:
- `int_t` arithmetic uses native C++ integer semantics, including truncating `/`
- `float_t` arithmetic uses native C++ floating-point semantics, including IEEE-style `/`
- integer-only operators such as `%`, `&`, `|`, `^`, `~`, `<<`, and `>>` follow native C++ behavior
- compound assignment operators such as `+=`, `/=`, and `<<=` follow the corresponding native C++ base operator
- increment and decrement follow native C++ prefix/postfix behavior
- PHP-specific identity and concatenation-assignment semantics remain helper-based in `scpp::php` rather than pretending to be ordinary C++ operator overloads
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

- `vector_t::at(...)` returns references for bindable element access, including native reference returns from generated code
