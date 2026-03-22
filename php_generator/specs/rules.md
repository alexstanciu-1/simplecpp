# Simple C++ – General Rules (Authoritative, Normalized)

This document is the single source of truth for the supported subset.

---

## 0. Generator Responsibility Boundary

The S2S generator is a deterministic structured code generator, not a semantic compiler.

It performs only the checks required to emit configured C++ output reliably. Symbol resolution, type validation, inheritance validation, override validation, and other semantic compile-time checks are delegated to the C++ compiler unless a generation rule explicitly requires a local structural check.

The generator must prefer deterministic syntactic lowering over semantic interpretation. If a supported source form can be lowered locally, it should be emitted. If the resulting C++ is semantically invalid, that failure belongs to the C++ compiler unless the generation rules state otherwise.

---

## 1. Runtime Contract

All generated code targets the `scpp` runtime.

Object construction and ownership helpers are runtime concepts. Current generation rules use `create<T>(...)` for user PHP class construction, while explicit runtime forms such as `shared(new MyClass)`, `weak($object)`, and `unique(new MyClass)` remain runtime-level constructs when they are later brought into the supported subset.

### Core Types
- `int_t`
- `float_t`
- `bool_t`
- `string_t`
- `nullable<T>`
- `shared_p<T>`
- `vector_t`
- runtime `null` / `nullopt` support via the runtime helpers

### Rules
- `string_t` uses constructor form, not `static_cast`
- `nullable<T>` is the null carrier for nullable value types
- object/class/interface handle types use `shared_p<T>` and are inherently nullable
- runtime `null` is the canonical null literal for generated code where null is supported
- null comparisons/checks must use the configured runtime helpers such as `php::is_null(...)` and `php::not_null(...)`

---

## 2. Type System

### Mandatory
- parameters must be typed
- return types must be explicit

### Mapping
- `int` → `int_t`
- `float` → `float_t`
- `bool` → `bool_t`
- `string` → `string_t`
- `?T` → `nullable<T>` for value-like types
- class / interface / abstract object types → `shared_p<T>`
- `?ClassType` / `?InterfaceType` / `?AbstractType` → `shared_p<T>`
- object nullability does not currently change the emitted C++ type; `A` and `?A` both emit `shared_p<A>` for now

### Returns
- non-void functions must return a value on all paths
- void functions cannot return a value


### Variable Typing
- explicit PHPDoc variable types are authoritative when present
- explicit local variable typing currently comes from PHPDoc variable annotations
- only the strict immediate-after-variable form is supported for explicit local variable typing
- valid form example: `$x /** string */ = "test";`
- invalid forms include placing the type comment before the variable, after the initializer, or detached from the variable token
- `$x /** string */ = "test";` → `string_t x("test");`
- `$x /** ?string */ = "test";` → `nullable<string_t> x("test");`
- `$x /** ?string */ = null;` → `nullable<string_t> x = null;`
- `$x /** A */ = new A();` → `shared_p<A> x = create<A>();`
- `$x /** ?A */ = null;` → `shared_p<A> x = null;`

### Untyped Variable Initialization
- untyped variables may still lower to explicit runtime-wrapped expressions
- `$x = "test";` → `auto x = string_t("test");`
- constructor selection, conversion resolution, and overload resolution remain the C++ compiler's responsibility

### Passing and Return Conventions
- `int_t`, `float_t`, and `bool_t` use normal value semantics for parameters and returns unless explicit `&` is present
- `string_t` and `vector_t` default to `const &` for parameters and return by value
- explicit PHP `&` disables the default `const &` convention and must be emitted as a mutable reference
- class/interface/abstract object types are emitted as `shared_p<T>` handles and are passed and returned by handle value
- object nullability intent (`T` vs `?T`) does not currently change the emitted object-handle type
- class/interface object types remain pointer-like in use (`->`)
- user PHP classes must not be stored by value in generated code
- runtime nullability enforcement for non-nullable object parameters/properties is deferred; current code generation keeps `T` and `?T` identical for object-handle types and relies on future injected checks

---

## 3. Literal Normalization

All literals must be normalized.

