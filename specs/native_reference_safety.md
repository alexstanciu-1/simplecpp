# Native Reference Safety in Prism++
Doc Status: normative
Status: Active

See `specs/spec_map.md` for document hierarchy, authority, and v1 conflict-resolution rules.

## Core rule

> No API may expose a native C++ reference or pointer to heap-backed interior storage whose lifetime or stability is owned by another object.

This rule is intentionally broad. It applies to the generated C++ surface, the runtime surface, and any source-language construct whose lowering would require such exposure.

## Terminology

### Native reference
A native C++ reference such as `T&`.

### Native pointer
A native C++ pointer such as `T*`.

### Heap-backed interior storage
Storage that lives inside a heap-owning wrapper or runtime-managed dynamic object and whose validity depends on that owner remaining alive and stable.

Examples include:
- vector element storage
- hash/table bucket or slot storage
- dynamic object property storage
- future string character storage
- any borrowed view into such storage

### Native-reference bindable
An expression is native-reference bindable only if Prism++ can lower it to a native C++ reference without violating the core rule above.

### Copy-stable handle-like type
A type whose copied value remains valid after the source container slot moves, is rebuilt, or dies, because the copied value owns or shares its own stable handle semantics.

In the current runtime, only `shared_p<T>` is approved as a copy-stable handle-like type for `try_ref(...)`.

## Allowed native-reference sources

Native references are allowed only for directly stable objects, for example:
- local variables
- function or method parameters
- direct stable fields
- whole-object wrappers/handles such as `string_t`, `vector_t`, and `shared_p<T>` when the reference is to the wrapper or handle object itself

Examples:

```cpp
int_t value = static_cast<int_t>(10);
int_t& value_ref = value;

vector_t<int_t> items;
auto& items_ref = items;

shared_p<Box> box;
auto& box_ref = box;
```

## Forbidden native-reference sources

Expressions rooted in dynamic interior access are not native-reference bindable.

This includes any source-language or runtime path that would expose:
- `T&`
- `T*`
- iterators, views, spans, or proxies that are equivalent to escaping native interior access
- any API implicitly convertible to a native reference or pointer into interior storage

Examples of forbidden shapes include:
- `vector_t<T>::operator[]` returning `T&`
- `vector_t<T>::at()` returning `T&`
- `hash_t<T>::operator[]` returning `T&`
- `mixed_t[...]` returning `mixed_t&`
- future `string_t[i]` returning a native character reference or pointer
- any slot getter returning `T&` or `T*`

At the source-language level, this means constructs such as the following are not supported in the current safe subset when they would require native interior access after lowering:

```php
$a =& $arr[10];
$a =& $my_vector[10];
$a =& $my_string[10];
```

## Dynamic data consequence

Dynamic data should be modeled as fat values and handles, not native references to their interior slots.

For example, if JSON objects are represented as dynamic object values carried by `mixed_t`, then expressions such as:

```cpp
auto& ref = data["user"];
```

remain forbidden even when the addressed slot currently stores a `shared_p<T>`, because the reference would still bind to interior storage owned by `data`.

Copying the value out first remains valid:

```cpp
auto user = data["user"];
auto& user_ref = user;
```

## `try_ref(...)` escape hatch

`try_ref(...)` is a restricted escape hatch.

It may return a copy of the addressed element only when that element is a copy-stable handle-like type. In the current runtime:

- `try_ref(...)` succeeds only for `shared_p<T>`
- `try_ref(...)` returns a copy of that `shared_p<T>`
- all other element types fail by throwing

This is allowed because the copied `shared_p<T>` no longer depends on the continued stability of the original container slot.

Example:

```cpp
vector_t<shared_p<Box>> boxes;
auto box = boxes.try_ref(static_cast<int_t>(10));
```

## `try_ref(...)` alias semantics

`try_ref(...)` provides memory/lifetime safety, not slot write-back aliasing.

If the returned copied `shared_p<T>` still points to the same shared pointee/object, mutating that pointee remains observable through other holders of that same shared object.

However, rebinding or reassigning the returned local handle does not update the original container slot.

Example:

```cpp
auto user = users.try_ref(static_cast<int_t>(10));
user->name = string_t("Alex");   // shared pointee mutation: observable elsewhere
user = make_shared<User>();       // local handle rebind: does not update users[10]
```

## Current runtime restrictions implied by this rule

The current safe subset should treat the following as forbidden or throwing paths:
- `mixed_t::as_*_ref()` typed native-reference bridges
- any by-reference lowering rooted in `[]`
- any by-reference lowering rooted in dynamic property or slot access
- any API that exports a native reference or pointer to interior dynamic storage

## Future direction

This is a conservative prototype rule.

The system may become more permissive in the future by recognizing additional memory-safe cases through:
- compile-time analysis
- runtime borrow counting
- stable-slot or stable-cell indirection
- a hybrid model

Any future relaxation must still preserve the core rule for truly unsafe native interior access.
