# SEMANTIC_MATRIX.md

## 1. Purpose

This document is the **single operational definition** of allowed and forbidden operations in the Simple C++ system.

It operationalizes `SPECIFICATIONS.md`.

It is intended to drive:
- generated/runtime type-layer code
- generated C++ tests
- generated PHP tests

If an operation is not explicitly listed in this document, it is **forbidden**.

This document is written to be:
- explicit
- exhaustive for the currently documented type families and operator families
- machine-derivable for implementation and testing

---

## 2. Global Conventions

### 2.1 Type families covered

Primitive semantic wrappers:
- `int_t`
- `float_t`
- `bool_t`
- `string_t`
- `null_t`

Composite / wrapper families:
- `nullable<T>`
- `shared_p<T>`
- `unique_p<T>`
- `weak_p<T>`

### 2.2 Result type convention

- arithmetic operators return a semantic numeric value type
- equality and relational comparison return native C++ `bool`
- logical operators return native C++ `bool`
- assignment returns the assigned semantic target type
- explicit conversions return the target semantic type

### 2.3 Boolean-result bridge

Native C++ `bool` produced by:
- comparison
- logical operators

is valid directly in generated control-flow lowering.

This bridge:
- is representation-only
- does not introduce general implicit conversion
- does not create truthiness for arbitrary values
- does not imply implicit assignment to `bool_t`

### 2.4 Forbidden by omission

If a cell, rule, or operator family is not explicitly marked as allowed, it is forbidden.

There are no implicit allowances based on:
- “same type”
- “numeric intuition”
- C++ defaults
- PHP behavior
- symmetry not explicitly listed

---

## 3. Arithmetic Operators (`+`, `-`, `*`, `/`)

Allowed arithmetic is **numeric-only**.

### 3.1 Binary arithmetic grid

| LHS \ RHS | `int_t` | `float_t` | `bool_t` | `string_t` | `null_t` | `nullable<T>` | `shared_p<T>` | `unique_p<T>` | `weak_p<T>` |
|---|---|---|---|---|---|---|---|---|---|
| `int_t` | ✔ `int_t` | ✔ `float_t` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `float_t` | ✔ `float_t` | ✔ `float_t` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `bool_t` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `string_t` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `null_t` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `nullable<T>` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `shared_p<T>` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `unique_p<T>` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `weak_p<T>` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |

### 3.2 Arithmetic notes

- `int_t + int_t -> int_t`
- `int_t - int_t -> int_t`
- `int_t * int_t -> int_t`
- `int_t / int_t -> int_t`

- `int_t (+,-,*,/) float_t -> float_t`
- `float_t (+,-,*,/) int_t -> float_t`
- `float_t (+,-,*,/) float_t -> float_t`

No other arithmetic combinations are allowed.

### 3.3 Unary arithmetic

| Operator | `int_t` | `float_t` | `bool_t` | `string_t` | `null_t` | `nullable<T>` | `shared_p<T>` | `unique_p<T>` | `weak_p<T>` |
|---|---|---|---|---|---|---|---|---|---|
| unary `+` | ✔ `int_t` | ✔ `float_t` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| unary `-` | ✔ `int_t` | ✔ `float_t` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |

---

## 4. Equality Operators (`==`, `!=`)

### 4.1 Equality grid

| LHS \ RHS | `int_t` | `float_t` | `bool_t` | `string_t` | `null_t` | `nullable<T>` | `shared_p<T>` | `unique_p<T>` | `weak_p<T>` |
|---|---|---|---|---|---|---|---|---|---|
| `int_t` | ✔ `bool` | ✔ `bool` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `float_t` | ✔ `bool` | ✔ `bool` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `bool_t` | ❌ | ❌ | ✔ `bool` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `string_t` | ❌ | ❌ | ❌ | ✔ `bool` | ❌ | ❌ | ❌ | ❌ | ❌ |
| `null_t` | ❌ | ❌ | ❌ | ❌ | ✔ `bool` | ✔ `bool` | ❌ | ❌ | ❌ |
| `nullable<T>` | ❌ | ❌ | ❌ | ❌ | ✔ `bool` | ✔ `bool` | ❌ | ❌ | ❌ |
| `shared_p<T>` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✔ `bool` (same wrapper family, same pointee type) | ❌ | ❌ |
| `unique_p<T>` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✔ `bool` (same wrapper family, same pointee type) | ❌ |
| `weak_p<T>` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✔ `bool` (same wrapper family, same pointee type) |

