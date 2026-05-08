Doc Status: normative


See `../../specs/spec_map.md` for document hierarchy, authority, and v1 conflict-resolution rules.

# Prism++ â€“ General Rules (Authoritative, Normalized)

> Transitional implementation note: see `../../specs/mixed_boundary_transitional.md`.

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
- `unique_p<T>`
- `weak_p<T>`
- `value_p<T>`
- `vector_t`
- runtime `null` / `nullopt` support via the runtime helpers

### Rules
- `string_t` uses constructor form, not `static_cast`
- `nullable<T>` is the null carrier for nullable value types
- object/class/interface handle types use `shared_p<T>` and are inherently nullable
- explicit runtime handle annotations `shared<T>`, `unique<T>`, `weak<T>`, and `weakref<T>` lower directly to `shared_p<T>`, `unique_p<T>`, and `weak_p<T>`
- `value_p<T>` is opt-in inline storage and is never the default lowering for PHP object types
- runtime `null` is the canonical null literal for generated code where null is supported
- null comparisons/checks must use the configured runtime helpers such as `php::is_null(...)` and `php::not_null(...)`
- generated/frontend-facing semantic calls should follow the active PHP profile surface
- legacy-profile generated calls target `scpp::php::*` entrypoints
- strict-profile generated calls may lower directly to shared `scpp::*` runtime families only through symbols declared by the active strict profile registry
- `scpp::php::*` may forward to shared `scpp::*` authorities when PHP semantics match the shared Prism++ semantics

---

## 2. Type System

### Mandatory
- function and method parameters must be typed explicitly
- parameter typing must come from exactly one source: native PHP type or supported doc-comment type
- native PHP type plus supported doc-comment type on the same parameter/property is an error
- class properties must be typed explicitly
- return types must be explicit

### Mapping
- `int` â†’ `int_t`
- `float` â†’ `float_t`
- `bool` â†’ `bool_t`
- `string` â†’ `string_t`
- `?T` â†’ `nullable<T>` for value-like types
- class / interface / abstract object types â†’ `shared_p<T>`
- `?ClassType` / `?InterfaceType` / `?AbstractType` â†’ `shared_p<T>`
- object nullability does not currently change the emitted C++ type; `A` and `?A` both emit `shared_p<A>` for now

### Returns
- non-void functions must return a value on all paths
- void functions cannot return a value


### Variable Typing
- explicit scanner-owned inline slot type annotations are authoritative when present
- accepted inline comment forms are limited to recognized typed slots, not generic PHPDoc tags
- local variables keep the strict immediate-after-variable form only
- valid local form example: `$x /** string */ = "test";`
- parameters and properties additionally support the leading attached form such as `function f(/** vector<int> */ $list): void {}` and `public /** int */ $x;`
- properties also support the immediate trailing form such as `public $items /** vector<int> */ = [];`
- function-like returns support the immediate post-signature form such as `function build() /** vector<int> */ { ... }` and `fn(/** int */ $x) /** function<int(int)> */ => ...`
- detached forms such as `/** @var vector<int> */ $items = [];` are not accepted as typed-slot metadata
- class constants support the leading attached form such as `const /** int */ X = 1;`
- constant declarations fall back to initializer-based type deduction in emitted C++ (`const auto ... = ...`)
- detached or non-adjacent type comments remain invalid
- `$x /** string */ = "test";` â†’ `string_t x("test");`
- `$x /** ?string */ = "test";` â†’ `nullable<string_t> x("test");`
- `$x /** ?string */ = null;` â†’ `nullable<string_t> x = null;`
- `$x /** A */ = new A();` â†’ `shared_p<A> x = create<A>();`
- `$x /** ?A */ = null;` â†’ `shared_p<A> x = null;`
- `$x /** value<Point> */ = new Point(1, 2);` â†’ `value_p<Point> x = value<Point>(static_cast<int_t>(1), static_cast<int_t>(2));`
- `$x /** weak<A> */ = null;` â†’ `weak_p<A> x = null;`
- `$x /** weakref<A> */ = null;` â†’ `weak_p<A> x = null;`
- `$x /** unique<A> */ = null;` â†’ `unique_p<A> x = null;`
- `$x /** shared<A> */ = null;` â†’ `shared_p<A> x = null;`
- `$x /** ref int */ = &$y;` â†’ `int_t& x = y;`
- `/** ref Point */` locals lower directly to `shared_p<Point>&` when `Point` lowers to an object handle
- `ref` lowering is intentionally a reduced write-through alias feature built on native C++ references; rebinding-through-alias and PHP-style alias-preserving `unset` are out of scope

