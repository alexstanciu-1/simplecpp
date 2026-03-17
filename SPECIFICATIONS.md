# Simple C++ — Specifications

## 1. Overview

This document defines the core language model for Simple C++.

Simple C++ is a C++-inspired intermediate language designed to be:
- simple
- predictable
- partially memory-safe, aiming to improve over time
- suitable for source-to-source (S2S) compilation

More specific behavioral rules are defined in:
- `CASTING.md` — conversion, operators, and conditionals
- `OBJECT_COMPARISON.md` — object comparison semantics

If conflicts arise, these specialized documents take precedence for their respective domains.

---

## 2. Type System

### 2.1 Primitive Types

The following primitive types are supported:

- `bool`
  - Values: `true`, `false`
  - Backed by: `scpp::bool_t`

- `int`
  - 8-byte signed integer
  - Backed by: `scpp::int_t` (`long long`)

- `float`
  - 8-byte floating point
  - Backed by: `scpp::float_t` (`double`)

Primitive types:
- are value types
- are not heap-allocated
- are passed by value
- are returned by value

---

### 2.2 Standard Wrapped Types

The following wrapped standard types are provided:

- `string`
  - Wrapper over `std::string`
  - Runtime type: `scpp::string_t`

- `vector<T>`
  - Wrapper over `std::vector<T>`
  - Runtime type: `scpp::vector_t<T>`

Where:
- `T` must be a valid Simple C++ type or a user-defined Simple C++ type

By default:
- `string` and `vector<T>` are passed by `const &`
- `string` and `vector<T>` are used to avoid unnecessary copying

Additional wrapped types may be added later.

---

### 2.3 Composite Types

#### `null_t`

Simple C++ defines a universal null type.

Definition:

    inline constexpr null_t null{};

    struct null_t {
        constexpr operator std::nullptr_t() const { return nullptr; }
        constexpr operator std::nullopt_t() const { return std::nullopt; }
    };

Behavior:
- represents both pointer-null and optional-empty
- is compatible with `nullptr` and `std::nullopt` in generated code
- does not implicitly convert to primitive types
- resolves only through contextual typing

`null_t` is intended to remain abstract at the language level and be resolved only where context provides a valid target type.

---

### 2.4 Pointer-like Types

Pointer-like types are managed runtime types provided by the language runtime:

- `shared_p<T>` → shared ownership
- `weak_p<T>` → non-owning reference
- `unique_p<T>` → exclusive ownership

Characteristics:
- no raw pointers are exposed to the user
- all pointer-like allocations are managed
- pointer semantics are not user-visible
- comparison behavior is defined in `OBJECT_COMPARISON.md`

---

### 2.5 Nullable Types

- `nullable<T>` → wrapper over `std::optional<T>`

Behavior:
- can hold either a value or `null`
- integrates with `null_t`
- comparison rules are defined in `OBJECT_COMPARISON.md`

---

## 3. Ownership Model

### 3.1 Default Behavior

- Objects are allocated using managed ownership
- Shared ownership is the default object model
- Direct use of raw pointers is not allowed

At the source level:

    auto x = new MyClass();

Generated form:

    auto x = create<MyClass>();

The `new` keyword in source languages is a semantic construct.

It does not correspond to C++ raw allocation.

All object creation is translated into runtime-managed allocation via:

    create<T>()

The generated form must not expose raw C++ allocation semantics.

---

### 3.2 Weak References

- `weak_p<T>` may be used to avoid cycles
- Cycles are not automatically prevented
- Cycles must currently be handled manually

Notes:
- shared ownership does **not** prevent cycles
- `weak_ptr`-like behavior must be used correctly where needed
- full safety remains to be proven over time

---

### 3.3 Allocation Rules

- Allocation is triggered through managed creation helpers such as:

    create<T>()

- Source-level `new` is allowed
- Source-level `new` is translated into managed allocation
- No user-visible raw allocation semantics exist

---

### 3.4 Stack Values

- Stack allocation is not allowed by default for object-like managed values
- Explicit stack allocation may be allowed in future versions

Primitive values remain value types and are not heap-allocated.

---

## 4. Passing Rules

### 4.1 Primitive Types

- passed by value
- returned by value

---

### 4.2 String and Vector

- `string`
- `vector<T>`

are passed by `const &` by default to avoid copying.

---

### 4.3 Other Types

Other non-primitive values should be wrapped in:
- `shared_p<T>`
- `unique_p<T>`
- `weak_p<T>`

They are passed by value with pointer-like semantics.

---

### 4.4 Explicit References

- Explicit pass-by-reference may be allowed
- Explicit return-by-reference may be allowed

Detailed function and reference rules may be defined separately.

---

## 5. Type Definitions

Types are implemented as wrappers in the `scpp` namespace.

Example:

    namespace scpp {
        class int_t {
        private:
            long long value;
        };
    }

Mapped runtime types:

- `scpp::bool_t` → `bool`
- `scpp::int_t` → `long long`
- `scpp::float_t` → `double`
- `scpp::string_t` → `std::string`
- `scpp::vector_t<T>` → `std::vector<T>`
- `scpp::nullable<T>` → `std::optional<T>`
- `scpp::shared_p<T>` → `std::shared_ptr<T>`
- `scpp::weak_p<T>` → `std::weak_ptr<T>`
- `scpp::unique_p<T>` → `std::unique_ptr<T>`

Notes:
- runtime types in the `scpp` namespace are not user-facing
- runtime types are generated by the transpiler
- all user-visible values must conform to the internal Simple C++ type system

---

## 6. Conversion and Operator Model

Casting and operator behavior is defined in `CASTING.md`.

Key principles:
- implicit conversions are limited
- explicit conversions use:

    (type)expression

- invalid conversions are compile-time errors
- forbidden conversions may be implemented as deleted conversions in the runtime
- conditionals follow special evaluation rules and do not imply general implicit conversions

---

## 7. Object Model

Object comparison behavior is defined in `OBJECT_COMPARISON.md`.

Key principles:
- objects compare by identity
- nullable values compare by contained value when present
- pointer-like types compare by identity or null state
- relational ordering for object-like values is not defined by default

---

## 8. Error Model

To be defined later.

This includes:
- runtime conversion failures
- invalid explicit conversions where runtime values are involved
- possible exception or error-object policy

---

## 9. Notes

- Each source language defines its own AST and transcoder
- A unified intermediate representation is not currently used
- The majority of AST traversal and transformation logic is language-specific

---

## 10. Related Specifications

- `CASTING.md`
- `OBJECT_COMPARISON.md`
