# Array Behavior Differences in Prism++ (PHP → Prism++)

This document outlines the current reduced Prism++ array/table subset and the remaining known differences from PHP.

This document is **non-authoritative**.
Authoritative sources:
- `../../specs/array_semantics.md`
- `../../specs/dynamic_types.md`
- `../../specs/native_reference_safety.md`

---

## 1. Current Subset (Authoritative-Aligned Summary)

The current intended Prism++ array/table model is:

- missing-key value read returns `null`
- pure reads do **not** create storage
- top-level keyed writes create missing keys
- nested writes autovivify missing intermediate table/hash nodes
- nested writes **throw** if an existing intermediate has the wrong kind
- append is allowed on:
	- array/hash carriers
	- `mixed(kind=null)` (bootstrap → creates array/table)
- `isset($a[k])` is null-sensitive:
	- missing → `false`
	- existing `null` → `false`
- `empty($a[k])` is **reduced**:
	- `true` only for:
		- `null`
		- `""`
		- empty array/table
- `unset($a[k])` is a **no-op** on missing keys
- array/property paths remain **outside the supported native by-reference subset**
- `.try_ref()`:
	- may be attempted on resulting values
	- succeeds **only for `shared_p<T>`**
	- returns a **copy of the handle**, not slot aliasing

---

## 2. Key Differences from PHP

### 2.1 Missing vs Null Collapse

```php
$x["missing"] == null
```

- Missing key and stored `null` are indistinguishable in value context
- This is **intentional in the reduced model**

---

### 2.2 `isset(...)` Behavior (Now Defined)

PHP:
- `isset($x["a"])` is false if value is null

Prism++:
- **same behavior now enforced**
	- missing → false
	- null → false

---

### 2.3 Reduced `empty(...)` Semantics

Prism++ does **not** implement full PHP falsiness.

Only considered empty:
- `null`
- `""`
- empty array/table

Not empty:
- `0`
- `0.0`
- `"0"`
- `false`

---

### 2.4 Key Coercion Differences

Not supported:
- numeric string → int conversion
- bool/null/float → key coercion

Only valid keys:
- exact `int`
- exact `string`

---

### 2.5 Negative / Large Integer Keys

- negative keys → not reliable
- large keys (beyond safe range) → not guaranteed

---

### 2.6 Append After Unset

PHP:
```php
unset($x[1]);
$x[] = 'c'; // usually key 2
```

Prism++:
- append uses internal max+1 logic
- may reuse earlier gaps differently

---

### 2.7 Nested Auto-Growth (Now Defined)

Prism++ supports controlled autovivification:

```php
$x["a"]["b"] = 1;
```

Behavior:
- missing `"a"` → created as table
- `"a"` exists but not table → **throw**

This is stricter than PHP and **intentional**.

---

### 2.8 Read vs Write Separation

- read paths:
	- never create storage
- write paths:
	- may create storage
	- may autovivify

This is a **core invariant** of the model.

---

### 2.9 By-Reference Behavior

Not equivalent to PHP:

```php
$r =& $x[2];
```

Prism++:
- array/property slots are **not valid native reference sources**
- no slot aliasing
- no rebinding semantics

`.try_ref()`:
- runtime attempt on resulting value
- only works for `shared_p<T>`
- does **not** expose container interior

---

### 2.10 Typed By-Reference Coercion

Not supported:

```php
function f(int &$x) {}
f($a["k"]);
```

- dynamic slot → native ref conversion is **outside the supported subset**

---

### 2.11 Copy-on-Write Differences

PHP:
- Zend zval copy-on-write

Prism++:
- shared storage + detach-on-write
- not full PHP semantics

---

### 2.12 Unsupported Key Types

Keys must be:
- `int`
- `string`

All others:
- unsupported / undefined

---

## 3. Historical / Transitional Notes (Preserved Context)

These behaviors may still appear in older code/tests but are no longer part of the supported model:

- `.as_*_ref()` accessors → removed from safe surface
- dynamic interior native references → forbidden
- implicit slot creation via read paths → no longer allowed
- by-ref coercion from `mixed_t` → removed

---

## 4. Safe Use Guidelines

Recommended:

- use only `int` / `string` keys
- treat missing reads as `null`
- rely on read/write separation
- use nested writes only where autovivification is intended
- never rely on PHP alias semantics
- use `.try_ref()` only for `shared_p<T>` scenarios

Avoid:

- deep alias chains
- mixed-type key usage
- relying on PHP falsy semantics
- assuming slot identity stability

---

## 5. Summary

Prism++ arrays/tables are now:

- **semantically consistent**
- **memory-safe**
- **intentionally reduced**

They are **not a full PHP match**, but they are now a **stable, well-defined subset suitable for further language expansion**.
