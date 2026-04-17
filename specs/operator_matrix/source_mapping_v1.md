# Operator / Cast / Language Semantics Matrix
## v1 Source Mapping

---

## 1. Purpose

Derived coordination spec.

This document defines the concrete family-first source mapping used by the
operator-matrix subsystem.

It maps each matrix family to:
- primary authority files
- secondary/supporting authority files
- runtime-config dependencies
- implementation touchpoints
- family-level notes
- exceptional override guidance

This document is operational.
It is used for:
- drift review
- regeneration triggers
- test-review routing
- conflict triage

---

## 2. Authority Relationship

This document is subordinate to:
- `specs/spec_map.md`
- normative language specs under `specs/`
- subsystem normative specs under `runtime/specs/` and `generators/php/specs/`
- `runtime/specs/config.json` for the current runtime-supported surface

It does not define new language semantics.
It defines the canonical mapping from matrix families to the existing semantic
authorities already present in the project.

---

## 3. Precedence

Current authority order:

1. `specs/spec_map.md`
2. normative language specs under `specs/`
3. subsystem normative specs under `runtime/specs/` and `generators/php/specs/`
4. `runtime/specs/config.json`
5. derived operator-matrix docs under `specs/operator_matrix/`
6. implementation/code-derived observations

Interpretation rule:
- the matrix must follow the same precedence
- grouped family mappings must not weaken the existing project authority order

---

## 4. Conflict Rule

The following state is a project error and must be fixed:

- a higher-authority spec defines behavior/support
- but `runtime/specs/config.json` does not encode the required runtime-supported surface

This is not a valid steady-state interpretation.
It is a synchronization defect between:
- specs
- runtime config
- generation/runtime implementation
- operator-matrix docs

Required action:
- fix the project sources
- then regenerate/review the matrix
- then update affected tests

---

## 5. Mapping Format

Each family mapping records:

- **Primary authority**
  - grouped references that define the semantics of the family
- **Secondary authority**
  - grouped references that refine or constrain the family
- **Runtime-config dependency**
  - how `runtime/specs/config.json` participates
- **Implementation touchpoints**
  - code/runtime areas typically affected
- **Notes**
  - family-specific interpretation rules
- **Overrides**
  - item-level exceptions when family-level mapping is insufficient

The mapping is intentionally family-first.
Item-level source declarations must remain rare.

---

## 6. Family Mappings

### 6.1 `condition_truthiness`

#### Primary authority
- `specs/conditional_expression_matrix.md`
- `specs/dynamic_types.md`
- `runtime/specs/spec.md`

#### Secondary authority
- `specs/mixed_boundary_transitional.md`
- `runtime/specs/catalog.md`

#### Runtime-config dependency
- `runtime/specs/config.json` must encode the current supported condition-evaluation surface
- config may constrain current implementation coverage, but must not contradict higher-authority truthiness rules

#### Implementation touchpoints
- condition helper logic
- ternary/elvis condition lowering
- runtime truthiness helpers for allowed carriers/wrappers
- generator condition lowering paths

#### Notes
- spec defines truthiness semantics
- runtime must conform
- matrix rows must remain profile-explicit
- `condition_truthiness` is distinct from explicit `(bool)` cast
- `mixed_t` participation is hybrid:
  - allowed kind space follows specs
  - per-kind execution behavior must match runtime implementation and must be kept synchronized with specs

#### Overrides
- none by default

---

### 6.2 `casts_explicit`

#### Primary authority
- `specs/dynamic_types.md`
- `runtime/specs/spec.md`

#### Secondary authority
- `specs/mixed_boundary_transitional.md`
- `runtime/specs/catalog.md`

#### Runtime-config dependency
- `runtime/specs/config.json` defines the current machine-readable explicit-cast surface
- missing config coverage for spec-required cast support is a project error

#### Implementation touchpoints
- cast helpers
- explicit typed-boundary paths
- mixed/wrapper cast normalization
- generator explicit-cast lowering

#### Notes
- casts are family-mapped, not item-mapped
- wrapper behavior is split by family semantics and fallback runtime behavior
- `mixed_t` cast behavior is hybrid:
  - kind space and allowed semantic targets come from specs
  - runtime behavior per supported kind must conform to spec and runtime contracts

#### Overrides
- use item-level override only if a specific cast target receives a dedicated contract later

---

### 6.3 `operators_unary`

#### Primary authority
- `runtime/specs/config.json`
- `runtime/specs/spec.md`

#### Secondary authority
- `specs/dynamic_types.md`
- `runtime/specs/operator_generation_flow.md`
- `runtime/specs/catalog.md`

#### Runtime-config dependency
- `runtime/specs/config.json` is the machine-readable operator-surface source for supported unary combinations

#### Implementation touchpoints
- generated operator surface
- unary helper/operator implementation
- generator unary lowering
- target-kind-sensitive mutation paths

#### Notes
- support must follow current authority order
- target-kind rules apply to mutating items
- profile-explicit rows remain mandatory

#### Overrides
- `pre_increment`
- `post_increment`
- `pre_decrement`
- `post_decrement`

These items may require operand-target-kind-specific review even when the family mapping remains shared.

---

### 6.4 `operators_binary_arithmetic_bitwise`

#### Primary authority
- `runtime/specs/config.json`
- `runtime/specs/spec.md`

#### Secondary authority
- `specs/dynamic_types.md`
- `runtime/specs/operator_generation_flow.md`
- `runtime/specs/catalog.md`

