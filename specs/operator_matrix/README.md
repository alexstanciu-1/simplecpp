# Operator Matrix Docs
Doc Status: normative
Status: Derived coordination specs

---

## 1. Purpose

The `specs/operator_matrix/` folder defines the canonical projection used for:
- operator / cast / helper matrix generation
- semantic visualization
- edge-case coverage tracking
- automated test synthesis

These documents are **derived coordination specs**.

They do **not** override normative language or runtime specs.

---

## 2. Authority Relationship

The operator-matrix documents sit below normative specs and below subsystem rules.

Recommended precedence:

1. `specs/spec_map.md`
2. normative language specs under `specs/`
3. subsystem normative specs under `runtime/specs/` and `generators/php/specs/`
4. machine-readable implementation config such as `runtime/specs/config.json`
5. derived operator-matrix docs under `specs/operator_matrix/`
6. generated artifacts and source code
7. tests

---

## 3. Interpretation Rule

If a matrix document conflicts with:
- a normative language spec,
- a subsystem runtime spec, or
- `runtime/specs/config.json` for current runtime-supported combinations,

then the matrix document must be updated.

The matrix documents are responsible for normalization and projection, not semantic override.

---

## 4. Scope of Authority Inside This Folder

This folder may act as the canonical authority for:
- family naming
- item identifiers
- item arity and operand-shape naming
- profile naming
- edge-case identifiers
- operand target kind naming
- matrix row schema
- test-seed grouping

This is organizational authority only.

Semantic behavior remains owned by the higher-level specs and runtime contracts.

---

## 5. Current Documents

- `catalog_v1.md` â€” canonical family taxonomy and v1 item inventory
- `type_universe_v1.md` â€” canonical type set and profile expansion basis
- `profile_semantics_v1.md` â€” family-by-family profile behavior and status/behavior rules
- `operand_target_kinds_v1.md` â€” canonical operand target kinds for mutation/reset-sensitive families
- `source_mapping_policy_v1.md` â€” family-first source-of-truth mapping policy
- `generation_rules_v1.md`
- `generation_rules_v1.md` â€” deterministic matrix row generation rules
- `output_schema_v1.md` â€” canonical output row schema and enums
- `test_seed_schema_v1.md` â€” canonical matrix-to-seed intermediate artifact and emitted-test handoff
- `test_generation_rules_v1.md` â€” matrix-to-test synthesis rules
- `source_mapping_v1.md` â€” concrete family-first mapping to authority files, runtime config, and implementation touchpoints
- `regeneration_policy_v1.md` â€” trigger, scope, validation, and project-error rules for matrix regeneration
- `matrix_validation_rules_v1.md` â€” acceptance and validation contract for generated matrix data
- `data/` â€” structured JSON input consumed by `tools/operator_matrix/`


## Wrapper-aware coalesce policy

`??` is wrapper-state driven for approved wrapper families and does not preserve wrapper carriers in the result. Approved wrapper families auto-unpack to their usable value domain for `coalesce`: `nullable<T>`, `result<T>`, and `result_or_false<T>`. In the current version, `result_or_bool<T>` is rejected in the runtime helper path on either side of `??`; a later typed semantic layer may move that rejection earlier.

Current coalesce interpretation rules:
- the generator remains intentionally type-blind, so some profile-specific invalid rows are runtime-rejected in v1 rather than rejected earlier
- runtime rejection should be described in terms of the selected branch having no usable value domain, not only in terms of the syntactic RHS
- `mixed_t(null)` is a valid selected mixed result domain when it is the selected fallback branch; it must not be conflated with wrapper states that have no usable selected value
- `mixed_t` uses selected value-domain semantics only for explicitly defined runtime kinds


## Generated matrix test output

The generator now emits concrete PHP matrix tests under `tests/php-matrix/` and a summary report at `build/operator_matrix/test_emission_report.json`.
The current runtime-matrix root is created but intentionally left empty until a later emitter phase.

Negative-generate emission and enablement are configurable by CLI flag. The current priority order is deterministic:
1. explicit diagnostic denylist
2. explicit diagnostic allowlist
3. global negative-generate enable mode
4. default disabled state


## Current ternary / elvis placement note

For `expr_1 ? expr_2 : expr_3` and `expr_1 ?: expr_3`, the current project uses one runtime helper path: `php::ternary_eval(...)`.

Important current layering rule:
- the runtime helper already owns wrapper-aware condition delegation and branch normalization for supported branch/result pairs
- the current operator-matrix structured data remains narrower than that helper in the emitted ternary / elvis slice
- current compile-time rejected elvis wrapper rows in the matrix data therefore represent a matrix-slice boundary, not proof that the runtime helper itself has no wrapper-aware condition behavior

Until the matrix expansion is completed, ternary / elvis discussions must state clearly whether they refer to:
- runtime-helper semantics, or
- the currently emitted operator-matrix slice

## Runtime Errors (JSON Mode)

When SCPP_ERROR_FORMAT=json is enabled:

- runtime errors must include a stable `code`
- tests should assert on `code`, not message text

For `??`:

- coalesce_selected_branch_has_no_usable_value_domain
- coalesce_reject_result_or_bool

For ternary / elvis condition runtime rejects:

- ternary_condition_reject_mixed_kind

Condition-truthiness note:
- ternary / elvis condition semantics follow the shared `condition_truthiness` family, not explicit `(bool)` cast rules
- `string_t` is truthy unless it is `""` or `"0"`
- typed/explicit `string_t -> bool_t` normalization remains narrower and may runtime-error on other literals

