# v0.2 instance method calls
Doc Status: normative

This records the bounded instance-method contract approved on 2026-10-07. Classes
and structs share declaration, parameter, return and visibility semantics; their
storage/copy differences remain unchanged. Availability is tracked in the
[CH10 checkpoint](../compiler/my-try/docs/catalog/10_objects.md#instance-methods--2026-10-07).

## Fixed instance calls

`$object->method(arguments)` and `$this->method(arguments)` select a fixed method
name on the receiver's declared record type. Public methods are externally callable;
private methods are callable only from the declaring record's instance context,
including calls on another instance of that same record. Omitted visibility is public.
Property and method names occupy separate lookup categories and may coincide.

Ordinary methods require an explicit return type and use the ordinary callable
argument/default/conversion and return rules. Defaults are not a second method-specific
mechanism. Constructor-specific `set:` inputs remain constructor-only. The bounded
implementation admits fixed by-value parameters and value/void returns. Static calls,
overloads, variadics, reference parameters/returns, inheritance/virtual dispatch and
bound callable values remain separate slices, not implicit support from this contract.
Named arguments are deferred to CH15.

## Receiver evaluation, access and lifetime

Evaluate the receiver once before explicit arguments, then evaluate arguments once
in source order. Select/check a shared receiver before evaluating those arguments;
an absent handle throws the existing checked runtime error. Keep the selected shared
object alive through the entire invocation, even if argument evaluation clears or
replaces the originating handle. Release the temporary retention afterward, including
on exceptions. This is retention of the same instance, not cloning.

An inline whole-storage receiver is borrowed for the call; do not copy it merely to
invoke a method. Mutations affect that storage. `$this` is the current instance and
is permitted as a fixed property/method receiver; copying, returning, passing or
rebinding `$this` is not enabled by this slice.

Methods currently have no declared read-only receiver contract. Therefore method
calls through read-only access paths reject during preparation, even for a method
whose current body happens not to mutate. Do not infer API mutability from body text.
This preserves the [read-only reference contract](native_reference_safety.md#v02-explicit-read-only-reference-parameters--2026-10-06),
not global immutability of shared objects.

The bounded lifetime admission supports retained shared receivers, current `$this`,
and stable inline whole storage (including eligible aliases/reference parameters).
Unique receivers, un-locked weak receivers, inline temporaries and projected inline
receivers reject during preparation pending lifetime integration. Explicitly locking
a weak handle produces an ordinary shared receiver; an expired lock still fails the
normal presence check. These are implementation gates, not permanent prohibitions on
those storage families. Never substitute a raw pointer or unchecked C++ reference to
silently accept an unsupported lifetime.
