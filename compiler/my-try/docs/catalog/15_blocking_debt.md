# 15. Accumulated blocking debt and final review
Doc Status: planning

[Catalog and workflow](README.md)

This is the final catalog chapter. Revisit the accumulated blocking design debt
after the feature chapters and the planned rest break, before declaring the overall
catalog pass complete. Keep this chapter last if more feature chapters are added.

Moving a debt here changes its review milestone, not its resolution status or the
language contract. Immediate correctness and memory-safety prerequisites still gate
the features that need them. This register is initially seeded with the explicitly
transferred iteration-policy and reference-iteration safety debts; it is not an exhaustive migration of existing
debts. Other chapter blockers remain in place unless explicitly transferred.

## Blocking inventory

| Debt | Origin | Status | Final completion requirement |
| --- | --- | --- | --- |
| CH06-ITER-01 — mutation/replacement during iteration | [Chapter 06](06_containers.md#ch06-iter-01--mutation-during-iteration) | Open; blocks final catalog completion, not Chapter 06 design closeout | Agree the long-term policy, reconcile contracts, implement required changes and prove behavior/safety |
| CH06-REF-01 — reference foreach safety foundation | [CTRL-FOREACH-003/004](06_containers.md#ctrl-foreach-003) | Open; reference foreach stays disabled; final-catalog blocker | Establish runtime guards and binding lifetime/COW guarantees before compiler integration |
| CH15-LIB-DYN-01 — library redesign and mixed/dynamic migration | [Chapter 06](06_containers.md) | Deferred by user; does not block core type migration | Review library-owned models, adapt affected APIs and prove enabled/disabled paths |
| CH15-DYNAMIC-PROP-01 — dynamic `->` | [Chapters 05/06](06_containers.md) | Deferred by user; not part of core family/JSON integration | Agree property semantics and implement shared prepared access with runtime checks and proofs |

User confirmation on 2026-10-05: library redesign, reference foreach and dynamic
`->` belong to this final chapter, not the remaining Chapter 06 implementation
queue. Reference foreach remains disabled until CH06-REF-01 is resolved. This
deferral does not change ordinary typed-object member access or existing contracts.

## CH06-ITER-01 — mutation policy

Transferred here by user decision on 2026-10-05. Review structural mutation and
source replacement during value and reference iteration, including changes through
aliases/calls, visited entries/values, owner lifetime, copy-on-write and invalidation.
Compare performance and implementation costs. Chapter 06 retains the detailed
snapshot-on-first-structural-mutation alternative; it is a candidate, not an approved
implementation. In reference iteration, preserving a snapshot must not silently
lose the binding's write-through relationship to the intended source.

Agreed interim direction for reference foreach:

- The value variable is a loop-scoped binding to the current element, not an
  escaping native C++ reference. Updating an existing element is permitted.
- Reject unsafe structural mutation and source replacement at runtime before
  invalidation. Enforce the guard at the owning mutation/storage paths, including
  aliases and calls; release it on every loop exit, including exceptions.
- Coordinate copy-on-write and owner lifetime; checking only at the next iteration
  is insufficient if the current binding can be accessed earlier.
- No unconditional snapshot or reliance on undefined behavior is permitted.

This records an agreed implementation direction, not delivered runtime protection.
The safety guard and focused proofs remain prerequisites for enabling reference
foreach. Deferring the broader policy does not expand existing value-iteration
support beyond its bounded non-mutating contract.

Close this item only after an explicit policy decision, updates to the owning
normative specifications, required compiler/runtime work, and focused proofs for
normal/exceptional exits, alias mutation, source replacement and binding validity.

## CH06-REF-01 — reference foreach safety foundation

Deferred here by user decision on 2026-10-05 after inspection showed that safe
runtime rejection requires a cross-owner foundation, not a local foreach adapter.
Both `foreach ($items as &$value)` and `foreach ($items as $key => &$value)` remain
disabled in my-try. Do not enable parsing/emission as a partially safe feature or
substitute the legacy key-driven source-slot rewrite. This is no longer scheduled
as the next Chapter 06 implementation slice.

Keep the agreed target: a loop-scoped current-element binding, independent of the
visible key variable, using the iterator's known position without inherently
repeating key lookups. No native reference may escape. The interim guard direction
above remains approved, but unimplemented.

Inspection findings and affected owners:

- Vector/hash structural mutation and replacement lack a common active-iteration
  guard. Some clear/move operations are `noexcept`; throwing from them without
  reviewing their contracts would terminate rather than deliver a runtime error.
- The original inspection concerned the old shared-hash `dynamic_t` alias. The
  approved tagged dynamic refactor now owns its binding and shared container payload,
  but does not supply active-iteration guards. Recheck replacement at that owner;
  guarding table entries alone still cannot protect source-binding replacement.
- Mixed copy-on-write can replace table storage during an ordinary write. Preserve
  the intended source's write-through semantics and independent copies; an old
  table pointer or extra shared owner alone does not establish this guarantee.

Resume with a separately agreed runtime foundation covering owner lifetime,
structural mutation, wrapper replacement, aliases/calls, COW and exception cleanup.
Then integrate one prepared element-binding path across the supported families.
Validate direct runtime behavior first, then generated programs and retained
compiler preparation/recovery. Include unchanged state after rejected mutation,
normal copy/move behavior, key independence, binding non-escape and all loop exits.
Review affected `noexcept` and native-access boundaries explicitly.

No runtime/compiler implementation was changed by this deferral. Existing bounded
value foreach remains available; neither safe mutation nor reference iteration is
claimed by its existing tests. The separate CH06-MIX-DYN-01 core integration gate
is now resolved; see Chapter 06's positive-pass conclusion.

## Design considerations for final review

These items require review, not automatic implementation or an implicit promotion
to blocking feature debt. Preserve the distinction from the blocking inventory above.

### Chapter 07 closeout debt — 2026-10-06

Transferred for final review by user approval; these do not require expanding the
bounded Chapter 07 implementation before Chapter 08. Existing restrictions remain
enforced until their respective contracts and proofs are agreed.

- **CH07-WARN-01 — advisory delivery:** establish a retained, non-fatal diagnostics
  owner and publication/retraction behavior for reference-return advice and relevant
  performance guidance. Coordinate with Chapter 14. Do not equate hard safety errors
  with delivered warnings or add emitter-only reporting as a substitute.
- **CH07-OWN-EXEC-01 — broader ownership operations:** review ownership selection and
  variadic transfers, broader const forms and precise additional target constraints.
  Constructor arguments remain with Chapter 10; member lifetime work belongs with
  Chapters 09/10 and their established type models. This entry tracks final review,
  not a competing implementation owner. Low-level pointer escapes, specialized
  deleters and intentional direct ownership nesting restrictions are not enabled.
- **CH07-REF-EXT-01 — broader reference returns:** review different origins, recursive
  reference returns, registered runtime reference returns and loop-header aliases.
  Same-origin multiple returns and control-flow body aliases are already implemented,
  not outstanding work. Require a truthful lifetime contract before widening origin
  admission; container-interior safety and disabled reference foreach remain unchanged.

Close each item by an explicit supported/deferred/excluded disposition, updated
owning contracts where needed and focused behavioral/lifetime/recovery evidence.
The inherited-limit review below should link these items rather than duplicate them.

### CH15-S2S-LIMITS-01 — review inherited S2S limitations

Requested on 2026-10-06: review all old S2S limitations against the capabilities of
the v0.2 semantic compiler. Do not preserve a restriction solely because the old
type-blind generator could not resolve types, storage origins or control flow.
This is a catalog-wide review debt, not approval to enable every excluded feature.

Inventory restrictions and workarounds from the imported catalog cards, legacy
generator rules/diagnostics/tests and relevant top-level contracts. For each, record
its source, original reason, current semantic owner, prerequisites and disposition:
already solved with proof, safely removable in a bounded slice, still a deliberate
language/safety rule, or deferred with an explicit blocker. Link existing debts
rather than duplicating their implementation queues.

Candidates include conditional/loop alias restrictions (body-local aliases are
already proved in Chapter 07), single-final-return restrictions, type-blind lowering
workarounds, delayed C++ diagnostics and avoidable runtime checks. Preserve runtime
checks for genuinely value-dependent failures; compile-time knowledge does not
remove their necessity. Native interior-reference safety, family separation and
intentional ownership restrictions are not automatically obsolete.

Complete the review with a traceable disposition ledger and agreed follow-up slices.
Any changed language contract needs explicit approval and an owning-spec update;
implementation needs focused accepted/rejected, lifetime and incremental-recovery
proofs where relevant. Record generated-program and native-compiler evidence
separately. Reviewing a limitation does not itself resolve it or enable its syntax.

### CH15-CALLABLE-01 — callable values in mixed/dynamic entries

Recorded at the user's request on 2026-10-05. Consider function/closure values in
both families, for example `{my_func: function (): int { return 1; }}`. Implementation
belongs with [Chapter 12](12_closures.md), not the current mixed layout/dynamic refactor.
The [storage discussion](../planning/mixed_dynamic_storage.md) should leave room for
this extension without implementing an unused callable kind or placeholder execution.

Candidate direction, to validate with the callable model:

- One callable concept for locals, arguments, returns and entries: code entry,
  signature (parameter types, passing modes and return type), and optional captured
  environment. A shared handle may preserve closure identity and environment lifetime;
  capture-free functions need no captured environment.
- Both value families may admit the same callable category without admitting each
  other's container payloads. Storing a callable as a property does not implicitly
  bind a receiver or `$this`; copying it out preserves its invocation meaning.
  Explicit receiver-bound methods would be a separate callable form.
- Consider treating captures as private callable state, not entries in the enclosing
  data tree. Decide whether captures may include either family, while preserving
  ownership, reference-escape rules and cycle handling. This is not yet a settled
  exception to family separation.
- Known signatures are validated during preparation; runtime-selected callables
  need kind/signature/argument checks before invocation. Share existing call contracts
  where truthful rather than adding container-specific invocation semantics.
- Target a callable handle within the 16-byte payload budget so tagged values may
  retain the proposed 24-byte layout; measure before claiming this is achieved.
  Reuse scalar/callable support without treating callbacks as JSON values.
- JSON decoding does not construct functions. Proposed encoding behavior is explicit
  failure for callable payloads; reconcile with the JSON contract when enabling them.

Review proofs should cover storing/extracting/copying/invoking callables in both
families, preserved capture state and lifetime, absent/non-callable entries,
signature mismatch, receiver independence, reference non-escape and encoding failure.

### CH15-SHAPE-01 — preserving array versus object identity

Deferred by user decision on 2026-10-05. For the mixed/dynamic refactor, continue
classifying container shape through the internal hash query (currently
`hash_t::is_packed()`, called `is_pure_array` in discussion). Do not introduce a
separate persistent array/object identity tag in this slice.

Later review whether construction/JSON decoding must retain `[]` versus `{}`
independently of the current keys, especially for empty containers. Current shape
classification must not be advertised as lossless preservation of original notation.
Review numeric-key objects, mutations that change packed shape, JSON round-trips,
property access and any storage/ABI cost before choosing a retained representation.
This is a deferred design consideration, not a prerequisite for the agreed refactor.

### CH15-JSON-01 — optional string-producing `to_json` alias

The user approved removing the misleading mixed-tree-producing runtime `to_json`
helper on 2026-10-05. `json_encode` / `scpp::json::encode` remain the checked
string-producing API, using direct traversal rather than intermediate boxed trees.
Later decide whether a `to_json` alias is useful. If introduced, it must produce
`result<string>` through the same writer, not construct a mixed/dynamic tree or
silently bypass checked failures. This naming debt does not block Chapter 06 or
authorize restoring the removed helper.

### CH15-LIB-DYN-01 — deferred library mixed/dynamic integration

User direction on 2026-10-05: do not delay the core mixed/dynamic migration for
library integration issues. Record affected APIs here; do not restore cross-family
boxing or claim deferred libraries work with the new types. Keep base-runtime
compilation independent of deferred optional-library integration where needed.

Review library-owned models before mechanically substituting dynamic for mixed.
The user identified hurried generic boxed APIs: use specific structures/classes for
known library records, scheduling state and results where appropriate. Reserve
mixed/dynamic for genuinely runtime-shaped user data. This design review is deferred,
not authority to redesign every library during the core runtime migration.

Tasks decisions already agreed:

- Boxed task results and `task_join` use the new dynamic family, not mixed.
- Workers may receive dynamic containers with shared identity. This does not imply
  automatic locking or make simultaneous unsynchronized mutation safe.
- Complete the worker-transfer/synchronization contract and enforcement separately;
  test shared reads, synchronized mutation, alias lifetime and result publication.

The attempted integration found that `hash<dynamic, mixed>` is rejected by typed
key admission; only `hash<mixed, mixed>` currently wraps the shared packed/associative
engine for mixed int/string keys. Resolve this through a reusable wrapper or a
task-owned scheduling representation, not by converting dynamic values into mixed.
Keep source key-domain support separate from private scheduler storage capability.

Task batch storage, result boxing/join, dynamic input scheduling, error/cancellation
paths, library registrations and disabled-module diagnostics must migrate together.
The compile-blocked task attempt was removed from the active tree; it is not a
completed feature. Existing task code still carries its old mixed assumptions until
this debt is handled. Existing JSON/MySQL runtime checkpoints remain implemented;
JSON's boxed source APIs are now registered in my-try; MySQL source registration
and remaining library consumer integration still need review.

### CH15-DYNAMIC-PROP-01 — dynamic property access

Deferred to Chapter 15 by user direction on 2026-10-05. Keep dynamic `->` disabled
while completing core family and JSON integration. Discuss fixed/computed property
names, reads/writes/probes/removal and eventual callable members before admission.
Reuse existing prepared place/access and runtime container machinery where truthful;
do not create a second mixed/dynamic traversal or revive cross-family payloads.

Resolve missing properties, null/scalar receivers, sequencing, shared alias effects
and the relation to CH15-SHAPE-01. Callable values do not automatically imply method
binding; coordinate with CH15-CALLABLE-01. Completion requires an agreed normative
contract plus preparation, generated-program and failure-state proofs. This debt
does not defer ordinary typed class/struct member work in its owning chapter.
