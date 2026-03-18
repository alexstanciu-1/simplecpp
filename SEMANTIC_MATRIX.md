# SEMANTIC_MATRIX.md

## Purpose

This document normalizes the current Simple C++ semantic rules into a matrix format so they can be:
- confirmed
- changed
- extended
- traced back into:
	- `RUNTIME_REQUIREMENTS.md`
	- source code
	- tests
	- coverage tracking

This file is a decision and normalization artifact.
It does not replace:
- `SPECIFICATIONS.md`
- `CASTING.md`
- `OBJECT_COMPARISON.md`

If a rule here conflicts with the core spec, the core spec remains authoritative until explicitly changed.

---

## Status Legend

- **Current** = matches current implemented/runtime-tested behavior or an agreed project rule
- **Spec** = stated in spec, but not fully normalized into exhaustive matrix/testing yet
- **Open** = still needs an explicit project decision
- **Candidate** = proposed normalization, needs confirmation

---

## Decision Columns

Use these columns when reviewing:
- **Decision**: `keep` / `change` / `remove` / `defer`
- **Notes**: short reason or replacement rule

---

# 1. Primitive Construction / Entry Rules

| Matrix ID | Area | Source / Form | Target | Allowed | Result | Enforcement | Status | Requirement(s) | Decision | Notes |
|---|---|---|---|---|---|---|---|---|---|---|
| SM-PRIM-001 | bool | native bool literal / value | `bool_t` | yes | `bool_t` | compile-time available | Current | `RT-BOOL-01` |  |  |
| SM-PRIM-002 | int | native integer literal / value | `int_t` | yes | `int_t` | compile-time available | Current | `RT-INT-01` |  |  |
| SM-PRIM-003 | float | native floating literal / value | `float_t` | yes | `float_t` | compile-time available | Current | `RT-FLOAT-01` |  |  |
| SM-PRIM-004 | string | native string literal / string-like input | `string_t` | yes | `string_t` | compile-time available | Current | `RT-STR-01` |  |  |
| SM-PRIM-005 | null | `null` | `null_t` | yes | `null_t` | compile-time available | Current | `RT-NULL-01` |  |  |
| SM-PRIM-006 | bool | `int_t` | `bool_t` | no | n/a | compile-time error | Current | `RT-BOOL-07`, `RT-FAIL-01` |  |  |
| SM-PRIM-007 | int | `bool_t` | `int_t` | yes (implicit) | `int_t` | compile-time available | Current | `RT-BOOL-05`, `RT-INT-06` |  |  |
| SM-PRIM-008 | float | `int_t` | `float_t` | yes (implicit) | `float_t` | compile-time available | Current | `RT-INT-06`, `RT-FLOAT-04` |  |  |
| SM-PRIM-009 | int | `float_t` | `int_t` | no implicit / yes explicit | `int_t` | compile-time available only via explicit path | Spec | `RT-INT-05`, `RT-FAIL-01` |  |  |
| SM-PRIM-010 | bool | `string_t` | `bool_t` | no implicit / yes explicit | `bool_t` | compile-time available only via explicit path | Current | `RT-STR-06`, `RT-BOOL-07` |  |  |
| SM-PRIM-011 | int | `string_t` | `int_t` | no implicit / yes explicit | `int_t` | compile-time available only via explicit path | Current | `RT-STR-06`, `RT-FAIL-01` |  |  |
| SM-PRIM-012 | float | `string_t` | `float_t` | no implicit / yes explicit | `float_t` | compile-time available only via explicit path | Current | `RT-STR-06`, `RT-FAIL-01` |  |  |
| SM-PRIM-013 | string | `bool_t` / `int_t` / `float_t` | `string_t` | yes explicit | `string_t` | compile-time available only via explicit helper path | Current | `RT-STR-06` |  |  |

---

# 2. Null Conversion / Context Rules

