# DESIGN_NOTES.md

## Purpose
This document contains non-normative explanations, rationale, and cross-language mapping for Simple C++.

This document must NOT define behavior. All normative rules live in:
- SPECIFICATIONS.md
- SEMANTIC_MATRIX.md

---

## 1. Design Philosophy

Simple C++ is a strict, explicit, non-coercive subset model.

Key principles:
- no implicit conversions
- no truthiness
- explicit semantics only
- predictable lowering to C++

---

## 2. Validation Model (Current)

The system relies on:
- deterministic lowering to C++
- C++ compilation for validation

The S2S generator is intentionally minimal and does not validate semantics.

When expression types are unknown at S2S time, the generator may still lower the source expression directly into C++ and use `auto` for unknown expression results.
Type resolution, external declaration visibility, overload selection, and failure for invalid combinations are intentionally delegated to the C++ compilation stage.

This means the current pipeline favors:
- faithful lowering
- explicit runtime semantics
- compiler-driven validation

It does NOT require:
- frontend type inference
- cross-file symbol resolution inside S2S
- early semantic rejection by the generator

---

## 3. Conversion Philosophy

Examples defined in SEMANTIC_MATRIX.md.

---

## 4. Conditional Model

- no truthiness
- only boolean-producing expressions allowed

---

## 5. PHP Mapping

Removes:
- implicit conversion
- truthiness
- dynamic typing

At the same time, the current lowering model accepts that PHP source may reference symbols whose types are not locally known during generation.
Such cases are carried forward into generated C++ and validated there.

---

## Final Rule

Non-normative.
