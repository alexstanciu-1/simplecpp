# SEMANTIC_MATRIX.md

## 1. Purpose

This matrix defines the currently documented allowed and forbidden operation families in Simple C++.

If an operation family is not explicitly listed, it is forbidden.

This document must align with:
- `SPECIFICATIONS.md`
- executable tests

---

## 2. Result Type Convention

- arithmetic returns a semantic numeric value type (`int_t` or `float_t`)
- comparison returns native C++ `bool`
- assignment returns the assigned semantic type

### 2.1 Boolean-result bridge
Native C++ `bool` produced by comparison or logical lowering is valid directly in generated control-flow contexts.

This bridge:
- is representation-only
- does not create general truthiness
- does not imply implicit conversion from arbitrary value types into `bool_t` or native `bool`

---

## 3. Arithmetic Operators

### 3.1 `int_t`

| LHS \ RHS | `int_t` | `float_t` | `bool_t` | `null_t` | `string_t` | pointer-like |
|---|---|---|---|---|---|---|
| `int_t` | ✔ `int_t` | ✔ `float_t` | ❌ | ❌ | ❌ | ❌ |

### 3.2 `float_t`

| LHS \ RHS | `int_t` | `float_t` | `bool_t` | `null_t` | `string_t` | pointer-like |
|---|---|---|---|---|---|---|
| `float_t` | ✔ `float_t` | ✔ `float_t` | ❌ | ❌ | ❌ | ❌ |

### 3.3 `bool_t`

| LHS \ RHS | any |
|---|---|
| `bool_t` | ❌ all arithmetic |

### 3.4 `string_t`

| LHS \ RHS | any |
|---|---|
| `string_t` | ❌ all arithmetic except separately documented string concatenation rules outside this core matrix |

### 3.5 `null_t`

| LHS \ RHS | any |
|---|---|
| `null_t` | ❌ all arithmetic |

### 3.6 Pointer-like families

| LHS \ RHS | any |
|---|---|
| pointer-like | ❌ all arithmetic |

---

## 4. Comparison Operators

### 4.1 Numeric

| LHS \ RHS | `int_t` | `float_t` |
|---|---|---|
| `int_t` | ✔ `bool` | ✔ `bool` |
| `float_t` | ✔ `bool` | ✔ `bool` |

### 4.2 `bool_t`

| LHS \ RHS | `bool_t` |
|---|---|
| `bool_t` | ✔ `bool` |

### 4.3 `string_t`

| LHS \ RHS | `string_t` |
|---|---|
| `string_t` | ✔ `bool` for equality / inequality only |

### 4.4 `null_t`

| LHS \ RHS | `null_t` |
|---|---|
| `null_t` | ✔ `bool` for equality / inequality only |

### 4.5 Pointer-like families

| LHS \ RHS | same wrapper family and compatible pointee discipline | other wrapper family |
|---|---|---|
| pointer-like | ✔ `bool` for `==` / `!=` only where documented | ❌ |

### 4.6 Forbidden comparison families
- pointer-like relational comparison (`<`, `<=`, `>`, `>=`)
- numeric vs `string_t`
- numeric vs `null_t`
- cross-family primitive comparison not explicitly listed
- cross-wrapper pointer comparison unless explicitly documented

---

## 5. Assignment Operators

### 5.1 Basic assignment
Basic assignment is allowed only where the semantic matrix and type rules provide a compatible target/source combination.

### 5.2 Compound assignment (`int_t`)
| Operator | Allowed |
|---|---|
| `+=` | ✔ |
| `-=` | ✔ |
| `*=` | ✔ |
| `/=` | ✔ |

### 5.3 Compound assignment (`float_t`)
| Operator | Allowed |
|---|---|
| `+=` | ✔ |
| `-=` | ✔ |
| `*=` | ✔ |
| `/=` | ✔ |

Compound assignment follows the same family restrictions as the underlying arithmetic.

---

## 6. Conversion Matrix

| From \ To | `int_t` | `float_t` | `bool_t` | `string_t` | `null_t` | pointer-like |
|---|---|---|---|---|---|---|
| `int_t` | — | ✔ explicit where documented | ❌ | ❌ | ❌ | ❌ |
| `float_t` | ✔ explicit via `to_int(...)` | — | ❌ | ❌ | ❌ | ❌ |
| `bool_t` | ✔ explicit | ❌ | — | ❌ | ❌ | ❌ |
| `string_t` | ❌ | ❌ | ❌ | — | ❌ | ❌ |
| `null_t` | ❌ | ❌ | ❌ | ❌ | — | ❌ |
| pointer-like | ❌ | ❌ | ❌ | ❌ | ❌ | — |

---

## 7. Conditional Usage

### 7.1 Directly valid conditions

| Expression family | Allowed in condition | Notes |
|---|---|---|
| `bool_t` value | ✔ | direct semantic-boolean value |
| comparison result (`bool`) | ✔ | native lowered bool bridge only |
| logical-expression result (`bool` or semantic-boolean equivalent) | ✔ | operands must already be semantic-boolean |

### 7.2 Forbidden direct conditions

| Expression family | Allowed in condition |
|---|---|
| `int_t` value | ❌ |
| `float_t` value | ❌ |
| `string_t` value | ❌ |
| `null_t` value | ❌ |
| pointer-like value by source-language truthiness | ❌ |
| `nullable<T>` value by source-language truthiness | ❌ |

### 7.3 Runtime-only contextual-bool note
Selected runtime wrapper families may expose tightly scoped contextual-bool support for generated/runtime code.

That support:
- is not source-language truthiness
- does not imply implicit assignment to `bool_t`
- does not broaden the general conversion matrix

---

## 8. Enforcement Notes

- forbidden means the program must not compile
- explicit conversion remains explicit even if implemented by a helper
- missing operator coverage does not imply permissiveness; it implies forbidden behavior

---

## 9. Completeness Rule

Every valid operation family should:
- appear in this matrix
- be backed by tests when observable
- be implemented or rejected by the toolchain
