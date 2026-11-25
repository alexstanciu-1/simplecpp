# Native reference safety

Doc Status: normative

A reference binds eligible stable storage of the declared type. Passing by writable reference requires writable storage. A readonly reference permits observation for the borrow's lifetime. Reference binding preserves storage identity, nullability and ownership. Replacing the value in a stable slot leaves references bound to that slot.

A return by reference requires a proven origin that survives the call. Locals, temporaries and interior collection slots that can relocate or expire cannot escape as references. Passing a whole vector or hash by reference borrows the caller's named slot for the call. Access through a dynamic key does not prove stable storage. Closure captures by reference must respect the originating storage's lifetime and the callback's escape boundary.

A PHP writable loop can lend its selected slot to a direct synchronous free function under the [scoped element borrow contract](collection_traversal.md#php-writable-vector-and-hash-foreach). The parameter must already support mutable references for that type.

See [collection borrows](../compiler/docs/collection_references.md), [object and ownership boundaries](type_use_modifiers.md) and [callable reference-return checks](../compiler/tests/closure_reference_returns_native.py).

## Reference parameters and defaults

A readonly reference borrows exact-type stable storage without copying. A default on an admitted reference parameter supplies call-local storage when the argument is omitted. A writable default is fresh for each call. A return may not escape storage created for a default. Explicit arguments retain the ordinary binding rules.

In PHP, synchronous instance and static methods of classes and structs can
borrow a named vector or hash using `const T &$view`. The caller supplies an
exact-type local or parameter. The method may read it, take a read-only lexical
alias, and forward it to another read-only parameter. A write through a separate
mutable parameter bound to the same slot is visible through the view. Virtual
overrides and interface implementations must preserve both the canonical collection
type and the read-only reference mode. Dispatch preserves the caller’s slot.
[Method and signature-edit tests](../compiler/tests/readonly_collection_methods_native.py).

## Constructor collection borrows

A PHP class or struct constructor may declare a required fixed parameter such as
`const vector<int> &$items` or `const hash<int,string> &$items`. The ordinary
binder requires an exact-type named local or parameter. Inline construction,
allocation and parent constructor forwarding borrow that slot for the call.
Named argument staging preserves the binding when a later argument changes the
collection. Assigning the collection to a field gives the field an independent
collection value. Writes through the read-only view and writable reference
constructor declarations are rejected. [Native and recovery tests](../compiler/tests/readonly_constructors_native.py).

## Read-only collection inputs on PHP callables

A closure declares a collection borrow as `const vector<int> &$view` or
`const hash<int,string> &$view`. In a context with an expected signature it
can write `const &$view` and infer the collection type. The passing mode must
match explicitly. For example, `callback<int,const vector<int>&>` accepts a
capture-free target returning an integer. An owned callable with the same input
has type `function<int(const vector<int>&)>` and may own by-value captures.

Both forms bind an exact-type named slot for the duration of each invocation.
They support forwarding the borrow and observing a mutation through another
argument bound to the same slot. Value, mutable-reference, and read-only-reference
inputs identify distinct callable types, including in typed collections.
Only a writable input can supply a reference result.
[Callable borrow and recovery tests](../compiler/tests/readonly_callable_inputs_native.py).

## Same-origin returns

Every returning path must identify the same surviving reference parameter. A local alias may name that storage. Returning the alias does not extend a local variable's lifetime. Follow the source and callable-specific preparation checks for admitted forms.
