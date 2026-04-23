Doc Status: normative


See `../../specs/spec_map.md` for document hierarchy, authority, and v1 conflict-resolution rules.

# Prism++ Operator Generation Flow

## Purpose
This document captures the current operator-generation flow introduced for the runtime operator-surface cleanup. It exists for two reasons:

1. as a procedure that can be followed when the operator surface is regenerated or extended
2. as explanatory information describing why the current design is structured this way

The current flow is intentionally centered on a generated canonical operator surface instead of scattered handwritten wrapper/member/free-operator definitions.

---

## 1. Current authority split

The current operator-generation flow uses this split:

- `runtime/specs/config.json` is the machine authority for which operator families and operand-kind combinations exist
- handwritten C++ concepts define the stable compile-time operand categories used by the generated surface
- a PHP generation tool provisions those config-driven rules into generated C++ operator definitions
- internal `detail::...` helpers implement the actual native or mixed dispatch behavior

This means the config decides which operator routes are emitted, while the concepts decide which compile-time category bucket a C++ operand belongs to.

The concepts are intentionally not generated from JSON. They are small, stable, handwritten entry points that let the generated operator surface stay readable and reviewable.

---

## 2. Design goal

The goal of this flow is to reduce or eliminate ambiguous operator resolution by ensuring that the compiler sees one canonical public operator surface for the supported categories.

The current design direction is:

- centralize operator participation in generated free operators
- avoid keeping overlapping public operator families spread across wrappers and helper files
- keep native-wrapper arithmetic/comparison behavior available without routing everything through `mixed_t`
- route mixed participation through a deliberate dynamic path instead of allowing accidental fallback paths to compete

This is primarily an overload-surface control mechanism.

---

## 3. Stable handwritten concepts

The current locked handwritten concepts are:

- `is_bool`
- `is_native_int`
- `is_native_float`
- `is_native_number`
- `is_string_like`
- `is_mixed`
- `is_mixed_compatible`

For the current runtime phase, `is_native_int` and `is_native_float` refer to the runtime wrapper numeric types rather than arbitrary built-in C++ arithmetic types.

In the current direction:

- `is_native_int` is the `int_t` category
- `is_native_float` is the `float_t` category
- `is_native_number` groups those two categories
- `is_mixed` is exact `mixed_t`
- operator-specific participation should be expressed with operator-specific concepts or `requires` clauses built from these stable category concepts

The concept layer is not intended to encode the full config semantics by itself. It narrows participation. The generated operator surface remains responsible for which operator families actually exist.

---

## 4. Canonical operator-surface rule

The current target model is:

- one generated public operator surface under `runtime/include/scpp/generated/operators.hpp`
- the mandatory public include entry point for the non-language runtime operator surface is `scpp/runtime.hpp`; language umbrellas may include it and add language-owned wrappers above it
- individual type headers are no longer required to remain operator-complete
- generated free operators become the canonical public entry points for covered operator families
- wrapper/member/free operators elsewhere that compete with the generated surface should be removed once coverage is complete and validated
- internal `detail::...` helpers remain allowed and are the preferred implementation targets for generated operators

This distinction matters:

- public overlapping operator surfaces create ambiguity risk
- internal helper functions do not

The cleanup rule is therefore not â€œremove all helper logicâ€. The rule is â€œremove competing public operator declarations once the generated surface fully covers themâ€.

---

## 5. Native path vs mixed path

For generated operators, the current model is two-path:

### Path A â€” native-wrapper path
Used when the participating operands are in the operatorâ€™s native-wrapper category bucket.

For example, for numeric operators this means the `int_t` / `float_t` path.

This path should:

- remain compile-time selected
- call stable internal `detail::...` helpers
- preserve native-wrapper result typing
- fail at compile time for unsupported native combinations

Example policy direction:

- `int_t op int_t` returns `int_t` when the operator family keeps integer semantics
- `int_t op float_t` and `float_t op int_t` return `float_t`
- invalid native combinations for that operator family must not silently route into dynamic fallback

### Path B â€” mixed path
Used when a covered operator kind includes `mixed_t` participation and at least one participating operand is `mixed_t`.

This path should:

