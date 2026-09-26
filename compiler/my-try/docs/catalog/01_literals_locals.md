# 01. Literals and local variables
Doc Status: planning

> **Important: LLVM is deferred by default. Work on the direct LLVM backend only when the user explicitly requests it. The v0.2 default scope is Frontend + C++ S2S.**

[Catalog and workflow](README.md) · [Source merge ledger](source_merge.json)

Start here: source spelling, literal values, typed declarations, initialization, reads and reassignment.

Order is a discussion sequence, not a claim that every row is a prerequisite. Split combined examples before implementation.

## Progress

Edit these rows as work proceeds. Imported source support is recorded below, independently of this progress.

| Entry | Status | PHP input example | Frontend | C++ S2S | LLVM | Proof / blocker |
| --- | --- | --- | --- | --- | --- | --- |
| [LIT-INT-001](#lit-int-001) | agreed | `$a = 10;` | proved | proved | deferred | [S2S integer slice](../s2s_integer_slice.md); PHP preparation + Clang execution; compiler-native evidence recorded in handoff |
| [LIT-BOOL-001](#lit-bool-001) | pending-discussion | `$a = true;` | unverified | unverified | deferred | — |
| [LIT-BOOL-002](#lit-bool-002) | pending-discussion | `$a = false;` | unverified | unverified | deferred | — |
| [LIT-FLOAT-001](#lit-float-001) | pending-discussion | `$a = 10.5;` | unverified | unverified | deferred | — |
| [LIT-STR-001](#lit-str-001) | pending-discussion | `$a = 'x';` | unverified | unverified | deferred | — |
| [LIT-STR-002](#lit-str-002) | pending-discussion | `$a = "x";` | unverified | unverified | deferred | — |
| [TYPE-VAR-001](#type-var-001) | pending-discussion | `$x string = "test";` | unverified | unverified | deferred | Strict source adaptation; imported legacy form retained |
| [VAR-ASSIGN-001](#var-assign-001) | pending-discussion | `$a = $b;` | unverified | unverified | deferred | — |
| [VAR-REASSIGN-001](#var-reassign-001) | pending-discussion | `$a = 1; $a = 2;` | unverified | unverified | deferred | — |
| [LIT-STR-003](#lit-str-003) | pending-discussion | `$a = "";` | unverified | unverified | deferred | — |
| [LIT-CONST-001](#lit-const-001) | pending-discussion | `$a = PHP_INT_MAX;` | unverified | unverified | deferred | — |
| [VAR-CHAIN-001](#var-chain-001) | pending-discussion | `$a = $b = 1;` | unverified | unverified | deferred | — |
| [VAR-CHAIN-002](#var-chain-002) | pending-discussion | `$a = 1; $b = $a;` | unverified | unverified | deferred | — |
| [VAR-CHAIN-003](#var-chain-003) | pending-discussion | `$a = 1; $b = $a; $c = $b;` | unverified | unverified | deferred | — |
| [VAR-CHAIN-004](#var-chain-004) | pending-discussion | `$a = 1; $b = $a + 1;` | unverified | unverified | deferred | — |
| [VAR-ORDER-001](#var-order-001) | pending-discussion | `$a = $b; $b = 1;` | unverified | unverified | deferred | — |
| [VAR-REASSIGN-002](#var-reassign-002) | pending-discussion | `$a = 1; $a = $a + 1;` | unverified | unverified | deferred | — |
| [VAR-REASSIGN-003](#var-reassign-003) | pending-discussion | `$a = 1; $a = $a + $a;` | unverified | unverified | deferred | — |
| [IDENT-VAR-001](#ident-var-001) | pending-discussion | `function f(int $int) { $while = $int; }` | unverified | unverified | deferred | — |
| [NOTE-011](#note-011) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-021](#note-021) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-033](#note-033) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-034](#note-034) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-035](#note-035) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |

### Deferred legacy syntax

These syntax-specific entries are retained for traceability, not scheduled before strict-mode features.

| Entry | Status | PHP input example | Frontend | C++ S2S | LLVM | Proof / blocker |
| --- | --- | --- | --- | --- | --- | --- |
| [TYPE-VAR-005](#type-var-005) | deferred-legacy | `/** string */ $x = "test";` | unverified | unverified | deferred | Legacy annotation syntax only; outside strict-first work |
## LIT-INT-001

**v0.2 decision / target C++:** The first assignment establishes a local with the
canonical Simple C++ `int` type inferred from its integer initializer. Preparation
retains the existing binding occurrence as declaration identity without mutating
the AST. The first one-file entry emits:

```cpp
auto local_0 = static_cast<scpp::int_t<>>(10LL);
```

The generated name uses the declaration token position. The current runtime type
is signed 64-bit; the native carrier suffix preserves the supported integer range.
Explicit `int` initialization, copies and reassignment have supporting proofs in
[tests/s2s.php](../../tests/s2s.php). This does not mark other catalog cards complete.
Scope encapsulation and the LANGUAGE+RUNTIME parent are described in the
[slice notes](../s2s_integer_slice.md). JSON import, other literal forms,
composite types, multi-file generation, validation and semantic invalidation are
outside this slice.

**Deferred illustration, not a prerequisite:** Explicit `int_t` versus `auto`,
with or without a literal cast, may be compared later if profiling justifies it.
A one-off Clang comparison is saved in [timing evidence](../../../../specs/planning/results/int_literal_clang_2026_09_25/README.md). It establishes no project-build gain; further spelling optimization is deferred.
Prioritize modular output and reduced recompilation under the
[catalog guidance](README.md#design-c-for-efficient-native-compilation).

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:30](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = 10;
```

**Existing C++ lowering / result**

```cpp
auto a = static_cast<int_t>(10);
```

**Category:** Literal

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** literal is integer; first assignment in scope

**Normalized pattern:** `<var> = <int-literal>`

**General rule:** Integer literals must be converted using `static_cast<int_t>(value)` and assigned using `auto` on first assignment in scope.

**Diagnostics:** Error if raw integer literal is emitted without cast.


## LIT-BOOL-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:32](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = true;
```

**Existing C++ lowering / result**

```cpp
auto a = static_cast<bool_t>(true);
```

**Category:** Literal

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** literal is boolean; first assignment in scope

**Normalized pattern:** `<var> = <bool-literal>`

**General rule:** Boolean literals must be converted using `static_cast<bool_t>(value)` and assigned using `auto` on first assignment in scope.

**Diagnostics:** Error if raw boolean literal is emitted without cast.

**Notes:** Covers `true` and `false`; duplicate bool rows should be collapsed.


## LIT-BOOL-002

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:33](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = false;
```

**Existing C++ lowering / result**

```cpp
auto a = static_cast<bool_t>(false);
```

**Category:** Literal

**Rule kind:** generation

**Source support status:** covered-by-LIT-BOOL-001

**Preconditions:** same as LIT-BOOL-001

**Normalized pattern:** `<var> = <bool-literal>`

**General rule:** Same generalized rule as LIT-BOOL-001.

**Notes:** Redundant seed row kept only for traceability.


## LIT-FLOAT-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:31](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = 10.5;
```

**Existing C++ lowering / result**

```cpp
auto a = static_cast<float_t>(10.5);
```

**Category:** Literal

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** literal is floating-point; first assignment in scope

**Normalized pattern:** `<var> = <float-literal>`

**General rule:** Float literals must be converted using `static_cast<float_t>(value)` and assigned using `auto` on first assignment in scope.

**Diagnostics:** Error if raw float literal is emitted without cast.


## LIT-STR-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:35](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = 'x';
```

**Existing C++ lowering / result**

```cpp
auto a = string_t("x");
```

**Category:** Literal

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** valid PHP string literal; first assignment in scope

**Normalized pattern:** `<var> = <string-literal>`

**General rule:** PHP string literals must first be normalized into a valid C++ string literal, then materialized as `string_t("...")`, and assigned using `auto` on first assignment in scope.

**Diagnostics:** Error if string is emitted without `string_t(...)`; error if normalization is missing.

**Notes:** Generator must implement PHP string normalization/conversion.


## LIT-STR-002

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:36](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = "x";
```

**Existing C++ lowering / result**

```cpp
auto a = string_t("x");
```

**Category:** Literal

**Rule kind:** generation

**Source support status:** covered-by-LIT-STR-001

**Preconditions:** same as LIT-STR-001

**Normalized pattern:** `<var> = <string-literal>`

**General rule:** Same generalized rule as LIT-STR-001.

**Notes:** Covers double-quoted string without interpolation.


## TYPE-VAR-001

**Strict-mode PHP input example:** `$x string = "test";`

This is the working source spelling. Imported examples and C++ expectations below remain reference material; the v0.2 result still needs agreement and proof.

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:322](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$x /** string */ = "test";
```

**Existing C++ lowering / result**

```cpp
string_t x("test");
```

**Category:** Type system

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** explicit inline typed-slot local annotation present

**Normalized pattern:** `typed local variable`

**General rule:** Explicit inline typed-slot local annotations are authoritative for local variables when present.

**Diagnostics:** Error only if the explicit type form itself is unsupported.

**Notes:** Type compatibility is left to the C++ compiler unless a generation rule requires local rejection.


## VAR-ASSIGN-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:40](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = $b;
```

**Existing C++ lowering / result**

```cpp
auto a = b;
```

**Category:** Variable

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** source variable already declared in scope

**Normalized pattern:** `<var> = <var>`

**General rule:** PHP variables are mapped to native C++ variables by removing the `$` prefix. Variable assignment generates direct C++ assignment.

**Diagnostics:** Error if source variable is used before declaration.


## VAR-REASSIGN-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:41](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = 1; $a = 2;
```

**Existing C++ lowering / result**

```cpp
auto a = static_cast<int_t>(1); a = static_cast<int_t>(2);
```

**Category:** Variable

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** same variable in same scope; second assignment is reassignment

**Normalized pattern:** `<var-decl>; <var-reassign>`

**General rule:** On first assignment in a scope, declare with `auto`. On later assignment to the same variable in the same scope, omit `auto`. Literals still follow normal literal conversion rules.

**Diagnostics:** Error if reassignment violates scope rules.


## LIT-STR-003

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:37](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = "";
```

**Existing C++ lowering / result**

```cpp
auto a = string_t("");
```

**Category:** Literal

**Rule kind:** generation

**Source support status:** covered-by-LIT-STR-001

**Preconditions:** same as LIT-STR-001

**Normalized pattern:** `<var> = <string-literal>`

**General rule:** Same generalized rule as LIT-STR-001.

**Notes:** Empty string stays under the same generalized string-literal rule.


## LIT-CONST-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:38](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = PHP_INT_MAX;
```

**Existing C++ lowering / result**

```cpp
auto a = PHP_INT_MAX;
```

**Category:** Constant

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** generator startup snapshot from `get_defined_constants()` contains the resolved constant name; first assignment in scope

**Normalized pattern:** `<var> = <predefined-const>`

**General rule:** Predefined/runtime constants discovered from `get_defined_constants()` at generator startup must lower to unqualified helper/constant names inside generated source because the source namespace block already uses `using namespace ::scpp;``. Generator-emitted runtime/helper references inside generated expression/type code must not use rooted `::scpp` / `::scpp::php` qualifiers. User-defined constants do not use this rule.

**Diagnostics:** Emit an error only if resolution/classification fails; do not guess that an unknown constant is predefined.

**Notes:** Classification depends on the PHP runtime/version executing the generator, so the target/test PHP version must stay aligned.


## VAR-CHAIN-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:42](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = $b = 1;
```

**Existing C++ lowering / result**

```cpp
auto b = static_cast<int_t>(1); auto a = b;
```

**Category:** Variable

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** chain introduces `b` by first assignment in scope

**Normalized pattern:** `<var> = (<var> = <expr>)`

**General rule:** Chained assignments must be decomposed into sequential statements. The inner assignment is evaluated first and follows normal declaration rules. The outer assignment assigns the resulting value.

**Diagnostics:** Error if generator emits chained assignment directly without normalization.


## VAR-CHAIN-002

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:43](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = 1; $b = $a;
```

**Existing C++ lowering / result**

```cpp
auto a = static_cast<int_t>(1); auto b = a;
```

**Category:** Variable

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** `a` declared before second statement; `b` first assignment in scope

**Normalized pattern:** `<var> = <expr>; <var> = <var>;`

**General rule:** Sequential assignments must respect declaration rules per statement. Each first assignment in scope introduces a new variable using `auto`.

**Diagnostics:** Error if source variable is used before declaration, literal not normalized, or `auto` omitted on first assignment.


## VAR-CHAIN-003

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:44](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = 1; $b = $a; $c = $b;
```

**Existing C++ lowering / result**

```cpp
auto a = static_cast<int_t>(1); auto b = a; auto c = b;
```

**Category:** Variable

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** each source variable declared before use

**Normalized pattern:** `<var> = <expr>; <var> = <var>; <var> = <var>;`

**General rule:** Sequential assignments are emitted in order. Each first assignment in scope declares the target with `auto`. Variable references use the normalized C++ identifier form.

**Diagnostics:** Error if a source variable is used before declaration or if a literal is not normalized.


## VAR-CHAIN-004

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:45](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = 1; $b = $a + 1;
```

**Existing C++ lowering / result**

```cpp
auto a = static_cast<int_t>(1); auto b = a + static_cast<int_t>(1);
```

**Category:** Variable

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** source variables declared before use; expression type-compatible

**Normalized pattern:** `<var> = <expr>; <var> = <expr>;`

**General rule:** When assigning from an expression, the expression must be fully normalized, and the target variable is declared with `auto` if it is the first assignment in scope.

**Diagnostics:** Error if variable used before declaration or literal not normalized.


## VAR-ORDER-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:46](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = $b; $b = 1;
```

**Existing C++ lowering / result**

```cpp
auto a = b; b = static_cast<int_t>(1);
```

**Category:** Variable

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** `b` must already be declared before the first statement

**Normalized pattern:** `<var> = <var>; <var> = <expr>;`

**General rule:** A variable may be used only if it is already declared in an accessible scope at that point. The generator must not reorder statements.

**Diagnostics:** Error if `b` is not already declared before first use.

**Notes:** No hoisting / no reordering.


## VAR-REASSIGN-002

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:47](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = 1; $a = $a + 1;
```

**Existing C++ lowering / result**

```cpp
auto a = static_cast<int_t>(1); a = a + static_cast<int_t>(1);
```

**Category:** Variable

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** variable already declared; expression type-compatible

**Normalized pattern:** `<var> = <expr>; <var> = <expr>;`

**General rule:** Reassignment must not use `auto`. The right-hand side must be fully normalized according to expression rules.

**Diagnostics:** Error if `auto` is used on reassignment, variable used before declaration, or literals not normalized.


## VAR-REASSIGN-003

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:48](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = 1; $a = $a + $a;
```

**Existing C++ lowering / result**

```cpp
auto a = static_cast<int_t>(1); a = a + a;
```

**Category:** Variable

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** variable already declared; operands type-compatible

**Normalized pattern:** `<var> = <expr>; <var> = <expr>;`

**General rule:** Reassignment expressions involving only variables do not require additional normalization beyond initial literal normalization.

**Diagnostics:** Error if variable used before declaration or initial literal not normalized.


## IDENT-VAR-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:243](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(int $int) { $while = $int; }
```

**Existing C++ lowering / result**

```cpp
void f(int_t int__) { auto while__ = int__; }
```

**Category:** Naming

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** generated C++ identifier must not collide with a reserved C++ keyword and must avoid local collisions in the same function-like scope

**Normalized pattern:** PHP variable identifier lowering

**General rule:** PHP variable names are preserved unless the raw name is a reserved C++ keyword. Reserved keyword names lower deterministically to `<name>__`, and if that candidate is already used in the same function-like scope the generator must continue with `<name>__1`, `<name>__2`, and so on until a free identifier is found.

**Diagnostics:** Apply the same remapped identifier consistently in declarations, headers, source definitions, and all local uses.

**Notes:** Applies to locals, parameters, and synthesized names that participate in the function-like variable map.


## TYPE-VAR-005

**Scope:** Legacy annotation syntax; deferred. This is not a strict-mode implementation prerequisite.

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:354](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
/** string */ $x = "test";
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Type system

**Rule kind:** rejection

**Source support status:** rejected

**Preconditions:** explicit local variable typing is requested, but the type comment is not placed immediately after the variable token

**Normalized pattern:** non-immediate local type comment

**General rule:** Explicit local variable typing is supported only in the strict immediate-after-variable form such as `$x /** string */ = "test";`. Detached, leading, or post-initializer type comments are rejected.

**Diagnostics:** Emit an error when the local type comment is not attached in the supported immediate form.

**Notes:** This keeps token-based local type extraction simple and deterministic.


## NOTE-011

**Source:** [generators/php/specs/rules.md:153](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## 3. Literal Normalization
>
> All literals must be normalized.
>
> ### Required forms
> - integer â†’ `static_cast<int_t>(v)`
> - float â†’ `static_cast<float_t>(v)`
> - bool â†’ `static_cast<bool_t>(v)`
> - string â†’ `string_t("...")`
>
> Applies to:
> - assignments
> - expressions
> - returns
> - function arguments
> - default values
>
> ---

## NOTE-021

**Source:** [generators/php/specs/rules.md:308](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## 11A. Variable naming normalization
>
> - PHP variable names are preserved unless the raw name is a reserved C++ keyword
> - reserved keyword names lower to `<name>__`
> - if that candidate already exists in the same function-like scope, the generator must try `<name>__1`, `<name>__2`, and so on until a free identifier is found
> - the chosen remapped identifier must be used consistently in declarations, headers, source definitions, helpers, and all uses within that function-like scope
>
> ---

## NOTE-033

**Source:** [generators/php/specs/rules.md:629](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## 4. Scope model
>
> A scope is:
> - a function body
> - a namespace body
> - the global namespace body
>
> This rule is used for first-assignment / `auto` decisions.

## NOTE-034

**Source:** [generators/php/specs/rules.md:638](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## 5. Variable model
>
> - PHP variables map to native C++ identifiers by removing the `$` prefix.
> - Example: `$a` -> `a`
> - First assignment in the current scope -> declare with `auto`
> - Reassignment in the same scope -> no `auto`
>
> Examples:
> ```cpp
> auto a = static_cast<int_t>(1);
> a = static_cast<int_t>(2);
> ```

## NOTE-035

**Source:** [generators/php/specs/rules.md:651](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## 6. Global literal normalization rule
>
> **All literals must always be converted to runtime-compatible C++ forms. No exceptions.**
>
> This applies:
> - in assignments
> - in expressions
> - in returns
> - in function arguments
> - in conditions
> - condition lowering must use `static_cast<bool>(...)` for expressions already known to produce `bool_t`
> - condition lowering must use `php::condition_truthy(...)` plus `static_cast<bool>(...)` for non-`bool_t` expressions that are allowed to enter control flow; `mixed_t` is only valid when its runtime payload is bool/int/float
> - in branch bodies
> - in loop bodies
>
> ### 6.1 Primitive literal normalization
> - `int` -> `static_cast<int_t>(...)`
> - `float` -> `static_cast<float_t>(...)`
> - `bool` -> `static_cast<bool_t>(...)`
>
> Examples:
> ```cpp
> auto a = static_cast<int_t>(10);
> auto a = static_cast<float_t>(10.5);
> auto a = static_cast<bool_t>(true);
> ```
>
> ### 6.2 String literal normalization
> PHP string literals must first be normalized into valid C++ string literals, then materialized as `string_t("...")`.
>
> Examples:
> ```cpp
> auto a = string_t("x");
> auto a = string_t("");
> ```
>
> ### 6.3 String restriction
> Never emit:
> ```cpp
> static_cast<string_t>(...)
> ```
>
> Always emit:
> ```cpp
> string_t(...)
> ```
>
> ### 6.4 Constant normalization
> The generator snapshots `get_defined_constants()` once at startup. Inside generated source namespace blocks, predefined/runtime constants lower to unqualified names because the source already uses `using namespace ::scpp;``. Generator-emitted runtime/helper references inside generated expression/type code MUST NOT use rooted `::scpp` or `::scpp::php` qualifiers; the only allowed rooted occurrences are the generated using-directives themselves and explicit import-lowering forms such as `use` declarations. User-defined constants stay in the generated user namespace model.
>
> Examples:
> ```cpp
> auto a = PHP_INT_MAX;                // inside generated `.cpp` namespace blocks with `using namespace ::scpp;`
> auto c = LIMIT;                      // user-defined constant in the current generated namespace
> auto d = A::B::LIMIT;                // user-defined constant in another generated namespace
> ```
