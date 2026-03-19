# SEMANTIC_MATRIX.md

## 1. Purpose

This document is the **normalized derived operational definition** of allowed and forbidden operations in the Simple C++ system.

It operationalizes:
- SPECIFICATIONS.md
- TYPE_FAMILY_REGISTRY.md
- DERIVATION_RULES.md

It is intended to drive:
- generated/runtime type-layer code
- generated C++ tests
- generated PHP tests

If an operation is not explicitly listed in this document as allowed, it is **forbidden**.

This document is written to be:
- explicit
- exhaustive for the currently registered type families and operator families
- machine-derivable from the compact source model
- generator-facing
- audit-friendly

---

## 2. Relationship to the Compact Source Model

This document is Layer 2 of the two-layer semantic model.

Layer 1 is:
- TYPE_FAMILY_REGISTRY.md
- DERIVATION_RULES.md

Layer 2 is:
- SEMANTIC_MATRIX.md

This matrix is not an optional summary.
It is the required normalized operational artifact used for deterministic generation.

---

## 3. Global Conventions

### 3.1 Current operational family set

The currently covered families are exactly those registered in TYPE_FAMILY_REGISTRY.md.

For the current language version, the normalized matrix resolves operations for:

Primitive semantic wrappers:
- `int_t`
- `float_t`
- `bool_t`
- `string_t`

Literal-like family:
- `null_t`

Unary template families:
- `nullable<T>`
- `shared_p<T>`
- `unique_p<T>`
- `weak_p<T>`

This local enumeration exists only so the current normalized matrix can name the concrete family set it resolves.
TYPE_FAMILY_REGISTRY.md remains the canonical inventory source.

### 3.2 Result type convention

- arithmetic operators return a semantic numeric value type
- equality and relational comparison return native C++ `bool`
- logical operators return native C++ `bool`
- assignment returns the assigned semantic target type
- explicit conversions return the target semantic type

### 3.3 Boolean-result bridge

Native C++ `bool` produced by:
- comparison
- logical operators

is valid directly in generated control-flow lowering.

This bridge:
- is representation-only
- does not introduce general implicit conversion
- does not create truthiness for arbitrary values
- does not imply implicit assignment to `bool_t`

### 3.4 Forbidden by omission

If a cell, rule, or operator family is not explicitly marked as allowed, it is forbidden.

There are no implicit allowances based on:
- “same type”
- “numeric intuition”
- C++ defaults
- PHP behavior
- symmetry not explicitly listed
- trait membership alone

---

## 4. Arithmetic Operators (`+`, `-`, `*`, `/`)

Allowed arithmetic is **numeric-only**.

### 4.1 Binary arithmetic grid

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

### 4.2 Unary arithmetic

Allowed:
- unary `+int_t` -> `int_t`
- unary `-int_t` -> `int_t`
- unary `+float_t` -> `float_t`
- unary `-float_t` -> `float_t`

All other unary arithmetic is forbidden.

---

## 5. Equality Operators (`==`, `!=`)

### 5.1 Equality grid

| LHS \ RHS | `int_t` | `float_t` | `bool_t` | `string_t` | `null_t` | `nullable<T>` | `shared_p<T>` | `unique_p<T>` | `weak_p<T>` |
|---|---|---|---|---|---|---|---|---|---|
| `int_t` | ✔ `bool` | ✔ `bool` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `float_t` | ✔ `bool` | ✔ `bool` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `bool_t` | ❌ | ❌ | ✔ `bool` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `string_t` | ❌ | ❌ | ❌ | ✔ `bool` | ❌ | ❌ | ❌ | ❌ | ❌ |
| `null_t` | ❌ | ❌ | ❌ | ❌ | ✔ `bool` | ✔ `bool` | ❌ | ❌ | ❌ |
| `nullable<T>` | ❌ | ❌ | ❌ | ❌ | ✔ `bool` | ✔ `bool` (same-`T` only) | ❌ | ❌ | ❌ |
| `shared_p<T>` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✔ `bool` (same-`T` only) | ❌ | ❌ |
| `unique_p<T>` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✔ `bool` (same-`T` only) | ❌ |
| `weak_p<T>` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✔ `bool` (same-`T` only) |

Notes:
- `nullable<T>` equality is allowed only for same-`T`
- `nullable<T>` and `null_t` equality is allowed in both directions
- pointer families compare only with the same outer family and same-`T`
- no cross-pointer-family equality exists

---

## 6. Relational Operators (`<`, `<=`, `>`, `>=`)

Relational comparison is numeric-only.

### 6.1 Relational grid

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

---

## 7. Logical Operators (`!`, `&&`, `||`)

Logical operators are `bool_t`-only.

### 7.1 Unary logical

Allowed:
- `!bool_t` -> `bool`

All other unary logical uses are forbidden.

### 7.2 Binary logical grid

| LHS \ RHS | `int_t` | `float_t` | `bool_t` | `string_t` | `null_t` | `nullable<T>` | `shared_p<T>` | `unique_p<T>` | `weak_p<T>` |
|---|---|---|---|---|---|---|---|---|---|
| `int_t` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `float_t` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `bool_t` | ❌ | ❌ | ✔ `bool` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `string_t` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `null_t` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `nullable<T>` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `shared_p<T>` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `unique_p<T>` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `weak_p<T>` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |

