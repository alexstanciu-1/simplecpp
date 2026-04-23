# Operator / Cast / Language Semantics Matrix
Doc Status: normative

---

## 1. Purpose

Derived coordination spec.

This document defines how matrix families map to their semantic sources of truth.

The mapping is **family-first by design**.
Item-level source mapping is used only for exceptional semantics.

---

## 2. Core Rule

The matrix must map each family to:
- primary normative sources
- secondary/supporting sources
- optional item-level overrides where family-level mapping is insufficient

This avoids duplicating source declarations for every individual operator.

---

## 3. Precedence

When sources disagree, precedence is:

1. `specs/spec_map.md`
2. normative language specs under `specs/`
3. subsystem normative specs under `runtime/specs/` and `generators/php/specs/`
4. `runtime/specs/config.json` for current runtime-supported surface
5. operator-matrix derived docs under `specs/operator_matrix/`
6. implementation/code-derived observations

---

## 4. Family-First Mapping

### `condition_truthiness`
Primary sources:
- `specs/conditional_expression_matrix.md`
- relevant truthiness rules in core/runtime specs

Secondary sources:
- `runtime/specs/config.json` â†’ condition coercion support

### `casts_explicit`
Primary sources:
- `runtime/specs/config.json` â†’ cast surface
- `specs/dynamic_types.md`
- wrapper/mixed cast specs where applicable

### `operators_unary`
Primary sources:
- runtime operator configuration
- relevant numeric/dynamic type specs

### `operators_binary_arithmetic_bitwise`
Primary sources:
- runtime operator configuration
- relevant numeric/dynamic type specs

### `operators_binary_logical_relational`
Primary sources:
- runtime operator configuration
- relevant comparison/logical semantics specs

### `operators_identity_strict`
Primary sources:
- helper-owned strict identity semantics
- wrapper normalization specs

Reason for override:
- `===` / `!==` are intentionally isolated from ordinary comparison and therefore need their own family-level source mapping

### `operators_conditional_selection`
Primary sources:
- `specs/conditional_expression_matrix.md`
- wrapper/result-shape specs
- runtime support rules

### `language_probes_and_reset`
Primary sources:
- `specs/count_empty_isset_contract.md`
- `specs/array_semantics.md`
- runtime support rules

---

## 5. Item-Level Override Rule

Use item-level source overrides only when:
- the item has unique semantics inside its family
- the item is helper-routed while peers are not
- the item is governed by a dedicated contract not shared by the rest of the family

Examples:
- `unset_keyed` may later point to a dedicated keyed-removal rule
- a specific cast item may point to a dedicated boundary-transition spec

---

## 6. Maintenance Rule

Whenever a normative family source changes, all affected matrix docs must be reviewed.
At minimum this may affect:
- `profile_semantics_v1.md`
- `generation_rules_v1.md`
- `output_schema_v1.md`
- `test_generation_rules_v1.md`

---

## 7. Summary

The source-of-truth model is:
- family-first
- override-only when necessary
- aligned to normative specs first and runtime surface second

## 8. Relationship to Concrete Mapping

This policy document defines the mapping strategy.
The concrete project mapping is recorded in `source_mapping_v1.md`.
