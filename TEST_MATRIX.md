# TEST_MATRIX.md

## Scope

This document is **normative**.

Defines required test surface derived from:
- SPECIFICATIONS.md
- SEMANTIC_MATRIX.md

---

## Purpose

- define what must be tested
- ensure full coverage of matrix
- support test generation

---

## Derivation Rule

All tests must map to:
- SPECIFICATIONS.md
- SEMANTIC_MATRIX.md
- TEST_MATERIALIZATION_CONTRACT.md

No new semantics allowed.

---

## Test Categories

- Positive compile
- Positive execution
- Negative compile

Note:
S2S rejection is not required in the current architecture.
Validation is performed via C++ compilation.

Where the generator intentionally emits unresolved expressions or external symbols because types are unknown at S2S time, the required validation outcome is still expressed through compile success or compile failure.

---

## 1. Arithmetic

### Positive compile
- exactly one compile-pass test per allowed arithmetic matrix cell
- exactly one compile-pass test per allowed unary arithmetic matrix cell

### Positive execution
- exact execution cases and expected values are defined in TEST_MATERIALIZATION_CONTRACT.md

### Negative compile
- exactly one compile-fail test per forbidden arithmetic row/column family:
  - any arithmetic with `bool_t`
  - any arithmetic with `string_t`
  - any arithmetic with `null_t`
  - any arithmetic with `nullable<T>`
  - any arithmetic with pointer wrappers

---

## 2. Equality

### Positive compile
- exactly one compile-pass test per allowed equality matrix cell

### Positive execution
- exact execution cases and expected values are defined in TEST_MATERIALIZATION_CONTRACT.md

### Negative compile
- exactly one compile-fail test per forbidden equality family:
  - cross-family comparisons
  - pointer cross-wrapper
  - pointer different `T`

---

## 3. Relational

### Positive compile
- exactly one compile-pass test per allowed relational matrix cell

### Positive execution
- exact execution cases and expected values are defined in TEST_MATERIALIZATION_CONTRACT.md

### Negative compile
- exactly one compile-fail test per forbidden non-numeric family

---

## 4. Logical

### Positive compile
- exactly one compile-pass test per allowed logical matrix cell

### Positive execution
- exact execution cases and expected values are defined in TEST_MATERIALIZATION_CONTRACT.md

### Negative compile
- exactly one compile-fail test per forbidden non-boolean-producing family

---

## 5. Assignment

### Positive compile
- exactly one compile-pass test per allowed assignment matrix cell

### Positive execution
- exact execution cases and expected values are defined in TEST_MATERIALIZATION_CONTRACT.md

### Negative compile
- exactly one compile-fail test per forbidden assignment family:
  - cross-family primitive assignment
  - implicit conversions
  - direct `T -> nullable<T>`
  - pointer mismatches
  - `unique_p<T>` copy assignment

---

## 6. Compound Assignment

### Positive compile
- exactly one compile-pass test per allowed compound assignment matrix cell

### Positive execution
- exact execution cases and expected values are defined in TEST_MATERIALIZATION_CONTRACT.md

### Negative compile
- exactly one compile-fail test per forbidden compound-assignment family:
  - mixed types
  - non-numeric types

---

## 7. Conversions

### Positive compile
- exactly one compile-pass test per allowed explicit conversion edge

### Positive execution
- exact execution cases and expected values are defined in TEST_MATERIALIZATION_CONTRACT.md

### Negative compile
- exactly one compile-fail test per forbidden conversion family

---

## 8. Conditionals

### Positive compile
- exactly one compile-pass test per valid conditional expression family

### Positive execution
- exact execution cases and expected values are defined in TEST_MATERIALIZATION_CONTRACT.md

### Negative compile
- exactly one compile-fail test per forbidden direct conditional family

---

## 9. Wrappers

### Positive compile
- exactly one compile-pass test per allowed same-wrapper equality and assignment rule

### Negative compile
- exactly one compile-fail test per forbidden wrapper family:
  - arithmetic
  - relational
  - cross-wrapper
  - truthiness

---

## 10. `nullable<T>`

### Positive compile
- exactly one compile-pass test per allowed `nullable<T>` rule

### Negative compile
- exactly one compile-fail test per forbidden `nullable<T>` family:
  - arithmetic
  - relational
  - truthiness
  - conversions
  - direct `T -> nullable<T>`

---

## 11. Unknown-Type Lowering / External Symbol Cases

### Positive compile
- exactly one compile-pass test where the generator emits `auto` and the final C++ type is deducible from visible declarations and allowed operations
- exactly one compile-pass test where unresolved external references become valid once matching C++ declarations are visible at compile time

### Negative compile
- exactly one compile-fail test for unresolved external references with no visible declaration at compile time
- exactly one compile-fail test for externally declared types that lead to forbidden operations under the semantic matrix
- exactly one compile-fail test for overload resolution failure caused by incompatible declarations

---

## 12. Symmetry

Where both operand orders are allowed, both operand orders MUST be tested exactly once at compile-pass level unless a single test file is explicitly defined in TEST_MATERIALIZATION_CONTRACT.md to cover both orders.

---

## 13. Coverage Requirement

The generated suite MUST satisfy all of the following:
- every allowed operation → exactly one required compile-pass materialization
- every forbidden family/rule → exactly one required compile-fail materialization
- every required execution case → exactly one execution materialization as defined in TEST_MATERIALIZATION_CONTRACT.md

---

## Final Statement

Defines required tests.

Does not define semantics.


## TYPE INVENTORY RULE

This document MUST NOT redefine the type inventory.

All type definitions MUST be sourced from:
SPECIFICATIONS.md → TYPE_FAMILY_REGISTRY

Any duplication is considered invalid and must be removed.
