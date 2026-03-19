# ARCHITECTURAL_RULES.md

## Scope

This document defines **architectural constraints and layering rules** for the Simple C++ system.

It does NOT define language semantics.

All semantic behavior MUST be defined exclusively in:
- SPECIFICATIONS.md
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

All language behavior is defined in exactly one place:

- SPECIFICATIONS.md (normative core)
- SEMANTIC_MATRIX.md (operational definition)

No other document may define behavior.

---

### 2. No Semantic Duplication

Semantic rules MUST NOT appear in:
- runtime documents
- test documents
- architectural documents

If duplication occurs, it must be removed.

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
- SEMANTIC_MATRIX.md

Responsibilities:
- define all language semantics
- define allowed and forbidden operations
- define conversion and conditional rules

---

### 2. Generation Layer

Responsibilities:
- transform source code into Simple C++ representation
- produce code consistent with the semantic matrix

Constraints:
- must not invent new semantics
- must not silently coerce types
- must not perform semantic validation in the current scope

**Clarification (current architecture):**
The S2S generator is a syntax-directed lowering stage, not a semantic validator.

**Unknown-type lowering policy (current architecture):**
When operand or result types are unknown at S2S time, the generator may still emit the lowered C++ expression without resolving semantic types locally.

In such cases:
- the generator may use `auto` for unknown expression results
- the generator may emit unresolved external symbol references unchanged in lowered form
- the generator is not required to resolve external declarations
- semantic validity, overload resolution, external symbol resolution, and failure for missing or incompatible declarations are handled at the C++ compilation stage

Emitting an unresolved expression does NOT imply that the expression is semantically valid.

---

### 3. Runtime Layer

Documents:
- RUNTIME_REQUIREMENTS.md
- RUNTIME_CODING_RULES.md
- RUNTIME_DESIGN_NOTE.md

Responsibilities:
- enforce semantics through C++ type system
- provide safe implementations of allowed operations
- prevent forbidden operations via compile-time constraints

Constraints:
- must not introduce new behavior
- must not allow implicit conversions
- must not contradict the matrix

---

### 4. Validation Layer

Documents:
- TEST_MATRIX.md
- TEST_COVERAGE.md

Responsibilities:
- validate that implementation conforms to specification
- ensure all matrix rules are tested

Constraints:
- tests are not authoritative
- tests must not define semantics

---

## Separation of Concerns

| Concern | Responsible Layer |
|--------|------------------|
| Language semantics | Specification Layer |
| Transformation | Generation Layer |
| Execution behavior | Runtime Layer |
| Verification | Validation Layer |

No layer may assume responsibilities of another.

---

## Enforcement Strategy

### C++ Enforcement (Primary)

- enforce typing rules
- prevent forbidden operations through compilation failure
- resolve externally visible declarations and overload selection
- reject missing or incompatible declarations

### S2S Enforcement (Future / Optional)

- may reject invalid constructs early
- not required in current architecture

---

## Consistency Requirements

The following must always hold:

- Every allowed operation appears in SEMANTIC_MATRIX.md
- Every forbidden operation is absent from SEMANTIC_MATRIX.md
- Every rule is testable
- Every test maps to a rule

---

## Evolution Rules

When modifying the system:

1. Update SPECIFICATIONS.md first
2. Update SEMANTIC_MATRIX.md
3. Update generators
4. Update runtime
5. Update tests

Never update lower layers first.

---

## Anti-Patterns (Forbidden)

The following are explicitly forbidden:

- Defining behavior in runtime documents
- Treating C++ compilation outcomes as defining language semantics
  instead of validating against the semantic matrix
- Using tests to determine semantics
- Allowing implicit conversions
- Adding rules outside the matrix

---

### Generator Completeness Guarantee

The S2S generator must be able to lower any syntactically valid source program
without requiring semantic completeness (such as full type knowledge or symbol resolution).

Semantic correctness is not a prerequisite for generation and is enforced
by the C++ compilation stage.

Generation failure must only occur for syntactic or structural reasons,
not for semantic invalidity.

## Final Rule

Architecture enforces structure.

Semantics define behavior.

These must remain strictly separated.
