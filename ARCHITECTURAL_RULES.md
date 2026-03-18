# Architectural Rules

## 1. Namespace Separation
The project uses two distinct generated-code layers:

- `scpp` = platform namespace
  - runtime types
  - runtime helpers
  - future standard/platform libraries such as `scpp::io`
- `scpp_gen` = generated user program namespace
  - translated user classes
  - translated user functions
  - translated top-level wrapper

Generated code must never place translated user declarations directly into `scpp`.

## 2. Generated Program Container
All translated user code must reside inside:

    namespace scpp_gen

or, when the source language has a namespace, inside:

    namespace scpp_gen::...

For PHP this means the PHP namespace is mapped underneath `scpp_gen`.

Examples:

    <?php
    $a = 10;

becomes conceptually:

    namespace scpp_gen {
        int __scpp_main__() {
            auto a = (scpp::int_t)10;
            return 0;
        }
    }

    int main() {
        return scpp_gen::__scpp_main__();
    }

And:

    <?php
    namespace Abc;
    class X {}

becomes conceptually:

    namespace scpp_gen::Abc {
        class X {};

        int __scpp_main__() {
            return 0;
        }
    }

    int main() {
        return scpp_gen::Abc::__scpp_main__();
    }

## 3. Host Entry Point
Generated executables use a global C++ host entry point:

    int main()

This host entry point must delegate to a generated program entry function inside `scpp_gen[::source_namespace...]`.

Suggested generated function name:

    __scpp_main__

## 4. Platform Access Rule
Generated code may use only the public API exposed in `scpp`.

Generated code must access platform/runtime facilities using fully qualified names:

    scpp::int_t
    scpp::string_t
    scpp::nullable<scpp::int_t>
    scpp::create<MyClass>()
    scpp::io::fopen(...)

The project adopts **Option A — fully qualified calls** as the default rule.

`using namespace scpp;` must not be emitted by default.

## 5. Shadowing
Compilation uses `-Wshadow` to prevent accidental shadowing.

## 6. No Root Namespace Access from Generated Code
Generated user code must not access the global C++ namespace directly except through the required host `main()` shim emitted by the toolchain.

## 7. Public Runtime Boundary
Generated code may only use the public API exposed in `scpp`.

## 8. Internal Bridge Boundary
The runtime library may use `std::*` internally, but this must not leak into generated-language-visible code.

## 8A. No Native Type or Native API Use in Generated Code
Generated Simple C++ code must never contain native C++ primitive types, native standard-library types, or direct calls to native functions as part of the generated-language surface.

This includes:
- native primitives such as `int`, `double`, `bool`
- direct `std::*` types or functions
- direct use of native C++ structures/classes as generated-language-visible values

All generated-language-visible values must use the public `scpp::*` platform surface.

## 8B. Interoperability Boundary
Interoperability with native C++ code, native libraries, and native data structures belongs to C++ integration code, not to generated Simple C++ code.

Any such bridge must be written explicitly in C++ outside the generated-language semantic surface.

## 9. Explicit Wrapper Entry
All literals and values must be wrapped explicitly:

    auto x = (scpp::int_t)12;
    auto s = (scpp::string_t)"abc";

Managed object creation must also enter through the public runtime helpers:

    auto a = scpp::create<MyClass>();
    auto b = scpp::shared<MyClass>();
    auto c = scpp::unique<MyClass>();

Non-owning references are derived from existing owning values:

    auto w = scpp::weak(a);

## 10. Spec-Driven Conversions
All conversions must be explicitly defined in the spec.

## 11. No Fallback Behavior
Anything not defined must be rejected by the Simple C++ toolchain.

Rejection may occur either during S2S transformation or during generated C++ compilation,
depending on where the rule is enforced in the current implementation.

The intended long-term direction is to move source-language-visible errors earlier into
S2S diagnostics where practical.

## 12. Generated C++ Compilation Check
The S2S transformation must also perform a generated-C++ compiler check.

If the generated C++ does not compile, the Simple C++ toolchain must treat that as a failure.
