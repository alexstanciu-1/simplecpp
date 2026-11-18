# Class interfaces

Doc Status: supporting

The [inheritance contract](../../../specs/inheritance_v0_2.md#interface-implementation)
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
Contracts remain exact in this slice; defaults are selected from the static callable.
Abstract implementers may leave bodies outstanding; an existing inherited abstract
method must still satisfy the exact callable contract.

Each invalidated record validates its obligations through existing ancestry and direct
interface edges. The existing record readiness flag now covers abstract and interface
contracts. Temporary requirement lists reference original declarations; method queries
select only the requested name. The only retained method map contains required
forwarding targets. No synthetic source method, copied body or flattened member table
is added. Unchanged contracts retain their readiness and no-op builds reuse prepared
bodies and native objects.

An inherited ordinary method can fulfill an interface without changing dispatch on its
original base. Preparation records the bridge target on the implementing descendant.
C++ emits an out-of-line overriding member that calls the selected base implementation.
By-value nontrivial parameters move into the implementation; references and scalar
parameters pass directly. A later source override replaces this interface slot. Calls
on the original nonvirtual base still use the original implementation. The prepared
call retains dispatch intent when its source contract remains a nonvirtual declaration.
Lowering does not resolve ancestry or inspect foreign executable bodies.

Native interfaces use public virtual interface inheritance and nonthrowing virtual
destruction. Concrete shared-view adapters preserve one control block, adjusted view
pointers, allocation identity and concrete cleanup. No runtime ownership code or
reference-count synchronization is changed. Required function boundaries and nullable
function types retain their existing checks; shared properties retain the temporary
relaxed empty-storage policy. Constructors with only interface edges have no concrete
base to initialize; preparation records an omitted initializer as a void no-op.

Existing watchers track interface signatures, implementer ancestry and selected or
missing method declarations. Invalidation clears resolved edges, bridges and readiness.
Header publication includes interface layouts; depfiles rebuild affected native
consumers even if emitted caller C++ is unchanged. Source-owned adapter demand is
retained on reuse and retired when its final consumer disappears.

## Verification

```sh
XDEBUG_MODE=off python3 -B v0.2/compiler/tests/interfaces_native.py --memcheck
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

Deferred: interface inheritance/diamonds, interface type tests/downcasts/cross-casts,
struct implementers, inherited-final satisfaction, variance and broader reference
returns, interface data/static/default members, traits, unique, borrows and native
threading. Unsupported inherited-final satisfaction is diagnosed as deferred, not
classified as an invalid language contract. Sequential tests do not prove thread safety
or a preparation-speed improvement.