- promote the participating operands into the `mixed_t` runtime path intentionally
- call a dedicated mixed runtime dispatcher or helper
- return `mixed_t`
- leave invalid dynamic combinations to runtime handling

This split preserves a reviewable fast/static path for wrapper-native combinations while keeping dynamic behavior explicit.

Typed-boundary bridges such as the temporary `nullable<T> -> T` generator-compatibility unwrap are not part of the intended public operator candidate surface. They exist only so explicit typed destinations keep working while the current frontend remains symbol/type-blind.

When a wrapper family needs operator participation before the frontend can emit explicit unwraps, the operator generator must add a centralized lifted operator layer instead of relying on those conversion bridges. For current nullable work, that lifted layer is responsible for:
- requiring present values for unary/binary/logical/relational/mutation participation
- preserving explicit sentinel comparisons against `null_t`/`nullopt_t`
- keeping equality with non-sentinel values well-defined without inventing SQL-style null propagation
- delegating real work to the wrapped-value operator family after the checked unwrap

Nullable arrow dereference is intentionally not part of the generated arithmetic/logical operator matrix. It lives on the stable `nullable<T>` wrapper surface itself so object/property and method access can use ordinary `x->member` lowering while still going through one centralized present-value check. The wrapper must forward to wrapped `T::operator->()` when the payload type is already pointer-like and otherwise return the address of the wrapped object for direct-object member access.

---

## 6. Procedure: how operator generation should be extended or regenerated

Use this procedure when changing the operator surface.

### Step 1 â€” read authority documents
Read at least:

- `specs/spec_map.md`
- `runtime/specs/spec.md`
- `runtime/specs/runtime_generation_guidelines.md`
- `runtime/specs/config.json`
- this file

If a change touches dynamic/mixed user-visible behavior, also read `specs/dynamic_types.md`.

### Step 2 â€” determine operator family scope
Decide which operator family is being generated or revised.

Examples:

- arithmetic
- comparison
- logical
- bitwise
- shift
- unary arithmetic / logical / bitwise
- assignment / compound assignment

Do not assume one familyâ€™s legality rules automatically transfer to another. `%`, `/`, comparisons, logical operators, and compound assignments often require different category rules.

### Step 3 â€” derive operator-specific operand buckets from config
For the target operator family, determine from `runtime/specs/config.json`:

- whether the operator family is enabled
- which wrapper/native categories are allowed on the static path
- whether `mixed_t` participates for that operator kind
- whether result typing is native-wrapper or dynamic
- whether the operator is unary, binary, or compound-assignment

The generator must not guess missing operator support.

### Step 4 â€” generate operator-specific constraints
Build the operator-specific `requires` logic from the stable handwritten concepts.

Examples of intended direction:

- numeric arithmetic operators should be constrained to `is_native_number(...)` and `is_mixed(...)` buckets as appropriate
- comparison operators may admit additional categories if the config allows them
- string/text operators should be added only when the config-backed path is intentionally introduced

Avoid using one universal operand concept for every operator family unless the config really models the same category surface everywhere.

### Step 5 â€” generate native-wrapper branch logic
For native-wrapper-covered combinations, generate a compile-time branch tree that:

- uses `if constexpr` / `else if constexpr`
- covers the intended native-wrapper combinations explicitly
- dispatches into internal `detail::...` helpers
- produces wrapper-native result types
- ends in a dependent `static_assert` for impossible/uncovered native cases

The generated native branch must not call back into the same public operator surface in a way that risks recursion or re-entry ambiguity.

### Step 6 â€” generate mixed branch logic
If the config says `mixed_t` participates for that operator kind, generate the mixed branch.

The mixed branch should:

- intentionally promote the operands into `mixed_t`
- call the dedicated runtime mixed dispatcher/helper for that operator family
- return `mixed_t`
- avoid pretending that invalid dynamic combinations are compile-time errors

### Step 7 â€” include the generated header in the runtime umbrella
The canonical generated operator header must remain reachable through the stable public runtime umbrella include.

The current target location is:

- `runtime/include/scpp/generated/operators.hpp`

The umbrella include should expose it consistently.

