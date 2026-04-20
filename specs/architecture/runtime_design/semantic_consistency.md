# Runtime Semantic Consistency and Anti-Drift Rules

Status: active normative architecture rule.

## Purpose

This document defines required runtime code-organization rules for semantic families such as casts, conditional selection, logical operators, `isset`, `empty`, `count`, and coalesce.

The goal is to prevent semantic drift between:

- runtime implementation
- operator matrix / generator expectations
- higher-level language/spec documents

These rules govern how runtime code must be structured so that shared behavior is implemented once, audited once, and changed once.

---

## 1. Operator family structure

### 1.1 One operator family per file

Each operator family MUST be implemented in a dedicated file or dedicated family-owned file group.

Examples:
- `coalesce.hpp`
- `conditional_selection.hpp`
- `logical.hpp`
- `comparison_strict.hpp`

The exact filename may vary with the current runtime layout, but ownership must remain one family per file boundary.

### 1.2 Thin public entry points

Public operator-family entry points MUST stay thin.

They may:
- validate family-specific preconditions
- call shared semantic helpers
- normalize the returned branch or result

They MUST NOT embed large duplicated semantic decision trees inline when the same behavior already exists in a shared helper.

---

## 2. Shared behavior rules

### 2.1 Shared behavior means the same function

If two or more operators are documented as sharing behavior, they MUST call the same semantic helper.

Equivalent-looking duplicated code is not sufficient.

Examples:
- ternary and elvis must share the same condition helper when their condition evaluation is defined as common behavior
- logical operators and conditional operators must share the same condition-truthiness authority when defined as common behavior

### 2.2 No parallel implementations

If a semantic helper already exists for a behavior category, new code MUST:
- reuse that helper, or
- explicitly replace that helper

It MUST NOT introduce a second helper that reimplements the same rule set in parallel.

---

## 3. Semantic authorities

### 3.1 One public semantic entry point per behavior category

Each behavior category MUST expose one public semantic authority.

Typical examples include:
- `condition_truthy(...)`
- `coalesce_has_usable_value(...)`
- `normalize_selected_branch(...)`
- `cast<T>(...)`
- `isset(...)`
- `empty(...)`
- `count(...)`

Internal helper layers may exist below the public authority, but there must be one reviewable semantic entry point.

### 3.2 Explicit ownership and boundary declaration

Each public semantic helper SHOULD state clearly in comments:
- the behavior category it owns
- the inputs it supports
- the inputs it rejects
- whether it is runtime authority, compile-time participation authority, or both
- the spec document(s) it implements

---

## 4. No semantic fallbacks

### 4.1 No fallback to unrelated semantic paths

A missing semantic specialization MUST NOT silently fall back to an unrelated path.

Forbidden examples:
- falling back from condition evaluation to generic `cast<bool>` because a direct condition rule is missing
- falling back to native C++ conversions because no explicit Prism++ rule was written
- relying on overload resolution to discover semantics that were not intentionally declared

A behavior must be:
- explicitly handled, or
- explicitly rejected

### 4.2 Rejection paths are part of the design

Invalid or unsupported cases must be rejected through stable, centralized error paths.
Rejection behavior must not drift independently from acceptance behavior.

---

## 5. Truthiness and condition handling

### 5.1 Single condition-truthiness authority

All control-flow truthiness decisions MUST route through the same semantic authority.

This applies to:
- `if`, `while`, `for`
- ternary (`cond ? a : b`)
- elvis (`cond ?: b`)
- logical operators (`!`, `&&`, `||`)

### 5.2 Truthiness is not generic bool cast

Condition truthiness and bool conversion are distinct semantic domains unless a higher-level spec explicitly states otherwise.

Current example intentionally preserved by this rule:
- `condition_truthy(string_t)` follows control-flow truthiness rules
- `string_t -> bool_t` normalization follows strict bool-literal parsing rules

Those paths must not be merged accidentally.

---

## 6. Wrapper and dynamic-type handling

### 6.1 Wrapper delegation must be centralized

When a wrapper type participates in a semantic family by delegating to a carried or lowered kind, that delegation must be implemented centrally.

This applies to categories such as:
- `nullable<T>`
- `result<T>`
- `result_or_false<T>`
- `result_or_bool<T>` where explicitly allowed
- `mixed_t`

### 6.2 Dynamic runtime dispatch must exist in one place

For dynamic carriers such as `mixed_t`, runtime kind dispatch MUST be centralized.

The same family-specific `switch(kind)` or equivalent dispatch logic must not be duplicated independently across multiple operator implementations.

---

## 7. Object-handle condition semantics

### 7.1 Object-handle truthiness must be explicit

When object handles participate in condition context, their truthiness must be implemented explicitly in the shared condition helper.

Required semantics:
- `shared_p<T>`: non-null is true, null is false
- `unique_p<T>`: non-null is true, null is false
- `weak_p<T>`: live target is true, expired or empty handle is false

This behavior must not be left to native C++ conversions or incidental fallback behavior.

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

The runtime, operator-matrix derivation, and generator expectations must not each grow independent hidden allowances.

---

## 11. Dependency direction for shared helpers

### 11.1 Shared helper layering must point downward

Operator-family files may depend on shared semantic helpers.
Shared semantic helpers must not depend on operator-family-specific wrappers for their meaning.

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

Before adding a new runtime helper for an operator, cast, intrinsic, or wrapper behavior, review all of the following:

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

If any of these answers is unclear, the change should be treated as drift-sensitive and reviewed before implementation proceeds.
