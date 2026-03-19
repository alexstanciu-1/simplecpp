# RUNTIME_API_CONTRACT.md

## 1. Scope

This document is **normative**.

It defines the **canonical runtime API surface** for the Simple C++ system.

It specifies:
- the exact public include contract
- the exact namespace model
- the exact public types
- the exact constructor and conversion surface
- the exact public operator surface
- the exact public assignment surface
- the exact conditional-bridge rule
- the exact internal/public API boundary

It does NOT define language semantics.

All semantic behavior MUST be defined exclusively in the specification layer:
- SPECIFICATIONS.md
- TYPE_FAMILY_REGISTRY.md
- DERIVATION_RULES.md
- SEMANTIC_MATRIX.md

This document defines the **only valid public runtime interface** that implements those semantics.

---

## 2. Authority Relationship

1. SPECIFICATIONS.md — semantic rules and invariants
2. TYPE_FAMILY_REGISTRY.md — family inventory and trait model
3. DERIVATION_RULES.md — deterministic derivation logic
4. SEMANTIC_MATRIX.md — normalized operational rules
5. RUNTIME_API_CONTRACT.md — canonical runtime interface
6. Runtime implementation

If any conflict exists:
- specification-layer documents take precedence for semantic meaning
- this document takes precedence for runtime API shape

---

## 3. Type Inventory Rule

This document MUST NOT redefine the type inventory independently.

The canonical inventory source is:
- TYPE_FAMILY_REGISTRY.md

This document may name public runtime types only where required to define the runtime API surface implementing the current specification layer.

---

## 4. Public Include Contract

The runtime MUST expose exactly one public entry point:

```cpp
#include "scpp/runtime.hpp"
```

All public runtime types, constructors, assignments, and operators defined by this contract MUST be reachable through this include.

No other header path is part of the public API contract.

Internal headers MAY exist, but they are not generator-visible and are not part of the stable API surface.

---

## 5. Namespace Model

### 5.1 Public namespace

All public runtime symbols MUST be defined in:

```cpp
namespace scpp
```

Generated code MUST:
- reference only symbols in `scpp`
- never reference any other runtime namespace

### 5.2 Internal namespace

The runtime MUST reserve:

```cpp
namespace scpp_intern
```

Constraints:
- generated code MUST NOT reference `scpp_intern`
- `scpp_intern` is not part of the stable public API
- implementation-only helpers, traits, constants, diagnostics, and utility functions MUST be placed in `scpp_intern`

### 5.3 Public/internal boundary

The runtime MUST NOT require generated code to reference anything outside `scpp`.

The S2S generator MUST qualify runtime symbols through `scpp`.

---

## 6. Public Types

The runtime MUST expose the public types required by the specification layer.

In the current language version, these are:
- `scpp::int_t`
- `scpp::float_t`
- `scpp::bool_t`
- `scpp::string_t`
- `scpp::null_t`
- `scpp::nullable<T>`
- `scpp::shared_p<T>`
- `scpp::unique_p<T>`
- `scpp::weak_p<T>`

The presence of these names in this document does not make this document the inventory authority.
TYPE_FAMILY_REGISTRY.md remains the authority.

---

## 7. Constructor and Conversion Surface

The runtime MUST expose only the constructor and explicit-conversion surface required to implement the allowed conversion and initialization behavior in SEMANTIC_MATRIX.md.

Constraints:
- no implicit conversion surface beyond what C++ syntax requires for direct construction of the semantic wrapper itself
- no convenience constructors that broaden semantics
- no public API that allows forbidden cross-family initialization through accidental overloads
- explicit conversion surface must remain aligned with the matrix

Current minimum explicit conversion obligations:
- support explicit conversion between `int_t` and `float_t`
- preserve rejection of all non-authorized explicit conversions

---

## 8. Public Operator Surface

The runtime MUST expose operator overloads exactly sufficient to implement the allowed operator pairs in SEMANTIC_MATRIX.md.

Constraints:
- no operator overload may broaden semantic behavior beyond the matrix
- no overload may rely on implicit C++ fallback behavior to authorize an otherwise forbidden semantic operation
- pointer-like and nullable-like families must not inherit accidental operator behavior from wrapped implementation details

Current operator families that the runtime must support, as defined by the matrix:
- arithmetic
- equality
- relational
- logical
- assignment
- compound assignment

---

## 9. Conditional Bridge

The runtime and generated code together MUST preserve the conditional bridge defined in the specification layer:

- `bool_t` is conditionally valid
- native C++ `bool` produced by allowed comparison and logical operations is valid for control flow
- no general truthiness is introduced for other semantic values

The runtime MUST NOT expose a public API that reintroduces truthiness for non-boolean semantic families.

---

## 10. Internal Freedom Boundary

The runtime implementation MAY use:
- traits
- helper templates
- deleted overloads
- concepts or SFINAE-compatible restrictions
- static assertions
- internal normalization helpers

But these implementation choices:
- MUST remain internal
- MUST not become generator-visible contract
- MUST not redefine semantics
- MUST not create alternate public API paths

---

## Final Statement

This document defines the canonical runtime interface surface.

It implements the specification layer.
It does not replace it.
