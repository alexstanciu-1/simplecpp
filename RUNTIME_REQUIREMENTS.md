# RUNTIME_REQUIREMENTS.md

## Scope

This document defines **runtime enforcement requirements** for the Simple C++ system.

It is **non-normative** with respect to language semantics.

It does NOT define language behavior.

All semantic rules MUST be defined exclusively in:
- SPECIFICATIONS.md
- SEMANTIC_MATRIX.md

This document specifies how those rules are **enforced** at the runtime / generated C++ level.

The canonical public runtime interface is defined in:
- RUNTIME_API_CONTRACT.md

---

## Purpose

The purpose of this document is to:

- define enforcement guarantees required from the runtime
- ensure strict adherence to SEMANTIC_MATRIX.md
- ensure strict adherence to RUNTIME_API_CONTRACT.md
- prevent accidental semantic leakage from C++ behavior
- enforce compile-time correctness wherever possible

---

## Core enforcement principles

### 1. Matrix-driven and contract-driven enforcement

All runtime behavior MUST be derived from:
- SEMANTIC_MATRIX.md
- RUNTIME_API_CONTRACT.md

Requirements:
- every implemented public operation must exist in the matrix
- every implemented public API form must match the API contract
- no public operation may exist outside the matrix
- absence in the matrix implies forbidden behavior

### 2. No semantic definition in runtime

The runtime MUST NOT:
- define new operations
- redefine existing operations
- refine or extend semantics

All runtime logic must be traceable to:
- SPECIFICATIONS.md
- SEMANTIC_MATRIX.md
- RUNTIME_API_CONTRACT.md

### 3. Compile-time failure guarantee

Forbidden operations MUST fail at compile time.

The runtime MUST NOT:
- fallback to permissive behavior
- silently coerce types
- attempt recovery from invalid constructs

Allowed internal enforcement techniques:
- deleted functions
- missing overloads
- constrained templates
- static assertions

### 4. No implicit C++ behavior

The runtime MUST NOT rely on implicit C++ mechanisms, including:
- implicit type conversions
- arithmetic promotion rules
- fallback operator resolution
- generic template deduction that broadens allowed types

All allowed public behavior must be explicitly defined.

---

## Operator enforcement requirements

### 1. Exact public coverage

For every public operator required by:
- SEMANTIC_MATRIX.md
- RUNTIME_API_CONTRACT.md

a corresponding explicit public implementation MUST exist.

For every operator not allowed:
- no public implementation must exist
- or the operator must be enforced as invalid through compile-time failure

### 2. Symmetry enforcement

If the matrix defines symmetric operations:
- both public directions MUST exist exactly as contracted

### 3. No partial implementation

Operators must not be partially implemented:
- no reliance on implicit conversion to cover missing cases
- no public template fallback to simulate missing overloads

---

## Conversion enforcement requirements

### 1. Explicit only

All public semantic conversions must be:
- explicit
- intentional
- directly traceable to the matrix
- exposed only through the contracted constructor surface

### 2. No implicit conversion paths

The runtime MUST NOT introduce:
- implicit constructors
- implicit conversion operators
- public helper-based semantic conversion paths

### 3. Internal helper constraint

Internal helper functions MAY exist only:
- in `scpp_intern`
- as implementation detail
- without becoming public semantic conversion paths

The runtime MUST NOT expose public helper functions that duplicate or broaden the contracted semantic conversion surface.

---

## Boolean enforcement requirements

### 1. No truthiness

The runtime MUST NOT allow:
- implicit conversion of non-boolean values to boolean
- conditional evaluation of arbitrary types
- public `operator bool()`

### 2. Contextual boolean

Public native-`bool` results MAY exist only through the contracted logical and comparison operator surface.

The runtime MUST NOT expose:
- a general public boolean bridge helper
- implicit assignment to bool_t or native bool from arbitrary semantic values

---

## Pointer and wrapper enforcement

### 1. Wrapper isolation

Pointer-like wrappers:
- shared_p<T>
- unique_p<T>
- weak_p<T>
- nullable<T>

must enforce strict type boundaries.

### 2. Operation restrictions

The runtime MUST:
- implement only operations allowed in the matrix
- expose only public forms allowed in the API contract
- reject all others at compile time

### 3. Ownership behavior

Ownership semantics (copy/move):
- are implementation details
- must not alter allowed or forbidden operations

---

## Error handling requirements

### 1. Fail fast

Invalid constructs must:
- fail as early as possible
- preferably at compile time

### 2. No silent recovery

The runtime MUST NOT:
- guess user intent
- coerce values into valid states
- provide fallback implementations

---

## Test alignment

All enforced behavior MUST:
- be covered by tests defined in TEST_MATRIX.md
- be materialized according to TEST_MATERIALIZATION_CONTRACT.md
- correspond to explicit matrix rules and contracted API forms

If a behavior cannot be tested deterministically, it must be reconsidered.

---

## Consistency guarantees

The runtime must ensure:
- every implemented public operation exists in SEMANTIC_MATRIX.md
- every implemented public API form matches RUNTIME_API_CONTRACT.md
- no extra public operations are introduced
- forbidden operations cannot compile
- no implicit behavior leaks into the system

---

## Evolution rules

When updating runtime:
1. verify rule exists in SEMANTIC_MATRIX.md
2. verify API form exists in RUNTIME_API_CONTRACT.md
3. implement enforcement explicitly
4. ensure forbidden cases fail
5. add or update tests

Runtime must never introduce public behavior before it exists in both the matrix and the API contract.

---

## Anti-patterns (forbidden)

- defining semantics in runtime code
- exposing public conversion helpers
- relying on implicit C++ conversions
- using generic templates as fallback mechanisms
- implementing operations not present in the matrix
- implementing public API forms not present in the API contract
- introducing hidden truthiness
- allowing partial operator coverage

---

## Final statement

The runtime is an **enforcement layer**.

It exists solely to implement the semantics defined in:
- SPECIFICATIONS.md
- SEMANTIC_MATRIX.md

through the canonical interface defined in:
- RUNTIME_API_CONTRACT.md

It must never become a source of truth for language behavior.
