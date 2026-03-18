# TEST_COVERAGE.md

## Purpose

This document records per-requirement coverage status for the current runtime snapshot.

It audits `RUNTIME_REQUIREMENTS.md` against the actual current test suite and distinguishes between:
- direct executable coverage
- partial coverage
- audit/manual coverage
- frontend-only constraints that are not meaningfully enforceable through the current runtime test harness

## Status Legend

- **covered** = direct test coverage exists and is linked below
- **partial** = some important aspects are tested, but manual review or deeper tests are still needed
- **audit/manual** = primarily structural, comment-level, or source-audit requirement
- **frontend/manual** = primarily a frontend/codegen contract, not a runtime-only unit-test obligation

## Important Note About Requirement IDs

`RUNTIME_REQUIREMENTS.md` currently contains a duplicated pair of IDs:
- `RT-CGEN-06`
- `RT-CGEN-07`

The earlier occurrences define helper/codegen behavior.
The later occurrences in the appended boundary section express the stronger “no native generated-surface types” rule.
This coverage file keeps the original IDs intact and calls out the duplication in notes instead of renumbering them silently.

## Coverage Table

| Requirement ID | Status | Linked test file(s) | Notes |
|---|---|---|---|
| RT-NS-01 | audit/manual | — | Verified by source layout under `include/scpp/*` and `src/*`. |
| RT-NS-02 | audit/manual | — | Namespace/global-namespace discipline is a source audit concern. |
| RT-NS-03 | audit/manual | — | Public/internal `std::*` leakage needs source review more than unit tests. |
| RT-PUB-01 | audit/manual | — | Public surface minimization is a design audit concern. |
| RT-PUB-02 | partial | `tests/runtime_mechanics/pass/RT-MEM-01_02_03_04_result_types.cpp` | No raw-pointer API is used by tests; public-surface audit still required. |
| RT-PUB-03 | partial | `tests/language_surface/pass/RT-CGEN-01_02_03_04_05_entry_points.cpp` | Generated-surface usage avoids `std::*`; source audit still needed because some native bridge constructors remain public. |
| RT-TRACE-01 | audit/manual | — | Requirement IDs are present in source comments; verify by code review. |
| RT-TRACE-02 | audit/manual | — | Source-basis traceability is maintained in `RUNTIME_REQUIREMENTS.md`; not a runtime test. |
| RT-FAIL-01 | partial | multiple fail-compile tests | Forbidden API paths are blocked in many places, but not exhaustively enumerated. |
| RT-FAIL-02 | partial | multiple fail-compile tests, `tests/runtime_mechanics/fail_compile/RT-PTR-BOOL-01_no_bool_assignment_from_wrappers.cpp` | Compile-time blocking exists for many paths; some constraints remain runtime or audit-only. |
| RT-FAIL-03 | partial | `tests/language_surface/pass/RT-STR-08_invalid_conversion_throws.cpp`, fail-compile suite | No fallback coercions are observed in tested paths; still a broader audit concern. |
| RT-NULL-01 | covered | `tests/language_surface/pass/RT-NULL-01_02_06_basic.cpp` | — |
| RT-NULL-02 | covered | `tests/language_surface/pass/RT-NULL-01_02_06_basic.cpp` | — |
| RT-NULL-03 | covered | `tests/language_surface/pass/RT-NULL-03_interop.cpp` | — |
| RT-NULL-04 | covered | `tests/language_surface/fail_compile/RT-NULL-04_no_null_to_int.cpp` | — |
| RT-NULL-05 | partial | `tests/language_surface/fail_compile/RT-NULL-04_no_null_to_int.cpp`, `tests/language_surface/fail_compile/RT-NULL-07_invalid_int_compare.cpp` | Dynamic-carrier prevention is inferred by absent conversions; still needs source audit. |
| RT-NULL-06 | covered | `tests/language_surface/pass/RT-NULL-01_02_06_basic.cpp` | — |
| RT-NULL-07 | covered | `tests/language_surface/fail_compile/RT-NULL-07_invalid_int_compare.cpp`, null-comparison pass tests | — |
| RT-NULL-08 | frontend/manual | — | This is a frontend/codegen rejection rule; runtime unit tests cannot enforce it meaningfully. |
| RT-BOOL-01 | covered | `tests/language_surface/pass/RT-BOOL-01_03_04_basic.cpp` | — |
| RT-BOOL-02 | covered | `tests/language_surface/pass/RT-BOOL-02_05_entry_to_int.cpp` | — |
| RT-BOOL-03 | covered | `tests/language_surface/pass/RT-BOOL-01_03_04_basic.cpp` | — |
| RT-BOOL-04 | covered | `tests/language_surface/pass/RT-BOOL-01_03_04_basic.cpp` | — |
| RT-BOOL-05 | covered | `tests/language_surface/pass/RT-BOOL-02_05_entry_to_int.cpp` | — |
| RT-BOOL-06 | covered | `tests/language_surface/fail_compile/RT-BOOL-06_arithmetic_forbidden.cpp` | — |
| RT-BOOL-07 | covered | `tests/language_surface/fail_compile/RT-BOOL-07_no_implicit_string_to_bool.cpp`, `tests/language_surface/pass/RT-STR-06_to_int_float_bool.cpp` | Covers the string side of the matrix clearly. |
| RT-INT-01 | covered | `tests/language_surface/pass/RT-INT-01_02_05_07_storage_condition_explicit.cpp` | — |
| RT-INT-02 | covered | `tests/language_surface/pass/RT-INT-01_02_05_07_storage_condition_explicit.cpp` | — |
| RT-INT-03 | covered | `tests/language_surface/pass/RT-INT-03_04_basic.cpp`, `tests/language_surface/pass/RT-INT-03_07_compound_assignment_and_explicit_float_to_int.cpp` | includes compound assignment coverage |
| RT-INT-04 | covered | `tests/language_surface/pass/RT-INT-03_04_basic.cpp`, `tests/language_surface/pass/RT-INT-06_RT-FLOAT-04_mixed_numeric.cpp` | — |
| RT-INT-05 | covered | `tests/language_surface/pass/RT-INT-01_02_05_07_storage_condition_explicit.cpp` | Uses `condition_value()` explicitly. |
| RT-INT-06 | covered | `tests/language_surface/pass/RT-INT-06_RT-FLOAT-04_mixed_numeric.cpp` | — |
| RT-INT-07 | covered | `tests/language_surface/pass/RT-INT-01_02_05_07_storage_condition_explicit.cpp`, `tests/language_surface/pass/RT-INT-03_07_compound_assignment_and_explicit_float_to_int.cpp` | Explicit native extraction and explicit `float_t -> int_t` helper are covered. |
| RT-INT-08 | covered | `tests/language_surface/fail_compile/RT-INT-08_no_bool_or_null_arithmetic.cpp`, `tests/language_surface/fail_compile/RT-INT-08_no_pointer_arithmetic.cpp` | bool/null and pointer-like arithmetic are both blocked. |
| RT-FLOAT-01 | covered | `tests/language_surface/pass/RT-FLOAT-01_02_06_storage_explicit.cpp` | — |
| RT-FLOAT-02 | covered | `tests/language_surface/pass/RT-FLOAT-01_02_06_storage_explicit.cpp` | — |
| RT-FLOAT-03 | covered | `tests/language_surface/pass/RT-FLOAT-03_basic.cpp` | — |
| RT-FLOAT-04 | covered | `tests/language_surface/pass/RT-INT-06_RT-FLOAT-04_mixed_numeric.cpp` | — |
| RT-FLOAT-05 | covered | `tests/language_surface/fail_compile/RT-FLOAT-05_no_conditional_truthiness.cpp` | — |
| RT-FLOAT-06 | partial | `tests/language_surface/pass/RT-FLOAT-01_02_06_storage_explicit.cpp` | Explicit native extraction is tested; full matrix/no-others audit still needed. |
| RT-STR-01 | covered | `tests/language_surface/pass/RT-STR-01_02_construction_and_const_ref.cpp` | — |
| RT-STR-02 | covered | `tests/language_surface/pass/RT-STR-01_02_construction_and_const_ref.cpp` | — |
| RT-STR-03 | covered | `tests/language_surface/pass/RT-STR-03_04_basic.cpp` | — |
| RT-STR-04 | covered | `tests/language_surface/pass/RT-STR-03_04_basic.cpp` | — |
| RT-STR-05 | covered | `tests/language_surface/fail_compile/RT-STR-05_no_implicit_numeric_concat.cpp` | — |
| RT-STR-06 | covered | `tests/language_surface/pass/RT-STR-06_to_int_float_bool.cpp` | — |
| RT-STR-07 | covered | `tests/language_surface/pass/RT-STR-06_to_int_float_bool.cpp` | — |
| RT-STR-08 | covered | `tests/language_surface/pass/RT-STR-08_invalid_conversion_throws.cpp` | — |
| RT-NBL-01 | covered | `tests/language_surface/pass/RT-NBL-01_optional_integration.cpp` | — |
| RT-NBL-02 | covered | `tests/language_surface/pass/RT-NBL-02_03_04_05_basic.cpp` | — |
| RT-NBL-03 | covered | `tests/language_surface/pass/RT-NBL-02_03_04_05_basic.cpp` | — |
| RT-NBL-04 | covered | `tests/language_surface/pass/RT-NBL-02_03_04_05_basic.cpp` | — |
| RT-NBL-05 | covered | `tests/language_surface/pass/RT-NBL-02_03_04_05_basic.cpp` | — |
| RT-NBL-06 | covered | `tests/language_surface/pass/RT-NBL-06_relational.cpp`, `tests/runtime_mechanics/pass/RT-NBL-06_null_relational_throws.cpp` | Covers both current non-null and current null-side runtime behavior. |
| RT-NBL-07 | audit/manual | — | “Not pointer-like ownership” is a design/property audit rather than a runtime test. |
| RT-VEC-01 | partial | `tests/language_surface/pass/RT-VEC-03_04_05_basic.cpp` | Wrapper usage is covered; internal wrapping detail is source-audit oriented. |
| RT-VEC-02 | covered | `tests/language_surface/fail_compile/RT-VEC-02_no_arithmetic.cpp` | — |
| RT-VEC-03 | covered | `tests/language_surface/pass/RT-VEC-03_04_05_basic.cpp` | — |
| RT-VEC-04 | covered | `tests/language_surface/pass/RT-VEC-03_04_05_basic.cpp` | — |
| RT-VEC-05 | covered | `tests/language_surface/pass/RT-VEC-03_04_05_basic.cpp` | — |
| RT-SH-01 | partial | `tests/runtime_mechanics/pass/RT-MEM-01_02_03_04_result_types.cpp`, `tests/runtime_mechanics/pass/RT-SH-03_04_identity_null.cpp` | Shared ownership behavior is exercised; raw/native bridge exposure still needs audit. |
| RT-SH-02 | audit/manual | — | Public raw-pointer transfer absence is a public-API audit requirement. |
| RT-SH-03 | covered | `tests/runtime_mechanics/pass/RT-SH-03_04_identity_null.cpp` | — |
| RT-SH-04 | covered | `tests/runtime_mechanics/pass/RT-SH-03_04_identity_null.cpp` | — |
| RT-SH-05 | covered | `tests/runtime_mechanics/fail_compile/RT-SH-05_no_relational.cpp` | — |
| RT-SH-06 | covered | `tests/runtime_mechanics/fail_compile/RT-SH-06_no_cross_wrapper_compare.cpp` | — |
| RT-UQ-01 | covered | `tests/runtime_mechanics/pass/RT-UQ-01_02_move_semantics.cpp`, `tests/runtime_mechanics/pass/RT-MEM-01_02_03_04_result_types.cpp` | — |
| RT-UQ-02 | covered | `tests/runtime_mechanics/pass/RT-UQ-01_02_move_semantics.cpp` | — |
| RT-UQ-03 | covered | `tests/runtime_mechanics/pass/RT-UQ-03_04_identity_null.cpp` | — |
| RT-UQ-04 | covered | `tests/runtime_mechanics/pass/RT-UQ-03_04_identity_null.cpp` | — |
| RT-UQ-05 | covered | `tests/runtime_mechanics/fail_compile/RT-UQ-05_no_relational.cpp` | — |
| RT-UQ-06 | covered | `tests/runtime_mechanics/fail_compile/RT-UQ-06_no_cross_wrapper_compare.cpp` | — |
| RT-WK-01 | partial | `tests/runtime_mechanics/pass/RT-WK-03_04_05_06_basic.cpp` | Non-owning behavior is evidenced by expiry semantics; still partly conceptual/audit-level. |
| RT-WK-02 | covered | `tests/runtime_mechanics/fail_compile/RT-WK-02_not_primary_allocation.cpp` | — |
| RT-WK-03 | covered | `tests/runtime_mechanics/pass/RT-WK-03_04_05_06_basic.cpp` | — |
| RT-WK-04 | covered | `tests/runtime_mechanics/pass/RT-WK-03_04_05_06_basic.cpp` | — |
| RT-WK-05 | covered | `tests/runtime_mechanics/pass/RT-WK-03_04_05_06_basic.cpp` | — |
| RT-WK-06 | covered | `tests/runtime_mechanics/pass/RT-WK-03_04_05_06_basic.cpp` | — |
| RT-WK-07 | covered | `tests/runtime_mechanics/fail_compile/RT-WK-07_no_relational.cpp` | — |
| RT-MEM-01 | covered | `tests/runtime_mechanics/pass/RT-MEM-01_02_03_04_result_types.cpp` | — |
| RT-MEM-02 | covered | `tests/runtime_mechanics/pass/RT-MEM-01_02_03_04_result_types.cpp` | — |
| RT-MEM-03 | covered | `tests/runtime_mechanics/pass/RT-MEM-01_02_03_04_result_types.cpp` | — |
| RT-MEM-04 | covered | `tests/runtime_mechanics/pass/RT-MEM-01_02_03_04_result_types.cpp` | — |
| RT-MEM-05 | covered | `tests/runtime_mechanics/pass/RT-MEM-05_06_08_weak_helper_surface.cpp`, `tests/runtime_mechanics/pass/RT-WK-03_04_05_06_basic.cpp` | — |
| RT-MEM-06 | partial | `tests/runtime_mechanics/pass/RT-MEM-05_06_08_weak_helper_surface.cpp` | Result surface is tested; non-allocation property still needs implementation audit. |
| RT-MEM-07 | audit/manual | — | “No inference from optimization/operator usage” is a semantics/design audit rule. |
| RT-MEM-08 | partial | `tests/runtime_mechanics/pass/RT-MEM-05_06_08_weak_helper_surface.cpp`, `tests/language_surface/pass/RT-CGEN-01_02_03_04_05_entry_points.cpp` | Helper surface is directly targeted in tests; long-term stability remains a design promise. |
| RT-CGEN-01 | covered | `tests/language_surface/pass/RT-CGEN-01_02_03_04_05_entry_points.cpp` | — |
| RT-CGEN-02 | covered | `tests/language_surface/pass/RT-CGEN-01_02_03_04_05_entry_points.cpp`, `tests/runtime_mechanics/pass/RT-MEM-01_02_03_04_result_types.cpp` | — |
| RT-CGEN-03 | covered | `tests/language_surface/pass/RT-CGEN-01_02_03_04_05_entry_points.cpp`, `tests/runtime_mechanics/pass/RT-MEM-05_06_08_weak_helper_surface.cpp` | Renumbering normalized; this ID now only covers `weak(x)` derivation for generated code. |
| RT-CGEN-04 | covered | `tests/language_surface/pass/RT-CGEN-01_02_03_04_05_entry_points.cpp` | Renumbering normalized; this ID now only covers the no-raw-allocation requirement. |
| RT-CGEN-05 | covered | `tests/language_surface/pass/RT-CGEN-01_02_03_04_05_entry_points.cpp` | Test demonstrates generated-surface usage without naming internal storage types. |
| RT-CGEN-06 | partial | — | Code-generation boundary rule: generated Simple C++ code must not contain native C++ primitive or `std::*` surface usage. This is mainly a source-audit/codegen-discipline rule, not a runtime-enforceable compile test in the current setup. |
| RT-CGEN-07 | partial | — | Native interoperability must stay in explicit C++ bridge/integration code outside the generated Simple C++ surface. This is currently an architectural audit rule rather than a runtime-enforced test. |
| RT-CMT-01 | audit/manual | — | Source comments include requirement IDs; verify by code review. |
| RT-CMT-02 | audit/manual | — | Comment semantic quality is not a runtime test concern. |
| RT-CMT-03 | audit/manual | — | Constraint wording is a source review concern. |
| RT-CMT-04 | audit/manual | — | Internal boundary comments require code review. |
| RT-TST-01 | covered | full pass suite | Every public wrapper/helper has at least one positive construction test in the current suite. |
| RT-TST-02 | covered | full pass suite | Allowed operator families are exercised across value, string, nullable, and comparison tests. |
| RT-TST-03 | covered | full fail-compile suite | Compile-fail tests exist for major forbidden operator/conversion families. |
| RT-TST-04 | covered | `tests/runtime_mechanics/pass/RT-MEM-01_02_03_04_result_types.cpp` | — |
| RT-TST-05 | covered | `tests/runtime_mechanics/pass/RT-SH-03_04_identity_null.cpp`, `tests/runtime_mechanics/pass/RT-UQ-03_04_identity_null.cpp`, `tests/runtime_mechanics/pass/RT-WK-03_04_05_06_basic.cpp` | — |
| RT-TST-06 | covered | `tests/language_surface/pass/RT-STR-06_to_int_float_bool.cpp`, `tests/language_surface/pass/RT-STR-08_invalid_conversion_throws.cpp` | — |
| RT-TST-07 | covered | `tests/language_surface/pass/RT-NBL-02_03_04_05_basic.cpp`, `tests/language_surface/pass/RT-NBL-06_relational.cpp`, `tests/runtime_mechanics/pass/RT-NBL-06_null_relational_throws.cpp` | — |

