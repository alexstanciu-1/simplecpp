# Operator / Cast / Language Semantics Matrix
## v1 Profile Semantics

---

## 1. Purpose

Derived coordination spec.

This document defines how canonical runtime profiles behave across the v1 matrix families.

It does **not** create new language semantics.
It projects existing semantics from:
- normative specs under `specs/`
- subsystem rules under `runtime/specs/`
- `runtime/specs/config.json`

This document is required for:
- profile-aware matrix generation
- edge-case enumeration
- status classification
- behavior classification
- automated test synthesis

---

## 2. Authority Relationship

This document is subordinate to:
- `specs/spec_map.md`
- `specs/dynamic_types.md`
- `specs/conditional_expression_matrix.md`
- `specs/count_empty_isset_contract.md`
- `specs/array_semantics.md`
- `runtime/specs/spec.md`
- `runtime/specs/config.json`

If this document conflicts with any source above, this document must be updated.

---

## 3. Semantic Layers

Each matrix row must distinguish these layers:

1. **type**
2. **profile**
3. **family-specific behavior**
4. **operand target kind** when the family mutates or targets storage

Example:
- `int_t`
- `int.zero`
- behavior under `condition_truthiness`
- target kind `assignable_variable` for `pre_increment`

The matrix must never collapse all profiles of the same type into one semantic result when runtime behavior differs.

---

## 4. Status and Behavior Vocabulary

Matrix evaluation must split **status** from **behavior_class**.

### 4.1 `status`

Allowed statuses:
- `supported`
- `compile_time_rejected`
- `unsupported_by_runtime_surface`

#### Meanings

##### `supported`
The family/item accepts the type/profile combination as part of the current semantic surface.
A supported row still requires a behavior class.

##### `compile_time_rejected`
The combination is outside the approved semantic surface and must not compile as a supported form.
Examples:
- invalid direct condition type
- mutation against a non-assignable temporary
- disallowed operator/type combination

##### `unsupported_by_runtime_surface`
The normative family concept may exist, but the current runtime/config surface does not define the combination as implemented.
This is distinct from compile-time rejection of the semantic form itself.

### 4.2 `behavior_class`

Allowed behavior classes:
- `deterministic_value`
- `runtime_partitioned`
- `throws`
- `noop`
- `failure_value`
- `helper_routed`

#### Meanings

##### `deterministic_value`
A supported row yields a normal value/result for the exact profile.

##### `runtime_partitioned`
The outer type is accepted, but runtime kind/profile must be expanded into separate rows.
This is most important for:
- `mixed_t`
- wrapper carriers that delegate to contained payload profiles

##### `throws`
The row compiles and reaches runtime, but evaluation must fail by throwing/raising for that profile.

##### `noop`
The row is valid, but missing/empty target state produces no mutation effect.
Relevant for reset-style operations such as missing-key `unset`.

##### `failure_value`
The row is valid, but reports failure/absence via a normal value rather than an exception.
Use only where the normative family explicitly does so.

##### `helper_routed`
The row is supported, but semantics are mediated through a dedicated helper/runtime path instead of ordinary primitive operator dispatch.

---

## 5. Core Discipline: Profile-Partitioned Support

Support must remain explicit at the **profile** level.

The matrix must not collapse support to a coarse row like:
- `cast_bool × mixed_t`

without also emitting the concrete rows such as:
- `cast_bool × mixed.bool.false`
- `cast_bool × mixed.int.zero`
- `cast_bool × mixed.string.zero_string`

UI summaries may aggregate.
Source matrix data must remain profile-exact.

---

## 6. Family Semantics

### 6.1 `condition_truthiness`

This family covers:
- `if_condition`
- `while_condition`
- `do_while_condition`
- `ternary_condition`
- `elvis_condition`

Normative source:
- `specs/conditional_expression_matrix.md`
- `runtime/specs/config.json` → `coercions.condition`

#### v1 rule
Condition context is **not** equivalent to generic PHP falsiness and is **not** equivalent to unconstrained `(bool)` cast.

Approved direct condition inputs:
- `bool_t`
- `int_t`
- `float_t`
- `mixed_t`
- `nullable<T>` only by wrapper delegation when the contained `T` itself is valid in condition context

Direct string truthiness is not approved in the current subset.

