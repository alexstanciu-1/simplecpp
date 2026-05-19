# Late Static Binding Template Guard
Doc Status: planning

Date: 2026-05-19

Purpose:
- capture a plausible implementation direction for PHP-shaped `static::method(...)`
- prefer a small generator-driven design over broader runtime type systems or heavyweight C++ dispatch scaffolding
- keep compile-time and emitted-code cost visible as a primary constraint

## Status

This document is planning only.

It does not change current language, generator, runtime, or docs authority by itself.

Current checked-in behavior remains:

- `static::` is rejected by the PHP S2S generator
- `static::$prop` remains unsupported

If this direction is adopted, the resulting authoritative updates should be made in:

- top-level language/spec docs if user-visible support is added
- `generators/php/specs/rules.md`
- `generators/php/specs/catalog.md`
- `generators/php/specs/unsupported.md`
- relevant generator tests and samples

## Problem Statement

Strict projects currently reject:

```php
static::hello();
```

with a generator error equivalent to:

```text
static:: is not supported in the current pass.
```

This is real authoring friction because `static::method(...)` is a normal PHP-shaped static-dispatch form and appears naturally while reducing or refactoring class logic.

The challenge is that ordinary C++ static member lookup does not provide PHP-style late static binding automatically:

- `B::run()` may legally resolve an inherited `A::run()`
- but code emitted inside `A::run()` does not automatically reinterpret lexical `A::hello()` as `B::hello()`
- C++ has no ambient nested PHP-style late-static context for static member calls

Any implementation must therefore choose an explicit lowering strategy.

## Design Goals

The working direction in this note prioritizes:

- support for `static::method(...)`
- minimal user-visible semantic surprise
- low C++ compiler load
- no fake object construction just to name a class
- no reliance on RTTI purely for identity bookkeeping
- no large virtual-carrier hierarchies unless later evidence proves they are necessary

## Non-Goals For Phase 1

This note does not propose first-pass support for:

- `static::$prop`
- `new static`
- `static::CONST`
- generalized late-static property or constant binding
- deeper hierarchy validation in the S2S generator

Phase 1 target surface is only:

- `static::method(...)`

## Rejected Directions Considered

### 1. Direct rewrite to lexical class

Example idea:

```cpp
A::hello(...)
```

Reason rejected:

- this is effectively `self::`, not PHP `static::`
- inherited base methods would keep binding to the lexical declaring class

### 2. `typeid(...)`-driven identity

Reason rejected:

- no built-in C++ method-id facility comes with it
- still needs explicit dispatch glue
- adds RTTI usage without clearly simplifying the design

### 3. Real object/no-op-instance construction as class anchor

Example idea:

```cpp
A::_static_A()::hello()
```

Reason rejected as a primary plan:

- risks unnecessary construction or allocation
- still hardcodes lexical class unless additional specialization machinery exists
- does not by itself create nested late-static context

### 4. `std::variant` / `std::visit`

Reason rejected for phase 1:

- probably heavier on template instantiation than a simpler generated helper
- solves a richer runtime-state problem than the phase-1 identity-only need

## Working Direction

Use a small generated helper with a scoped template guard:

```cpp
_static<CURRENT_CLASS>([&]() {
	return __static_call_method(...);
})
```

The key properties are:

- `CURRENT_CLASS` is encoded as a template argument
- the helper may establish late-static context if none is active
- if a late-static context is already active, it preserves that outer context
- the helper pops/restores context only if it created it
- nested `static::...` sites therefore share the outer late-static class naturally

This gives the implementation an explicit nested late-static scope, which ordinary C++ static calls do not provide on their own.

## Core Runtime/Generator Model

### Template guard helper

Sketch:

```cpp
template <typename CurrentClass, typename Fn>
auto _static(Fn&& fn);
```

Behavior:

1. determine whether a late-static class is already active
2. if none is active, raise `CurrentClass` as the active late-static class
3. run `fn`
4. restore the previous state only if this helper activated it

### Late-static call helpers

The lambda body should not blindly hardcode:

