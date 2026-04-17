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

Implemented items:
- `if_condition`
- `cast_bool`

Implemented types:
- `bool_t`
- `int_t`
- `nullable<int_t>`

Test generation is out of scope for this phase.

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

Exit codes:
- `0` → generation and validation succeeded
- `1` → usage or IO failure
- `2` → validation errors were found
