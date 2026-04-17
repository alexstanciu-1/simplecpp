# Operator / Cast / Language Semantics Matrix
## v1 Generation Rules

---

## 1. Purpose

Derived coordination spec.

This document defines how matrix rows are generated from:
- normative specs
- runtime subsystem specs
- `runtime/specs/config.json`
- canonical matrix docs in `specs/operator_matrix/`

It converts the catalog/type/profile model into deterministic, testable matrix rows.

---

## 2. Inputs

Required inputs:
- `specs/spec_map.md`
- family-level normative specs under `specs/`
- relevant subsystem specs under `runtime/specs/` and `generators/php/specs/`
- `runtime/specs/config.json`
- `catalog_v1.md`
- `type_universe_v1.md`
- `profile_semantics_v1.md`
- `operand_target_kinds_v1.md`
- `source_mapping_policy_v1.md`

---

## 3. Precedence

When inputs disagree, apply this order:

1. `specs/spec_map.md`
2. normative language specs under `specs/`
3. subsystem normative specs under `runtime/specs/` and `generators/php/specs/`
4. `runtime/specs/config.json` for current runtime-supported surface
5. operator-matrix derived docs under `specs/operator_matrix/`
6. implementation-derived observations

Implementation-derived observations may confirm gaps, but must not override higher-level sources.

---

## 4. Core Generation Algorithm

For each catalog item:

1. Resolve its family.
2. Resolve family-first source mapping.
3. Determine arity and required axes.
4. Expand the applicable type universe.
5. Expand runtime profiles for each participating type.
6. Expand operand target kinds when the item/family requires them.
7. Apply family-specific filters from `profile_semantics_v1.md`.
8. Resolve current runtime support from `runtime/specs/config.json`.
9. Classify `status`.
10. Classify `behavior_class` for supported rows.
11. Assign result type/profile where applicable.
12. Tag edge-case metadata.
13. Emit the canonical row.

The algorithm must be deterministic and reproducible.

---

## 5. Type and Profile Expansion

Rows must be generated against expanded profiles, not coarse types.

Examples:
- `int_t` expands into `int.zero`, `int.nonzero`
- `nullable<int_t>` expands into `nullable.empty`, `nullable.present.int.zero`, `nullable.present.int.nonzero`
- `mixed_t` expands into runtime-kind profiles

The generator must not emit a single coarse row when profile-specific behavior differs.

---

## 6. Operand Target Kind Expansion

Target-kind expansion is required only for items/families that depend on targetability or mutation validity.

v1 requires target-kind expansion for:
- `pre_increment`
- `post_increment`
- `pre_decrement`
- `post_decrement`
- `unset_value`
- `unset_keyed`

Canonical target kinds come from `operand_target_kinds_v1.md`.

For non-target-sensitive rows, target-kind fields remain null/absent.

---

## 7. Wrapper Expansion Rules

### `nullable<T>`
- always emit `nullable.empty`
- emit one row per `nullable.present.<T_profile>`
- preserve wrapper identity in the emitted row
- apply delegation only where the family allows it

### `mixed_t`
- emit one row per active runtime kind/profile
- concrete rows remain explicit for every active runtime kind/profile

### `result<T>` / `result_or_false<T>` / `result_or_bool<T>`
- preserve wrapper identity
- emit success/failure carrier rows explicitly
- do not erase wrapper semantics into plain `T`

---

## 8. Status Resolution Rules

### `compile_time_rejected`
Use when the semantic form is outside the approved surface.
Examples:
- direct condition on a disallowed type
- mutation against `temporary_result`
- disallowed operator/type form

### `unsupported_by_runtime_surface`
Use when the broader semantic category exists but current runtime/config does not define it as implemented.

### `supported`
Use only when the row is part of the current approved and implemented surface.
Supported rows must also include `behavior_class`.

---

## 9. Behavior Classification Rules

For `status=supported`, assign exactly one `behavior_class`:
- `deterministic_value`
- `throws`
- `noop`
- `failure_value`
- `helper_routed`

Examples:
- strict identity rows normally use `helper_routed`
- missing-key `unset_keyed` rows use `noop`
- invalid runtime payload rows that compile use `throws`

---

## 10. Edge-Case Enumeration Policy

The generator must emit rows for all materially distinct runtime profiles.

Minimum required partitions include:
- zero vs non-zero numeric profiles
- `bool.false` vs `bool.true`
- empty string vs `"0"` vs non-empty non-zero string
- empty vs non-empty hash
- nullable empty vs present
- mixed kind splits
- result success vs failure carriers
- keyed element present vs missing when probe/reset semantics differ
- valid target kind vs invalid target kind for mutating/reset operations

---

## 11. Aggregation Rule

UI/reporting may produce aggregated summaries.
The source matrix must keep the concrete rows.
No aggregate row may replace the profile- and target-kind-exact rows.

---

## 12. Test Seed Annotation Rule

Each emitted row must include enough metadata to decide whether it should generate:
- a runtime success test
- a runtime throw test
- a runtime noop test
- a compile-fail test
- a helper-routed semantic regression test

That mapping is defined by `test_generation_rules_v1.md`.

## 13. Profile Explicitness Rule

All source rows MUST be fully profile-expanded.
Aggregated rows are not allowed in the source dataset.
