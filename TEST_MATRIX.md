# TEST_MATRIX.md

## Purpose

This document maps the current runtime snapshot to an initial requirement-based test suite.

It is based on the uploaded project snapshot used as source of truth for this chat.

The suite is intentionally split into:
- `tests/pass/` for valid behavior
- `tests/fail_compile/` for compile-time rejection cases

This first batch focuses on requirements that are clearly implemented in the current codebase.
It does **not** claim full requirement coverage yet.

---

## Build Model

Current runtime snapshot is a split build, so tests should compile with:

```bash
g++ -std=c++20 -Wall -Wextra -Werror -Iinclude src/*.cpp tests/pass/<test>.cpp -o /tmp/test_bin
```

Compile-fail tests should be checked like this:

```bash
g++ -std=c++20 -Wall -Wextra -Werror -Iinclude src/*.cpp tests/fail_compile/<test>.cpp -o /tmp/test_bin
```

Expected result for `fail_compile`:
- compilation must fail
- failure reason should match the test comment at least in substance

---

## Coverage Status

### Covered in this batch
- null basics
- bool basics
- int basics
- float basics
- mixed numeric promotion behavior
- string basics and explicit conversions
- nullable basics
- vector minimal behavior
- shared/unique/weak helpers and comparisons
- ownership helper result types
- selected compile-time forbidden paths

### Not fully covered yet
- all cross-type forbidden combinations
- exhaustive object-comparison matrix
- every public comment/traceability rule
- build-system automation for pass/fail suites
- frontend/codegen integration assumptions
- all deferred or partially implemented requirements

---

## Test Index

| Test File | Primary Requirement(s) | Type | Notes |
|---|---|---|---|
| `tests/pass/RT-NULL-01_02_06_basic.cpp` | `RT-NULL-01`, `RT-NULL-02`, `RT-NULL-06` | pass | null constant and null equality |
| `tests/pass/RT-BOOL-01_03_04_basic.cpp` | `RT-BOOL-01`, `RT-BOOL-03`, `RT-BOOL-04` | pass | bool wrapper equality and logical operators |
| `tests/pass/RT-INT-03_04_basic.cpp` | `RT-INT-03`, `RT-INT-04` | pass | int arithmetic and comparison |
| `tests/pass/RT-INT-06_RT-FLOAT-04_mixed_numeric.cpp` | `RT-INT-06`, `RT-FLOAT-04` | pass | mixed int/float promotion behavior |
| `tests/pass/RT-FLOAT-03_basic.cpp` | `RT-FLOAT-03` | pass | float arithmetic |
| `tests/pass/RT-STR-03_04_basic.cpp` | `RT-STR-03`, `RT-STR-04` | pass | string concatenation and comparison |
| `tests/pass/RT-STR-06_to_int_float_bool.cpp` | `RT-STR-06`, `RT-STR-07` | pass | explicit string conversions |
| `tests/pass/RT-STR-08_invalid_conversion_throws.cpp` | `RT-STR-08` | pass | invalid explicit conversions use throw path |
| `tests/pass/RT-NBL-02_03_04_05_basic.cpp` | `RT-NBL-02`, `RT-NBL-03`, `RT-NBL-04`, `RT-NBL-05` | pass | nullable creation and equality/null checks |
| `tests/pass/RT-NBL-06_relational.cpp` | `RT-NBL-06` | pass | nullable relational behavior on non-null values |
| `tests/pass/RT-VEC-03_04_05_basic.cpp` | `RT-VEC-03`, `RT-VEC-04`, `RT-VEC-05` | pass | vector indexing, append, size |
| `tests/pass/RT-MEM-01_02_03_04_result_types.cpp` | `RT-MEM-01`..`RT-MEM-04` | pass | helper result-type and default ownership checks |
| `tests/pass/RT-SH-03_04_identity_null.cpp` | `RT-SH-03`, `RT-SH-04` | pass | shared identity and null-state comparison |
| `tests/pass/RT-UQ-03_04_identity_null.cpp` | `RT-UQ-03`, `RT-UQ-04` | pass | unique identity and null-state comparison |
| `tests/pass/RT-WK-03_04_05_06_basic.cpp` | `RT-WK-03`, `RT-WK-04`, `RT-WK-05`, `RT-WK-06` | pass | weak derivation, identity, and expired/null behavior |
| `tests/fail_compile/RT-BOOL-06_arithmetic_forbidden.cpp` | `RT-BOOL-06` | fail_compile | `bool_t + bool_t` must not compile |
| `tests/fail_compile/RT-STR-05_no_implicit_numeric_concat.cpp` | `RT-STR-05` | fail_compile | `string_t + int_t` must not compile |
| `tests/fail_compile/RT-SH-05_no_relational.cpp` | `RT-SH-05` | fail_compile | relational compare on `shared_p<T>` must not compile |
| `tests/fail_compile/RT-UQ-05_no_relational.cpp` | `RT-UQ-05` | fail_compile | relational compare on `unique_p<T>` must not compile |
| `tests/fail_compile/RT-WK-07_no_relational.cpp` | `RT-WK-07` | fail_compile | relational compare on `weak_p<T>` must not compile |
| `tests/fail_compile/RT-SH-06_no_cross_wrapper_compare.cpp` | `RT-SH-06` | fail_compile | `shared_p<T> == weak_p<T>` must not compile |
| `tests/fail_compile/RT-UQ-06_no_cross_wrapper_compare.cpp` | `RT-UQ-06` | fail_compile | `unique_p<T> == shared_p<T>` must not compile |
| `tests/fail_compile/RT-WK-02_not_primary_allocation.cpp` | `RT-WK-02` | fail_compile | `weak<T>(...)` primary allocation form must not exist |
| `tests/fail_compile/RT-NULL-04_no_null_to_int.cpp` | `RT-NULL-04` | fail_compile | `int_t x = null;` must not compile |
| `tests/fail_compile/RT-BOOL-07_no_implicit_string_to_bool.cpp` | `RT-BOOL-07`, `RT-STR-06` | fail_compile | implicit `string_t -> bool_t` must not compile |

---

## Notes on Current Snapshot

1. Some spec-level "must remain unavailable" rules are currently enforced by **absence of overloads**, not by explicit `= delete`.
2. `nullable<T>` relational comparison on null values currently throws at runtime rather than failing at compile time. The pass test reflects the implemented behavior on valid non-null inputs only.
3. String conversion failure is currently a centralized throw path in `src/string_t.cpp`, so invalid-conversion tests are runtime tests, not compile-fail tests.
4. Mixed numeric operators are defined out-of-line in `src/int_t.cpp`, so all tests must compile with `src/*.cpp`.

---

## Recommended Next Step After This Batch

After running this suite successfully, the next best move is to add:
- one test per remaining requirement ID
- a tiny test runner script for pass tests
- a tiny compile-fail checker script
- explicit notes in `RUNTIME_INDEX.md` linking tests back to symbols


## Test Intent Boundary

This suite contains two different kinds of tests:

- **language-surface tests**: these should prefer `scpp::*` value/field types because they model generated Simple C++ expectations
- **runtime-mechanics tests**: these may use small native C++ host-side helper structs when the purpose is only to validate ownership/control-block mechanics rather than generated-language-visible field semantics

Whenever a native helper struct appears in a test, the test file must say so explicitly.
