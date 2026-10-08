# Closures (Lambda Functions)
Doc Status: normative
## Overview

A closure is an anonymous function value defined inline:

```php
$f = function (int $a): int {
	return $a + 1;
};
```

Closures are **first-class local values** with a **concrete compiler-generated type**.

---

## Semantics

- Each closure expression produces a **unique, anonymous type**
- Closures are **not dynamically typed**
- Closures are **not interchangeable unless explicitly coerced**
- Invocation uses standard call syntax:

```php
$ret = $f(10);
```

---

## Allowed usage

Closures are supported in **local, concrete contexts only**:

```php
$f = function (int $a): int {
	return $a + 1;
};

$ret = $f(10);
```

---

## Explicit callable boundaries

`function<R(A, B, ...)>` denotes a callable value contract with return type `R`
and ordered parameter types. It does not encode parameter names or a particular
closure expression's identity.

```php
$f function<int(int)> = function (int $value): int { return $value + 1; };
function apply(function<int(int)> $operation, int $value): int {
	return $operation($value);
}
```

An explicit local or parameter contract permits a compatible concrete closure to
cross that boundary. Inferred closure locals remain concrete; this conversion is
not applied merely because a value is callable. Different closure expressions may
initialize or replace the same explicitly typed callable storage.

The current v0.2 slice requires exact canonical parameter and return types, with
ordinary by-value parameters. No variance or numeric signature adaptation is
inferred. Explicit callable locals require initialization. Copying and reassignment
preserve the callable contract. Function and instance/static method parameters use
the same rule, for classes and structs alike.

Empty callable storage, callable returns, reference signatures, captures and
contextual signature inference remain deferred in this slice. Properties and
wrapper/container storage of callable values are also deferred. The C++ backend
represents explicit callable contracts with `std::function`; this does not change
the concrete-lambda lowering of inferred locals.

## Forbidden usage (concrete closures)

Closures **cannot be stored in dynamic or untyped containers**:

```php
$data = [];
$data['fn'] = function () { ... }; // âŒ compile-time error
```

Closures **cannot be assigned to mixed_t**:

```php
$m = function () { ... }; // âŒ if $m is mixed_t
```

Closures **cannot be implicitly unified across different types**:

```php
$f = $cond
	? function (int $x): int { return $x + 1; }
	: function (int $x): int { return $x + 2; }; // âŒ
```

---

## Capture semantics

Closures may capture variables from the surrounding scope:

```php
$x = 10;

$f = function (int $a): int use ($x) {
	return $a + $x;
};
```

Captured values are stored as fields in the generated closure object.

---

## Lowering (C++)

Closures lower to native C++ lambdas:

```php
$f = function (int $a): int {
	return $a + 1;
};
```

â†“

```cpp
auto f = [](int a) -> int {
	return a + 1;
};
```

Captured variables become lambda captures.

No type erasure is introduced unless required by a callable boundary.

---

## Design constraints

- Closures are **compile-time concrete objects**, not runtime dynamic values
- Concrete local closures require no type-erasure allocation; explicit callable boundaries may allocate.
- Inference does not introduce a generic callable type. Conversion requires an explicit callable contract.