---

## 8. Assignment (`=`)

### 8.1 Assignment grid

| LHS \ RHS | `int_t` | `float_t` | `bool_t` | `string_t` | `null_t` | `nullable<T>` | `shared_p<T>` | `unique_p<T>` | `weak_p<T>` |
|---|---|---|---|---|---|---|---|---|---|
| `int_t` | ✔ `int_t` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `float_t` | ❌ | ✔ `float_t` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `bool_t` | ❌ | ❌ | ✔ `bool_t` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `string_t` | ❌ | ❌ | ❌ | ✔ `string_t` | ❌ | ❌ | ❌ | ❌ | ❌ |
| `null_t` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `nullable<T>` | ❌ | ❌ | ❌ | ❌ | ✔ `nullable<T>` | ✔ `nullable<T>` (same-`T` only) | ❌ | ❌ | ❌ |
| `shared_p<T>` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✔ `shared_p<T>` (same-`T` only) | ❌ | ❌ |
| `unique_p<T>` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✔ `unique_p<T>` (same-`T` only) | ❌ |
| `weak_p<T>` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✔ `weak_p<T>` (same-`T` only) |

Notes:
- `nullable<T> = null_t` is allowed
- `null_t` is not itself assignable as a destination
- no pointer cross-family assignment exists

---

## 9. Compound Assignment (`+=`, `-=`, `*=`, `/=`)

Compound assignment is permitted only where the corresponding arithmetic result type matches the left-hand-side type exactly.

### 9.1 Compound assignment grid

#### `+=`

| LHS \ RHS | `int_t` | `float_t` | other |
|---|---|---|---|
| `int_t` | ✔ `int_t` | ❌ | ❌ |
| `float_t` | ✔ `float_t` | ✔ `float_t` | ❌ |
| other | ❌ | ❌ | ❌ |

#### `-=`

| LHS \ RHS | `int_t` | `float_t` | other |
|---|---|---|---|
| `int_t` | ✔ `int_t` | ❌ | ❌ |
| `float_t` | ✔ `float_t` | ✔ `float_t` | ❌ |
| other | ❌ | ❌ | ❌ |

#### `*=`

| LHS \ RHS | `int_t` | `float_t` | other |
|---|---|---|---|
| `int_t` | ✔ `int_t` | ❌ | ❌ |
| `float_t` | ✔ `float_t` | ✔ `float_t` | ❌ |
| other | ❌ | ❌ | ❌ |

#### `/=`

| LHS \ RHS | `int_t` | `float_t` | other |
|---|---|---|---|
| `int_t` | ✔ `int_t` | ❌ | ❌ |
| `float_t` | ✔ `float_t` | ✔ `float_t` | ❌ |
| other | ❌ | ❌ | ❌ |

---

## 10. Explicit Conversions

Explicit conversions are represented in this normalized matrix as a **finite resolved edge list** rather than as a full grid.

This representation is intentional.

Reason:
- the current conversion surface is sparse
- the generator requires explicit authorized source/target edges
- a finite edge list is the normalized operational form for this operator family in the current language version

### 10.1 Allowed explicit conversion edges

- `int_t -> float_t`
- `float_t -> int_t`

### 10.2 Forbidden explicit conversion rule

Every explicit conversion edge not listed in section 10.1 is forbidden.

This includes, in the current language version:
- all conversions involving `bool_t`, unless added explicitly in a future revision
- all conversions involving `string_t`, unless added explicitly in a future revision
- all conversions involving `null_t`, unless added explicitly in a future revision
- all conversions involving `nullable<T>`, unless added explicitly in a future revision
- all conversions involving pointer-like families, unless added explicitly in a future revision

---

## 11. Conditionals

Conditionally valid operands are:

- `bool_t`
- native `bool` produced by allowed equality comparisons
- native `bool` produced by allowed relational comparisons
- native `bool` produced by allowed logical operations

All other semantic values are conditionally invalid.

---

## 12. Family-Specific Rules

### 12.1 `null_t`

- `null_t` has no arithmetic participation
- `null_t` has no relational participation
- `null_t` has no logical participation
- `null_t` is conditionally invalid
- `null_t` participates in equality only where explicitly listed
- `null_t` participates in assignment only where explicitly listed

### 12.2 `nullable<T>`

- `nullable<T>` does not inherit arithmetic from `T`
- `nullable<T>` does not inherit relational comparison from `T`
- `nullable<T>` does not inherit logical participation from `T`
- `nullable<T>` is conditionally invalid
- `nullable<T>` equality and assignment are same-`T` constrained except for explicit `null_t` interaction

### 12.3 Pointer families

For each of:
- `shared_p<T>`
- `unique_p<T>`
- `weak_p<T>`

the following hold:
- no arithmetic participation
- no relational participation
- no logical participation
- no conditional participation
- equality is same outer family and same-`T` only
- assignment is same outer family and same-`T` only

---

## Final Statement

This document is the normalized derived operational matrix.

TYPE_FAMILY_REGISTRY.md and DERIVATION_RULES.md exist to make this matrix easier to evolve without sacrificing deterministic generation.
