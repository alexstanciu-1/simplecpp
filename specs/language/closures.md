# Closures (Lambda Functions)

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

## Forbidden usage

Closures **cannot be stored in dynamic or untyped containers**:

```php
$data = [];
$data['fn'] = function () { ... }; // ❌ compile-time error
```

Closures **cannot be assigned to mixed_t**:

```php
$m = function () { ... }; // ❌ if $m is mixed_t
```

Closures **cannot be implicitly unified across different types**:

```php
$f = $cond
	? function (int $x): int { return $x + 1; }
	: function (int $x): int { return $x + 2; }; // ❌
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

↓

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
- No allocation is required for local closures
- No implicit conversion to a generic callable type occurs

---

## Future extensions (non-normative)

The following may be introduced in future versions:

- Typed callable parameters (`function(int): int`)
- Returning closures
- Owning callable containers (`inplace_function`)
- Borrowed callable views (`function_ref`)
