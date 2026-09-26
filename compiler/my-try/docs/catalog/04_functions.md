# 04. Functions, parameters and calls
Doc Status: planning

> **Important: LLVM is deferred by default. Work on the direct LLVM backend only when the user explicitly requests it. The v0.2 default scope is Frontend + C++ S2S.**

[Catalog and workflow](README.md) · [Source merge ledger](source_merge.json)

Requires locals and expressions. Begin with a no-argument function, then returns, scalar parameters and calls; split composite examples.

Order is a discussion sequence, not a claim that every row is a prerequisite. Split combined examples before implementation.

## Progress

Edit these rows as work proceeds. Imported source support is recorded below, independently of this progress.

| Entry | Status | PHP input example | Frontend | C++ S2S | LLVM | Proof / blocker |
| --- | --- | --- | --- | --- | --- | --- |
| [FUNC-DECL-001](#func-decl-001) | pending-discussion | `function f() { }` | unverified | unverified | deferred | — |
| [FUNC-VOID-001](#func-void-001) | pending-discussion | `function f(): void { return; }` | unverified | unverified | deferred | — |
| [FUNC-DECL-002](#func-decl-002) | pending-discussion | `function f(): int { return 1; }` | unverified | unverified | deferred | — |
| [FUNC-RETURN-001](#func-return-001) | pending-discussion | `return;` | unverified | unverified | deferred | — |
| [FUNC-DECL-003](#func-decl-003) | pending-discussion | `function f(int $a): int { return $a; }` | unverified | unverified | deferred | — |
| [TYPE-PARAM-001](#type-param-001) | pending-discussion | `function f(int $a, float $b, bool $c): void {}` | unverified | unverified | deferred | — |
| [FUNC-CALL-001](#func-call-001) | pending-discussion | `f();` | unverified | unverified | deferred | — |
| [FUNC-CALL-002](#func-call-002) | pending-discussion | `f($a);` | unverified | unverified | deferred | — |
| [FUNC-DECL-004](#func-decl-004) | pending-discussion | `function f(int $a, string $b): int { return $a; }` | unverified | unverified | deferred | — |
| [FUNC-DEFAULT-001](#func-default-001) | pending-discussion | `function f(int $a = 1): int { return $a; }` | unverified | unverified | deferred | — |
| [FUNC-RECURSION-001](#func-recursion-001) | pending-discussion | `function f(int $a): int { return f($a - 1); }` | unverified | unverified | deferred | — |
| [FUNC-OVERLOAD-001](#func-overload-001) | pending-discussion | `function f(int $a): int {} function f(string $a): int {}` | unverified | unverified | deferred | — |
| [FUNC-DECL-005](#func-decl-005) | pending-discussion | `function f($a) { return $a; }` | unverified | unverified | deferred | — |
| [FUNC-RETURN-002](#func-return-002) | pending-discussion | `return $a;` | unverified | unverified | deferred | — |
| [FUNC-RETURN-003](#func-return-003) | pending-discussion | `return $a + 1;` | unverified | unverified | deferred | — |
| [FUNC-CALL-003](#func-call-003) | pending-discussion | `f($a, 1, "x", true);` | unverified | unverified | deferred | — |
| [FUNC-CALL-004](#func-call-004) | pending-discussion | `sum_all(1, 2, 3);` | unverified | unverified | deferred | — |
| [SCOPE-GLOBAL-001](#scope-global-001) | pending-discussion | `$a = 1; function f(): int { return $a; }` | unverified | unverified | deferred | — |
| [SCOPE-LOCAL-001](#scope-local-001) | pending-discussion | `function f(): void { $a = 1; $b = $a; }` | unverified | unverified | deferred | — |
| [SCOPE-SHADOW-001](#scope-shadow-001) | pending-discussion | `$a = 1; function f(): void { $a = 2; }` | unverified | unverified | deferred | — |
| [TYPE-PARAM-003D](#type-param-003d) | pending-discussion | `function f(string $s): void { $s .= "x"; }` | unverified | unverified | deferred | — |
| [NOTE-016](#note-016) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-039](#note-039) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-040](#note-040) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
## FUNC-DECL-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:137](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f() { }
```

**Existing C++ lowering / result**

```cpp
void f() { }
```

**Category:** Function

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** function has no declared return type

**Normalized pattern:** `function <name>() <block>`

**General rule:** If no return type is specified in PHP, the function must be generated as `void` in C++.

**Diagnostics:** Error if a `return <expr>` exists inside a function without a declared return type; `return;` is allowed.

**Notes:** This is stricter than PHP.


## FUNC-VOID-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:145](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(): void { return; }
```

**Existing C++ lowering / result**

```cpp
void f() { return; }
```

**Category:** Function

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** function return type is `void`

**Normalized pattern:** `function <name>(): void <block>`

**General rule:** PHP `void` functions map directly to C++ `void` functions. A bare `return;` is emitted directly.

**Diagnostics:** Error if a `void` function returns a value.


## FUNC-DECL-002

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:138](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(): int { return 1; }
```

**Existing C++ lowering / result**

```cpp
int_t f() { return static_cast<int_t>(1); }
```

**Category:** Function

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** supported return type; all paths return value

**Normalized pattern:** `function <name>(): <type> <block>`

**General rule:** PHP typed return functions must be mapped to the corresponding `_t` type in C++. All returned expressions must be normalized.

**Diagnostics:** Error if return expression is incompatible, literal not normalized, or function does not return a value on all paths.


## FUNC-RETURN-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:146](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
return;
```

**Existing C++ lowering / result**

```cpp
return;
```

**Category:** Function

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** inside function; valid in current return context

**Normalized pattern:** `return`

**General rule:** `return;` is emitted directly. It represents a void return.

**Diagnostics:** Error if used outside a function or in a non-void function where a value is required.


## FUNC-DECL-003

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:139](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(int $a): int { return $a; }
```

**Existing C++ lowering / result**

```cpp
int_t f(int_t a) { return a; }
```

**Category:** Function

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** parameter and return type supported; returned expression compatible

**Normalized pattern:** `function <name>(<typed-param>): <type> <block>`

**General rule:** PHP typed parameters and typed returns map to their Prism++ runtime types. Returning a variable of the declared type is emitted directly.

**Diagnostics:** Error if parameter/return type unsupported or returned expression incompatible.


## TYPE-PARAM-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:329](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(int $a, float $b, bool $c): void {}
```

**Existing C++ lowering / result**

```cpp
void_t f(int_t a, float_t b, bool_t c) {}
```

**Category:** Type system

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** parameter types are explicit and supported

**Normalized pattern:** `primitive parameters`

**General rule:** `int_t`, `float_t`, and `bool_t` parameters are emitted by value unless explicit `&` is present.

**Diagnostics:** Error if an unsupported primitive parameter form is requested.

**Notes:** The same value rule applies to returns unless explicit `&` is present.


## FUNC-CALL-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:149](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
f();
```

**Existing C++ lowering / result**

```cpp
f();
```

**Category:** Function

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** function declared or available in scope

**Normalized pattern:** `<name>()`

**General rule:** Function calls without arguments are emitted directly.

**Diagnostics:** Error if function is not declared.


## FUNC-CALL-002

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:150](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
f($a);
```

**Existing C++ lowering / result**

```cpp
f(a);
```

**Category:** Function

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** function declared; argument expression valid and in scope

**Normalized pattern:** `<name>(<expr>)`

**General rule:** Function calls with arguments are emitted directly, with all arguments normalized beforehand.

**Diagnostics:** Error if function not declared, argument uses undeclared variables, or argument type incompatible.


## FUNC-DECL-004

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:141](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(int $a, string $b): int { return $a; }
```

**Existing C++ lowering / result**

```cpp
int_t f(int_t a, string_t b) { return a; }
```

**Category:** Function

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** all parameter types supported; return type supported

**Normalized pattern:** `function <name>(<typed-param>, <typed-param>): <type> <block>`

**General rule:** Multiple typed parameters are mapped positionally to their corresponding `_t` types in C++. Return type mapping follows the same rules.

**Diagnostics:** Error if any parameter type unsupported, return type unsupported, or return expression incompatible.


## FUNC-DEFAULT-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:143](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(int $a = 1): int { return $a; }
```

**Existing C++ lowering / result**

```cpp
int_t f(int_t a = static_cast<int_t>(1)) { return a; }
```

**Category:** Function

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** parameter type supported; default expression compatible

**Normalized pattern:** `function <name>(<param> = <expr>): <type> <block>`

**General rule:** Default parameter values must be preserved and normalized according to literal rules. The parameter type must be explicit.

**Diagnostics:** Error if default value incompatible with parameter type or default literal not normalized.


## FUNC-RECURSION-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:161](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(int $a): int { return f($a - 1); }
```

**Existing C++ lowering / result**

_Not supplied in source._

**Category:** Function

**Rule kind:** generation

**Normalized pattern:** `recursive function call`


## FUNC-OVERLOAD-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:106](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:269](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(int $a): int {} function f(string $a): int {}
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Function

**Rule kind:** rejection

**Source support status:** rejected

**Preconditions:** same function namespace/scope

**Normalized pattern:** same-name multiple free functions

**General rule:** Function overloading is forbidden by Prism++ design.

**Diagnostics:** Emit an error on duplicate function names regardless of parameter differences.

**Notes:** Mirrors the method rule.


## FUNC-DECL-005

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:142](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f($a) { return $a; }
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Function

**Rule kind:** rejection

**Source support status:** rejected

**Normalized pattern:** `function <name>(<untyped-param>) <block>`

**General rule:** Function parameters must have explicit types. Untyped parameters are not allowed.

**Diagnostics:** Error if any parameter is missing a type.

**Notes:** No implicit parameter typing.


## FUNC-RETURN-002

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:147](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
return $a;
```

**Existing C++ lowering / result**

```cpp
return a;
```

**Category:** Function

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** inside non-void function; `a` declared; type-compatible

**Normalized pattern:** `return <expr>`

**General rule:** Returning a variable is emitted directly, provided it is already normalized and type-compatible with the functionâ€™s declared return type.

**Diagnostics:** Error if used outside function, in `void` function, variable undeclared, or type incompatible.


## FUNC-RETURN-003

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:148](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
return $a + 1;
```

**Existing C++ lowering / result**

```cpp
return a + static_cast<int_t>(1);
```

**Category:** Function

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** inside non-void function; expression type-compatible

**Normalized pattern:** `return <expr>`

**General rule:** Return expressions must follow full expression normalization rules. Any literal inside the expression must be converted using the appropriate `static_cast<..._t>(...)`.

**Diagnostics:** Error if literal not normalized, expression type incompatible, or used in a `void` function.


## FUNC-CALL-003

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:151](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
f($a, 1, "x", true);
```

**Existing C++ lowering / result**

```cpp
f(a, static_cast<int_t>(1), string_t("x"), static_cast<bool_t>(true));
```

**Category:** Function

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** function declared; all arguments valid expressions

**Normalized pattern:** `<name>(<expr>, <expr>, ...)`

**General rule:** All function call arguments must be individually normalized before emission. Literals must follow their respective conversion rules.

**Diagnostics:** Error if any literal not normalized, argument type incompatible, or function not declared.


## FUNC-CALL-004

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:152](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
sum_all(1, 2, 3);
```

**Existing C++ lowering / result**

```cpp
sum_all(::scpp::vector_t<int_t>{static_cast<int_t>(1), static_cast<int_t>(2), static_cast<int_t>(3)});
```

**Category:** Function

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** callee signature is known; trailing variadic parameter type is explicit and supported

**Normalized pattern:** call to typed trailing variadic

**General rule:** Calls to typed trailing variadic functions/methods are lowered by packing the variadic tail into a `vector_t<T>` temporary.

**Diagnostics:** Error if the callee variadic element type is unknown/unsupported, or if an untyped variadic would be required.

**Notes:** Non-variadic leading parameters keep their normal positional lowering.


## SCOPE-GLOBAL-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:163](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = 1; function f(): int { return $a; }
```

**Existing C++ lowering / result**

_Not supplied in source._

**Category:** Scope

**Rule kind:** generation

**Normalized pattern:** `global scope + function scope`


## SCOPE-LOCAL-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:164](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(): void { $a = 1; $b = $a; }
```

**Existing C++ lowering / result**

_Not supplied in source._

**Category:** Scope

**Rule kind:** generation

**Normalized pattern:** `local scope variable declaration and use`


## SCOPE-SHADOW-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:165](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = 1; function f(): void { $a = 2; }
```

**Existing C++ lowering / result**

_Not supplied in source._

**Category:** Scope

**Rule kind:** rejection-or-generation

**Normalized pattern:** `name reuse across scopes`


## TYPE-PARAM-003D

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:335](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(string $s): void { $s .= "x"; }
```

**Existing C++ lowering / result**

```cpp
void_t f(string_t s) { s += string_t("x"); }
```

**Category:** Type system

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** parameter type is by-value `string`/`vector_t` and the body proves a write or reassignment on the parameter root

**Normalized pattern:** `string/vector parameter owned-local convention`

**General rule:** The same two-mode rule applies to `string_t` and `vector_t`: proven writes or reassignment require owning pass-by-value `T x`; otherwise read-only params stay `const T&`.

**Diagnostics:** Error if the generator keeps such a param as `const &`.

**Notes:** Reassignment is supported but can be expensive for large values.


## NOTE-016

**Source:** [generators/php/specs/rules.md:234](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## 8. Functions
>
> - typed parameters are mandatory
> - explicit return types are mandatory
> - default values must be normalized
> - nullable types must be emitted as `nullable<T>` for nullable value types
> - references are supported for functions and methods when explicit in source
> - reference semantics are emitted literally and are never inferred
> - default parameter values are allowed and belong to declarations only
> - primary-type normalized PHP union parameters are supported by design; see `primary_type_normalized_parameters.md`
> - for a union parameter, the first listed type is the primary type and later types are secondary source types
> - current generator lowering extracts the primary type as the emitted callable-body type while annotation parsing and validation are handled centrally
>
> ---

## NOTE-039

**Source:** [generators/php/specs/rules.md:788](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## 10. Return rules
>
> ### 10.1 Missing declared return type
> - Missing return type -> `auto`
>
> ### 10.2 Primitive-like returns
> Return by value for:
> - `int_t`
> - `float_t`
> - `bool_t`
> - `nullable<int_t>`
> - `nullable<float_t>`
> - `nullable<bool_t>`
>
> ### 10.3 Heavy / wrapper returns
> Return by `const &` only when the returned expression is clearly an existing stable object/reference.
>
> Examples:
> ```cpp
> const string_t& f(const string_t& a) { return a; }
> const nullable<string_t>& f(const nullable<string_t>& a) { return a; }
> ```
>
> Return by value for:
> - literals
> - temporary objects
> - computed expressions
> - concatenations
> - function call results
> - any return expression whose lifetime safety is not explicitly known
>
> Examples:
> ```cpp
> string_t f() { return string_t("x"); }
> string_t f(const string_t& a) { return a + string_t("x"); }
> string_t f() { return func_in_another_file(); }
> nullable<string_t> f(const nullable<string_t>& a) { return null; }
> ```

## NOTE-040

**Source:** [generators/php/specs/rules.md:827](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## 11. Function declaration rules
>
> ### 11.1 Parameters
> - Function and method parameters must have explicit types
> - Missing parameter type -> error
> - Parameter type fallback to `auto` is forbidden
> - If both a native PHP type and a supported doc-comment type are present, emit an error
>
> ### 11.2 Representative forms
> ```cpp
> int_t f(int_t a) { return a; }
> const string_t& f(const string_t& a) { return a; }
> string_t f() { return string_t("x"); }
> ```