### Required forms
- integer → `static_cast<int_t>(v)`
- float → `static_cast<float_t>(v)`
- bool → `static_cast<bool_t>(v)`
- string → `string_t("...")`

Applies to:
- assignments
- expressions
- returns
- function arguments
- default values

---

## 4. Scope and Declaration

### Scope kinds
- global
- namespace
- function

### Rules
- first assignment in scope declares with `auto`
- reassignment in the same scope must not redeclare with `auto`
- use-before-declare is an error

### Overloading
- function and method overloading are forbidden by Simple C++ design
- the generator must reject same-name overload sets rather than attempting overload-based lowering

---

## 5. Expressions

### Supported operator families
- arithmetic: `+ - * / %`
- comparison: `== != < <= > >=`
- logical: `&& ||`

### Rules
- recursive AST normalization
- literals are normalized at leaves
- parentheses are preserved
- no combinatorial expansion

---

## 6. Runtime Delegation

PHP-specific behavior must go through runtime helpers when required.

Examples:
- `php::isset`
- `php::empty`
- predefined/runtime constants through `::scpp::php` (classified from `get_defined_constants()`)
- `php::identical`
- `php::not_identical`
- `scpp::pow`
- `scpp::cmp`

---

## 7. Casting

- scalar casts use `static_cast<T>(...)`
- string conversion uses `string_t(...)`
- C-style casts are allowed only for non-literals when required as a temporary form

---

## 8. Functions

- typed parameters are mandatory
- explicit return types are mandatory
- default values must be normalized
- nullable types must be emitted as `nullable<T>` for nullable value types
- references are supported for functions and methods when explicit in source
- reference semantics are emitted literally and are never inferred
- default parameter values are allowed and belong to declarations only

---

## 9. Control Flow

### Supported
- `if / else / elseif`
- `while`
- `do-while`
- `for`
- `switch` (known mismatch remains documented separately)

### Rejected
- `foreach`

---

## 10. Statements

- expression statements are allowed
- compound assignments are allowed after normalization
- `++` and `--` require a declared variable

---

## 11. Rejected Features

- arrays
- `stdClass` / object iteration
- `foreach`
- `include` / `require`
- `and` / `or` / `xor`
- untyped parameters
- function or method overloading
- untyped raw `null` assignment

---

## 12. Incompatibilities

See `incompatibilities.md`.

Known items include:
- division semantics
- `switch` behavior differences
- spaceship operator

---

## 13. Compilation Constraints

All generated C++ code must compile with `-Wshadow` enabled.

### Implications
- generated symbol access must remain explicit and unambiguous under C++ shadowing semantics
- generation must not rely on unstable lookup behavior
- use-before-declare remains an error

---

## 14. Namespaces

### 14.1 Declaration Emission
- PHP namespaces are emitted under `scpp::...`
- semicolon and braced namespace forms are structurally equivalent
- compact nested namespace syntax such as `namespace scpp::A::B {}` is valid and preferred

### 14.2 Qualified Name Lowering
- fully-qualified PHP names `\A\B\x` lower to `::scpp::A::B::x`
- qualified PHP names `A\B\x` lower to `A::B::x`
- unqualified PHP names `x` remain `x`

### 14.3 Uniform Symbol Path Rule
Qualified symbol access is uniform across namespace-like members.

Namespaces, classes, functions, constants, and namespace-scope variables use the same path resolution syntax, while preserving their own symbol kind and usage rules.

### 14.4 Symbol Resolution Simplicity
Except for explicitly defined cases, the generator must not attempt semantic symbol resolution.

Namespace and class name lowering remains syntactic unless a rule states otherwise.

### 14.5 Namespace Imports
PHP `use` imports are deferred.

They must not be translated directly to C++ `using namespace`.

### 14.6 Namespace-Scope Constants and Variables
- namespace-scope constants are allowed
- namespace-scope mutable variables are forbidden


## 15. File Emission Model

- one PHP input file generates one `.hpp` file and one `.cpp` file
- generation is organized per input file, not per class
- the generated header contains declarations and the generated source contains out-of-line definitions
- generated files may always include a broad runtime/project header
- include minimization is not required for the generator

