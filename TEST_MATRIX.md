# TEST_MATRIX.md

## Purpose

This document maps the current runtime snapshot to the current requirement-based test suite.

It is based on the uploaded project snapshot used as source of truth for this chat.

The suite is now separated into:
- `tests/language_surface/pass/`
- `tests/language_surface/fail_compile/`
- `tests/runtime_mechanics/pass/`
- `tests/runtime_mechanics/fail_compile/`

This split makes the intent explicit:
- **language-surface** tests model what generated Simple C++ code is expected to rely on
- **runtime-mechanics** tests validate ownership/control details that require test helpers or host-side scaffolding

---

## Build Model

Current runtime snapshot is a split build, so all tests compile with:

```bash
g++ -std=c++20 -Wall -Wextra -Werror -Iinclude src/*.cpp <test>.cpp -o /tmp/test_bin
```

Pass suite:

```bash
./tests/run_pass.sh
```

Compile-fail suite:

```bash
./tests/run_fail_compile.sh
```

Expected result for `fail_compile`:
- compilation must fail
- failure reason should match the test comment at least in substance

---

## Coverage Status Legend

- **covered** = requirement has a direct primary test or a clearly linked test set
- **partial** = some parts are covered, but the requirement still needs manual audit or deeper tests
- **audit/manual** = not meaningfully testable through the current unit-test model, or primarily a source-structure rule
- **frontend/manual** = belongs mainly to frontend/codegen contract rather than runtime-only unit tests

---

## Current Test Inventory

### Language-surface pass tests

| Test File | Primary Requirement(s) | Status | Notes |
|---|---|---|---|
| `tests/language_surface/pass/RT-NULL-01_02_06_basic.cpp` | `RT-NULL-01`, `RT-NULL-02`, `RT-NULL-06` | covered | null constant and null equality |
| `tests/language_surface/pass/RT-NULL-03_interop.cpp` | `RT-NULL-03` | covered | contextual `nullptr` / `nullopt` interop |
| `tests/language_surface/pass/RT-BOOL-01_03_04_basic.cpp` | `RT-BOOL-01`, `RT-BOOL-03`, `RT-BOOL-04` | covered | bool wrapper equality and logical operators |
| `tests/language_surface/pass/RT-BOOL-02_05_entry_to_int.cpp` | `RT-BOOL-02`, `RT-BOOL-05` | covered | bool runtime entry and bool→int wrapper path |
| `tests/language_surface/pass/RT-INT-01_02_05_07_storage_condition_explicit.cpp` | `RT-INT-01`, `RT-INT-02`, `RT-INT-05`, `RT-INT-07` | covered | storage and explicit predicate/helper boundary; no source-language truthiness implied |
| `tests/language_surface/pass/RT-INT-03_04_basic.cpp` | `RT-INT-03`, `RT-INT-04` | covered | int arithmetic and comparison |
| `tests/language_surface/pass/RT-INT-06_RT-FLOAT-04_mixed_numeric.cpp` | `RT-INT-06`, `RT-FLOAT-04` | covered | mixed numeric promotion behavior |
| `tests/language_surface/pass/RT-FLOAT-01_02_06_storage_explicit.cpp` | `RT-FLOAT-01`, `RT-FLOAT-02`, `RT-FLOAT-06` | partial | explicit native extraction covered |
| `tests/language_surface/pass/RT-FLOAT-03_basic.cpp` | `RT-FLOAT-03` | covered | float arithmetic |
| `tests/language_surface/pass/RT-STR-01_02_construction_and_const_ref.cpp` | `RT-STR-01`, `RT-STR-02` | covered | wrapper construction and `const &` API use |
| `tests/language_surface/pass/RT-STR-03_04_basic.cpp` | `RT-STR-03`, `RT-STR-04` | covered | string concatenation and comparison |
| `tests/language_surface/pass/RT-STR-06_to_int_float_bool.cpp` | `RT-STR-06`, `RT-STR-07` | covered | explicit conversions and valid string→bool inputs |
| `tests/language_surface/pass/RT-STR-08_invalid_conversion_throws.cpp` | `RT-STR-08` | covered | centralized invalid-conversion failure path |
| `tests/language_surface/pass/RT-NBL-01_optional_integration.cpp` | `RT-NBL-01` | covered | null integration and value access |
| `tests/language_surface/pass/RT-NBL-02_03_04_05_basic.cpp` | `RT-NBL-02`, `RT-NBL-03`, `RT-NBL-04`, `RT-NBL-05` | covered | nullable construction, state, equality, null checks |
| `tests/language_surface/pass/RT-NBL-06_relational.cpp` | `RT-NBL-06` | covered | non-null relational path |
| `tests/language_surface/pass/RT-VEC-03_04_05_basic.cpp` | `RT-VEC-03`, `RT-VEC-04`, `RT-VEC-05` | covered | minimal vector operations |
| `tests/language_surface/pass/RT-CGEN-01_02_03_04_05_entry_points.cpp` | `RT-CGEN-01`..`RT-CGEN-05` | covered | direct generated-surface usage path covered for the primary entry-point requirements |

### Language-surface compile-fail tests

