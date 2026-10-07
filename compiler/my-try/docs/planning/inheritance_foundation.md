# CH11 relationship and member-lookup foundation
Doc Status: planning

## Approved boundaries

The user approved the CH11 grouping and these initial boundaries:

- Class extends class; struct extends struct. Both may implement interfaces.
  Cross-kind base inheritance is not enabled by this agreement.
- Preserve existing dispatch: ordinary methods are not automatically virtual.
  Abstract/interface methods establish virtual contracts; implementations must
  satisfy those contracts. Virtual-by-default would require a language decision.
- Keep the shared type-declaration AST and separate property/method/constant
  categories. Relationship resolution and effective lookup belong to analysis.
- Simple trait use remains the intended trait scope; adaptation blocks are deferred.

These are approved directions, not implementation availability. Normative amendments
must accompany their respective executable slices.

## Inspection findings

`prepared_record::fields`, `static_fields` and `methods` currently describe declared
members. C++ declaration generation and static definitions consume those inventories;
constructor initialization and preparation equivalence also depend on them.
Appending inherited members would conflate lookup with physical layout/ownership.

Before this migration, method ownership and source-type registration assumed
a concrete class/struct record. Interfaces must not
be represented as constructible records merely to reuse method machinery.

Visibility previously used a `public_access` boolean on prepared fields, callables and
constants. Protected access requires an explicit three-state visibility concept,
used consistently by admission, incremental equivalence and C++ spelling.

## Approved prerequisite model slice — implemented 2026-10-07

Generalize member ownership into an abstract source member-owning declaration role;
keep concrete record identity and a distinct interface identity underneath it.
Share member declaration/signature infrastructure, not layout or construction facts.
Continue to use the existing concrete common AST node for both declaration kinds.
Traits will join composition separately; this is not approval to model them as bases.

Keep declared-member inventories local. An analysis-owned inherited lookup worker
walks prepared relationship links and returns the original declaration/facts. It
observes each consulted declaration/name pool, including misses, for incremental
invalidation. Do not retain a flattened duplicate member inventory by default.
Access checks use the selected member's declaring owner, not the receiver type.

Replace binary visibility with a shared public/protected/private enum. Do not add
a protected boolean alongside the old public boolean. Backend code reads settled
visibility and relationships; it does not resolve access or ancestry.

The prerequisite touches collection identities, source-type registration, method
ownership/signatures, preparation lifecycle/equivalence and backend consumers.
The user approved this coordinated migration. CH10 behavior is preserved through
focused class/struct and callable model proofs. Next add relationship admission and inherited lookup
with cross-file base edits, cycle/final errors, and fresh/incremental agreement.

Non-goals: derived/base conversions, inherited constructor execution, parent calls,
late-static binding, interface dispatch execution and trait composition in the
model-only prerequisite. Those remain the following CH11 groups. No native compiler
rebuild is implied by this plan.

The implemented prerequisite adds `collected_member_owner`, distinct
`collected_record`/`collected_interface` identities and `declaring_type()` backlinks.
Interface registration uses the existing source-type index, without record layout
or operation capabilities. At that checkpoint interface source parsing/preparation remained gated.
Prepared fields, callables and constants now use `member_visibility`; protected
source admission/access remained gated. Inherited lookup
described above follows in the checkpoint below.

## Relationship lifecycle and lookup — implemented 2026-10-07

The user approved independent relationship preparation within the existing scheduler.
`type_relationship_work` is attached separately to `collected_member_owner` and owns
`prepared_type_relationships`: an optional record base, direct interface identities
and finality. It shares declaration dependency, failure/retry and retirement machinery.
It does not own record layout or a flattened member inventory. Record work requires
relationship completion; relationship work never requires layout or method bodies.

Relationship work acknowledges the collected occurrence's parse revision separately
from the record declaration's dirty flag. A later layout settlement cannot consume
its pending source changes, and relationship completion cannot mark layout ready.
Equivalent facts retain their identity/version after no-op or body-only parsing.
Reset/deletion clears relationship facts and retirement severs dependency edges.

The existing parser now records named extends/implements headers, interfaces and
bodyless interface methods through the common declaration/callable syntax. Analysis
resolves source identities and rejects wrong kinds, cycles, final bases and duplicate
direct interfaces. This is not complete interface signature/override validation.

`Inherited_Lookup` observes every consulted property/method/constant pool, including
misses, and returns original declarations with their declaring owner. Local matches
hide inherited lookup candidates. Interface diamonds deduplicate the same declaration;
distinct inherited declarations remain separate candidates for later contract checking.
Record implements edges supply obligations, not concrete implementations.

`Preparation_Worker::prepare_relationships` exposes the analysis-only checkpoint;
ordinary preparation uses the same work. It publishes no executable prepared files.
At that checkpoint record/interface execution and C++ emission remained gated.

## Concrete execution checkpoint

The user approved bounded same-kind concrete inheritance, default-constructible bases,
and rejection of ordinary inherited-member redeclarations. The protected receiver
decision explicitly adopts C++ restrictions; broader PHP behavior is CH15 discussion
debt. The implemented contract and remaining gates live in
[the normative spec](../../../../specs/inheritance_v0_2.md) and
[the CH11 checkpoint](../catalog/11_inheritance.md#concrete-inheritance-and-protected-access--2026-10-07).
Base argument forwarding, parent calls, inherited data inputs and overrides remain
follow-ups. Existing zero-parameter public base constructors are supported; source
defaulted arguments still need the ordinary sequenced call path in a later slice.