- explicit inline object/value storage is opt-in only through the PHPDoc forms `value<T>` and `value<?T>`
- object-handle local wrappers are expressed canonically as `shared<T>` and `unique<T>`
- `value<T>`, `value<?T>`, `shared<T>`, and `unique<T>` are currently supported for typed local variables only
- legacy `value T` is still accepted temporarily for compatibility, but `value<T>` and `value<?T>` are the canonical forms going forward
- strict local wrapper shortcuts are supported only for direct constructor assignment: `/** value */`, `/** shared */`, and `/** unique */` must appear on a typed local whose initializer is exactly `new ClassName(...)`; the generator must immediately normalize them to `value<ClassName>`, `shared<ClassName>`, or `unique<ClassName>`. After normalization, explicit wrapper forms such as `value<T>`, `shared<T>`, and `unique<T>` initialized from `new U(...)` must validate that `T` and `U` match exactly.
- bare local wrapper shortcuts must be rejected when the initializer is not a direct `new ClassName(...)` expression, when the class target is not statically known, or when the assignment shape is not a normal direct local assignment
- when a `value<T>` local is initialized from `new T(...)`, generation must use `value<T>(...)` instead of `create<T>(...)`
- when a `value<?T>` local is initialized from `new T(...)`, generation must lower to `nullable<T>{T(...)}`; this is the canonical nullable inline-object lowering and must not silently degrade to `mixed_t`
- when a `unique<T>` local is initialized from `new T(...)`, generation must use `::scpp::unique<T>(...)` instead of `create<T>(...)`
- explicit wrapper locals initialized from `new ...` must reject constructor-target mismatches; for example, `/** value<A> */ = new B()` is a generator error and must not silently default-initialize `A`
- `value<T>` locals remain object-like at the usage surface: property and method access must continue to lower through `->`, for example `$x /** value<MyClass> */ = new MyClass(); $x->property_1 = 10;` lowers conceptually to `value_p<MyClass> x = value<MyClass>(); x->property_1 = static_cast<int_t>(10);`
- `value<?T>` locals remain object-like at the usage surface through nullable dereference: for example `$x /** value<?MyClass> */ = null; $y /** value<?MyClass> */ = new MyClass();` lowers conceptually to `nullable<MyClass> x = null; nullable<MyClass> y{MyClass()};`, and `$x->prop` must fail at runtime while `$y->prop` must behave like `MyClass`
- `shared_p<MyClass> x = null` is not a valid nullable-wrapper test because it exercises handle-null semantics, not `nullable<T>` semantics
- explicit type intent must never silently fall back to `mixed_t`; unsupported or malformed explicit type syntax such as an unknown wrapper form must fail generation with a diagnostic
- explicit reference lowering over handle-like wrappers must emit a native handle reference (`shared_p<T>&`, `unique_p<T>&`, `weak_p<T>&`) instead of creating nested pointer/reference layers

### Untyped Variable Initialization
- untyped variables may still lower to explicit runtime-wrapped expressions
- `$x = "test";` â†’ `auto x = string_t("test");`
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
- raw `&`, `&&`, and `*` must not appear inside source type definitions or PHPDoc type comments; explicit references are represented only by PHP reference syntax and typed local `ref T` annotations

---

## 3. Literal Normalization

All literals must be normalized.

### Required forms
- integer â†’ `static_cast<int_t>(v)`
- float â†’ `static_cast<float_t>(v)`
- bool â†’ `static_cast<bool_t>(v)`
- string â†’ `string_t("...")`

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
- function and method overloading are forbidden by Prism++ design
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

Current architectural rule:
- legacy PHP-facing lowering targets `scpp::php::*`
- strict PHP-facing lowering may target shared `scpp::*` runtime families when the active strict profile registry declares those symbols
- language entrypoints remain the stable generator-facing surface for legacy and PHP-owned semantics

---

## 7. Casting

- scalar casts use `static_cast<T>(...)`
- string conversion uses explicit `cast<string_t>(...)` only for supported pairs
- in all other cases string conversion uses `string_t(...)`
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
- primary-type normalized PHP union parameters are supported by design; see `primary_type_normalized_parameters.md`
- for a union parameter, the first listed type is the primary type and later types are secondary source types
- current generator lowering extracts the primary type as the emitted callable-body type while annotation parsing and validation are handled centrally

---

## 9. Control Flow

### Supported
- `if / else / elseif`
- `while`
- `do-while`
- `for`
- `switch` (known mismatch remains documented separately)

### Rejected
- `foreach` over `vector_t` lowers to an indexed C++ `for` loop

---

## 10. Statements

- expression statements are allowed
- compound assignments are allowed after normalization; `.=` must normalize the right-hand side through the same explicit string cast path as `.`
- `++` and `--` require a declared variable

---

## 10A. File Prologue `require_once` Subset

- `require_once` is supported only as a static compile-time include in the file prologue
- only the exact literal-string form is supported: `require_once "path/file.php";`
- `require_once` is rejected after any non-prologue construct
- before `require_once`, only comments and `declare(...);` are allowed
- `namespace`, `use`, constants, classes, functions, and executable statements close the prologue
- `require_once` is not allowed inside namespaces, functions, methods, classes, or executable statement blocks
- dynamic include expressions are rejected, including `__DIR__` concatenation and any computed path form
- the generator does not check file existence and does not read or transpile the required file
- lowering is purely textual at generation time: `.php` suffixes map to `.hpp` and the generated header emits `#include "..."`

---

## 11. Rejected Features