### Forward Declarations
- forward declarations may be used only in trivial obvious cases where a class type is referenced through `shared_p<T>` in declarations
- the generator must not build a dependency solver for include optimization
- if a case is not trivially safe for forward declaration, the generator may use the simpler include-based path instead

## 16. Expression Emission Policy

- expression lowering must remain structural and simple
- the generator must not try to behave like a semantic expression compiler
- casts, operators, precedence-preserving grouping, wrapper-type behavior, and null checks are emitted into C++ according to the configured forms and are then handled by the runtime and the C++ compiler
- the generator should only reject an expression when a generation rule explicitly marks that source form unsupported

## 17. Deferred Intent Metadata

- source-level intent that is not yet enforced at generation time may be recorded as metadata
- this includes, for example, non-null object intent where `T` and `?T` currently emit the same object-handle type
- recording intent metadata must not change the current emitted C++ form unless a generation rule explicitly requires it
- namespace-scope assignments are allowed only when they lower to constant declarations such as `const auto X = ...;`

### 14.7 Namespace-Scope Executable Code
Executable statements must not be emitted directly at namespace scope.

Executable statements inside the same namespace body are consolidated into a single synthetic namespace `main()`, even when declarations appear between them.

Declarations remain at namespace scope and do not split execution into separate synthetic functions.

Source order of executable statements must be preserved when consolidating them into the synthetic namespace `main()`.

This consolidation is valid only when all executable statements belong to the same namespace body and can be merged into a single generated code block for that namespace.

If execution reaches the end of the synthetic namespace `main()` without an explicit return, the generator must append `return 0;`.

The generated global `int main()` must return the result of the selected synthetic namespace `main()` call.

### 14.8 Cross-Namespace Execution Restriction
Executable statement consolidation applies only within a single namespace body.

Executable code in a parent namespace and executable code in a nested namespace create different execution flows and are not allowed together.

A nested namespace may appear inside a parent namespace execution region only when the nested namespace contributes declarations only.

### 14.9 Multiple Namespace Blocks
Multiple namespace blocks in one file are deferred until file-structure and translation-unit rules are specified.

---

## 15. Class Construction and Static Access

### 15.1 Object Construction
`new Class(...)` must be lowered to `create<Class>(...)`.

Examples:
- `new X()` → `create<X>()`
- `new \A\B\X()` → `create<::scpp::A::B::X>()`

The generator must not emit raw `new` for these supported construction forms.

### 15.2 Static Access
- same-namespace static access remains unqualified, for example `X::make()`
- fully-qualified PHP static access lowers to rooted C++ access, for example `\A\X::make()` → `::scpp::A::X::make()`

### 15.3 Static Access Through Instances
PHP static access through an instance must be lowered syntactically using `::scpp::class_t<decltype(...)>`.

Example:
- `$x::make()` → `::scpp::class_t<decltype(x)>::make()`

The generator must not attempt to validate whether `::scpp::class_t<decltype(...)>::member` is semantically valid for the produced C++ type.

If the emitted C++ is invalid, it must fail at C++ compile time rather than being rejected by the generator.

---

# Appendix: Full Original Rules (verbatim)

# Simple C++ – rules.md

This is the single source of truth for generation rules and runtime assumptions.

## 1. Scope and precedence

- General rules in this document have precedence over per-example decisions.
- Concrete examples may be corrected to comply with these rules.
- The catalog is for coverage and traceability; this file defines the normative behavior.

## 2. Emission namespace

All generated C++ code must be emitted inside:

```cpp
namespace scpp {
	// generated code
}
```

## 3. Runtime assumptions

### 3.1 Provided runtime types
Primitive-like types:
- `int_t` -> signed 8-byte integer
- `bool_t` -> C++ `bool`
- `float_t` -> signed 8-byte floating point

Wrapper / heavy types:
- `string_t` -> wrapper around `std::string`
- `vector_t` -> wrapper around `std::vector`

Null support:
- `null_t` -> custom type
- `null` -> `inline constexpr null_t null {};`

Nullable support:
- `nullable<T>`
- `shared_p<T>`
- `vector_t`
- runtime `null` / `nullopt` support via the runtime helpers

### 3.2 Provided runtime helpers
- `create<T>()`
- `shared<T>()`
- `weak<T>()`
- `unique<T>()`

