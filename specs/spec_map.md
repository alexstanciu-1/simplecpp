# Prism++ Specification Map (v1)

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

## 2. Authority and priority order

### Level 1 — Project language/spec authority

1. `specs/spec_map.md`
   - authority map
   - interpretation rules
   - conflict-resolution rules

2. `specs/dynamic_types.md`
   - authoritative for dynamic-type user-visible behavior
   - especially authoritative for:
     - `## 1.2 Explicit Typed Boundaries`
     - `## 1.3 Technical Compromises to Preserve Explicit Typed Boundaries in v1`

3. `specs/array_semantics.md`
   - authoritative for the current supported array/table subset
   - especially authoritative for:
     - read/write/missing-key behavior
     - nested mutation
     - append semantics
     - `isset` / `empty` / `unset` on array/table carriers
     - the rule that typed value destinations do not change array-read missing-key behavior

### Level 2 — Subsystem normative rules

4. `php_generator/specs/rules.md`
   - authoritative for generator lowering rules
   - must follow `specs/dynamic_types.md`
   - must not silently narrow valid v1 explicit typed boundary sites

5. `runtime/specs/spec.md`
   - authoritative for runtime semantics and runtime/generation relationship
   - must follow `specs/dynamic_types.md`
   - runtime cleanup must not remove bridges still required by valid v1 explicit typed boundary sites

6. other subsystem spec files under:
   - `php_generator/specs/`
   - `runtime/specs/`
   - may refine local behavior
   - must not contradict Level 1 documents

### Level 3 — Audit / planning / supporting documents

7. audit, todo, and reference documents under `specs/`
   - examples: `dynamic_types_impl_audit.md`, `mixed_boundary_transitional.md`, `todo*.md`, `references.md`
   - useful for planning and consistency checks
   - not higher authority than the normative specs above
   - includes `cli_installation_milestone.md` for the current first-binary installer/CLI contract

### Level 4 — Implementation

8. source code and tests
   - runtime implementation
   - generator implementation
   - tests
   - must conform to the authoritative specs above

---

## 3. v1 priority rule

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

## 4. Interpretation rules

### 4.1 Explicit typed boundary sites are normative in v1

If the destination type is clearly visible and the site is allowed by `specs/dynamic_types.md`, that behavior is part of the v1 contract.

Typical examples include typed destinations such as:
- typed local assignment
- typed property assignment
- typed by-value function argument
- typed by-value method argument
- typed return

### 4.2 The generator should make boundaries explicit when it can

The preferred long-term design is for the generator to emit explicit conversion/cast boundaries.

However, until the generator is capable of doing that reliably for all valid sites, runtime bridges required by `1.2` and `1.3` remain allowed and, where necessary, required.

### 4.3 `mixed_t` must not be interpreted in isolation

`mixed_t` runtime cleanup must be reviewed against:
- `specs/dynamic_types.md`
- `specs/array_semantics.md` when the behavior depends on array/table access
- especially dynamic-types sections `1.2` and `1.3`
- current generator capability

Do not apply a pure “strict runtime only” interpretation if that breaks a valid v1 explicit typed boundary site.

### 4.4 Do not silently upgrade temporary rules into permanent doctrine

Temporary v1 bridges are allowed because of current generator limitations.
They should be documented as temporary when possible.
They should be removed only after equivalent generator behavior exists and tests confirm parity.

---

## 5. Required implementation checklist

Before changing generator or runtime behavior in areas touched by dynamic typing, check all of the following:

1. Is this a valid explicit typed boundary site under `specs/dynamic_types.md`?
2. Does section `1.3` allow a temporary v1 compromise here?
3. Does the generator already emit an explicit bridge/cast for this exact site?
4. If not, is the runtime still required to support it?
5. Are there tests covering the current behavior?
6. Will the change break current user-visible v1 behavior?

If any answer is uncertain, the change should be treated as spec-sensitive and reviewed against `specs/dynamic_types.md` first.

---

## 6. Common mistakes to avoid

Do not do the following without checking `specs/dynamic_types.md` sections 1.2 and 1.3:

- remove `mixed_t` to native bridge behavior only because it looks cleaner in C++
- treat runtime purity as automatically higher priority than user-visible v1 compatibility
- assume generator explicit-cast coverage exists everywhere
- change runtime behavior first and plan to fix generator later
- interpret audit/todo files as higher authority than the normative specs

---

## 7. Document roles

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

### `runtime/specs/spec.md`
Role:
- runtime semantic contract and runtime/generator split
- authoritative also for the rule that translated/runtime failures throw in-process exceptions and only outer process boundaries map uncaught failures to non-zero exit status

Authority:
- normative, but subordinate to `specs/dynamic_types.md` for dynamic-type visible behavior

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

### `php_generator/specs/rules.md`
Role:
- main generator lowering rules
- includes the authoritative lowering model for `throw` / `try` / `catch` / `finally` in the current supported subset

Authority:
- normative for generator behavior, but subordinate to higher-level documents

### `php_generator/specs/primary_type_normalized_parameters.md`
Role:
- normative generator/language-side contract for primary-type normalized PHP union parameters
- documents `@arg.<param>.from(Type) = ...` metadata and the current implementation boundary

Authority:
- normative for this feature, but subordinate to higher-level documents

### `php_generator/specs/rules_catalog.md`
Role:
- catalog of normalized generator rules and examples

Authority:
- supporting/normative only insofar as it agrees with higher-level documents

### audit / todo / reference files
Role:
- planning, review, tracking, explanation

Authority:
- non-authoritative unless explicitly promoted by a higher-level spec

---

## 8. Recommended reading order for a new session

1. `specs/spec_map.md`
2. `specs/dynamic_types.md`
3. `specs/array_semantics.md` when arrays/tables are involved
4. relevant subsystem spec:
   - `php_generator/specs/rules.md`
   - `runtime/specs/spec.md`
4. relevant local supporting docs
5. implementation files

---

## 9. Summary rule

When in doubt:
- start from `specs/spec_map.md`
- then read `specs/dynamic_types.md`
- and for current v1 dynamic-type behavior, let sections `1.2` and `1.3` decide before applying cleanup instincts

### `specs/cli_installation_milestone.md`
Role:
- installer and public CLI contract for the current first-binary milestone
- defines repo-based current-user install model and `scpp` command surface

Authority:
- supporting/spec-planning authority for installation and launcher behavior in this milestone
