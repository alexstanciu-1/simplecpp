# References in Prism++
Doc Status: supporting
See `specs/spec_map.md` for document hierarchy, authority, and v1 conflict-resolution rules.
See also `specs/native_reference_safety.md` for the normative native-reference safety boundary.

## Overview

Prism++ uses a conservative and predictable reference model.

References are:
- explicit
- single-binding
- conservative by design
- allowed only when the source storage is directly stable under the current safe subset

The current goal is not to emulate full PHP reference behavior. The goal is to preserve a safe subset that maps cleanly to generated C++ without exposing unsafe native references or pointers to dynamic interior storage.

## Core rules

### 1. Single binding
A variable may be bound by reference (`&`) at most once.

### 2. No native references to dynamic interior storage
Any source-language construct whose lowering would require a native C++ reference or pointer to heap-backed interior storage is rejected in the current safe subset.

This includes array/vector/string element-style interior reference binding and similar dynamic slot/property paths.

### 3. Explicit syntax only
Prism++ never infers native reference semantics. A native reference exists only when explicit source syntax is accepted by the current rules and can be lowered safely.

## Supported behavior

### 1. Stable local alias
```php
$a = 1;
$b =& $a;
```

### 2. Stable alias chains without rebinding
```php
$a = 1;
$b =& $a;
$c =& $b;
```

### 3. Reference parameter / return syntax, when the referenced storage is directly stable and the declaration is otherwise valid
```php
function f(int &$a): void {}
function &id(int &$x): int { return $x; }
```

## Unsupported behavior

### 1. Reference rebinding
```php
$x =& $a;
$x =& $b;
```

### 2. Conditional binding
```php
if ($c) { $x =& $a; } else { $x =& $b; }
```

### 3. Conditional or loop-scoped reference binding
```php
if ($cond) {
    $x =& $a;
}

while ($cond) {
    $y =& $b;
}
```

### 4. Array / vector / string slot binding
```php
$x =& $arr[0];
$x =& $vec[0];
$x =& $str[0];
```

### 5. Property / slot binding rooted in dynamic storage
```php
$x =& $obj->items[0];
$x =& $data["user"];
```

### 6. By-reference returns of array/property/slot chains
```php
function &get_inner(array &$arr): array {
    return $arr["inner"];
}
```

### 7. By-reference returns with multiple return statements
```php
function &pick(bool $cond, int &$a, int &$b): int {
    if ($cond) {
        return $a;
    }
    return $b;
}
```

## Current safe subset guidance

### Allowed native-reference sources
Native references are currently intended only for directly stable objects such as:
- locals
- parameters
- direct stable fields
- whole-object wrappers/handles such as `string_t`, `vector_t`, and `shared_p<T>` when the reference is to the wrapper/handle object itself

### Forbidden native-reference sources
The following are not native-reference bindable in the current safe subset:
- any `[]`-rooted expression
- dynamic property / slot access rooted in dynamic storage
- any path that would require `mixed_t::as_*_ref()`
- any path that would require exposing a native reference or pointer to interior dynamic storage

## Runtime note

The current safe subset treats typed native-reference extraction from `mixed_t` as disabled runtime surface. The legacy `.as_*_ref()` helpers may remain present temporarily for transition purposes, but they are not part of the supported safe reference model.

## `try_ref(...)`

For the only approved copy-stable handle-like escape hatch in the current runtime, see `specs/native_reference_safety.md`.

In short:
- generated code may call `try_ref(...)` on the resulting value as a restricted runtime attempt
- array/property paths themselves are not approved native by-reference targets
- `try_ref(...)` currently has value only for `shared_p<T>`
- it succeeds only for `shared_p<T>`
- it returns a copy
- it does not provide slot write-back aliasing

## Return-by-reference warnings

- Return-by-reference is not recommended in Prism++ and should surface a generator warning even when generation is still allowed.
- Reference-returning flows remain deliberately narrower than PHP and should be treated as a conservative subset feature.
