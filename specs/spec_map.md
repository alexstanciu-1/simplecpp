# Prism++ Specification Map (v1)
Doc Status: normative
> Transitional implementation note: see `specs/mixed_boundary_transitional.md`.

Status: Active
Purpose: document hierarchy, authority, and interpretation rules for the current project.

---

## 1. Why this file exists

This file is the entry point for reading the project specs.

Its job is to explain:
- which documents are authoritative
- which documents are descriptive or supporting
- how conflicts must be resolved
- how v1 temporary compromises must be interpreted

If a new implementation pass, review, or chat session starts, this document should be read first.

---

## 2. Document status labels

Every repository documentation artifact should be classified as one of:
- `normative`
- `derived`
- `supporting`
- `planning`
- `historical`

These labels are used as follows:
- `normative`
  - defines current contract, structure, or required behavior inside its authority domain
- `derived`
  - generated or downstream coordination material derived from higher authorities
- `supporting`
  - explanatory, operational, onboarding, or reference material that helps use the project but does not define semantics by itself
- `planning`
  - roadmap, transition, audit, review, or intended-future material that is not yet current authority
- `historical`
  - archived material kept for traceability only

Repository-wide labeling is recorded in:
- `specs/document_status_catalog.json`

Interpretation rules:
- Markdown docs should carry an in-file `Doc Status:` label.
- JSON docs/configs should be classified in the catalog unless their schema is explicitly designed to carry a status field safely.
- A document's status label does not override the authority order below; it clarifies how the document should be read within that order.

---

## 3. Authority and priority order

The authority stack for the current project is:

### Level 1 - Master authority map

1. `specs/spec_map.md`
   - the master map of document roles, priority, and conflict resolution
   - if this file is updated, the rest of the stack should be interpreted through the updated map

### Level 2 - User-visible language and project semantics

2. top-level normative specs under `specs/`
   - these define user-visible meaning and project policy
   - they are the primary authority for language behavior, narrowing decisions, and current v1 commitments

3. especially important top-level semantic authorities:
   - `specs/dynamic_types.md`
   - `specs/array_semantics.md`
   - `specs/compact_layout_types.md`
   - `specs/metaprogramming_contract.md`
   - `specs/count_empty_isset_contract.md`
   - `specs/async_await.md`
   - relevant builtin contracts under `specs/builtins/`

4. `specs/dynamic_types.md` remains especially authoritative for:
   - `## 1.2 Explicit Typed Boundaries`
   - `## 1.3 Technical Compromises to Preserve Explicit Typed Boundaries in v1`

### Level 3 - Architecture and anti-drift governance

5. architecture-governance rules under `specs/architecture/`
   - authoritative for structure, dependency direction, semantic ownership, and anti-drift implementation rules
   - must not override user-visible semantics from Level 2

6. especially important architecture authorities:
   - `specs/architecture/runtime_layering.md`
   - `specs/architecture/runtime_design/semantic_consistency.md`
   - `specs/architecture/runtime_design/structure.md`
   - `specs/architecture/runtime_design/README.md`

### Level 4 - Runtime and generator subsystem contracts

7. runtime subsystem specs under `runtime/specs/`
   - authoritative for runtime public contract, helper behavior, and runtime organization within the limits set by Levels 2 and 3

8. generator subsystem specs under `generators/php/specs/`
   - authoritative for lowering and supported/rejected generator behavior within the limits set by Levels 2 and 3

9. especially important subsystem authorities:
   - `runtime/specs/spec.md`
   - `generators/php/specs/rules.md`
   - supporting local subsystem docs under `runtime/specs/` and `generators/php/specs/`

10. workflow-specialized normative specs that remain subordinate to higher levels:
   - `specs/builtin_intake_procedure.md`
   - `specs/runtime/error_handling.md`
   - `specs/git_workflow_release_procedure.md`

### Level 5 - Machine-readable config and derived coordination data

