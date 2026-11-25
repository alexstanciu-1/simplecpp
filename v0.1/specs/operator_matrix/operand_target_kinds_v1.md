# Operator / Cast / Language Semantics Matrix
Doc Status: normative

---

## 1. Purpose

Derived coordination spec.

This document defines the canonical **operand target kind** dimension used by matrix families that:
- mutate storage
- require assignable targets
- distinguish between values and targetable locations

This document does not redefine language semantics.
It provides the normalized target-kind vocabulary used by:
- `catalog_v1.md`
- `profile_semantics_v1.md`
- `generation_rules_v1.md`
- `output_schema_v1.md`
- `test_generation_rules_v1.md`

---

## 2. When This Dimension Applies

Operand target kind is required only for families/items where semantic validity depends on the target form.

v1 includes:
- `pre_increment`
- `post_increment`
- `pre_decrement`
- `post_decrement`
- `unset_value`
- `unset_keyed`
- `operators_compound_assignment`

Other families may ignore this dimension in v1 unless later specs require it.

---

## 3. Canonical Target Kinds

### `plain_value`
A non-targeted value expression.
Examples:
- literal
- pure computed value
- rvalue-like non-storage result

### `assignable_variable`
A named writable variable/storage slot.
Examples:
- local variable
- assignable symbol-backed value slot

### `keyed_element`
A writable keyed/container element target.
Examples:
- hash element by key
- array-like keyed storage location

### `member_property`
A writable member/property target.
Examples:
- object property
- handle-backed member slot
- direct `->property` write surface

### `chained_writable_path`
A writable path reached through a composed lvalue chain.
Examples:
- `->property[0]`
- `["k"]->property`
- representative deeper member/keyed write path whose final sink is still writable

### `temporary_result`
A temporary expression result that may have a type/profile but is not a valid mutation/reset target.
Examples:
- function return temporary
- cast result temporary
- computed expression temporary

---

## 4. Family Guidance

### Mutating unary (`++` / `--`)
- valid target kinds are family- and config-dependent
- `plain_value` and `temporary_result` must not be treated as valid mutation targets
- `assignable_variable` is the default valid target kind
- `keyed_element` is valid only when higher-level specs/runtime support permit it

### `operators_compound_assignment`
- requires a writable lhs target form
- `assignable_variable` is the default canonical target kind
- `member_property` participates when the same compound-assignment semantics are written back through `->property`
- `keyed_element` remains the canonical keyed/container write form
- `chained_writable_path` represents a deeper composed write path that still resolves to a writable sink
- `plain_value` and `temporary_result` are not valid compound-assignment targets

### `unset_value`
- requires a targetable storage form
- `assignable_variable` is the default valid target kind
- `temporary_result` is rejected

### `unset_keyed`
- requires `keyed_element`
- missing-key rows are modeled through target/profile state and may use `behavior_class=noop`

---

## 5. Schema Discipline

Where applicable, matrix rows must record:
- `lhs_target_kind`
- `rhs_target_kind`
- `third_target_kind`

Unused target-kind fields must remain null/absent rather than invented.

---

## 6. Notes

This vocabulary is intentionally small.

v1 does not attempt to model the full C++ or compiler-theory lvalue/prvalue/xvalue space.
It only models the distinctions required to generate correct operator/reset rows and tests.