### 3.3 Runtime boundary
The generator does **not** validate whether operator overloads or conversions exist in the runtime.

If generated C++ later fails because of:
- operator overload gaps
- unsupported runtime conversions
- stream operator gaps
- missing runtime helpers

that is outside the current generator scope and may fail at C++ compile time.

### 3.4 Allowed assumed runtime/operator surface
The generator is allowed to emit code that assumes support for:
- arithmetic operators
- comparison operators
- logical operators
- `std::cout <<`
- string concatenation through `+`
- comparisons against `null`

## 4. Scope model

A scope is:
- a function body
- a namespace body
- the global namespace body

This rule is used for first-assignment / `auto` decisions.

## 5. Variable model

- PHP variables map to native C++ identifiers by removing the `$` prefix.
- Example: `$a` -> `a`
- First assignment in the current scope -> declare with `auto`
- Reassignment in the same scope -> no `auto`

Examples:
```cpp
auto a = static_cast<int_t>(1);
a = static_cast<int_t>(2);
```

## 6. Global literal normalization rule

**All literals must always be converted to runtime-compatible C++ forms. No exceptions.**

This applies:
- in assignments
- in expressions
- in returns
- in function arguments
- in conditions
- in branch bodies
- in loop bodies

### 6.1 Primitive literal normalization
- `int` -> `static_cast<int_t>(...)`
- `float` -> `static_cast<float_t>(...)`
- `bool` -> `static_cast<bool_t>(...)`

Examples:
```cpp
auto a = static_cast<int_t>(10);
auto a = static_cast<float_t>(10.5);
auto a = static_cast<bool_t>(true);
```

### 6.2 String literal normalization
PHP string literals must first be normalized into valid C++ string literals, then materialized as `string_t("...")`.

Examples:
```cpp
auto a = string_t("x");
auto a = string_t("");
```

### 6.3 String restriction
Never emit:
```cpp
static_cast<string_t>(...)
```

Always emit:
```cpp
string_t(...)
```

### 6.4 Constant normalization
The generator snapshots `get_defined_constants()` once at startup. Constants found in that predefined-runtime snapshot are lowered through `::scpp::php`, while user-defined constants stay in the generated user namespace model.

Examples:
```cpp
auto a = PHP_INT_MAX;                // inside generated .cpp namespace blocks with `using namespace ::scpp::php;`
auto b = ::scpp::php::PHP_INT_MAX;   // explicit form
auto c = LIMIT;                      // user-defined constant in the current generated namespace
auto d = ::scpp::A::B::LIMIT;        // user-defined constant in another generated namespace
```

## 7. Null and nullable rules

### 7.1 Untyped null assignment
Direct untyped `null` assignment is not allowed:
```php
$a = null;
```
-> error

### 7.2 Nullable mapping
- `?T` -> `nullable<T>`

Examples:
- `?int` -> `nullable<int_t>`
- `?string` -> `nullable<string_t>`

### 7.3 Typed null
Allowed:
```cpp
nullable<int_t> a = null;
```

### 7.4 Nullable return of null
Allowed.

## 8. Type mapping

- `int` -> `int_t`
- `float` -> `float_t`
- `bool` -> `bool_t`
- `string` -> `string_t`
- `vector` -> `vector_t`
- `void` -> `void`

Not implemented yet:
- PHP `array`

## 9. Parameter passing rules

### 9.1 Pass by value
- `int_t`
- `float_t`
- `bool_t`
- `nullable<int_t>`
- `nullable<float_t>`
- `nullable<bool_t>`

### 9.2 Pass by const &
- `string_t`
- `vector_t`
- `nullable<string_t>`
- `nullable<vector_t>`
- future heavy wrapper types

## 10. Return rules

### 10.1 Missing declared return type
- Missing return type -> `auto`

### 10.2 Primitive-like returns
Return by value for:
- `int_t`
- `float_t`
- `bool_t`
- `nullable<int_t>`
- `nullable<float_t>`
- `nullable<bool_t>`

### 10.3 Heavy / wrapper returns
Return by `const &` only when the returned expression is clearly an existing stable object/reference.

