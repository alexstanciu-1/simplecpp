# Operator / Cast / Language Semantics Matrix
## v1 Matrix Validation Rules

---

## 1. Purpose

This document defines the validation rules for the generated operator matrix.

It exists to determine whether a generated matrix is:
- structurally valid
- authority-complete
- semantically coherent
- consistent with profile-explicit generation
- suitable for test generation

This document is a derived coordination specification.
It does not define language semantics.
It defines how a generated matrix is checked against the
operator-matrix coordination layer and the upstream authority chain.

---

## 2. Authority Relationship

This document is subordinate to:
- `specs/*`
- `runtime/specs/*`
- `runtime/specs/config.json`

It must remain consistent with:
- `specs/operator_matrix/catalog_v1.md`
- `specs/operator_matrix/type_universe_v1.md`
- `specs/operator_matrix/profile_semantics_v1.md`
- `specs/operator_matrix/source_mapping_v1.md`
- `specs/operator_matrix/generation_rules_v1.md`
- `specs/operator_matrix/output_schema_v1.md`
- `specs/operator_matrix/test_generation_rules_v1.md`
- `specs/operator_matrix/regeneration_policy_v1.md`

If this document conflicts with upstream normative or runtime authority,
the upstream authority wins and the matrix must be corrected.

---

## 3. Validation Goals

Validation must answer all of the following:

- Is every generated row structurally well-formed?
- Is every generated row attributable to a known family and item?
- Is every generated row profile-explicit?
- Is every generated row consistent with family source mapping?
- Is every generated row consistent with wrapper and mixed-kind expansion rules?
- Is every edge-case row test-generatable?
- Is there any contradiction between specs, runtime specs, and runtime config?

---

## 4. Validation Severity Classes

Validation outcomes are classified as:

### project_error
Used when the project authority graph is inconsistent and must be fixed.

Examples:
- spec says supported, runtime config does not define it
- runtime config defines support that no normative authority explains
- source mapping is missing for a family used by the matrix
- family/item names disagree across operator-matrix docs

### validation_error
Used when the generated matrix is malformed or incomplete.

Examples:
- unknown profile id
- missing result field where required
- invalid target-kind usage
- duplicate contradictory rows
- unsupported source-row aggregation

### warning
Used only for non-authoritative quality issues that do not change semantics.

Examples:
- optional explanatory note missing
- ordering inconsistency in human-readable output
- non-blocking traceability metadata omission

Warnings should remain rare. When in doubt, prefer `validation_error`
or `project_error`.

---

## 5. Structural Validation Rules

The generated matrix MUST satisfy all of the following:

### 5.1 Required identity fields
Every row must include:
- `family_id`
- `item_id`

`item_id` must belong to the declared `family_id`.

### 5.2 Required operand fields
Every row must include the operand fields required by its item arity.

Examples:
- unary item → `lhs_type`, `lhs_profile`
- binary item → `lhs_type`, `rhs_type`, `lhs_profile`, `rhs_profile`
- ternary item → all required condition/then/else fields

No row may include extraneous operand fields that are invalid for its item kind.

### 5.3 Required result/status fields
Every row must include:
- `status`
- `behavior_class`

Rows with defined evaluation results must also include:
- `result_type` when applicable
- `result_profile` when applicable

Rows that are rejected at compile time may omit result fields only if the
schema explicitly allows this omission.

### 5.4 Field enum validity
All enum-like fields must use recognized values only.

This applies to:
- family ids
- item ids
- status values
- behavior_class values
- target-kind ids
- profile ids
- test-seed classes

### 5.5 Target-kind applicability
Operand target-kind fields may appear only for items/families that support them.

They are required where mutation or reset semantics depend on target form.

Target kinds must not appear on rows where they are semantically irrelevant.

---

## 6. Family and Source-Mapping Validation

### 6.1 Family registration
Every generated family must exist in:
- `catalog_v1.md`
- `source_mapping_v1.md`

### 6.2 Item registration
Every generated item must exist in:
- `catalog_v1.md`

If an item has family-specific source notes or overrides,
those must be representable under the family mapping policy.

### 6.3 Source attribution completeness
Every family used by the generated matrix must have:
- grouped primary authority references
- grouped secondary authority references where needed
- implementation touchpoints where applicable

Missing source attribution is a `project_error`.

### 6.4 Family-first rule
Validation must enforce the family-first mapping model.

Item-level overrides are allowed only for documented exceptions.
A generated row must not rely on an implicit undocumented per-item source split.

---

## 7. Profile-Explicit Validation

The matrix source dataset MUST remain fully profile-explicit.

### 7.1 No aggregated source rows
Source rows must not use aggregated type-only support summaries in place of
concrete profiles.

Disallowed example:
- one source row for `cast_bool × mixed_t`