| Matrix ID | Area | Source / Form | Target / Context | Allowed | Result | Enforcement | Status | Requirement(s) | Decision | Notes |
|---|---|---|---|---|---|---|---|---|---|---|
| SM-NULL-001 | null | `null_t` | `nullable<T>` | yes | empty `nullable<T>` | compile-time available | Current | `RT-NULL-02`, `RT-NBL-02` |  |  |
| SM-NULL-002 | null | `null_t` | `shared_p<T>` | yes | empty `shared_p<T>` | compile-time available | Current | `RT-NULL-02`, `RT-SH-04` |  |  |
| SM-NULL-003 | null | `null_t` | `unique_p<T>` | yes | empty `unique_p<T>` | compile-time available | Current | `RT-NULL-02`, `RT-UQ-04` |  |  |
| SM-NULL-004 | null | `null_t` | `weak_p<T>` | yes | empty `weak_p<T>` | compile-time available | Current | `RT-NULL-02`, `RT-WK-06` |  |  |
| SM-NULL-005 | null | `null_t` | `bool_t` / `int_t` / `float_t` / `string_t` | no | n/a | compile-time error | Current | `RT-NULL-04`, `RT-FAIL-01` |  |  |
| SM-NULL-006 | null | `null_t` | arithmetic operand | no | n/a | compile-time error | Current | `RT-NULL-07`, `RT-FAIL-01` |  |  |
| SM-NULL-007 | null | `null_t` | conditional | Open | Open | Open | Open | `RT-NULL-05` |  | not decided in this pass |
| SM-NULL-008 | null | `null_t == X` / `null_t != X` | any Simple C++ value | yes | equality true only for semantic-null states | compile-time available | Current | `RT-NULL-07`, `RT-NULL-08` |  | only `==` and `!=` |
| SM-NULL-009 | null | `null_t` | relational (`<`, `<=`, `>`, `>=`) with anything | no | n/a | compile-time error | Current | `RT-NULL-07`, `RT-FAIL-01` |  |  |
| SM-NULL-010 | null | `null_t` | unresolved target context (for example `auto x = null;`) | no | n/a | compile-time error | Current | `RT-NULL-09`, `RT-FAIL-01` |  | overloads/templates are out of generated-language scope |

---

# 3. Arithmetic Operators

Supported operators considered here:
- `+`
- `-`
- `*`
- `/`

| Matrix ID | Left | Right | Allowed | Result Type | Enforcement | Status | Requirement(s) | Decision | Notes |
|---|---|---|---|---|---|---|---|---|---|
| SM-ARITH-001 | `int_t` | `int_t` | yes | `int_t` | compile-time available | Current | `RT-INT-03` |  |  |
| SM-ARITH-002 | `int_t` | `float_t` | yes | `float_t` | compile-time available | Current | `RT-INT-06`, `RT-FLOAT-04` |  |  |
| SM-ARITH-003 | `float_t` | `int_t` | yes | `float_t` | compile-time available | Current | `RT-INT-06`, `RT-FLOAT-04` |  |  |
| SM-ARITH-004 | `float_t` | `float_t` | yes | `float_t` | compile-time available | Current | `RT-FLOAT-03` |  |  |
| SM-ARITH-005 | `bool_t` | any arithmetic operand | no | n/a | compile-time error | Current | `RT-BOOL-06`, `RT-FAIL-01` |  |  |
| SM-ARITH-006 | `string_t` | arithmetic operand | no | n/a | compile-time error | Current | `RT-STR-05`, `RT-FAIL-01` |  | except string + string concatenation |
| SM-ARITH-007 | `null_t` | arithmetic operand | no | n/a | compile-time error | Current | `RT-NULL-07`, `RT-FAIL-01` |  |  |
| SM-ARITH-008 | `nullable<T>` | any arithmetic operand | no by default | n/a | compile-time error | Candidate | `RT-NBL-*`, `RT-FAIL-01` |  | confirm no arithmetic surface |
| SM-ARITH-009 | `vector_t<T>` | any arithmetic operand | no | n/a | compile-time error | Current | `RT-VEC-02`, `RT-FAIL-01` |  |  |
| SM-ARITH-010 | pointer-like wrappers | any arithmetic operand | no | n/a | compile-time error | Candidate | `RT-SH-*`, `RT-UQ-*`, `RT-WK-*`, `RT-FAIL-01` |  | confirm explicitly forbidden surface |

