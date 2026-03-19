# SPECIFICATIONS.md

## 1. Purpose

Normative semantic core.

This document defines:
- the authority model
- global semantic invariants
- the enforcement model
- the closed-world rule
- the two-layer semantic architecture

Detailed family inventory is defined in:
- TYPE_FAMILY_REGISTRY.md

Deterministic semantic derivation is defined in:
- DERIVATION_RULES.md

Explicit operational outcomes are defined in:
- SEMANTIC_MATRIX.md

---

## 2. Authority Model

1. SPECIFICATIONS.md
2. TYPE_FAMILY_REGISTRY.md
3. DERIVATION_RULES.md
4. SEMANTIC_MATRIX.md
5. Generated artifacts
6. Non-normative documents

Lower documents MUST NOT redefine or override higher semantic meaning.

---

## 3. Terminology

- Allowed: must compile
- Forbidden: must not compile
- Explicit conversion: requires explicit construct
- Implicit conversion: forbidden
- Semantic boolean:
  - `bool_t`
  - comparison result (native `bool`)
  - logical result (native `bool`)

These representations are not interchangeable outside conditional evaluation.

Native `bool` values are only valid as conditional evaluation results and do not participate in general assignment or conversion.

---

## 4. Core Invariants

- no implicit conversions
- no truthiness
- strict operator typing
- no fallback coercion
- closed-world semantics
- deterministic derivation
- explicit operational normalization

Matrix completeness rule:

If an operation is not authorized by the specification layer and explicitly resolved in SEMANTIC_MATRIX.md, it is forbidden.

---

## 5. Two-Layer Semantic Model

The specification layer uses two semantic sublayers.

### 5.1 Layer 1 — Compact source model

Defined by:
- TYPE_FAMILY_REGISTRY.md
- DERIVATION_RULES.md

This layer exists to:
- centralize the family inventory
- classify family traits
- define deterministic rule derivation
- support future extensibility without uncontrolled duplication

### 5.2 Layer 2 — Normalized operational model

Defined by:
- SEMANTIC_MATRIX.md

This layer exists to:
- provide explicit allowed/forbidden outcomes
- serve as the generator-facing operational definition
- remain exhaustive for the currently registered family set
- preserve implementation and test auditability

The compact source model does not replace the normalized matrix.
The normalized matrix remains mandatory.

---

## 6. Closed-World Type Rule

The complete currently supported type-family inventory is defined exclusively in TYPE_FAMILY_REGISTRY.md.

No other document may redefine the inventory independently.

If a family is not present in TYPE_FAMILY_REGISTRY.md, it does not exist in the language.

---

## 7. Conversion Model

Only conversions explicitly authorized by the specification layer are allowed.

No implicit conversion exists.

Trait membership does not itself authorize conversion.
Explicit conversion surface must remain fully resolved in SEMANTIC_MATRIX.md and implemented through the runtime contract.

---

## 8. Operator Model

Operators are governed by deterministic derivation rules and resolved in the normalized operational matrix.

There are no implicit allowances based on:
- same spelling as C++
- same spelling as PHP
- same-family intuition
- trait membership alone
- unstated symmetry

---

## 9. Conditional Semantics

Only semantic booleans are conditionally valid.

Conditional validity is closed and explicit.

There is no general truthiness for semantic values.

---

## 10. Enforcement Model

In the current architecture:

- semantic correctness is enforced primarily by generated C++ compilation and the runtime/type system
- the S2S generator is not required to perform semantic validation
- earlier validation may be introduced in future tooling but is not required

When operand or result types are unknown during S2S lowering:

- the generator may emit the lowered C++ expression without resolving semantic types locally
- the generator may use `auto` for unknown expression results
- the generator is not required to resolve external symbol declarations during S2S
- missing declarations, incompatible declarations, invalid overload resolution, and forbidden operations are intended to fail at the C++ compilation stage

Emission of an unresolved lowered expression does not imply semantic acceptance.

---

## Final Statement

This document defines semantic intent, invariants, and authority.

TYPE_FAMILY_REGISTRY.md defines the family inventory and trait model.

DERIVATION_RULES.md defines deterministic semantic derivation.

SEMANTIC_MATRIX.md defines explicit operational behavior.
