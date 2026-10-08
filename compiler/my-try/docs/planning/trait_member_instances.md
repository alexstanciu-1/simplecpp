# Consumer-owned trait member instances
Doc Status: planning

## Approved direction — 2026-10-08

Traits contribute ordinary instance/static methods, explicitly typed instance/static properties and constants
to classes and structs through simple `use A, B;` declarations. Reject same-category
collisions, including authored consumer methods replacing contributed methods.
Property/method/constant names remain separate categories. Adaptations,
lifecycle methods remain outside the execution slice. Abstract instance requirements
are distinct from concrete contributions and may be satisfied by a same-named method.

Nested use is now resolved by an analysis-owned transient DFS, before materialization.
It rejects active-path cycles, deduplicates visited origin identities across paths and
retains each intermediate origin in the consumer batch, even when it has no members.
Thus existing snapshot retirement also observes intermediate use/import edits. Each
origin still materializes directly into the final consumer, using its original scope.

## Abstract requirement separation — implemented

Consumer-owned bodyless contributions retain independent signature work and lexical
provenance. `Member_Inventory::requirements` exposes them separately from ordinary
methods; layout collision checks and normal method emission use the implementation
view. Type contracts retain `trait_requirements`, validate every exact signature and
visibility obligation, and select one abstract slot when no implementation exists.
Inherited lookup prefers implementations and falls back to an unresolved requirement.
Compatible same-name slots select the widest required visibility, then source order.

Backend enumeration emits selected abstract slots only, never both a requirement
and its implementation. Reference-cycle analysis connects interface and trait
requirements to selected implementations through one traversal. Existing private
collections own signature work and participate in reset/retirement; no requirement
AST, runtime trait base or duplicated implementation inventory was introduced.

The agreed coordinated prerequisite is complete. Authored, contributed and inherited
satisfaction, abstract propagation, reference-return cycles and signature/body-shape
edit/retry/repair/reset/fresh checks are in `tests/traits.php`. Dated evidence is in CH11.

The user approved consumer-owned instances after inspection established that one
source method owns one declaring type, prepared signature and prepared body, and
its expressions also own attached facts. Reusing those mutable attachments across
consumers would corrupt receiver, access and member-resolution facts.

## Ownership contract

- Preserve the authored trait AST and collected member identities as origins.
- A consumer owns its contributed instances separately from its authored inventories.
- Each working member has independent syntax, occurrence/scopes and preparation
  state, with an origin link for diagnostics and invalidation. A shallow AST copy is
  insufficient: parameters, defaults, body expressions and lexical scopes must not
  share mutable facts between consumers or with the authored trait.
- Lexical names/imports come from the trait source; receiver and member access use
  the consuming class/struct. These are distinct contexts, not one rewired global scope.
- Ordinary preparation, signature/body work, member access and C++ lowering remain
  shared. Traits are not runtime base classes and cannot be value/handle types.
- Trait edits invalidate instances; removed uses retire their semantic dependencies,
  fragments, occurrences and scopes through the existing ordered lifecycle.
- Retained token ranges and source locations must remain valid for all instance
  owners until retirement. Do not let source-token cleanup strand instance spans.
- Materialization is an explicit composition responsibility. Do not make parser
  syntax recognition resolve trait targets, make analysis call a backend, or mutate
  authored consumer membership to hide generated members.

## Implementation checkpoints

1. **Implemented:** shared trait declaration/use parsing and distinct collected and
   nominal source identity. Uses collect ordinary named type references. Reconciliation
   notices use-list edits; source methods retain original trait ownership. Normal
   execution never attaches record receiver/layout facts to authored trait bodies.
2. **Implemented:** consumer-owned instance storage and independent working syntax,
   occurrences and scopes. `Trait_Materialization` reuses the member parser through
   a private fragment collection and deep-copied token snapshot. Trait-local lexical
   context and consumer declaring ownership stay separate. Two consumers prepare
   method signatures/bodies independently through the ordinary worker, without
   changing origin syntax/facts or authored consumer membership. This is an explicit
   composition boundary, invoked by the coordinator for source `use` declarations.