11. machine-readable JSON sources are authoritative inside their own machine-owned domain
   - they refine support/config/data shape
   - they must not invent higher-level semantics by themselves

12. especially important machine-readable sources:
   - `runtime/specs/config.json`
   - `specs/operator_matrix/data/*.json`
   - `generators/php/specs/php_builtins.json`

### Level 6 - Test governance and operational tooling

13. test-governance specs under `tests/specs/`
   - authoritative for how tests are derived, classified, and organized
   - subordinate to semantic, runtime, and generator authority

14. tool docs and harness configs
   - operationally important
   - not primary semantic authority unless explicitly promoted by a higher-level spec

### Level 7 - Planning, audit, and implementation evidence

15. audit / todo / reference / planning documents
   - examples: `dynamic_types_impl_audit.md`, `mixed_boundary_transitional.md`, `todo*.md`, `references.md`
   - useful for planning, review, and drift detection
   - non-authoritative unless explicitly promoted

16. source code, current implementation behavior, and tests
   - implementation is evidence and regression input
   - it must conform to the authoritative specs above rather than define them

---

## 4. Conflict-resolution rule

When sources disagree, resolve them in this order:

1. `specs/spec_map.md`
2. top-level semantic specs under `specs/`
3. architecture-governance specs under `specs/architecture/`
4. subsystem specs under `runtime/specs/` and `generators/php/specs/`
5. machine-readable config/data sources
6. test-governance specs
7. planning/audit/reference docs
8. implementation behavior

A disagreement should not be silently normalized away. It should be recognized as one of:
- `spec_gap`
- `known_fail`
- `regression`

---

## 5. v1 priority rule

For v1, the following rule is normative:

> If a stricter long-term runtime or API-purity preference conflicts with
> `specs/dynamic_types.md` section `1.2 Explicit Typed Boundaries` or section
> `1.3 Technical Compromises to Preserve Explicit Typed Boundaries in v1`, then
> sections 1.2 and 1.3 take priority for current user-visible behavior.

This means:
- a valid explicit typed boundary site must continue to work in v1
- temporary runtime bridges are allowed when the generator does not yet provide full explicit-cast coverage
- such bridges must not be removed merely because they are not the desired long-term design

---

## 6. Interpretation rules

### 5.1 Explicit typed boundary sites are normative in v1

If the destination type is clearly visible and the site is allowed by `specs/dynamic_types.md`, that behavior is part of the v1 contract.

Typical examples include typed destinations such as:
- typed local assignment
- typed property assignment
- typed by-value function argument
- typed by-value method argument
- typed return

### 5.2 The generator should make boundaries explicit when it can

The preferred long-term design is for the generator to emit explicit conversion/cast boundaries.

However, until the generator is capable of doing that reliably for all valid sites, runtime bridges required by `1.2` and `1.3` remain allowed and, where necessary, required.

### 5.3 `mixed_t` must not be interpreted in isolation

`mixed_t` runtime cleanup must be reviewed against:
- `specs/dynamic_types.md`
- `specs/array_semantics.md` when the behavior depends on array/table access
- especially dynamic-types sections `1.2` and `1.3`
- current generator capability

Do not apply a pure "strict runtime only" interpretation if that breaks a valid v1 explicit typed boundary site.

### 5.4 Do not silently upgrade temporary rules into permanent doctrine

Temporary v1 bridges are allowed because of current generator limitations.
They should be documented as temporary when possible.
They should be removed only after equivalent generator behavior exists and tests confirm parity.

### 5.5 Architecture-governance documents do not override language meaning

The documents under `specs/architecture/` are normative for structure, ownership, layering, and anti-drift rules.

They must be used to organize implementation correctly, but they must not be read as permission to change user-visible semantics defined by higher-level top-level specs.

### 5.6 Machine-readable config/data does not invent semantics by itself

JSON config and matrix data are authoritative in their own machine-owned domain, but they do not outrank the semantic specs.

If JSON, code, and prose disagree, the higher-level semantic specs win unless the authority map is explicitly updated.

---