#### Canonical profile behavior

##### `bool_t`
- `bool.false` → `status=supported`, `behavior_class=deterministic_value`, result false
- `bool.true` → `status=supported`, `behavior_class=deterministic_value`, result true

##### `int_t`
- `int.zero` → `status=supported`, `behavior_class=deterministic_value`, result false
- `int.nonzero` → `status=supported`, `behavior_class=deterministic_value`, result true

##### `float_t`
- `float.zero` → `status=supported`, `behavior_class=deterministic_value`, result false
- `float.nonzero` → `status=supported`, `behavior_class=deterministic_value`, result true

##### `string_t`
- all current profiles → `status=compile_time_rejected`

Notes:
- PHP-style non-empty-string truthiness is not part of this family.
- If boolean intent is needed for string input, explicit normalization must happen before the condition site.

##### `hash_t`
- all current profiles → `status=compile_time_rejected`

##### `nullable<T>`
- `nullable.empty` → `status=supported`, `behavior_class=deterministic_value`, result false
- `nullable.present.<T_profile>` → delegate to contained `T_profile`

Examples:
- `nullable<int_t>.present.int.zero` → false
- `nullable<int_t>.present.int.nonzero` → true
- `nullable<string_t>.present.string.nonempty_nonzero` → `status=compile_time_rejected`

##### `mixed_t`
Outer type summary:
- `status=supported`
- `behavior_class=runtime_partitioned`

Accepted runtime kinds:
- `mixed.bool.false` → false
- `mixed.bool.true` → true
- `mixed.int.zero` → false
- `mixed.int.nonzero` → true
- `mixed.float.zero` → false
- `mixed.float.nonzero` → true

Rejected runtime kinds:
- `mixed.null` → `status=supported`, `behavior_class=throws`
- `mixed.string.*` → `status=supported`, `behavior_class=throws`
- `mixed.hash.*` → `status=supported`, `behavior_class=throws`

#### Matrix generation rule
The family must emit one row per relevant runtime profile, not a single merged `mixed_t` row.

---

### 6.2 `casts_explicit`

This family covers:
- `cast_bool`
- `cast_int`
- `cast_float`
- `cast_string`

Normative source:
- `runtime/specs/config.json` → `casts`
- `specs/dynamic_types.md`
- `specs/mixed_boundary_transitional.md`

#### Core rule
The matrix should model the **centralized explicit cast surface**.
It must not silently invent PHP-wide coercion beyond configured cast rules.

##### `cast_bool`
Supported native profiles:
- `bool.false` → `status=supported`, `behavior_class=deterministic_value`, result false
- `bool.true` → `status=supported`, `behavior_class=deterministic_value`, result true
- `int.zero` → `status=supported`, `behavior_class=deterministic_value`, result false
- `int.nonzero` → `status=supported`, `behavior_class=deterministic_value`, result true
- `float.zero` → `status=supported`, `behavior_class=deterministic_value`, result false
- `float.nonzero` → `status=supported`, `behavior_class=deterministic_value`, result true
- `string.*` → governed by configured strict string-bool rules; unsupported payloads use `behavior_class=throws`

##### `nullable<T>`
- `nullable.empty` → supported only if a normative unwrap-null rule exists; otherwise `status=supported`, `behavior_class=throws`
- `nullable.present.<T_profile>` → unwrap first, then apply contained rule

##### `mixed_t`
- outer summary row uses `behavior_class=runtime_partitioned`
- concrete rows dispatch by active kind and then apply cast behavior

##### `cast_int`, `cast_float`, `cast_string`
- row generation must remain config-driven
- supported rows use `behavior_class=deterministic_value`
- runtime payload failures use `behavior_class=throws`
- wrapper carriers delegate only where a higher-level rule exists

---

### 6.3 `operators_unary`

This family covers:
- `logical_not`
- `unary_plus`
- `unary_minus`
- `bitwise_not`
- `pre_increment`
- `post_increment`
- `pre_decrement`
- `post_decrement`

Normative source:
- runtime operator configuration
- relevant language/runtime numeric and mixed-value specs

#### Core rule
Unary operator support is determined by the combination of:
- item
- operand type/profile
- operand target kind when mutation is involved

