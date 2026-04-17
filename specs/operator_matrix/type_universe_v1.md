# Operator / Cast / Language Semantics Matrix
## v1 Type Universe

---

## 1. Purpose

This document defines the **canonical type universe** used by the operator matrix.

It provides:
- the base type set
- wrapper types
- expansion rules into runtime profiles

This is required for:
- matrix generation
- edge-case coverage
- automated test synthesis

---

## 2. Base Types (Scalar)

### int_t
### float_t
### bool_t
### string_t

---

## 3. Compound Types

### hash_t
- associative / array-like container

---

## 4. Wrapper Types

### nullable<T>
Represents:
- empty (null)
- present(T)

### mixed_t
Runtime-tagged union.

Allowed kinds:
- null
- bool
- int
- float
- string
- hash

### result<T>
Represents:
- success(T)
- failure(exception)

### result_or_false<T>
Represents:
- success(T)
- false

### result_or_bool<T>
Represents:
- success(T)
- bool

---

## 5. Profile Expansion Rules

Each type must expand into runtime profiles.

---

## 6. Scalar Profiles

### int_t
- int.zero
- int.nonzero

### float_t
- float.zero
- float.nonzero

### bool_t
- bool.false
- bool.true

### string_t
- string.empty
- string.zero_string
- string.nonempty_nonzero

---

## 7. hash_t Profiles

- hash.empty
- hash.nonempty

---

## 8. nullable<T> Expansion

- nullable.empty
- nullable.present.<T_profile>

---

## 9. mixed_t Expansion

- mixed.null
- mixed.bool.false
- mixed.bool.true
- mixed.int.zero
- mixed.int.nonzero
- mixed.float.zero
- mixed.float.nonzero
- mixed.string.empty
- mixed.string.zero_string
- mixed.string.nonempty_nonzero
- mixed.hash.empty
- mixed.hash.nonempty

---

## 10. result* Expansion

### result<T>
- result.success.<T_profile>
- result.failure

### result_or_false<T>
- result.success.<T_profile>
- result.false

### result_or_bool<T>
- result.success.<T_profile>
- result.bool.false
- result.bool.true

---

## 11. Notes

- All operators must resolve against profiles, not just types.
- Profiles are required for correctness in condition, casting, and comparison.
- Wrapper expansion is recursive.
