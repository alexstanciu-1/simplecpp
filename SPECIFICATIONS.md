# SPECIFICATIONS.md (Rewritten --- Normative Core)

## 1. Purpose

This document defines the **normative semantics** of the *Simple C++*
language and toolchain.

It specifies: - type system behavior - operator semantics - conversion
rules - enforcement model

All other documents must conform to this specification.

------------------------------------------------------------------------

## 2. Source of Truth Hierarchy

In case of conflict, the following precedence applies:

1.  **Executable tests (fail/pass)**
2.  **SEMANTIC_MATRIX.md**
3.  **This document (SPECIFICATIONS.md)**
4.  **RUNTIME_REQUIREMENTS.md (constraints and rationale)**

Implication: - Tests define the final observable behavior. - The
semantic matrix defines allowed/forbidden operations. - This document
defines system-wide rules and invariants.

------------------------------------------------------------------------

## 3. Terminology (Normative)

The following terms are used with strict meaning:

-   **Allowed**\
    A construct is valid and must compile.

-   **Forbidden**\
    A construct must not compile (either rejected or fail compilation).

-   **Rejected (S2S)**\
    Must be rejected during source-to-source transformation.

-   **Compile-time failure (C++)**\
    May pass S2S but must fail during C++ compilation.

-   **Explicit conversion**\
    Requires an explicit function or cast (never implicit).

-   **Implicit conversion**\
    Automatic conversion. Always forbidden unless explicitly stated
    (none currently allowed).

-   **Unsupported**\
    Not implemented yet; behavior is undefined.

------------------------------------------------------------------------

## 4. Core Language Invariants

### 4.1 No implicit conversions

There are **no implicit conversions** between types.

### 4.2 No truthiness

There is **no truthiness model**.

### 4.3 Strict operator typing

Operators are only defined for explicitly allowed type combinations.

### 4.4 No fallback coercion

There is no automatic fallback such as numeric, string, or boolean
coercion.

### 4.5 Semantic completeness via matrix

All valid operations must be defined in `SEMANTIC_MATRIX.md`.

------------------------------------------------------------------------

## 5. Type System Overview

Primitive types: - int_t - float_t - bool_t - string_t - null_t

Composite types: - nullable`<T>`{=html} - shared_p`<T>`{=html} -
unique_p`<T>`{=html} - weak_p`<T>`{=html}

------------------------------------------------------------------------

## 6. Conversion Model

### 6.1 General rule

Conversions are never implicit.

### 6.2 Explicit conversions

-   float_t → int_t via to_int(float_t), truncation toward zero
-   bool_t → int_t (true → 1, false → 0)

### 6.3 Forbidden conversions

-   null_t → non-nullable
-   string_t → numeric or bool
-   pointer wrappers → numeric or other wrappers

------------------------------------------------------------------------

## 7. Operator Semantics

### 7.1 Arithmetic

Allowed only if explicitly defined.

### 7.2 Comparison

All comparison operators return native C++ bool.

### 7.3 Assignment

Compound assignments allowed only if defined in matrix.

### 7.4 Pointer-like types

Allowed: - equality within same wrapper

Forbidden: - arithmetic - relational comparisons - cross-wrapper
comparisons

------------------------------------------------------------------------

## 8. Conditional Semantics

Conditions must resolve explicitly to bool_t.

------------------------------------------------------------------------

## 9. Enforcement Model

### 9.1 S2S enforcement

Reject invalid constructs early.

### 9.2 C++ enforcement

Fail compilation when necessary.

### 9.3 Rule classification

Each rule must be classified as S2S or C++ enforced.

------------------------------------------------------------------------

## 10. Runtime Constraints

Runtime must: - prevent implicit conversions - use explicit
constructors - delete forbidden operators

------------------------------------------------------------------------

## 11. Relationship to PHP

Simple C++ is a restricted transformation target, not PHP runtime.

------------------------------------------------------------------------

## 12. Completeness Requirement

Every rule must exist in: - semantic matrix - tests - implementation or
rejection

------------------------------------------------------------------------

## 13. Non-goals

The system does not support: - PHP semantics - dynamic typing - implicit
coercion
