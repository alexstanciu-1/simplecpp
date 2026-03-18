# Architectural Rules

## 1. Namespace Containment
All generated code must reside inside:
    namespace scpp

## 2. Shadowing
Compilation uses -Wshadow to prevent accidental shadowing.

## 3. No Root Namespace Access
Generated code must not access the global C++ namespace directly.

## 4. Namespace Fixing
The S2S compiler must ensure all references are qualified to remain within `scpp`.

## 5. Public Runtime Boundary
Generated code may only use the public API exposed in `scpp`.

## 6. Internal Bridge Boundary
The runtime library may use std::* internally, but this must not leak into generated code.

## 7. Explicit Wrapper Entry
All literals and values must be wrapped explicitly:

    auto x = (int_t)12;
    auto s = (string_t)"abc";

Managed object creation must also enter through the public runtime helpers:

    auto a = create<MyClass>();
    auto b = shared<MyClass>();
    auto c = unique<MyClass>();

Non-owning references are derived from existing owning values:

    auto w = weak(a);

## 8. Spec-Driven Conversions
All conversions must be explicitly defined in the spec.

## 9. No Fallback Behavior
Anything not defined must fail at S2S transformation time.
