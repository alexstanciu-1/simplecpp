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

## v0.2 block-local aliases — 2026-10-06

Native `=&` bindings may introduce locals inside conditional arms, loop bodies and
switch case-group bodies. Existing lexical scope and typed-shadowing rules apply;
there is no branch-merged alias or rebinding of an existing variable. Each loop-body
entry creates its own local binding. Fallthrough does not expose a preceding switch
group's alias. Body aliases are not visible in loop conditions or updates.

Targets must be already established stable storage: an outer local, an earlier
same-block local, a parameter, an eligible direct field or a supported reference-call
result. Aliases preserve source provenance and read-only access, and never extend
the target's lifetime. Leaving a scope (including break/continue) ends the alias,
not the target's lifetime. Existing container-interior and escaping-reference
rejections remain in force.

Loop-header alias declarations, reference foreach and broader reference-return
control flow remain outside this slice. An alias to an ordinary foreach value local
aliases that copied local, not the source container element.

## v0.2 explicit read-only reference parameters — 2026-10-06

`function read(const T &$value): R` borrows exact-type stable storage without
copying it. This is a semantic read-only access contract, separate from both
value/reference passing and any backend optimization of ordinary value parameters.
Mutable storage and existing read-only borrows may both supply the argument.
Temporary values, conversions and container-interior references remain rejected;
`const` does not relax the existing native-reference eligibility rules.

Reads, ordinary value copies (where the type is copyable), and forwarding to another
read-only reference parameter are allowed. Local `=&` aliases inherit read-only
access, including alias chains and direct stable field projections. Copying an
ordinary value does not make the new local's storage read-only.

Preparation rejects every supported write through that access path: assignment,
compound updates, increments/decrements, reset/cleanup, indexed writes/appends and
removal, explicit unique extraction, `take` outputs and reuse as a `foreach` output.
Passing the place or its aliases/projections to a mutable-reference parameter also
rejects. Runtime enforcement or a later C++ const error is not the validation owner.

Read-only access is not global immutability or exclusive borrowing. Another mutable
alias may change the same storage, and subsequent reads observe that change. Ordinary
copies retain their existing value/COW/shared-identity semantics; this does not clone
shared objects or introduce transitive read-only tracking through copied handles.
In particular, `const shared<T>&` does not mean `shared<const T>`. Future pointee
member/method operations must preserve the source access-path contract deliberately;
the C++ handle qualifier alone is not sufficient.

The bounded source slice admits fixed explicit reference parameters only. By-value
`const T $value`, const-reference returns, parameter defaults, variadics, temporary
binding, methods/closures and broader const type/local syntax remain deferred. The
read-only flag is part of the callable contract; changing it invalidates dependent
callers and the affected body. C++ signatures, borrowed argument temporaries and
local native aliases preserve `const T&` without taking an owning snapshot.

## v0.2 whole-storage reset and aliases — 2026-10-06

`unset($variable)` and `clean($variable)` are storage resets, never symbol deletion,
reference detachment or destruction of the variable's storage. The name remains
usable and native aliases to that storage remain bound.

- Whole-variable `unset` resets nullable storage or a shared/unique/weak handle to its absent state. Applying it
  directly to a native alias or reference parameter is rejected, not interpreted
  as PHP-style alias detachment. Resetting the original nullable owner while aliases
  exist is valid; all those aliases observe absence and can later assign a value.
- `clean` writes the defined default value through a local, parameter or native
  alias. Supported scalar defaults are false, numeric zero and empty string;
  typed vectors/hashes become empty. This permission does not follow merely from
  a type being constructible, indexable or storable.
- `clean` resets mixed and dynamic storage to their own null values. In particular,
  cleaning dynamic storage does **not** clear the old shared container in place.
  Other copied dynamic bindings retain that object; native aliases to the reset
  variable instead observe null. No cross-family conversion is involved.
- `clean` is not the nullable/ownership-handle reset spelling; use `unset` on the owner or ordinary
  null assignment through an alias/reference parameter. Result wrappers and arbitrary
  structs do not acquire a cleanup contract from this rule.
- Keyed `unset($container[$key])` is a separate removal operation governed by
  [array semantics](array_semantics.md#10-unsetak). Its existing lazy/no-creation rules
  remain unchanged, including when the root variable is a native alias.

The initial compiler slice accepts a single variable operand as a statement.
Direct property resets, indexed `clean` and value-producing forms
await their own admission/model work. An already valid local alias to a
stable field can be cleaned according to its selected storage type. These limits
do not relax the prohibition on heap-interior references.

## v0.2 unique transfer and stable aliases

`move($source)` transfers ownership, not the variable's storage or its native aliases.
Aliases to that whole storage remain bound and observe the empty source after extraction.
An approved destination alias receives ownership through its existing storage binding.
Reference parameters likewise refer to caller-owned handle storage; this does not
require or introduce a Rust-style exclusive-borrow rule. The initial direct syntax
excludes field projections and locally known field aliases on either side, and does
not expose container-interior references. See the
[explicit-transfer contract](type_use_modifiers.md#v02-explicit-unique-transfer--2026-10-06).

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
