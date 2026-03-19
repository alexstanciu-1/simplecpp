# DESIGN_NOTES.md

## Scope

This document is **non-normative**.

It provides design commentary and rationale for the Simple C++ documentation and architecture.

It does NOT define language semantics.

Semantic authority exists only in:
- SPECIFICATIONS.md
- TYPE_FAMILY_REGISTRY.md
- DERIVATION_RULES.md
- SEMANTIC_MATRIX.md

---

## Documentation Architecture

The documentation stack is intentionally layered.

### Specification layer
- SPECIFICATIONS.md
- TYPE_FAMILY_REGISTRY.md
- DERIVATION_RULES.md
- SEMANTIC_MATRIX.md

### Runtime layer
- RUNTIME_API_CONTRACT.md
- RUNTIME_REQUIREMENTS.md
- RUNTIME_CODING_RULES.md
- RUNTIME_DESIGN_NOTE.md
- RUNTIME_INDEX.md

### Validation layer
- TEST_MATRIX.md
- TEST_MATERIALIZATION_CONTRACT.md
- TEST_COVERAGE.md

---

## Why the specification layer is split

The specification layer uses a two-layer semantic model.

### Compact source model
- TYPE_FAMILY_REGISTRY.md
- DERIVATION_RULES.md

Purpose:
- centralize family identity
- classify traits and compatibility classes
- support language growth without uncontrolled duplication

### Normalized operational model
- SEMANTIC_MATRIX.md

Purpose:
- give explicit generator-facing outcomes
- preserve auditability
- preserve deterministic runtime and test generation

This split exists to improve maintainability without sacrificing explicitness.

---

## Why subordinate docs must not redefine type inventories

Type inventory drift is a major consistency risk.

If runtime or test documents restate type inventories independently, future additions can produce hidden contradictions.

That is why TYPE_FAMILY_REGISTRY.md is the canonical inventory source.

Subordinate documents may reference currently relevant families only where operationally necessary.

---

## Why traits are constrained

Traits are useful for organization, but trait-only semantics would be too ambiguous for generator-safe code production.

Therefore:
- traits classify participation
- derivation rules resolve legality logic
- the matrix remains the explicit operational result

This preserves deterministic generation.

---

## Final

This document is commentary only.
