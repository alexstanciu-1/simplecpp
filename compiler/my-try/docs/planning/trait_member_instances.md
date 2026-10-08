# Consumer-owned trait member instances
Doc Status: planning

## Approved direction — 2026-10-08

Traits contribute ordinary instance methods and explicitly typed instance properties
to classes and structs through simple `use A, B;` declarations. Reject same-category
collisions, including authored consumer methods replacing contributed methods.
Property/method names remain separate categories. Nested uses, adaptations,
lifecycle methods, static members and constants are outside the first execution slice.

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
   execution rejects before trait bodies can acquire record receiver/layout facts.
2. **Pending:** consumer-owned instance storage, independent working syntax/occurrence
   materialization and source/consumer context separation. Prove two consumers of one
   trait can prepare independently, without changing origin syntax or facts.
3. **Pending:** observed contributed-member lookup, collisions, ordinary signature/body
   scheduling and C++ artifact enumeration. Reuse member categories and source-callable
   work; no separate trait-method lowering implementation.
4. **Pending:** incremental edit/removal/retry/reset integration and generated-program
   proofs, including private access, namespace/import differences and property defaults.

Do not enable executable trait composition based solely on checkpoint 1. Do not
add inert instance records merely to claim checkpoint 2 without materialization and
an ownership-isolation proof. The approved migration needs no further semantic decision
unless implementation exposes a new cross-owner redesign beyond this plan.

## Evidence

`tests/trait_model.php` proves source identities, grouped/qualified uses, unchanged
authored consumer inventories, retained method identity, use/body edits, malformed-use
recovery, removal and fresh parsing. It does not prove consumer instantiation,
executable traits or native compiler portability.
