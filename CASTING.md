# Casting and Operator Rules

## 3.1 Overview

This section defines:
- allowed implicit conversions
- explicit conversion rules
- operator behavior

The goal is to maintain:
- predictability
- minimal ambiguity
- simple mental model

Function overloading is not supported.
User-defined operator overloading is not supported.

Invalid conversions are rejected at compile time. Forbidden conversions may be implemented as deleted conversions in the runtime.

Some explicit conversions may fail at runtime when input values are not valid.

The exact runtime error handling model (exceptions, error objects, or termination) will be defined in a later section.

Object comparison behavior is defined separately in `OBJECT_COMPARISON.md`.

---

## 3.2 Types Covered

- `bool`
- `int`
- `float`
- `string`
- `null_t`
- `nullable<T>`
- pointer-like types:
  - `shared_p<T>`
  - `weak_p<T>`
  - `unique_p<T>`

---

## 3.3 Implicit Conversion Matrix

Allowed implicit conversions:

| From   | To             |
|--------|----------------|
| bool   | int            |
| int    | float          |
| null_t | nullable<T>    |
| null_t | shared_p<T>    |
| null_t | weak_p<T>      |
| null_t | unique_p<T>    |

All other implicit conversions are forbidden.

---

## 3.4 Explicit Conversion Matrix

Allowed explicit conversions:

| From   | To     |
|--------|--------|
| bool   | float  |
| int    | bool   |
| float  | bool   |
| float  | int    |
| int    | string |
| float  | string |
| bool   | string |
| string | int    |
| string | float  |
| string | bool   |

Explicit conversion syntax:

    (type)expression

This syntax does not map directly to C++ casting semantics.

It is restricted to conversions explicitly allowed by the Simple C++ conversion matrix.

If the conversion is not explicitly defined:
→ compile-time error

---

## 3.5 Forbidden Conversions

The following are not allowed:

- numeric ↔ pointer-like
- string ↔ pointer-like
- object ↔ primitive (implicit)
- pointer-like ↔ primitive
- `null_t` → primitive

Forbidden conversions must fail at compile time.

---

## 3.6 `null` Behavior

- `null` has type `null_t`
- it remains `null_t` until resolved by context
- if no valid target type is known → compile-time error

Assignment from `null_t` is allowed only for:
- `nullable<T>`
- pointer-like types (`shared_p<T>`, `weak_p<T>`, `unique_p<T>`)

Examples:

    auto x = null; // error
    shared_p<MyClass> a = null; // valid
    nullable<int> b = null;     // valid

Comparison rules:

    null == null        // true
    null != null        // false

Generated-code-visible equivalences:

    null == nullptr         // true
    null != nullptr         // false
    null == std::nullopt    // true
    null != std::nullopt    // false
    nullptr == std::nullopt // true
    nullptr != std::nullopt // false

Comparison with `null` is only valid when both operands are:
- `null_t`
- `nullable<T>`
- pointer-like types

All other comparisons involving `null` are invalid.

If multiple valid target types exist, the expression is rejected as ambiguous.

---

## 3.7 Arithmetic Operators

Supported:
- `+`
- `-`
- `*`
- `/`

Rules:

| Expression    | Result |
|---------------|--------|
| int + int     | int    |
| int + float   | float  |
| float + int   | float  |
| float + float | float  |

Invalid:
- arithmetic on `bool`
- arithmetic on `null`
- arithmetic on pointer-like types

---

## 3.8 Comparison Operators

Supported:
- `==`
- `!=`
- `<`
- `<=`
- `>`
- `>=`

Rules:

| Expression        | Result |
|------------------|--------|
| int == int       | bool   |
| int == float     | bool   |
| float == float   | bool   |
| bool == bool     | bool   |
| string == string | bool   |
| null == null     | bool   |

Notes:
- numeric comparison uses standard promotion rules
- for mixed `int` / `float`, `int` is promoted to `float` before comparison
- string comparison is lexicographic
- object comparison rules are defined separately in `OBJECT_COMPARISON.md`

---

## 3.9 Logical Operators and Conditionals

Supported:
- `&&`
- `||`
- `!`

Rules:
- logical operators operate on `bool`

Conditional expressions define a special evaluation rule:
- `int` is allowed in conditionals (`0 = false`, non-zero = true)
- `null` is allowed in conditionals and evaluates to false
- this does not imply general implicit conversion to `bool`

Examples:

    if (0)      // false
    if (125)    // true
    if (null)   // false

Invalid:

    if (0.00)   // error
    if (125.88) // error

---

## 3.10 Assignment Operators

Supported:
- `=`
- `+=`
- `-=`
- `*=`
- `/=`

Rules:
- assignment requires valid implicit or explicit conversion
- explicit-only conversions must use explicit cast syntax
- compound assignment follows arithmetic rules

---

## 3.11 String Operators

Supported:

| Expression        | Result |
|------------------|--------|
| string + string  | string |
| string += string | string |

Not allowed:
- implicit numeric concatenation

---

## 3.12 Vector Behavior

- no arithmetic operators for `vector<T>`
- indexing is supported
- append is supported via:

    my_vector[] = value;

The empty index operator `[]` is treated as an append operation.

---

## 3.13 User-defined Conversions

User-defined operator overloading is not supported.

A limited set of explicit conversion methods (e.g. `toString`, `toInt`) may be allowed.

These are:
- explicit by default
- restricted in scope

---

## 3.14 Operator Precedence

Operator precedence follows standard C++ rules unless otherwise specified.

---

## 3.15 String to Bool Conversion (Detailed)

Explicit conversion required.

Allowed values:
- `"1"` → `true`
- `"0"` → `false`
- `"true"` → `true`
- `"false"` → `false`

Behavior:
- if value is known at compile time and invalid → compile-time error
- if value is not known at compile time and invalid → runtime error

---

## 3.16 Numeric Comparison Rule

For any comparison involving `int` and `float`:
- `int` is converted to `float`
- comparison is done in float domain

---

## 3.17 Enforcement Rule

All operations must match the defined matrices exactly.

If no rule exists for a given combination:
→ the expression is a compile-time error.

---

## 3.18 Code Generation Rule

Generated C++ code must construct Simple C++ runtime values explicitly.

Examples:

    auto x = (int_t)12;
    auto s = (string_t)"text";

This prevents generated code from relying on native C++ primitive semantics and ensures all values enter the Simple C++ runtime model explicitly.