---

# 4. Comparison Operators

Supported operators considered here:
- `==`
- `!=`
- `<`
- `<=`
- `>`
- `>=`

## 4.1 Primitive / String Comparison

| Matrix ID | Left | Right | Allowed | Result Type | Enforcement | Status | Requirement(s) | Decision | Notes |
|---|---|---|---|---|---|---|---|---|---|
| SM-CMP-001 | `bool_t` | `bool_t` | `==`, `!=` only | `bool_t` | compile-time available | Current | `RT-BOOL-03` |  |  |
| SM-CMP-002 | `int_t` | `int_t` | all six | `bool_t` | compile-time available | Current | `RT-INT-04` |  |  |
| SM-CMP-003 | `int_t` | `float_t` | all six | `bool_t` | compile-time available with float promotion | Current | `RT-FLOAT-04` |  |  |
| SM-CMP-004 | `float_t` | `int_t` | all six | `bool_t` | compile-time available with float promotion | Current | `RT-FLOAT-04` |  |  |
| SM-CMP-005 | `float_t` | `float_t` | all six | `bool_t` | compile-time available | Current | `RT-FLOAT-03` |  |  |
| SM-CMP-006 | `string_t` | `string_t` | all six | `bool_t` | compile-time available | Current | `RT-STR-04` |  | lexicographic |
| SM-CMP-007 | `string_t` | numeric / bool | no | n/a | compile-time error | Spec | `RT-FAIL-01` |  | expand tests exhaustively |
| SM-CMP-008 | `bool_t` | numeric / string | no unless explicitly specified | n/a | compile-time error | Spec | `RT-FAIL-01` |  | need exhaustive matrix rows |

## 4.2 Null Comparison

| Matrix ID | Left | Right | Allowed | Result Type | Enforcement | Status | Requirement(s) | Decision | Notes |
|---|---|---|---|---|---|---|---|---|---|
| SM-CMP-009 | `null_t` | `null_t` | `==`, `!=` only | `bool_t` | compile-time available | Current | `RT-NULL-06` |  |  |
| SM-CMP-010 | `null_t` | any Simple C++ value | `==`, `!=` only | `bool_t` | compile-time available | Current | `RT-NULL-07`, `RT-NULL-08` |  | true only for semantic-null values |
| SM-CMP-011 | `null_t` | relational compare with anything | no | n/a | compile-time error | Current | `RT-NULL-07`, `RT-FAIL-01` |  |  |

## 4.3 Nullable Comparison

| Matrix ID | Left | Right | Allowed | Result Type | Enforcement | Status | Requirement(s) | Decision | Notes |
|---|---|---|---|---|---|---|---|---|---|
| SM-CMP-012 | `nullable<T>` | `nullable<T>` | `==`, `!=` | `bool_t` | compile-time available | Current | `RT-NBL-04` |  | compare empty/value states and values |
| SM-CMP-013 | `nullable<T>` | `null_t` | `==`, `!=` | `bool_t` | compile-time available | Current | `RT-NBL-05`, `RT-NULL-08` |  |  |
| SM-CMP-014 | `nullable<T>` | `nullable<T>` | relational operators | `bool_t` | runtime-valid only when both non-null | Current | `RT-NBL-06` |  | current implementation throws on null-side relations |
| SM-CMP-015 | `nullable<T>` | different `nullable<U>` | no by default | n/a | compile-time error | Candidate | `RT-FAIL-01` |  | confirm strict same-T requirement |
| SM-CMP-016 | `nullable<T>` | raw `T` | Open | Open | Open | Open | `RT-NBL-*` |  | decide if direct comparisons should exist |

## 4.4 Object / Ownership Wrapper Comparison