### 4.2 Equality notes

- Numeric equality is allowed only for:
  - `int_t == int_t`
  - `int_t == float_t`
  - `float_t == int_t`
  - `float_t == float_t`
- `bool_t` equality is same-family only
- `string_t` equality is same-family only
- `null_t == null_t` is allowed
- `nullable<T> == null_t` and `null_t == nullable<T>` are allowed
- `nullable<T> == nullable<T>` is allowed only for the same `T`
- Pointer-like equality is allowed only:
  - within the same wrapper family
  - with the same pointee type

All other equality comparisons are forbidden.

---

## 5. Relational Comparison Operators (`<`, `<=`, `>`, `>=`)

### 5.1 Relational grid

| LHS \ RHS | `int_t` | `float_t` | `bool_t` | `string_t` | `null_t` | `nullable<T>` | `shared_p<T>` | `unique_p<T>` | `weak_p<T>` |
|---|---|---|---|---|---|---|---|---|---|
| `int_t` | ✔ `bool` | ✔ `bool` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `float_t` | ✔ `bool` | ✔ `bool` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `bool_t` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `string_t` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `null_t` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `nullable<T>` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `shared_p<T>` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `unique_p<T>` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `weak_p<T>` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |

### 5.2 Relational notes

Relational comparison is allowed only for numeric families:
- `int_t` with `int_t`
- `int_t` with `float_t`
- `float_t` with `int_t`
- `float_t` with `float_t`

All other relational comparisons are forbidden.

---

## 6. Logical Operators (`&&`, `||`, `!`)

Logical operators are defined only over **boolean-producing expressions**.

### 6.1 Binary logical grid (`&&`, `||`)

| LHS \ RHS | `bool_t` | comparison result `bool` | logical result `bool` |
|---|---|---|---|
| `bool_t` | ✔ `bool` | ✔ `bool` | ✔ `bool` |
| comparison result `bool` | ✔ `bool` | ✔ `bool` | ✔ `bool` |
| logical result `bool` | ✔ `bool` | ✔ `bool` | ✔ `bool` |

### 6.2 Unary logical grid (`!`)

| Operand | Result |
|---|---|
| `bool_t` | ✔ `bool` |
| comparison result `bool` | ✔ `bool` |
| logical result `bool` | ✔ `bool` |

### 6.3 Logical notes

- Logical operators are not defined directly over:
  - `int_t`
  - `float_t`
  - `string_t`
  - `null_t`
  - pointer wrappers
  - `nullable<T>`
- A value of type `bool_t` is a valid semantic boolean operand.
- Comparison and logical results may participate in further logical expressions via the native-`bool` control-flow bridge.
- For public runtime API purposes, user-defined logical operators are required only where at least one operand is a semantic runtime type. Built-in `bool && bool`, `bool || bool`, and `!bool` remain host-language behavior and are not part of the runtime API surface.

---

## 7. Basic Assignment Operator (`=`)

Assignment is explicit and does not imply conversion unless the matrix says so.

### 7.1 Basic assignment grid

