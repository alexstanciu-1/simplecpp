# Operator Matrix Structured Data

This folder stores the machine-readable input consumed by `tools/operator_matrix/generator.php`.

## Files
- `families.json` — registered families/items and grouped authority references
- `types.json` — canonical type ids and fully enumerated profiles for the current working slice
- `semantics.json` — profile-explicit source rows per unary or binary definition

## Current Working Slice
Families:
- `condition_truthiness`
- `casts_explicit`
- `operators_conditional_selection`

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

## Rules
- every source row must remain profile-explicit
- aggregated rows do not belong in this dataset
- every definition must cover the exact profile set declared for its `lhs_type`
- the generator validates completeness for the current working slice
