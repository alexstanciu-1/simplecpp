# Reference Matrix PHP Tests

This folder records a broad PHP-source reference matrix for the Simple C++ project.
It is now primarily a historical fixture snapshot, not the active metadata-driven harness surface.

## Goals

- arrays
- pass by reference
- return by reference
- assign by reference

## Guardrails

These fixtures were originally drafted against the reduced Simple C++ reference model.
Some older positive cases no longer match the current safe subset, especially slot-chain by-reference shapes.

Use the metadata-backed suites under `tests/php/references` and `tests/php/arrays` as the source of truth for current automated coverage.

## Layout

- `tests/php/arrays/level_03`
- `tests/php/pass_by_reference/level_02`
- `tests/php/return_by_reference/level_02`
- `tests/php/assign_by_reference/level_02`
- `tests/php/rejected_reference_model/level_02`

## Counts

- legacy positive arrays: 52
- legacy positive pass by reference: 40
- legacy positive return by reference: 32
- legacy positive assign by reference: 36
- legacy negative rejected reference model: 44
- total: 204

## Notes

- Array `var_dump` output is expected to follow the project runtime, not raw PHP's reference-marking format.
- Negative cases are source fixtures for rejection coverage; they are not intended to compile successfully in the Simple C++ pipeline.
- Several legacy positive fixtures predate the current rule that by-reference array slot access must resolve to directly stable native-reference-bindable storage.
- The catalog files in this folder should be read as a historical inventory unless and until the fixtures are migrated into `.test-info.json`-backed suites.