| Target \ Source | `int_t` | `float_t` | `bool_t` | `string_t` | `null_t` | `nullable<T>` | `shared_p<T>` | `unique_p<T>` | `weak_p<T>` |
|---|---|---|---|---|---|---|---|---|---|
| `int_t` | ✔ `int_t` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `float_t` | ❌ | ✔ `float_t` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `bool_t` | ❌ | ❌ | ✔ `bool_t` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `string_t` | ❌ | ❌ | ❌ | ✔ `string_t` | ❌ | ❌ | ❌ | ❌ | ❌ |
| `null_t` | ❌ | ❌ | ❌ | ❌ | ✔ `null_t` | ❌ | ❌ | ❌ | ❌ |
| `nullable<T>` | ✔ `nullable<T>` from same `T` wrapped nullable only where same-family rule applies | ❌ | ❌ | ❌ | ✔ `nullable<T>` | ✔ `nullable<T>` for same `T` | ❌ | ❌ | ❌ |
| `shared_p<T>` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✔ `shared_p<T>` (same pointee type) | ❌ | ❌ |
| `unique_p<T>` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✔ `unique_p<T>` (move assignment only; copy assignment forbidden) | ❌ |
| `weak_p<T>` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✔ `weak_p<T>` (same pointee type) |

### 7.2 Assignment notes

- Primitive wrappers allow same-family assignment only.
- No primitive assignment by implicit conversion is allowed.
- `nullable<T>` assignment allows:
  - assignment from `null_t`
  - assignment from `nullable<T>` with the same `T`
- Direct `T -> nullable<T>` assignment is forbidden.
- `shared_p<T>` and `weak_p<T>` assignment are same wrapper family and same pointee type only.
- `unique_p<T>` assignment is move-only within the same wrapper family and same pointee type.

---

## 8. Compound Assignment Operators (`+=`, `-=`, `*=`, `/=`)

### 8.1 Compound assignment grid

| Target \ Source | `int_t` | `float_t` | `bool_t` | `string_t` | `null_t` | `nullable<T>` | `shared_p<T>` | `unique_p<T>` | `weak_p<T>` |
|---|---|---|---|---|---|---|---|---|---|
| `int_t` | ✔ `int_t` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `float_t` | ❌ | ✔ `float_t` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `bool_t` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `string_t` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `null_t` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `nullable<T>` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `shared_p<T>` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `unique_p<T>` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `weak_p<T>` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |

### 8.2 Compound assignment notes

- `int_t` supports `+=`, `-=`, `*=`, `/=` with `int_t` only
- `float_t` supports `+=`, `-=`, `*=`, `/=` with `float_t` only
- Mixed-type compound assignment is forbidden
- Non-numeric compound assignment is forbidden

---

## 9. Explicit Conversion Grid

Only explicit conversions listed below are allowed.

### 9.1 Explicit conversion table

| From \ To | `int_t` | `float_t` | `bool_t` | `string_t` | `null_t` | `nullable<T>` | `shared_p<T>` | `unique_p<T>` | `weak_p<T>` |
|---|---|---|---|---|---|---|---|---|---|
| `int_t` | — | ✔ explicit | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `float_t` | ✔ explicit | — | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `bool_t` | ✔ explicit | ❌ | — | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `string_t` | ❌ | ❌ | ❌ | — | ❌ | ❌ | ❌ | ❌ | ❌ |
| `null_t` | ❌ | ❌ | ❌ | ❌ | — | ❌ | ❌ | ❌ | ❌ |
| `nullable<T>` | ❌ | ❌ | ❌ | ❌ | ❌ | — | ❌ | ❌ | ❌ |
| `shared_p<T>` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | — | ❌ | ❌ |
| `unique_p<T>` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | — | ❌ |
| `weak_p<T>` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | — |

### 9.2 Conversion notes

Allowed explicit conversions are exactly:
- `int_t -> float_t`
- `float_t -> int_t`
- `bool_t -> int_t`

All other conversions are forbidden.

### 9.3 Normalization note

This section defines only the existence of allowed conversion edges.

It does NOT define:
- the runtime API mechanism
- the exact constructor surface
- the exact syntax used by the generator

Those are defined in:
- RUNTIME_API_CONTRACT.md

---

## 10. Conditional Validity

