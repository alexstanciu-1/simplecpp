# Operator Matrix Structured Data
Doc Status: supporting

This folder stores the machine-readable input consumed by `tools/operator_matrix/generator.php`.

## Files
- `families.json` - registered families/items and grouped authority references
- `types.json` - canonical type ids and fully enumerated profiles for the current working slice
- `semantics.index.json` - definition index describing family/item/type groupings and TSV row-file paths
- `semantics/<family>/<item>.tsv` - profile-explicit flat row data, one line per semantic row

## Current Working Slice
Families:
- `condition_truthiness`
- `casts_explicit`
- `operators_conditional_selection`
- `operators_unary`
- `operators_binary_arithmetic`
- `operators_binary_logical`
- `operators_comparison_equality`

Implemented items:
- `if_condition`
- `cast_bool`
- `cast_int`
- `cast_float`
- `cast_string`
- `coalesce`
- `elvis`
- `ternary`
- `logical_not`
- `unary_plus`
- `unary_minus`
- `bitwise_not`
- `pre_increment`
- `post_increment`
- `pre_decrement`
- `post_decrement`
- `add`
- `subtract`
- `multiply`
- `divide`
- `modulo`
- `logical_and`
- `logical_or`
- `equal`
- `not_equal`

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
- approved `coalesce` wrapper families auto-unpack to their usable value domain: `nullable<T>`, `result<T>`, and `result_or_false<T>`
- current `coalesce` runtime rejection includes `result_or_bool<T>` participation and selected-branch rows that still have no usable value domain
- `mixed_t(null)` remains a valid selected mixed result domain for `coalesce` when it is the selected fallback branch
- `elvis`
- `ternary` currently supports same-type rows for non-wrapper `bool_t`, `int_t`, `float_t`, and `mixed_t`
- current wrapper families are still emitted as compile-time rejected `elvis` rows in the structured dataset pending matrix expansion, even though the runtime ternary/elvis helper already has wrapper-aware condition delegation for current wrapper families
- `ternary` currently supports same-type `then/else` rows for non-wrapper branch types and uses the current condition-truthiness slice for the condition operand

## Rules
- every source row must remain profile-explicit
- aggregated rows do not belong in this dataset
- every definition must cover the exact profile set declared for its `lhs_type`
- binary definitions must cover the exact profile Cartesian product declared for `lhs_type` and `rhs_type`
- the generator validates completeness for the current working slice
- TSV row files should stay flat and line-oriented: one header row, then one semantic row per line

## Runtime Reject Expectations

Runtime-reject tests should:
- prefer JSON mode (`SCPP_ERROR_FORMAT=json`)
- validate:
  - `error.code`
- avoid:
  - substring matching on message

This ensures stability when messages change.
