# Native reference safety

Doc Status: normative

A reference binds eligible stable storage of the declared type. Passing by writable reference requires writable storage. A readonly reference permits observation for the borrow's lifetime. Reference binding preserves storage identity, nullability and ownership. Replacing the value in a stable slot leaves references bound to that slot.

A return by reference requires a proven origin that survives the call. Locals, temporaries and interior collection slots that can relocate or expire cannot escape as references. Passing a whole vector or hash by reference borrows the caller's named slot for the call. Access through a dynamic key does not prove stable storage. Closure captures by reference must respect the originating storage's lifetime and the callback's escape boundary.

See [collection borrows](../compiler/docs/collection_references.md), [object and ownership boundaries](type_use_modifiers.md) and [callable reference-return checks](../compiler/tests/closure_reference_returns_native.py).

## Reference parameters and defaults

A readonly reference borrows exact-type stable storage without copying. A default on an admitted reference parameter supplies call-local storage when the argument is omitted. A writable default is fresh for each call. A return may not escape storage created for a default. Explicit arguments retain the ordinary binding rules.

## Same-origin returns

Every returning path must identify the same surviving reference parameter. A local alias may name that storage. Returning the alias does not extend a local variable's lifetime. Follow the source and callable-specific preparation checks for admitted forms.
