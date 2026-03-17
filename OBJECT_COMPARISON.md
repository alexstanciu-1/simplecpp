# Object Comparison Rules

## 1. Core Principle

Objects are not compared by structural value by default.

All object-like managed types are compared by:
- identity (same underlying instance)
- or null/empty state

This avoids hidden costs and unclear semantics.

---

## 2. Equality and Inequality

Supported operators:
- `==`
- `!=`

### 2.1 `shared_p<T>`

- `shared_p<T> == shared_p<T>` → true if both refer to the same underlying object
- `shared_p<T> != shared_p<T>` → inverse

### 2.2 `unique_p<T>`

- `unique_p<T> == unique_p<T>` → true if both refer to the same underlying object
- `unique_p<T> != unique_p<T>` → inverse

### 2.3 `weak_p<T>`

Rules:
- if both are expired or empty → equal
- if one is expired/empty and the other is not → not equal
- if both resolve to live objects → compare resolved identity

Expired weak references are treated as `null`.

---

## 3. Object vs `null`

Allowed:
- `object == null`
- `object != null`

Meaning:
- checks whether the object reference is empty or null

Applies to:
- `shared_p<T>`
- `unique_p<T>`
- `weak_p<T>` (expired is considered `null`)

---

## 4. Cross-type Object Comparison

Comparing different object types is forbidden.

Example:

    shared_p<A> == shared_p<B> // error

Unless explicitly defined in future extensions.

---

## 5. Relational Operators

Forbidden for object-like types:
- `<`
- `<=`
- `>`
- `>=`

Reason:
- no meaningful default ordering
- pointer address ordering is not exposed
- structural ordering is not defined

---

## 6. Nullable Types

### 6.1 Equality

- `nullable<T> == nullable<T>`:
  - both null → true
  - one null → false
  - both non-null → compare contained values using `T` rules

### 6.2 Inequality

- inverse of equality

### 6.3 Relational Operators

Allowed only if:
- `T` supports relational operators
- both values are non-null

Otherwise:
- comparison is invalid

Example:

    nullable<int>(5) < nullable<int>(7) // valid
    nullable<int>(null) < nullable<int>(7) // error

---

## 7. Comparison Matrix

### Equality / Inequality

| Left           | Right          | Rule |
|----------------|---------------|------|
| shared_p<T>    | shared_p<T>   | identity |
| unique_p<T>    | unique_p<T>   | identity |
| weak_p<T>      | weak_p<T>     | resolved identity / expired treated as null |
| shared_p<T>    | null          | empty/null check |
| unique_p<T>    | null          | empty/null check |
| weak_p<T>      | null          | empty/expired check |
| nullable<T>    | null          | no-value check |
| nullable<T>    | nullable<T>   | value compare if non-null |

### Relational

| Left           | Right          | Rule |
|----------------|---------------|------|
| object-like    | object-like   | forbidden |
| nullable<T>    | nullable<T>   | allowed only if T supports and both non-null |
| object-like    | null          | forbidden |

---

## 8. User-defined Comparison

User-defined operator overloading is not supported.

Custom comparison behavior is not available in v1.

Future extensions may allow:
- explicit comparison methods (e.g. `equals()`)

---

## 9. Notes

- Object comparison is intentionally simple and identity-based
- No deep equality is performed by default
- Nullable types provide value-based comparison when applicable
- Weak references treat expired values as null in comparison contexts
