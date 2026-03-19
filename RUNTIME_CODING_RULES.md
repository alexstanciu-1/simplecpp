# RUNTIME_CODING_RULES.md

## Scope

This document defines **runtime implementation rules and coding constraints** for the Simple C++ system.

It does NOT define language semantics.

All language behavior MUST be defined exclusively in:
- SPECIFICATIONS.md
- SEMANTIC_MATRIX.md

If any rule in this document appears to define behavior, it is invalid and must be relocated.

The canonical public runtime interface is defined in:
- RUNTIME_API_CONTRACT.md

This document defines implementation rules for conforming to that API contract.

---

## Purpose

The purpose of this document is to:
- define safe implementation patterns
- enforce compile-time guarantees
- prevent accidental semantic leakage from C++ into the language
- ensure strict adherence to the semantic matrix
- ensure strict adherence to the public API contract

---

## Core Principles

### 1. Matrix-driven and contract-driven implementation

All runtime behavior must be derived from:
- SEMANTIC_MATRIX.md
- RUNTIME_API_CONTRACT.md

Rules:
- no operation may be implemented unless explicitly allowed by the matrix
- all allowed public operations must have the exact public form required by the API contract
- missing operations must result in compile-time failure

### 2. No implicit C++ behavior

The runtime must not rely on implicit C++ behavior.

Forbidden:
- implicit conversions
- automatic type promotion
- fallback operator overloads
- template-based implicit resolution

All behavior must be explicitly defined.

### 3. Compile-time enforcement

Forbidden operations must fail at compile time.

Allowed enforcement techniques for internal implementation:
- deleted operators
- constrained templates
- missing overloads
- static assertions

Public behavior must never:
- silently coerce types
- fallback to permissive behavior

### 4. No semantic inference

The runtime must not infer behavior from:
- C++ defaults
- STL behavior
- compiler behavior

All behavior must be explicitly defined and traceable to the matrix and API contract.

---

## Operator implementation rules

### 1. Exact public mapping

Each public operator implementation must correspond to an entry in:
- SEMANTIC_MATRIX.md
- RUNTIME_API_CONTRACT.md

If a public operator is not present in the contract:
- it must not exist publicly

### 2. No partial coverage

Operators must not be partially implemented.

If both operand orders are allowed:
- both public operator forms must exist exactly as contracted

### 3. No overload fallback

Do not rely on:
- implicit conversions
- generic templates
- fallback overloads

Each allowed public combination must be explicitly defined.

---

## Boolean handling rules

### 1. Contextual boolean usage

The runtime may expose native-`bool` results only through the public operator surface contracted in `RUNTIME_API_CONTRACT.md`.

Constraints:
- no public `operator bool()`
- no public truthiness helpers
- no general conversion-to-bool API

### 2. Forbidden conversions

The runtime must NOT allow:
- implicit conversion to bool
- implicit conversion to bool_t
- assignment from arbitrary types to bool

---

## Pointer and ownership rules

### 1. Ownership semantics

Pointer wrappers:
- shared_p<T>
- unique_p<T>
- weak_p<T>

Ownership behavior (copy/move) is an implementation detail.

It must NOT affect:
- allowed operations
- comparison semantics
- assignment validity
- the required public API surface

### 2. Comparison safety

Pointer comparison must:
- only be implemented where allowed by the matrix and API contract
- never allow relational operators
- never allow cross-wrapper comparison

---

## Conversion rules

### 1. Explicit only

All semantic conversions must be:
- explicit
- intentional
- exposed only through the public constructor surface required by `RUNTIME_API_CONTRACT.md`

No implicit conversion mechanisms may exist.

### 2. No public helper conversions

Public helper functions for semantic conversions are forbidden.

In particular, the runtime must not expose public APIs such as:
- `to_int(...)`
- `to_float(...)`

Internal implementation helpers MAY exist only in `scpp_intern` and MUST NOT form part of the public semantic conversion surface.

---

## Error handling philosophy

### 1. Fail fast

Invalid constructs must:
- fail at compile time whenever possible

### 2. No silent recovery

The runtime must never:
- correct invalid input
- guess user intent
- fallback to permissive behavior

---

## Consistency requirements

The following must always hold:

- every implemented public operation exists in SEMANTIC_MATRIX.md
- every public API form matches RUNTIME_API_CONTRACT.md
- every forbidden public operation fails compilation
- no runtime behavior contradicts the specification

---

## Anti-patterns (forbidden)

- implementing behavior not present in the matrix
- exposing public conversion helpers
- relying on implicit C++ conversions
- using generic templates to bypass restrictions
- allowing fallback operator behavior
- introducing hidden truthiness
- inferring behavior from STL or compiler defaults

---

## Final rule

Runtime enforces semantics.

It does not define them.
