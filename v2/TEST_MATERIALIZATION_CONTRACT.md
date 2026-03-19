# TEST_MATERIALIZATION_CONTRACT.md

## 1. Scope

This document is **normative**.

It defines the **canonical materialization rules** for generating the full test suite from:
- SPECIFICATIONS.md
- TYPE_FAMILY_REGISTRY.md
- DERIVATION_RULES.md
- SEMANTIC_MATRIX.md
- TEST_MATRIX.md

It specifies:
- the exact finite instantiation model for template families
- the exact execution value corpus
- the exact compile-pass / compile-fail materialization rules
- the exact file and naming conventions for generated tests

It does NOT define semantics.

---

## 2. Authority Relationship

1. SPECIFICATIONS.md — semantic core
2. TYPE_FAMILY_REGISTRY.md — canonical family inventory and traits
3. DERIVATION_RULES.md — deterministic semantic derivation
4. SEMANTIC_MATRIX.md — allowed/forbidden operations
5. TEST_MATRIX.md — required validation surface
6. TEST_MATERIALIZATION_CONTRACT.md — exact materialization rules

---

## 3. Type Inventory Rule

This document MUST NOT redefine the type inventory independently.

The canonical inventory source is:
- TYPE_FAMILY_REGISTRY.md

This document defines finite test instantiation representatives only.

---

## 4. Finite Template Instantiation Model

To ensure deterministic and generator-safe test materialization, template families MUST be instantiated using a finite canonical representative set.

### 4.1 Canonical primary semantic representatives

- `T1 = scpp::int_t`
- `T2 = scpp::float_t`

These representatives are used because:
- they exercise the numeric primitive families
- they provide a deterministic same-`T` and different-`T` mismatch pair
- they avoid unbounded combinatorics

### 4.2 Unary template-family materialization rule

For every unary template family registered in TYPE_FAMILY_REGISTRY.md, the generated suite MUST materialize exactly:

- `<T1>`
- `<T2>`

For the current language version, this yields:
- `scpp::nullable<scpp::int_t>`
- `scpp::nullable<scpp::float_t>`
- `scpp::shared_p<scpp::int_t>`
- `scpp::shared_p<scpp::float_t>`
- `scpp::unique_p<scpp::int_t>`
- `scpp::unique_p<scpp::float_t>`
- `scpp::weak_p<scpp::int_t>`
- `scpp::weak_p<scpp::float_t>`

### 4.3 Unary template-family same-`T` positive rule

Positive tests that require unary template-family compatibility MUST use:
- `<T1>` with `<T1>`
- `<T2>` with `<T2>`

### 4.4 Unary template-family mismatch rule

Negative tests that require unary template-family incompatibility MUST use:
- `<T1>` with `<T2>`

### 4.5 Binary template-family materialization rule

For any future binary template family registered in TYPE_FAMILY_REGISTRY.md, the generated suite MUST materialize exactly these canonical pairs:

- `<T1, T1>`
- `<T1, T2>`

Positive same-parameter tests MUST use:
- `<T1, T1>` with `<T1, T1>`

Negative mismatch tests MUST use at least one of:
- `<T1, T2>` with `<T1, T1>`
- `<T1, T2>` with `<T2, T1>`

This rule exists so future families such as `map_t<K, V>` can be integrated without redesigning test generation.

---

## 5. Execution Value Corpus

The execution suite MUST use exactly the following canonical values.

### 5.1 `int_t` values
- `0`
- `1`
- `-1`
- `2`

### 5.2 `float_t` values
- `0.0`
- `1.5`
- `-1.5`
- `2.0`

### 5.3 `bool_t` values
- `true`
- `false`

### 5.4 `string_t` values
- `"a"`
- `"b"`

---

## 6. Materialization Strategy

For each required test surface category from TEST_MATRIX.md, materialization MUST proceed as follows:

1. enumerate the allowed matrix cases for the current operator family
2. emit compile-pass tests for every allowed current-family case
3. emit compile-fail tests for representative forbidden categories
4. emit execution assertions where runtime-observable behavior exists
5. include template same-`T`, different-`T`, and `null_t` special-case coverage where applicable

---

## 7. File Naming Rule

Generated tests MUST encode:
- operator family
- positive or negative status
- representative family pattern
- special-case marker where needed

Naming must remain deterministic and machine-derivable.

---

## Final Statement

This document defines the finite, deterministic test materialization model required for generator-safe validation.