| Test File | Primary Requirement(s) | Status | Notes |
|---|---|---|---|
| `tests/language_surface/fail_compile/RT-BOOL-06_arithmetic_forbidden.cpp` | `RT-BOOL-06` | covered | `bool_t + bool_t` unavailable |
| `tests/language_surface/fail_compile/RT-BOOL-07_no_implicit_string_to_bool.cpp` | `RT-BOOL-07` | covered | implicit `string_t -> bool_t` unavailable |
| `tests/language_surface/fail_compile/RT-NULL-04_no_null_to_int.cpp` | `RT-NULL-04` | covered | primitive wrapper assignment from null unavailable |
| `tests/language_surface/fail_compile/RT-NULL-07_invalid_int_compare.cpp` | `RT-NULL-07` | covered | primitive vs `null` comparison unavailable |
| `tests/language_surface/fail_compile/RT-INT-08_no_bool_or_null_arithmetic.cpp` | `RT-INT-08` | covered | `int_t` arithmetic with `bool_t` / `null` unavailable |
| `tests/language_surface/fail_compile/RT-FLOAT-05_no_conditional_truthiness.cpp` | `RT-FLOAT-05` | covered | no implicit truthiness for `float_t` |
| `tests/language_surface/fail_compile/RT-STR-05_no_implicit_numeric_concat.cpp` | `RT-STR-05` | covered | `string_t + int_t` unavailable |
| `tests/language_surface/fail_compile/RT-VEC-02_no_arithmetic.cpp` | `RT-VEC-02` | covered | vector arithmetic unavailable |

### Runtime-mechanics pass tests

| Test File | Primary Requirement(s) | Status | Notes |
|---|---|---|---|
| `tests/runtime_mechanics/pass/RT-MEM-01_02_03_04_result_types.cpp` | `RT-MEM-01`..`RT-MEM-04` | covered | helper result types and default ownership |
| `tests/runtime_mechanics/pass/RT-MEM-05_06_08_weak_helper_surface.cpp` | `RT-MEM-05`, `RT-MEM-06`, `RT-MEM-08` | partial | `weak(x)` result and usage covered; non-allocation/stability aspects remain audit-linked |
| `tests/runtime_mechanics/pass/RT-SH-03_04_identity_null.cpp` | `RT-SH-03`, `RT-SH-04` | covered | shared identity and null-state comparison |
| `tests/runtime_mechanics/pass/RT-UQ-01_02_move_semantics.cpp` | `RT-UQ-01`, `RT-UQ-02` | covered | unique ownership and move-only behavior |
| `tests/runtime_mechanics/pass/RT-UQ-03_04_identity_null.cpp` | `RT-UQ-03`, `RT-UQ-04` | covered | unique identity and null-state comparison |
| `tests/runtime_mechanics/pass/RT-WK-03_04_05_06_basic.cpp` | `RT-WK-03`, `RT-WK-04`, `RT-WK-05`, `RT-WK-06` | covered | weak derivation, expiry, identity, null-state |
| `tests/runtime_mechanics/pass/RT-NBL-06_null_relational_throws.cpp` | `RT-NBL-06` | covered | current runtime null-side throw path |
| `tests/runtime_mechanics/pass/RT-PTR-BOOL-01_contextual_bool.cpp` | hardening / contextual bool boundary | covered | explicit `operator bool()` works in conditions for nullable/shared/unique/weak only |

### Runtime-mechanics compile-fail tests

| Test File | Primary Requirement(s) | Status | Notes |
|---|---|---|---|
| `tests/runtime_mechanics/fail_compile/RT-SH-05_no_relational.cpp` | `RT-SH-05` | covered | shared relational compare unavailable |
| `tests/runtime_mechanics/fail_compile/RT-SH-06_no_cross_wrapper_compare.cpp` | `RT-SH-06` | covered | shared cross-wrapper compare unavailable |
| `tests/runtime_mechanics/fail_compile/RT-UQ-05_no_relational.cpp` | `RT-UQ-05` | covered | unique relational compare unavailable |
| `tests/runtime_mechanics/fail_compile/RT-UQ-06_no_cross_wrapper_compare.cpp` | `RT-UQ-06` | covered | unique cross-wrapper compare unavailable |
| `tests/runtime_mechanics/fail_compile/RT-PTR-BOOL-01_no_bool_assignment_from_wrappers.cpp` | hardening / contextual bool boundary | covered | explicit contextual bool does not imply implicit assignment to `bool_t` or `bool` |
| `tests/runtime_mechanics/fail_compile/RT-WK-02_not_primary_allocation.cpp` | `RT-WK-02` | covered | no `weak<T>(...)` primary allocator |
| `tests/runtime_mechanics/fail_compile/RT-WK-07_no_relational.cpp` | `RT-WK-07` | covered | weak relational compare unavailable |

---

## Audit Result Against `RUNTIME_REQUIREMENTS.md`

### Newly covered in this batch

This update added tests specifically for previously uncovered runtime behaviors:
- `RT-NULL-03`
- `RT-NULL-07`
- `RT-BOOL-02`
- `RT-BOOL-05`
- `RT-INT-01`
- `RT-INT-02`
- `RT-INT-05`
- `RT-INT-07`
- `RT-INT-08`
- `RT-FLOAT-01`
- `RT-FLOAT-02`
- `RT-FLOAT-05`
- `RT-FLOAT-06` (partial)
- `RT-STR-01`
- `RT-STR-02`
- `RT-NBL-01`
- `RT-VEC-02`
- `RT-UQ-01`
- `RT-UQ-02`
- `RT-MEM-05`
- `RT-MEM-06` (partial)
- `RT-CGEN-01`..`RT-CGEN-05` (covered)
- `RT-CGEN-06`..`RT-CGEN-07` (audit-only / partial)

### Remaining uncovered by executable tests

After this batch, the remaining requirements are primarily:
- source-structure / public-boundary rules
- traceability/comment rules
- frontend-only constraints
- requirements that need source inspection more than runtime execution

See `TEST_COVERAGE.md` for the full per-requirement status table.


## Hardening Note
The runtime now prefers explicit deleted overloads, deleted constructors, and constrained templates/concepts for unsupported or type-dependent paths so forbidden operations fail deterministically at compile time where practical.
