# Late Static Binding CRTP Specialization
Doc Status: planning

Date: 2026-05-19

Purpose:
- record the CRTP-like specialization direction as a possible v2 follow-up for `static::method(...)`
- preserve a lower-runtime-overhead alternative to the current phase-1 template-guard/runtime-context approach
- keep compiler-load and generator-complexity tradeoffs explicit before any broader class-emission redesign

## Status

This document is planning only.

It does not change current language, generator, runtime, or docs authority by itself.

Current implementation direction for first-pass support lives separately and is centered on:

- `_static<CURRENT_CLASS>(...)`
- a small active late-static context
- generated per-class dispatch helpers for `static::method(...)`

This v2 note exists only as a “to look at later” path.

## Why This Exists

The phase-1 runtime-context design was chosen because it fit the current generator shape better and was implementable without a larger class-lowering rewrite.

Even so, a CRTP-like specialization path remains worth revisiting because it may offer:

- lower runtime overhead
- less ambient runtime state
- simpler final emitted call sites for some inherited static-method patterns
- a more purely compile-time expression of PHP late-static method rebinding

## Core Idea

For inherited static methods that use:

```php
static::method(...)
```

generate a helper shape where the effective called class is carried as a template parameter rather than as ambient runtime context.

Conceptual sketch:

```cpp
template <typename _STATIC>
struct A__late_static_helper {
	static string_t run() {
		return _STATIC::hello();
	}
};
```

Then a concrete class could route inherited static behavior through a specialization context such as:

```cpp
class A {
public:
	static string_t hello();
	static string_t run();
};

class B : public A {
public:
	static string_t hello();
	static string_t run();
};
```

with generated wrappers or helper calls that effectively specialize inherited late-static behavior for `B`.

## Why It Was Not Chosen For Phase 1

The current generator and class emission pipeline made this path less attractive as the first implementation because it likely requires:

- more aggressive restructuring of generated class/method emission
- possible synthetic descendant wrappers for inherited static methods
- careful integration with the current header/source split
- more design work around inheritance shape and helper placement

The phase-1 runtime-context design was more incremental.

## What To Revisit In V2

1. Can inherited static methods that use `static::...` be specialized per current emitted class without violating generator-boundary rules?
2. Can helper-template emission stay file-local and structurally driven?
3. Does CRTP-like specialization reduce runtime work enough to justify the extra generator complexity?
4. Does the emitted C++ remain acceptable in compile time, readability, and maintenance?
5. Can this be introduced selectively without destabilizing ordinary class generation?

## Relation To Current Boundary Rules

Any CRTP-like v2 path must still preserve the project’s generator model:

- no cross-source-file semantic validation
- no semantic compiler behavior
- no type-driven global hierarchy reasoning beyond explicit file-local structure

If a CRTP-like direction requires broader semantic knowledge than the current file-local IR can safely provide, that would be a strong reason to reject it.

## Recommendation

Keep this on the v2 list only.

Do not block the current phase-1 late-static implementation on a CRTP redesign.

If the phase-1 implementation later shows meaningful runtime cost, code-size growth, or maintainability pain, revisit this note as the main alternative optimization direction.
