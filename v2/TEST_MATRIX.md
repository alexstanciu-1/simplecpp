# TEST_MATRIX.md

## 1. Scope

This document is **normative**.

It defines the required **test surface categories** that must be materialized from the specification layer.

It does NOT define semantic behavior.

Semantic authority exists only in:
- SPECIFICATIONS.md
- TYPE_FAMILY_REGISTRY.md
- DERIVATION_RULES.md
- SEMANTIC_MATRIX.md

---

## 2. Authority Relationship

1. SPECIFICATIONS.md — semantic core
2. TYPE_FAMILY_REGISTRY.md — family inventory and traits
3. DERIVATION_RULES.md — deterministic derivation logic
4. SEMANTIC_MATRIX.md — normalized operational outcomes
5. TEST_MATRIX.md — required validation surface
6. TEST_MATERIALIZATION_CONTRACT.md — exact materialization rules

---

## 3. Type Inventory Rule

This document MUST NOT redefine the type inventory independently.

The canonical inventory source is:
- TYPE_FAMILY_REGISTRY.md

This document may reference current families only where required to define the validation surface.

---

## 4. Required Test Surface by Operator Family

The generated suite MUST cover the following operator families:

- arithmetic
- equality
- relational
- logical
- assignment
- compound assignment
- explicit conversions
- conditionals
- family-specific rules

For each family above, tests MUST cover:
- positive compile-pass cases
- negative compile-fail cases
- execution cases where runtime-observable behavior exists

---

## 5. Required Compatibility Coverage

Tests MUST cover the following compatibility shapes where they exist in the matrix:

- primitive self-family positive pairs
- primitive cross-family positive pairs
- primitive forbidden pairs
- unary template same-`T` positive pairs
- unary template different-`T` negative pairs
- family-specific `null_t` interaction rules
- pointer-family same-outer-family same-`T` positive pairs
- pointer-family same-outer-family different-`T` negative pairs
- pointer-family cross-family negative pairs

---

## 6. Required Conditional Coverage

Tests MUST cover:
- direct `bool_t` conditionals
- native `bool` results produced by allowed comparisons
- native `bool` results produced by allowed logical expressions
- representative forbidden non-boolean semantic operands in conditionals

---

## 7. Required Conversion Coverage

Tests MUST cover:
- every allowed explicit conversion
- representative forbidden explicit conversions from each conversion category
- rejection of implicit conversions

---

## 8. Required Matrix Completeness Validation

The generated validation suite MUST be able to demonstrate that:
- every explicitly allowed matrix case is covered positively
- every representative forbidden family category is covered negatively
- template mismatch cases are covered deterministically
- family-specific edge rules are covered explicitly

---

## Final Statement

This document defines what must be tested.

It does not define what is allowed.
