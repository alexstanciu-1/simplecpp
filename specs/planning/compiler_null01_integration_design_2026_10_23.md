# NULL-01 coordinated integration design
Doc Status: planning

Approved by the user on 2026-10-23. Class-self redesign remains separately gated.

## Fresh evidence and scope

At bf5a667bf, `/tmp/null01-boundary-reuse-01` reproduces five source boundaries in
ordinary and prelowered emission: raw null returns/arguments to ?Node reject;
Node = null, typed-empty return to ?Node and ?Node storage passed to Node& accept.
These are preparation/emission observations, not native execution. Saved cases:
results/compiler_bootstrap_backlog_2026_10_23/null01_integration/cases.json.
Actual compiler witnesses include nullable preparation_work_owner accessors and
iterator exhaustion. The current normalization erases the information required to
accept the former without also admitting null to required declarations.

The nullable registry and physical handle representation are ready. A local widening
of Ownership_Conversions would preserve the wrong frontend contract. Global optional
unwrapping would likewise weaken reference and retained signature identity.

## Proposed owner changes

1. Type_Preparation stops normalizing optional nominal handles. Existing registry
   identity, signature comparison and opaque specialization keys remain authoritative.
   Type_Queries gains explicit presence-aware receiver interpretation, not a global
   change making optional values report ordinary ownership or exact equality.
2. Conversion_Preparation applies presence policy before ownership policy. Retain a
   nullable presence step plus an optional payload conversion in the existing
   conversion_decision model. Reuse Ownership_Conversions for safe ancestry. Validate
   composition during preparation; emission renders the settled sequence and never
   resolves ancestry. No general conversion graph or implicit numeric lifting.
3. Optional-to-required values evaluate once, check before callee entry/destination
   replacement, retain the same control block and then apply an admitted upcast.
   Required-to-optional and optional-to-optional upcasts preserve absence and identity.
   Literal null to required declarations rejects. Explicit shared/unique/weak keep
   their agreed possibly-empty contracts and redundant optional spelling rejects.
4. Member and callable owners preserve receiver retention and checked access while
   resolving the optional payload's members. Reference compatibility stays exact.
   Existing callable adaptation gains only the accepted directional by-value presence
   relation; no throwing narrowing adapter or container covariance is admitted.
5. Required field initialization checks constructor-success boundaries, including
   inherited storage and early returns. Required class locals use LOCAL-01's existing
   completion machinery for definite assignment. Hidden empty initialization must not
   escape as a readable required object. _uninitialized_ remains converter backing
   storage/accessors, not a native type state.
6. Existing ordinary/prelowered conversion and wrapper consumers render shared-handle
   presence checks and optional payload transport. No new shared-owner allocation,
   weak-lifetime emulation or class-self redesign is bundled here.

## Validation / risks / approval boundary

The change crosses formation, retained conversion data, receiver/callable checking,
initialization and backend transport. It therefore exceeds a local owner refactor.
AGENTS.md required confirmation for this scope; the user has now approved it. The accepted
language decisions are unchanged; approval concerns the coordinated implementation.

Validation includes literal-null rejections, nullable exact/ancestry values, absent
failure before side effects, invariant references, directional overrides, fields and
locals on early/branch exits, receiver lifetime, retained signature edits/revert,
PHP/legacy portability comparison, both native output modes and prelowered template
exclusion. Existing explicit ownership operations and value-nullable behavior regress.
Measure a new compiler bootstrap after a coherent vertical slice. Full acceptance
requires all boundary and initialization obligations, not just successful emission.

Recommended: approve this bounded coordinated integration, keeping class-self owner
registration separately gated. Alternative: defer NULL-01 while completing independent
COPY-01, source, binding and task work. Merely accepting null returns in the current
ownership dispatcher is not a correct intermediate contract.
