# Prism++ vs PHP - Language Feature Gap Analysis
Doc Status: planning
## Scope
Language-level features only (no functions/helpers/runtime APIs).
Based on PHP language reference + project evidence.

## Legend
- **Confirmed missing** = verified from project behavior/specs
- **Probable missing** = no evidence of support; assume unsupported until verified

---

## Gap Table

| Area | PHP language feature | Status in Prism++ | Confidence | Note |
|---|---|---:|---:|---|
| File/program model | include / require | Confirmed missing | High | Core PHP file structure |
| Namespaces | Multiple namespace blocks per file | Confirmed missing | High | |
| Arrays | Real PHP arrays (ordered map) | Confirmed missing | High | |
| Iteration | foreach full semantics | Probable missing | Medium | depends on array model |
| References | Assignment by reference =& | Confirmed missing | High | |
| References | Full reference semantics | Confirmed missing | High | |
| Functions | Closures | Probable missing | Medium | |
| Functions | Closure capture (use) | Probable missing | Medium | |
| Functions | Arrow functions | Probable missing | Medium | |
| Functions | First-class callables | Probable missing | Medium | |
| Generators | yield | Probable missing | High | |
| Generators | yield from | Probable missing | High | |
| Async | Fibers | Probable missing | High | |
| Classes | Traits | Confirmed missing | High | |
| Classes | Trait conflict resolution | Confirmed missing | High | |
| Classes | Anonymous classes | Probable missing | High | |
| Classes | Dynamic properties | Confirmed missing | High | |
| Classes | Dynamic property names | Confirmed missing | High | |
| Classes | Dynamic method names | Confirmed missing | High | |
| Classes | static::$prop | Confirmed missing | High | |
| Classes | Magic methods (__get etc.) | Probable missing | Medium | |
| Enums | Enumerations | Probable missing | High | |
| Enums | Backed enums | Probable missing | High | |
| Attributes | Attributes #[...] | Probable missing | High | |
| Control flow | match expression | Probable missing | Medium | |
| Operators | Nullsafe operator ?-> | Probable missing | Medium | |
| Operators | and / or / xor | Confirmed missing | High | |
| Types | Untyped parameters | Confirmed missing | High | |
| Types | Untyped variadics | Confirmed missing | High | |
| Types | (array) cast | Confirmed missing | High | |
| Types | (object) cast | Confirmed missing | High | |
| Types | Union types | Probable missing | Medium | |
| Types | Constructor property promotion | Probable missing | Medium | |
| Arguments | Named arguments | Probable missing | Medium | |

---

## Highest Impact Gaps

1. include / require
2. PHP arrays
3. References
4. Closures
5. Generators (yield)
6. Traits
7. Anonymous classes
8. Dynamic object model
9. Modern PHP features (match, enums, attributes, fibers)

---

## Notes
This document separates:
- language-level absence
vs
- semantic differences

Further refinement recommended:
- Confirm each "Probable missing" against implementation.

