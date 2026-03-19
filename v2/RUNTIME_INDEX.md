# RUNTIME_INDEX.md

## Scope

This document is **non-normative**.

It provides an index and navigation guide for runtime-related documentation in the Simple C++ system.

It does NOT define language semantics.

All language behavior is defined exclusively in the specification layer:
- SPECIFICATIONS.md
- TYPE_FAMILY_REGISTRY.md
- DERIVATION_RULES.md
- SEMANTIC_MATRIX.md

Runtime documents implement and enforce those semantics but do not define them.

---

## Purpose

This index exists to:
- organize runtime documentation
- clarify the role of each runtime document
- guide contributors to the correct source of truth
- reinforce separation between specification and implementation

---

## Relationship to the Specification

The runtime layer is subordinate to the specification.

- SPECIFICATIONS.md defines high-level rules and invariants
- TYPE_FAMILY_REGISTRY.md defines the canonical family inventory and trait model
- DERIVATION_RULES.md defines deterministic semantic derivation
- SEMANTIC_MATRIX.md defines normalized allowed and forbidden operations
- runtime documents implement those rules

If any runtime document appears to contradict the specification layer, the specification layer takes precedence.

---

## Runtime Documentation Overview

### 1. Core Runtime Constraints

#### RUNTIME_API_CONTRACT.md

Defines the canonical public runtime API surface.

Includes:
- required public include
- required namespaces
- required public types
- required operator and conversion surface boundaries

Does NOT define:
- semantic meaning
- allowed/forbidden semantics independently

---

#### RUNTIME_REQUIREMENTS.md

Defines implementation-level constraints required to enforce the specification.

Includes:
- enforcement expectations
- compile-time guarantees
- constraints on implementation behavior

Does NOT define:
- language semantics
- allowed/forbidden operations

---

#### RUNTIME_CODING_RULES.md

Defines coding standards and implementation rules for runtime components.

Includes:
- safe implementation patterns
- restrictions on C++ features
- guidelines to prevent implicit behavior

Does NOT define:
- operator behavior
- type semantics
- conversion rules

---

### 2. Design and Rationale

#### RUNTIME_DESIGN_NOTE.md

Explains design decisions behind the runtime.

Includes:
- rationale for wrapper types
- reasoning behind compile-time enforcement
- trade-offs in implementation

Does NOT define:
- language rules
- semantic behavior

---

## Layer Position

The runtime layer sits below the specification layer:

1. Specification Layer
   - SPECIFICATIONS.md
   - TYPE_FAMILY_REGISTRY.md
   - DERIVATION_RULES.md
   - SEMANTIC_MATRIX.md

2. Generation Layer
   - source-to-source transformation

3. Runtime Layer
   - runtime implementation
   - runtime documentation

4. Validation Layer
   - test documents

Lower layers must not redefine higher-layer semantics.

---

## Navigation Guide

To understand the system:

- start with SPECIFICATIONS.md
- then read TYPE_FAMILY_REGISTRY.md
- then read DERIVATION_RULES.md
- then read SEMANTIC_MATRIX.md
- then consult runtime documents for implementation details

---

## Final

This document is an index only.
