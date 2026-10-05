# 15. Accumulated blocking debt and final review
Doc Status: planning

[Catalog and workflow](README.md)

This is the final catalog chapter. Revisit the accumulated blocking design debt
after the feature chapters and the planned rest break, before declaring the overall
catalog pass complete. Keep this chapter last if more feature chapters are added.

Moving a debt here changes its review milestone, not its resolution status or the
language contract. Immediate correctness and memory-safety prerequisites still gate
the features that need them. This register is initially seeded with the explicitly
transferred mutation-policy debt; it is not an exhaustive migration of existing
debts. Other chapter blockers remain in place unless explicitly transferred.

## Blocking inventory

| Debt | Origin | Status | Final completion requirement |
| --- | --- | --- | --- |
| CH06-ITER-01 — mutation/replacement during iteration | [Chapter 06](06_containers.md#ch06-iter-01--mutation-during-iteration) | Open; blocks final catalog completion, not Chapter 06 design closeout | Agree the long-term policy, reconcile contracts, implement required changes and prove behavior/safety |

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
