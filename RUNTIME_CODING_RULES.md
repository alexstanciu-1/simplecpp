# Runtime Coding Rules

## 1. Semantic Wrappers
All types (int_t, string_t, etc.) represent language semantics, not C++ primitives.

## 2. Minimal Implicit Conversions
Avoid implicit conversion operators that allow leakage into native C++.

## 3. Explicit Conversions
Use explicit constructors or helpers for conversions defined as explicit.

## 4. Operator Control
Operators must be implemented directly on wrapper types or as scpp functions.

## 5. Small Public API
Expose only necessary constructs to generated code, including only the managed creation helpers required by the spec (`create<T>()`, `shared<T>()`, `unique<T>()`, and `weak(x)`).

## 6. Internal Accessors
Allow internal access for runtime implementation, not for generated code.

## 7. Deleted Invalid Paths
Use =delete to block invalid operations.

## 8. Runtime Validation
Perform runtime validation where compile-time checks are not possible.

## 9. Modular Headers
Each type/module should be isolated in its own header.


## Hardening Note
The runtime now prefers explicit deleted overloads, deleted constructors, and constrained templates/concepts for unsupported or type-dependent paths so forbidden operations fail deterministically at compile time where practical.


## 10. Contextual Bool Discipline
Where the runtime intentionally exposes boolean context for wrappers such as `nullable<T>` or pointer-like wrappers, it should do so via `explicit operator bool()` only. This support is for generated/runtime mechanics, not for source-language truthiness, and it must not reopen broad implicit conversion paths or implicit assignment into `bool_t` / native `bool`.

## 11. Constrained Templates and Concepts
Use constrained templates/concepts where an operation should exist only when the wrapped type supports it. This is preferred over accidental template instantiation or late runtime failure.