| Matrix ID | Left | Right | Allowed | Result Type | Enforcement | Status | Requirement(s) | Decision | Notes |
|---|---|---|---|---|---|---|---|---|---|
| SM-CMP-017 | `shared_p<T>` | `shared_p<T>` | `==`, `!=` only | `bool_t` | compile-time available | Current | `RT-SH-03` |  | identity |
| SM-CMP-018 | `unique_p<T>` | `unique_p<T>` | `==`, `!=` only | `bool_t` | compile-time available | Current | `RT-UQ-03` |  | identity / current ownership state |
| SM-CMP-019 | `weak_p<T>` | `weak_p<T>` | `==`, `!=` only | `bool_t` | compile-time available | Current | `RT-WK-05` |  | resolved identity / expired-as-null semantics |
| SM-CMP-020 | `shared_p<T>` | relational compare | no | n/a | compile-time error | Current | `RT-SH-05` |  |  |
| SM-CMP-021 | `unique_p<T>` | relational compare | no | n/a | compile-time error | Current | `RT-UQ-05` |  |  |
| SM-CMP-022 | `weak_p<T>` | relational compare | no | n/a | compile-time error | Current | `RT-WK-07` |  |  |
| SM-CMP-023 | cross-wrapper compare (`shared_p<T>` vs `weak_p<T>`, etc.) | any compare except via `null` | no | n/a | compile-time error | Current | `RT-SH-06`, `RT-UQ-06`, `RT-FAIL-01` |  |  |
| SM-CMP-024 | same wrapper, different `T` | any compare | no by default | n/a | compile-time error | Candidate | `RT-FAIL-01` |  | confirm explicit same-T requirement |

---

# 5. Logical Operators and Conditional Semantics

## 5.1 Logical Operators

| Matrix ID | Operand(s) | Allowed | Result Type | Enforcement | Status | Requirement(s) | Decision | Notes |
|---|---|---|---|---|---|---|---|---|
| SM-LOG-001 | `bool_t && bool_t` / `bool_t || bool_t` / `!bool_t` | yes | `bool_t` | compile-time available | Current | `RT-BOOL-04` |  |  |
| SM-LOG-002 | non-`bool_t` logical operators | no | n/a | compile-time error | Candidate | `RT-FAIL-01` |  | confirm universal rule |

## 5.2 Conditional Use (`if`, `while`, etc.)

| Matrix ID | Expression Type | Allowed in condition | Interpretation | Enforcement | Status | Requirement(s) | Decision | Notes |
|---|---|---|---|---|---|---|---|---|
| SM-COND-001 | `bool_t` | yes | direct boolean value | compile-time available | Current | `RT-BOOL-04` |  |  |
| SM-COND-002 | `int_t` | yes | zero = false, non-zero = true | compile-time available in conditional only | Current | `RT-INT-07` |  |  |
| SM-COND-003 | `null_t` | Open | Open | Open | Open | `RT-NULL-05` |  | not decided in this pass |
| SM-COND-004 | `float_t` | no | n/a | compile-time error | Current | `RT-FLOAT-05` |  |  |
| SM-COND-005 | `string_t` | no | n/a | compile-time error | Candidate | `RT-FAIL-01` |  | should be explicit if desired |
| SM-COND-006 | `nullable<T>` | Open | Open | Open | Open | `RT-NBL-*` |  | decide whether nullable truthiness exists |
| SM-COND-007 | pointer-like wrappers | no by default | n/a | compile-time error | Candidate | `RT-FAIL-01` |  | confirm no pointer truthiness |
| SM-COND-008 | `vector_t<T>` | no | n/a | compile-time error | Candidate | `RT-FAIL-01` |  | confirm no collection truthiness |

---

# 6. Assignment Semantics

## 6.1 Plain Assignment

