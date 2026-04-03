# Reference / Array Test Matrix Plan

This document tracks the broad PHP-source test pack indexed under `tests/php/reference_matrix/` and stored in the regular `tests/php/*/level_02/` and layout `tests/php/*/level_03/` layout. 

## Intent

The goal is to expand source coverage around:

- arrays
- pass by reference
- return by reference
- assign by reference

## Critical rule

The matrix follows the **Simple C++** model, not full PHP semantics.

That means:

- positive fixtures stay inside the documented reduced reference model
- positive fixtures stay inside the documented reduced array subset
- negative fixtures capture shapes that should be rejected rather than treated as positive behavior

## Positive coverage

### Arrays
- flat writes
- nested writes on existing structure
- append
- direct slot by-ref argument passing
- by-value array copy isolation
- mutation of nested leaves within the supported subset

### Pass by reference
- scalar by-ref params
- array by-ref params
- root + nested leaf combinations
- direct DIM call arguments in supported shapes

### Return by reference
- explicit typed scalar ref returns
- explicit typed array ref returns
- direct forwarding through a known ref-returning function

### Assign by reference
- single stable alias
- static alias chains without rebinding
- direct DIM aliasing
- aliasing from an explicitly typed ref-returning call

## Negative coverage

The rejection bucket focuses on:

- alias rebinding
- conditional binding
- rebinding through alias chains
- slot/property rebinding patterns
- untyped ref returns
- branch-selected ref-return targets

## Current count

- 204 PHP fixtures total

See also:

- `tests/php/reference_matrix/README.md`
- `tests/php/reference_matrix/CATALOG.md`
