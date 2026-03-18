# RUNTIME_INDEX.md

## Purpose

This document is the traceability index between:
- runtime requirement IDs
- runtime source files
- public symbols
- implementation responsibility

It is intended to make impact analysis faster when:
- a semantic rule changes
- a requirement changes
- a source file changes
- a test fails
- a new should-fail scenario is added

This index is subordinate to:
- `RUNTIME_REQUIREMENTS.md`
- `SPECIFICATIONS.md`
- `CASTING.md`
- `OBJECT_COMPARISON.md`

If a conflict appears, the specification documents and runtime requirements remain the source of truth.

---

## Usage Rules

- Each requirement ID should appear in:
	- `RUNTIME_REQUIREMENTS.md`
	- source code comments for the implementing symbol(s)
	- tests that validate the requirement
- This index should remain short and structural
- Do not duplicate full requirement text here
- When implementation moves between files, update this index immediately
- When a requirement is split, replace the old index rows with the new ones

---

## Index Format

Each row captures:

- **Requirement Prefix / ID**
- **Primary File**
- **Primary Symbol(s)**
- **Responsibility**
- **Notes**

---

## Core Runtime Index

| Requirement Prefix / ID | Primary File | Primary Symbol(s) | Responsibility | Notes |
|---|---|---|---|---|
| RT-CORE-* | `include/scpp/runtime.hpp` and `include/scpp/runtime_fwd.hpp` | shared forward declarations / common aliases | Core runtime entry surface and shared declarations | Optional aggregator layer if present |
| RT-CGEN-* | `include/scpp/runtime.hpp`, `include/scpp/memory.hpp` | exported public runtime API | Codegen-visible API boundary | Must match generated code assumptions and exclude native generated-language-visible types/API use |
| RT-INTG-* | `include/scpp/type_traits.hpp` and `include/scpp/internal/*` | constrained helper concepts / internal helpers only | Internal implementation support | Must not leak into generated-code-visible API |

---

## Null

| Requirement Prefix / ID | Primary File | Primary Symbol(s) | Responsibility | Notes |
|---|---|---|---|---|
| RT-NULL-* | `include/scpp/null_t.hpp` | `scpp::null_t`, `scpp::null` | Universal language-level null representation | Supports contextual null conversion only where allowed |

---

## Primitive Wrappers

| Requirement Prefix / ID | Primary File | Primary Symbol(s) | Responsibility | Notes |
|---|---|---|---|---|
| RT-BOOL-* | `include/scpp/bool_t.hpp` | `scpp::bool_t` | Semantic boolean wrapper | No host-language truthiness leakage |
| RT-INT-* | `include/scpp/int_t.hpp` | `scpp::int_t` | Semantic integer wrapper | Arithmetic and comparison rules must align with `CASTING.md` |
| RT-FLOAT-* | `include/scpp/float_t.hpp` | `scpp::float_t` | Semantic floating-point wrapper | Mixed numeric arithmetic may also require cross-file operators |
| RT-NUM-* | `include/scpp/int_t.hpp`, `include/scpp/float_t.hpp` | mixed numeric operators / comparison helpers | Shared numeric behavior across `int_t` and `float_t` | Keep responsibility explicit if extracted into a dedicated file |

---

## String

| Requirement Prefix / ID | Primary File | Primary Symbol(s) | Responsibility | Notes |
|---|---|---|---|---|
| RT-STR-* | `include/scpp/string_t.hpp` | `scpp::string_t` | Semantic string wrapper | Explicit string conversions only |

---

## Nullable

| Requirement Prefix / ID | Primary File | Primary Symbol(s) | Responsibility | Notes |
|---|---|---|---|---|
| RT-NBL-* | `include/scpp/nullable.hpp` | `scpp::nullable<T>` | Optional-like nullable wrapper | Must support `null_t` assignment/comparison where specified |

---

## Managed Ownership Types

