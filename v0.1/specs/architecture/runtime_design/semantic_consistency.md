# Runtime Semantic Consistency and Anti-Drift Rules
Doc Status: normative
Status: active normative architecture rule.

## Purpose

This document defines required runtime code-organization rules for semantic families such as casts, arithmetic, conditional selection, logical operators, `isset`, `empty`, `count`, and coalesce.

The goal is to prevent semantic drift between:

- runtime implementation
- generator expectations
- higher-level language/spec documents

These rules govern how runtime code must be structured so that shared behavior is implemented once, audited once, and changed once.

---

## 1. Semantic authority model

### 1.1 One semantic family, one real authority

Each semantic family MUST have exactly one real semantic authority.

Examples:
- `scpp::condition_truthy(...)`
- `scpp::coalesce_eval(...)`
- `scpp::isset(...)`
- `scpp::empty(...)`
- `scpp::count(...)`
- `scpp::cast<T>(...)`

This is stronger than "one file per family".
It means the runtime and generator must not leave multiple implementation paths alive for the same semantic meaning.

### 1.2 One compiler-visible entry point per active family

For each active family, there must be one intended compiler-visible entry point.

The project must not rely on the C++ compiler discovering semantics through multiple unrelated overload paths.

Operator overloads and convenience helpers may exist only if they delegate to the same semantic authority.
They must not become competing semantic definitions.

### 1.3 Language entrypoints are adapters, not second authorities

Language-facing runtime entrypoints such as `scpp::php::*` are stable frontend-facing adapters.

They may:
- forward to shared `scpp::*` semantic authorities
- validate frontend-specific preconditions
- later replace forwarding with language-specific semantics if explicitly required

They must not create parallel semantic implementations when the shared family already exists.

---

## 2. Family ownership and file structure

### 2.1 One semantic family per file or family-owned file group

Each semantic family MUST be implemented in a dedicated file or dedicated family-owned file group.

Examples:
- `runtime/include/operators/coalesce/coalesce.hpp`
- `runtime/include/operators/conditional/condition_truthiness.hpp`
- `runtime/include/operators/logical/logical.hpp`
- `runtime/include/casts/bool_cast.hpp`

Language adapters mirror that structure:
- `runtime/include/lang/php/operators/coalesce/coalesce.hpp`
- `runtime/include/lang/php/operators/conditional/condition_truthiness.hpp`

### 2.2 Shared base first, language adapter second

When a family currently uses shared Prism++ semantics, the preferred structure is:

1. shared family authority in `scpp::*`
2. language adapter in `scpp::<lang>::*`

Current example:
- shared base authority under `runtime/include/operators/`
- PHP adapter under `runtime/include/lang/php/operators/`

### 2.3 `support/` is not for semantics

`support/` is restricted to non-semantic utilities.

It may contain:
- plumbing helpers
- storage utilities
- formatting helpers
- small reusable mechanics that do not define language meaning

It must not contain:
- semantic family authorities
- meaning-bearing operator/cast/count/isset/empty/coalesce truth tables
- hidden fallback behavior

Programming-language semantics and support utilities are different categories and must not be mixed.

---

## 3. Thin public entry points

### 3.1 Shared family entry points must stay thin

Public family entry points in `scpp::*` MUST stay thin.

They may:
- validate family-specific preconditions
- call shared semantic helpers
- normalize the returned branch or result

They MUST NOT embed large duplicated semantic decision trees inline when the same behavior already exists in a shared helper.

### 3.2 Language adapters must stay thinner

Language adapters such as `scpp::php::*` SHOULD be even thinner.

If the language behavior matches the shared family, the language adapter should forward directly.
If the language behavior differs, the adapter may become the language-owned authority for that language only.

---

## 4. Shared behavior rules

### 4.1 Shared behavior means the same function

If two or more operators are documented as sharing behavior, they MUST call the same semantic helper.

Equivalent-looking duplicated code is not sufficient.

Examples:
- ternary and elvis must share the same condition helper when their condition evaluation is defined as common behavior
- logical operators and conditional operators must share the same condition-truthiness authority when defined as common behavior

### 4.2 No parallel implementations

If a semantic helper already exists for a behavior category, new code MUST:
- reuse that helper, or
- explicitly replace that helper

It MUST NOT introduce a second helper that reimplements the same rule set in parallel.

Migration rule:
- prefer moving the whole family and deleting the old version in one pass
- if that is too tangled, centralize to one authority first and move it in the next pass
- never keep two real semantic implementations alive in parallel

---

## 5. No semantic fallbacks

### 5.1 No fallback to unrelated semantic paths

A missing semantic specialization MUST NOT silently fall back to an unrelated path.

Forbidden examples:
- falling back from condition evaluation to generic `cast<bool>` because a direct condition rule is missing
- falling back to native C++ conversions because no explicit Prism++ rule was written
- relying on overload resolution to discover semantics that were not intentionally declared

