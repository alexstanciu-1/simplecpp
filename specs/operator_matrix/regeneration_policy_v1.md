# Operator / Cast / Language Semantics Matrix
Doc Status: normative

---

## 1. Purpose

Derived coordination spec.

This document defines when the operator-matrix artifacts must be regenerated,
what scope must be regenerated, what validations are mandatory after
regeneration, and which mismatch states are project errors.

This document is operational.
It is used for:
- change triage
- regeneration scope selection
- validation routing
- review discipline
- stale-matrix prevention

---

## 2. Authority Relationship

This document is subordinate to:
- `specs/spec_map.md`
- normative language specs under `specs/`
- subsystem normative specs under `runtime/specs/` and `generators/php/specs/`
- `runtime/specs/config.json` for the current runtime-supported surface

It does not define new language semantics.
It defines the operational policy for keeping the matrix synchronized with the
current project truth.

---

## 3. Regeneration Principle

The operator matrix is not a static document set.

It is a derived coordination layer that MUST be reviewed and regenerated when a
change affects:
- semantic authority
- supported implementation surface
- profile expansion
- matrix schema
- test-seed derivation
- source mapping

Interpretation rule:
- if a relevant upstream source changes, the matrix must be treated as stale
  until regeneration/review is completed
- if the impact is uncertain, regeneration scope must be rounded upward, not
  downward

---

## 4. Trigger Sources

Regeneration review is mandatory after changes to any of the following groups.

### 4.1 Normative language specs

Examples:
- `specs/dynamic_types.md`
- `specs/array_semantics.md`
- `specs/conditional_expression_matrix.md`
- `specs/count_empty_isset_contract.md`

Trigger effect:
- regenerate affected families
- rerun affected validations
- review impacted test seeds

### 4.2 Subsystem runtime/generator specs

Examples:
- `runtime/specs/spec.md`
- `runtime/specs/catalog.md`
- `runtime/specs/operator_generation_flow.md`
- `generators/php/specs/*.md`

Trigger effect:
- regenerate affected families
- re-check source mappings and family notes
- review implementation touchpoints if behavior changed

### 4.3 Machine-readable runtime surface

Examples:
- `runtime/specs/config.json`

Trigger effect:
- regenerate all affected operator/cast families
- revalidate support rows against config
- treat spec/config mismatch as a project error

### 4.4 Runtime or generator implementation affecting semantics/support

Examples:
- generated operator surface
- runtime helper logic
- condition/cast helper implementation
- keyed probe/reset implementation
- generator lowering for operators, casts, truthiness, probes, wrappers

Trigger effect:
- perform regeneration review
- if implementation change is purely internal and provably does not affect
  semantics, note that conclusion explicitly during review
- otherwise regenerate affected families and tests

### 4.5 Operator-matrix coordination docs

Examples:
- `specs/operator_matrix/catalog_v1.md`
- `specs/operator_matrix/type_universe_v1.md`
- `specs/operator_matrix/profile_semantics_v1.md`
- `specs/operator_matrix/operand_target_kinds_v1.md`
- `specs/operator_matrix/source_mapping_v1.md`
- `specs/operator_matrix/output_schema_v1.md`
- `specs/operator_matrix/test_generation_rules_v1.md`

Trigger effect:
- regenerate any derived artifacts that depend on the changed document
- rerun schema/consistency checks
- review whether earlier matrix rows or test seeds became stale

---

## 5. Regeneration Scope

Regeneration scope must be selected conservatively.

### 5.1 Family-only regeneration

Allowed when all of the following are true:
- the change maps cleanly to one matrix family or a tightly coupled family pair
- profile universe/schema is unchanged
- output row format is unchanged
- test generation rules outside the affected family remain unchanged

Typical cases:
- `language_probes_and_reset` contract adjustment
- `operators_identity_strict` helper alignment
- `condition_truthiness` rule refinement

### 5.2 Family set regeneration

Required when:
- multiple related families are affected
- wrapper behavior used by several families changes
- source mapping changes for shared authorities
- operand target kind policy changes

