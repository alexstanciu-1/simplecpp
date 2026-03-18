# DESIGN_NOTES.md

## 1. Purpose

This document provides non-normative explanations, rationale, and design decisions behind the Simple C++ language.

It complements:
- `SPECIFICATIONS.md` for normative rules
- `SEMANTIC_MATRIX.md` for allowed operation families

This document may include:
- examples
- trade-offs
- comparisons
- rationale

It does **not** define behavior by itself.

---

## 2. Design Philosophy

Simple C++ is intentionally **not PHP-compatible**.

Instead, it prioritizes:
- strict typing
- explicit conversions
- predictable semantics
- mechanically enforceable boundaries

### Core principle
> If behavior cannot be reasoned about statically, it should not be accepted casually.

---

## 3. Why no implicit conversions

PHP-style coercion makes it easy to write code whose meaning depends on runtime value shape rather than declared semantic type.

Removing broad implicit conversions gives:
- fewer hidden branches in meaning
- smaller operator surfaces
- stronger compile-time rejection

---

## 4. Why no truthiness

In PHP, many unrelated value families can become conditions:
- numbers
- strings
- null-like states
- containers

That is convenient, but it mixes value semantics with control-flow semantics.

Simple C++ separates them:
- non-boolean values stay non-boolean
- conditions must already be semantic-boolean
- there is no “try to interpret this value as truthy” fallback

This improves predictability and keeps control-flow rules narrow.

---

## 5. Why conditions still mention native `bool`

There is one subtle implementation choice that matters:

- the language-level boolean wrapper is `bool_t`
- comparison operations return native C++ `bool`

This is deliberate.

### Rationale
Wrapping every comparison result back into `bool_t` would add noise and complexity in generated code without improving the semantic model.

So Simple C++ uses a narrow bridge:
- comparisons and logical lowering may yield native `bool`
- that native `bool` is allowed directly in generated control flow
- this does **not** mean `int_t`, `string_t`, `null_t`, or arbitrary wrappers gain truthiness

The bridge exists only for already-boolean results.

---

## 6. Arithmetic rules

Allowed:
- `int + int`
- `int + float`
- `float + float`

Forbidden:
- boolean arithmetic
- string arithmetic in the numeric sense
- null arithmetic
- pointer-like arithmetic

The goal is to keep arithmetic numeric-only.

---

## 7. Comparison design

Comparison operators return native C++ `bool`.

### Why not `bool_t`?
- less wrapping noise in generated code
- simpler control-flow lowering
- easier interop with ordinary C++ condition syntax

This is a representation choice, not a widening of the conversion model.

---

## 8. Conversion design

Conversions must be visible in source or in the generated lowering path.

Current explicitly documented examples include:
- `to_int(float_t)` with truncation toward zero
- explicit `bool_t -> int_t`

The design preference is:
- no hidden widening
- no hidden booleanization
- no broad “best effort” parsing/coercion

---

## 9. Pointer and nullable discipline

Pointer-like families and `nullable<T>` are intentionally more restricted than many host-language analogues.

Why:
- pointer truthiness tends to leak host-language habits into the semantic layer
- optional/null-like wrappers become hard to reason about if they participate in broad coercion

Where the runtime exposes contextual boolean support for such wrappers, it should stay tightly scoped and runtime-oriented.

That support is for generated/runtime mechanics, not a license for source-language truthiness.

---

## 10. Enforcement strategy

Simple C++ intentionally uses two enforcement layers:

1. S2S rejection
2. generated C++ compilation failures

This is pragmatic:
- some violations are easiest to reject at the source-language level
- some are cheapest to enforce by the runtime surface and C++ type system

The long-term preference is earlier diagnostics where practical, but compile-time hardening remains valuable.

---

## 11. Differences from PHP

| Feature | PHP | Simple C++ |
|---|---|---|
| Typing | dynamic | strict semantic wrappers |
| Coercion | broad implicit | narrow and explicit |
| Truthiness | broad | none |
| Comparisons | loose + coercive variants | narrow, family-specific |
| Conditions | many value families allowed | semantic-boolean only |

---

## 12. Trade-offs

### Advantages
- more predictable control flow
- tighter operator boundaries
- better compile-time failure behavior
- easier reasoning about generated code

### Costs
- less shorthand
- more explicit helper usage
- more front-end responsibility when mapping from PHP-like syntax

---

## 13. Future clarification areas

The docs still benefit from deeper normalization around:
- `nullable<T>` semantics
- assignment compatibility vs conversion helpers
- exact enforcement ownership for each rule family

---

## 14. How to read the docs

- `SPECIFICATIONS.md`: invariants and interpretation rules
- `SEMANTIC_MATRIX.md`: allowed and forbidden operation families
- `RUNTIME_REQUIREMENTS.md`: what the runtime must provide or block
- `DESIGN_NOTES.md`: why the rules look the way they do