#### Runtime-config dependency
- `runtime/specs/config.json` is the machine-readable source for supported binary arithmetic/bitwise combinations
- config must remain synchronized with higher-authority numeric/dynamic-type semantics

#### Implementation touchpoints
- generated binary operator surface
- arithmetic and bitwise runtime implementation
- generator binary operator lowering

#### Notes
- family-level grouping is intentional
- profile-partitioned support must remain explicit in matrix rows
- if specs require support that config omits, the project is in error and must be fixed

#### Overrides
- none by default

---

### 6.5 `operators_binary_logical_relational`

#### Primary authority
- `runtime/specs/config.json`
- `runtime/specs/spec.md`

#### Secondary authority
- `specs/dynamic_types.md`
- `runtime/specs/operator_generation_flow.md`
- `runtime/specs/catalog.md`

#### Runtime-config dependency
- `runtime/specs/config.json` is the machine-readable source for supported logical/relational combinations

#### Implementation touchpoints
- generated logical/relational operator surface
- comparison helpers/operators
- generator comparison/logical lowering

#### Notes
- this family excludes strict identity
- profile-explicit support is required; no coarse source rows
- higher-authority semantic rules still govern interpretation

#### Overrides
- none by default

---

### 6.6 `operators_identity_strict`

#### Primary authority
- `specs/dynamic_types.md`
- `runtime/specs/spec.md`

#### Secondary authority
- `specs/mixed_boundary_transitional.md`
- `runtime/specs/catalog.md`

#### Runtime-config dependency
- `runtime/specs/config.json` may constrain current integrated support where applicable
- config does not replace the helper-owned strict-identity semantics

#### Implementation touchpoints
- `runtime/include/lang/php/support/php_value.hpp`
- helper-owned normalization/equality logic
- generator lowering for strict identity operators

#### Notes
- this family is intentionally isolated from ordinary comparison
- authority split is explicit:
  - normalization rules belong to specs
  - execution belongs to the helper/runtime implementation
- runtime must conform to the normative behavior

#### Overrides
- `identical`
- `not_identical`

These are family-defining exceptions by design and therefore always remain in this dedicated family.

---

### 6.7 `operators_conditional_selection`

#### Primary authority
- `specs/conditional_expression_matrix.md`
- `specs/dynamic_types.md`
- `runtime/specs/spec.md`

#### Secondary authority
- `specs/mixed_boundary_transitional.md`
- `runtime/specs/catalog.md`

#### Runtime-config dependency
- `runtime/specs/config.json` must encode the current supported conditional-selection surface
- config omissions against spec-required support are project errors

#### Implementation touchpoints
- `php::coalesce_eval(...)`
- `php::ternary_eval(...)`
- generator lowering for `??`, ternary, and elvis
- wrapper/result-shape normalization

#### Notes
- this family combines condition evaluation and result-shape selection semantics
- wrapper behavior follows:
  - family-level semantic rules first
  - runtime behavior as fallback only where needed
- profile-explicit rows are mandatory

#### Overrides
- `coalesce`
- `ternary`
- `elvis`

These items may reference different helper paths, but remain family-mapped unless future divergence requires dedicated source contracts.

---

### 6.8 `language_probes_and_reset`

#### Primary authority
- `specs/count_empty_isset_contract.md`
- `specs/array_semantics.md`
- `runtime/specs/spec.md`

#### Secondary authority
- `specs/dynamic_types.md`
- `runtime/specs/catalog.md`

#### Runtime-config dependency
- `runtime/specs/config.json` must reflect the current supported helper surface for probe/reset-related lowering where the runtime surface is machine-configured

#### Implementation touchpoints
- probe helper logic for `count`, `empty`, and `isset`
- keyed carrier/value-read paths
- keyed removal/reset paths
- generator lowering for probe/reset helpers

#### Notes
- grouped mapping is split semantically inside the family:
  - probes (`count`, `empty`, `isset`) are governed primarily by `specs/count_empty_isset_contract.md`
  - reset/removal (`unset`) is governed primarily by `specs/array_semantics.md`
- item-level rows must preserve keyed vs value form
- target-kind rules may apply for reset-sensitive cases

#### Overrides
- `unset_value`
- `unset_keyed`

These items may require more specific keyed/value reset references if a dedicated reset contract is added later.

---

## 7. Grouped Reference Rule

This document uses grouped references by design.

Reasons:
- matches the family-first architecture
- reduces duplication
- keeps review routing maintainable
- allows future item-level overrides without exploding the mapping surface

Grouped references must still be specific enough that a reviewer can identify:
- the primary semantic owner
- the runtime-config dependency
- the typical implementation touchpoints

---

## 8. Regeneration / Review Trigger Guidance

A change in any mapped authority file requires review of the affected family and of:
- `specs/operator_matrix/profile_semantics_v1.md`
- `specs/operator_matrix/generation_rules_v1.md`
- `specs/operator_matrix/output_schema_v1.md`
- `specs/operator_matrix/test_generation_rules_v1.md`

In addition, consider:
- generator lowering
- runtime helper behavior
- edge-case tests derived from affected profiles

---

## 9. Summary

The operator-matrix subsystem uses a concrete family-first mapping.

Core decisions captured here:
- `===` / `!==` live in a dedicated strict-identity family
- specs define behavior; runtime must conform
- `mixed_t` remains hybrid where family semantics require both spec-owned kind rules and runtime-owned execution behavior
- wrappers are handled family-first, with runtime fallback only where necessary
- spec/config mismatches are project errors, not valid steady-state interpretations
- grouped references are intentional and authoritative for matrix routing