## Remaining Uncovered Requirements After This Batch

There is no large remaining batch of executable runtime tests to add without either:
- turning audit/manual rules into stricter code constraints first, or
- extending the runtime surface/spec further

The remaining gaps are concentrated in:
- public-boundary/source-audit rules (`RT-NS-*`, `RT-PUB-*`, `RT-TRACE-*`, `RT-CMT-*`)
- frontend-only rules (`RT-NULL-08`)
- design-promise requirements (`RT-MEM-07`, parts of `RT-MEM-08`)
- matrix completeness rules that would benefit from additional negative cases (`RT-INT-07`, `RT-FLOAT-06`)

## Recommended Next Step

The next best move is no longer “add tests blindly.”

It is:
1. decide which audit/manual requirements should become enforceable code constraints
2. tighten the runtime API where those rules should be mechanically enforced
3. then add new compile-fail tests for those newly enforceable boundaries


## Hardening Note
The runtime now prefers explicit deleted overloads, deleted constructors, and constrained templates/concepts for unsupported or type-dependent paths so forbidden operations fail deterministically at compile time where practical.


## Hardening-specific tests added

- `tests/runtime_mechanics/pass/RT-PTR-BOOL-01_contextual_bool.cpp` confirms that explicit contextual `operator bool()` is usable in conditions for `shared_p<T>`, `unique_p<T>`, `weak_p<T>`, and `nullable<T>` without broadening ordinary implicit conversion semantics.
- `tests/runtime_mechanics/fail_compile/RT-PTR-BOOL-01_no_bool_assignment_from_wrappers.cpp` confirms that contextual `operator bool()` does not allow implicit assignment into `bool_t` or native `bool`.
