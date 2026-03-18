# OPERATOR_GRID_EXHAUSTIVE.md

## Purpose

This document expands the current semantic matrix into an **explicit, exhaustive operator grid** for the currently documented type families in Simple C++.

It is derived from the **current documentation state**:
- `SPECIFICATIONS.md`
- `SEMANTIC_MATRIX.md`
- `DESIGN_NOTES.md`

This file is **documentation-only**. It does not override the executable tests.

## Scope and interpretation

- `✔ ...` means the combination is currently documented as allowed.
- `❌` means the combination is currently documented as forbidden.
- `❌*` means the current docs do **not** define this combination; under the current spec rule (“if not listed, it is forbidden”), it is treated as forbidden, but it should still be clarified later if `nullable<T>` is meant to have first-class semantics.
- Comparison results are native C++ `bool`.
- Arithmetic tables apply to each binary arithmetic operator currently in scope: `+`, `-`, `*`, `/`.
- Relational comparison tables apply to: `<`, `<=`, `>`, `>=`.
- Equality tables apply to: `==`, `!=`.
- Compound assignment tables apply to the currently documented operators: `+=`, `-=`, `*=`, `/=`.

## Type families covered

- `int_t`
- `float_t`
- `bool_t`
- `string_t`
- `null_t`
- `shared_p<T>`
- `unique_p<T>`
- `weak_p<T>`
- `nullable<T>`

## 1. Binary arithmetic grid (`+`, `-`, `*`, `/`)

| LHS \\ RHS | int_t | float_t | bool_t | string_t | null_t | shared_p<T> | unique_p<T> | weak_p<T> | nullable<T> |
|---|---|---|---|---|---|---|---|---|---|
| int_t | ✔ int_t | ✔ float_t | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| float_t | ✔ float_t | ✔ float_t | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| bool_t | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| string_t | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| null_t | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| shared_p<T> | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| unique_p<T> | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| weak_p<T> | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| nullable<T> | ❌* | ❌* | ❌* | ❌* | ❌* | ❌* | ❌* | ❌* | ❌* |

## 2. Equality / inequality grid (`==`, `!=`)

| LHS \\ RHS | int_t | float_t | bool_t | string_t | null_t | shared_p<T> | unique_p<T> | weak_p<T> | nullable<T> |
|---|---|---|---|---|---|---|---|---|---|
| int_t | ✔ bool | ✔ bool | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌* |
| float_t | ✔ bool | ✔ bool | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌* |
| bool_t | ❌ | ❌ | ✔ bool | ❌ | ❌ | ❌ | ❌ | ❌ | ❌* |
| string_t | ❌ | ❌ | ❌ | ✔ bool | ❌ | ❌ | ❌ | ❌ | ❌* |
| null_t | ❌ | ❌ | ❌ | ❌ | ✔ bool | ❌ | ❌ | ❌ | ❌* |
| shared_p<T> | ❌ | ❌ | ❌ | ❌ | ❌ | ✔ bool (same wrapper, same T) | ❌ | ❌ | ❌* |
| unique_p<T> | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✔ bool (same wrapper, same T) | ❌ | ❌* |
| weak_p<T> | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✔ bool (same wrapper, same T) | ❌* |
| nullable<T> | ❌* | ❌* | ❌* | ❌* | ❌* | ❌* | ❌* | ❌* | ❌* |

## 3. Relational comparison grid (`<`, `<=`, `>`, `>=`)

| LHS \\ RHS | int_t | float_t | bool_t | string_t | null_t | shared_p<T> | unique_p<T> | weak_p<T> | nullable<T> |
|---|---|---|---|---|---|---|---|---|---|
| int_t | ✔ bool | ✔ bool | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌* |
| float_t | ✔ bool | ✔ bool | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌* |
| bool_t | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌* |
| string_t | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌* |
| null_t | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌* |
| shared_p<T> | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌* |
| unique_p<T> | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌* |
| weak_p<T> | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌* |
| nullable<T> | ❌* | ❌* | ❌* | ❌* | ❌* | ❌* | ❌* | ❌* | ❌* |

## 4. Basic assignment grid (`lhs = rhs`)

