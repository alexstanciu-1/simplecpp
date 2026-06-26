# Array / Table Semantics in Prism++
Doc Status: normative
Status: Active

See `specs/spec_map.md` for document hierarchy, authority, and v1 conflict-resolution rules.
See also:
- `specs/dynamic_types.md`
- `specs/native_reference_safety.md`
- `specs/references.md`

## 1. Purpose

This document defines the current supported Prism++ array/table subset.

It is intentionally narrower than full PHP array semantics.
The goal is to stabilize:
- value reads
- write paths
- nested mutation
- append behavior
- missing-key behavior
- `isset` / `empty` / `unset` on array/table carriers
- the interaction between array reads and typed value destinations

This document does **not** expand the supported native-reference surface.
Reference safety remains governed by `specs/native_reference_safety.md` and `specs/references.md`.

## 2. Core model

Prism++ treats array/table operations through three semantic classes:

### 2.1 Value read path
Examples:
```php
$x = $a["k"];
echo $a["k"];
return $a["k"];
takeValue($a["k"]);
```

Rules:
- a value read does not create storage
- a value read does not autovivify
- a missing-key read produces `null`
- nested value reads also stay non-mutating

### 2.2 Write path
Examples:
```php
$a["k"] = 1;
$a["x"]["y"] = 1;
$a[] = $v;
```

Rules:
- a write path is allowed to create storage
- top-level missing-key write creates the key
- nested writes may autovivify missing intermediate table/hash nodes
- nested writes throw if an existing intermediate node has the wrong kind and cannot act as a table/hash carrier

### 2.3 Native-reference path
Examples:
```php
f($a["k"]);     // where f(int &$x)
$r =& $a["k"];
return $a["k"]; // by reference
```

Rules:
- array/property paths are not approved native by-reference binding targets in the current safe subset
- generated code may still call `.try_ref()` on the **resulting value** as a restricted runtime attempt
- `.try_ref()` succeeds only for `shared_p<T>` and returns a copy
- `.try_ref()` on all other element/value kinds fails by throwing
- `.try_ref()` does not provide slot write-back aliasing

This preserves the rule that Prism++ does not expose native references or pointers to dynamic interior storage owned by another object.

## 3. Missing-key behavior

### 3.1 Plain value read

```php
$x = $a["missing"];
```

Result:
- returns `null`
- does not create the key

### 3.2 Typed value destinations reached from array reads

Examples:
```php
$x int = $a["k"];
takeInt($a["k"]);
function f(): int { return $a["k"]; }
```

Normative rule:
- array read semantics do **not** get a special typed-context rule
- the array read first yields its normal value result (`null` on miss)
- then the ordinary typed value-boundary rules from `specs/dynamic_types.md` apply

So in typed value contexts:
- the same missing-key read still yields `null`
- conversion or failure is then determined by the ordinary typed boundary rules
- by-reference boundaries remain excluded from this model

## 4. `isset($a[k])`

### 4.1 Missing key

```php
isset($a["missing"])
```

Result:
- `false`

### 4.2 Existing key with `null`

```php
$a["k"] = null;
isset($a["k"])
```

Result:
- `false`

### 4.3 Existing key with non-null value

Result:
- `true`

Normative summary:
- `isset($a[k])` is null-sensitive in the current intended subset
- it must not collapse into pure key-existence semantics
- the normative cross-runtime contract now lives in `specs/count_empty_isset_contract.md`

## 5. `empty($a[k])`

### 5.1 Missing key

```php
empty($a["missing"])
```

Result:
- `true`

### 5.2 Existing key

`empty($a[k])` follows the same top-level runtime contract documented in `specs/count_empty_isset_contract.md`.

In particular:
- `null`, `false`, `0`, `0.0`, `""`, and empty array/table values are empty
- `"0"` remains the one deliberate Prism++ exception and is not empty
- unsupported/nonsensical type families must runtime-error rather than inventing new keyed-emptiness behavior

