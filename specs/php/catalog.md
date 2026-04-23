# Simple C++ for PHP â€” Language Catalog
Doc Status: normative
Status: Draft / AI anchor
Scope: PHP authoring surface for Simple C++

---

## 1. Purpose

This document defines the **PHP-facing language surface** supported and expected by Simple C++.

It is:
- normative for how PHP should be written
- short and enforceable
- aligned with runtime semantics

---

## 2. Interpretation Rules

- **Stable** â€” preferred and safe
- **Transitional** â€” allowed but not ideal
- **Partial** â€” limited support
- **Unsupported** â€” do not use

---

## 3. Core Model

### PHP as Authoring Language â€” Stable

Contract:
- PHP is the primary input language
- Code must be written with explicit runtime behavior in mind
- Generated C++ is not the authoring surface

---

## 4. Variables

### Assignment â€” Stable

```php
$x = 10;
$name = "Alex";
```

Contract:
- Variables map to runtime-backed values
- Type stability is preferred

---

## 5. Functions

### Definition â€” Stable

```php
function add(int $a, int $b): int {
	return $a + $b;
}
```

Contract:
- Types are enforced at boundaries
- Return types must match declared types

---

## 6. Control Flow

### if / else â€” Stable

```php
if ($x > 0) {
	// ...
}
```

Contract:
- Conditions should be explicit when boolean intent matters
- String conditions follow PHP-style truthiness (`""` and `"0"` are false; other strings are true)
- Typed/explicit bool normalization is narrower than condition truthiness
- Normalize mixed/dynamic values before control-flow use when the carried kind is not already clear

---

### foreach â€” Stable

```php
foreach ($data as $item) {
	// ...
}
```

Contract:
- Iteration over arrays/dynamic structures is supported

---

## 7. Arrays

### Mixed arrays â€” Stable

```php
$data = [];
$data[] = 1;
$data['name'] = "Alex";
```

Contract:
- Mixed keys supported
- Maps to dynamic runtime structure

---

## 8. Null / False

### Distinction â€” Stable

```php
if ($value === null) {}
if ($value === false) {}
```

Contract:
- Must not be merged
- Must be handled explicitly

---

## 9. Comparisons

### Strict comparison â€” Preferred

```php
if ($a === $b) {}
```

Contract:
- Always prefer strict comparison
- Loose comparison is Transitional

---

## 10. Functions (Core usage)

### DB query pattern â€” Stable

```php
$res = $db->query("SELECT id FROM users");

if ($res === false) {
	return;
}
```

Contract:
- Failure must be explicitly handled

---

## 11. Objects

### Method calls â€” Stable

```php
$row = $res->fetch_assoc();
```

Contract:
- Method calls must be explicit
- Returned values must be validated

---

## 12. Unsupported / Constrained

### Exceptions â€” Unsupported

Contract:
- No implicit exception flow

### References (&) â€” Partial

Contract:
- Limited or avoided

### Dynamic variable variables â€” Unsupported

---

## 13. Preferred Style

- Use strict comparisons
- Check failure explicitly
- Separate null and false
- Avoid implicit truthiness, especially string-in-condition forms; `mixed` in condition context currently delegates only for runtime null/bool/int/float/string payloads
- Prefer predictable structures

---

## 14. AI Rules

- Do not generate PHP shortcuts that hide behavior
- Do not merge null and false
- Always show explicit failure handling
- Prefer clear, deterministic code

---

## 15. Canonical Example

```php
$res = $db->query("SELECT id FROM users");

if ($res === false) {
	return;
}

$row = $res->fetch_assoc();

if ($row === null) {
	return;
}

echo $row['id'];
```

---

## 16. Change Policy

- Any change must align with runtime catalog
- New constructs must define behavior explicitly
