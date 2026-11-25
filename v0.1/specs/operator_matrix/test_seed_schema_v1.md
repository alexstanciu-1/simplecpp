# Operator / Cast / Language Semantics Matrix
Doc Status: normative

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
- `build/operator_matrix/test_emission_report.json`

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
- current implementation note: `coalesce_reject_result_or_bool` is intentionally emitted as `negative_runtime` because the rejection lives in `php::coalesce_eval(...)` for now
- all other supported rows -> `positive`

### Suite Mapping

Current v1 mapping:
- `operators_conditional_selection` -> `php-matrix`

The current generator now also emits concrete PHP matrix tests into `tests/php-matrix/`.
The seed layer remains the canonical intermediate artifact consumed by the emitter.

---

## 6. Validation Rules

Validation must ensure:
- every required source row has at least one seed
- no duplicate `seed_id` values exist
- every seed references at least one source row id

---

## 7. Notes

v1 now includes deterministic PHP matrix test emission for `operators_conditional_selection`.
Concrete files are emitted under `tests/php-matrix/<item_id>/<level>/` using the existing `*.test-info.json` project contract.
Only the currently reliable positive non-wrapper slice is enabled by default; wrapper-heavy and negative slices are emitted as disabled `experimental` tests so the suite stays runnable while gaps remain visible.

Negative-generate emission is configurable:
- `--emit-negative-generate=none|all`
- `--enable-negative-generate=none|all`
- `--enable-negative-generate-diagnostic=<csv>`
- `--disable-negative-generate-diagnostic=<csv>`
- `--negative-generate-disabled-status=experimental|known_fail`
- `--strict-negative-generate-enable`

Enablement priority is deterministic:
1. explicit diagnostic denylist
2. explicit diagnostic allowlist
3. global negative-generate enable mode
4. default disabled state
