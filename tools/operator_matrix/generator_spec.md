# Operator Matrix Generator (v1 Working Slice)

## Purpose
Provide a clean executable pipeline that:
- reads structured operator-matrix data
- validates family/type/profile coverage
- emits deterministic source rows
- writes build artifacts for review

## Inputs
- `specs/operator_matrix/data/families.json`
- `specs/operator_matrix/data/types.json`
- `specs/operator_matrix/data/semantics.json`

## Current Scope
Families:
- `condition_truthiness`
- `casts_explicit`

Items:
- `if_condition`
- `cast_bool`

Types covered:
- `bool_t`
- `int_t`
- `float_t`
- `string_t`
- `nullable<int_t>`
- `nullable<bool_t>`
- `nullable<float_t>`
- `nullable<string_t>`
- `mixed_t`

## Current Guarantees
- deterministic `row_id`
- full profile-explicit source rows
- validation for unknown ids and duplicate row ids
- validation for exact profile coverage per `(family_id, item_id, lhs_type)` definition

## Output
- `build/operator_matrix/matrix.json`
- `build/operator_matrix/validation_report.json`

## Notes
- This generator reads structured JSON only
- it does not parse Markdown
- test generation remains a separate phase
- config-level contradiction checks are intentionally deferred from this working slice
