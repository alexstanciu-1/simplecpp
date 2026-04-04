# Reference Matrix PHP Tests

This folder adds a broad PHP-source test matrix for the Simple C++ project.

## Goals

- arrays
- pass by reference
- return by reference
- assign by reference

## Guardrails

These tests intentionally follow the documented reduced Simple C++ reference model.
They do **not** assume full PHP reference semantics or full PHP array semantics.

Positive tests only cover shapes that are currently documented as supported or intentionally targeted.
Negative tests capture shapes that should be rejected by analysis / lowering.

## Layout

- `tests/php/arrays/layer_02`
- `tests/php/pass_by_reference/layer_02`
- `tests/php/return_by_reference/layer_02`
- `tests/php/assign_by_reference/layer_02`
- `tests/php/rejected_reference_model/layer_02`

## Counts

- positive arrays: 52
- positive pass by reference: 40
- positive return by reference: 32
- positive assign by reference: 36
- negative rejected reference model: 44
- total: 204

## Notes

- Array `var_dump` output is expected to follow the project runtime, not raw PHP's reference-marking format.
- Negative cases are source fixtures for rejection coverage; they are not intended to compile successfully in the Simple C++ pipeline.
- Positive cases keep to the reduced supported surface documented in `specs/references.md`, `php_generator/specs/rules.md`, and `runtime/specs/simple_cpp_array_limitations.md`.