## 6. Top-level keyed write

```php
$a["k"] = 1;
```

Result:
- creates the key if missing
- overwrites the key if already present

## 7. Nested write

### 7.1 Missing intermediate

```php
$a["x"]["y"] = 1;
```

If `x` is missing:
- autovivify `x` as a table/hash carrier
- then continue the write

### 7.2 Wrong intermediate kind

If `x` already exists but is not table/hash compatible:
- throw
- do not silently overwrite the existing scalar/object/value into a table/hash

This yields the current nested-write rule:
- missing intermediate â†’ autovivify
- wrong existing intermediate kind â†’ throw

## 8. Read-path side effects

Pure reads must not create storage.

Examples:
```php
$x = $a["k"];
return $a["k"];
takeValue($a["k"]);
```

Rule:
- no pure read path may materialize a missing key or nested structure

## 9. Append semantics

### 9.1 Supported target kinds

```php
$a[] = $v;
```

Append is supported on array/hash-compatible carriers.

### 9.2 Null-state `mixed` bootstrap

If the target is `mixed` and currently in the null state:
- append is allowed
- the target autovivifies into an array/table carrier

## 10. `fixed_array<T, N>` first-slice semantics

`fixed_array<T, N>` is the source-facing fixed-size sequential container.

Current rules:
- `N` must be a non-negative integer literal in the type expression
- array literals assigned or returned into `fixed_array<T, N>` are positional only
- literal element count must exactly match `N`
- indexed reads and writes are supported
- `foreach` iterates in index order with `int` keys
- `count(...)` returns `N`
- `empty(...)` is true only when `N` is zero
- append, unset, resize, keyed literals, and mixed/dynamic fixed-array conversion are not part of the first slice

### 9.3 Other unsupported append targets

If the target is not array/hash compatible and is not the null-state `mixed` bootstrap case:
- throw or reject according to the lowering/runtime boundary involved

## 10. `unset($a[k])`

### 10.1 Missing key

```php
unset($a["missing"]);
```

Result:
- no-op

### 10.2 Nested unset with missing parent

```php
unset($a["x"]["y"]);
```

If `x` is missing:
- no-op

### 10.3 Existing key

If the key exists:
- remove that key
- preserve the visible identity of all remaining keys

## 11. Interaction with reference safety

This array/table subset does not widen the supported reference model.

Normative rules:
- array/property paths are still outside the supported native by-reference subset
- by-reference normalization from `mixed_t` to native `T&` remains unsupported
- generated code may use `.try_ref()` only as the restricted handle-copy escape hatch already documented elsewhere

Important distinction:
- shared pointee mutation visibility through `shared_p<T>` may still be observable
- PHP slot aliasing, slot rebinding, and slot write-back are **not** provided by that model

## 12. Supported vs unsupported summary

### Supported
- value read of existing key
- value read of missing key â†’ `null`
- top-level keyed write
- nested keyed write with autovivification of missing intermediates
- append on array/hash carriers
- append on `mixed(kind=null)` with autovivification
- `isset($a[k])` with null-sensitive semantics
- `empty($a[k])` under the PHP-aligned Prism++ emptiness rule above, with the documented `"0"` exception
- `unset($a[k])` as no-op on missing keys and removal on existing keys
- typed value destinations fed by array reads through the ordinary typed-boundary rules

### Outside the supported subset / rejected
- any array/property path as a direct native by-reference binding target
- any rule that would require exposing native interior references or pointers from dynamic storage
- nested write that silently overwrites a wrong-kind existing intermediate into a table/hash
- any special typed-context read rule that changes missing-key semantics only because the destination is typed

## 13. Wording convention

For this topic:
- model/spec boundary statements should use **outside the supported subset** when describing unsupported semantics
- generator/runtime failure behavior may use **rejected** or **throws** when describing the concrete implementation outcome
