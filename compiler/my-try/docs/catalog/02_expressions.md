# 02. Expressions and scalar operations
Doc Status: planning

> **Important: LLVM is deferred by default. Work on the direct LLVM backend only when the user explicitly requests it. The v0.2 default scope is Frontend + C++ S2S.**

[Catalog and workflow](README.md) · [Source merge ledger](source_merge.json)

Requires locals. Establish precedence, operand types and evaluation order before composed expressions.

Order is a discussion sequence, not a claim that every row is a prerequisite. Split combined examples before implementation.

## Progress

Edit these rows as work proceeds. Imported source support is recorded below, independently of this progress.

| Entry | Status | PHP input example | Frontend | C++ S2S | LLVM | Proof / blocker |
| --- | --- | --- | --- | --- | --- | --- |
| [EXPR-PAREN-001](#expr-paren-001) | pending-discussion | `$a = ($b + 1);` | unverified | unverified | deferred | — |
| [EXPR-ARITH-001](#expr-arith-001) | pending-discussion | `$a = 1 + 2;` | unverified | unverified | deferred | — |
| [EXPR-SUB-001](#expr-sub-001) | pending-discussion | `$a = 1 - 2;` | unverified | unverified | deferred | — |
| [EXPR-MUL-001](#expr-mul-001) | pending-discussion | `$a = 2 * 3;` | unverified | unverified | deferred | — |
| [EXPR-DIV-001](#expr-div-001) | pending-discussion | `$a = 4 / 2;` | unverified | unverified | deferred | — |
| [EXPR-MOD-001](#expr-mod-001) | pending-discussion | `$a = 5 % 2;` | unverified | unverified | deferred | — |
| [EXPR-POW-001](#expr-pow-001) | pending-discussion | `$a = 2 ** 3;` | unverified | unverified | deferred | — |
| [EXPR-NEG-001](#expr-neg-001) | pending-discussion | `$a = -$b;` | unverified | unverified | deferred | — |
| [EXPR-POS-001](#expr-pos-001) | pending-discussion | `$a = +$b;` | unverified | unverified | deferred | — |
| [EXPR-BNOT-001](#expr-bnot-001) | pending-discussion | `$a = ~$b;` | unverified | unverified | deferred | — |
| [EXPR-NESTED-001](#expr-nested-001) | pending-discussion | `$a = ($b + 1) * 2;` | unverified | unverified | deferred | — |
| [EXPR-CHAIN-001](#expr-chain-001) | pending-discussion | `$a = $b + 1 + $c;` | unverified | unverified | deferred | — |
| [EXPR-CONCAT-001](#expr-concat-001) | pending-discussion | `$a = "a" . "b";` | unverified | unverified | deferred | — |
| [EXPR-CONCAT-002](#expr-concat-002) | pending-discussion | `$a = $b . "x";` | unverified | unverified | deferred | — |
| [EXPR-CONCAT-003](#expr-concat-003) | pending-discussion | `$a = "x" . $b;` | unverified | unverified | deferred | — |
| [EXPR-COMP-001](#expr-comp-001) | pending-discussion | `$a = ($b == 1);` | unverified | unverified | deferred | — |
| [EXPR-NEQ-001](#expr-neq-001) | pending-discussion | `$a = ($b != 1);` | unverified | unverified | deferred | — |
| [EXPR-SEQ-001](#expr-seq-001) | pending-discussion | `$a = ($b === 1);` | unverified | unverified | deferred | — |
| [EXPR-SNEQ-001](#expr-sneq-001) | pending-discussion | `$a = ($b !== 1);` | unverified | unverified | deferred | — |
| [EXPR-LT-001](#expr-lt-001) | pending-discussion | `$a = ($b < 1);` | unverified | unverified | deferred | — |
| [EXPR-LTE-001](#expr-lte-001) | pending-discussion | `$a = ($b <= 1);` | unverified | unverified | deferred | — |
| [EXPR-GT-001](#expr-gt-001) | pending-discussion | `$a = ($b > 1);` | unverified | unverified | deferred | — |
| [EXPR-GTE-001](#expr-gte-001) | pending-discussion | `$a = ($b >= 1);` | unverified | unverified | deferred | — |
| [EXPR-SPACESHIP-001](#expr-spaceship-001) | pending-discussion | `$a = ($b <=> $c);` | unverified | unverified | deferred | — |
| [EXPR-LOGIC-001](#expr-logic-001) | pending-discussion | `$a = true && false;` | unverified | unverified | deferred | — |
| [EXPR-OR-001](#expr-or-001) | pending-discussion | `$a = true \|\| false;` | unverified | unverified | deferred | — |
| [EXPR-NOT-001](#expr-not-001) | pending-discussion | `$a = !$b;` | unverified | unverified | deferred | — |
| [EXPR-ANDWORD-001](#expr-andword-001) | pending-discussion | `$a = $b and $c;` | unverified | unverified | deferred | — |
| [EXPR-ORWORD-001](#expr-orword-001) | pending-discussion | `$a = $b or $c;` | unverified | unverified | deferred | — |
| [EXPR-XORWORD-001](#expr-xorword-001) | pending-discussion | `$a = $b xor $c;` | unverified | unverified | deferred | — |
| [EXPR-PREINC-001](#expr-preinc-001) | pending-discussion | `$a = ++$b;` | unverified | unverified | deferred | — |
| [EXPR-POSTINC-001](#expr-postinc-001) | pending-discussion | `$a = $b++;` | unverified | unverified | deferred | — |
| [EXPR-PREDEC-001](#expr-predec-001) | pending-discussion | `$a = --$b;` | unverified | unverified | deferred | — |
| [EXPR-POSTDEC-001](#expr-postdec-001) | pending-discussion | `$a = $b--;` | unverified | unverified | deferred | — |
| [STMT-EXPR-001](#stmt-expr-001) | pending-discussion | `$a + 1;` | unverified | unverified | deferred | — |
| [STMT-ASSIGNOP-001](#stmt-assignop-001) | pending-discussion | `$a += 1;` | unverified | unverified | deferred | — |
| [STMT-ASSIGNOP-002](#stmt-assignop-002) | pending-discussion | `$a -= 1;` | unverified | unverified | deferred | — |
| [STMT-ASSIGNOP-003](#stmt-assignop-003) | pending-discussion | `$a *= 2;` | unverified | unverified | deferred | — |
| [STMT-ASSIGNOP-004](#stmt-assignop-004) | pending-discussion | `$a /= 2;` | unverified | unverified | deferred | — |
| [STMT-ASSIGNOP-005](#stmt-assignop-005) | pending-discussion | `$a .= "x";` | unverified | unverified | deferred | — |
| [STMT-ASSIGNOP-006](#stmt-assignop-006) | pending-discussion | `$a = 1; $a += 1;` | unverified | unverified | deferred | — |
| [STMT-ASSIGNOP-007](#stmt-assignop-007) | pending-discussion | `$a %= 2;` | unverified | unverified | deferred | — |
| [STMT-ASSIGNOP-008](#stmt-assignop-008) | pending-discussion | `$a &= 3;` | unverified | unverified | deferred | — |
| [STMT-ASSIGNOP-009](#stmt-assignop-009) | pending-discussion | `$a \|= 3;` | unverified | unverified | deferred | — |
| [STMT-ASSIGNOP-010](#stmt-assignop-010) | pending-discussion | `$a ^= 3;` | unverified | unverified | deferred | — |
| [STMT-ASSIGNOP-011](#stmt-assignop-011) | pending-discussion | `$a <<= 1;` | unverified | unverified | deferred | — |
| [STMT-ASSIGNOP-012](#stmt-assignop-012) | pending-discussion | `$a >>= 1;` | unverified | unverified | deferred | — |
| [STMT-PREINC-001](#stmt-preinc-001) | pending-discussion | `++$a;` | unverified | unverified | deferred | — |
| [STMT-PREDEC-001](#stmt-predec-001) | pending-discussion | `--$a;` | unverified | unverified | deferred | — |
| [STMT-POSTINC-001](#stmt-postinc-001) | pending-discussion | `$a++;` | unverified | unverified | deferred | — |
| [STMT-POSTDEC-001](#stmt-postdec-001) | pending-discussion | `$a--;` | unverified | unverified | deferred | — |
| [STR-INTERP-001](#str-interp-001) | pending-discussion | `$a = "hello $name";` | unverified | unverified | deferred | — |
| [STR-INTERP-002](#str-interp-002) | pending-discussion | `$a = "sum {$a}";` | unverified | unverified | deferred | — |
| [STR-INDEX-001](#str-index-001) | pending-discussion | `$a = $s[0];` | unverified | unverified | deferred | — |
| [NOTE-013](#note-013) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-018](#note-018) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-027](#note-027) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-043](#note-043) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-044](#note-044) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-054](#note-054) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
## EXPR-PAREN-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:63](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = ($b + 1);
```

**Existing C++ lowering / result**

```cpp
auto a = (b + static_cast<int_t>(1));
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** inner expression valid

**Normalized pattern:** `<var> = (<expr>)`

**General rule:** Parentheses must be preserved in the emitted code to maintain evaluation order. Inner expressions must follow all normalization rules.

**Diagnostics:** Error if inner expression violates normalization rules.

**Notes:** Structural rule.


## EXPR-ARITH-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:54](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = 1 + 2;
```

**Existing C++ lowering / result**

```cpp
auto a = static_cast<int_t>(1) + static_cast<int_t>(2);
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** operands numeric-compatible

**Normalized pattern:** `<var> = <expr> <arith-op> <expr>`

**General rule:** Binary arithmetic operations (`+`, `-`, `*`, `/`, `%`) must be emitted as arithmetic on normalized runtime-typed operands. Any literal operand must first be converted with the appropriate `static_cast<..._t>(...)`.

**Diagnostics:** Error if operand is non-numeric or a literal is emitted without normalization.

**Notes:** Replaces the separate ADD/SUB/MUL/DIV/MOD rows as the generalized rule.


## EXPR-SUB-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:55](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = 1 - 2;
```

**Existing C++ lowering / result**

```cpp
auto a = static_cast<int_t>(1) - static_cast<int_t>(2);
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** covered-by-EXPR-ARITH-001

**Preconditions:** operands numeric-compatible

**Normalized pattern:** `<var> = <expr> <arith-op> <expr>`

**General rule:** Same generalized rule as EXPR-ARITH-001.

**Notes:** Redundant seed row.


## EXPR-MUL-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:56](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = 2 * 3;
```

**Existing C++ lowering / result**

```cpp
auto a = static_cast<int_t>(2) * static_cast<int_t>(3);
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** covered-by-EXPR-ARITH-001

**Preconditions:** operands numeric-compatible

**Normalized pattern:** `<var> = <expr> <arith-op> <expr>`

**General rule:** Same generalized rule as EXPR-ARITH-001.

**Notes:** Redundant seed row.


## EXPR-DIV-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:57](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = 4 / 2;
```

**Existing C++ lowering / result**

```cpp
auto a = static_cast<int_t>(4) / static_cast<int_t>(2);
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** supported-with-known-incompatibility

**Preconditions:** operands numeric-compatible

**Normalized pattern:** `<var> = <expr> <arith-op> <expr>`

**General rule:** Same generalized rule as EXPR-ARITH-001.

**Diagnostics:** Division has a known PHP/C++ semantic mismatch; see incompatibilities.

**Notes:** Keep tracked in `incompatibilities.md`.


## EXPR-MOD-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:58](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = 5 % 2;
```

**Existing C++ lowering / result**

```cpp
auto a = static_cast<int_t>(5) % static_cast<int_t>(2);
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** covered-by-EXPR-ARITH-001

**Preconditions:** operands numeric-compatible

**Normalized pattern:** `<var> = <expr> <arith-op> <expr>`

**General rule:** Same generalized rule as EXPR-ARITH-001.

**Notes:** Redundant seed row.


## EXPR-POW-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:59](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = 2 ** 3;
```

**Existing C++ lowering / result**

```cpp
auto a = scpp::pow(static_cast<int_t>(2), static_cast<int_t>(3));
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** operands numeric-compatible

**Normalized pattern:** `<var> = <expr> ** <expr>`

**General rule:** Exponentiation must be mapped to `scpp::pow(...)`. All operands must be normalized to runtime types before invocation.

**Diagnostics:** Error if operands are non-numeric or literals are not normalized.

**Notes:** `scpp::pow` must be created in runtime.


## EXPR-NEG-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:60](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = -$b;
```

**Existing C++ lowering / result**

```cpp
auto a = -b;
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** operand numeric-compatible

**Normalized pattern:** `<var> = -<expr>`

**General rule:** Unary numeric negation is emitted directly as `-<expr>`. No extra cast is needed unless the operand itself requires normalization before use.

**Diagnostics:** Error if operand is non-numeric.


## EXPR-POS-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:61](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = +$b;
```

**Existing C++ lowering / result**

```cpp
auto a = +b;
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** operand numeric-compatible

**Normalized pattern:** `<var> = +<expr>`

**General rule:** Unary plus is emitted directly as `+<expr>` and acts as a no-op on already normalized numeric expressions.

**Diagnostics:** Error if operand is non-numeric.


## EXPR-BNOT-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:62](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = ~$b;
```

**Existing C++ lowering / result**

```cpp
auto a = ~b;
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** operand integer-bitwise-compatible

**Normalized pattern:** `<var> = ~<expr>`

**General rule:** Unary bitwise NOT is emitted directly as `~<expr>`. Grouping must be preserved when it participates in a larger expression, for example `(~b) * 2`.

**Diagnostics:** Error if operand is not compatible with the current integer-bitwise runtime surface.

**Notes:** Matches the current generator/runtime path.


## EXPR-NESTED-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:64](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = ($b + 1) * 2;
```

**Existing C++ lowering / result**

```cpp
auto a = (b + static_cast<int_t>(1)) * static_cast<int_t>(2);
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** covered-by-EXPR-PAREN-001-and-EXPR-ARITH-001

**Preconditions:** recursive expression validity

**Normalized pattern:** `<var> = (<expr>) <arith-op> <expr>`

**General rule:** Apply recursive normalization bottom-up on the AST.

**Diagnostics:** Error if any literal at any depth is not normalized.

**Notes:** Illustrates recursive normalization.


## EXPR-CHAIN-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:65](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = $b + 1 + $c;
```

**Existing C++ lowering / result**

```cpp
auto a = b + static_cast<int_t>(1) + c;
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** covered-by-EXPR-ARITH-001

**Preconditions:** operands numeric-compatible

**Normalized pattern:** `<var> = <expr> <arith-op> <expr> ...`

**General rule:** Associative arithmetic chains stay under the same generalized arithmetic rule.

**Diagnostics:** Error if any literal in the chain is not normalized.

**Notes:** No per-length rule explosion.


## EXPR-CONCAT-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:67](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = "a" . "b";
```

**Existing C++ lowering / result**

```cpp
auto a = string_t("a") + string_t("b");
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** operands are valid string-compatible expressions

**Normalized pattern:** `<var> = <expr> . <expr>`

**General rule:** PHP string concatenation `.` maps to C++ `+` on `string_t` operands. Any non-string operand that is supported in string context must be made explicit by the generator using `cast<string_t>(...)`. Unsupported operand types must fail.

**Diagnostics:** Error if raw C string would cause pointer arithmetic / wrong overload resolution.


## EXPR-CONCAT-002

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:68](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = $b . "x";
```

**Existing C++ lowering / result**

```cpp
auto a = cast<string_t>(b) + string_t("x");
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** covered-by-EXPR-CONCAT-001

**Preconditions:** same as EXPR-CONCAT-001

**Normalized pattern:** `<var> = <expr> . <expr>`

**General rule:** Same generalized rule as EXPR-CONCAT-001.


## EXPR-CONCAT-003

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:69](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = "x" . $b;
```

**Existing C++ lowering / result**

```cpp
auto a = string_t("x") + cast<string_t>(b);
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** covered-by-EXPR-CONCAT-001

**Preconditions:** same as EXPR-CONCAT-001

**Normalized pattern:** `<var> = <expr> . <expr>`

**General rule:** Same generalized rule as EXPR-CONCAT-001.

**Diagnostics:** Error if emitted as `"x" + b`, which would be wrong in C++.


## EXPR-COMP-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:71](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = ($b == 1);
```

**Existing C++ lowering / result**

```cpp
auto a = (b == static_cast<int_t>(1));
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** supported-with-known-incompatibility

**Preconditions:** operands comparable

**Normalized pattern:** `<var> = <expr> <comp-op> <expr>`

**General rule:** Comparison operations (`==`, `!=`, `<`, `<=`, `>`, `>=`) must operate on normalized operands. Any literal operand must be converted using the appropriate runtime cast.

**Diagnostics:** Error if operands are incompatible or a literal is not normalized.

**Notes:** PHP loose comparison mismatch still open.


## EXPR-NEQ-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:72](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = ($b != 1);
```

**Existing C++ lowering / result**

```cpp
auto a = (b != static_cast<int_t>(1));
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** covered-by-EXPR-COMP-001-with-known-incompatibility

**Preconditions:** operands comparable

**Normalized pattern:** `<var> = <expr> <comp-op> <expr>`

**General rule:** Same generalized rule as EXPR-COMP-001.

**Diagnostics:** Same loose-comparison mismatch concern.


## EXPR-SEQ-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:73](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = ($b === 1);
```

**Existing C++ lowering / result**

```cpp
auto a = php::identical(b, static_cast<int_t>(1));
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** valid expressions

**Normalized pattern:** `<var> = <expr> === <expr>`

**General rule:** Strict comparison (`===`) must be mapped to runtime helpers to preserve PHP semantics. The helper returns `bool_t`, not native `bool`.

**Diagnostics:** Error if generator emits `===` as native C++ `==`.

**Notes:** Part of strict-comparison family.


## EXPR-SNEQ-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:74](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = ($b !== 1);
```

**Existing C++ lowering / result**

```cpp
auto a = php::not_identical(b, static_cast<int_t>(1));
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** valid expressions

**Normalized pattern:** `<var> = <expr> !== <expr>`

**General rule:** Strict inequality (`!==`) must be mapped to runtime helpers to preserve PHP semantics. The helper returns `bool_t`, not native `bool`.

**Diagnostics:** Error if generator emits `!==` as native C++ `!=`.

**Notes:** Part of strict-comparison family.


## EXPR-LT-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:75](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = ($b < 1);
```

**Existing C++ lowering / result**

```cpp
auto a = (b < static_cast<int_t>(1));
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** covered-by-EXPR-COMP-001

**Preconditions:** operands comparable

**Normalized pattern:** `<var> = <expr> <comp-op> <expr>`

**General rule:** Same generalized rule as EXPR-COMP-001.


## EXPR-LTE-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:76](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = ($b <= 1);
```

**Existing C++ lowering / result**

```cpp
auto a = (b <= static_cast<int_t>(1));
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** covered-by-EXPR-COMP-001

**Preconditions:** operands comparable

**Normalized pattern:** `<var> = <expr> <comp-op> <expr>`

**General rule:** Same generalized rule as EXPR-COMP-001.


## EXPR-GT-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:77](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = ($b > 1);
```

**Existing C++ lowering / result**

```cpp
auto a = (b > static_cast<int_t>(1));
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** covered-by-EXPR-COMP-001

**Preconditions:** operands comparable

**Normalized pattern:** `<var> = <expr> <comp-op> <expr>`

**General rule:** Same generalized rule as EXPR-COMP-001.


## EXPR-GTE-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:78](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = ($b >= 1);
```

**Existing C++ lowering / result**

```cpp
auto a = (b >= static_cast<int_t>(1));
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** covered-by-EXPR-COMP-001

**Preconditions:** operands comparable

**Normalized pattern:** `<var> = <expr> <comp-op> <expr>`

**General rule:** Same generalized rule as EXPR-COMP-001.


## EXPR-SPACESHIP-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:79](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = ($b <=> $c);
```

**Existing C++ lowering / result**

```cpp
auto a = scpp::cmp(b, c);
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** operands must be comparable under runtime semantics

**Normalized pattern:** `<var> = <expr> <=> <expr>`

**General rule:** PHP spaceship must be mapped to a runtime helper returning `int_t` with values `-1`, `0`, `1`.

**Diagnostics:** Error if emitted as native C++ `<=>`, which returns a comparison category type, not PHP-style `int_t`.

**Notes:** `scpp::cmp` must be created in runtime.


## EXPR-LOGIC-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:81](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = true && false;
```

**Existing C++ lowering / result**

```cpp
auto a = static_cast<bool_t>(true) && static_cast<bool_t>(false);
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** operands boolean-compatible

**Normalized pattern:** `<var> = <expr> <logic-op> <expr>`

**General rule:** Logical operators (`&&`, `||`) are emitted directly. Literal operands must be normalized if they are raw literals; no extra casting is required for already boolean-compatible expressions.

**Diagnostics:** Error if operands are non-boolean.

**Notes:** Covers both `&&` and `||`.


## EXPR-OR-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:82](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = true || false;
```

**Existing C++ lowering / result**

```cpp
auto a = static_cast<bool_t>(true) || static_cast<bool_t>(false);
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** covered-by-EXPR-LOGIC-001

**Preconditions:** operands boolean-compatible

**Normalized pattern:** `<var> = <expr> <logic-op> <expr>`

**General rule:** Same generalized rule as EXPR-LOGIC-001.

**Notes:** Redundant seed row.


## EXPR-NOT-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:83](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = !$b;
```

**Existing C++ lowering / result**

```cpp
auto a = !b;
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** operand must be boolean-compatible

**Normalized pattern:** `<var> = !<expr>`

**General rule:** Logical negation is emitted directly as `!<expr>`. No extra cast is required if the operand is already boolean-compatible.

**Diagnostics:** Error if operand is non-boolean.


## EXPR-ANDWORD-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:84](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = $b and $c;
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Expression

**Rule kind:** rejection

**Source support status:** rejected

**Normalized pattern:** `<var> = <expr> and <expr>`

**General rule:** The operators `and`, `or`, and `xor` are not supported.

**Diagnostics:** Emit an error if any of these operators are encountered.

**Notes:** Rejected because operator precedence in PHP is badly designed for these operators.


## EXPR-ORWORD-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:85](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = $b or $c;
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Expression

**Rule kind:** rejection

**Source support status:** rejected

**Normalized pattern:** `<var> = <expr> or <expr>`

**General rule:** The operators `and`, `or`, and `xor` are not supported.

**Diagnostics:** Emit an error if any of these operators are encountered.

**Notes:** Rejected because operator precedence in PHP is badly designed for these operators.


## EXPR-XORWORD-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:86](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = $b xor $c;
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Expression

**Rule kind:** rejection

**Source support status:** rejected

**Normalized pattern:** `<var> = <expr> xor <expr>`

**General rule:** The operators `and`, `or`, and `xor` are not supported.

**Diagnostics:** Emit an error if any of these operators are encountered.

**Notes:** Rejected because operator precedence in PHP is badly designed for these operators.


## EXPR-PREINC-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:92](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = ++$b;
```

**Existing C++ lowering / result**

```cpp
auto a = ++b;
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** operand must be a mutable numeric variable

**Normalized pattern:** `<var> = ++<expr>`

**General rule:** Pre-increment is emitted directly as `++<expr>`. The result of the increment expression is assigned.

**Diagnostics:** Error if operand is not mutable or non-numeric.


## EXPR-POSTINC-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:93](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = $b++;
```

**Existing C++ lowering / result**

```cpp
auto a = b++;
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** operand must be a mutable numeric variable

**Normalized pattern:** `<var> = <expr>++`

**General rule:** Post-increment is emitted directly as `<expr>++`. The original value is assigned, then the increment occurs.

**Diagnostics:** Error if operand is not mutable or non-numeric.


## EXPR-PREDEC-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:94](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = --$b;
```

**Existing C++ lowering / result**

```cpp
auto a = --b;
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** operand must be a mutable numeric variable

**Normalized pattern:** `<var> = --<expr>`

**General rule:** Pre-decrement is emitted directly as `--<expr>`. The value is decremented first, then assigned.

**Diagnostics:** Error if operand is not mutable or non-numeric.


## EXPR-POSTDEC-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:95](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = $b--;
```

**Existing C++ lowering / result**

```cpp
auto a = b--;
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** operand must be a mutable numeric variable

**Normalized pattern:** `<var> = <expr>--`

**General rule:** Post-decrement is emitted directly as `<expr>--`. The original value is assigned, then the decrement occurs.

**Diagnostics:** Error if operand is not mutable or non-numeric.


## STMT-EXPR-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:104](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a + 1;
```

**Existing C++ lowering / result**

```cpp
a + static_cast<int_t>(1);
```

**Category:** Statement

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** all variables used must be declared in scope

**Normalized pattern:** `<expr>;`

**General rule:** Expression statements are emitted directly. All sub-expressions must follow normalization rules (e.g., literal casting).

**Diagnostics:** Error if variable is used before declaration or literal is not normalized.

**Notes:** No assignment â†’ no `auto`.


## STMT-ASSIGNOP-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:105](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a += 1;
```

**Existing C++ lowering / result**

```cpp
a += static_cast<int_t>(1);
```

**Category:** Statement

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** variable must already be declared in scope

**Normalized pattern:** `<var> <assign-op>= <expr>`

**General rule:** Compound assignment operators (`+=`, `-=`, `*=`, `/=`, etc.) are emitted directly. All operands must be normalized (e.g., literals cast).

**Diagnostics:** Error if variable is not previously declared or literal is not normalized.


## STMT-ASSIGNOP-002

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:106](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a -= 1;
```

**Existing C++ lowering / result**

```cpp
a -= static_cast<int_t>(1);
```

**Category:** Statement

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** variable must already be declared in scope

**Normalized pattern:** `<var> <assign-op>= <expr>`

**General rule:** Compound assignment operators (`+=`, `-=`, `*=`, `/=`, etc.) are emitted directly. All operands must be normalized (e.g., literals cast).

**Diagnostics:** Error if variable is not previously declared or literal is not normalized.


## STMT-ASSIGNOP-003

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:107](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a *= 2;
```

**Existing C++ lowering / result**

```cpp
a *= static_cast<int_t>(2);
```

**Category:** Statement

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** variable must already be declared in scope

**Normalized pattern:** `<var> <assign-op>= <expr>`

**General rule:** Compound assignment operators (`+=`, `-=`, `*=`, `/=`, etc.) are emitted directly. All operands must be normalized (e.g., literals cast).

**Diagnostics:** Error if variable is not previously declared or literal is not normalized.


## STMT-ASSIGNOP-004

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:108](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a /= 2;
```

**Existing C++ lowering / result**

```cpp
a /= static_cast<int_t>(2);
```

**Category:** Statement

**Rule kind:** generation

**Source support status:** supported-with-known-incompatibility

**Preconditions:** variable must already be declared in scope

**Normalized pattern:** `<var> <assign-op>= <expr>`

**General rule:** Compound assignment operators (`+=`, `-=`, `*=`, `/=`, etc.) are emitted directly. All operands must be normalized.

**Diagnostics:** Error if variable is not previously declared or literal is not normalized.

**Notes:** Division keeps the same semantic mismatch as `/`.


## STMT-ASSIGNOP-005

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:109](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a .= "x";
```

**Existing C++ lowering / result**

```cpp
a += string_t("x");
```

**Category:** Statement

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** variable must already be declared in scope; target must be string-compatible

**Normalized pattern:** `<var> .= <expr>`

**General rule:** PHP string-concatenation assignment `.=` maps to `+=` on `string_t`-compatible operands. The right-hand side must go through the same explicit string-cast path as `.`/interpolation, so non-string operands are converted through the string operand helper and string literals normalize as `string_t("...")`.

**Diagnostics:** Error if variable is not previously declared, target is not string-compatible, or string literal is not normalized.


## STMT-ASSIGNOP-006

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:110](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = 1; $a += 1;
```

**Existing C++ lowering / result**

```cpp
auto a = static_cast<int_t>(1); a += static_cast<int_t>(1);
```

**Category:** Statement

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** variable declared by first statement; RHS type-compatible

**Normalized pattern:** `<var> = <expr>; <var> += <expr>;`

**General rule:** Compound assignments follow normal reassignment rules. The left-hand side must already be declared, and the right-hand side must be fully normalized.

**Diagnostics:** Error if variable not declared or literal not normalized.


## STMT-ASSIGNOP-007

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:111](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a %= 2;
```

**Existing C++ lowering / result**

```cpp
a %= static_cast<int_t>(2);
```

**Category:** Statement

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** variable must already be declared in scope

**Normalized pattern:** `<var> %= <expr>`

**General rule:** C++-native numeric compound assignments that exist in the target language (`%=`, `&=`, `|=`, `^=`, `<<=`, `>>=`) must be emitted directly after operand normalization.

**Diagnostics:** Error if variable is not previously declared or the RHS is not normalized.


## STMT-ASSIGNOP-008

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:112](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a &= 3;
```

**Existing C++ lowering / result**

```cpp
a &= static_cast<int_t>(3);
```

**Category:** Statement

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** variable must already be declared in scope

**Normalized pattern:** `<var> &= <expr>`

**General rule:** Same generalized direct-emission rule as STMT-ASSIGNOP-007.

**Diagnostics:** Error if variable is not previously declared or the RHS is not normalized.


## STMT-ASSIGNOP-009

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:113](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a |= 3;
```

**Existing C++ lowering / result**

```cpp
a |= static_cast<int_t>(3);
```

**Category:** Statement

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** variable must already be declared in scope

**Normalized pattern:** `<var> |= <expr>`

**General rule:** Same generalized direct-emission rule as STMT-ASSIGNOP-007.

**Diagnostics:** Error if variable is not previously declared or the RHS is not normalized.


## STMT-ASSIGNOP-010

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:114](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a ^= 3;
```

**Existing C++ lowering / result**

```cpp
a ^= static_cast<int_t>(3);
```

**Category:** Statement

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** variable must already be declared in scope

**Normalized pattern:** `<var> ^= <expr>`

**General rule:** Same generalized direct-emission rule as STMT-ASSIGNOP-007.

**Diagnostics:** Error if variable is not previously declared or the RHS is not normalized.


## STMT-ASSIGNOP-011

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:115](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a <<= 1;
```

**Existing C++ lowering / result**

```cpp
a <<= static_cast<int_t>(1);
```

**Category:** Statement

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** variable must already be declared in scope

**Normalized pattern:** `<var> <<= <expr>`

**General rule:** Same generalized direct-emission rule as STMT-ASSIGNOP-007.

**Diagnostics:** Error if variable is not previously declared or the RHS is not normalized.


## STMT-ASSIGNOP-012

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:116](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a >>= 1;
```

**Existing C++ lowering / result**

```cpp
a >>= static_cast<int_t>(1);
```

**Category:** Statement

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** variable must already be declared in scope

**Normalized pattern:** `<var> >>= <expr>`

**General rule:** Same generalized direct-emission rule as STMT-ASSIGNOP-007.

**Diagnostics:** Error if variable is not previously declared or the RHS is not normalized.


## STMT-PREINC-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:117](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
++$a;
```

**Existing C++ lowering / result**

```cpp
++a;
```

**Category:** Statement

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** variable must already be declared in scope; mutable and numeric-compatible

**Normalized pattern:** `++<var>`

**General rule:** Statement-level pre-increment is emitted directly as `++<var>`.

**Diagnostics:** Error if variable is not previously declared, not mutable, or non-numeric.


## STMT-PREDEC-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:118](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
--$a;
```

**Existing C++ lowering / result**

```cpp
--a;
```

**Category:** Statement

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** variable must already be declared in scope; mutable and numeric-compatible

**Normalized pattern:** `--<var>`

**General rule:** Statement-level pre-decrement is emitted directly as `--<var>`.

**Diagnostics:** Error if variable is not previously declared, not mutable, or non-numeric.


## STMT-POSTINC-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:119](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a++;
```

**Existing C++ lowering / result**

```cpp
a++;
```

**Category:** Statement

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** variable must already be declared in scope; mutable and numeric-compatible

**Normalized pattern:** `<var>++`

**General rule:** Statement-level post-increment is emitted directly as `<var>++`.

**Diagnostics:** Error if variable is not previously declared, not mutable, or non-numeric.


## STMT-POSTDEC-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:120](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a--;
```

**Existing C++ lowering / result**

```cpp
a--;
```

**Category:** Statement

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** variable must already be declared in scope; mutable and numeric-compatible

**Normalized pattern:** `<var>--`

**General rule:** Statement-level post-decrement is emitted directly as `<var>--`.

**Diagnostics:** Error if variable is not previously declared, not mutable, or non-numeric.


## STR-INTERP-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:301](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = "hello $name";
```

**Existing C++ lowering / result**

_Not supplied in source._

**Category:** String

**Rule kind:** rejection-or-generation

**Normalized pattern:** `<var> = <interpolated-string>`


## STR-INTERP-002

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:302](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = "sum {$a}";
```

**Existing C++ lowering / result**

_Not supplied in source._

**Category:** String

**Rule kind:** rejection-or-generation

**Normalized pattern:** `<var> = <braced-interpolated-string>`


## STR-INDEX-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:303](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = $s[0];
```

**Existing C++ lowering / result**

_Not supplied in source._

**Category:** String

**Rule kind:** rejection-or-generation

**Normalized pattern:** `<var> = <string-expr>[<expr>]`


## NOTE-013

**Source:** [generators/php/specs/rules.md:190](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## 5. Expressions
>
> ### Supported operator families
> - arithmetic: `+ - * / %`
> - comparison: `== != < <= > >=`
> - logical: `&& ||`
>
> ### Rules
> - recursive AST normalization
> - literals are normalized at leaves
> - parentheses are preserved
> - no combinatorial expansion
>
> ---

## NOTE-018

**Source:** [generators/php/specs/rules.md:263](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## 10. Statements
>
> - expression statements are allowed
> - compound assignments are allowed after normalization; `.=` must normalize the right-hand side through the same explicit string cast path as `.`
> - `++` and `--` require a declared variable
>
> ---

## NOTE-027

**Source:** [generators/php/specs/rules.md:459](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## 16. Expression Emission Policy
>
> - expression lowering must remain structural and simple
> - explicit grouping/parentheses must be preserved when the PHP AST encodes grouped binary expressions
> - the generator must not flatten grouped expressions in a way that changes PHP operator precedence
> - the generator must not try to behave like a semantic expression compiler
> - casts, operators, precedence-preserving grouping, wrapper-type behavior, and null checks are emitted into C++ according to the configured forms and are then handled by the runtime and the C++ compiler
> - the generator should only reject an expression when a generation rule explicitly marks that source form unsupported

## NOTE-043

**Source:** [generators/php/specs/rules.md:894](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## 14. Expression normalization rules
>
> ### 14.1 Recursive normalization
> Expression normalization is recursive and bottom-up on the AST.
>
> Every literal at any depth must be normalized.
>
> Example:
> ```cpp
> auto a = (b + static_cast<int_t>(1)) * static_cast<int_t>(2);
> ```
>
> ### 14.2 Parentheses
> Preserve parentheses to maintain evaluation order.
>
> ### 14.3 Chained assignment
> Chained assignments must be decomposed into sequential statements.
>
> Example:
> ```cpp
> auto b = static_cast<int_t>(1);
> auto a = b;
> ```

## NOTE-044

**Source:** [generators/php/specs/rules.md:918](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## 15. Generalized operator families
>
> ### 15.1 Arithmetic family
> Covers:
> - `+`
> - `-`
> - `*`
> - `/`
> - `%`
>
> Rule:
> Binary arithmetic operations must be emitted as arithmetic on normalized runtime-typed operands. Any literal operand must first be converted with the appropriate `static_cast<..._t>(...)`.
>
> Examples:
> ```cpp
> auto a = static_cast<int_t>(1) + static_cast<int_t>(2);
> auto a = static_cast<int_t>(1) - static_cast<int_t>(2);
> auto a = static_cast<int_t>(2) * static_cast<int_t>(3);
> auto a = static_cast<int_t>(4) / static_cast<int_t>(2);
> auto a = static_cast<int_t>(5) % static_cast<int_t>(2);
> auto a = b + static_cast<int_t>(1) + c;
> ```
>
> ### 15.2 Concatenation family
> PHP `.` maps to C++ `+` on `string_t` operands.
>
> Examples:
> ```cpp
> auto a = string_t("a") + string_t("b");
> auto a = cast<string_t>(b) + string_t("x");
> auto a = string_t("x") + cast<string_t>(b);
> ```
>
> ### 15.3 Non-strict comparison family
> Covers:
> - `==`
> - `!=`
> - `<`
> - `<=`
> - `>`
> - `>=`
>
> Rule:
> Comparison operations must operate on normalized operands. Any literal operand must be converted using the appropriate runtime cast.
>
> Examples:
> ```cpp
> auto a = (b == static_cast<int_t>(1));
> auto a = (b != static_cast<int_t>(1));
> auto a = (b < static_cast<int_t>(1));
> auto a = (b <= static_cast<int_t>(1));
> auto a = (b > static_cast<int_t>(1));
> auto a = (b >= static_cast<int_t>(1));
> ```
>
> ### 15.4 Strict comparison family
> - `===` -> `php::identical(...)`
> - `!==` -> `php::not_identical(...)`
>
> Examples:
> ```cpp
> auto a = php::identical(b, static_cast<int_t>(1));
> auto a = php::not_identical(b, static_cast<int_t>(1));
> ```
>
> ### 15.5 Unary operators currently lowered directly
> Examples:
> ```cpp
> auto a = -b;
> auto a = +b;
> auto a = ~b;
> ```
>
> Notes:
> - unary minus lowers as `(-<expr>)`
> - unary plus lowers as `(+<expr>)`
> - unary bitwise NOT lowers as `(~<expr>)`
> - grouped unary/binary combinations must preserve AST structure, for example `(-a) * 2` and `(~a) * 2`

## NOTE-054

**Source:** [generators/php/specs/rules.md:1228](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## Assignment-expression lambda fallback
>
> - Default rule: assignment statements and simple assignment expressions lower directly and do **not** use a helper lambda.
> - Example statement: `$x[0]["name"] = "first";` lowers to `x[0]["name"] = "first";`.
> - Example simple expression: `$y = ($x[0]["name"] = "first");` lowers to `mixed_t y = (x[0]["name"] = "first");` when native C++ assignment-expression semantics already preserve the assigned value.
> - Fallback rule: emit a helper lambda only for complex expression contexts where the generator must guarantee single evaluation and return the assigned value explicitly, especially append expressions or larger composed expressions such as function-call arguments / concatenations that would otherwise duplicate work or lose PHP assignment-value semantics.
