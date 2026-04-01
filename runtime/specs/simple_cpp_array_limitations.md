# Array Behavior Differences in Simple C++ (PHP → Simple C++)

This document outlines the known behavioral differences between PHP arrays and the current `table_t` implementation in Simple C++.
This document is non-authoritative.

## Overview

`table_t` is not a full PHP array implementation. It supports a practical subset of behaviors but diverges in several important areas.

---

## Known Differences

### 1. Negative integer keys
- Not reliably supported
- May be reinterpreted as large unsigned integers

### 2. Large integer keys
- Keys beyond 32-bit range are not safe
- May lead to incorrect behavior

### 3. Append after unset
PHP:
```php
unset($x[1]);
$x[] = 'c'; // usually key 2
```

Simple C++:
- Likely reuses max+1 logic → may become key 1

---

### 4. Missing vs null collapse

```php
$x["missing"] == null
```

- Missing key and stored null are indistinguishable in value context

---

### 5. isset behavior mismatch

PHP:
- `isset($x["a"])` is false if value is null

Simple C++:
- behaves like key existence check (true if key exists)

---

### 6. Key coercion differences

Not supported:
- numeric string → int conversion
- bool/null/float coercion

Only:
- exact int
- exact string

---

### 7. Nested auto-growth

Not fully supported:

```php
$x["a"]["b"] = 1;
```

- May fail or behave inconsistently

---

### 8. By-ref materialization

```php
add($x[999], 1);
```

- May create slot if missing
- read-only access does NOT create slot

---

### 9. Typed by-ref coercion

```php
add(int &$a)
```

- May coerce underlying value
- may change stored type

---

### 10. Reference alias semantics

Not equivalent to PHP:

```php
$r =& $x[2];
```

- Full PHP aliasing not supported

---

### 11. Copy-on-write differences

PHP:
- copy-on-write arrays

Simple C++:
- standard C++ container behavior
- no zval semantics

---

### 12. Unsupported key types

Keys must be:
- int
- string

Other types:
- undefined / unsupported

---

## Safe Use Guidelines

Recommended:
- single-level array access
- int/string keys only
- no reliance on isset(null) behavior
- avoid negative/large keys
- avoid deep nested writes

---

## Summary

Simple C++ arrays are:

- suitable for structured data and basic usage
- not a full PHP semantic match

Expect differences in edge cases and advanced behaviors.
