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
- `generation_rules_v1.md` — deterministic matrix row generation rules
- `output_schema_v1.md` — canonical output row schema and enums
- `test_generation_rules_v1.md` — matrix-to-test synthesis rules
- `source_mapping_v1.md` — concrete family-first mapping to authority files, runtime config, and implementation touchpoints