3. **Implemented:** read-only `Member_Inventory` views feed observed local-member
   lookup, ordinary record layout/defaults, same-category collision checks,
   signature/body scheduling, virtual contracts and C++ artifact enumeration.
   Working methods emit into the consumer's implementation unit, not the trait's
   lexical source unit. Private collections participate in reference-cycle checks.
   No separate trait-method lowering or authored AST flattening is introduced.
4. **Implemented:** automatic source-use selection, incremental edit/removal/retry/reset
   integration and generated-program proofs. Analysis resolves targets on every
   preparation invocation; the coordinator materializes complete consumer batches.
   The successful parse join retires stale private collections before source scopes
   are disposed. Preparation reset and full reset include private syntax/work.

5. **Implemented:** static members and constants use the same consumer-owned graph.
   The fragment parser accepts constants with an explicit private registration scope,
   preserving lexical provenance without inserting members into authored inventories.
   The read-only constant inventory feeds eager scheduling and inherited-collision
   checks; ordinary constant preparation observes every consumer member scope.
   Static methods reuse demanded callable instances and ordinary C++ emission. Static
   properties have separate storage per direct consumer; inherited storage stays with
   that owner. Bare constant lookup follows the expression's lexical namespace scope,
   not the consumer selected for access/self. No trait-specific backend path is needed.

## Evidence

`tests/trait_model.php` proves source identities, grouped/qualified uses, unchanged
authored consumer inventories, retained method identity, use/body edits, malformed-use
recovery, removal, automatic consumer selection and fresh parsing. It does not by
itself prove generated-program execution or native compiler portability.

`tests/trait_instances.php` proves materialization into a class and a struct,
trait-local imported parameter types, independent receivers/private consumer access,
property initializer syntax, ordinary signature/body preparation, source-edit-safe
token snapshots and explicit per-instance semantic/frontend retirement. Property
layout/default preparation now follows ordinary record preparation. The extended
proof covers contributed-field reads, intra-trait and consumer calls, collisions,
same-named property/method coexistence, unsupported member rejection, reference-return
cycles and emitted method placement. `tests/traits.php` proves automatic composition,
no-op identity, failed-parse preservation, default edits, use/origin removal, retry,
repair, reset and fresh-output agreement. Private collections are not published as
additional source files. The expanded static-member proof covers both record kinds,
independent/inherited storage, late-static overrides, lexical constants, private access,
reference returns, variadics and constant/default edit recovery. Dated results live in
CH11; the compiler itself has not been rebuilt natively for this slice.

Field preparation temporarily selects the field occurrence's collection for source
spelling and restores it afterward; dependency/access ownership remains the consumer
record. This distinction is required for cross-file property defaults, not just bodies.
`Trait_Composition` owns selection/materialization/lifecycle sequencing. It invalidates
record membership through the analysis worker, retires private work with notifications,
then disposes occurrences. Deleted private sources evict backend fragments through the
existing backend cleanup. Successful publication acknowledges private change handoffs
alongside source collections. Merely attaching a new instance is not an incremental
update protocol; the standalone materialization API is for isolated model proofs.

The initial snapshot copies the full origin token buffer per instance, preserving
all retained indexes without AST clone dispatch or source-text regeneration. A compact
shared immutable provenance buffer is a later optimization, not a reason to share
mutable token rows. Live instances must be retired before their origin lexical
environment is disposed. Freshness uses origin/consumer input snapshots and lexical
scope identity. This conservatively rebuilds instances on any edit to either source
file, while identical inputs preserve identity. Targets are re-resolved every prepare,
so changed imports or namespace membership cannot reuse a cached selection. Narrower
member-level invalidation and compact provenance storage are later optimizations.
