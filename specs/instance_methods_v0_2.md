# v0.2 instance method calls
Doc Status: normative

This records the bounded instance-method contract approved on 2026-10-07. Classes
and structs share declaration, parameter, return and visibility semantics; their
storage/copy differences remain unchanged. Availability is tracked in the
[CH10 checkpoint](../compiler/my-try/docs/catalog/10_objects.md#instance-methods--2026-10-07).

## Fixed instance calls

### Property declaration spelling

Class/struct instance and static properties accept both `public vector<int> $items;`
and `public $items vector<int>;`. The type appears on exactly one side of the name;
even identical types on both sides reject. Both spellings define the same property
type, modifiers and initialization contract. Nullable, qualified, callable and nested
generic types use the ordinary type grammar. Existing untyped declarations with an
inferable initializer remain unchanged. This adds no parameter/property promotion.

### Invocation

The [concrete inheritance extension](inheritance_v0_2.md) enables inherited fixed
methods and protected access with C++-compatible receiver restrictions. It supersedes
the original slice's inheritance gate below, not its virtual-dispatch/overload gates.

`$object->method(arguments)` and `$this->method(arguments)` select a fixed method
name on the receiver's declared record type. Public methods are externally callable;
private methods are callable only from the declaring record's context,
including calls on another instance of that same record. Omitted visibility is public.
Property and method names occupy separate lookup categories and may coincide.

Ordinary methods require an explicit return type and use the ordinary callable
argument/default/conversion and return rules. Defaults are not a second method-specific
mechanism. Constructor-specific `set:` inputs remain constructor-only. The bounded
implementation admits value/void and bounded same-origin reference returns. Overloads, inheritance/virtual dispatch and
bound callable values remain separate slices, not implicit support from this contract.
Named arguments are deferred to CH15. Static calls now have their own bounded
[contract](static_members_v0_2.md) on the same method/callable foundation.

## Shared method parameters — 2026-10-07

Instance and static methods on both classes and structs use the ordinary function
parameter contract, including fixed by-value parameters, writable `T &$value` and
read-only `const T &$value` references, and a typed trailing `T ...$values` pack.
Native references require exact compatible stable storage; no implicit conversions,
temporary binding or heap-interior borrowing are introduced. Read-only access is
enforced during preparation, including forwarding, aliases and container mutation,
under [the existing reference rules](native_reference_safety.md).

A typed variadic tail is an owned `vector<T>`, may be empty and may follow fixed
parameters (including references or supported by-value defaults). It must be last;
reference variadics, explicit pack defaults and call-site unpacking remain excluded.
Element admission/conversion and copy capability are the same as for free functions.
Explicit arguments are evaluated once in source order; value snapshots and reference
bindings retain their distinct semantics. Two references may alias the same storage;
read-only access does not promise that another alias cannot change it.

This extends parameter admission only: constructor parameters, receiver lifetime
restrictions and reference-return restrictions remain unchanged. No method-specific
packing or borrowing implementation is defined.

## Same-origin method reference returns — 2026-10-07

Instance/static methods on classes/structs share the
[same-origin reference-return contract](native_reference_safety.md#v02-same-origin-reference-returns--2026-10-06).
`function &select(T &$value): T` requires exactly one fixed writable reference
parameter of the return storage type. Every return selects that whole parameter,
directly, through local aliases or through nonrecursive function/method forwarding.
Ordinary completion checks still apply. Returning locals, temporaries, `$this`
properties or container interiors is rejected, as are multiple possible origins,
recursive reference returns and const-reference returns. Constructors are unchanged.

Ordinary assignment `$copy = $ops->select($value)` copies the selected value using
its normal copy contract; it does not acquire a storage alias or leak memory.
Value/COW copies remain values; shared handles retain shared identity. Noncopyable
values reject copying during preparation. `$alias =& $ops->select($value)` instead
binds the caller's original storage. Neither form extends that storage's lifetime.
Receiver retention lasts through the call only; returned storage cannot depend on
the receiver staying alive. Existing receiver eligibility is unchanged.

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
rebinding `$this` is not enabled by this slice. The
[bounded closure receiver contract](language/closures.md#v02-borrowed-receiver-captures)
additionally permits explicit `use ($this)` and automatic arrow receiver capture:
these borrow the instance within the current invocation, without copying or retaining it.

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