Typical cases:
- `mixed_t` participation changes
- wrapper delegation changes shared by casts and conditional selection
- keyed behavior changes affecting probes and reset

### 5.3 Full matrix regeneration

Required when any of the following change:
- `type_universe_v1.md`
- profile expansion rules
- `output_schema_v1.md`
- `generation_rules_v1.md`
- `test_generation_rules_v1.md`
- `runtime/specs/config.json` format or broad operator coverage
- project-wide authority order or interpretation rules

### 5.4 Full matrix + test-seed regeneration

Required when:
- row IDs or profile IDs change
- behavior/status classification rules change
- test naming or test-seed policy changes
- source mapping changes invalidate existing traceability

---

## 6. Mandatory Post-Regeneration Checks

After regeneration, all of the following checks must pass.

### 6.1 Authority checks
- every family has a valid source mapping
- no family references a missing authority file
- no derived document claims higher authority than normative specs or runtime config

### 6.2 Profile checks
- all referenced profile IDs exist in the current type universe
- source rows remain fully profile-expanded
- no aggregated source rows exist
- wrapper expansions remain recursive and valid

### 6.3 Schema checks
- all rows conform to `output_schema_v1.md`
- enums use only allowed values
- target-kind fields appear only where applicable
- source/provenance fields remain well-formed

### 6.4 Support-surface checks
- rows classified as supported are backed by current authority and config
- config-covered combinations do not contradict higher-authority specs
- unsupported combinations are not emitted as supported through stale rules

### 6.5 Test-generation checks
- every edge-case class required by policy can still map to a test-seed class
- compile-time rejection rows still map to compile-fail tests
- runtime behavior classes still map to the correct runtime test pattern
- no stale test IDs or stale family/profile references remain

---

## 7. Error Policy

The following states are project errors and must be fixed.

### 7.1 Spec/config mismatch
- higher-authority spec requires a supported surface
- but `runtime/specs/config.json` does not encode it

### 7.2 Config/spec mismatch
- config encodes support
- but higher-authority specs do not define or allow it

### 7.3 Missing authority anchor
- a family or item requires mapping
- but `source_mapping_v1.md` has no adequate grouped authority entry

### 7.4 Stale matrix contradiction
- matrix rows or notes still reflect superseded semantics
- after upstream sources changed

### 7.5 Broken profile discipline
- source rows collapse profile-explicit behavior into coarse rows
- unknown profile IDs appear
- operand target kinds are omitted where required for mutating/reset-sensitive items

Project-error rule:
- these states must not be normalized as â€œpartial supportâ€ or â€œacceptable driftâ€
- the project sources must be fixed, then matrix regeneration/review must be rerun

---

## 8. Review Workflow

When a triggering change occurs:

1. identify changed files
2. map them to matrix families via `source_mapping_v1.md`
3. choose regeneration scope using this policy
4. regenerate/review affected rows and test seeds
5. run mandatory post-regeneration checks
6. classify any mismatch as either:
   - expected synchronized change, or
   - project error requiring source fixes
7. update operator-matrix docs if the derived coordination layer changed
8. update affected tests when behavior, support, or IDs changed

---

## 9. Human Review Checklist

Before accepting a change that touches matrix-relevant sources, confirm:

- Which family or families are affected?
- Was regeneration scope chosen conservatively?
- Do source mappings still point to the correct grouped authorities?
- Are all source rows still fully profile-explicit?
- Did any status or behavior classification change?
- Do operand target kinds still apply correctly?
- Do required edge-case test seeds still exist?
- Is there any spec/config mismatch that must be fixed before merge?

---

## 10. Summary

The operator matrix must be regenerated or explicitly re-reviewed whenever the
project changes a matrix-relevant authority, support surface, profile universe,
schema rule, or test-generation rule.

The default bias is conservative:
- regenerate more rather than less
- treat mismatches as project defects, not acceptable drift
- keep source rows profile-explicit and authority-traceable