Examples:
```cpp
const string_t& f(const string_t& a) { return a; }
const nullable<string_t>& f(const nullable<string_t>& a) { return a; }
```

Return by value for:
- literals
- temporary objects
- computed expressions
- concatenations
- function call results
- any return expression whose lifetime safety is not explicitly known

Examples:
```cpp
string_t f() { return string_t("x"); }
string_t f(const string_t& a) { return a + string_t("x"); }
string_t f() { return func_in_another_file(); }
nullable<string_t> f(const nullable<string_t>& a) { return null; }
```

## 11. Function declaration rules

### 11.1 Parameters
- Parameters must have explicit types
- Missing parameter type -> error

### 11.2 Representative forms
```cpp
int_t f(int_t a) { return a; }
const string_t& f(const string_t& a) { return a; }
string_t f() { return string_t("x"); }
```

## 12. PHP runtime boundary rules

These PHP semantics must go through the `php::` layer:

- `unset($a)` -> `php::unset(a);`
- `isset($b)` -> `php::isset(b)`
- `empty($b)` -> `php::empty(b)`
- strict equality `===` -> `php::identical(...)`
- strict inequality `!==` -> `php::not_identical(...)`
- predefined/runtime constants discovered from `get_defined_constants()` -> `::scpp::php::...`
- user-defined non-class constants -> generated user namespace path (no `::scpp::php` remapping)

## 13. Simple C++ runtime/helper boundary rules

Helpers that are not plain PHP semantic primitives may go through the `scpp::` layer.

Current accepted case:
- exponentiation `**` -> `scpp::pow(...)`

Example:
```cpp
auto a = scpp::pow(static_cast<int_t>(2), static_cast<int_t>(3));
```

## 14. Expression normalization rules

### 14.1 Recursive normalization
Expression normalization is recursive and bottom-up on the AST.

Every literal at any depth must be normalized.

Example:
```cpp
auto a = (b + static_cast<int_t>(1)) * static_cast<int_t>(2);
```

### 14.2 Parentheses
Preserve parentheses to maintain evaluation order.

### 14.3 Chained assignment
Chained assignments must be decomposed into sequential statements.

Example:
```cpp
auto b = static_cast<int_t>(1);
auto a = b;
```

## 15. Generalized operator families

### 15.1 Arithmetic family
Covers:
- `+`
- `-`
- `*`
- `/`
- `%`

Rule:
Binary arithmetic operations must be emitted as arithmetic on normalized runtime-typed operands. Any literal operand must first be converted with the appropriate `static_cast<..._t>(...)`.

Examples:
```cpp
auto a = static_cast<int_t>(1) + static_cast<int_t>(2);
auto a = static_cast<int_t>(1) - static_cast<int_t>(2);
auto a = static_cast<int_t>(2) * static_cast<int_t>(3);
auto a = static_cast<int_t>(4) / static_cast<int_t>(2);
auto a = static_cast<int_t>(5) % static_cast<int_t>(2);
auto a = b + static_cast<int_t>(1) + c;
```

### 15.2 Concatenation family
PHP `.` maps to C++ `+` on `string_t` operands.

Examples:
```cpp
auto a = string_t("a") + string_t("b");
auto a = b + string_t("x");
auto a = string_t("x") + b;
```

### 15.3 Non-strict comparison family
Covers:
- `==`
- `!=`
- `<`
- `<=`
- `>`
- `>=`

Rule:
Comparison operations must operate on normalized operands. Any literal operand must be converted using the appropriate runtime cast.

Examples:
```cpp
auto a = (b == static_cast<int_t>(1));
auto a = (b != static_cast<int_t>(1));
auto a = (b < static_cast<int_t>(1));
auto a = (b <= static_cast<int_t>(1));
auto a = (b > static_cast<int_t>(1));
auto a = (b >= static_cast<int_t>(1));
```

### 15.4 Strict comparison family
- `===` -> `php::identical(...)`
- `!==` -> `php::not_identical(...)`

Examples:
```cpp
auto a = php::identical(b, static_cast<int_t>(1));
auto a = php::not_identical(b, static_cast<int_t>(1));
```

### 15.5 Unary numeric operators
Examples:
```cpp
auto a = -b;
auto a = +b;
```

