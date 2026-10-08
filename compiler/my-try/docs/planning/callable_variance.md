# Shared callable variance
Doc Status: planning

## Agreed scope — 2026-10-08

Virtual overrides, interface implementations and trait requirements share directional
signature admission. A provided fixed by-value shared parameter may accept an ancestor
of the required parameter; a provided shared result may narrow the required result.
Class handles, interface handles and shared structs use the same observed ancestry.
Cross-casts, numeric conversions and container element conversions are not subtyping.

Reference parameters and returns (including origin), inline values, unique/weak
ownership and variadic element types remain invariant. Counts, passing/access modes,
optional positions and variadic shape remain unchanged. Parameter names and default
values may differ; callers continue to select defaults through their static contract.
Static redeclarations and constructors do not acquire variance.

## Checkpoint 1: signature adaptation foundation

`Callable_Adaptation::between` compares declaration-independent callable signatures.
Its optional transient relationship query enables shared ancestry; without that query
the comparison is exact. The result owns ordered parameter conversion decisions
(required to provided) and a result conversion (provided to required). Only identity
and shared upcast operations are admitted; facts retain neither queries nor workers,
and backend spelling is absent. Reference conversions remain identity only.

This first checkpoint delegated existing exact comparisons to the shared owner.
It kept source variance rejected until the dispatch integration below.

Proof: 22 adaptation-model cases plus reference-return checks and six adjacent PHP
suites passed in `/tmp/callable-adaptation-final2`. Existing generated execution stayed
green: 32 abstract-dispatch cases without PCH in `/tmp/callable-adaptation-dispatch`.
The compiler itself was not rebuilt natively.

## Checkpoint 2: dispatch integration — implemented

Each `prepared_dispatch_binding` observes a required declaration/slot owner and a
selected implementation/dispatch owner, owning the directional adaptation. Local
method decisions and inherited dispatch introductions remain name-indexed; bindings
do not become source members. An inherited ordinary method can gain a descendant-owned
slot without changing its original declaration. Trait bindings feed semantic cycle
analysis without inventing a runtime trait base. Contract equivalence observes binding
identities and conversion types/operations through the existing incremental lifecycle.

`CPP_Dispatch` owns source-facing nonvirtual entries, distinct authored implementation
names and owner-qualified virtual slots. Required slots delegate through the selected
implementation slot, preserving descendant overriding across multiple levels. Explicit
parent calls name the original authored implementation and bypass redispatch. Bridge
definitions live in source implementation units, keeping headers declaration-only
for callable types and avoiding complete-type include cycles. Existing signatures and
bodies remain independently cached; settled dispatch is assembled alongside them.

Source admission now enables the agreed shared-value variance. Concrete calls retain
the authored signature; base/interface calls retain their static contract and defaults.
No runtime representation changes, synthetic AST methods, payload copies, new objects
or downcasts are added. Native-call/bridge overhead has not been benchmarked; no claim
of automatic bridge elimination is made. The subsequent approved final-forwarding slice
binds new interface views to inherited final slots without overriding them; interface-
qualified static dispatch remains rejected and is explicitly deferred to CH15.

Focused proofs cover multi-level/abstract overrides, interfaces and inherited forwarding,
class/struct receivers, shared struct and interface payloads, trait requirements,
parent calls, identity/one-body execution, defaults, absence, invariant reference results,
signature/ancestry failure/retry/repair and reset/fresh output agreement. Execution
evidence is recorded in the chapter checkpoint. The compiler itself was not rebuilt.
