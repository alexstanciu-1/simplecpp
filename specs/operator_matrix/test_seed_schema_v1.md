# Operator / Cast / Language Semantics Matrix
## v1 Test Seed Schema

---

## 1. Purpose

This document defines the canonical intermediate artifact produced between:
- source matrix row generation
- concrete test emission

The v1 seed layer is intentionally conservative and row-faithful.
In the current working slice, only `operators_conditional_selection` rows participate. Each eligible matrix row with a non-empty `test_seed_class` produces exactly one seed.

---

## 2. Output Artifact

Generator output path:

- `build/operator_matrix/test_seeds.json`
- `build/operator_matrix/test_seed_validation_report.json`

---

## 3. Seed Shape

```json
{
	"seed_id": "",
	"family_id": "",
	"item_id": "",
	"subfamily_id": "",
	"arity": 0,
	"test_seed_class": "",
	"matrix_status": "",
	"behavior_class": null,
	"outcome_class": "",
	"suite": "",
	"target_flow": "",
	"level": "",
	"feature": "",
	"group": "",
	"relative_directory": "",
	"suggested_stem": "",
	"suggested_source_path": "",
	"suggested_info_path": "",
	"operands": {
		"lhs": {"type": null, "profile": null, "target_kind": null},
		"rhs": {"type": null, "profile": null, "target_kind": null},
		"third": {"type": null, "profile": null, "target_kind": null}
	},
	"expected": {
		"result_type": null,
		"result_profile": null,
		"diagnostic_class": null
	},
	"edge_case": false,
	"edge_case_id": null,
	"source_row_ids": [],
	"source_family_refs": [],
	"source_item_refs": [],
	"notes": null
}
```

---

## 4. Determinism Rules

- `seed_id` must be derived deterministically from the source row id
- path suggestions must be deterministic
- byte-for-byte equivalent inputs must produce byte-for-byte equivalent seed ordering

---

## 5. v1 Mapping Rules

### Row Inclusion

Generate one seed for every `operators_conditional_selection` matrix row whose `test_seed_class` is non-empty.

### Outcome Class

- `compile_time_rejected` -> `negative_generate`
- `unsupported_by_runtime_surface` -> `negative_generate`
- `supported + throws` -> `negative_runtime`
- all other supported rows -> `positive`

### Suite Mapping

Current v1 mapping:
- `operators_conditional_selection` -> `php-matrix`

This is only a planning target for later test emission.
The seed layer itself does not emit concrete tests.

---

## 6. Validation Rules

Validation must ensure:
- every required source row has at least one seed
- no duplicate `seed_id` values exist
- every seed references at least one source row id

---

## 7. Notes

v1 intentionally avoids test-file emission.
That remains a later phase consuming this seed artifact and the existing `*.test-info.json` project contract.
