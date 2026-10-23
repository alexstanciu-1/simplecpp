# v0.2 instance method calls
Doc Status: normative

This records the bounded instance-method contract approved on 2026-10-07. Classes
and structs share declaration, parameter, return and visibility semantics; their
storage/copy differences remain unchanged. Availability is tracked in the
[CH10 checkpoint](../compiler/my-try/docs/catalog/10_objects.md#instance-methods--2026-10-07).

## Fixed instance calls

### Shared class self values — 2026-10-23

In an ordinary source-class instance method, `$this` used as a value acquires a
shared handle to the same instance and existing owner. Assignment, by-value
arguments and returns use ordinary shared-value conversions. Inherited methods
preserve both the owner and the adjusted receiver address; no object is cloned.
The acquired handle can outlive the handle that originally invoked the method.

Direct owning acquisition in constructors/destructors rejects during preparation.
Acquisition without a live registered shared owner fails at runtime, including
indirect acquisition before shared construction completes. Inline/unique ownership
is not promoted. Borrowed receiver capture behavior is unchanged, and ordinary
strong-reference cycles are not prevented automatically.

The initial implementation binds source-class owners through v0.2 shared
construction, including outlined allocation. Its native observer and prelowered
layout use the same lifecycle policy: new object copies/moves start unbound;
assignment preserves the destination's owner. v0.1 retains its existing mechanism.
Native factories bypassing v0.2 source construction do not automatically bind an
observer; broader native adoption integration remains outside this first slice.
Ordinary/prelowered inherited retention and final release are exercised by
`compiler/my-try/tests/shared_self.py`. This is bounded feature evidence, not a
completed native compiler bootstrap or full SELF-01 acceptance claim.

### Property declaration spelling

Class/struct instance and static properties accept both `public vector<int> $items;`
and `public $items vector<int>;`. The type appears on exactly one side of the name;
even identical types on both sides reject. Both spellings define the same property
type, modifiers and initialization contract. Nullable, qualified, callable and nested
generic types use the ordinary type grammar. Existing untyped declarations with an
inferable initializer remain unchanged. This adds no parameter/property promotion.

### Plain property writes through indexed shared values — 2026-10-10

An ordinary checked indexed read returning a shared class/record handle may serve
as the receiver of plain property assignment, for example
`$entry->lines[1]->credit = 100`. This is object-property mutation, not replacement
of a container element. The indexing contract supplies the value; no vector/hash
specific property-assignment rule or container writeback is involved.

Evaluate the receiver path and key once, check the indexed read, retain the selected
shared handle and resolve the destination property before evaluating/converting the
RHS. Bounds/missing-key/null-owner failures therefore prevent RHS execution. Removal
or replacement of the original element/container during the RHS does not redirect
the selected object write. The retained receiver is released after the write's
value snapshot is obtained. Shared field owners are likewise retained across keys.

Visibility, property mutability and assignment conversions remain ordinary rules.
A copied shared handle preserves object identity; copying it from read-only container
storage does not imply deep freezing of that object. A read-only property itself
remains unwritable. Inline element values cannot be mutated through this form: a
copied record would not update the container. No native reference or alias into
container/object storage is exposed.

This bounded extension admits plain statement writes and their use in existing
assignment chains as a value. RHS assignment chains on this new destination,
compound/increment updates, inline-element mutation and new ownership-transfer
contracts remain deferred.

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
The [read-only](native_reference_safety.md#read-only-reference-defaults--2026-10-22) and
[writable literal-default rules](native_reference_safety.md#writable-reference-defaults--2026-10-22)
also apply to fixed canonical scalar reference parameters on source methods.
Explicit native reference arguments require exact compatible stable storage; no implicit conversions,
temporary binding or heap-interior borrowing are introduced. Read-only access is
enforced during preparation, including forwarding, aliases and container mutation,
under [the existing reference rules](native_reference_safety.md).

A typed variadic tail is an owned `vector<T>`, may be empty and may follow fixed
parameters (including references or supported by-value defaults). It must be last;
true reference variadics, explicit pack defaults and call-site unpacking remain excluded.
The bounded `T &...$values` compatibility spelling below denotes read-only owned values.
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
is permitted as a fixed property/method receiver. For a struct, `$this` is also an
existing value place: ordinary assignment, by-value arguments and by-value returns
copy it under the normal copy-capability rules. Copies are memberwise, preserving
shared-field identity and each value field's own copy semantics. A method invoked
through `shared<S>` still produces an `S` value at an `S` boundary, not a retained
shared struct handle. The receiver itself is never moved from implicitly.

Rebinding `$this`, borrowing it into a new native reference and returning a native
reference to it remain rejected by the existing receiver/reference restrictions.
Borrowed receiver captures remain borrowed; a value copy produced inside the bounded
closure follows ordinary value rules. Neither struct self copying nor class self
acquisition promotes inline or unique ownership to shared ownership.
The converter-owned `_struct_copy_` protocol is separate from this native capability. The
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

## Read-only owned compatibility packs — 2026-10-22

Named source free functions and static/instance methods accept `T &...$values`
for canonical `int`, `float`, `bool` and `string`. This is a compatibility spelling
for a read-only owned value pack, not caller-element references. It is distinct
from fixed `T &$value`, which retains its live alias and write-back contract.

Tail arguments use ordinary value-pack admission, conversion and copy restrictions.
Each is evaluated and snapshotted once, left-to-right; later argument effects and
writes through separate fixed references do not change earlier snapshots. The
completed pack is owned storage whose lifetime covers invocation and cleanup. Empty
packs are permitted. Fixed parameters, including their admitted defaults, may precede
the tail. The tail must remain last and cannot have an explicit default.

The callee may read, index, iterate, bind read-only local aliases and forward the
whole pack to an eligible read-only parameter. Mutation, append, replacement,
cleanup/removal and writable-reference forwarding through the pack or its aliases
reject during shared preparation. An ordinary copy creates independent mutable
value storage; returning an ordinary value copy preserves it after pack cleanup.
Borrowed references to pack storage may not escape the call. Existing escape and
capture restrictions apply without new lifetime inference.

The backend may borrow call-owned storage when safe, or pass owned values; it must
preserve these semantics. No caller aliasing, pack-of-references representation or
copy-back is implied by the compatibility marker. Ordinary `T ...$values` remains
a mutable owned value pack in v0.2.

This scope does not introduce explicit `const T &...$values` spelling, general
read-only value parameters, wider element types, constructor/anonymous-callable/
provider/callback variadic bridges, call-site unpacking or asynchronous retention.
Those contracts remain separate. Pinned v0.1 evidence executes tested free/static
scalar read-only packs and rejects direct element writes. Its instance-method
packing probes fail native compilation; current method support follows the v0.2
contract rather than a claim of demonstrated executable baseline parity.