| Matrix ID | Source | Target | Allowed | Enforcement | Status | Requirement(s) | Decision | Notes |
|---|---|---|---|---|---|---|---|---|
| SM-ASG-001 | same type | same type | yes | compile-time available | Current | `RT-INT-03`, `RT-FLOAT-03`, `RT-STR-03`, `RT-NBL-*`, `RT-SH-*`, `RT-UQ-*`, `RT-WK-*` |  | basic value/wrapper assignment |
| SM-ASG-002 | `bool_t` | `int_t` | yes | compile-time available | Spec | `RT-BOOL-05`, `RT-INT-06` |  | confirm plain assignment path, not only expression path |
| SM-ASG-003 | `int_t` | `float_t` | yes | compile-time available | Spec | `RT-INT-06`, `RT-FLOAT-04` |  | confirm plain assignment path |
| SM-ASG-004 | `float_t` | `int_t` | no implicit / yes explicit | compile-time explicit only | Spec | `RT-INT-05`, `RT-FAIL-01` |  |  |
| SM-ASG-005 | `string_t` | primitive wrappers | no implicit / yes explicit | compile-time explicit only | Current | `RT-STR-06` |  | via helper conversions |
| SM-ASG-006 | `null_t` | nullable / pointer-like wrappers | yes | compile-time available | Current | `RT-NULL-02`, `RT-NBL-02`, `RT-SH-04`, `RT-UQ-04`, `RT-WK-06` |  |  |
| SM-ASG-007 | cross-wrapper ownership assignment | no by default | compile-time error | Candidate | `RT-FAIL-01` |  | confirm no bridge assignment |
| SM-ASG-008 | native type entry directly in generated code | no | compile-time / codegen rule | Current | `RT-CGEN-06`, `RT-CGEN-07` |  | mostly codegen policy |

## 6.2 Compound Assignment

| Matrix ID | Form | Allowed | Result | Enforcement | Status | Requirement(s) | Decision | Notes |
|---|---|---|---|---|---|---|---|---|
| SM-ASG-009 | `int_t += int_t`, `-=`, `*=`, `/=` | yes | `int_t` | compile-time available | Spec | `RT-INT-03` |  | confirm all four are required |
| SM-ASG-010 | `float_t += float_t`, `-=`, `*=`, `/=` | yes | `float_t` | compile-time available | Spec | `RT-FLOAT-03` |  | confirm all four are required |
| SM-ASG-011 | mixed numeric compound assignment | Open | Open | Open | Open | `RT-INT-*`, `RT-FLOAT-*` |  | need exact rule |
| SM-ASG-012 | `string_t += string_t` | yes | `string_t` | compile-time available | Current | `RT-STR-03` |  |  |
| SM-ASG-013 | all other compound assignment combinations | no | n/a | compile-time error | Candidate | `RT-FAIL-01` |  | confirm global ban |

---

# 7. Explicit Conversion Helpers

| Matrix ID | Helper / Form | Input | Output | Allowed | Invalid Case Handling | Status | Requirement(s) | Decision | Notes |
|---|---|---|---|---|---|---|---|---|---|
| SM-CONV-001 | `to_int(...)` | `string_t` | `int_t` | yes | runtime failure on invalid dynamic input | Current | `RT-STR-06`, `RT-STR-08` |  |  |
| SM-CONV-002 | `to_float(...)` | `string_t` | `float_t` | yes | runtime failure on invalid dynamic input | Current | `RT-STR-06`, `RT-STR-08` |  |  |
| SM-CONV-003 | `to_bool(...)` | `string_t` | `bool_t` | yes | runtime failure on invalid dynamic input | Current | `RT-STR-06`, `RT-STR-07`, `RT-STR-08` |  |  |
| SM-CONV-004 | `to_string(...)` | `bool_t` / `int_t` / `float_t` | `string_t` | yes | n/a | Current | `RT-STR-06` |  |  |
| SM-CONV-005 | explicit cast/helper | `float_t` | `int_t` | yes | n/a | Spec | `RT-INT-05` |  | confirm chosen syntax |
| SM-CONV-006 | explicit cast/helper | `int_t` | `float_t` | yes | n/a | Spec | `RT-FLOAT-04` |  | confirm chosen syntax |
| SM-CONV-007 | invalid explicit primitive/object conversion outside matrix | any | any | no | compile-time error | Spec | `RT-FAIL-01` |  | needs exhaustive matrix rows |
| SM-CONV-008 | invalid explicit string conversion from compile-time known invalid constant | `string_t` constant | primitive wrapper | Open | Open | Open | `RT-STR-08` |  | decide compile-time vs runtime for constant inputs |

