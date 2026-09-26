# 03. Conditions and control flow
Doc Status: planning

> **Important: LLVM is deferred by default. Work on the direct LLVM backend only when the user explicitly requests it. The v0.2 default scope is Frontend + C++ S2S.**

[Catalog and workflow](README.md) · [Source merge ledger](source_merge.json)

Requires expressions. Start with if/else, then joins, loops and control transfer.

Order is a discussion sequence, not a claim that every row is a prerequisite. Split combined examples before implementation.

## Progress

Edit these rows as work proceeds. Imported source support is recorded below, independently of this progress.

| Entry | Status | PHP input example | Frontend | C++ S2S | LLVM | Proof / blocker |
| --- | --- | --- | --- | --- | --- | --- |
| [CTRL-IF-001](#ctrl-if-001) | pending-discussion | `if ($a) { $b = 1; }` | unverified | unverified | deferred | — |
| [CTRL-IF-002](#ctrl-if-002) | pending-discussion | `if ($a) { $b = 1; } else { $b = 2; }` | unverified | unverified | deferred | — |
| [CTRL-IF-003](#ctrl-if-003) | pending-discussion | `if ($a) { } elseif ($b) { } else { }` | unverified | unverified | deferred | — |
| [CTRL-WHILE-001](#ctrl-while-001) | pending-discussion | `while ($a) { $b++; }` | unverified | unverified | deferred | — |
| [EXPR-TERNARY-001](#expr-ternary-001) | pending-discussion | `$a = $b ? $c : $d;` | unverified | unverified | deferred | — |
| [CTRL-SWITCH-001](#ctrl-switch-001) | pending-discussion | `switch ($a) { case 1: break; default: break; }` | unverified | unverified | deferred | — |
| [CTRL-MATCH-001](#ctrl-match-001) | pending-discussion | `$a = match ($b) { 1 => 10, default => 0 };` | unverified | unverified | deferred | — |
| [CTRL-DOWHILE-001](#ctrl-dowhile-001) | pending-discussion | `do { $b++; } while ($a);` | unverified | unverified | deferred | — |
| [CTRL-FOR-001](#ctrl-for-001) | pending-discussion | `for ($i = 0; $i < 10; $i++) { }` | unverified | unverified | deferred | — |
| [CTRL-BREAK-001](#ctrl-break-001) | pending-discussion | `break;` | unverified | unverified | deferred | — |
| [CTRL-CONTINUE-001](#ctrl-continue-001) | pending-discussion | `continue;` | unverified | unverified | deferred | — |
| [SCOPE-VAR-001](#scope-var-001) | pending-discussion | `$x = 1; if (true) { $x = 2; } echo $x;` | unverified | unverified | deferred | — |
| [NOTE-017](#note-017) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
## CTRL-IF-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:122](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
if ($a) { $b = 1; }
```

**Existing C++ lowering / result**

```cpp
if (a) { auto b = static_cast<int_t>(1); }
```

**Category:** Control flow

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** condition must be boolean-compatible

**Normalized pattern:** `if (<expr>) <block>`

**General rule:** `if` statements are emitted directly. The condition must be boolean-compatible. The body is emitted as a block, and all inner statements must follow standard normalization rules (including `auto` only on first assignment in scope and literal casting).

**Diagnostics:** Error if condition is not boolean-compatible, declaration rules are violated, or literals are not normalized.

**Notes:** Scope rule applies inside the block.


## CTRL-IF-002

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:123](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
if ($a) { $b = 1; } else { $b = 2; }
```

**Existing C++ lowering / result**

```cpp
if (a) { auto b = static_cast<int_t>(1); } else { auto b = static_cast<int_t>(2); }
```

**Category:** Control flow

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** condition must be boolean-compatible

**Normalized pattern:** `if (<expr>) <block> else <block>`

**General rule:** `if/else` statements are emitted directly. Each branch is its own scope. Inner assignments follow normal scope rules, including `auto` only on first assignment in that branch scope, and literal normalization.

**Diagnostics:** Error if condition is not boolean-compatible, branch declaration rules are violated, or literals are not normalized.


## CTRL-IF-003

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:124](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
if ($a) { } elseif ($b) { } else { }
```

**Existing C++ lowering / result**

```cpp
if (a) { } else if (b) { } else { }
```

**Category:** Control flow

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** each condition must be boolean-compatible

**Normalized pattern:** `if / elseif / else chain`

**General rule:** PHP `elseif` chains are emitted as C++ `else if` chains. Conditions must be boolean-compatible.

**Diagnostics:** Error if a condition is not boolean-compatible or `elseif` is emitted literally into C++.


## CTRL-WHILE-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:127](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
while ($a) { $b++; }
```

**Existing C++ lowering / result**

```cpp
while (a) { b++; }
```

**Category:** Control flow

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** condition must be boolean-compatible; body statements valid in scope

**Normalized pattern:** `while (<expr>) <block>`

**General rule:** `while` loops are emitted directly. The condition must be boolean-compatible. The loop body follows normal statement and scope rules.

**Diagnostics:** Error if condition is not boolean-compatible or body uses undeclared variables.


## EXPR-TERNARY-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:88](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = $b ? $c : $d;
```

**Existing C++ lowering / result**

```cpp
auto a = php::ternary_eval([&]() -> decltype(auto) { return b; }, [&]() -> decltype(auto) { return c; }, [&]() -> decltype(auto) { return d; });
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** condition and branch combination must be covered by the runtime ternary matrix

**Normalized pattern:** `<var> = <cond> ? <expr> : <expr>`

**General rule:** The ternary operator lowers through `php::ternary_eval(...)` so condition truthiness and branch normalization are resolved centrally in the runtime instead of ad hoc in the generator.

**Diagnostics:** Error if the runtime ternary matrix does not define the branch pair.

**Notes:** This keeps direct expressions and assigned locals consistent.


## CTRL-SWITCH-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:125](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
switch ($a) { case 1: break; default: break; }
```

**Existing C++ lowering / result**

```cpp
switch (a) { case static_cast<int_t>(1): break; default: break; }
```

**Category:** Control flow

**Rule kind:** generation

**Source support status:** supported-with-known-incompatibility

**Preconditions:** switch expression and case values must be compatible

**Normalized pattern:** `switch (<expr>) { case ... }`

**General rule:** `switch` is emitted directly as C++ `switch`, with normalized literals.

**Diagnostics:** Error if literals are not normalized.

**Notes:** PHP `switch` uses loose comparison; C++ `switch` uses strict matching.


## CTRL-MATCH-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:126](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = match ($b) { 1 => 10, default => 0 };
```

**Existing C++ lowering / result**

```cpp
int_t tmp; if (php::identical(b, static_cast<int_t>(1))) { tmp = static_cast<int_t>(10); } else { tmp = static_cast<int_t>(0); } auto a = tmp;
```

**Category:** Control flow

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** all match arms must produce compatible types; default arm must exist

**Normalized pattern:** `<var> = match (<expr>) { <cases> }`

**General rule:** PHP `match` must be lowered to an `if / else if / else` chain using strict comparison (`php::identical`). A temporary variable must be introduced to preserve expression semantics.

**Diagnostics:** Error if no `default` arm, result types differ, or `match` is emitted directly.

**Notes:** Preserves PHP `===` semantics.


## CTRL-DOWHILE-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:128](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
do { $b++; } while ($a);
```

**Existing C++ lowering / result**

```cpp
do { b++; } while (a);
```

**Category:** Control flow

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** condition must be boolean-compatible; body statements valid in scope

**Normalized pattern:** `do <block> while (<expr>)`

**General rule:** `do-while` loops are emitted directly. The body executes first, then the condition is evaluated. All inner statements must follow normalization and scope rules.

**Diagnostics:** Error if condition is not boolean-compatible or body uses undeclared variables.


## CTRL-FOR-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:129](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
for ($i = 0; $i < 10; $i++) { }
```

**Existing C++ lowering / result**

```cpp
for (auto i = static_cast<int_t>(0); i < static_cast<int_t>(10); i++) { }
```

**Category:** Control flow

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** loop variable declared in init; condition boolean-compatible

**Normalized pattern:** `for (<init>; <cond>; <step>) <block>`

**General rule:** `for` loops are emitted directly. Initialization follows normal declaration rules (`auto` on first assignment). Condition and step follow expression normalization rules, including literal casting.

**Diagnostics:** Error if literals are not normalized, declaration rules are violated, or condition is not boolean-compatible.

**Notes:** Loop variable scope is limited to the `for` statement.


## CTRL-BREAK-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:134](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
break;
```

**Existing C++ lowering / result**

```cpp
break;
```

**Category:** Control flow

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** must be inside a loop or `switch`

**Normalized pattern:** `break`

**General rule:** `break` is emitted directly.

**Diagnostics:** Error if used outside of loop or `switch`.


## CTRL-CONTINUE-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:135](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
continue;
```

**Existing C++ lowering / result**

```cpp
continue;
```

**Category:** Control flow

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** must be inside a loop

**Normalized pattern:** `continue`

**General rule:** `continue` is emitted directly.

**Diagnostics:** Error if used outside of a loop.


## SCOPE-VAR-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:316](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$x = 1; if (true) { $x = 2; } echo $x;
```

**Existing C++ lowering / result**

```cpp
auto x = static_cast<int_t>(1); if (...) { x = static_cast<int_t>(2); } php::echo_one(x);
```

**Category:** Scope

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** variable resolves to a declaration in the current block or an enclosing block

**Normalized pattern:** `block-local variable visibility`

**General rule:** Safe v1 uses block-local visibility. The first write in a block declares a new variable only when no visible variable of the same name exists in an enclosing block. Otherwise the write is an assignment to the visible outer variable. A variable is visible only in its declaring block and child blocks.

**Diagnostics:** Emit an explicit generator error when a variable is used outside the block where it was declared.

**Notes:** This intentionally rejects PHP-style block escape for locals and closures.


## NOTE-017

**Source:** [generators/php/specs/rules.md:249](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## 9. Control Flow
>
> ### Supported
> - `if / else / elseif`
> - `while`
> - `do-while`
> - `for`
> - `switch` (known mismatch remains documented separately)
>
> ### Rejected
> - `foreach` over `vector_t` lowers to an indexed C++ `for` loop
>
> ---
