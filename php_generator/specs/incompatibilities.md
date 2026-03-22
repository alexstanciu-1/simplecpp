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
