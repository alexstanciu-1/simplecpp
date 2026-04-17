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
- `operators_conditional_selection`

Items:
- `if_condition`
- `cast_bool`
- `coalesce`
- `elvis`

Type universe currently present in structured data:
- `bool_t`
- `int_t`
- `float_t`
- `string_t`
- `nullable<T>` for current scalar `T`
- `mixed_t`
- `result<T>` for current scalar `T`
- `result_or_false<T>` for current scalar `T`
- `result_or_bool<T>` for current scalar `T`

Current `elvis` working slice:
- same-type rows for non-wrapper `bool_t`, `int_t`, `float_t`, and `mixed_t`
- compile-time rejected rows for the current wrapper families until a dedicated wrapper-aware `elvis` policy is specified

## Current Guarantees
- deterministic `row_id`
- full profile-explicit source rows
- validation for unknown ids and duplicate row ids
- validation for exact profile coverage per definition, including binary `(family_id, item_id, lhs_type, rhs_type)` definitions

## Output
- `build/operator_matrix/matrix.json`
- `build/operator_matrix/validation_report.json`

## Notes
- this generator reads structured JSON only
- it does not parse Markdown
- test generation remains a separate phase
- config-level contradiction checks are intentionally deferred from this working slice
