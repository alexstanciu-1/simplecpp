# Runtime Structure — Language Isolation & Operator Organization

## Purpose
Defines physical organization of runtime code to prevent semantic conflicts, enforce ownership, and avoid premature abstraction.

## 1. Language Isolation

### Rule 1.1 — One folder per language
Each supported language MUST have its own dedicated runtime folder.

Example:
runtime/include/lang/php/
runtime/include/lang/js/
runtime/include/lang/python/

All semantic logic for a language MUST live inside its language folder.

### Rule 1.2 — No cross-language semantic sharing
Semantic behavior MUST NOT be shared across languages.

Examples:
- truthiness rules
- null handling
- ?? behavior
- isset, empty, count
- string-to-bool interpretation
- comparison semantics

These MUST remain inside the owning language folder.

## 2. No Common Layer (for now)

### Rule 2.1 — No shared semantic layer
At this stage:
- NO shared semantic folder is allowed
- NO attempt to unify behavior across languages

### Rule 2.2 — Re-evaluation later
A shared layer may only be introduced after at least two languages are implemented and compared.

## 3. Operator Organization

### Rule 3.1 — Use operators/ folder
Each language MUST define:
operators/

Example:
runtime/include/lang/php/operators/

### Rule 3.2 — One operator family per file
Each operator family MUST be implemented in a dedicated file or subfolder.

Example:
operators/
    conditional/
        conditional_selection.hpp
        condition_truthiness.hpp
    coalesce/
        coalesce.hpp
    logical/
        logical.hpp
    comparison/
        comparison_strict.hpp

### Rule 3.3 — No operator logic outside operators/
Operator semantics MUST NOT be placed in support/, misc/, or unrelated runtime files.

## 4. Supporting Structure

Allowed:
operators/
intrinsics/
casts/
support/
detail/ (optional)

## 5. Forbidden Patterns

- shared semantic helpers across languages
- common truthiness/coalesce/cast logic
- operator logic outside operators/
- generic misc folders
- cross-language semantic includes

## Summary

- each language owns its runtime semantics
- no shared semantic layer yet
- operators are centralized under operators/