---

# 8. String Boolean Normalization

| Matrix ID | Input `string_t` value | Allowed | Output | Invalid | Status | Requirement(s) | Decision | Notes |
|---|---|---|---|---|---|---|---|---|
| SM-STRBOOL-001 | `"true"` | yes | `bool_t(true)` | no | Current | `RT-STR-07` |  |  |
| SM-STRBOOL-002 | `"false"` | yes | `bool_t(false)` | no | Current | `RT-STR-07` |  |  |
| SM-STRBOOL-003 | `"1"` | yes | `bool_t(true)` | no | Current | `RT-STR-07` |  |  |
| SM-STRBOOL-004 | `"0"` | yes | `bool_t(false)` | no | Current | `RT-STR-07` |  |  |
| SM-STRBOOL-005 | `""` | Open | Open | Open | Open | `RT-STR-07` |  | decide whether empty string is accepted |
| SM-STRBOOL-006 | whitespace-only | Open | Open | Open | Open | `RT-STR-07` |  | decide trimming policy |
| SM-STRBOOL-007 | `"yes"`, `"no"`, `"on"`, `"off"` | no currently | n/a | runtime failure | Current | `RT-STR-08` |  |  |
| SM-STRBOOL-008 | case-insensitive variants (`"True"`, `"FALSE"`) | Open | Open | Open | Open | `RT-STR-07` |  | decide case policy |

---

# 9. Vector Semantics

| Matrix ID | Operation / Form | Allowed | Result / Meaning | Enforcement | Status | Requirement(s) | Decision | Notes |
|---|---|---|---|---|---|---|---|---|
| SM-VEC-001 | default construction | yes | empty `vector_t<T>` | compile-time available | Current | `RT-VEC-01` |  |  |
| SM-VEC-002 | indexing `v[i]` | yes | element reference/value | compile-time available | Current | `RT-VEC-03` |  |  |
| SM-VEC-003 | append helper `append(value)` | yes | element appended | compile-time available | Current | `RT-VEC-04` |  |  |
| SM-VEC-004 | size query | yes | size value | compile-time available | Current | `RT-VEC-05` |  |  |
| SM-VEC-005 | arithmetic on vector | no | n/a | compile-time error | Current | `RT-VEC-02` |  |  |
| SM-VEC-006 | append via spec syntax `v[] = value` | Open | append | Open | Open | `RT-VEC-04` |  | current runtime uses `append(...)` helper |
| SM-VEC-007 | bounds behavior on invalid index | Open | Open | Open | Open | `RT-VEC-*` |  | decide checked vs unchecked contract |
| SM-VEC-008 | comparison semantics | Open | Open | Open | Open | `RT-VEC-*`, `RT-FAIL-01` |  | likely no implicit comparison surface by default |

---

# 10. Nullable Semantics

| Matrix ID | Rule | Allowed | Result / Meaning | Enforcement | Status | Requirement(s) | Decision | Notes |
|---|---|---|---|---|---|---|---|---|
| SM-NBL-001 | `nullable<T>` from `T` | yes | value-present nullable | compile-time available | Current | `RT-NBL-02` |  |  |
| SM-NBL-002 | `nullable<T>` from `null` | yes | empty nullable | compile-time available | Current | `RT-NBL-02`, `RT-NULL-08` |  |  |
| SM-NBL-003 | `has_value()` / empty-state query | yes | state query | compile-time available | Current | `RT-NBL-03` |  |  |
| SM-NBL-004 | `nullable<T> == nullable<T>` | yes | compare state/values | compile-time available | Current | `RT-NBL-04` |  |  |
| SM-NBL-005 | `nullable<T> == null` | yes | empty-state compare | compile-time available | Current | `RT-NBL-05`, `RT-NULL-08` |  |  |
| SM-NBL-006 | relational compare when both sides non-null and `T` supports compare | yes | compare contained values | runtime valid | Current | `RT-NBL-06` |  |  |
| SM-NBL-007 | relational compare when one or both sides null | Open / currently throw | Open | runtime throw currently | Current | `RT-NBL-06`, `RT-FAIL-03` |  | needs final semantic decision |
| SM-NBL-008 | arithmetic on `nullable<T>` | no by default | n/a | compile-time error | Candidate | `RT-FAIL-01` |  | confirm no arithmetic surface |
| SM-NBL-009 | direct conditional truthiness for `nullable<T>` | Open | Open | Open | Open | `RT-NBL-*` |  | decide explicitly |

