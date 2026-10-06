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

## v0.2 bounded local aliases and reference returns

The `compiler/my-try` implementation accepts both inferred and explicitly typed
local bindings:

```php
$value int = 10;
$alias =& $value;
$typed_alias int =& $value;
$alias = 20;                 // writes value; does not rebind alias
$copy = $alias;              // normal value copy
```

Binding must introduce a new local. An existing variable cannot change from owning
storage to an alias, and an alias cannot be rebound. Explicit types must designate
the exact storage type (including the existing same-representation integer aliases);
binding never inserts a conversion temporary. Ordinary lexical scope/shadowing rules
apply, including rejecting a self-reference in an explicitly typed initializer.
Plain nested blocks may contain bindings; conditional/loop/switch regions may not.
An already established alias may be used and written within those regions.

The target must already exist and outlive the alias's lexical scope. Whole locals,
parameters, direct stable struct fields and alias chains are supported. Interior
container/dynamic slots and temporary-derived fields remain forbidden. The alias
does not extend its owner's lifetime. Aliasing a dynamic variable aliases its whole
tagged value: replacing through the alias replaces that variable, unlike copying a
dynamic value that shares a container payload.

Initial reference-returning functions require an explicit return type matching
exactly one fixed reference parameter; other by-value parameters are permitted.
Their only return statement must be the final unconditional statement of the
function body. It must select that whole parameter, directly or through local aliases:

```php
function &identity(int &$value): int {
    $alias =& $value;
    return $alias;
}
$value int = 10;
$borrowed =& identity($value);
$copy = identity($value);
```

Returning a local, by-value parameter, temporary or field/slot selection rejects
during preparation. Calls substitute the supplying argument's storage origin; a
reference-returning call does not extend its argument's lifetime. Capturing with
`=&` preserves the alias, whereas normal assignment copies according to the type's
ordinary copy contract. No implicit move or copyability exemption is introduced.

Nonrecursive reference-return forwarding is supported, directly or through aliases:

```php
function &forward(int &$value): int {
    return identity($value);
}
```

Each call substitutes its supplying argument's storage origin. The final returned
place must still be the enclosing function's whole reference parameter. A local may
be borrowed through a call for local use, but neither it nor an alias rooted in it
may escape through the return. Forward declarations and multi-hop chains follow the
same rule; no new lifetime is created by an intermediate call.

Recursive call components containing a reference-returning function are rejected
during preparation, including self-calls and cycles through by-value helpers. This
bounded restriction is structural: unused functions and calls in unreachable branches
are included. Ordinary by-value recursion remains supported. Recursive reference
returns, runtime-registered reference returns, const references and reference
foreach are separate work. The advisory warning policy below remains intended;
my-try's missing retained warning delivery is tracked as CH07-WARN-01 in its catalog.

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
