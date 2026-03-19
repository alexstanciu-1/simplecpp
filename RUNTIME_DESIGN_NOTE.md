# RUNTIME_DESIGN_NOTE.md

## Scope

This document is **non-normative**.

It explains runtime design decisions and implementation rationale for the Simple C++ system.

It does NOT define language semantics.

All language behavior MUST be defined exclusively in:
- SPECIFICATIONS.md
- SEMANTIC_MATRIX.md

If any statement in this document appears to define or modify behavior, it is invalid and must be relocated.

The canonical public runtime interface is defined in:
- RUNTIME_API_CONTRACT.md

---

## Purpose

The purpose of this document is to:
- explain why the runtime is structured as it is
- document implementation trade-offs
- clarify how C++ mechanisms are used to enforce the semantic model
- provide guidance for maintainers and contributors

---

## Design goals

The runtime is designed to:

1. **Enforce semantics defined elsewhere**
   - The runtime must reflect the rules in the semantic matrix, not invent them

2. **Match one canonical public interface**
   - The runtime must expose exactly the API shape required by `RUNTIME_API_CONTRACT.md`

3. **Fail at compile time whenever possible**
   - Prefer static errors over runtime errors

4. **Eliminate implicit behavior**
   - Avoid all implicit conversions and fallback mechanisms

5. **Be predictable and explicit**
   - Every allowed operation must be deliberate and visible in code

---

## Type wrapper design

### Rationale

Primitive and composite types are implemented as wrapper types (e.g., `int_t`, `float_t`, `shared_p<T>`).

This allows:
- strict control over operator availability
- prevention of implicit conversions
- explicit modeling of ownership and nullability

### Key properties

- no implicit conversion operators
- explicit constructors only where contracted
- controlled operator overloading
- public API surface fixed by `RUNTIME_API_CONTRACT.md`

---

## Operator implementation strategy

### Explicit overloads

Each public operator is implemented explicitly for allowed type combinations.

Rationale:
- prevents accidental coverage via templates
- ensures full alignment with the semantic matrix
- ensures exact agreement with the API contract

### Deleted or missing operators

Forbidden operations are enforced internally by:
- deleting operators
- omitting overloads

Rationale:
- guarantees compile-time failure
- prevents silent fallback behavior

### No generic fallback

Generic templates or catch-all overloads are avoided in the public surface.

Rationale:
- prevents unintended type acceptance
- ensures all public behavior is explicitly declared

---

## Compile-time enforcement

### Techniques used

Internal enforcement may use:
- deleted functions
- constrained templates
- static assertions
- explicit overload sets

### Rationale

Compile-time enforcement ensures:
- immediate feedback
- no runtime ambiguity
- strong guarantees about correctness

Public behavior remains the exact surface defined in `RUNTIME_API_CONTRACT.md`.

---

## Boolean handling

### Design choice

Boolean behavior is tightly controlled to avoid truthiness.

### Public API approach

- no implicit conversion to `bool`
- no public `operator bool()`
- no general public condition helper
- comparison and logical operators return native `bool` exactly where contracted

### Rationale

This preserves:
- strict conditional semantics
- separation between semantic boolean and host-language convenience
- a deterministic public interface

---

## Pointer and ownership model

### Wrapper-based approach

Pointer-like types are implemented as wrappers:
- `shared_p<T>`
- `unique_p<T>`
- `weak_p<T>`

### Rationale

This allows:
- explicit ownership semantics
- safe memory handling
- controlled comparison behavior

### Ownership behavior

Copy/move semantics follow the canonical runtime contract.

Important:
- these behaviors are implementation details except where exposed by the contract
- they must not alter allowed operations defined in the matrix

---

## Conversion handling

### Public conversion model

All public semantic conversions are implemented through the contracted explicit constructor surface sufficient to support generator-emitted `static_cast`.

There is no public helper-based conversion API.

### Rationale

- avoids hidden behavior
- gives the generator one canonical conversion form
- aligns public conversion surface exactly with the matrix
- prevents multiple equivalent public spellings

### Internal helpers

Internal helpers MAY exist in `scpp_intern` only as implementation detail.

They:
- must not be public
- must not be required by generated code
- must not form an alternative semantic conversion surface

---

## Error handling philosophy

### Fail fast

Invalid constructs should fail:
- at compile time whenever possible

### No silent recovery

The runtime must not:
- guess intent
- coerce values
- provide fallback behavior

---

## Alignment with specification

The runtime must always satisfy:
- every implemented public operation exists in SEMANTIC_MATRIX.md
- every implemented public API form exists in RUNTIME_API_CONTRACT.md
- no extra public operations are introduced
- all forbidden operations fail compilation

---

## Evolution guidelines

When modifying runtime behavior:
1. verify rule exists in SEMANTIC_MATRIX.md
2. verify public form exists in RUNTIME_API_CONTRACT.md
3. implement explicitly
4. ensure forbidden cases fail
5. add tests

Never introduce public behavior first in runtime.

---

## Anti-patterns (forbidden)

- defining semantics in runtime
- exposing public semantic conversion helpers
- relying on implicit C++ conversions
- using generic templates as public fallback
- allowing truthiness through `operator bool`
- implementing behavior not present in matrix
- implementing public forms not present in the API contract

---

## Final statement

The runtime is an enforcement mechanism.

It exists to implement the semantics defined in the specification through one canonical public interface.

It must never become a source of truth.