| LHS \\ RHS | int_t | float_t | bool_t | string_t | null_t | shared_p<T> | unique_p<T> | weak_p<T> | nullable<T> |
|---|---|---|---|---|---|---|---|---|---|
| int_t | ✔ int_t | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌* |
| float_t | ❌ | ✔ float_t | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌* |
| bool_t | ❌ | ❌ | ✔ bool_t | ❌ | ❌ | ❌ | ❌ | ❌ | ❌* |
| string_t | ❌ | ❌ | ❌ | ✔ string_t | ❌ | ❌ | ❌ | ❌ | ❌* |
| null_t | ❌ | ❌ | ❌ | ❌ | ✔ null_t | ❌ | ❌ | ❌ | ❌* |
| shared_p<T> | ❌ | ❌ | ❌ | ❌ | ❌ | ✔ shared_p<T> | ❌ | ❌ | ❌* |
| unique_p<T> | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✔ unique_p<T> | ❌ | ❌* |
| weak_p<T> | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✔ weak_p<T> | ❌* |
| nullable<T> | ❌* | ❌* | ❌* | ❌* | ❌* | ❌* | ❌* | ❌* | ✔ nullable<T>* |

## 5. Compound assignment grid (`+=`, `-=`, `*=`, `/=`)

| LHS \\ RHS | int_t | float_t | bool_t | string_t | null_t | shared_p<T> | unique_p<T> | weak_p<T> | nullable<T> |
|---|---|---|---|---|---|---|---|---|---|
| int_t | ✔ int_t | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌* |
| float_t | ❌ | ✔ float_t | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌* |
| bool_t | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌* |
| string_t | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌* |
| null_t | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌* |
| shared_p<T> | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌* |
| unique_p<T> | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌* |
| weak_p<T> | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌* |
| nullable<T> | ❌* | ❌* | ❌* | ❌* | ❌* | ❌* | ❌* | ❌* | ❌* |

## 6. Explicit conversion grid

| LHS \\ RHS | int_t | float_t | bool_t | string_t | null_t | shared_p<T> | unique_p<T> | weak_p<T> | nullable<T> |
|---|---|---|---|---|---|---|---|---|---|
| int_t | — | ✔ explicit | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌* |
| float_t | ✔ explicit via to_int() | — | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌* |
| bool_t | ✔ explicit | ❌ | — | ❌ | ❌ | ❌ | ❌ | ❌ | ❌* |
| string_t | ❌ | ❌ | ❌ | — | ❌ | ❌ | ❌ | ❌ | ❌* |
| null_t | ❌ | ❌ | ❌ | ❌ | — | ❌ | ❌ | ❌ | ❌* |
| shared_p<T> | ❌ | ❌ | ❌ | ❌ | ❌ | — | ❌ | ❌ | ❌* |
| unique_p<T> | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | — | ❌ | ❌* |
| weak_p<T> | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | — | ❌* |
| nullable<T> | ❌* | ❌* | ❌* | ❌* | ❌* | ❌* | ❌* | ❌* | —* |


## 7. Condition usage grid

| Type | Usable directly as condition |
|---|---|
| int_t | ❌ |
| float_t | ❌ |
| bool_t | ✔ |
| string_t | ❌ |
| null_t | ❌ |
| shared_p<T> | ❌ |
| unique_p<T> | ❌ |
| weak_p<T> | ❌ |
| nullable<T> | ❌* |

## 8. Current documentation gaps exposed by this exhaustive grid

### 8.1 `nullable<T>` semantics are not actually specified
The current docs mention `nullable<T>` as a type family, but they do not define:
- arithmetic behavior
- equality behavior
- relational behavior
- assignment compatibility against `T` or `null_t`
- conditional use

That is why the `nullable<T>` row/column is marked `❌*` almost everywhere.

### 8.2 Condition semantics still need one explicit bridge
The current docs say:
- conditions must resolve explicitly to `bool_t`
- comparisons return native C++ `bool`

That leaves one unresolved documentation gap:
- whether comparison results are directly usable in generated control flow without a `bool_t` wrapper step

This file does not resolve that gap; it only exposes it.

### 8.3 Assignment semantics remain intentionally strict
This grid documents only assignment combinations that are clearly stated by the current docs.
That means:
- no implicit same-family widening
- no implicit conversion on assignment
- no inferred support for `int_t = float_t`, `float_t = int_t`, etc.

If the intended design is broader than this, the docs should say so explicitly.

## 9. Recommended next follow-up

The next highest-value documentation step is to define one of these explicitly:
1. `nullable<T>` semantics
2. condition bridge semantics (`bool` vs `bool_t`)
3. assignment rules for explicit conversions vs assignment compatibility
