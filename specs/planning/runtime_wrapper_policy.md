# Wrapper policy and constrained template arguments
Doc Status: planning

Checkpoint: 2026-10-27, after core inventory `9cea7c941`.
Owner: [runtime-core completion plan](runtime_core_completion.md).
Purpose: discuss the remaining semantic and generation boundaries before creating
an implementation goal. Existing normative constraints below are confirmed facts;
proposed policy/mechanism rows are not new language permissions.

## Confirmed source template contract

[Metaprogramming contract §6.1–6.3](../metaprogramming_contract.md#61-default-contract-for-a-bare-type-parameter)
is explicit: a bare source type parameter requires `copyable_value`.
That requires known representation, copy construction, copy assignment and a valid
compiler-managed cleanup contract. No-op cleanup must be established, not guessed.
It permits owned copying and call-scoped nonescaping read-only borrowing. It does
**not** grant default construction, zero initialization, movement/ownership transfer,
arithmetic, equality, ordering, hashing, arbitrary methods/fields or `new T`.

The generic definition is checked against its formal contracts even if unused.
Each application independently checks argument eligibility, even if its body does
not exercise all guaranteed operations. A favorable concrete argument cannot grant
extra permissions to the generic body. A unique handle cannot satisfy a bare
copyable source parameter merely because a particular instantiation avoids copying.

Runtime metadata families have explicit parameter requirements; do not impose the
bare-source default on every runtime parameter or treat a missing requirement as
unrestricted permission. The physical API generator is not a substitute semantic
compiler. Native C++ traits/probes prove physical bindings, not source admission.

| Role/family | Existing argument constraints / separate operation requirements |
| --- | --- |
| Bare source T | copyable_value, with no implied arithmetic/default/move/equality permissions |
| vector<T> | Stored value capability; copyability depends on T. Individual copying/mutation/iteration operations need their own contracts. |
| hash<V,K=string> | V is storable; K is hashable and comparable. This is the actual registered parameter order. Copy operations depend on payload/key capabilities. |
| nullable<T> | Capability-based value storage, plus explicit forbidden compositions. Copy/extraction/take separately require the applicable copy contract; presence does not require copying/default construction. |
| result/result_or_false/result_or_bool<T> | Result payload boundaries use `result_payload`, not storage or copying alone. Each copying operation also requires effective copyability. Do not claim every native C++ Result<T> is source-admitted. |
| shared/unique/weak<T> | Ownership-target validation, not blanket pointee copyability. Handle copying and pointee copying are distinct; unique is move-only. |
| value<T> | Existing storage modifier and normalization rules; not a freely nestable new wrapper family. |
| Callable signatures | Parameter/return/passing-mode and capture/lifetime contracts, not an unconstrained T-shaped callback. |

Important evidence: the current physical vector/hash/nullable/Result recipes require
copy construction and assignment unconditionally. This is a physical coverage limit,
not proof that the source requires every runtime payload to be copyable. Extend the
same capability/lifecycle mechanism when implementing admitted noncopyable cases.
Do not invent user constraint syntax as part of the API generator work.

## Confirmed composition restrictions and supported distinctions

Sources: [type-use modifiers](../type_use_modifiers.md),
[ownership boundary](../../runtime/specs/spec.md#v02-ownership-boundary--2026-10-06),
[Result payload and take contracts](../error_values_and_take.md).

- Immediate ownership-on-ownership nesting rejects all shared/unique/weak pairs,
  after alias/role resolution. Named holders and containers of handles are not
  recursively banned; their own formation/operation contracts still apply.
- Nullable directly around explicit shared/unique/weak rejects as redundant. Handles
  already have their own absence contract. Do not confuse this with optional source
  class/interface types, whose required/optional identities remain distinct even
  when their physical storage is a shared handle.
- `value<shared<T>>`, `value<unique<T>>`, `value<weak<T>>` reject. Redundant
  `shared<Class>` and `value<Struct>` reject. Redundant value modifiers on registered
  scalar/enum/runtime value identities normalize before interning; they grant no
  new operations. `value<Class>` retains its actual value-storage semantics.
- `shared<void>` and `unique<void>` reject before interning, including unused/nested
  applications. The cited amendment explicitly leaves weak-target admission
  unchanged; do not silently generalize it into a new weak<void> rule.
- `result<?T>`, `result_or_false<?T>` and `result_or_bool<?T>` payload boundaries
  are explicitly admitted through nullable's result_payload capability. A successful
  outer Result may contain an absent inner nullable. `take` succeeds and copies that
  absence; outer failure preserves the old output. Do not flatten these states.
- No blanket admission of Result-of-Result, arbitrary nested nullable, result<void>,
  result<error>, arbitrary ownership payloads or wrapper-delegated indexing follows
  from those examples. Resolve each through existing formation and operation facts;
  unresolved combinations stay explicit gaps rather than inheriting C++ permissiveness.

## Wrapper policy: agreed arithmetic, remaining groups proposed

Separate three concepts: **payload-state carriers**, **ownership handles**, and
**storage modifiers**. Mixed/dynamic are tagged value families, not generic wrappers
that should be peeled until a scalar appears. Source required/optional objects also
retain their own prepared presence contracts.

### Payload-state carriers

One descriptor per admitted family records its payload identity, state observations,
checked extraction, construction, lifecycle and operation-specific policies. The
state vocabulary needs payload, null/absence, structured error, false sentinel and
true sentinel. A single `has_value`/boolean flag cannot represent every contract.

| Operation group | Existing rules to preserve; proposed shared implementation shape |
| --- | --- |
| Arithmetic / numeric ordering | For admitted combinations, require the selected payload(s), apply the ordinary payload operator rules, and return that operation's result. Non-value states throw; do not invent optional propagation or Result wrapping. Compile-time payload admission precedes runtime extraction. |
| Ordinary equality | Observe original states and apply the existing state-comparison policy before payload equality. Do not require payloads from failure/absence just to compare. Admitted numeric payloads promote; incompatible known scalar pairs reject, boxed scalar kind checks can throw. Two structured Result failures compare equal under ordinary equality, unlike strict identity. |
| Strict identity | A separate normalization/policy preserves scalar kinds and the specified error metadata. It is not ordinary equality with numeric promotion disabled by accident; bool sentinels versus payloads have their own documented normalization. |
| Conditions / logical operators | Absent nullable, Result error and false sentinel are false; true sentinel is true; present payload delegates only if its type has a condition contract. A present empty typed vector does not acquire truthiness. Lazy and/or remain compiler-owned. |
| Text concatenation | Preserve the bounded text contract: present payload renders by text rules; absent nullable and boolean sentinels render empty text; structured Result failure throws. A true sentinel renders differently from a present true bool payload. |
| take | State-dispatch with exact writable output contracts, owned snapshot/copy semantics and family-specific alternative output. A true result_or_bool sentinel returns true but does not assign the payload. No implicit move/borrow or catch-all behavior. |
| Coalescing / selection | Use usable-value state rather than truthiness. result_or_bool remains prohibited for ??, even in an unselected branch. Preserve exact admitted join rules and lazy evaluation; do not recursively normalize nested carriers away. |
| Compound/update | Preserve original destination, snapshot/validate/compute, then write back under its declared storage contract. No silent kind change, narrowing, move or wrapper-family change merely because a binary operation is admitted. |
| empty / isset / reset | Separate contracts. empty is collection-only and does not unwrap wrappers. Presence is not truthiness or payload availability in every family. Reset/clean/unset follow their existing ownership/state rules. |

Agreed arithmetic lifting boundary (2026-10-27): **one admitted carrier layer per selected operation**.
A nested payload stays a real typed payload; another operation on it requires its
own admission/extraction. This prevents automatic recursive peeling of, for example,
result<nullable<int>> into a plain number. This does not ban valid nested type formation or settle comparison/selection lifting.

Arithmetic examples covered by the decision:

- For an admitted `result<int> + nullable<int>` numeric operation, success would
  require both payloads and return the ordinary promoted integer; either non-value
  state would throw. Uniform lifting across all four carrier families is approved, pending implementation
  and normative admission migration; this is not a claim that it already works in the compiler.
- `result<nullable<int>>` with a successful empty nullable is neither outer error
  nor integer zero. The operation must not erase that distinction implicitly.
- `shared<int>` is not an arithmetic operand merely because int is numeric; checked
  access must be explicit. Locking a weak handle is also explicit.

### Ownership and value storage

Do not put shared/unique/weak into the payload-lifting rules. Observe shared/unique
presence without consulting pointee truthiness; access is checked. Unique movement
is explicit. Weak requires an explicit lock, obtaining a shared handle; it has no
implicit source condition/presence shortcut. Identity and ancestry conversions use
prepared ownership/target facts, never numeric promotion or pointee comparison.

`value<T>` resolves through the existing storage normalization before operator
selection. It does not add another failure state or inherit an automatic operator
forwarding policy. Containers and callables similarly need their own protocols,
not entry into a universal wrapper-peeling loop.

## How to keep generated combinations small

Proposed mechanism, extending existing metadata rather than creating another registry:

1. Validate formation and operation admission using existing semantic roles,
   capabilities and explicitly supported operation domains.
2. Describe each operand independently: plain, admitted carrier observation/checked
   projection, or ownership observation. Preserve the required evaluation order.
3. Normalize the payload operator through the current global resolver.
4. Reuse per-family/per-payload projection/observation functions and normalized payload
   implementations. Emit only demanded concrete signatures; do not publish all
   wrapper-family × payload-type × wrapper-family × payload-type combinations.
5. For equality/selection, reuse finite operation-specific state policies while
   retaining payload type/kind and error identity where required. Internal state
   observations must not box payloads, allocate per scalar, or erase owning lifetime.

This is a mechanism proposal, not a promise that every category pair is admitted.
It does not turn lazy expressions into eager free-function calls. Guarded extraction
must occur before later operand evaluation where the ordered-conversion contract
requires it; comparison snapshots retain their distinct sequencing rules.

## Discussion gates and backlog before an implementation goal

| ID | Topic / deliverable | Status |
| --- | --- | --- |
| W01 | Confirm one-layer checked lifting and ordinary payload result; no automatic optional/Result propagation | Agreed 2026-10-27; pending implementation |
| W02 | Decide whether to broaden cross-carrier arithmetic consistently or preserve only today's bounded source admissions | Agreed 2026-10-27: uniform arithmetic across all four families for otherwise admitted numeric payload pairs |
| W03 | Confirm reuse of existing state-aware equality, identity, condition, text, take and coalescing policies | Equality/ordering and state-preserving strict identity agreed; existing truthiness and explicit conversion policy confirmed 2026-10-27 |
| W04 | Account for all formation rules in one prepared descriptor path: bare-source default versus runtime parameters, result_payload and forbidden compositions | Confirmed constraints; enforcement audit and metadata integration remain |
| W05 | Extend conditional lifecycle/operation capabilities, prove an admitted noncopyable payload and reject unavailable copy operations | Pending design; do not conflate this with new user-authored constraint syntax |
| W06 | Define payload observation/projection transport and demand ownership in the existing C++ generator; no public templates or heap-interior references | Proposed implementation mechanism |
| W07 | Implement carrier families and lifted groups in small coherent batches; include nested successful absence, false payload/sentinels, errors and left-to-right failures | Depends on W01–W06 and core A/B prerequisites |
| W08 | Ownership recipes and application target facts; explicit weak lock, unique movement, presence and identity | Separate from payload lifting; dependencies shared with lifecycle work |
| W09 | Complete metadata/behavior/lifetime/allocation/publication/compile-cost matrix; retain all formation-negative tests | Required proof; batch compilation rather than per-row builds |
| W10 | Reconcile this backlog with core completion batches, then create an implementation goal with review/commit/push checkpoints | After discussion; no implementation goal started in this checkpoint |

Confirmed constraints and accepted arithmetic decisions are recorded above. Other
proposals remain unaccepted. Next discussion: equality, strict identity and ordering.

## Accepted arithmetic decision — 2026-10-27

Uniform arithmetic lifting applies across nullable, result, result_or_false and
result_or_bool, including their combinations with admitted plain operands. Validate
formation and the numeric payload operation first. Extract exactly one carrier
layer with checked payload access, then use the ordinary promotion/operation and
return its numeric result. Any non-value carrier state throws; do not propagate a
wrapper or treat absence/sentinels as numbers. Preserve ordered operand evaluation
and failure precedence. Inner carriers do not automatically unwrap; ownership
handles and value-storage modifiers are not additional payload-state carriers.
This decision changes the proposed admission scope; implementation and the source
semantic migration remain future work. It does not automatically authorize ordering,
wrapper equality or every specialized arithmetic operation on new payload domains.

## Equality and ordering discussion — existing contracts and proposals

Sources: `specs/expression_evaluation.md`, especially canonical-carrier completion,
cross-family ordinary equality, mixed numeric equality and strict identity amendments.

Agreed 2026-10-27, pending implementation: uniform ordinary equality across the four families: validate payload
comparison admission first; evaluate/snapshot whole operands left-to-right; compare
outer state tags, then invoke payload equality only for two present payloads. A plain
operand is a payload for this rule. Matching absence states compare equal, matching
false/true sentinels compare equal, and two structured Result errors compare equal
without comparing their metadata. Different non-value states and non-value/payload
pairs compare unequal. `!=` negates a successful equality result; invalid comparisons
remain errors. Existing narrow literal-sentinel comparisons retain their own rules.
This generalizes the shared mechanism without re-deciding truthiness or parsing text.
Missing payloads cannot rescue a statically invalid payload comparison.

Agreed 2026-10-27, pending implementation: ordering (`<`, `<=`, `>`, `>=`, and separately admitted `<=>`): checked
one-layer extraction like arithmetic, followed by the payload's admitted ordering.
A non-value state throws; do not sort null/errors/sentinels before numbers or strings.
Boolean/string/numeric admission remains owned by the payload operator group, and
NaN spaceship behavior remains separately governed. Uniform cross-carrier admission is approved within the payload operator's domain;
this is not a claim of implemented frontend support.

Agreed 2026-10-27: strict identity preserves carrier states too. A sentinel is
not strictly identical to a successful payload with the same scalar value. Compare
matching state categories first; compare present payloads with their strict identity
rules, without numeric promotion; compare structured errors using their identity
metadata. Matching states can still be compared across admitted carrier families;
this decision adds state preservation, not an unrequested exact-family requirement.
For pairs admitted by both operators, `a === b` must imply `a == b`; `!==` negates
strict identity. This intentionally supersedes the old source-visible normalization
that identified a false sentinel with a successful false payload. Literal null and
sentinel-specific comparison routes must be audited against this invariant rather
than used as a back door to the old normalization.

This is an approved semantic change awaiting normative-spec/implementation migration,
not a statement that the existing strict-identity helpers already obey it.

The compact mechanism remains separate per-operand state observation/extraction plus
one group policy and one normalized payload operation, not a generated implementation
for every pair of wrapper types. No implementation goal starts during this discussion.

## Agreed truthiness and conversion policy — 2026-10-27

Confirmed current condition rules: absent nullable, Result failure and false sentinel
are false; true sentinel is true; successful payloads delegate to their admitted
condition semantics. This does not mean success or presence: a successful zero is
falsey, while a true sentinel has no payload. Typed vector/hash payloads lack a
condition contract, so wrapping them does not enable conditions. Shared/unique test
handle presence; weak requires explicit lock. The user previously chose to preserve
existing truthiness. This does not create automatic arithmetic-style recursive
extraction; payload condition delegation needs its own explicit admission.

`empty` is collection-only and rejects these wrappers. Historical conditional-matrix
text saying empty observes wrapped string zero is superseded by
`specs/count_empty_isset_contract.md`; do not carry that stale sentence into metadata.

Current native cast lifting (`runtime/include/scpp/cast.hpp`) requires a successful
payload for ordinary required-value casts, then calls the payload cast. Even an
explicit bool cast requires a payload: a true result_or_bool sentinel can be truthy
in a condition yet fail a required bool cast. String conversion has an existing
exception: absent nullable and false/true sentinel states produce empty string;
structured Result error still throws. Explicit string cast formatting and text-context
formatting remain distinct where specified (notably floats). Native helper breadth
is not automatic source admission.

Accepted conversion policy, pending implementation and normative migration:

- Carrier to required scalar: one checked carrier layer, then the selected explicit
  scalar cast. No recursive peeling or treating error/sentinel states as numbers.
  Preserve existing explicit string-conversion exceptions as a named text policy.
- Same-family carrier conversion, where formation and payload conversion are admitted:
  preserve outer non-value state; convert a successful payload only. Do not turn a
  thrown cast failure into a Result failure, invent move permissions, or mutate the
  source. This is an accepted generalization, not a claim that every such conversion
  exists today.
- Plain payload into a carrier: retain admitted explicit/exact success construction;
  distinguish a wrapped bool false from a false sentinel. No implicit multi-step
  numeric conversion plus wrapping unless separately admitted.
- Different carrier families: no blanket conversion. Preserve only explicit supported
  paths; require deliberate error/sentinel handling rather than silently discarding it.
- Ownership conversions retain target/ancestry/transfer/locking contracts, separate
  from scalar conversion and carrier lifting.

The user confirmed that bool casts require a successful payload, even when the
carrier's condition is truthy, and that existing explicit string-cast exceptions
remain. The conversion policy above is accepted. Same-family conversion preserves
outer states; it does not authorize arbitrary cross-family or recursive casts.

Nested conditions retain existing admitted condition delegation. The arithmetic/cast
one-layer rule must not silently remove an already-admitted payload condition rule,
nor may native recursive helpers admit new source combinations. Noncopyable observation
must not acquire a copy requirement. No new decision is needed merely to preserve
those contracts.

## Remaining decisions before the implementation goal

The discussed wrapper arithmetic, comparisons, strict identity, truthiness and
conversion directions are settled. Existing formation, ownership, take, emptiness,
lazy selection and evaluation-order contracts remain constraints. Remaining work
must distinguish goal-scope choices from implementation design.

Two overall scope choices remain for discussion:

1. **Core completion denominator.** Proposed: the admitted core source contracts and
   their public-runtime dependencies from the completion inventory. Keep native-only
   fixed arrays, arenas, work queues, bitsets and async machinery as explicit follow-up
   demands unless a required core consumer needs them. Modules remain excluded by the
   user's instruction. This is not permission to omit a required dependency.
2. **Frontend adoption.** Proposed: this goal finishes the runtime/public metadata,
   shared native semantics and physical publication paths, with semantic spec updates
   and compatibility impacts recorded. Integrating all of these paths into compiler
   preparation/lowering is a separate goal. Runtime tests must not be represented as
   source-program support. Any existing frontend disagreement is documented explicitly.

Engineering checkpoints inside that goal, rather than new language decisions:

- Assemble existing formation/capability facts; fix touched drift without broadening
  source admission. Do not create a second semantic type registry.
- Prove state-preserving observations and checked projection with owned/borrowed
  transport appropriate to the existing lifetime contract.
- Extend conditional lifecycle before claiming general noncopyable container support.
- Design concrete callback signatures/capture transport at the callable batch; preserve
  existing invocation/lifetime semantics. Escalate only genuinely new semantics or a
  wide ownership refactor that cannot fit the approved batch.
- Audit literal sentinel comparisons against the newly accepted strict-identity
  implication; do not preserve an old special case that contradicts that decision.
- Keep demand-driven ownership, generated output freshness, focused headers and bounded
  compilation checks. Commit/push coherent tested slices; report measured allocations,
  safety and compile costs without claiming every combination was tested.

Suggested goal sequence remains core batches A through G, split into their documented
sub-slices. Include a review checkpoint after the first complete carrier path and before
ownership/callback expansion. The goal should finish only when each included contract
has a tested public path or an explicitly user-accepted exclusion. No goal is created
until the overall scope is settled and implementation is requested.

## Implementation goal activated — 2026-10-27

Both scope recommendations are accepted: native-only facilities enter only when an
included core contract needs them; complete frontend adoption is a separate goal.
The runtime-core completion goal is active. Begin with A1 ordinary numeric operations,
then follow the documented small-slice backlog, testing and committing/pushing each
coherent slice. Review the first complete carrier path before ownership/callback
expansion. Existing source/native admission gaps must remain explicit.

## Implementation checkpoint — C1 checked projection

The first shared one-layer arithmetic mechanism is implemented through family
`carrier` metadata and the global `carrier_projection` policy. Nullable and Result
resolve through it; the public-only execution/allocation/lifetime/publication proof
currently covers application-owned nullable<int64>. This is partial W06/W07 progress,
not completion of four-family behavior, conditional lifecycle or W09. The governing
arithmetic decision is now recorded in expression_evaluation.md. Equality, strict
identity, conditions and conversions retain their agreed separate state policies.
