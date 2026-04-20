# Operator Matrix Docs

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

- `catalog_v1.md` — canonical family taxonomy and v1 item inventory
- `type_universe_v1.md` — canonical type set and profile expansion basis
- `profile_semantics_v1.md` — family-by-family profile behavior and status/behavior rules
- `operand_target_kinds_v1.md` — canonical operand target kinds for mutation/reset-sensitive families
- `source_mapping_policy_v1.md` — family-first source-of-truth mapping policy
- `generation_rules_v1.md`
- `generation_rules_v1.md` — deterministic matrix row generation rules
- `output_schema_v1.md` — canonical output row schema and enums
- `test_seed_schema_v1.md` — canonical matrix-to-seed intermediate artifact and emitted-test handoff
- `test_generation_rules_v1.md` — matrix-to-test synthesis rules
- `source_mapping_v1.md` — concrete family-first mapping to authority files, runtime config, and implementation touchpoints
- `regeneration_policy_v1.md` — trigger, scope, validation, and project-error rules for matrix regeneration
- `matrix_validation_rules_v1.md` — acceptance and validation contract for generated matrix data
- `data/` — structured JSON input consumed by `tools/operator_matrix/`


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
