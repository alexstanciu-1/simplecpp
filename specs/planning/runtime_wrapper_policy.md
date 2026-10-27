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

## Proposed compact wrapper policy

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

Proposed lifting boundary: **one admitted carrier layer per selected operation**.
A nested payload stays a real typed payload; another operation on it requires its
own admission/extraction. This prevents automatic recursive peeling of, for example,
result<nullable<int>> into a plain number. This boundary is a proposal, not a new
ban on valid nested type formation.

Examples to settle explicitly before implementation:

- For an admitted `result<int> + nullable<int>` numeric operation, success would
  require both payloads and return the ordinary promoted integer; either non-value
  state would throw. The example proposes general lifting, not a claim that every
  cross-carrier source combination is already admitted.
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
| W01 | Confirm one-layer checked lifting and ordinary payload result; no automatic optional/Result propagation | Proposed; discuss first |
| W02 | Decide whether to broaden cross-carrier arithmetic consistently or preserve only today's bounded source admissions | Open language-scope decision; do not infer broad permission from the native lifted templates |
| W03 | Confirm reuse of existing state-aware equality, identity, condition, text, take and coalescing policies | Proposed preservation; any simplification changing these contracts needs separate approval |
| W04 | Account for all formation rules in one prepared descriptor path: bare-source default versus runtime parameters, result_payload and forbidden compositions | Confirmed constraints; enforcement audit and metadata integration remain |
| W05 | Extend conditional lifecycle/operation capabilities, prove an admitted noncopyable payload and reject unavailable copy operations | Pending design; do not conflate this with new user-authored constraint syntax |
| W06 | Define payload observation/projection transport and demand ownership in the existing C++ generator; no public templates or heap-interior references | Proposed implementation mechanism |
| W07 | Implement carrier families and lifted groups in small coherent batches; include nested successful absence, false payload/sentinels, errors and left-to-right failures | Depends on W01–W06 and core A/B prerequisites |
| W08 | Ownership recipes and application target facts; explicit weak lock, unique movement, presence and identity | Separate from payload lifting; dependencies shared with lifecycle work |
| W09 | Complete metadata/behavior/lifetime/allocation/publication/compile-cost matrix; retain all formation-negative tests | Required proof; batch compilation rather than per-row builds |
| W10 | Reconcile this backlog with core completion batches, then create an implementation goal with review/commit/push checkpoints | After discussion; no implementation goal started in this checkpoint |

Confirmed constraints are recorded above; proposals are deliberately not labeled
accepted decisions. The immediate discussion is W01/W02: should every otherwise
admitted numeric payload combination lift across the four state carriers, with checked
extraction and a plain numeric result, or should initial publication retain the narrower
current cross-family admission set? The mechanism can support either; the source
contract must make the choice before implementation.