| Requirement Prefix / ID | Primary File | Primary Symbol(s) | Responsibility | Notes |
|---|---|---|---|---|
| RT-SH-* | `include/scpp/shared_p.hpp` | `scpp::shared_p<T>` | Shared ownership wrapper | Public semantic wrapper over implementation storage |
| RT-UQ-* | `include/scpp/unique_p.hpp` | `scpp::unique_p<T>` | Unique ownership wrapper | Explicit ownership model only |
| RT-WK-* | `include/scpp/weak_p.hpp` | `scpp::weak_p<T>` | Non-owning reference wrapper | Must be derived from owning object or empty state |

---

## Memory Helpers / Factories

| Requirement Prefix / ID | Primary File | Primary Symbol(s) | Responsibility | Notes |
|---|---|---|---|---|
| RT-MEM-* | `include/scpp/memory.hpp` | `create<T>()`, `shared<T>()`, `unique<T>()`, `weak(x)` | Managed construction and ownership helper surface | `create<T>()` follows current default ownership policy |
| RT-OWN-* | `include/scpp/memory.hpp`, pointer wrapper headers | ownership policy helpers / glue | Ownership policy enforcement | Keep frontend lowering default separate from explicit ownership helpers |

---

## Vector

| Requirement Prefix / ID | Primary File | Primary Symbol(s) | Responsibility | Notes |
|---|---|---|---|---|
| RT-VEC-* | `include/scpp/vector_t.hpp` | `scpp::vector_t<T>` | Constrained vector wrapper | Keep minimal until spec deepens |

---

## Comparison Semantics

| Requirement Prefix / ID | Primary File | Primary Symbol(s) | Responsibility | Notes |
|---|---|---|---|---|
| RT-CMP-OBJ-* | `include/scpp/shared_p.hpp`, `include/scpp/unique_p.hpp`, `include/scpp/weak_p.hpp`, `include/scpp/nullable.hpp` | object/null comparison operators | Object identity and null-state comparison rules | Must align with `OBJECT_COMPARISON.md` |
| RT-CMP-NUM-* | `include/scpp/int_t.hpp`, `include/scpp/float_t.hpp` | numeric comparison operators | Numeric comparison promotion rules | Must align with `CASTING.md` |

---

## Source / Translation Unit Index

Use this section only if the runtime remains split into headers and `.cpp` files.

| Source File | Implements Mainly | Notes |
|---|---|---|
| `src/bool_t.cpp` | `RT-BOOL-*` | Out-of-line bool wrapper support currently used by the split build |
| `src/float_t.cpp` | `RT-FLOAT-*`, `RT-CMP-NUM-*` | Out-of-line float wrapper and numeric comparison support |
| `src/int_t.cpp` | `RT-INT-*`, `RT-NUM-*`, `RT-CMP-NUM-*` | Out-of-line integer wrapper plus mixed numeric operators |
| `src/string_t.cpp` | `RT-STR-*` | Explicit string conversion and parsing failure paths |

## Test Mapping Convention

Recommended test path structure:

```text
tests/language_surface/pass/RT-INT-03_04_basic.cpp
tests/language_surface/pass/RT-FLOAT-03_basic.cpp
tests/language_surface/fail_compile/RT-STR-05_no_implicit_numeric_concat.cpp
tests/runtime_mechanics/fail_compile/RT-SH-05_no_relational.cpp
```

Recommended rule:
- one primary requirement per test file
- additional related requirements may be listed in comments
- should-fail tests must state the expected reason
- use `language_surface` for generated-language-visible semantics and `runtime_mechanics` for host/runtime-only behavior

---

## Change Impact Workflow

When a logic change is proposed:

1. identify the affected requirement ID(s)
2. locate matching rows in this index
3. update the implementing symbol(s)
4. update requirement-linked source comments
5. update pass tests
6. update should-fail tests
7. update this index if files or primary symbols changed

---

## Maintenance Rule

This file should be updated whenever one of these changes:
- a new runtime file is introduced
- a requirement ID is renamed or split
- a primary implementing symbol changes
- a responsibility moves to another file

It should not be updated for ordinary wording-only edits unless traceability changed.