## 7. Required implementation checklist

Before changing generator or runtime behavior in areas touched by dynamic typing or conditional semantics, check all of the following:

1. Is this a valid explicit typed boundary site under `specs/dynamic_types.md`?
2. Does section `1.3` allow a temporary v1 compromise here?
3. Does the generator already emit an explicit bridge/cast for this exact site?
4. If not, is the runtime still required to support it?
5. Is there a top-level semantic spec for this behavior family?
6. Is there an architecture-governance rule fixing ownership or helper centralization for this family?
7. Are there tests covering the current behavior?
8. Will the change break current user-visible v1 behavior?

If any answer is uncertain, the change should be treated as spec-sensitive and reviewed against the relevant higher-level specs first.

---

## 8. Common mistakes to avoid

Do not do the following without checking the higher-level specs first:

- remove `mixed_t` to native bridge behavior only because it looks cleaner in C++
- treat runtime purity as automatically higher priority than user-visible v1 compatibility
- assume generator explicit-cast coverage exists everywhere
- change runtime behavior first and plan to fix generator later
- interpret audit/todo files as higher authority than the normative specs
- let machine-readable config or current implementation silently override top-level semantics
- treat architecture docs as permission to change semantics rather than to organize implementation

---

## 9. Document roles

### `specs/dynamic_types.md`
Role:
- primary language/spec authority for dynamic typing

Authority:
- normative

### `specs/array_semantics.md`
Role:
- primary language/spec authority for the current supported array/table subset
- defines current value-read, write-path, nested-mutation, append, and `isset` / `empty` / `unset` behavior

Authority:
- normative

### `specs/count_empty_isset_contract.md`
Role:
- primary top-level contract for `count(...)`, `empty(...)`, and `isset(...)`
- defines the shared semantic family boundary for these helpers, including narrowed emptiness and non-mutating probe rules

Authority:
- normative

### `specs/builtins/*`
Role:
- per-builtin user-visible contracts for accepted inputs, narrowed compatibility, return states, wrapper/runtime split, and test expectations

Authority:
- normative within each builtin's scope

### `runtime/specs/spec.md`
Role:
- runtime semantic contract and runtime/generator split
- authoritative also for the rule that translated/runtime failures throw in-process exceptions and only outer process boundaries map uncaught failures to non-zero exit status

Authority:
- normative, but subordinate to top-level semantic specs

### `runtime/specs/hash_t.md`
Role:
- local runtime contract for `hash_t`

Authority:
- normative for `hash_t`, but subordinate to higher-level documents

### `runtime/specs/runtime_generation_guidelines.md`
Role:
- runtime code-generation discipline and layering guidance

Authority:
- normative for generation workflow, but subordinate to higher-level documents

### `specs/runtime/error_handling.md`
Role:
- runtime error output contract
- defines default human-readable messages versus JSON-mode output
- defines stable versus non-stable JSON fields and debug-trace behavior

Authority:
- normative for runtime error presentation, but subordinate to higher-level documents

### `specs/builtin_intake_procedure.md`
Role:
- mandatory procedure for new builtin intake
- defines contract freeze, folder placement, compile planning, registration discipline, and sandbox-test completion requirements for builtin work

Authority:
- normative for builtin workflow, but subordinate to higher-level documents

### `specs/architecture/runtime_layering.md`
Role:
- normative architecture rule for runtime/core/module/language layering
- fixes dependency direction and public umbrella-header intent
- fixes the current multi-language / multi-module build-composition direction

Authority:
- normative for runtime/language/module layering, but subordinate to higher-level documents

### `specs/architecture/runtime_design/semantic_consistency.md`
Role:
- normative runtime design-governance rule for semantic consistency and anti-drift structure
- fixes file-ownership, shared-helper reuse, semantic-authority, and anti-fallback rules for runtime work

Authority:
- normative for runtime semantic organization and code-structure discipline, but subordinate to higher-level documents and user-visible language semantics