```cpp
CurrentClass::method(...)
```

because that would lock in the lexical class and defeat true late-static behavior for nested/inherited cases.

Instead, the lambda should call a generated late-static dispatcher helper for the specific method family, for example:

```cpp
_static<CURRENT_CLASS>([&]() {
	return __static_call_hello(...);
})
```

where `__static_call_hello(...)` uses the currently active late-static class to dispatch to the correct concrete class static method.

### Dispatch representation

This note intentionally does not lock the project into one final internal representation yet.

Plausible low-cost options include:

- generated class ids plus `switch`
- generated lightweight class tokens
- another comparably small explicit dispatch mechanism

Current planning preference:

- avoid RTTI-only identity
- avoid per-method virtual carrier hierarchies in the first pass
- prefer something generator-owned and compiler-cheap

## Why The Template Guard Is Attractive

Compared with alternative directions discussed so far, the template guard has several practical benefits:

- cleaner generated call-site shape than passing raw runtime ids everywhere
- explicit nested scope for late-static context
- no need to rely on ordinary C++ static lookup to propagate across calls
- better fit for the current generator than a whole-program hierarchy analysis approach
- more incremental than redesigning class emission around CRTP immediately

## Relation To Current Generator Constraints

The generator is intentionally local and type-blind.

This planning direction respects that constraint:

- it does not require full hierarchy validation
- it does not require global semantic inference
- it does require explicit lowering at `static::method(...)` sites
- it does require a small shared notion of active late-static context

The generator is already aware of the current class context while emitting method bodies.
That awareness can supply `CURRENT_CLASS` to the `_static<...>(...)` helper.

## Expected Lowering Shape

Source:

```php
class A {
	public static function run(): string {
		return static::hello();
	}

	public static function hello(): string {
		return "A";
	}
}

class B extends A {
	public static function hello(): string {
		return "B";
	}
}
```

Planning-level conceptual lowering:

```cpp
string_t A::run() {
	return _static<A>([&]() -> string_t {
		return __static_call_hello();
	});
}
```

If `B::run()` enters through inherited static flow, the active late-static class should remain `B` through nested `_static<...>(...)` scopes, so `__static_call_hello()` resolves to `B::hello()`.

This note leaves open whether inherited static entrypoints should also gain generated wrappers or whether ambient active-class state alone is sufficient for the first pass.

## Open Questions

1. What is the smallest acceptable internal dispatch representation for `__static_call_<method>(...)`?
2. Should the active late-static context be runtime-owned, generator-owned, or split between the two?
3. Do inherited static method entrypoints need generated wrappers to establish the correct outer class more explicitly?
4. Can the first pass safely restrict support to static methods only when the target method name is literal and locally normalizable?
5. How should missing/invalid late-static context fail if a dispatcher is reached unexpectedly outside a properly raised scope?

## Proposed First Implementation Scope

1. Keep `static::$prop` unsupported.
2. Add planning/spec rows for `static::method(...)` only.
3. Add a small late-static scope helper with explicit nested behavior.
4. Lower `static::method(...)` to `_static<CURRENT_CLASS>(...)` plus a generated method-family dispatcher helper.
5. Add one focused positive generator/runtime test based on the existing `A`/`B` inherited static-method shape.
6. Add at least one negative test confirming that unsupported late-static property access still rejects cleanly.

## Validation Strategy

Validate at the smallest layer that proves the feature:

- focused transpilation fixture for `static::method(...)`
- generator test for the emitted helper shape
- project/build test showing inherited static call behavior
- no broader runtime or hierarchy expansion unless the first pass proves insufficient

## Recommendation

The template-guard direction is a credible candidate for a first real implementation because it:

- acknowledges that plain C++ static calls do not carry PHP late-static context
- keeps the implementation explicit
- avoids RTTI-for-its-own-sake
- appears lighter than variant or virtual-carrier approaches

Before implementation, the next step should be a small design spike that answers only these questions:

- how active late-static context is represented internally
- what one generated `__static_call_<method>` helper looks like
- whether inherited static entrypoints need wrappers in practice
