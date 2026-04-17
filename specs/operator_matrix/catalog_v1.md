# Operator / Cast / Language Semantics Matrix
## v1 Canonical Catalog

---

## 1. Purpose

Derived coordination spec.

This document defines the **canonical taxonomy and item inventory** for the dynamic
operator / cast / language semantics matrix.

This matrix is used for:
- semantic visualization (types × operators)
- edge-case coverage tracking
- automated test generation
- regression validation against specs and runtime

This document defines:
- semantic families
- exact items included in v1
- structural expectations for matrix generation

---

## 2. Authority Relationship

This document is subordinate to:
- `specs/spec_map.md`
- normative language specs under `specs/`
- subsystem normative specs under `runtime/specs/` and `generators/php/specs/`
- `runtime/specs/config.json` for current runtime-supported combinations

This document normalizes matrix structure and naming.
It does not override normative semantic behavior.

---

## 3. Design Constraints

The taxonomy must be:

- Stable (not tied to UI or implementation details)
- Complete enough for edge-case modeling
- Small enough to remain maintainable
- Aligned with runtime + specs authority

---

## 4. Top-Level Families (v1)

### F1. `condition_truthiness`

Evaluation of expressions in condition context.

#### Items
- `if_condition`
- `while_condition`
- `do_while_condition`
- `ternary_condition`
- `elvis_condition`

#### Notes
- Not equivalent to `(bool)` cast
- Uses restricted truthiness domain
- Must support runtime profile expansion

---

### F2. `casts_explicit`

Explicit user-facing casts.

#### Items
- `cast_bool`
- `cast_int`
- `cast_float`
- `cast_string`

#### Notes
- Includes compile-time + runtime validation
- Must model wrapper unwrapping behavior
- Must distinguish allowed vs runtime-failing conversions

---

### F3. `operators_unary`

Unary operators.

#### Items
- `logical_not`
- `unary_plus`
- `unary_minus`
- `bitwise_not`
- `pre_increment`
- `post_increment`
- `pre_decrement`
- `post_decrement`

#### Metadata flags
- `mutates_input` (for `++` / `--`)
- `requires_lvalue`
- `operand_target_kind_applicable`

---

### F4. `operators_binary_arithmetic_bitwise`

Binary arithmetic and bitwise operators.

#### Items

Arithmetic:
- `add`
- `sub`
- `mul`
- `div`
- `mod`

Bitwise / shift:
- `bit_and`
- `bit_or`
- `bit_xor`
- `shift_left`
- `shift_right`

---

### F5. `operators_binary_logical_relational`

Binary logical and relational operators.

#### Items

Logical:
- `logical_and`
- `logical_or`

Relational / value comparison:
- `equal`
- `not_equal`
- `less`
- `less_equal`
- `greater`
- `greater_equal`

#### Notes
- This family excludes strict PHP identity
- `===` / `!==` are intentionally modeled in a dedicated family

---

### F6. `operators_identity_strict`

Strict identity operators.

#### Items
- `identical`
- `not_identical`

#### Notes
- Dedicated family by design
- Helper-based semantics
- Uses PHP-visible normalization
- Wrapper-aware behavior required
- Must not be merged into ordinary comparison reporting

---

### F7. `operators_conditional_selection`

Selection operators.

#### Items
- `coalesce`
- `ternary`
- `elvis`

#### Notes
- Includes both condition evaluation and result-shape logic
- Must support wrapper-preserving behavior

---

### F8. `language_probes_and_reset`

Language-level helpers (not operators).

#### Items

Value probes:
- `isset_value`
- `empty_value`
- `count_value`

Keyed probes:
- `isset_keyed`
- `empty_keyed`

Reset / mutation:
- `unset_value`
- `unset_keyed`

#### Notes
- Keyed and non-keyed forms must be modeled separately
- `unset` semantics differ between value and keyed forms
- Operand target kinds are relevant for `unset` forms

---

## 5. Subfamily Tags

Each item must include a subfamily tag for filtering and reporting.

Allowed subfamilies:

- `condition`
- `cast`
- `unary`
- `arithmetic`
- `bitwise`
- `logical`
- `relational`
- `identity`
- `selection`
- `probe`
- `reset`

---

## 6. Matrix Structure Requirements

The matrix MUST NOT be limited to type-level combinations.

Each evaluation must include:

- type
- profile
- family-specific behavior

For families that mutate or target storage, matrix evaluation must also include:

- operand target kind

---

## 7. Required Axes

Each matrix entry must support:

- `item_id`
- `lhs_type`
- `rhs_type` (if applicable)
- `third_type` (ternary only)
- `lhs_profile`
- `rhs_profile`
- `third_profile`

And, when applicable:

- `lhs_target_kind`
- `rhs_target_kind`
- `third_target_kind`

---

## 8. Summary

v1 defines a controlled semantic surface consisting of:

- condition evaluation
- explicit casts
- unary operators
- binary operators
- strict identity operators
- conditional selection
- language probes
- reset operations

All evaluation must be:

- profile-aware
- wrapper-aware
- target-kind-aware where required
- edge-case explicit

This catalog is the foundation for:
- matrix generation
- visualization
- automated test synthesis
