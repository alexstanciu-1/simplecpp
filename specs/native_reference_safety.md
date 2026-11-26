# Native reference safety

Doc Status: normative

A reference binds eligible stable storage of the declared type. Passing by writable reference requires writable storage. A readonly reference permits observation for the borrow's lifetime. Reference binding preserves storage identity, nullability and ownership. Replacing the value in a stable slot leaves references bound to that slot.

A return by reference requires a proven origin that survives the call. Locals, temporaries and interior collection slots that can relocate or expire cannot escape as references. Passing a whole vector or hash by reference borrows the caller's named slot for the call. Access through a dynamic key does not prove stable storage. Closure captures by reference must respect the originating storage's lifetime and the callback's escape boundary.

A PHP writable loop can lend its selected slot to a direct synchronous free function under the [scoped element borrow contract](collection_traversal.md#php-writable-vector-and-hash-foreach). The parameter must already support the declared reference mode for that type.

See [collection borrows](../compiler/docs/collection_references.md), [object and ownership boundaries](type_use_modifiers.md) and [callable reference-return checks](../compiler/tests/closure_reference_returns_native.py).

## Private collection copies at calls

A by-value collection parameter has independent snapshot semantics. The compiler may omit
the allocation of its private copy when it proves that failure to allocate that copy is
the only possible observable difference. The proof covers argument evaluation, aliasing,
storage lifetime, and the copy and destruction effects of keys and values. Authored
destructors and user-code failures must remain observable. Allocation failures from
operations that still execute propagate under their ordinary contracts. An unknown
effect requires a private copy.

Typed vectors and hashes publish a read-only indexed lookup capability. Once the receiver and key have been evaluated, lookup preserves the collection, including its size and key set. Collections with the same snapshot contents yield the corresponding stored value or the same checked failure. Preparation selects the indexed operation from the receiver type. A borrowing proof checks its lookup capability separately from operand evaluation, copying the result, and destruction. For example, evaluating the key in `$snapshot[changeKey($writer)]` can still mutate the source through an alias.

## Private local collection snapshots

A local initialized by value from a vector or hash observes the contents at initialization through its last use, including when another alias changes the source. The compiler may omit the local’s private allocation under the same copy, destruction and failure rules as a by-value collection parameter. The source must stay alive and the compiler must prove that all uses observe the snapshot.

A synchronous function can end with an ordinary local initialized from an exact-type named parameter followed immediately by a scalar read of that local as its return value. Earlier statements can write through aliases: the snapshot observes the source when the declaration executes. Lowering inspects at most the final three statements in the top-level function body. Between the snapshot and the return, one ordinary scalar local may save a first read with identity conversion and effect-free copy and destruction. The return may read the snapshot again directly or add the saved scalar to the second read with identity operand conversions. Count, emptiness, checked indexing and key presence use the prepared reader rules. An inert literal or an ordinary by-value scalar parameter may supply the key. An intervening write, a call to compute the key, or an observable element destructor requires the ordinary copy. Checked operations retain their failures at the read.

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