- reduced PHP array subset (see catalog rows `ARR-*`)
- `stdClass` / object iteration
- `foreach` by value is supported for typed `vector<T>` / `hash<T>` surfaces, for the current packed `hash_t<mixed_t>` dynamic-array surface, and for approved wrappers that delegate an iterable success payload through the runtime iterable surface
- foreach key/value variables are always emitted as fresh loop-local variables in the generated C++; they shadow outer locals of the same PHP name inside the loop body
- by-reference foreach is currently lowered through source-slot rewriting rather than a standalone alias local
- value-only form synthesizes a hidden key local such as `_<value>_key_`
- explicit-key form preserves the PHP key variable and rewrites the foreach value variable through the source slot keyed by that variable
- this lowering is provisional and subject to future improvement
- for boxed-array foreach over `mixed_t`, indexed loop lowering uses the generator-facing `mixed_t::size()` / `mixed_t::at(...)` surface instead of reaching through to raw table internals
- for wrapper-carried iterable payloads, the generator remains type-blind and simply lowers against the runtime iterable surface exposed by the wrapper
- explicit function/method reference returns require an explicit declared PHP return type and must still satisfy the native-reference safety rule; dynamic interior slot/property returns are not allowed
- `include`, `include_once`, and `require`
- `and` / `or` / `xor`
- untyped parameters
- function or method overloading
- untyped raw `null` assignment

---



## 11A. Variable naming normalization

- PHP variable names are preserved unless the raw name is a reserved C++ keyword
- reserved keyword names lower to `<name>__`
- if that candidate already exists in the same function-like scope, the generator must try `<name>__1`, `<name>__2`, and so on until a free identifier is found
- the chosen remapped identifier must be used consistently in declarations, headers, source definitions, helpers, and all uses within that function-like scope

---

## 11B. Array subset (v1)

Supported array lowering is intentionally narrow and split by target typing.

Priority note:
- `../../specs/dynamic_types.md` sections **1.2 Explicit Typed Boundaries** and **1.3 Technical Compromises to Preserve Explicit Typed Boundaries in v1** govern current typed-destination lowering from `mixed_t`
- until symbol resolution/static analysis is strong enough to inject every required explicit cast at the exact site, generated/runtime-visible behavior must continue to preserve those v1 bridges
- generator cleanup must therefore not assume that removing runtime bridge casts is safe merely because the long-term model prefers explicit emitted casts

### Untyped PHP arrays
- untyped `[]` lowers to `mixed_t x = mixed_t{table_()}` when it is used as an explicit array-present initializer; dynamic locals may also start as `mixed_t x;` / `mixed_t x = null` and autovivify later
- untyped `[v1, v2, ...]` lowers to `mixed_t x = mixed_t{table_(table_item_(...), ...)}`
- first assignment declaration inference is intentionally overridden for fat-value bootstrap initializers: `$x = [];`, `$x = [ ... ];`, and `$x = null;` must declare as `mixed_t`, never `auto`, so the variable immediately exposes the fat `mixed_t` API (`append`, `operator[]`, `get`) and preserves null-state autovivification
- nested append writes are full mutating LHS chains: `$x["users"][] = $v;` must lower through mutating access on `$x["users"]` and then `append(...)` (for example `x[string_t("users")].append(...)`), never through the read-only `.get(...)` path.
- untyped `["k" => v]` lowers to `mixed_t x = mixed_t{table_(table_kv_("k", ...))}`
- nested untyped arrays recurse through the same `table_ / table_item_ / table_kv_` helpers
- PHP array reads in read-only contexts lower to `get(...)` / `_find_val(...)`; mutating contexts still lower to `operator[]` / `append(...)`
- native PHP `array` type declarations now lower to `mixed_t`; function-entry guards enforce `array` vs `?array` before any user code runs, and explicit PHP `&` lowers to `mixed_t&`
- PHP keyed writes now lower to direct `operator[]` assignment
- PHP append writes lower to `append(...)`; simple right-hand sides inline directly, while non-trivial right-hand sides may spill into a temporary to keep assignment-style lowering explicit
- `unset($a[k])` lowers to `remove(k)`; missing-key `unset` remains a no-op
- `isset($a[k])` lowers through the runtime `isset(...)` helper and must preserve null-sensitive semantics (`missing` â†’ `false`, existing `null` â†’ `false`)
- `empty($a[k])` lowers through the runtime `empty(...)` helper for the resulting value; under the current supported subset it is true only for `null`, `""`, and empty array/table values
- the normative cross-runtime contract is defined in `specs/count_empty_isset_contract.md`

### Typed vectors
- `/** vector<T> */ []` lowers to `vector_t<T>{}`
- `/** vector<T> */ [e1, e2, ...]` lowers to `vector_t<T>{e1, e2, ...}`
- typed vector literals must remain positional; explicit keys are rejected

