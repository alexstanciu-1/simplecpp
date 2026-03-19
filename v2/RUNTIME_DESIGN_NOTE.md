# RUNTIME_DESIGN_NOTE.md

## Scope

This document is **non-normative**.

It explains design rationale for the Simple C++ runtime.

It does NOT define language semantics.

All semantic authority exists only in:
- SPECIFICATIONS.md
- TYPE_FAMILY_REGISTRY.md
- DERIVATION_RULES.md
- SEMANTIC_MATRIX.md

---

## Design Goals

The runtime is designed to:

- provide a stable semantic wrapper layer for generated C++
- encode language restrictions into C++ type/operator structure
- keep the public API narrow
- fail forbidden operations during C++ compilation
- remain extensible as the language grows

---

## Relationship to the Specification Layer

The runtime does not define what is allowed.

The specification layer does that in two semantic sublayers:

### Layer 1 — compact source model
- TYPE_FAMILY_REGISTRY.md
- DERIVATION_RULES.md

### Layer 2 — normalized operational model
- SEMANTIC_MATRIX.md

The runtime is implemented against Layer 2 for explicit operational fidelity, while the compact source model exists to make future extension manageable.

---

## Why wrapper families exist

Wrapper families exist so the language can enforce:
- no implicit conversion
- no general truthiness
- strict operator participation
- family-specific assignment and equality rules
- explicit distinction between semantic values and C++ builtin behavior

Without wrappers, too much behavior would be inherited accidentally from C++.

---

## Why the hybrid spec model helps runtime evolution

A trait registry alone would be too abstract for safe generation.

A matrix alone is explicit but scales poorly as the language expands.

The hybrid model gives:
- compact classification and extension points in TYPE_FAMILY_REGISTRY.md
- deterministic legality rules in DERIVATION_RULES.md
- explicit generator-facing outcomes in SEMANTIC_MATRIX.md

This is the right balance for runtime generation without human intervention.

---

## Why the matrix remains mandatory

The runtime needs explicit answers, not design intent.

Examples:
- whether a pair is allowed
- what its exact result type is
- whether `same-T` is required
- whether `null_t` is a special exception
- whether an operation is forbidden even though families share traits

That is why the matrix remains mandatory even after introducing a trait registry.

---

## Future extensibility

If future families such as `map_t<K, V>` are introduced, the intended update path is:

1. add the family to TYPE_FAMILY_REGISTRY.md
2. define derivation logic or family-specific overrides in DERIVATION_RULES.md
3. normalize the resulting operational outcomes in SEMANTIC_MATRIX.md
4. update runtime API obligations in RUNTIME_API_CONTRACT.md
5. update deterministic validation artifacts

This keeps runtime evolution procedural and auditable.

---

## Final

This document is explanatory only.
