# SPECIFICATIONS.md

## 1. Purpose

Normative semantic core.

All operational detail is in SEMANTIC_MATRIX.md.

---

## 2. Authority Model

1. SPECIFICATIONS.md
2. SEMANTIC_MATRIX.md
3. Generated artifacts
4. Non-normative documents

---

## 3. Terminology

- Allowed: must compile
- Forbidden: must not compile
- Explicit conversion: requires explicit construct
- Implicit conversion: forbidden
- Semantic boolean:
  - bool_t
  - comparison result (native bool)
  - logical result (native bool)

These representations are not interchangeable outside conditional evaluation.
Native bool values are only valid as conditional evaluation results and do not participate in general assignment or conversion.

---

## 4. Core Invariants

- no implicit conversions
- no truthiness
- strict operator typing
- no fallback coercion

Matrix completeness:
If not in SEMANTIC_MATRIX.md → forbidden.

---

## 5. Type System

- int_t, float_t, bool_t, string_t, null_t
- nullable<T>, shared_p<T>, unique_p<T>, weak_p<T>

---

## 6. Conversion Model

Only conversions defined in SEMANTIC_MATRIX.md are allowed.

---

## 7. Operator Model

Defined exclusively in SEMANTIC_MATRIX.md.

---

## 8. Conditional Semantics

Only semantic booleans allowed.

---

## 9. Enforcement Model

In the current architecture:

- Semantic correctness is enforced primarily by generated C++ compilation and the runtime/type system
- The S2S generator is not required to perform semantic validation
- Earlier validation may be introduced in future tooling but is not required

When operand or result types are unknown during S2S lowering:

- the generator may emit the lowered C++ expression without resolving semantic types locally
- the generator may use `auto` for unknown expression results
- the generator is not required to resolve external symbol declarations during S2S
- missing declarations, incompatible declarations, invalid overload resolution, and forbidden operations are intended to fail at the C++ compilation stage

Emission of an unresolved lowered expression does not imply semantic acceptance.

---

## Final Statement

Defines meaning.

SEMANTIC_MATRIX.md defines behavior.


## TYPE_FAMILY_REGISTRY (Normative)

This section defines the canonical registry of all type families. All other documents MUST reference this registry and MUST NOT redefine type inventories independently.

Each family declares:

- family_name
- kind
- template_arity
- same_family_equality
- assignment_model
- truthiness
- conversion_participation
- operator_participation

### Current Families

#### int_t
- kind: primitive
- template_arity: 0
- same_family_equality: allowed
- assignment_model: direct
- truthiness: not allowed
- conversion_participation: explicit numeric conversions
- operator_participation: arithmetic, equality, relational

#### float_t
- kind: primitive
- template_arity: 0
- same_family_equality: allowed
- assignment_model: direct
- truthiness: not allowed
- conversion_participation: explicit numeric conversions
- operator_participation: arithmetic, equality, relational

#### bool_t
- kind: primitive
- template_arity: 0
- same_family_equality: allowed
- assignment_model: direct
- truthiness: allowed
- conversion_participation: restricted
- operator_participation: logical, equality

#### string_t
- kind: primitive
- template_arity: 0
- same_family_equality: allowed
- assignment_model: direct
- truthiness: not allowed
- conversion_participation: restricted
- operator_participation: equality

#### nullable<T>
- kind: nullable-like
- template_arity: 1
- same_family_equality: allowed
- assignment_model: nullable-aware
- truthiness: not allowed
- conversion_participation: explicit only
- operator_participation: equality

#### shared_p<T>, unique_p<T>, weak_p<T>
- kind: pointer-like
- template_arity: 1
- same_family_equality: allowed (same T)
- assignment_model: pointer semantics
- truthiness: not allowed
- conversion_participation: restricted
- operator_participation: equality

NOTE:
Future families (e.g. map_t<K,V>) MUST be added here first before any other document is updated.