---

# 11. Ownership / Managed Wrapper Semantics

## 11.1 Factory Surface

| Matrix ID | Form | Allowed | Result | Enforcement | Status | Requirement(s) | Decision | Notes |
|---|---|---|---|---|---|---|---|---|
| SM-MEM-001 | `create<T>(args...)` | yes | current default managed ownership helper | compile-time available | Current | `RT-MEM-01`, `RT-MEM-02` |  | currently `shared_p<T>` |
| SM-MEM-002 | `shared<T>(args...)` | yes | `shared_p<T>` | compile-time available | Current | `RT-MEM-03` |  |  |
| SM-MEM-003 | `unique<T>(args...)` | yes | `unique_p<T>` | compile-time available | Current | `RT-MEM-04` |  |  |
| SM-MEM-004 | `weak(x)` from owning compatible value | yes | `weak_p<T>` | compile-time available | Current | `RT-MEM-05`, `RT-WK-03`, `RT-CGEN-03` |  |  |
| SM-MEM-005 | `weak<T>(args...)` primary allocation helper | no | n/a | compile-time error | Current | `RT-WK-02`, `RT-MEM-06` |  |  |
| SM-MEM-006 | raw `new`, `std::make_shared`, `std::make_unique` in generated code surface | no | n/a | codegen rule / compile policy | Current | `RT-CGEN-04`, `RT-CGEN-06` |  |  |

## 11.2 Wrapper Interaction

| Matrix ID | Rule | Allowed | Enforcement | Status | Requirement(s) | Decision | Notes |
|---|---|---|---|---|---|---|---|
| SM-MEM-007 | `shared_p<T>` copy | yes | compile-time available | Current | `RT-SH-01`, `RT-SH-03` |  |  |
| SM-MEM-008 | `unique_p<T>` copy | no | compile-time error | Current | `RT-UQ-01` |  |  |
| SM-MEM-009 | `unique_p<T>` move | yes | compile-time available | Current | `RT-UQ-02` |  |  |
| SM-MEM-010 | `weak_p<T>` expired compare as null | yes | runtime/compare semantics | Current | `RT-WK-04`, `RT-WK-06`, `RT-NULL-08` |  |  |
| SM-MEM-011 | direct conversion between ownership wrappers | no by default | compile-time error | Candidate | `RT-FAIL-01` |  | confirm no bridge conversions |
| SM-MEM-012 | stack allocation of managed object-like values in generated code | no by default | codegen rule | Spec | `RT-CGEN-06`, `RT-CGEN-07` |  | confirm final policy |

---

# 12. Generated-Code Boundary Rules

These are semantic rules, but many are primarily enforced by code generation policy rather than runtime overloads.

