# Reference / Array Test Matrix Plan

This document tracks the broad PHP-source test pack indexed under `tests/php/reference_matrix/` and stored in the regular `tests/php/*/level_02/` and `tests/php/*/level_03/` layout.

See also:
- `specs/references.md`
- `specs/native_reference_safety.md`

## Intent

The goal is to expand source coverage around:
- arrays
- pass by reference
- return by reference
- assign by reference

## Critical rule

The matrix follows the **Prism++** model, not full PHP semantics.

That means:
- positive fixtures stay inside the documented reduced reference model
- positive fixtures stay inside the documented reduced array subset
- negative fixtures capture shapes that should be rejected rather than treated as positive behavior

## Positive coverage

### Arrays
- flat writes
- nested writes on existing structure
- append
- by-value array copy isolation
- mutation of nested leaves within the supported subset

### Pass by reference
- scalar by-ref params from directly stable sources
- stable local alias chains without rebinding
- direct stable field / parameter forwarding where native-reference safety is preserved

### Return by reference
- explicit typed scalar ref returns from directly stable sources
- direct forwarding through a known ref-returning function when the target is already native-reference bindable

### Assign by reference
- single stable alias
- static alias chains without rebinding
- direct stable local/parameter binding only

## Negative coverage

The rejection bucket focuses on:
- alias rebinding
- conditional binding
- rebinding through alias chains
- slot/property rebinding patterns
- untyped ref returns
- branch-selected ref-return targets
- any `[]`-rooted reference binding
- by-reference returns of dynamic slot/property chains
- any source form that would require `.as_*_ref()` or equivalent native interior extraction
- `try_ref(...)` on non-`shared_p<T>` element types

## Current count

- 204 PHP fixtures total

See also:
- `tests/php/reference_matrix/README.md`
- `tests/php/reference_matrix/CATALOG.md`
