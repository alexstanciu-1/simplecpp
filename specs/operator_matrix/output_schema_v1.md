# Operator / Cast / Language Semantics Matrix
## v1 Output Schema

---

## 1. Purpose

Derived coordination spec.

This document defines the canonical row schema for generated matrix data.
The schema is profile-aware and, where required, target-kind-aware.

---

## 2. Row Shape

Each row must support these fields.
Fields may be omitted/null only when not applicable.

```json
{
	"row_id": "",
	"family_id": "",
	"item_id": "",
	"subfamily_id": "",
	"arity": 0,
	"lhs_type": null,
	"rhs_type": null,
	"third_type": null,
	"lhs_profile": null,
	"rhs_profile": null,
	"third_profile": null,
	"lhs_target_kind": null,
	"rhs_target_kind": null,
	"third_target_kind": null,
	"status": "",
	"behavior_class": null,
	"result_type": null,
	"result_profile": null,
	"edge_case": false,
	"edge_case_id": null,
	"test_seed_class": null,
	"diagnostic_class": null,
	"source_family_refs": [],
	"source_item_refs": [],
	"notes": null
}
```

---

## 3. Required Core Fields

Always required:
- `row_id`
- `family_id`
- `item_id`
- `subfamily_id`
- `arity`
- `status`
- `edge_case`
- `source_family_refs`

Required for supported rows:
- `behavior_class`

Required when applicable:
- operand types
- operand profiles
- operand target kinds
- result fields
- test metadata

---

## 4. Status Enum

Allowed `status` values:
- `supported`
- `compile_time_rejected`
- `unsupported_by_runtime_surface`

### Notes
- `status` answers whether the row belongs to the current supported semantic/runtime surface.
- It does not describe runtime behavior by itself.

---

## 5. Behavior Class Enum

Allowed `behavior_class` values:
- `deterministic_value`
- `throws`
- `noop`
- `failure_value`
- `helper_routed`

### Notes
- `behavior_class` is required only for `status=supported` rows.
- supported rows with `behavior_class=throws` may omit `result_type` / `result_profile` when the operation has no successful value result.
- `compile_time_rejected` and `unsupported_by_runtime_surface` rows must not invent runtime behavior.

---

## 6. Target Kind Fields

Target-kind fields are used only when an item/family depends on targetability or mutation validity.

Allowed values are defined by `operand_target_kinds_v1.md`:
- `plain_value`
- `assignable_variable`
- `keyed_element`
- `temporary_result`

When not applicable, target-kind fields remain null/absent.

---

## 7. Source Reference Fields

### `source_family_refs`
Family-level authoritative references.
These are required for every row.

### `source_item_refs`
Optional item-level override references.
Use only where the family-level mapping is insufficient.

---

## 8. Diagnostic and Test Fields

### `diagnostic_class`
Canonical classification string for:
- throw reasons
- compile-fail reasons
- unsupported-surface reasons

Examples:
- `invalid_condition_type`
- `invalid_mutation_target_kind`
- `unsupported_runtime_surface_combination`
- `invalid_mixed_kind_for_cast_bool`

### `test_seed_class`
Canonical high-level mapping used by test generation.
Examples:
- `runtime_success`
- `runtime_throw`
- `runtime_noop`
- `compile_fail`
- `helper_regression`

---

## 9. Determinism Rule

Rows must be emitted in deterministic order.
The same inputs must produce byte-for-byte equivalent row ordering and identifiers.

---

## 10. Aggregation Rule

This schema is for **source matrix rows**.
Aggregated/UI summary rows must be derived from these rows and must not replace them.
