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

The existing type-contract owner now delegates all exact comparisons to this owner.
Source-level variance is still rejected: this checkpoint does not attach adaptations
to dispatch edges or emit bridges. There is no claim of executable variance support.

Proof: 22 adaptation-model cases plus reference-return checks and six adjacent PHP
suites passed in `/tmp/callable-adaptation-final2`. Existing generated execution stayed
green: 32 abstract-dispatch cases without PCH in `/tmp/callable-adaptation-dispatch`.
The compiler itself was not rebuilt natively.

## Checkpoint 2: dispatch integration — next

Separate each required dispatch contract from the selected authored implementation.
Retain original declaration identities and prepared adaptations, without copied AST
methods/bodies. Generate typed bridges where needed; execute the authored body once,
preserving shared identity without object allocation or runtime downcasts. Concrete
calls retain the authored signature; base/interface calls retain their static contract.

Cover multi-level overriding, several interface contracts, inherited implementations,
abstract intermediates, explicit parent calls, finality, defaults and reference-return
invariance. Keep existing undecided dispatch limits gated unless separately approved.
Signature/ancestry edits need failure/retry/repair and reset/fresh proofs before source
admission is widened. Update the authoritative inheritance spec when enabling execution.