A behavior must be:
- explicitly handled, or
- explicitly rejected

### 5.2 Rejection paths are part of the design

Invalid or unsupported cases must be rejected through stable, centralized error paths.
Rejection behavior must not drift independently from acceptance behavior.

---

## 6. Truthiness and condition handling

### 6.1 Single condition-truthiness authority

All control-flow truthiness decisions MUST route through the same semantic authority.

This applies to:
- `if`, `while`, `for`
- ternary (`cond ? a : b`)
- elvis (`cond ?: b`)
- logical operators (`!`, `&&`, `||`)

Current authority:
- `scpp::condition_truthy(...)`

### 6.2 Truthiness is not generic bool cast

Condition truthiness and bool conversion are distinct semantic domains unless a higher-level spec explicitly states otherwise.

Those paths must not be merged accidentally.

---

## 7. Wrapper and dynamic-type handling

### 7.1 Wrapper delegation must be centralized

When a wrapper type participates in a semantic family by delegating to a carried or lowered kind, that delegation must be implemented centrally.

This applies to categories such as:
- `nullable<T>`
- `result<T>`
- `result_or_false<T>`
- `result_or_bool<T>` where explicitly allowed
- `mixed_t`

### 7.2 Dynamic runtime dispatch must exist in one place

For dynamic carriers such as `mixed_t`, runtime kind dispatch MUST be centralized.

The same family-specific `switch(kind)` or equivalent dispatch logic must not be duplicated independently across multiple operator implementations.

### 7.3 `mixed_t` is shared substrate, not language-owned semantics

`mixed_t` is a Prism++ runtime type and may be reused by multiple languages.

That does not make `mixed_t` member functions the authority for language semantics.
Language meaning must still be owned by the relevant family authority.

If `mixed_t` exposes convenience methods for semantic families, those methods must delegate to the shared family authority.

Current examples:
- `mixed_t::empty()` delegates to `scpp::empty(...)`
- `mixed_t::isset(...)` delegates to `scpp::isset(...)`

---

## 8. Separation of semantic domains

### 8.1 Control-flow truthiness and coalesce usability are separate

The following are distinct semantic domains and MUST remain separate:
- `condition_truthy(...)`
- `coalesce_has_usable_value(...)`

They may support overlapping types, but they must not call each other as substitutes.

### 8.2 User-visible condition semantics and normalization semantics are separate

A rule that normalizes a value into a typed destination is not automatically the same as a control-flow truthiness rule.

The runtime must preserve these distinctions explicitly instead of collapsing them into one generic conversion path.

---

## 9. Native C++ behavior is not semantic authority

### 9.1 No semantic reliance on implicit C++ meaning

Native C++ behavior may be used for storage, transport, and performance, but it is not the authority for Prism++ language meaning.

The runtime MUST NOT rely on C++ features such as:
- `operator bool`
- implicit constructors as language-semantic conversions
- STL/container truthiness
- raw pointer truthiness as a replacement for explicit runtime rules

---

## 10. Compile-time participation authority

### 10.1 One participation authority per behavior category

Allowed and rejected type combinations for a behavior category must be defined through one reviewable participation authority.

The runtime, matrix derivation, and generator expectations must not each grow independent hidden allowances.

---

## 11. Dependency direction for shared helpers

### 11.1 Shared helper layering must point downward

Family entry files may depend on shared semantic helpers.
Shared semantic helpers must not depend on family adapters for their meaning.

This avoids circular semantic ownership and keeps shared helpers reusable.

---

## 12. Spec traceability

### 12.1 Shared semantic helpers should link back to specs

Where practical, the authoritative helper for a behavior category should include a short comment that points to the spec(s) it implements.

This improves reviewability and reduces the chance of future code motion losing the semantic boundary.

---

## 13. Folder placement rule

### 13.1 Runtime design-rule documents belong in a dedicated specs folder

Runtime design-governance documents must live under `specs/architecture/runtime_design/`.

They must not be added loosely into the main `runtime/` folder.
This keeps runtime implementation directories focused on code and keeps design rules grouped in one discoverable location.

---

## 14. Practical review checklist

Before adding or moving a runtime helper for an operator, cast, intrinsic, or wrapper behavior, review all of the following:

1. Does this semantic family already have an authority helper?
2. If yes, can the new site call it directly?
3. If not, is a new authority actually required?
4. Will this helper create a parallel decision path?
5. Does it introduce a semantic fallback?
6. Does it rely on native C++ behavior for meaning?
7. Is dynamic dispatch duplicated?
8. Is the rejection path aligned with the same authority?
9. Is the file placement consistent with the family-ownership rule?
10. Does the helper comment identify what spec it implements?
11. Is `support/` staying non-semantic?
12. Is there exactly one intended compiler-visible entry point for the family?

If any of these answers is unclear, the change should be treated as drift-sensitive and reviewed before implementation proceeds.
