# Simple C++ – Incompatibilities / Semantic Mismatch Notes

This document tracks known or expected incompatibilities between PHP semantics and emitted C++ semantics, 
to be handled later by explicit rules, runtime helpers, or rejection rules.

## Open incompatibilities

### INCOMP-ARITH-DIV-001
**Area:** Arithmetic  
**Status:** Open  
**Priority:** Important for later

**Issue**  
Division is not identical between PHP and C++:
- PHP: `/` produces a floating-point result
- C++: `/` depends on operand types

**Why this matters**  
A direct emission of normalized numeric operands may still produce target semantics that do not match PHP if result-type normalization is not handled explicitly.

**Impact**  
This affects code generation for arithmetic expressions using `/`, especially when both operands are integer-like values.

**Required later decision**  
Add a rule for numeric promotion / result type normalization for division so the emitted C++ preserves the intended PHP behavior.

**Example**
PHP:
```php
$a = 4 / 2;
```

Current generated shape:
```cpp
auto a = static_cast<int_t>(4) / static_cast<int_t>(2);
```

**Risk**  
This may not preserve PHP semantics without an additional normalization rule.

**Required later decision**  
Add a rule for numeric promotion / result type normalization for division so the emitted C++ preserves the intended PHP behavior.

## INCOMP-COMP-LOOSE-001
**Area:** Comparison  
**Status:** Open  
**Priority:** Important for later

**Issue**  
PHP loose comparison (`==`, `!=`) is not identical to C++ `==` / `!=`.

**Why this matters**  
Current generation maps:
```php
$b == 1
$b != 1
```
to native C++ comparison on normalized operands, which may not preserve PHP loose-comparison semantics.

**Required later decision**  
Either:
- replace loose comparison with PHP-runtime helpers, or
- formally restrict the supported operand/type combinations.


## INCOMP-NS-USE-CONST-001
**Area:** Namespace import / `use const`  
**Status:** Open  
**Priority:** Important

**Issue**  
`use const` is currently lowered with a C++ `using` declaration:

```php
use const A\B\X;
```

```cpp
using ::scpp::A::B::X;
```

This diverges from PHP when the current namespace already defines a constant with the same short name.

**Observed behavior**  
PHP:
- `const X = 1; use const A\B\X; echo X;` may resolve to the imported constant

Current generated C++:
- the local constant and imported `using` declaration conflict at compile time

**Impact**  
This is a real namespace/import mismatch bucket specific to `use const` without aliasing.

**Current workaround**  
Alias the imported constant:

```php
use const A\B\X as Y;
```

This currently lowers correctly through:

```cpp
inline constexpr auto& Y = ::scpp::A::B::X;
```

**Required later decision**  
Either:
- keep this as an intentional non-PHP-compatible restriction, or
- add a dedicated constant-import lowering strategy that avoids the C++ `using` conflict.

## INCOMP-EXPR-PARENS-001
**Area:** Expression lowering  
**Status:** Open  
**Priority:** Important

**Issue**  
Grouped binary expressions may currently lose explicit parentheses during C++ emission.

**Example**  
PHP:
```php
echo ($a + $b) * $c;
```

Observed generated C++:
```cpp
::scpp::php::echo(a + b * c);
```

**Impact**  
This changes evaluation order and result:
- PHP: `(8 + 5) * 4 = 52`
- current generated C++: `8 + 5 * 4 = 28`

**Scope**  
This is not namespace-specific. It affects general expression emission wherever the AST encodes an explicitly grouped sub-expression.

**Required later decision**  
Preserve grouped-expression structure during emission so explicit PHP grouping survives into generated C++.
