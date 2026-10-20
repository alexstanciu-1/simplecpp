# Closure-sized language repair goals
Doc Status: planning

## Objective and accounting

User-requested planning target: advance by at least three percentage points of the
original 64-issue inventory per implementation goal. Two fully closed original
issues equal 3.125 percentage points. The pre-batch baseline was 26 closed, one
partial (S09), and 37 other open issues: 40.625% fully closed.

Recent search and replacement goals completed six API signatures and made both
original S09 fixtures pass. The separately recorded S09-B1 runtime-call boundary
kept S09 partial, so those implementation gains did not change the closure metric.
Keep that history and the original denominator; do not split issues, hide new
blockers, or declare completion merely to meet the percentage target.

## Goal sizing

- Discuss a complete closure batch before authorizing its implementation goal.
  Agree semantics, ownership, runtime-change bounds, acceptance cases and exclusions
  for every selected issue, including known follow-on blockers.
- Target three original issue closures where practical, with two as the minimum
  progress objective. A third issue supplies useful scope margin; it is approved
  upfront, not added opportunistically during execution.
- Internal runtime probes, implementation steps, fixture repairs and regressions
  become checkpoints inside the same goal rather than separate user-facing goals.
  Keep commits divided by owner or coherent repair so the batch remains reviewable.
- A goal is complete only when its authorized objective is fulfilled. A new
  cross-owner redesign or unresolved semantic choice requires discussion; a numeric
  target does not authorize wider changes or justify silently dropping scope.
- Run behavioral and focused integration proofs on dev002. No automatic full
  operator matrix, native compiler rebuild or broader runtime rewrite.
- Report closed issues and percentage-point gain, partial work, newly exposed gaps,
  original fixtures passing, and remaining decisions. Count bookkeeping-only
  reconciliation separately from newly implemented functionality.

This is a batching target, not a guarantee that unknown architectural problems
can be solved without another discussion. Larger normal-discussion issues may
need a justified exception; explain it before creating a smaller goal where known.

## Completed first batch: compatibility and vector APIs

S09, V-R07 and V-H10 closed: three closures reached 29/64 (45.3125%), a gain of
4.6875 points. Two closures would reach 28/64 (43.75%), a gain of 3.125 points; this
is a progress checkpoint, not permission to leave a third authorized task undone.

| Issue | Closure work | Required discussion / proof |
| --- | --- | --- |
| S09 | Settle S09-B1 and complete the original false-branch fixture; existing six API signatures remain controls | Decide wrapper-to-payload runtime argument policy and its truthful owner. Do not invent a substr-only conversion or silently add flow narrowing. Verify states/errors, ordered argument snapshots and the unchanged original fixture. |
| V-R07 | Expose seven vector helper names across eight forms, including both compact arities | Agree generic type binding, reference mutation versus observation, fill-copy constraints, clear/capacity policy and negative-size errors. Probe providers first, then verify strict source behavior and production provider build. Keep legacy-profile fixture evidence separate. |
| V-H10 | Repair the closure-container negative test's masked assertion | Agree the actual unsupported storage boundary; reach that boundary with valid initialization and retain a typed callable-container positive control. Preserve the old failure evidence and keep fixture work in its own commit. |

All three were reviewed and authorized before implementation. The bounded copy
prerequisite received separate approval; see [closeout evidence](closure_batch.md).
V-E01's already-resolved environment status was not used to pad this batch.

## Candidate subsequent batches

These are an ordered discussion queue, not implementation authorization or closure
promises. Review contracts and feasibility before freezing each goal.

| Batch | Original issues | Reason to discuss together |
| --- | --- | --- |
| Regex contract and provider bindings — completed | V-R04, V-R09 | [Closeout](regex_replacement.md): 31/64 closed, +3.125 points. V-R05 callback ABI remains a separate discussion. |
| Public platform and compatibility aliases — completed | V-D12, V-D09 | [Closeout](platform_alias.md): 33/64 closed, +3.125 points. Qualified aliases preserve source types; platform/environment contracts verified on dev002. |
| Remaining boxed/selection operators | V-L09, V-L10 | Settle integer-kind bitwise/shift admission and homogeneous wrapper coalescing; separate implementation owners and focused state/effect proofs. |

V-E01 needs a separate evidence/status reconciliation: its ledger already says
resolved-environment but lacks the fully-closed repair status. Such a justified
closure is useful bookkeeping and should be labelled as such. It must not replace
planned compiler/runtime capability work merely to improve the displayed number.

After these batches, regroup the remaining normal-discussion items by dependency
and owner (layout declarations/queries, project/module integration, callable
boundaries, ownership/transfer policies). Do not pre-authorize architecture work
or assign misleading fixed throughput to those larger tasks.

## Approved decisions for the first batch — 2026-10-20

S09-B1 inspection confirms that ordinary `Result_Conversions` already supports
checked extraction at by-value argument boundaries. `Runtime_Call_Preparation`
currently rejects before `Call_Preparation::admit` can select that conversion.
Proposed policy: retain the exact-match phase unchanged; only when it has zero
matches, consider fully concrete, fixed-arity runtime contracts using identity or
one existing checked wrapper-to-exact-payload conversion for value inputs. Require
one candidate; ambiguous fallback candidates reject. References/observation stay
exact. Do not introduce generic inference through extraction, conversion ranking,
numeric promotion, chained conversion, variadic/default adaptation or flow narrowing.
Use ordinary conversion/capability and argument-emission owners after selection.
Probe runtime extraction first; prove errors, source preservation, ordered snapshots,
exact-match priority, ambiguity and the unchanged original false-branch fixture.
Any needed cross-owner redesign is a discussion gate, not automatically authorized.

V-R07 proposed contract: mutation helpers use writable exact vector references;
capacity uses the existing unary non-retaining observation mode. Resize and filled
infer one exact T, with fill values copied through existing capability rules.
Both clear names retain capacity. Reserve/explicit compact provide a capacity lower
bound without truncation; no-argument compact is a nonbinding shrink request.
Negative counts/capacities throw existing value_error without changing the vector
(ordinary argument evaluation still occurs before entering the provider). Test
scalars, strings, copyable containers and representative noncopyable/reference
boundaries, aliased fill snapshots, destruction/content preservation and production
provider linkage. No runtime change is presumed; isolate any provider gap first.

V-H10 proposed migration: replace the ambiguous untyped [] setup with an explicit
mixed-element container so rejection reaches unsupported closure boxing/storage.
Keep it negative for that actual reason. Add a vector<function<int()>> positive
control with append/invocation and an incompatible-signature rejection. Preserve
the old source/diagnostic in dated evidence; perform fixture migration separately
from the compiler/API commits. This does not ban supported typed callable storage.

The user approved this batch and its implementation goal on 2026-10-20. During
verification, explicit move into vector_resize exposed a provider-copy prerequisite:
transport can move a unique value, but the provider still needs copies of the fill.
The user separately authorized a bounded metadata/shared-preparation copy requirement.
`requires_copy` belongs to fixed value parameter contracts, survives generic
instantiation, and is checked using ordinary source-aware capability preparation.
It does not constrain inference or change runtime implementation. The concrete
provider header is the existing public `scpp/lang/php.hpp`; the lower support header
alone is not self-contained. See [batch closeout](closure_batch.md).