### Typed hashes
- `/** hash<T> */ []` lowers to `hash_t<T>{}`
- `/** hash<T> */ ["k" => v, ...]` lowers to a typed `hash_t<T>` initializer sequence with the default `string_t` key surface
- `/** hash<T, T_KEY> */ ...` lowers to `hash_t<T, T_KEY>` when an explicit typed key family is requested
- typed hash literals may use keyed entries for all supported key families
- append-style entries are structurally available on the runtime surface for generator compatibility, but are semantically valid only for integer-keyed typed hashes
- typed hash read-only dim access lowers through checked keyed access
- typed hash write dim access lowers through mutating keyed access / append on `hash_t<T>` or `hash_t<T, T_KEY>` as appropriate

### Intentional v1 deviations from PHP
- `hash_t` keeps integer keys and string keys distinct (`1` != `"1"`)
- `operator[]` is now the primary read/write surface for lowered PHP array access. Mutable paths autovivify missing slots; const paths return the static null-like value on miss. `find(...)` remains reserved for presence-sensitive logic; `at(...)` remains the runtime checked-access API.
- typed value destinations reached from array reads keep the same missing-key read semantics first, then apply the ordinary typed-boundary rules from `../../specs/dynamic_types.md`

See also `../../specs/array_semantics.md` for the authoritative current subset.

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
`use` lowers through explicit namespace-local C++ declarations. The generator keeps the model structural and does not perform semantic symbol resolution beyond the import kind already present in the PHP AST.

Core rules:
- every imported path is treated as absolute when emitted from `use`
- `using namespace` must not be emitted for PHP `use`
- emitted import declarations are namespace-local and are placed inside the generated `namespace scpp::... {}` block
- conflicts are delegated to PHP/C++ compile-time behavior; the generator does not try to pre-resolve them

Supported now:
- `use A\B\C;` lowers to `using ::scpp::A::B::C;`
- `use A\B\C as D;` lowers to `using D = ::scpp::A::B::C;`
- `use function A\B\f;` lowers to `using ::scpp::A::B::f;`
- `use function A\B\f as g;` lowers to `inline constexpr auto g = ::scpp::A::B::f;`
- `use const A\B\X;` lowers to `using ::scpp::A::B::X;`
- `use const A\B\X as Y;` lowers to `inline constexpr auto& Y = ::scpp::A::B::X;`
- grouped imports are supported by expanding them to one emitted declaration per imported element:
  - `use A\B\{C, D};`
  - `use A\B\{C as D};`
  - `use function A\B\{f, g as h};`
  - `use const A\B\{X, Y as Z};`

Notes:
- plain `use` is treated as a symbol import, not as a namespace-alias feature
- fully-qualified PHP names in normal code still lower via the rooted `::scpp::...` form
- non-root qualified names in normal code remain syntactic, for example `A\B\C` -> `A::B::C`
- no PHP fallback import/name-resolution behavior is implemented

Known semantic edge:
- `use const A\B\X;` can still differ from PHP when the current namespace already defines `X`; PHP may prefer the imported constant while C++ `using` produces a conflict

### 14.6 Namespace-Scope Constants and Variables
- namespace-scope constants are allowed
- emitted constant declarations use initializer-based type deduction (`const auto`) instead of requiring an explicit mapped scalar type
- namespace-scope executable bootstrap statements are allowed and are lowered into the synthetic namespace execution function
- namespace-scope static variables are forbidden


## 15. File Emission Model

- one PHP++ input file generates one `.hpp` file and one `.cpp` file
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
- explicit grouping/parentheses must be preserved when the PHP AST encodes grouped binary expressions
- the generator must not flatten grouped expressions in a way that changes PHP operator precedence
- the generator must not try to behave like a semantic expression compiler
- casts, operators, precedence-preserving grouping, wrapper-type behavior, and null checks are emitted into C++ according to the configured forms and are then handled by the runtime and the C++ compiler
- the generator should only reject an expression when a generation rule explicitly marks that source form unsupported

## 17. Deferred Intent Metadata

- source-level intent that is not yet enforced at generation time may be recorded as metadata
- this includes, for example, non-null object intent where `T` and `?T` currently emit the same object-handle type
- recording intent metadata must not change the current emitted C++ form unless a generation rule explicitly requires it
- namespace-scope assignments that participate in executable bootstrap code are allowed and are lowered inside the synthetic namespace execution function
- namespace-scope static variables remain rejected

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
Multiple braced namespace blocks in one file are supported when they lower into ordinary declarations and, at most, one selected synthetic execution entry point.

Supported forms include:
- `namespace A { ... } namespace B { ... }`
- `namespace A\B { ... } namespace { ... }`
- multiple braced namespace blocks containing declarations only
- multiple braced namespace blocks followed by a braced global namespace block `namespace { ... }`

Lowering rules:
- each PHP namespace block lowers independently under the `scpp::...` root
- a braced global namespace block `namespace { ... }` lowers to `namespace scpp { ... }`
- rooted calls from the global block must lower without an empty namespace segment, for example `::scpp::__scpp_main()`
- declarations remain in their own generated namespace blocks and are not merged by name just because they appear in the same source file

Restriction:
- executable-statement consolidation still applies only within one namespace body at a time
- cross-namespace execution merging remains forbidden
- this section currently covers braced namespace blocks; semicolon-form multi-block behavior remains governed by the existing execution restrictions and file-structure rules

