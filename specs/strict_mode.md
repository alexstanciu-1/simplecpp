# Strict Mode (Guidance Profile)
Doc Status: normative
## Status

Strict mode is a **user-facing coding-guidance profile**.

In the current version:
- it is **documentation only**
- it is **not enforced** by the language, generator, or runtime

Future major versions may introduce enforcement based on this profile.

---

## Goals

Strict mode aims to:

- reduce propagation of `mixed_t`
- encourage early resolution of `nullable<T>` and `result*` wrappers
- minimize implicit conversions
- improve clarity and predictability of operator behavior

---

## Core Principles

### 1. Stabilize dynamic values early

When working with `mixed_t`, prefer resolving to a concrete type early.

If the left side already provides a stable explicit destination type, that destination is explicit enough. A separate cast is usually unnecessary.

**Preferred:**
```php
$i int = $arr[10];
```

```php
$counts hash<int> = [];
$counts["id"] = $row["id"];
```

```php
$items vector<int> = [];
$items[] = $row["count"];
```

**Discouraged:**
```php
$i = $arr[10];
```

---

### 2. Unpack wrappers near meaningful boundaries

For:
- `nullable<T>`
- `result`
- `result_or_bool`
- `result_or_false`

Prefer early unpacking (e.g. via `take(...)`) unless the wrapper itself is required for subsequent logic.

---

### 3. Typed destination is explicit enough

Typed destinations are preferred over explicit casts when possible.

**Preferred:**
```php
$i int = $arr[10];
```

```php
$obj->name = $row["name"]; // property is string
```

```php
$counts["key"] = $row["count"]; // counts is hash<int>
```

**Less preferred:**
```php
$i int = (int) $arr[10];
```

The same rule applies when the typed destination is provided by a stable container or property path rather than by a standalone local.

---

### 4. Avoid implicit conversions

Avoid relying on implicit extraction from:
- `mixed_t`
- `nullable<T>`
- `result*`

Normalize values at explicit typed destinations before:
- storage
- return
- branching
- reuse

If a stable explicit destination already exists on the left side, that destination counts as the normalization point.

Examples:

```php
$name string = $row["name"];
```

```php
$obj->count = $row["count"]; // property is int
```

```php
$items[] = $row["count"]; // items is vector<int>
```

```php
$extra["title"] = $row["title"]; // extra is hash<mixed>; stays mixed
```

---

### 5. Avoid dynamic and wrapper values in conditions

Avoid using unresolved values in:

- `if`
- `while`
- logical operators (`&&`, `||`)
- ternary (`?:`)

This is guidance, not a claim that such conditions are always invalid.
String conditions use PHP-style truthiness in the language/runtime, while typed/explicit `bool` normalization is narrower and may reject ambiguous string literals.

**Discouraged:**
```php
if ($mixed) { ... }
```

**Preferred:**
```php
$ok /* bool */ = $mixed;
if ($ok) { ... }
```

---

### 6. Operator guidance

Prefer concrete operands for operators.

#### Arithmetic

**Discouraged:**
```php
$sum = $int + $mixed;
```

**Preferred:**
```php
$right /* int */ = $mixed;
$sum = $int + $right;
```

#### Comparison / Equality

Avoid comparing concrete values with unresolved `mixed_t` when possible.

#### Boolean evaluation

Resolve operands before truthiness-based operations.

#### Null/defaulting (`??`)

Use directly when it represents the intended semantic boundary.
Otherwise, normalize earlier.

---

### 7. Avoid `auto` for dynamic or wrapper values

Avoid `auto` when it preserves:
- `mixed_t`
- `nullable<T>`
- `result*`

---

## Exceptions

The following are tolerated:

- Passing unresolved values directly to typed functions when the contract is clear
- Preserving wrappers when their semantic meaning is required
- Using `??` as a deliberate boundary for defaulting logic

---

## Summary

Strict mode favors:

- early type stabilization
- early wrapper resolution
- minimal propagation of dynamic state
- explicit operator semantics

It is a guidance layer designed to improve clarity and long-term correctness without changing language behavior.
