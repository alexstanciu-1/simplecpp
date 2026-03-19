# TEST_COVERAGE.md

## Scope

This document is non-normative.

It tracks actual test coverage for behavior defined in:
- SPECIFICATIONS.md
- SEMANTIC_MATRIX.md

It does NOT define language semantics.

---

## Purpose

- track which rules are covered by tests
- identify coverage gaps
- ensure traceability

---

## Coverage Model

### Source of truth

All coverage maps to:
- SPECIFICATIONS.md
- SEMANTIC_MATRIX.md

---

### Coverage types

- Positive coverage (allowed operations)
- Negative coverage (forbidden operations)

---

### Granularity

Coverage must exist per:
- operator
- type combination
- conversion
- conditional rule

---

## Covered Areas

- arithmetic
- comparison
- logical
- assignment
- compound assignment
- conversions
- conditional semantics
- pointer/wrapper behavior
- nullable<T>

---

## Known Coverage Gaps

Must explicitly list:
- missing matrix coverage
- missing negative tests

---

## Traceability

Each test must map to:
- matrix rule
- operator + types

---

## Clarification

This document tracks actual coverage.

Required coverage is defined in TEST_MATRIX.md.

---

## Final Statement

Tracks coverage only.

Does not define semantics.


## TYPE INVENTORY RULE

This document MUST NOT redefine the type inventory.

All type definitions MUST be sourced from:
SPECIFICATIONS.md → TYPE_FAMILY_REGISTRY

Any duplication is considered invalid and must be removed.
