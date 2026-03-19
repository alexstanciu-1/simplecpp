# RUNTIME_REQUIREMENTS.md

## Scope

This document is **normative**.

It defines implementation requirements for the Simple C++ runtime.

It does NOT define language semantics.

All semantic authority exists only in the specification layer:
- SPECIFICATIONS.md
- TYPE_FAMILY_REGISTRY.md
- DERIVATION_RULES.md
- SEMANTIC_MATRIX.md

This document defines what the runtime implementation MUST do in order to implement that specification layer faithfully.

---

## Purpose

The runtime implementation requirements exist to ensure:

- semantic enforcement remains strict
- generated C++ compilation is a valid enforcement point
- the public runtime surface remains stable and narrow
- forbidden behavior is not accidentally reintroduced through C++ defaults
- implementation choices do not broaden the language

---

## Authority Relationship

1. SPECIFICATIONS.md — semantic core and invariants
2. TYPE_FAMILY_REGISTRY.md — canonical family inventory and trait model
3. DERIVATION_RULES.md — deterministic semantic derivation logic
4. SEMANTIC_MATRIX.md — normalized operational outcomes
5. RUNTIME_API_CONTRACT.md — canonical public runtime surface
6. RUNTIME_REQUIREMENTS.md — implementation requirements
7. Runtime implementation

If any conflict exists:
- the specification layer governs semantic meaning
- RUNTIME_API_CONTRACT.md governs public API shape
- this document governs implementation obligations only

---

## Core Runtime Requirements

### 1. Specification fidelity

The runtime MUST implement only behavior allowed by the specification layer.

The runtime MUST NOT:
- add convenience behavior not authorized by the matrix
- add implicit conversions
- add truthiness
- expose alternate public APIs that bypass semantic restrictions

### 2. Matrix-faithful enforcement

The runtime MUST enforce the normalized operational outcomes defined in SEMANTIC_MATRIX.md.

This means:
- every allowed operation required by the matrix must be supported
- every forbidden operation category must fail at generated C++ compilation
- compile-time enforcement is preferred wherever practical

The runtime is operationally implemented against the matrix, but that matrix is itself subordinate to:
- SPECIFICATIONS.md
- TYPE_FAMILY_REGISTRY.md
- DERIVATION_RULES.md

### 3. No semantic broadening through C++

The runtime MUST prevent accidental semantic broadening caused by:
- builtin arithmetic fallback
- accidental implicit constructors
- accidental conversion operators
- default pointer truthiness
- default wrapped-type operator inheritance
- permissive template deduction paths

### 4. Public/internal separation

The runtime MUST expose only the public surface required by RUNTIME_API_CONTRACT.md.

Everything else SHOULD be internal.

Generated code MUST NOT depend on internal helpers, traits, or implementation-specific namespaces.

### 5. Explicit conversion control

Explicit conversions MUST remain exactly aligned with the specification layer.

Trait membership or internal implementation convenience MUST NOT create additional public explicit-conversion surface.

### 6. Family isolation

Nullable-like and pointer-like families MUST NOT inherit additional behavior from their wrapped implementation types unless explicitly authorized by the specification layer.

In particular:
- `nullable<T>` does not inherit arithmetic, relational, logical, or conditional behavior from `T`
- pointer-like families do not inherit arithmetic, relational, logical, or conditional behavior from underlying C++ representation

### 7. Conditional-bridge preservation

The runtime MUST preserve the conditional bridge exactly as specified:

- `bool_t` is conditionally valid
- native `bool` produced by allowed comparison and logical operations is valid for control flow
- native `bool` is not thereby promoted to a general semantic family
- no non-boolean semantic family gains truthiness

### 8. Deterministic failure model

Forbidden operations must fail deterministically during generated C++ compilation.

Failure MUST NOT depend on:
- undefined runtime behavior
- execution-time checks for type legality
- implementation accidents
- unspecified overload ambiguity

The intended failure mode is compile-time rejection through the exposed runtime type/operator surface.

---

## Implementation Freedom

The runtime implementation MAY use:
- helper templates
- concepts or SFINAE-style restriction
- deleted overloads
- internal traits
- `static_assert`
- wrapper internals
- internal utility layers

But such choices:
- MUST remain internal
- MUST NOT become public contract
- MUST NOT alter semantic meaning
- MUST NOT replace the specification layer as authority

---

## Validation Relationship

The runtime implementation MUST be testable against:
- TEST_MATRIX.md
- TEST_MATERIALIZATION_CONTRACT.md
- TEST_COVERAGE.md

Tests validate runtime conformance to the specification layer.
Tests do not define language semantics.

---

## Final Statement

This document defines runtime implementation obligations.

It is subordinate to the full specification layer and must remain aligned with it.
