# v0.2 destructors and automatic cleanup
Doc Status: normative

The [concrete inheritance extension](inheritance_v0_2.md) specifies child/base
subobject cleanup order and virtual destruction for virtual/interface contracts.
Ordinary nonpolymorphic hierarchies do not acquire virtual destruction. Its shared
base/interface handle rules supersede the original non-inheriting scope where applicable.

Classes and structs share the bounded authored destructor form:

```php
public function __destruct() {
    // Cleanup while this object's properties still exist.
}
```

Omitted visibility is public. Destructors require instance access, zero parameters,
no declared return type and no reference return. Static, duplicate and value-returning
destructors reject in preparation. Public, protected and private declarations are
accepted subject to the cleanup-access rules below.
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
is not given a stronger guarantee by this slice. Inheritance and virtual destruction
follow the inheritance contract.

## Destruction access

Destructor visibility remains meaningful; automatic ownership helpers gain no
privileged access and this slice introduces no custom deleters.

- A protected base destructor is accessible to descendant subobject cleanup. An
  implicit child destructor is public; it does not inherit the base's visibility.
- A private base destructor cannot be invoked by descendant cleanup; this unsupported
  hierarchy is rejected during preparation, not deferred to C++ diagnostics.
- Shared allocation requires a public destructor on the actual allocated record,
  even inside a source factory. An existing shared view may name a base with a
  non-public destructor: its original control block retains concrete-object cleanup.
  Shared copying, weak observation/locking and checked casts do not allocate a new object.
- Unique storage requires a public payload destructor, including empty storage.
  Unique ancestry transfer additionally retains its virtual-destruction requirement.
- Ordinary inline struct cleanup checks lexical access. Explicit `value<Class>`
  storage and runtime-owned container/nullable payload cleanup require public access,
  because their runtime wrappers are not members or friends of the payload.

The same checks apply to ordinary `new`, keyed `data` construction, fields, locals
and value-call boundaries. A private constructor accessible to a factory does not
grant the allocation helper access to a private destructor. Explicit destructor calls
remain forbidden for every visibility. Nonthrowing behavior and cleanup order are unchanged.

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