Direct consumers of the non-language runtime should treat `scpp/runtime.hpp` as the mandatory public entry point for generated operator availability. Language-specific generated code may instead include a language umbrella such as `scpp/lang/php.hpp`, which in turn includes `scpp/runtime.hpp`. Code that includes only individual wrapper headers should not rely on those headers remaining operator-complete.

### Step 8 â€” remove overlapping public operators only after coverage is verified
After generation is updated and validated, remove overlapping public operator definitions from wrapper/member/free-operator code.

Keep:

- internal `detail::...` helpers
- non-overlapping support code
- implementation primitives needed by the generated operator surface

This removal must be done only after the generated surface covers the same intended semantics.

### Step 9 â€” validate
At minimum validate:

- generation produces a non-empty operator header
- runtime builds successfully
- native-wrapper paths compile and resolve without ambiguity
- mixed-involved paths route to the mixed runtime dispatcher as intended
- compile-time native-invalid cases fail as expected
- no new competing public operator paths remain for the families already migrated

---

## 7. Information: why the current flow does not generate everything from config directly

The current design intentionally does not generate the concepts themselves from JSON.

Reason:

- the concepts are not the semantic authority
- the concepts are small compile-time category labels that keep the generated code readable
- the config is still the semantic authority for which generated operator routes exist

This keeps the roles clear:

- concepts narrow participation
- generated operators define the actual public route surface
- helpers implement behavior

---

## 8. Information: why a generated canonical surface is preferred over many scattered overloads

Scattered public operator definitions increase ambiguity risk because the compiler may see:

- member operators
- free operators
- bridge operators
- implicit construction routes
- mixed and native families that overlap accidentally

The generated canonical surface is preferred because it makes the visible route set deliberate and reviewable.

This does not mean every implementation detail should be generated. It means the public operator entry points should be centrally provisioned from config.

---

## 9. Current cleanup rule

Until migration is complete, partial coexistence may still exist. The intended end state is:

- generated operator families are the only public operator families for the migrated categories
- wrapper classes retain helper/member APIs only where they are not competing operator surfaces
- internal helpers remain available to implement the generated operators

If ambiguity appears, first check whether overlapping public operators still remain outside the generated surface.

---

## 10. Current output location and implementation split

Current intended split:

- generator script: `runtime/tools/generate_operators.php`
- generated operator surface: `runtime/include/scpp/generated/operators.hpp`
- helper implementation target: `scpp::detail::...`
- public umbrella include continues to expose the generated surface through the runtime umbrella

This split is part of the current runtime-generation procedure and should remain stable unless intentionally revised.

---

## 11. Summary

The current operator-generation flow is:

1. config decides which operator routes should exist
2. handwritten concepts define stable operand categories
3. PHP generation emits the canonical public operator surface
4. generated operators select native-wrapper or mixed path deliberately
5. internal helpers implement the actual work
6. overlapping public operator surfaces are removed after verified migration

That is the current procedural and informational model for the operator-generation work.


## result_or_false<T>, result_or_bool<T>, and result<T>

- `result_or_false<T>` is the explicit PHP-compatibility false-able wrapper.
- For `result_or_false<bool_t>`, plain `bool` / `bool_t` construction remains a wrapped payload value. The false sentinel stays explicit through `false_sentinel`, `null`, or `nullopt`; code generation must not silently rewrite a typed payload `false` into the sentinel for this specialization. It shares the centralized guarded-value operator/cast lifting model with `nullable<T>`, but its empty state represents the PHP `false` sentinel rather than `null`.
- `result<T>` is the explicit structured-failure wrapper. Value-required casts, typed-boundary conversions, and operator participation unwrap only the success value; the error state is never silently converted into a usable value.
- `result<T>` error inspection is explicit through `error()`. The generator must lower `$result->error()->...` to the wrapper method rather than treating `error` as a real payload property.
- `result_or_bool<T>` is the explicit PHP-compatibility bool-able wrapper for contracts such as `T|bool`. It shares the same guarded-value lifting surface, but its non-value states are the PHP boolean sentinels `false` and `true`.
- Policy lock for `result_or_bool<bool_t>`: implicit `bool` / `bool_t` construction and assignment target the wrapped payload. Use `false_sentinel`, `null`, or `nullopt` for the false state, and `true_sentinel` for the true state.