---

## 15. Class Construction and Static Access

### 15.1 Object Construction
`new Class(...)` must be lowered to `create<Class>(...)`.

Examples:
- `new X()` â†’ `create<X>()`
- `new \A\B\X()` â†’ `create<::scpp::A::B::X>()`

The generator must not emit raw `new` for these supported construction forms.

### 15.2 Static Access
- same-namespace static access remains unqualified, for example `X::make()`
- fully-qualified PHP static access lowers to rooted C++ access, for example `\A\X::make()` â†’ `::scpp::A::X::make()`

### 15.3 Static Access Through Instances
PHP static access through an instance must be lowered syntactically using `::scpp::class_t<decltype(...)>`.

Example:
- `$x::make()` â†’ `::scpp::class_t<decltype(x)>::make()`

The generator must not attempt to validate whether `::scpp::class_t<decltype(...)>::member` is semantically valid for the produced C++ type.

If the emitted C++ is invalid, it must fail at C++ compile time rather than being rejected by the generator.

---

# Appendix: Full Original Rules (verbatim)

# Prism++ â€“ rules.md

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
- `unique_p<T>`
- `weak_p<T>`
- `value_p<T>`
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
- condition lowering must use `static_cast<bool>(...)` for expressions already known to produce `bool_t`
- condition lowering must use `php::condition_truthy(...)` plus `static_cast<bool>(...)` for non-`bool_t` expressions that are allowed to enter control flow; `mixed_t` is only valid when its runtime payload is bool/int/float
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
The generator snapshots `get_defined_constants()` once at startup. Inside generated source namespace blocks, predefined/runtime constants lower to unqualified names because the source already uses `using namespace ::scpp;``. Generator-emitted runtime/helper references inside generated expression/type code MUST NOT use rooted `::scpp` or `::scpp::php` qualifiers; the only allowed rooted occurrences are the generated using-directives themselves and explicit import-lowering forms such as `use` declarations. User-defined constants stay in the generated user namespace model.

Examples:
```cpp
auto a = PHP_INT_MAX;                // inside generated `.cpp` namespace blocks with `using namespace ::scpp;`
auto c = LIMIT;                      // user-defined constant in the current generated namespace
auto d = A::B::LIMIT;                // user-defined constant in another generated namespace
```

## 7. Null and nullable rules

### 7.1 Typed local predeclaration

Safe v1 supports explicit local declarations without initialization when the local has an explicit type annotation.

Example:

```php
$f /** function<int(int)> */;
```

This form exists so an outer block can predeclare a local that will later be assigned inside child blocks while still respecting block-local visibility. Bare `callable` is not sufficient; use a concrete `function<return_type(arg_types)>` annotation.

The same concrete `function<return_type(arg_types)>` annotation form is also accepted on closure parameters when PHP syntax cannot express a native callable signature directly, for example `function (/** function<int(int)> */ $fn, int $x): int { ... }`.

Safe v1 also accepts scanner-owned shorthand type sites that are normalized before `php-ast` parsing, for example:

```php
$count int = 0;
public $items vector<string> = [];
function build($items vector<string>): vector<string> { ... }
$make = fn($x int) function<function<int(int)>(int)> =>
	fn($y int): int => $x + $y;
