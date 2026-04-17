# Operator Matrix Structured Data

This folder stores the machine-readable input consumed by `tools/operator_matrix/generator.php`.

## Files
- `families.json` — registered families/items and grouped authority references
- `types.json` — canonical type ids and fully enumerated profiles for the current working slice
- `semantics.json` — profile-explicit source rows per unary, binary, or ternary definition

## Current Working Slice
Families:
- `condition_truthiness`
- `casts_explicit`
- `operators_conditional_selection`

Implemented items:
- `if_condition`
- `cast_bool`
- `coalesce`
- `elvis`
- `ternary`

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

Current selection-family support:
- `coalesce` includes the wrapper-aware policy already documented for approved wrapper families
- `elvis`
- `ternary` currently supports same-type rows for non-wrapper `bool_t`, `int_t`, `float_t`, and `mixed_t`
- current wrapper families are emitted as compile-time rejected `elvis` rows until a dedicated wrapper-aware `elvis` policy is specified
- `ternary` currently supports same-type `then/else` rows for non-wrapper branch types and uses the current condition-truthiness slice for the condition operand

## Rules
- every source row must remain profile-explicit
- aggregated rows do not belong in this dataset
- every definition must cover the exact profile set declared for its `lhs_type`
- binary definitions must cover the exact profile Cartesian product declared for `lhs_type` and `rhs_type`
- the generator validates completeness for the current working slice