##### Non-mutating unary items
For `logical_not`, `unary_plus`, `unary_minus`, and `bitwise_not`:
- target kind is normally not applicable
- support is profile-driven and config-driven
- unsupported combinations use `status=compile_time_rejected` or `status=unsupported_by_runtime_surface` depending on whether the semantic form exists but is not currently implemented

##### Mutating unary items (`++` / `--`)
For `pre_increment`, `post_increment`, `pre_decrement`, `post_decrement`:
- target kind is required
- `assignable_variable` and, where approved, `keyed_element` are eligible targets
- `plain_value` and `temporary_result` must not be treated as valid mutation targets

Examples:
- `pre_increment × int_t × assignable_variable` → supported only if config allows the operator/type combination
- `pre_increment × int_t × temporary_result` → `status=compile_time_rejected`

---

### 6.4 `operators_binary_arithmetic_bitwise`

This family covers arithmetic, bitwise, and shift operators.

Normative source:
- runtime operator configuration
- relevant numeric and dynamic-type specs

#### Rule
These rows are primarily config-driven.
The matrix must:
- expand wrapper participation explicitly
- keep profile partitions visible where runtime values differ
- distinguish unsupported surface from semantic rejection

For wrapper carriers:
- `nullable<T>` must not be silently treated as native `T` unless a wrapper rule explicitly delegates
- `mixed_t` must be expanded by runtime kind when config allows the family path

---

### 6.5 `operators_binary_logical_relational`

This family covers:
- `logical_and`
- `logical_or`
- `equal`
- `not_equal`
- `less`
- `less_equal`
- `greater`
- `greater_equal`

Normative source:
- runtime operator configuration
- dynamic/wrapper specs

#### Rule
This family excludes strict identity.
If a row uses helper-mediated comparison semantics rather than ordinary dispatch, it does not belong here.

Rows are evaluated as:
- config-driven for current support surface
- profile-aware for wrapper and mixed participation
- explicit about result determinism vs runtime throw behavior

---

### 6.6 `operators_identity_strict`

This family covers:
- `identical`
- `not_identical`

Normative source:
- helper-owned PHP identity semantics
- relevant wrapper normalization specs

#### Rule
This family is intentionally separate from ordinary comparison.
Rows in this family should normally use:
- `status=supported`
- `behavior_class=helper_routed`

The matrix must keep wrapper and mixed normalization explicit.
A summary row may say the family is helper-routed, but concrete profile rows are still required.

---

### 6.7 `operators_conditional_selection`

This family covers:
- `coalesce`
- `ternary`
- `elvis`

Normative source:
- `specs/conditional_expression_matrix.md`
- wrapper specs
- runtime support rules

#### Rule
Rows must distinguish:
- condition profile behavior
- branch/result shape behavior
- wrapper-preserving behavior where specified

The family must not be flattened into a simple binary-operator table.

---

### 6.8 `language_probes_and_reset`

This family covers:
- `isset_value`
- `isset_keyed`
- `empty_value`
- `empty_keyed`
- `count_value`
- `unset_value`
- `unset_keyed`

Normative source:
- `specs/count_empty_isset_contract.md`
- `specs/array_semantics.md`
- runtime support rules

#### Probe rule
Probe operations should generally use:
- `status=supported`
- `behavior_class=deterministic_value`

unless the normative source defines a different failure channel.

#### Reset rule
`unset` rows must include operand target kind.
Examples:
- `unset_value × assignable_variable` → supported when the target form is valid
- `unset_keyed × keyed_element.present` → supported; mutation effect applied
- `unset_keyed × keyed_element.missing` → `status=supported`, `behavior_class=noop`
- `unset_value × temporary_result` → `status=compile_time_rejected`

---

## 7. Wrapper Delegation Rule

Wrapper types must never be silently erased.

When a family supports a wrapper by delegation, the matrix row must still record:
- wrapper type
- wrapper profile
- delegated contained profile

This applies especially to:
- `nullable<T>`
- `mixed_t`
- `result<T>`
- `result_or_false<T>`
- `result_or_bool<T>`

---

## 8. Summary

This document defines the canonical profile-behavior discipline for v1.

The critical rules are:
- split `status` from `behavior_class`
- keep profile-partitioned support explicit
- include operand target kind where mutation/reset semantics require it
- keep strict identity in its own dedicated family
