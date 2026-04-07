# References in Prism++

## Overview

Prism++ uses a simplified and predictable reference model.

References are:
- single-binding
- stable (never rebind)
- equivalent to native C++ references for explicit local / property aliases

## Advantages

- Deterministic behavior
- No hidden alias switching
- Safe mapping to C++ (no pointer-to-slot model)
- Better performance (no indirection layer)
- Easier reasoning and debugging

## Supported behavior

### 1. Single binding
```php
$x =& $a;
$x = 10;
```

### 2. Function reference (no rebinding)
```php
function &id(&$x) { return $x; }
$b =& id($a);
```

### 3. Property reference (no rebinding)
```php
$x =& $obj->v;
```

### 4. Static alias chains
```php
$a = 1;
$b =& $a;
$c =& $b;
```

## Unsupported behavior

### 1. Reference rebinding
```php
$x =& $a;
$x =& $b;
```

### 2. Property rebinding
```php
$x =& $a->v;
$x =& $b->v;
```

### 3. Array rebinding
```php
$x =& $arr[0];
$x =& $arr[1];
```

### 4. Conditional binding
```php
if ($c) { $x =& $a; } else { $x =& $b; }
```

### 5. Rebinding through chains
```php
$c =& $b;
$c =& $other;
```

### 6. Conditional or loop-scoped reference binding
```php
if ($cond) {
    $x =& $a;
}

while ($cond) {
    $y =& $b;
}
```

### 7. By-reference returns of array/property slots
```php
function &get_inner(array &$arr): array {
    return $arr["inner"];
}
```

### 8. By-reference returns with multiple return statements
```php
function &pick(bool $cond, array &$a, array &$b): array {
    if ($cond) {
        return $a;
    }
    return $b;
}
```

## Rule

A variable may be bound by reference (&) at most once.

Violations may result in compile-time errors.


## Historical note - typed scalar by-reference proxy parameters (deprecated / no longer generated)

The runtime still contains the scalar proxy helper types:

- `int_ref`
- `float_ref`
- `bool_ref`
- `string_ref`

The S2S generator no longer lowers typed scalar by-reference parameters through these proxy views.
Current rule:

- **Normative rule — native-equivalent by-reference parameter normalization:** all native-equivalent typed by-reference parameters are normalized through template dispatch
- in the current supported set, `int&`, `float&`, `bool&`, and `string&` accept the semantic domain `(T|mixed)&`
- native `T&` binds directly
- `mixed_t&` is accepted only through the normalized template path and must be runtime-validated before user code runs
- on a matching runtime kind, the callee normalizes through the exact `as_*_ref()` accessor and then operates on the native `T&` view
- on a non-matching runtime kind, normalization fails with a runtime error; no value-conversion fallback is allowed on the by-reference boundary
- typed scalar by-reference parameters must no longer rely on generator-emitted proxy adaptation, sibling `mixed_t&` bridge overloads, or implicit typed reference casts on `mixed_t`

`mixed_t` must not expose implicit typed reference casts. In particular, `operator int_t&()`, `operator float_t&()`, `operator bool_t&()`, and `operator string_t&()` must not exist.

Additional conservative generator rejects in the current subset:
- reference binding inside `if` / `switch` / loops is rejected
- by-reference functions / methods may use only one `return` statement
- by-reference returns are allowed only for stable aliasable expressions rooted in a by-reference parameter, `$this`, or another reference derived from stable storage
- allowed stable chains include direct or indirect array/property access rooted in those stable bases, for example `$a[0]`, `$a[0]["k"]`, `$this->x`, and `$this->child->id`
- returning a local alias variable is allowed only when that alias was created via `=&` from another stable root
- rejected roots include by-value parameters, plain locals, temporaries, computed values, call-rooted storage expressions, and any path that would require value normalization/coercion before returning by reference

## Return-by-reference warnings

- Return-by-reference is not recommended in Prism++ and must always surface a generator warning even when generation is still allowed.
- The generator must also warn for local copy-after-alias patterns rooted in a by-reference call result, for example `$inner =& get_inner($arr); $copy = $arr;`, because Prism++ may not preserve PHP alias semantics for that flow.


For mixed/native by-reference boundaries and the current runtime-validated template normalization rule, see `specs/dynamic_types.md`.