```

The pre-tokenizer normalizes those surfaces into parseable PHP source while separately preserving explicit site metadata for locals, properties, params, and function-like return slots. Because return-site ownership is scanner-owned, nested closure or arrow return annotations no longer rely on accidental raw `php-ast` doc-comment attachment. Arrow functions (`fn (...) => expr`) are also supported in Safe v1. php-ast exposes them as `AST_ARROW_FUNC` without an explicit `use (...)` list, so the generator infers implicit by-value captures from referenced outer locals and lowers them to native C++ lambdas with value captures.

### 7.2 Untyped null assignment
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
- Function and method parameters must have explicit types
- Missing parameter type -> error
- Parameter type fallback to `auto` is forbidden
- If both a native PHP type and a supported doc-comment type are present, emit an error

### 11.2 Representative forms
```cpp
int_t f(int_t a) { return a; }
const string_t& f(const string_t& a) { return a; }
string_t f() { return string_t("x"); }
```

## 12. PHP runtime boundary rules

These PHP semantics must go through the `php::` layer:

- `unset($a)` -> `php::unset(a);` only when the lowered target type is nullable / pointer-like / handle-like and supports an empty state
- for non-nullable value/container-like targets, use `clean($a)` -> `php::clean(a);` as the current project direction instead of lowering to `php::unset(a);`
- `isset($b)` -> `php::isset(b)`
- when the exporter normalizes multi-operand forms, generation must follow the exported tree instead of reconstructing surface syntax
- `empty($b)` -> `php::empty(b)`
- strict equality `===` -> `php::identical(...)`
- strict inequality `!==` -> `php::not_identical(...)`
- both helpers return `bool_t`, not native `bool`, because they are PHP-semantic runtime operations
- predefined/runtime constants discovered from `get_defined_constants()` -> unqualified `...` inside generated source namespace blocks
- user-defined non-class constants -> generated user namespace path (no `::scpp::php` remapping)

## 13. Prism++ runtime/helper boundary rules

Helpers that are not plain PHP semantic primitives may go through the `scpp::` layer.

Current accepted case:
- exponentiation `**` -> `scpp::pow(...)`

### 13.1 Rooted runtime qualification ban
Generator MUST NOT emit fully-qualified names like `::scpp` or `::scpp::php` in generated expression/type code because generated source namespace blocks already inject:
- `using namespace ::scpp;`

Examples:
```cpp
table_(table_item_(string_t("x")))
expect_array_argument(x, false, "x")
create<MyClass>()
class_t<decltype(obj)>::make()
A::B::LIMIT
```

Never emit these rooted runtime/helper forms inside generated expression/type code:
```cpp
::scpp::table_(...)
php::expect_array_argument(...)
::scpp::create<MyClass>()
::scpp::class_t<decltype(obj)>::make()
::scpp::A::B::LIMIT
```

Allowed exception:
- generated using-directives/import-lowering lines may still use rooted forms, for example `using namespace ::scpp;` or `using ::scpp::A::B::f;`

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
auto a = cast<string_t>(b) + string_t("x");
auto a = string_t("x") + cast<string_t>(b);
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

### 15.5 Unary operators currently lowered directly
Examples:
```cpp
auto a = -b;
auto a = +b;
auto a = ~b;
```

Notes:
- unary minus lowers as `(-<expr>)`
- unary plus lowers as `(+<expr>)`
- unary bitwise NOT lowers as `(~<expr>)`
- grouped unary/binary combinations must preserve AST structure, for example `(-a) * 2` and `(~a) * 2`

## 16. Null coalescing and ternary-family lowering

Accepted lowering for null coalescing:
```php
$b ?? 1
```

becomes:
```cpp
php::coalesce_eval(
	[&]() -> decltype(auto) { return b; },
	[&]() -> decltype(auto) { return static_cast<int_t>(1); }
)
```

Accepted lowering for ternary:
```php
$a ? $b : 0
```

becomes:
```cpp
php::ternary_eval(
	[&]() -> decltype(auto) { return a; },
	[&]() -> decltype(auto) { return b; },
	[&]() -> decltype(auto) { return static_cast<int_t>(0); }
)
```

Accepted lowering for elvis:
```php
$a ?: 0
```

becomes:
```cpp
([&]() -> auto {
	auto __scpp_cond_value = a;
	return php::ternary_eval(
		[&]() -> decltype(auto) { return __scpp_cond_value; },
		[&]() -> decltype(auto) { return __scpp_cond_value; },
		[&]() -> decltype(auto) { return static_cast<int_t>(0); }
	);
}())
```

Rules:
- current lowering emits helper calls here rather than solving branch/result typing inline
- `??` and `?:` use different runtime result matrices, but they do share one wrapper-normalization rule for PHP-visible null / bool / dynamic semantics
- the current `??` matrix includes explicit dynamic-carrier entries for `mixed_t ?? T`, `T ?? mixed_t`, and `nullable<T> ?? mixed_t`; these normalize to `mixed_t` rather than attempting a typed payload result
- `??` auto-unpacks only the approved wrapper families (`nullable<T>`, `result<T>`, and `result_or_false<T>`) to their usable value domain; in the current version `result_or_bool<T>` is rejected by the runtime helper on either side of coalesce because current lowering does not resolve that row statically here, while `?:` follows its own truthiness-based wrapper policy.
- helper lambdas preserve lazy right/branch evaluation
- elvis lowering must evaluate the left operand exactly once
- unsupported operand/branch combinations must fail deterministically at compile time in the runtime helper layer
- fallback literals still follow normal literal conversion rules

See also: `specs/conditional_expression_matrix.md`.

## 17. Output rules

- generated code currently routes output through `echo_eval(...)`
- lowering must preserve the exporter shape while preserving left-to-right echo operand evaluation
- for the current exporter:
	- each `AST_ECHO` node carries one operand
	- `echo a, b, c;` is exported as multiple sibling `AST_ECHO` nodes
	- adjacent echo nodes from the same lowered statement stream may be coalesced into one `echo_eval(...)` call
- each emitted operand must be wrapped as a thunk and evaluated inside the runtime helper in order

Examples:
```cpp
echo_eval([&]() -> decltype(auto) { return a; });
echo_eval(
	[&]() -> decltype(auto) { return a; },
	[&]() -> decltype(auto) { return b; },
	[&]() -> decltype(auto) { return c; }
);
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
- properties without defaults must be typed explicitly
- properties with default values may omit the explicit type; the generator infers the emitted C++ member type from the default initializer
- if both a native PHP type and a supported doc-comment type are present, emit an error
- instance properties are emitted in the header only
- non-static property default values are supported and lower to in-class default member initializers
- dynamic properties are not supported
- dynamic property names are not supported
- object-typed fields lower to handle fields such as `shared_p<B>`
- when needed for headers, forward declarations such as `class B;` may be emitted
- static object-typed properties use the same handle model
- static property fetch/read/write/increment lower to `Class::prop` storage access in generated C++
- supported static-property class forms are `ClassName::$prop`, `self::$prop`, and `parent::$prop`
- `static::$prop` is not supported in the current pass

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
- `$x::make()` â†’ `::scpp::class_t<decltype(x)>::make()`
- `$x::$prop` â†’ `::scpp::class_t<decltype(x)>::prop`
- the generator must not attempt to validate whether the generated C++ member access is semantically valid


