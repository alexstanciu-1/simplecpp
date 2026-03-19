# RUNTIME_CODING_RULES.md

## Scope

This document is **normative**.

It defines coding rules for implementing the Simple C++ runtime.

It does NOT define language semantics.

All semantic authority exists only in:
- SPECIFICATIONS.md
- TYPE_FAMILY_REGISTRY.md
- DERIVATION_RULES.md
- SEMANTIC_MATRIX.md

---

## Purpose

These rules exist to keep the runtime implementation:
- semantically strict
- predictable
- generator-safe
- resistant to accidental C++ broadening
- auditable

---

## Authority Relationship

1. SPECIFICATIONS.md — semantic core
2. TYPE_FAMILY_REGISTRY.md — family inventory and traits
3. DERIVATION_RULES.md — deterministic derivation logic
4. SEMANTIC_MATRIX.md — normalized operational outcomes
5. RUNTIME_API_CONTRACT.md — public runtime surface
6. RUNTIME_REQUIREMENTS.md — implementation obligations
7. RUNTIME_CODING_RULES.md — implementation coding discipline

If any conflict exists:
- the specification layer governs semantics
- runtime contract governs public shape
- this document governs implementation style and safety discipline

---

## Coding Rules

### 1. No semantic invention

Runtime code MUST NOT invent behavior not explicitly required by the specification layer.

Do not add:
- convenience overloads
- catch-all templates
- permissive forwarding constructors
- helper conversions exposed publicly
- implicit truthiness paths

### 2. Prefer compile-time restriction

Where behavior is forbidden by the matrix, implementation should prefer compile-time rejection using:
- deleted overloads
- constrained overload sets
- concepts
- `static_assert`
- non-viable overload design

Execution-time legality checks are not the preferred enforcement path for static type legality.

### 3. Keep public APIs narrow

Public runtime declarations should be only those required by RUNTIME_API_CONTRACT.md and justified by SEMANTIC_MATRIX.md.

Internal helpers belong in `scpp_intern`.

### 4. Do not rely on wrapped-type defaults

Do not rely on implicit behavior inherited from:
- builtin arithmetic types
- wrapped standard-library types
- pointer truthiness
- default comparison operators
- synthesized conversions

Every exposed semantic behavior must be deliberate.

### 5. Preserve family isolation

Implementation must keep family behavior isolated.

In particular:
- `nullable<T>` must not accidentally expose `T` arithmetic
- pointer-like families must not accidentally expose raw pointer-like conditionals
- wrapper implementation details must not leak into public semantics

### 6. Keep conversion surface explicit

Explicit conversion support must mirror the matrix exactly.

Do not add constructors or operators merely because two families share a trait category.

Traits support derivation and organization.
Traits do not authorize extra runtime surface on their own.

### 7. Avoid semantic duplication in comments and docs

Runtime code comments MAY explain implementation mechanics.

They MUST NOT become alternate semantic authority.

If a comment appears to define allowed/forbidden language behavior independently of the specification layer, the comment is invalid.

### 8. Deterministic diagnostics over accidental ambiguity

When choosing between:
- a design that cleanly rejects forbidden operations
- a design that happens to fail through overload confusion

prefer the clean, deliberate rejection path.

### 9. Keep future extension points internal

If helper machinery is added to support future families such as `map_t<K, V>`, it should remain internal until the specification layer explicitly authorizes those families.

---

## Final Statement

This document defines coding discipline for runtime implementation.

It supports the full specification layer but does not replace it.
