# TYPE_FAMILY_REGISTRY.md

## 1. Scope

This document is **normative**.

It defines the **canonical type-family registry** for the Simple C++ system.

It specifies:
- the complete set of semantic type families currently supported
- the classification of each family
- template arity
- trait membership
- family-level compatibility constraints
- family-level operator participation categories
- family-level conversion participation categories

It does NOT define complete allowed/forbidden behavior for every operation pair.

Full operational behavior MUST be resolved in:
- DERIVATION_RULES.md
- SEMANTIC_MATRIX.md

---

## 2. Authority Relationship

1. SPECIFICATIONS.md — semantic core and authority model
2. TYPE_FAMILY_REGISTRY.md — canonical family inventory and traits
3. DERIVATION_RULES.md — deterministic semantic derivation rules
4. SEMANTIC_MATRIX.md — normalized derived operational outcomes
5. Generated artifacts
6. Non-normative documents

If any conflict exists:
- SPECIFICATIONS.md governs semantic intent and invariants
- this document governs family identity, classification, and trait membership
- DERIVATION_RULES.md governs derivation logic
- SEMANTIC_MATRIX.md governs normalized resolved operational outcomes

---

## 3. Closed-World Rule

The set of type families is closed.

If a family is not declared in this document, it does not exist in the current language definition.

No other document may introduce a new type family, alias a family into existence, or redefine family classification.

---

## 4. Registry Field Definitions

Each family entry MUST define:

- family_name
- kind
- template_arity
- traits
- equality_compatibility_class
- assignment_compatibility_class
- explicit_conversion_category
- notes

Field meanings:

### 4.1 kind

Allowed values:
- primitive
- literal-like
- nullable-like
- pointer-like
- container-like

### 4.2 template_arity

Non-template families use `0`.

Unary template families use `1`.

Binary template families use `2`.

### 4.3 traits

Traits are closed and finite.

The allowed traits in the current system are:

- `arithmetic_numeric`
- `logical_boolean`
- `equality_comparable`
- `relational_numeric`
- `conditional_boolean`
- `nullable_family`
- `pointer_family`
- `null_literal_family`

Traits classify participation.
Traits do not by themselves authorize a concrete operation pair.
Concrete authorization is determined only through DERIVATION_RULES.md and resolved in SEMANTIC_MATRIX.md.

### 4.4 compatibility classes

Compatibility classes allow derivation rules to express family-level compatibility without repeating family inventories in multiple documents.

They are closed in the current system.

Allowed equality compatibility classes:
- `numeric_cross`
- `bool_self`
- `string_self`
- `null_self`
- `nullable_same_T_plus_null`
- `shared_same_T`
- `unique_same_T`
- `weak_same_T`

Allowed assignment compatibility classes:
- `direct_self`
- `numeric_explicit_only`
- `nullable_same_T_plus_null`
- `shared_same_T`
- `unique_same_T`
- `weak_same_T`
- `none`

### 4.5 explicit_conversion_category

Allowed values:
- `numeric`
- `bool_restricted`
- `string_restricted`
- `nullable_restricted`
- `pointer_restricted`
- `null_restricted`

---

## 5. Canonical Family Registry

### 5.1 `int_t`

- family_name: `int_t`
- kind: primitive
- template_arity: 0
- traits:
  - `arithmetic_numeric`
  - `equality_comparable`
  - `relational_numeric`
- equality_compatibility_class: `numeric_cross`
- assignment_compatibility_class: `direct_self`
- explicit_conversion_category: `numeric`
- notes:
  - participates in numeric arithmetic
  - participates in numeric equality
  - participates in numeric relational comparison
  - is not conditionally valid directly

### 5.2 `float_t`

- family_name: `float_t`
- kind: primitive
- template_arity: 0
- traits:
  - `arithmetic_numeric`
  - `equality_comparable`
  - `relational_numeric`
- equality_compatibility_class: `numeric_cross`
- assignment_compatibility_class: `direct_self`
- explicit_conversion_category: `numeric`
- notes:
  - participates in numeric arithmetic
  - participates in numeric equality
  - participates in numeric relational comparison
  - is not conditionally valid directly

