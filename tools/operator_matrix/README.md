# Operator Matrix Tooling

Status: v1 working subset

---

## Purpose

This tool generates machine-readable operator-matrix rows from structured JSON
input stored under `specs/operator_matrix/data/`.

The tool intentionally does **not** parse Markdown directly.
The Markdown docs remain human-facing coordination specs.
The JSON files provide the normalized input required by the generator.

---

## Current Scope

Implemented families:
- `condition_truthiness`
- `casts_explicit`
- `operators_conditional_selection`

Implemented items:
- `if_condition`
- `cast_bool`
- `coalesce`
- `elvis`
- `ternary`

Current working type universe in structured data includes:
- `bool_t`
- `int_t`
- `float_t`
- `string_t`
- `nullable<T>` for current scalar `T`
- `mixed_t`
- `result<T>` for current scalar `T`
- `result_or_false<T>` for current scalar `T`
- `result_or_bool<T>` for current scalar `T`

Current `elvis` slice:
- supports same-type rows for non-wrapper `bool_t`, `int_t`, `float_t`, and `mixed_t`
- emits compile-time rejected rows for the current wrapper families until a dedicated wrapper-aware `elvis` policy is specified

Current `ternary` slice:
- supports same-type `then/else` rows for `bool_t`, `int_t`, `float_t`, `string_t`, and `mixed_t`
- accepts condition types `bool_t`, `int_t`, `float_t`, and `mixed_t`
- keeps wrapper families out of the condition position in this working slice

Test-file emission is out of scope for this phase. The tool now emits row-faithful test seeds as an intermediate planning artifact for the current `operators_conditional_selection` slice.

---

## CLI

Run from the project root:

```bash
php tools/operator_matrix/generator.php
php tools/operator_matrix/generator.php --family=condition_truthiness
php tools/operator_matrix/generator.php --validate
php tools/operator_matrix/generator.php --stdout
```

Generated artifacts:
- `build/operator_matrix/matrix.json`
- `build/operator_matrix/validation_report.json`
- `build/operator_matrix/test_seeds.json`
- `build/operator_matrix/test_seed_validation_report.json`

Exit codes:
- `0` → generation and validation succeeded
- `1` → usage or IO failure
- `2` → validation errors were found
