Doc Status: normative


See `../../specs/spec_map.md` for document hierarchy, authority, and v1 conflict-resolution rules.

# Prism++ â€“ Primary-Type Normalized Parameters

Status: Active v1 design + template-wrapper implementation slice.

## 1. Purpose

This document defines the Prism++ interpretation of PHP union parameters used as normalized inputs.

This is a Prism++ language concept by design. It does not attempt to match standard PHP union semantics, traditional C++ overload semantics, or full C++ template semantics.

## 2. Core rule

For a parameter declared with a PHP union type:

```php
function add(int|float $left, int $right): int {}
```

Prism++ interprets the first listed type as the **primary type**.
All later listed types are **secondary source types**.
The callable body works with the primary type only.

So the example above means:

- primary type: `int`
- secondary source type: `float`
- body-visible type: `int`

## 3. Generation model

When a function or method contains one or more normalized parameters, the generator emits:

- a template wrapper for the callable itself
- one normalization helper per normalized parameter
- ordinary non-normalized parameters unchanged in the callable signature
- the original PHP parameter name inside the callable body after normalization

Baseline conceptual shape:

```cpp
template <typename T_left>
int_t add(T_left&& _left, int_t right) {
	int_t left = _norm_add__left(std::forward<T_left>(_left));
	return (left + right);
}
```

For larger lowered bodies, the generator may split execution into a canonical typed executor so the heavy body lives in the `.cpp` file while normalization stays inline in the header.

Conceptual split shape:

```cpp
int_t add__exec(int_t left, int_t right);

template <typename T_left>
int_t add(T_left&& _left, int_t right) {
	int_t left = _norm_add__left(std::forward<T_left>(_left));
	return add__exec(left, right);
}
```

Current generator heuristic: use the canonical executor split when the callable needs normalized template lowering and its lowered statement body has more than 2 statements.

## 4. Normalization rules

A secondary source type is normalized into the primary type before normal function or method logic begins.

Preferred path:
- explicit normalization rule from docblock metadata

Fallback path:
- emit the configured cast utility in canonical form, currently `cast<T>(...)` for project explicit scalar casts
- the generator does not validate whether the resulting cast is semantically valid beyond the configured lowering route
- invalid generated casts are expected to fail later in C++ compilation or at runtime for `mixed_t` dispatch cases

## 5. Annotation grammar

Supported docblock line form:

```php
@arg.left.from(float) = (int)$left
```

Rules:
- `@` is mandatory
- spaces around `=` are optional
- source type names may be namespaced
- multiple `@arg.<param>.from(Type)` lines are allowed for the same parameter when the source `Type` differs
- duplicate source `Type` rules for the same parameter are a generator error
- the right-hand-side is real PHP expression code, not a mini DSL
- the generator parses that right-hand-side into php-ast and lowers it through the normal expression codegen path
- the expression is evaluated in a helper-local scope where the annotated parameter name is bound to the current source-typed input value
- semantic validity is still governed by the normal generator/runtime rules for the emitted expression

## 6. Accepted routes inside normalization

For the current implementation slice, normalized parameters support:

- direct accepted scalar-like wrapper types
- `mixed_t` as a runtime carrier for those already-accepted scalar-like types

Rules:
- `mixed_t` is not the public callable surface model
- `mixed_t` is only handled inside the generated normalization helper
- runtime kinds outside the declared accepted scalar-like types are rejected at runtime
- object-like secondary source types are not part of this v1 implementation slice yet

## 7. Non-goals

### 7.1 Traditional C++ overload semantics

Not supported and not desired.

If same-name callables need materially different behavior, use multiple distinct functions or methods instead of overload-style same-name variants.

### 7.2 Templates as a language promise

Templates are acknowledged as a complementary future mechanism for genuinely generic algorithms, but there is no short-term language promise here.

This feature uses template machinery as a code-generation mechanism for normalized parameters.

## 8. Internal naming

Generator-internal normalization helpers use this shape:

- `_norm_<callable>__<param>`

Examples:
- `_norm_add__left`
- `_norm_order_cancel__order_id`
- `_norm_method__left`

Only parameters that actually require normalization receive generated storage names such as `_left`.
Ordinary single-type parameters keep their original emitted names.

## 9. Validation rules

The generator must raise hard errors for:
- malformed `@arg...` annotation syntax
- unknown parameter names in `@arg...` rules
- `.from(Type)` where `Type` is not present in the declared union
- duplicate `.from(Type)` entries for the same parameter

## 10. Scope

This feature applies to:
- free functions
- instance methods
- static methods

The current safe subset does not extend this mechanism to by-reference normalization from `mixed_t`. Typed by-reference parameters remain limited to cases where the source expression is already directly stable and native-reference bindable under `specs/native_reference_safety.md`.

## 11. Current implementation status

Current generator implementation includes:
- centralized parsing of `@arg.<param>.from(Type) = expression`
- hard validation of malformed, duplicate, and out-of-union rules
- primary-type extraction from union declarations
- template-wrapper lowering for scalar-like normalized parameters
- one generated normalization helper per normalized parameter
- optional `__exec` body split for non-trivial normalized callables so the canonical typed body can live in the source file
- plain single-type parameters kept unchanged in the generated signature
- explicit `@arg.<param>.from(Type)` lowering into normalization helper branches via real PHP expression parsing and normal expression lowering
- cast-utility fallback when no explicit normalization rule exists

Non-scalar union members remain follow-up work. By-reference normalization from `mixed_t` is not part of the current safe subset; broader future work may revisit this only if it can preserve the native-reference safety rule.