| Matrix ID | Rule | Expected State | Enforcement Mode | Status | Requirement(s) | Decision | Notes |
|---|---|---|---|---|---|---|---|
| SM-CGEN-001 | generated code uses only `scpp::` language-visible types/helpers | required | codegen + audit | Current | `RT-CGEN-01`, `RT-CGEN-06` |  |  |
| SM-CGEN-002 | generated code does not expose native types / `std::*` directly | required | codegen + audit | Current | `RT-CGEN-05`, `RT-CGEN-06` |  |  |
| SM-CGEN-003 | generated code does not call native allocation primitives directly | required | codegen + audit | Current | `RT-CGEN-04`, `RT-CGEN-06` |  |  |
| SM-CGEN-004 | native interop belongs in C++ bridge code, not generated Simple C++ surface | required | architecture + audit | Current | `RT-CGEN-07` |  |  |
| SM-CGEN-005 | generated code may use `create<T>()`, `shared<T>()`, `unique<T>()`, `weak(x)` only as defined | required | codegen + runtime surface | Current | `RT-CGEN-02`, `RT-CGEN-03` |  |  |
| SM-CGEN-006 | generated code must not rely on overloads or template surface features | required | codegen + audit | Current | `RT-CGEN-06`, `RT-CGEN-07` |  | runtime may still use templates internally |

---

# 13. Runtime Failure Policy

This area is not fully closed yet and should likely be finalized explicitly.

| Matrix ID | Failure Scenario | Current Behavior | Candidate Long-Term Rule | Status | Requirement(s) | Decision | Notes |
|---|---|---|---|---|---|---|---|
| SM-FAIL-001 | invalid explicit `string_t -> int_t` | runtime throw | choose final runtime error model | Open | `RT-STR-08`, `RT-FAIL-02` |  |  |
| SM-FAIL-002 | invalid explicit `string_t -> float_t` | runtime throw | choose final runtime error model | Open | `RT-STR-08`, `RT-FAIL-02` |  |  |
| SM-FAIL-003 | invalid explicit `string_t -> bool_t` | runtime throw | choose final runtime error model | Open | `RT-STR-08`, `RT-FAIL-02` |  |  |
| SM-FAIL-004 | invalid nullable relational compare involving null | runtime throw currently | decide: throw vs compile-time ban vs defined ordering | Open | `RT-NBL-06`, `RT-FAIL-03` |  |  |
| SM-FAIL-005 | forbidden unsupported operation outside runtime failure set | compile-time error | keep compile-time by default | Current | `RT-FAIL-01` |  |  |

---

# 14. Highest-Value Open Decisions

These are the most important semantic decisions still not fully normalized.

| Decision ID | Topic | Current State | Why It Matters | Recommended Next Action | Decision | Notes |
|---|---|---|---|---|---|---|
| SD-001 | `null_t` conditional semantics | still open | affects language truthiness model | confirm exact rule or ban |  |  |
| SD-002 | `nullable<T>` relational comparison with null side(s) | current runtime throw | affects semantics and tests | choose final rule explicitly |  |  |
| SD-003 | `nullable<T>` truthiness | undefined | affects conditional matrix | decide explicit yes/no |  |  |
| SD-004 | string-to-bool accepted set | partially defined by implementation | affects source compatibility | confirm exact accepted literals and case/whitespace policy |  |  |
| SD-005 | compound assignment full matrix | only partly normalized | affects wrapper completeness | confirm allowed set explicitly |  |  |
| SD-006 | vector append syntax | runtime uses `append(...)`, spec hints `[] = value` append form | affects language/runtime mapping | decide canonical surface |  |  |
| SD-007 | explicit conversion syntax for numeric conversions | behavior exists conceptually | affects frontend and tests | choose exact API form |  |  |
| SD-008 | wrapper bridge conversions | mostly absent by omission | affects ownership rigor | confirm universal ban unless explicitly added later |  |  |
| SD-009 | final runtime error model | still open | affects failures, tests, docs | define project-wide error policy |  |  |

---

## Review Instruction

When reviewing this file, the fastest effective workflow is:

1. confirm or change the rows in:
	- section 2 (`null`)
	- section 5 (`conditionals`)
	- section 7 (`explicit conversions`)
	- section 10 (`nullable`)
	- section 11 (`ownership`)
	- section 13 (`runtime failure policy`)
2. then confirm whether:
	- every **Candidate** row should become **Current**
	- every **Open** row should be decided now or deferred
3. after that, propagate the decisions into:
	- `RUNTIME_REQUIREMENTS.md`
	- source comments
	- tests
	- `TEST_COVERAGE.md`
