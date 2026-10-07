# v0.2 destructors and automatic cleanup
Doc Status: normative

The [concrete inheritance extension](inheritance_v0_2.md) specifies child/base
subobject cleanup order. It does not enable polymorphic deletion or establish
virtual-destructor policy; the original non-inheriting scope below is historical
where that extension explicitly applies.

Classes and structs share the bounded authored destructor form:

```php
public function __destruct() {
    // Cleanup while this object's properties still exist.
}
```

Omitted visibility is public. The initial implementation requires public instance
access, zero parameters, no declared return type and no reference return. Static,
duplicate, private/protected and value-returning destructors reject in preparation.
The ordinary void-body contract allows fallthrough and bare `return;`. Explicit
source invocation (`$object->__destruct()`, `Type::__destruct()` or expression-qualified
static spelling) is rejected. Destruction is an ownership operation, not an ordinary
call that users may repeat.

## Ownership and ordering

Destruction follows storage representation, not the class/struct keyword:

- Inline objects are destroyed when their storage lifetime ends, including early
  return, break/continue and exception unwinding. Independently copied inline
  objects each have their own lifetime and destructor invocation.
- Shared objects are destroyed on release of their last owning handle. Copying a
  handle does not copy the object; weak handles do not keep it alive.
- Unique objects are destroyed when their owning handle releases them. Ownership
  transfer is not object destruction.
- Local native aliases do not own or extend the target's lifetime.

The authored destructor body runs before instance member destruction. `$this`
properties and ordinary instance methods are usable under their existing contracts;
copying/escaping `$this` is not newly enabled. Members are destroyed in reverse
declaration order after the body. Constructor failure does not invoke the destructor
body for the incompletely constructed object; already constructed members clean up
under normal C++ ownership rules.

Assignment to an existing inline object remains memberwise assignment, not an
implicit destructor/reconstruction of that whole object. Replacing an owning handle
can release its old object. Static-storage shutdown ordering across unrelated units
is not given a stronger guarantee by this slice. Inheritance, virtual destruction
and non-public destructor access policies await CH11.

## Nonthrowing boundary

Destructors are nonthrowing. An exception escaping the destructor body or member
cleanup terminates execution; it is not swallowed or returned as a recoverable
cleanup result. The compiler does not claim an effect analysis proving that every
body operation is incapable of failure. Fallible cleanup that needs error reporting
belongs in an ordinary explicit method.

## Shared implementation and copy/move preservation

Destructors use the shared method AST, declaration/body preparation, dependencies and
out-of-line implementation-unit emission. C++ declarations and definitions carry
`noexcept`; bodies are not placed in type headers.

An authored destructor must not accidentally suppress existing memberwise movement
or change semantic copy capability. The backend explicitly defaults memberwise
copy/move construction and assignment for these records, restoring default
construction where it was previously implicit. Noncopyable members still delete
copying, and source preparation continues to reject noncopyable copies. Moved-from
inline objects remain live until their own storage ends; their destructors must
tolerate the normal moved-from state. No user-authored copy/move special members or
new source transfer forms are introduced.