## 16. Null coalescing

Accepted lowering:
```php
$b ?? 1
```

becomes:
```cpp
(b != null) ? b : static_cast<int_t>(1)
```

Rules:
- `null` is emitted directly
- fallback literals still follow normal literal conversion rules

## 17. Output rules

- `echo` -> `std::cout <<`

Examples:
```cpp
std::cout << a;
std::cout << a << b << c;
```

## 18. Error handling policy

For unsupported or invalid cases:
- stop generation immediately
- throw an error
- include file / line / position if available

## 19. Formatting

Current target:
- compact
- readable
- tabs for indentation

## 20. Notes on known open incompatibilities

These are known and not yet fully resolved in rules:

### 20.1 Division semantics
PHP `/` produces a floating-point result; C++ `/` depends on operand types.
A later normalization/promotion rule is required.

### 20.2 Loose comparison semantics
PHP `==` and `!=` are not fully equivalent to native C++ `==` and `!=`.
A later decision must either:
- route them through runtime helpers, or
- formally restrict supported operand/type combinations.



## 16. Classes, Inheritance, and Members

### 16.1 File Split
- each user PHP class lowers to a header declaration unit and a source implementation unit
- the header contains the class definition, fields, constructor declarations, destructor declarations, and method declarations
- the source contains out-of-line constructor, destructor, and method bodies

### 16.2 Supported Forms
- inheritance is supported
- interfaces are supported
- traits are not supported
- only one parent class is allowed
- multiple interfaces are allowed

### 16.3 Base Alias
- if a class has a parent, emit `using base = Parent;` in the class body
- `parent::method(...)` lowers to `base::method(...)`
- `parent::__construct(...)` lowers to a base initializer call

### 16.4 Construction and Lifetime Surface
- `new Class(...)` lowers to `create<Class>(...)`
- direct construction of user-defined PHP classes is forbidden
- direct construction is required for whitelisted runtime/value types such as `string_t` and `vector_t`
- explicit runtime ownership forms such as `weak($object)`, `unique(new MyClass)`, and `shared(new MyClass)` are allowed surface forms when separately supported by the generator; `shared(new MyClass)` is the explicit counterpart of `create<MyClass>(...)`

### 16.5 Instance Context
- `$this` is valid only in instance methods, constructors, and destructors
- `$this->prop` lowers to `this->prop`
- `$this->method(...)` lowers to `this->method(...)`

### 16.6 Properties
- properties must be typed
- instance properties are emitted in the header only
- property initialization at declaration is not supported; constructor initialization must be used instead
- dynamic properties are not supported
- dynamic property names are not supported
- object-typed fields lower to handle fields such as `shared_p<B>`
- when needed for headers, forward declarations such as `class B;` may be emitted
- static object-typed properties use the same handle model

### 16.7 Methods and Special Members
- non-static methods are supported
- static methods are supported
- constructors are supported
- destructors are supported
- abstract classes are supported when explicitly declared abstract
- abstract methods lower to pure virtual methods
- interface methods lower to pure virtual methods
- `#[\Override]` is required to emit `override`; the generator must not infer overrides
- `final` is preserved on declarations
- `abstract static` methods are rejected
- `static` with `#[\Override]` is rejected
- class and method overloading are forbidden

### 16.8 Dispatch and Validation Boundary
- ordinary methods are not made virtual unless an explicit rule requires it
- dispatch remains ordinary C++ dispatch
- the generator must not attempt hierarchy validation, symbol resolution across files, or override correctness checks unless a generation rule explicitly requires a local structural check
- let the C++ compiler fail for semantic issues outside generator scope

### 16.9 Static Access Through Instances
- PHP static access through an instance must be lowered syntactically using `::scpp::class_t<decltype(...)>`
- `$x::make()` → `::scpp::class_t<decltype(x)>::make()`
- `$x::$prop` → `::scpp::class_t<decltype(x)>::prop`
- the generator must not attempt to validate whether the generated C++ member access is semantically valid


## (Added) Global Execution Clarification

For global PHP executable code:

namespace scpp {
	int main() { ... }
}

int main() {
	return scpp::main();
}