This section defines whether an expression family is valid in a conditional context.

### 10.1 Valid conditional expression families

| Expression family | Valid in condition | Result / interpretation |
|---|---|---|
| `bool_t` value | ✔ | semantic boolean operand |
| comparison result (`bool`) | ✔ | native control-flow bool bridge |
| logical-expression result (`bool`) | ✔ | native control-flow bool bridge |

### 10.2 Forbidden direct conditional expression families

| Expression family | Valid in condition |
|---|---|
| `int_t` value | ❌ |
| `float_t` value | ❌ |
| `string_t` value | ❌ |
| `null_t` value | ❌ |
| `nullable<T>` value by truthiness | ❌ |
| `shared_p<T>` value by truthiness | ❌ |
| `unique_p<T>` value by truthiness | ❌ |
| `weak_p<T>` value by truthiness | ❌ |

### 10.3 Conditional notes

- No truthiness exists.
- A non-boolean semantic value cannot become a condition by host-language convention.
- Pointer wrappers and `nullable<T>` are not directly valid as conditions by value presence.

---

## 11. Pointer / Wrapper Family Summary

This section restates wrapper-family operational constraints in normalized form.

### 11.1 `shared_p<T>`

Allowed:
- equality / inequality with `shared_p<T>` of same pointee type
- assignment from `shared_p<T>` of same pointee type

Forbidden:
- arithmetic
- relational comparison
- cross-wrapper comparison
- conversion to/from numeric, string, boolean, or nullable families

### 11.2 `unique_p<T>`

Allowed:
- equality / inequality with `unique_p<T>` of same pointee type
- move assignment from `unique_p<T>` of same pointee type

Forbidden:
- arithmetic
- relational comparison
- cross-wrapper comparison
- copy assignment
- conversion to/from numeric, string, boolean, or nullable families

### 11.3 `weak_p<T>`

Allowed:
- equality / inequality with `weak_p<T>` of same pointee type
- assignment from `weak_p<T>` of same pointee type

Forbidden:
- arithmetic
- relational comparison
- cross-wrapper comparison
- conversion to/from numeric, string, boolean, or nullable families

### 11.4 `nullable<T>`

Allowed:
- equality / inequality with `null_t`
- equality / inequality with `nullable<T>` of same `T`
- assignment from `null_t`
- assignment from `nullable<T>` of same `T`

Forbidden:
- arithmetic
- relational comparison
- direct truthiness in conditions
- conversions unless explicitly added elsewhere

---

## 12. Consistency and derivation notes

### 12.1 Symmetry is explicit
Where both directions are allowed, both directions are listed explicitly in the grids.

### 12.2 No hidden closure
No operation is allowed merely because its result type participates in another operation.

### 12.3 Test derivability
This document is intended to be sufficient to derive:
- positive tests for allowed operations
- negative tests for forbidden operations

### 12.4 Runtime derivability
This document is intended to be sufficient to derive:
- explicit allowed public overloads
- absent or invalid forbidden public overloads
- explicit conversion surface
- conditional validation rules

The exact public runtime interface and exact conversion mechanism are defined in:
- RUNTIME_API_CONTRACT.md

---

## 13. Final rule

This document is the single operational source of truth for allowed and forbidden operations.

If an operation, conversion, comparison, assignment, logical form, or conditional use is not explicitly listed here as allowed, it is forbidden.


## MATRIX STRUCTURE (Normative Organization)

The semantic matrix is organized into independent operator blocks. Each block MUST be complete and self-contained.

### Blocks

1. Arithmetic
2. Equality
3. Relational
4. Logical
5. Assignment
6. Compound Assignment
7. Explicit Conversions
8. Conditionals
9. Family-Specific Rules

Rules:
- Each block MUST define full cross-family behavior.
- Absence of a rule implies forbidden operation.
- Blocks MUST NOT depend on implicit rules from other blocks.

This structure allows safe extension when adding new families.
