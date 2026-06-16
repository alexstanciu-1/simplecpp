# Strict Closure `use` Callable Plan
Doc Status: planning

Date: 2026-06-08

## Purpose

Plan strict-mode PHS support for predictable PHP-shaped closure capture with `use (...)`, including explicit callable locals and a narrow structural convenience rule for immediately declared closure initializers.

This note is planning guidance only. It does not supersede `specs/language/closures.md`, `specs/spec_map.md`, or generator rules.

## Target User Surface

The primary strict form is an explicitly typed callable local:

```php
$f function<int()> = function () use ($a): int {
	return $a;
};
```

The initializer closure may omit its return type when the callable local type supplies the full expected signature:

```php
$f function<int()> = function () use ($a) {
	return $a;
};
```

A convenience form is allowed when the closure initializer has a complete syntactic signature directly at the assignment site:

```php
$f = function () use ($a): int {
	return $a;
};
```

This convenience form should be treated as local structural synthesis of the callable storage type, not general semantic inference.

## Non-Goals

- Do not support non-PHP closure syntax such as `function (): int use (...)`.
- Do not infer closure return types from body expressions.
- Do not infer or unify callable types across conditionals or containers.
- Do not allow closures to become `mixed_t` or dynamic table payloads.
- Do not change namespace `use` import behavior.

## Semantics

Capture behavior should match regular PHP expectations where practical:

- `use ($a)` captures the current value at closure creation time.
- `use (&$a)` captures by reference, so later writes are visible.
- Multiple captures preserve source order in the emitted lambda capture list.
- Captured variables must be simple local variable names already visible at the closure site.
- Closure parameters remain explicitly typed unless an expected callable signature supplies the parameter types.

## Lowering Shape

Explicit callable local:

```php
$f function<int()> = function () use ($a): int {
	return $a;
};
```

Conceptual lowering:

```cpp
std::function<int_t()> f = [a]() -> int_t {
	return a;
};
```

Convenience local with complete closure signature:

```php
$add = function (int $x, int $y) use ($base): int {
	return $base + $x + $y;
};
```

Conceptual synthesized storage:

```cpp
std::function<int_t(int_t, int_t)> add = [base](int_t x, int_t y) -> int_t {
	return base + x + y;
};
```

## Implementation Notes

- Reuse the existing closure AST `uses` path in `Generator.php`.
- Ensure strict local callable syntax such as `$f function<int()> = ...` feeds `currentExpectedClosureSignature`.
- When no explicit local type exists, synthesize `std::function<Return(Params...)>` only for direct local assignment from `AST_CLOSURE` with explicit closure return type and typed parameters.
- Keep the generator structural: do not inspect closure body returns to infer the return type.
- Preserve existing generator boundary rules; deeper type compatibility remains compiler/STAN/runtime territory unless a local generation rule already owns it.

## First Validation Matrix

```php
$a = 1;
$f function<int()> = function () use ($a): int {
	return $a;
};
echo $f(), "\n";
```

Expected output: `1`

```php
$a = 1;
$f function<int()> = function () use ($a) {
	return $a;
};
echo $f(), "\n";
```

Expected output: `1`

```php
$a = 1;
$f function<int()> = function () use (&$a): int {
	return $a;
};
$a = 2;
echo $f(), "\n";
```

Expected output: `2`

```php
$a = 1;
$b = 2;
$c = 3;
$aaa function<int()> = function () use ($a, $b, $c): int {
	return $a + $b + $c;
};
echo $aaa(), "\n";
```

Expected output: `6`

```php
$base = 10;
$add = function (int $x, int $y) use ($base): int {
	return $base + $x + $y;
};
echo $add(2, 3), "\n";
```

Expected output: `15`

## Rejection Matrix

```php
$f = function () use ($a) {
	return $a;
};
```

Expected: clear generator diagnostic requiring either an explicit callable local type or an explicit closure return type.

```php
$f = $condition
	? function (): int { return 1; }
	: function (): int { return 2; };
```

Expected: unsupported until a separate conditional callable unification design exists.

```php
$items = [];
$items[] = function (): int { return 1; };
```

Expected: unsupported; closures are not dynamic/container payloads in the current strict model.

## Decisions And Open Questions

- Explicit local callable type plus explicit closure return type mismatches are STAN-owned diagnostics before build. Example: `$f function<string()> = function (): int { return 1; };` should be reported by STAN rather than by broad S2S callable compatibility inference.
- Should by-value capture of object handles copy the handle exactly, preserving shared object identity, or should any additional non-null contract checks be introduced later by STAN/runtime boundary work?
- Should STAN own undeclared capture diagnostics before generation, or should generator diagnostics remain the first enforcement layer for this feature?
