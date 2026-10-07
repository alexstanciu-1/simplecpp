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
or operation capabilities. Interface source parsing/preparation remains gated.
Prepared fields, callables and constants now use `member_visibility`; protected
source admission/access remains gated until ancestry facts exist. Inherited lookup
described above is the next slice, not part of this checkpoint.
