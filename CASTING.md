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

---

## 3.2 Types Covered

- bool
- int
- float
- string
- null_t
- nullable<T>
- pointer-like types:
  - shared_p<T>
  - weak_p<T>
  - unique_p<T>

---

## 3.3 Implicit Conversion Matrix

Allowed implicit conversions:

| From   | To          |
|--------|-------------|
| bool   | int         |
| bool   | float       |
| int    | float       |
| null_t | nullable<T> |
| null_t | shared_p<T> |
| null_t | weak_p<T>   |
| null_t | unique_p<T> |

All other implicit conversions are forbidden.

---

## 3.4 Explicit Conversion Matrix

Allowed explicit conversions:

| From   | To     |
|--------|--------|
| int    | bool   |
| float  | bool   |
| float  | int    |
| int    | string |
| float  | string |
| bool   | string |
| string | int    |
| string | float  |

Explicit conversion syntax is to be defined.

---

## 3.5 Forbidden Conversions

The following are not allowed:

- numeric ↔ pointer-like
- string ↔ pointer-like
- object ↔ primitive (implicit)
- pointer-like ↔ primitive
- null_t → primitive

Forbidden conversions must fail at compile time.

---

## 3.6 null Behavior

- null has type null_t
- it remains null_t until resolved by context
- if no valid target type is known → compile-time error

Assignment from null_t is allowed only for:
- nullable<T>
- pointer-like types (shared_p<T>, weak_p<T>, unique_p<T>)

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

Comparison with null is only valid when both operands are:
- null_t
- nullable<T>
- pointer-like types

All other comparisons involving null are invalid.

If multiple valid target types exist, the expression is rejected as ambiguous.

---

## 3.7 Arithmetic Operators

Supported:
- +
- -
- *
- /

Rules:

| Expression      | Result |
|-----------------|--------|
| int + int       | int    |
| int + float     | float  |
| float + int     | float  |
| float + float   | float  |

Invalid:
- arithmetic on bool
- arithmetic on null
- arithmetic on pointer-like types

---

## 3.8 Comparison Operators

Supported:
- ==
- !=
- <
- <=
- >
- >=

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
- string comparison is lexicographic
- object comparison rules to be defined later

---

## 3.9 Logical Operators and Conditionals

Supported:
- &&
- ||
- !

Rules:
- logical operators operate on bool

Conditional expressions define a special evaluation rule:
- int is allowed in conditionals (0 = false, non-zero = true)
- this does not imply a general implicit conversion from int to bool

Examples:

    if (0)      // false
    if (125)    // true

Invalid:

    if (0.00)   // error
    if (125.88) // error

Meaning:
- int is allowed in conditionals
- float is not implicitly convertible to bool

---

## 3.10 Assignment Operators

Supported:
- =
- +=
- -=
- *=
- /=

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

- no arithmetic operators for vector<T>
- indexing supported
- append supported via:

    my_vector[] = value;

The empty index operator [] is treated as an append operation.

---

## 3.13 User-defined Conversions

User-defined operator overloading is not supported.

A limited set of explicit conversion methods (e.g. toString, toInt) may be allowed.

These are:
- explicit by default
- restricted in scope

---

## 3.14 Operator Precedence

Operator precedence follows standard C++ rules unless otherwise specified.