## (Added) Global Execution Clarification

For global PHP executable code:

namespace scpp {
	int main() { ... }
}

int main() {
	return scpp::main();
}

Interpolation AST finding:
- interpolated strings are represented as `AST_ENCAPS_LIST`, not as binary concat chains
- generator lowering should join each part in order and cast interpolated non-string values to `string_t` explicitly
- when an interpolated fragment is an expression subtree inside `{...}`, the node must be lowered by the ordinary expression renderer and then wrapped in `cast<string_t>(...)`; interpolation must not introduce a separate expression-lowering path
- precedence is delegated to the AST / normal expression renderer; interpolation only performs string normalization around the rendered expression
- `samples/know_how/` remains the exporter-behavior reference folder for these checks

## Wrapper nesting constraints

- Ownership/value wrappers may not be nested. The following are invalid and must fail generation: `value<value<T>>`, `shared<shared<T>>`, `unique<unique<T>>`, and any mixed wrapper-inside-wrapper form such as `value<shared<T>>`.


### String interpolation limitations

Inside interpolated strings ("..."), only simple expressions are allowed.

Supported:
- `$var`
- `$obj->prop`
- `$arr[index]`
- `$obj->method()`

Not supported:
- arithmetic expressions (`{$a + $b}`)
- ternary expressions (`{$a ? $b : $c}`)
- null coalescing expressions (`{$a ?? $b}`)

This matches PHP behavior.



## Nested table dim support

- Nested table dim reads chain through non-mutating reads so `$x["inner"][0]` lowers via `get(...)` / `_find_val(...)` and does not autovivify the right-hand side.
- Nested table dim writes stay on the mutating path for the full lvalue chain, so `$x[0]["name"] = "first";` lowers through chained `operator[]` access, not through `get(...)` on intermediate segments.
- Nested append on a table-valued slot is supported through chained `operator[]` plus `append(...)` on `mixed_t` / `hash_t<mixed_t>`.
- Table-valued assignments into table slots now use direct `mixed_t` assignment through the mutating container API.
- Reference assignment from a direct DIM slot is not part of the current safe subset.

## Assignment-expression lambda fallback

- Default rule: assignment statements and simple assignment expressions lower directly and do **not** use a helper lambda.
- Example statement: `$x[0]["name"] = "first";` lowers to `x[0]["name"] = "first";`.
- Example simple expression: `$y = ($x[0]["name"] = "first");` lowers to `mixed_t y = (x[0]["name"] = "first");` when native C++ assignment-expression semantics already preserve the assigned value.
- Fallback rule: emit a helper lambda only for complex expression contexts where the generator must guarantee single evaluation and return the assigned value explicitly, especially append expressions or larger composed expressions such as function-call arguments / concatenations that would otherwise duplicate work or lose PHP assignment-value semantics.


## Array argument materialization

- A typed PHP `array` parameter now lowers to `mixed_t` (or `mixed_t&` for explicit PHP `&` when the source expression is otherwise valid under the current safe subset).
- There is no approved `mixed_t` to native typed by-reference normalization rule in the current safe subset.
- Native references bind directly only from already-stable native-reference-bindable sources.
- Any by-reference source rooted in `[]`, dynamic slot/property access, or `.as_*_ref()`-style interior extraction is rejected by design.
- The generator emits a function-entry guard for every `array` / `?array` parameter before user code runs.
  - `array` accepts only table-capable `mixed_t` kinds.
  - `?array` accepts table-capable kinds plus null-kind `mixed_t`.
- Read-only nested argument access uses `get(...)` / `_find_val(...)`; mutating by-value array paths stay on the mutating container API.
- Example: `function touch(array $x): array { $x["name"] = "changed"; return $x; }` lowers to `mixed_t touch(mixed_t x) { expect_array_argument(x, false, "x"); x[string_t("name")] = string_t("changed"); return x; }`.
- See `../../specs/native_reference_safety.md` and `../../specs/references.md`.

## Warning: reassignment of by-value composite params

- Reassigning a by-value parameter of type `array`, `string`, or `vector_t<...>` is supported, but it is **not recommended** for large values.
- Once the function body proves a reassignment or write on that parameter, the emitted C++ signature becomes owning `T x` instead of `const T& x`.
- That means the **entire incoming value is copied at function entry**.
- This can be expensive for large `hash_t`, `string_t`, or `vector_t` values, even if the original incoming value is used only briefly before reassignment.
- Prefer introducing a new local variable instead of overwriting the parameter when avoiding that full copy matters.