Required form:
- `cast_bool × mixed.null`
- `cast_bool × mixed.bool.false`
- `cast_bool × mixed.bool.true`
- etc.

### 7.2 Known profile ids only
Every referenced profile must be defined by:
- `type_universe_v1.md`
- any later approved extension to the type/profile catalog

### 7.3 Wrapper expansion completeness
Where wrappers participate, validation must confirm that required expansions
were not skipped.

This includes:
- `nullable<T>`
- `mixed_t`
- `result<T>`
- `result_or_false<T>`
- `result_or_bool<T>`

### 7.4 Mixed-kind explicitness
Where `mixed_t` participates in a family that depends on runtime kind,
the row set must explicitly represent the relevant runtime kinds.

### 7.5 Condition-profile explicitness
Condition-sensitive rows must keep value-partitioned profiles explicit.

Examples:
- `int.zero`
- `int.nonzero`
- `float.zero`
- `float.nonzero`
- `string.empty`
- `string.zero_string`
- `nullable.empty`
- `nullable.present.<profile>`

---

## 8. Semantic Validation Rules

### 8.1 Status/behavior coherence
`status` and `behavior_class` must form a valid pair.

Examples:
- `compile_time_rejected` must not claim deterministic result metadata
- a supported row that throws at runtime must use the supported status with
  a throwing behavior class, not compile-time rejection
- supported `throws` rows may omit `result_type` / `result_profile` when no value is produced
- helper-routed semantics must still obey family rules

### 8.2 Result coherence
For identical source conditions, rows must not disagree on:
- result type
- result profile
- status
- behavior class

unless the distinction is explained by:
- a different runtime profile
- a different target kind
- a documented family override

### 8.3 Wrapper delegation coherence
Wrapper-driven families must respect the documented delegation model.

Examples:
- `nullable<T>` present-state behavior delegates to contained profile rules
- `mixed_t` uses kind-specific behavior where required
- `result*` wrappers follow family-specific success/failure/false/bool behavior

### 8.4 Strict identity family separation
`===` and `!==` rows must belong only to the strict-identity family and
must not appear under ordinary relational/value-comparison families.

### 8.5 Probe/reset separation
Validation must respect the documented split:
- probe semantics governed by probe contracts
- reset/removal semantics governed by reset/container semantics

---

## 9. Consistency Validation Across Project Inputs

### 9.1 Spec/config contradiction
If a normative spec defines support but `runtime/specs/config.json`
does not define the corresponding runtime-supported combination,
this is a `project_error`.

### 9.2 Config/spec contradiction
If `runtime/specs/config.json` defines support that has no upstream
normative basis, this is a `project_error`.

### 9.3 Stale naming
If family or item names differ across operator-matrix documents,
source mapping, regeneration policy, or generated rows,
this is a `project_error`.

### 9.4 Missing family coverage
If a documented family exists but cannot be generated or validated due to
missing mapping or missing schema support, this is a `project_error`
unless the family is explicitly marked as deferred.

---

## 10. Test-Generation Validation

### 10.1 Edge-case rows must be test-addressable
Every row marked as an edge case must map to a valid test-seed class.

### 10.2 Compile-time rejection rows
Rows with compile-time rejection status must map to compile-fail tests
when the project test infrastructure supports such tests.

### 10.3 Runtime-behavior rows
Runtime rows must map to test categories consistent with their behavior class.

Examples:
- deterministic value → runtime pass/assert test
- throws → runtime throw/failure test
- noop → runtime no-op verification
- failure-value → runtime value assertion

### 10.4 Target-kind-sensitive rows
If behavior depends on target kind, the test seed must preserve that dimension.

### 10.5 No silent edge-case loss
Validation must fail if an edge-case source row is not representable in
the test-generation layer.

---

## 11. Determinism and Reproducibility

Generated validation results must be deterministic.

At minimum:
- identical inputs must generate identical row keys
- row ordering must be stable
- duplicate generation paths must collapse into one canonical row key
  or fail explicitly when contradictory

---

## 12. Minimum Validation Checklist

A generated matrix is not acceptable unless all of the following are true:

- every row matches schema
- every family and item is known
- every profile id is known
- source rows are fully profile-explicit
- target-kind usage is valid
- family source mapping is complete
- no spec/config contradiction exists
- no contradictory duplicate rows exist
- edge-case rows remain test-addressable
- strict-identity rows remain in the strict-identity family only

---

## 13. Summary

`matrix_validation_rules_v1.md` defines the acceptance contract for a generated matrix.

It enforces:
- structural correctness
- family/source completeness
- profile-explicit generation
- wrapper-expansion completeness
- strict consistency across specs, runtime specs, runtime config,
  operator-matrix docs, and test-generation expectations

A matrix that fails these checks is not merely incomplete.
Depending on failure type, it is either:
- invalid generated data
- or evidence of a project-level authority mismatch that must be fixed
before further generation or implementation work continues.
