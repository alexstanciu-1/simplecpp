# 12. Closures and callable values
Doc Status: planning

> **Important: LLVM is deferred by default. Work on the direct LLVM backend only when the user explicitly requests it. The v0.2 default scope is Frontend + C++ S2S.**

[Catalog and workflow](README.md) · [Source merge ledger](source_merge.json)

Requires functions, scope resolution and lifetime. Begin with no captures, then value/reference capture.

Order is a discussion sequence, not a claim that every row is a prerequisite. Split combined examples before implementation.

## Progress

Edit these rows as work proceeds. Imported source support is recorded below, independently of this progress.

| Entry | Status | PHP input example | Frontend | C++ S2S | LLVM | Proof / blocker |
| --- | --- | --- | --- | --- | --- | --- |
| [CLOSURE-USE-001](#closure-use-001) | pending-discussion | `$f function<int()> = function () use ($a): int { return $a; };`<br>`$f = function () use ($a) { return $a; };` | unverified | unverified | deferred | Source variants need reconciliation |
| [CLOSURE-USE-002](#closure-use-002) | pending-discussion | `$f function<int()> = function () use (&$a): int { return $a; };` | unverified | unverified | deferred | — |
| [CLOSURE-CALLABLE-001](#closure-callable-001) | pending-discussion | `$f function<int()> = function () use ($a) { return $a; };` | unverified | unverified | deferred | — |
| [CLOSURE-CALLABLE-002](#closure-callable-002) | pending-discussion | `$add = function (int $x, int $y) use ($base): int { return $base + $x + $y; };` | unverified | unverified | deferred | — |
| [CLOSURE-RETURN-001](#closure-return-001) | pending-discussion | `$f = function () use ($a): vector<int> { return [$a, 2]; };` | unverified | unverified | deferred | — |
| [CLOSURE-REJECT-001](#closure-reject-001) | pending-discussion | `$f = function () use ($a) { return $a; };` | unverified | unverified | deferred | — |
| [CLOSURE-REJECT-002](#closure-reject-002) | pending-discussion | `$items[] = function (): int { return 1; };` | unverified | unverified | deferred | — |
| [CLOSURE-STAN-001](#closure-stan-001) | pending-discussion | `$f function<string()> = function (): int { return 1; };` | unverified | unverified | deferred | — |
| [CLOSURE-001](#closure-001) | pending-discussion | `$f = function (int $a): int { if ($a === 1) { return 2; } return $a; };` | unverified | unverified | deferred | — |
| [CLOSURE-CALL-001](#closure-call-001) | pending-discussion | `$f(); $g(3); $h(3); $h(3, 9);` | unverified | unverified | deferred | — |
| [SCOPE-VAR-002](#scope-var-002) | pending-discussion | `if (true) { $g = function (): int { return 1; }; } return $g();` | unverified | unverified | deferred | — |
| [SCOPE-VAR-003](#scope-var-003) | pending-discussion | `$f function<int(int)>; if (true) { $f = function (int $x): int { return $x + 1; }; } echo $f(5);` | unverified | unverified | deferred | Strict source adaptation; imported legacy form retained |
| [ARROW-001](#arrow-001) | pending-discussion | `$a = 10; $f = fn(int $x): int => $x + $a;` | unverified | unverified | deferred | — |
## CLOSURE-USE-001

**v0.2 decision / target C++:** Pending discussion.

Different source versions share this ID. Preserve both until their differences are discussed; neither is silently selected as the v0.2 decision.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:153](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$f function<int()> = function () use ($a): int { return $a; };
```

**Existing C++ lowering / result**

```cpp
std::function<int_t()> f = [a]() -> int_t { return a; };
```

**Category:** Closure

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** target local has explicit `function<...>` type; closure captures simple local variables

**Normalized pattern:** typed callable local with closure `use` capture

**General rule:** Explicit strict callable locals lower to `std::function<...>` storage. Closure `use ($a)` lowers to a by-value C++ lambda capture at closure creation time.

**Diagnostics:** Error if the capture is not a simple visible local variable or if the closure cannot be rendered as a concrete callable initializer.

**Notes:** Callable locals are concrete typed values, not `mixed_t`.

### Imported version 2

**Source:** [generators/php/specs/rules_catalog.md:314](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$f = function () use ($a) { return $a; };
```

**Existing C++ lowering / result**

```cpp
auto f = [a]() { return a; };
```

**Category:** Closure

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** every captured variable uses by-value capture syntax

**Normalized pattern:** `closure with use capture`

**General rule:** `use ($x, $y)` lowers to native lambda capture-by-value. The current pass copies the lowered local variables into the lambda capture list and preserves those locals inside the closure body.

**Diagnostics:** Emit an error for `use (&$x)` or other unsupported capture shapes.

**Notes:** This pass intentionally excludes PHP reference-capture semantics.


## CLOSURE-USE-002

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:154](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$f function<int()> = function () use (&$a): int { return $a; };
```

**Existing C++ lowering / result**

```cpp
std::function<int_t()> f = [&a]() -> int_t { return a; };
```

**Category:** Closure

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** target local has explicit `function<...>` type; capture is explicitly by reference

**Normalized pattern:** closure `use` reference capture

**General rule:** Closure `use (&$a)` lowers to a by-reference C++ lambda capture so later writes are visible.

**Diagnostics:** Error if the referenced capture target is not a simple visible local variable.

**Notes:** This mirrors regular PHP capture intent within the current local callable model.


## CLOSURE-CALLABLE-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:155](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$f function<int()> = function () use ($a) { return $a; };
```

**Existing C++ lowering / result**

```cpp
std::function<int_t()> f = [a]() -> int_t { return a; };
```

**Category:** Closure

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** target local has explicit complete `function<...>` type

**Normalized pattern:** callable local supplies closure signature

**General rule:** When a closure initializer omits its return type, an explicit callable local type may provide the expected return and parameter signature for lowering.

**Diagnostics:** Error if no expected callable signature exists and the closure contains a value return without an explicit closure return type.

**Notes:** The generator does not infer return types by inspecting return expressions.


## CLOSURE-CALLABLE-002

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:156](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$add = function (int $x, int $y) use ($base): int { return $base + $x + $y; };
```

**Existing C++ lowering / result**

```cpp
std::function<int_t(int_t, int_t)> add = [base](int_t x, int_t y) -> int_t { return base + x + y; };
```

**Category:** Closure

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** direct local assignment from closure with explicit parameter and return signature

**Normalized pattern:** direct closure signature synthesis

**General rule:** A direct local assignment from a closure with a complete syntactic signature may synthesize the local `std::function<Return(Params...)>` storage type.

**Diagnostics:** Error if the closure signature is incomplete, if parameters are untyped, or if the assignment is not a direct local closure initializer.

**Notes:** This is structural synthesis only, not general semantic type inference.


## CLOSURE-RETURN-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:157](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$f = function () use ($a): vector<int> { return [$a, 2]; };
```

**Existing C++ lowering / result**

```cpp
std::function<vector_t<int_t>()> f = [a]() -> vector_t<int_t> { return vector_t<int_t>{a, static_cast<int_t>(2)}; };
```

**Category:** Closure

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** closure return type is scanner-owned and maps to `vector_t<T>`; returned literal is positional

**Normalized pattern:** closure vector return literal

**General rule:** Scanner-owned closure return annotations such as `: vector<int>` lower to mapped callable return types. A positional array literal returned from a `vector_t<T>` closure/function return context lowers to a typed vector literal.

**Diagnostics:** Error if the array literal contains explicit keys or unsupported element shapes.

**Notes:** This must not rely on php-ast doc-comment ownership for declared returns.


## CLOSURE-REJECT-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:158](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$f = function () use ($a) { return $a; };
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Closure

**Rule kind:** rejection

**Source support status:** rejected

**Preconditions:** no explicit closure return type and no expected callable target signature

**Normalized pattern:** closure missing return type

**General rule:** Untyped closure assignment with a value return is rejected unless the closure has an explicit return type or the target provides a complete callable signature.

**Diagnostics:** Emit a clear generator diagnostic requiring an explicit return type or expected callable signature.

**Notes:** The generator does not infer return types from closure bodies.


## CLOSURE-REJECT-002

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:159](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$items[] = function (): int { return 1; };
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Closure

**Rule kind:** rejection

**Source support status:** rejected

**Preconditions:** closure assigned into array/dynamic container slot

**Normalized pattern:** closure container storage

**General rule:** Closures are local concrete callable values and cannot be stored in array or dynamic container slots/literals in the current strict model.

**Diagnostics:** Emit a clear generator diagnostic instructing the user to assign the closure to a concrete callable local.

**Notes:** Keeps closures out of `mixed_t` and dynamic table payloads.


## CLOSURE-STAN-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:160](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$f function<string()> = function (): int { return 1; };
```

**Existing C++ lowering / result**

```cpp
STAN ERROR
```

**Category:** Closure

**Rule kind:** STAN diagnostic

**Source support status:** supported-with-diagnostic

**Preconditions:** explicit callable local signature and explicit closure signature disagree

**Normalized pattern:** callable signature mismatch

**General rule:** A mismatch between an explicit callable local signature and the closure initializer's explicit signature is owned by STAN before build.

**Diagnostics:** STAN should report the mismatch before native generation/compile proceeds in strict projects.

**Notes:** The S2S generator remains structurally driven and does not perform broad callable compatibility inference.


## CLOSURE-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:313](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$f = function (int $a): int { if ($a === 1) { return 2; } return $a; };
```

**Existing C++ lowering / result**

```cpp
std::function<int_t(int_t)> f = [](int_t a) -> int_t { if (static_cast<bool>(a == int_t(1))) { return int_t(2); } return a; };
```

**Category:** Closure

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** anonymous function expression is assigned or passed as an expression

**Normalized pattern:** `<var> = function (...) { ... }`

**General rule:** Anonymous functions lower to a native C++ lambda. When the assignment target or inferred closure signature is concrete, the emitted local may use `std::function<Ret(Args...)>` initialized from that lambda. Closure parameters keep declared native PHP types when present; when a closure parameter has no native PHP type, Safe v1 also accepts an inline doc-comment type such as `/** function<int(int)> */ $fn` so the generator can emit a concrete `std::function<...>` parameter type. Closure factories that return another closure may also use the post-signature doc-comment form `$make = function () /** function<int(int)> */ { return function (int $x): int { ... }; };` in the simple single-return case, and the generator treats that attached doc-comment as the outer closure's explicit return type. Closure bodies reuse the normal statement-lowering pipeline, including `if`, `return`, `echo`, and expression statements within the supported subset. If a closure parameter or closure return is given both a native PHP type and a doc annotation, the generator rejects it as a conflicting type source.

**Diagnostics:** Emit an error only for unsupported inner statements/expressions inside the closure body.

**Notes:** This is the first closure pass and does not imply full PHP callable semantics.


## CLOSURE-CALL-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:315](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$f(); $g(3); $h(3); $h(3, 9);
```

**Existing C++ lowering / result**

```cpp
f(); g(int_t(3)); h(int_t(3)); h(int_t(3), int_t(9));
```

**Category:** Closure

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** callee resolves to a closure-valued variable lowered into a native lambda-backed callable (typically `std::function<...>` when a concrete signature is required)

**Normalized pattern:** `closure-valued variable invocation`

**General rule:** Closure-valued variables are invoked with ordinary C++ call syntax. Exact-arity calls are emitted directly. Trailing default arguments work when the lowered native lambda parameter list carries a concrete type and native C++ default argument.

**Diagnostics:** Emit an error only when the closure expression or the underlying call arguments contain unsupported forms.

**Notes:** This pass does not attempt broader PHP callable normalization across strings, arrays, or invokable objects.


## SCOPE-VAR-002

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:317](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
if (true) { $g = function (): int { return 1; }; } return $g();
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Scope

**Rule kind:** rejection

**Source support status:** rejected

**Preconditions:** variable was first declared in a nested block and later used outside that block

**Normalized pattern:** `out-of-block variable use`

**General rule:** Variables and closure-valued locals do not escape `if`, loop, or other nested statement blocks in Safe v1.

**Diagnostics:** Emit an explicit generator error naming the variable and source line.

**Notes:** This keeps the lowering aligned with the emitted C++ lexical model rather than PHP variable-table semantics.


## SCOPE-VAR-003

**Strict-mode PHP input example:** `$f function<int(int)>; if (true) { $f = function (int $x): int { return $x + 1; }; } echo $f(5);`

This is the working source spelling. Imported examples and C++ expectations below remain reference material; the v0.2 result still needs agreement and proof.

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:318](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$f /** function<int(int)> */; if (true) { $f = function (int $x): int { return $x + 1; }; } echo $f(5);
```

**Existing C++ lowering / result**

```cpp
std::function<int_t(int_t)> f; if (...) { f = [](int_t x) -> int_t { return (x + static_cast<int_t>(1)); }; } php::echo_one(f(static_cast<int_t>(5)));
```

**Category:** Scope

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** local is explicitly predeclared in the current block or an enclosing block

**Normalized pattern:** `typed local predeclaration`

**General rule:** Safe v1 permits a typed local declaration without initialization when the type is explicit. Function-valued locals must use `function<return_type(arg_types)>` annotations so the generator can emit a concrete `std::function<...>` declaration.

**Diagnostics:** Emit an explicit generator error when a later use occurs without an in-scope declaration or when the declared type syntax is invalid.

**Notes:** This is the supported way to predeclare a closure-valued local that will be assigned from child blocks.


## ARROW-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:319](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = 10; $f = fn(int $x): int => $x + $a;
```

**Existing C++ lowering / result**

```cpp
auto a = static_cast<int_t>(10); std::function<int_t(int_t)> f = [a](int_t x) mutable -> int_t { return (x + a); };
```

**Category:** Closure

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** arrow function expression is assigned or passed as an expression

**Normalized pattern:** `fn (...) => expr`

**General rule:** Arrow functions lower to native C++ lambdas using implicit by-value capture inferred from referenced outer locals. php-ast exposes them as `AST_ARROW_FUNC` (kind 72) with the body normalized as a single return statement, so Safe v1 reuses closure parameter and return-type lowering while discovering captures automatically instead of reading a `use (...)` list. Existing callable-signature rules apply: native PHP parameter and return types work normally, inline doc-comment callable parameter types are accepted when needed, and captures are emitted as value captures.

**Diagnostics:** Emit an explicit generator error for unsupported parameter shapes such as variadics/default params or conflicting PHP-vs-doc type sources.

**Notes:** Arrow functions do not support explicit `use (...)`; capture is implicit and by value only.
