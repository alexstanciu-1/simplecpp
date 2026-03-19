# TEST_COVERAGE.md

## Scope

This document is **normative**.

It defines coverage expectations for the generated Simple C++ test suite.

It does NOT define semantic behavior.

Semantic authority exists only in:
- SPECIFICATIONS.md
- TYPE_FAMILY_REGISTRY.md
- DERIVATION_RULES.md
- SEMANTIC_MATRIX.md

---

## Coverage Goal

The generated suite must provide full coverage of the normalized operational matrix for the currently registered family set.

Coverage means:
- every allowed operational case has a positive test
- every forbidden operator family/category has representative negative tests
- family-specific special cases are covered explicitly
- template compatibility and mismatch cases are covered deterministically

---

## Type Inventory Rule

This document MUST NOT redefine the type inventory independently.

The canonical inventory source is:
- TYPE_FAMILY_REGISTRY.md

---

## Required Coverage Dimensions

Coverage must include:
- operator-family coverage
- type-family coverage
- trait-sensitive coverage where traits affect derivation
- same-`T` and different-`T` template coverage
- `null_t` special-case coverage
- conditional validity coverage
- explicit conversion coverage
- assignment and compound-assignment coverage

---

## Final

This document defines the required coverage standard only.