### 5.3 `bool_t`

- family_name: `bool_t`
- kind: primitive
- template_arity: 0
- traits:
  - `logical_boolean`
  - `equality_comparable`
  - `conditional_boolean`
- equality_compatibility_class: `bool_self`
- assignment_compatibility_class: `direct_self`
- explicit_conversion_category: `bool_restricted`
- notes:
  - participates in logical operators
  - participates only in self-family equality
  - is conditionally valid directly

### 5.4 `string_t`

- family_name: `string_t`
- kind: primitive
- template_arity: 0
- traits:
  - `equality_comparable`
- equality_compatibility_class: `string_self`
- assignment_compatibility_class: `direct_self`
- explicit_conversion_category: `string_restricted`
- notes:
  - participates only in self-family equality
  - has no arithmetic participation
  - is not conditionally valid directly

### 5.5 `null_t`

- family_name: `null_t`
- kind: literal-like
- template_arity: 0
- traits:
  - `equality_comparable`
  - `null_literal_family`
- equality_compatibility_class: `null_self`
- assignment_compatibility_class: `none`
- explicit_conversion_category: `null_restricted`
- notes:
  - participates in self-family equality
  - participates in family-specific nullable equality rules
  - participates in family-specific nullable assignment rules
  - is not conditionally valid directly

### 5.6 `nullable<T>`

- family_name: `nullable<T>`
- kind: nullable-like
- template_arity: 1
- traits:
  - `equality_comparable`
  - `nullable_family`
- equality_compatibility_class: `nullable_same_T_plus_null`
- assignment_compatibility_class: `nullable_same_T_plus_null`
- explicit_conversion_category: `nullable_restricted`
- notes:
  - equality is restricted to same-`T` or `null_t` where separately authorized
  - assignment is restricted to same-`T` or `null_t` where separately authorized
  - no arithmetic participation
  - not conditionally valid directly

### 5.7 `shared_p<T>`

- family_name: `shared_p<T>`
- kind: pointer-like
- template_arity: 1
- traits:
  - `equality_comparable`
  - `pointer_family`
- equality_compatibility_class: `shared_same_T`
- assignment_compatibility_class: `shared_same_T`
- explicit_conversion_category: `pointer_restricted`
- notes:
  - equality is same-family same-`T` only unless separately authorized
  - assignment is same-family same-`T` only unless separately authorized
  - not conditionally valid directly

### 5.8 `unique_p<T>`

- family_name: `unique_p<T>`
- kind: pointer-like
- template_arity: 1
- traits:
  - `equality_comparable`
  - `pointer_family`
- equality_compatibility_class: `unique_same_T`
- assignment_compatibility_class: `unique_same_T`
- explicit_conversion_category: `pointer_restricted`
- notes:
  - equality is same-family same-`T` only unless separately authorized
  - assignment is same-family same-`T` only unless separately authorized
  - not conditionally valid directly

### 5.9 `weak_p<T>`

- family_name: `weak_p<T>`
- kind: pointer-like
- template_arity: 1
- traits:
  - `equality_comparable`
  - `pointer_family`
- equality_compatibility_class: `weak_same_T`
- assignment_compatibility_class: `weak_same_T`
- explicit_conversion_category: `pointer_restricted`
- notes:
  - equality is same-family same-`T` only unless separately authorized
  - assignment is same-family same-`T` only unless separately authorized
  - not conditionally valid directly

---

## 6. Extensibility Rule

Any new type family, including future families such as `map_t<K, V>`, MUST be introduced here first.

A family addition is incomplete and invalid unless all of the following are updated coherently:
- TYPE_FAMILY_REGISTRY.md
- DERIVATION_RULES.md
- SEMANTIC_MATRIX.md
- RUNTIME_API_CONTRACT.md
- TEST_MATRIX.md
- TEST_MATERIALIZATION_CONTRACT.md where template materialization is affected

---

## Final Statement

This document defines the canonical family inventory and trait model.

It does not replace the operational matrix.

It exists to make matrix derivation explicit, extensible, and generator-safe.
