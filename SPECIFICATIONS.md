# SPECIFICATIONS.md

## 1. Purpose

This document defines the normative semantics of the *Simple C++* language and toolchain.

It specifies:
- type system behavior
- operator semantics
- conversion rules
- conditional semantics
- enforcement model

All other documents must conform to this specification.

---

## 2. Source of Truth Hierarchy

In case of conflict, the following precedence applies:

1. **Executable tests (pass/fail)**
2. **SEMANTIC_MATRIX.md**
3. **This document (`SPECIFICATIONS.md`)**
4. **RUNTIME_REQUIREMENTS.md** for implementation constraints and rationale

Implications:
- tests define final observable behavior
- the semantic matrix defines allowed and forbidden operation families
- this document defines system-wide invariants and interpretation rules

---

## 3. Terminology

- **Allowed**: valid and must compile
- **Forbidden**: must not compile, whether rejected in S2S or by generated C++ compilation
- **Rejected (S2S)**: must be rejected during source-to-source transformation
- **Compile-time failure (C++)**: may pass S2S but must fail during C++ compilation
- **Explicit conversion**: requires an explicit helper, cast form, or explicitly named bridge
- **Implicit conversion**: automatic conversion; forbidden unless explicitly stated
- **Semantic boolean**: an expression that is valid as a control-flow condition under the Simple C++ rules
- **Native control-flow bool**: a native C++ `bool` used only as the lowered representation of a semantic-boolean result

---

## 4. Core Language Invariants

### 4.1 No implicit conversions
There are no implicit conversions between semantic value families unless explicitly listed.

### 4.2 No truthiness
There is no truthiness model. Non-boolean values do not become conditions merely because a host language could interpret them that way.

### 4.3 Strict operator typing
Operators are defined only for explicitly allowed type combinations.

### 4.4 No fallback coercion
There is no automatic numeric, string, pointer, or boolean fallback coercion.

### 4.5 Matrix completeness
All valid operation families must be represented in `SEMANTIC_MATRIX.md`. If an operation family is not listed there, it is forbidden.

---

## 5. Type System Overview

Primitive semantic wrappers:
- `int_t`
- `float_t`
- `bool_t`
- `string_t`
- `null_t`

Composite / wrapper families:
- `nullable<T>`
- `shared_p<T>`
- `unique_p<T>`
- `weak_p<T>`

---

## 6. Conversion Model

### 6.1 General rule
Conversions are never implicit unless the matrix explicitly says otherwise.

### 6.2 Explicit conversions currently defined
- `float_t -> int_t` via `to_int(float_t)`, truncating toward zero
- `bool_t -> int_t` with `true -> 1` and `false -> 0`

### 6.3 Forbidden conversion families
The following families are forbidden unless a narrower explicit rule is added elsewhere:
- `null_t ->` non-nullable primitive or string
- `string_t ->` numeric or `bool_t`
- pointer-like wrappers -> numeric or string
- cross-wrapper pointer family conversions

---

## 7. Operator Semantics

### 7.1 Arithmetic
Arithmetic is allowed only for explicitly documented numeric combinations.

### 7.2 Comparison
All comparison operators return native C++ `bool`.

This is a representation choice for lowered/generated code, not a separate source-language value family.

### 7.3 Assignment
Assignment and compound assignment are allowed only where explicitly documented by the matrix and requirements.

### 7.4 Pointer-like families
Allowed:
- same-wrapper equality where documented

Forbidden by default:
- arithmetic
- relational ordering
- cross-wrapper comparison unless explicitly documented elsewhere

---

## 8. Conditional Semantics

### 8.1 Semantic rule
Conditions accept **semantic-boolean expressions only**.

### 8.2 What counts as semantic-boolean
A condition is valid only if it is one of the following:
- a `bool_t` value
- the result of a comparison expression
- the result of a logical expression over semantic-boolean operands
- an explicitly documented runtime-only contextual-bool bridge used by generated/runtime code for specific wrapper families

### 8.3 Native `bool` bridge
Comparison and logical expressions may be represented as native C++ `bool` in lowered/generated code.

That native `bool` result is valid directly in generated control flow, but it does **not** create a general implicit conversion rule from arbitrary semantic values to `bool_t` or to native `bool`.

### 8.4 Forbidden direct conditions
The following are forbidden as direct conditions unless wrapped by an explicitly documented boolean-producing operation:
- `int_t`
- `float_t`
- `string_t`
- `null_t`
- pointer-like wrappers by source-language truthiness
- `nullable<T>` by source-language truthiness

### 8.5 Runtime-only contextual bool
The runtime may expose tightly scoped `explicit operator bool()` or equivalent contextual-bool APIs for selected wrapper families as a code-generation / hardening mechanism.

Such APIs:
- are not general implicit conversions
- do not imply source-language truthiness
- do not permit implicit assignment into `bool_t` or native `bool`

---

## 9. Enforcement Model

### 9.1 Source-to-source enforcement
Reject invalid constructs early where practical.

### 9.2 Generated C++ enforcement
Generated C++ may enforce forbidden combinations via deleted functions, missing overloads, constrained templates, or other compile-time mechanisms.

### 9.3 Rule classification
Every major rule should be classifiable as primarily:
- S2S-enforced
- C++-enforced
- or jointly enforced

---

## 10. Runtime Constraints

The runtime must:
- prevent unintended implicit conversions
- prefer explicit constructors and named helpers
- delete or constrain forbidden operations where practical
- preserve the distinction between semantic booleans and host-language conveniences

---

## 11. Relationship to PHP

Simple C++ is a restricted transformation target, not a PHP runtime.

Key differences from PHP include:
- no loose truthiness
- no broad implicit coercion
- strict operator families
- explicit conversion boundaries

---

## 12. Completeness Requirement

Every semantic rule should be reflected in:
- this specification or the semantic matrix
- at least one implementation-level requirement when runtime behavior matters
- tests whenever the rule is externally observable

---

## 13. Non-goals

The system does not aim to:
- preserve PHP truthiness
- preserve dynamic typing
- allow broad host-language implicit conversions
- expose native C++ freedom directly to generated language code
