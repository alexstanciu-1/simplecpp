# ARCHITECTURAL_RULES.md

## Scope

This document defines **architectural constraints and layering rules** for the Simple C++ system.

It does NOT define language semantics.

All semantic behavior MUST be defined exclusively in:
- SPECIFICATIONS.md
- TYPE_FAMILY_REGISTRY.md
- DERIVATION_RULES.md
- SEMANTIC_MATRIX.md

If any rule in this document appears to define behavior, it is invalid and must be relocated.

---

## Purpose

The purpose of this document is to:
- enforce clear separation of concerns
- define system layering
- prevent semantic leakage across layers
- ensure deterministic generation and validation

---

## Core Architectural Principles

### 1. Single Source of Truth

All language behavior is defined only in the specification layer:

- SPECIFICATIONS.md (normative semantic core)
- TYPE_FAMILY_REGISTRY.md (canonical family inventory and traits)
- DERIVATION_RULES.md (deterministic derivation logic)
- SEMANTIC_MATRIX.md (normalized operational definition)

No other document may define behavior.

---

### 2. No Semantic Duplication

Semantic rules MUST NOT appear in:
- runtime documents
- test documents
- architectural documents

If duplication occurs, it must be removed.

The runtime and test layers may restate obligations derived from semantics, but they MUST NOT redefine semantic meaning.

---

### 3. Direction of Dependency

Dependencies flow strictly downward:

1. Specification Layer
2. Generation Layer
3. Runtime Layer
4. Validation Layer

Lower layers MUST NOT redefine or override higher-layer semantics.

---

### 4. No Backward Influence

- Runtime implementation MUST NOT influence specification
- Tests MUST NOT define behavior
- Generated artifacts MUST NOT redefine semantics

If divergence occurs, documentation must be updated explicitly.

---

## Layering Model

### 1. Specification Layer

Documents:
- SPECIFICATIONS.md
- TYPE_FAMILY_REGISTRY.md
- DERIVATION_RULES.md
- SEMANTIC_MATRIX.md

Responsibilities:
- define semantic invariants
- define the complete closed-world family inventory
- define trait membership and compatibility classes
- define deterministic derivation rules
- define normalized allowed and forbidden operations
- define conversion and conditional rules

---

### 2. Generation Layer

Responsibilities:
- transform source code into Simple C++ representation
- produce code consistent with the semantic matrix
- derive runtime and test obligations from the specification layer

Constraints:
- must not invent new semantics
- must not silently coerce types
- must not perform semantic validation in the current scope unless explicitly designed to do so in future tooling

**Clarification (current architecture):**
The S2S generator is a syntax-directed lowering stage, not a semantic validator.

**Unknown-type lowering policy (current architecture):**
When operand or result types are unknown at S2S time, the generator may still emit the lowered C++ expression without resolving semantic types locally.

In such cases:
- the generator may use `auto` for unknown expression results
- the generator may emit unresolved external symbol references unchanged in lowered form
- the generator is not required to resolve external declarations
- invalid semantics are intended to fail during generated C++ compilation

Emission of an unresolved lowered expression does not imply semantic acceptance.

---

### 3. Runtime Layer

Responsibilities:
- implement the canonical runtime interface
- enforce semantics defined by the specification layer
- expose only the public runtime surface required by generated code

Constraints:
- runtime documents must not redefine semantic meaning
- runtime implementation must not broaden allowed behavior
- runtime convenience must not override semantic strictness

---

### 4. Validation Layer

Responsibilities:
- validate that runtime and generated code behavior match the specification layer
- materialize deterministic positive and negative tests

Constraints:
- tests must derive from the specification layer
- tests must not serve as semantic authority

---

## Two-Layer Semantic Model

The current specification architecture uses a two-layer semantic model inside the specification layer:

### Layer 1 — Compact source model

Defined by:
- TYPE_FAMILY_REGISTRY.md
- DERIVATION_RULES.md

Purpose:
- support maintainable language growth
- classify family behavior compactly
- make extension points explicit
- avoid repeated type inventories across subordinate documents

### Layer 2 — Normalized operational model

Defined by:
- SEMANTIC_MATRIX.md

Purpose:
- provide explicit, exhaustive, generator-facing outcomes
- preserve auditability
- preserve deterministic runtime and test generation
- prevent human interpretation during generation

The compact source model does not replace the matrix.
It exists to justify and stabilize the matrix.

---

## Final Constraint

Any future semantic extension, including new families or changed operator rules, is valid only if it updates the full specification layer coherently.

Partial semantic extension is invalid.
