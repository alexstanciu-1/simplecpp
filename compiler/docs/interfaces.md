# Class interfaces

Doc Status: supporting

The [inheritance contract](../../specs/inheritance_v0_2.md#interface-implementation)
owns semantics. The [frontend contract](../../specs/compiler_frontends.md#class-interfaces)
records this bounded implementation's spellings and deferrals.

Interfaces share the record AST and nominal identity. The record kind selects required
shared storage; there is no additional interface AST hierarchy or boxed payload.
Written interface edges are valid multiplicity and belong to their record. Prepared
edges reference original interface definitions. A C++ colon list resolves its first
edge as either a concrete base or an interface; later edges must be interfaces.

Interface prototypes have no body or body preparation job. Declaration preparation
reuses signature and override compatibility checks. An authored implementation needs
Override, while an unchanged inherited implementation needs no new source declaration.
The static callable selects defaults. Fixed shared-value parameters allow contravariance and shared results allow covariance.
Abstract implementers may leave bodies outstanding; an existing inherited abstract
method must still satisfy the directional callable contract.

Each invalidated record validates its obligations through concrete ancestry and transitive
interface edges. A temporary visited set deduplicates the original interfaces in a diamond. The existing record readiness flag now covers abstract and interface
contracts. Temporary requirement lists reference original declarations; method queries
select only the requested name. Preparation retains forwarding targets and the distinct contracts needing shared-view adapters. No synthetic source method, copied body or flattened member table
is added. Unchanged contracts retain their readiness and no-op builds reuse prepared
bodies and native objects.

An inherited ordinary method can fulfill an interface without changing dispatch on its
original base. Preparation records the bridge target on the implementing descendant.
C++ emits an out-of-line overriding member that calls the selected base implementation.
By-value nontrivial parameters move into the implementation; references and scalar
parameters pass directly. A later source override replaces this interface slot. Calls
on the original nonvirtual base still use the original implementation. The prepared
call retains dispatch intent when its source contract remains a nonvirtual declaration.
An inherited final implementation seals the source method for all descendant views.
A final nonvirtual implementation uses the ordinary forwarding member. For a final virtual
implementation, the descendant introduces a native base for its new interface views.
That base forwards to the declaring class's existing final slot through the descendant;
a C++ using-declaration keeps direct class calls on the original slot. The existing
forwarding map supplies both forms. No body is copied or interface entry allocated at runtime.

Native interfaces use public virtual interface inheritance and nonthrowing virtual
destruction. An interface redeclaring an existing native signature imports the original
slot with a C++ using-declaration. Its source declaration still selects defaults. This
allows two branches to redeclare a common slot without adding competing native overrides.
Concrete shared-view adapters preserve one control block, adjusted view
pointers, allocation identity and concrete cleanup. No runtime ownership code or
reference-count synchronization is changed. Required function boundaries and class
instance fields preserve written nullability. Constructors with only interface edges have no concrete
base to initialize; preparation records an omitted initializer as a void no-op.

Existing watchers track interface signatures, implementer ancestry and selected or
missing method declarations. Invalidation clears resolved edges, bridges and readiness.
Header publication includes interface layouts; depfiles rebuild affected native
consumers even if emitted caller C++ is unchanged. Source-owned adapter demand is
retained on reuse and retired when its final consumer disappears.

## Shared-value dispatch

Only a virtual signature with a by-value shared parameter or shared result uses a
signature-based C++ slot. The slot encodes the canonical return and parameter types
and passing modes, so independently compiled descendants agree on its name. A variant
override introduces an implementation slot and forwards inherited slots to it. The
bridge moves each changed argument or result through the existing shared-view adapter.
This preserves allocation identity and empty owners. An explicit ancestor call still
selects the ancestor implementation. Rebuild affected native units when adopting this ABI.

The [shared variance test](../tests/shared_variance_native.py) compares retained
and fresh builds through ancestry, presence, signature and deletion edits in all three flavors.

## Verification

```sh
XDEBUG_MODE=off python3 -B compiler/tests/interfaces_native.py --memcheck
```

The suite exercises all three flavors on Clang and GCC: local/inherited implementations,
mandatory markers, abstract obligations, multiple views, per-contract defaults, reference
forwarding, checked conversions, single evaluation and receiver retention, weak expiry,
allocation identity and Valgrind cleanup. It compares retained and fresh output and
diagnostics through edits/errors/recovery, source deletion/restoration, header rebuilds,
body-only caller reuse, adapter removal and successful forwarding-binding
removal/restoration with unrelated native-object reuse. Standalone forwarding targets
have one declaration in the common inventory. It also checks standalone emission and
native namespace qualification with a record named `scpp`.

Interface capability tests and checked casts now reuse the
[shared-object access path](object_access.md), preserving control blocks and adjusted views.

The [interface inheritance fixture](../tests/interface_inheritance_native.py) exercises diamond dispatch,
ancestor conversions, conflicting contracts, defaults, cycle diagnostics and incremental recovery.
Preparation validates parent contracts before implementers in both fresh and retained generations.

Deferred: struct implementers and broader reference
returns, interface data/static/default members, trait use in interfaces, unique, borrows and native
threading. Sequential tests do not prove thread safety
or a preparation-speed improvement.

## Class and interface parameter intersections

A required by-value parameter can name one class and one interface. PHP writes
`Base&Readable $item`, C++ writes `Base&Readable item`, and JS writes
`item: Base&Readable`. Either order names the same contract. A declared type or a direct interface guard on a local can establish the two components. An intersection can forward to another pair
when both requirements follow from its declared components.

One concrete native pair retains the class owner and an adjusted interface pointer.
Member preparation selects the appropriate view on the existing name occurrence.
Fields use the class view. Distinct same-name methods on independent contracts require
selecting a constituent explicitly, for example by passing the argument to a helper
with that constituent as its parameter type. Calls and returns using either constituent
reuse ordinary shared ownership. Conversions use compile-time pointer adjustments.
Intersection reference parameters, defaults, producer parameters, written storage
and return types are outside this slice.

A successful interface guard can supply a borrowed native view to a by-value
argument. A negative guard with a single returning or throwing arm supplies the
same proof on fallthrough. Preparation tracks the tested local by storage ID.
The argument links to its test through its existing binding slot. A guard with
no such consumer still emits the ordinary boolean test. Loops, exception joins,
and yield suspension do not propagate the proof. Writable reference parameters
cannot establish it because another binding can replace their owner.
[Guarded arguments and incremental recovery](../tests/guarded_intersections_native.py)

[Native and incremental proof](../tests/intersection_parameters_native.py)

## Closed class-union parameters

A required by-value parameter can name two distinct classes with a common class
ancestor. PHP writes `NameExpr|FieldExpr $item`, C++ writes
`NameExpr|FieldExpr item`, and JS writes `item: NameExpr|FieldExpr`.
The type-reference siblings retain source order. Preparation interns the two
resolved arms in canonical name order and selects the nearest common class
from their settled base chains. The owner of that class is the native ABI.
Virtual slot names retain the source union identity even when two signatures
use the same owner. Shared-view conversions use the existing native adapters.

The [union parameter proof](../tests/union_parameters_native.py) covers cross-unit
calls, constructor and virtual method parameters, reversed signatures, rejected
siblings, and ancestry edits that change the native owner.

Readonly instance fields use the same union resolver. Layout preparation settles
ancestry first and then resolves pending union fields. The field retains the
source union for admission and exposes its common-base owner for native storage
and header dependencies. The [field proof](../tests/readonly_union_fields_native.py)
covers constructor initialization, payload lifetime and incremental ancestry edits.

Free functions, instance methods and static factories can return the same two-arm
contract. Virtual and interface declarations require the same canonical pair.
A direct arm test on a by-value union parameter can narrow both branches of an
`if/else`. Rebinding the parameter invalidates the proof for subsequent reads.

The [parser-to-visitor proof](../tests/union_pipeline_native.py) passes both
factory results through a constructor-initialized readonly field to a visitor
in another unit. It changes the nearest common base, removes an arm and compares
incremental diagnostics, generated artifacts and recovery with fresh compilation.
