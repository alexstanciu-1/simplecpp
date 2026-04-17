# Operator / Cast / Language Semantics Matrix
## v1 Test Generation Rules

---

## 1. Purpose

Derived coordination spec.

This document defines how canonical matrix rows become tests.
It consumes rows that already conform to `output_schema_v1.md`.

---

## 2. Test Generation Inputs

Required row fields:
- `item_id`
- operand type/profile fields
- operand target kind fields when applicable
- `status`
- `behavior_class`
- `edge_case`
- `diagnostic_class`
- `test_seed_class`

---

## 3. Primary Mapping Rules

### `status=compile_time_rejected`
Generate:
- compile-fail test

Expected assertions:
- generation/compile must fail for the encoded reason
- test should assert the expected `diagnostic_class` where practical

### `status=unsupported_by_runtime_surface`
Normally do not generate runtime execution tests.
Generate only surface/regression tests if the project chooses to lock this gap explicitly.

### `status=supported` + `behavior_class=deterministic_value`
Generate:
- runtime success test

Expected assertions:
- compile succeeds
- runtime succeeds
- result matches exact expected value/profile

### `status=supported` + `behavior_class=throws`
Generate:
- runtime throw test

Expected assertions:
- compile succeeds
- runtime fails through the expected throw/error path
- `diagnostic_class` matches where practical

### `status=supported` + `behavior_class=noop`
Generate:
- runtime noop test

Expected assertions:
- compile succeeds
- runtime succeeds
- target state remains unchanged where noop semantics apply

### `status=supported` + `behavior_class=helper_routed`
Generate:
- helper-routed semantic regression test

Expected assertions:
- behavior matches the helper-owned semantic contract
- wrapper/mixed normalization is exercised explicitly

### `status=supported` + `behavior_class=failure_value`
Generate:
- runtime failure-value test

Expected assertions:
- compile succeeds
- runtime succeeds
- failure/absence is represented by the expected value, not by an exception

---

## 4. Edge-Case Priority Rule

Always generate tests for rows that represent materially distinct edge cases, including:
- `nullable.empty`
- `nullable.present.*`
- `mixed_t` kind splits
- zero vs non-zero numeric profiles
- `string.empty` vs `string.zero_string` vs `string.nonempty_nonzero`
- hash empty vs non-empty
- present-key vs missing-key probe/reset cases
- valid vs invalid operand target kind for mutating/reset items

---

## 5. Target-Kind-Aware Tests

For items that use operand target kinds, test generation must preserve that dimension.

Examples:
- `pre_increment × int_t × assignable_variable` → runtime success or compile-fail depending on support
- `pre_increment × int_t × temporary_result` → compile-fail
- `unset_keyed × keyed_element.present` → runtime mutation test
- `unset_keyed × keyed_element.missing` → runtime noop test

A profile-only test is insufficient when target-kind semantics differ.

---

## 6. Naming Rule

Suggested normalized pattern:

`test_<item_id>_<lhs_type>_<rhs_type>_<lhs_profile>_<rhs_profile>_<lhs_target_kind>_<status_or_behavior>`

Only include the fields that apply.
Names must remain deterministic.

---

## 7. De-duplication Rule

Do not emit duplicate tests for rows that are semantically identical after all applicable dimensions are considered.

De-duplication must never collapse:
- distinct runtime profiles
- distinct target kinds
- distinct behavior classes
- distinct diagnostic classes

---

## 8. Minimum Coverage Rule

At minimum, each family/item must receive tests for:
- one representative deterministic success path where supported
- every distinct throw/noop/failure-value behavior class that exists
- every compile-time rejection class that is intentionally part of the surface boundary
- helper-routed paths where semantic normalization is important

---

## 9. Notes

The matrix is the source of truth for edge-case test synthesis.
If the generated test plan does not preserve profile partitions and target-kind distinctions, the test plan is incomplete.