## Runtime language target

PHP-target array-key normalization is a runtime concern. The generator must not duplicate numeric-string key normalization logic. Builds used by the project test harness are expected to compile the runtime and generated samples with `-DSCPP_LANGUAGE_TARGET_PHP=1`.



## Direct DIM call arguments

- Direct DIM expressions used as function-call arguments lower through the direct slot path `[]`, not `.get(...)`.
- Example: `add($x[0])` â†’ `add(x[0])`.
- Direct DIM call arguments are valid only for ordinary value passing. They are not native-reference bindable by virtue of being direct DIM expressions.
- Computed expressions keep the normal read path. Example: `$x[0] + 10` remains `x.get(0) + 10` inside the larger expression.
- This is an intentional simplification: direct DIM call arguments may autovivify/create a slot. Use an explicit read-only form such as `?? null` when that behavior is not desired.


## Return-by-reference warnings

- Return-by-reference is not recommended in Prism++ and must always surface a generator warning even when generation is still allowed.
- The generator must also warn for local copy-after-alias patterns rooted in a by-reference call result, for example `$inner =& get_inner($arr); $copy = $arr;`, because Prism++ may not preserve PHP alias semantics for that flow.

## Historical note â€” typed scalar by-reference proxy lowering

The runtime may still contain legacy helper/proxy infrastructure, but that legacy path is not part of the supported safe subset. The current design direction is the native-reference safety rule documented in `../../specs/native_reference_safety.md`.


## PHP runtime relative symbol registry

Generator-emitted calls that are known Prism++ runtime intrinsics may be emitted through a runtime-symbol registry inside `namespace scpp { ... }`. The registry is profile-specific and is stored in `generators/php/specs/php_runtime_symbols_legacy.json` or `generators/php/specs/php_runtime_symbols_strict.json`. Entries are recorded as relative symbol paths under `scpp`, or as visible-to-target mappings relative to `scpp` for strict profile flat names such as `fs_is_file -> fs::is_file`. User-defined functions must not be rewritten through this registry when the generator has already resolved them as user declarations.

Architecture note:

- strict-profile direct emission to shared runtime families is allowed only for symbols declared by the active profile registry
- the registry is the approved bridge between visible/source strict names and shared runtime-family targets


## Runtime Symbol Registry (relative to scpp)

Any runtime function intended to be callable from transpiled PHP code through the registry **must be registered** in the active profile file:

`generators/php/specs/php_runtime_symbols_legacy.json`

`generators/php/specs/php_runtime_symbols_strict.json`

The S2S generator uses this registry to emit the registered relative path directly. For example:

    php::function_name(...)

Or, for strict flat visible names:

    fs_is_file(...)  ->  fs::is_file(...)

### Precedence
User-defined PHP functions take precedence over runtime symbols with the same name.  
The registry is only applied when no user-defined function is resolved. Bare source calls may resolve through the registry by unique tail-name match.

### Important
If a symbol is not present in the registry, the generator will **not** rewrite it through the runtime-symbol registry, even if it exists in the runtime.



## Explicit result_or_false<T> and result<T> type intent

- `result_or_false<T>`, `result_or_bool<T>`, and `result<T>` are explicit type-intent wrappers only; the generator must not infer them from PHP unions automatically in this pass.
- For `result_or_false<bool_t>`, plain `false` remains a wrapped payload value; the explicit false-sentinel forms are `false_sentinel`, `null`, and `nullopt`. The generator must not rewrite a typed `false` payload into the sentinel for this specialization.
- explicit type intent must never silently degrade to `mixed_t`; unsupported or malformed `result_or_false<T>` / `result_or_bool<T>` / `result<T>` syntax is a generator error.
- wrapper lowering uses the canonical mapped inner runtime type, so `result_or_false<MyBox>` lowers to `result_or_false<shared_p<MyBox>>`, `result_or_bool<MyBox>` lowers to `result_or_bool<shared_p<MyBox>>`, while `result<int>` lowers to `result<int_t>`.
- `$result->error()->...` is the supported error-access surface for `result<T>` and must lower to the wrapper method rather than a payload property named `error`.
- `take(...)` is a reserved runtime helper name in the PHP-facing source subset. It lowers to `php::take(...)` when no user-defined function named `take` is resolved.
- `take($value, $source)` is valid only for source expressions typed as `nullable<T>` or `result_or_false<T>`.
- `take($value, $error, $source)` is valid only for source expressions typed as `result<T>`.
- `take($value, $bool, $source)` is valid only for source expressions typed as `result_or_bool<T>`.
- `take(...)` output arguments must be simple local variables in v1. Wrong arity, wrong output type, or a non-wrapper source is a compile-time generator error when the source or output type is known.
- `take(...)` evaluates its source expression exactly once and returns `bool_t`; for `result_or_bool<T>`, the helper returns `true` for both wrapped-value and bool-true states so mysqli-style APIs remain representable.
- `take(...)` is the preferred explicit payload-extraction form for `result*<T>` wrappers because the generator does not perform symbol-resolution-driven wrapper inference.