### `specs/architecture/runtime_design/structure.md`
Role:
- normative runtime structure rule for language isolation and operator-family placement
- fixes the current direction that PHP semantic operator families belong under `runtime/include/lang/php/operators/`

Authority:
- normative for runtime file organization and ownership, but subordinate to user-visible language semantics

### `specs/architecture/runtime_design/README.md`
Role:
- entry point for the runtime design-governance folder
- defines the scope and placement rule for runtime design documents

Authority:
- supporting architecture-governance entry point, subordinate to the more specific runtime-design documents

### `generators/php/specs/rules.md`
Role:
- main generator lowering rules
- includes the authoritative lowering model for `throw` / `try` / `catch` / `finally` in the current supported subset

Authority:
- normative for generator behavior, but subordinate to higher-level documents

### `generators/php/specs/primary_type_normalized_parameters.md`
Role:
- normative generator/language-side contract for primary-type normalized PHP union parameters
- documents `@arg.<param>.from(Type) = ...` metadata and the current implementation boundary

Authority:
- normative for this feature, but subordinate to higher-level documents

### `generators/php/specs/rules_catalog.md`
Role:
- catalog of normalized generator rules and examples

Authority:
- supporting/normative only insofar as it agrees with higher-level documents

### `runtime/specs/config.json`
Role:
- machine-readable runtime contract/config source
- defines the current data-driven support matrix for runtime-owned types, casts, overloads, and helpers

Authority:
- authoritative inside its machine-readable config domain, but subordinate to higher-level semantic specs

### `specs/operator_matrix/data/*.json`
Role:
- machine-readable operator-matrix input data
- derived coordination source for matrix generation, validation, and test-seed generation

Authority:
- authoritative for matrix-tool input shape, but subordinate to normative specs and runtime contracts

### `tests/specs/*`
Role:
- test derivation, classification, provenance, and planning rules

Authority:
- normative for test governance, but subordinate to semantic, runtime, and generator authority

### audit / todo / reference files
Role:
- planning, review, tracking, explanation

Authority:
- non-authoritative unless explicitly promoted by a higher-level spec

---

## 10. Recommended reading order for a new session

1. `specs/spec_map.md`
2. relevant top-level semantic specs:
   - `specs/dynamic_types.md`
   - `specs/array_semantics.md`
   - `specs/count_empty_isset_contract.md` when conditional probes/counting are involved
   - relevant builtin spec under `specs/builtins/`
3. relevant architecture-governance specs:
   - `specs/architecture/runtime_layering.md`
   - `specs/architecture/runtime_design/semantic_consistency.md`
   - `specs/architecture/runtime_design/structure.md`
4. relevant subsystem spec:
   - `generators/php/specs/rules.md`
   - `runtime/specs/spec.md`
5. relevant local supporting docs and machine-readable config/data
6. implementation files

---

## 11. Summary rule

When in doubt:
- start from `specs/spec_map.md`
- then read the relevant top-level semantic specs
- then read the relevant architecture-governance specs
- then read the runtime/generator subsystem specs
- and for current v1 dynamic-type behavior, let `specs/dynamic_types.md` sections `1.2` and `1.3` decide before applying cleanup instincts

### `specs/cli_installation_milestone.md`
Role:
- installer and public CLI contract for the current first-binary milestone
- defines repo-based current-user install model and `scpp` command surface

Authority:
- supporting/spec-planning authority for installation and launcher behavior in this milestone

### `specs/project_build_v1.md`
Role:
- first practical project-build contract
- defines `scpp init`, `prism.json`, `.prism/`, and Ninja-backed `scpp build` / `scpp run`

Authority:
- supporting/spec-planning authority for the current project-build workflow

### `specs/operator_matrix/`
Role:
- derived coordination specs for the operator / cast / helper matrix
- includes regeneration policy, source mapping, validation guidance, and test-generation coordination

Authority:
- derived and subordinate to normative specs and runtime contracts
- must not define new semantics or override `runtime/specs/config.json` for current runtime-supported combinations
