# Closure-sized language repair goals
Doc Status: planning

## Objective and accounting

User-requested planning target: advance by at least three percentage points of the
original 64-issue inventory per implementation goal. Two fully closed original
issues equal 3.125 percentage points. The current baseline is 26 closed, one
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

## Recommended next batch: compatibility and vector APIs

Target S09, V-R07 and V-H10: three closures would reach 29/64 (45.3125%), a gain of
4.6875 points. Two closures would reach 28/64 (43.75%), a gain of 3.125 points; this
is a progress checkpoint, not permission to leave a third authorized task undone.

| Issue | Closure work | Required discussion / proof |
| --- | --- | --- |
| S09 | Settle S09-B1 and complete the original false-branch fixture; existing six API signatures remain controls | Decide wrapper-to-payload runtime argument policy and its truthful owner. Do not invent a substr-only conversion or silently add flow narrowing. Verify states/errors, ordered argument snapshots and the unchanged original fixture. |
| V-R07 | Expose seven vector helper names across eight forms, including both compact arities | Agree generic type binding, reference mutation versus observation, fill-copy constraints, clear/capacity policy and negative-size errors. Probe providers first, then verify strict source behavior and production provider build. Keep legacy-profile fixture evidence separate. |
| V-H10 | Repair the closure-container negative test's masked assertion | Agree the actual unsupported storage boundary; reach that boundary with valid initialization and retain a typed callable-container positive control. Preserve the old failure evidence and keep fixture work in its own commit. |

Review all three before starting the implementation goal. If S09-B1 needs a wide
redesign, revise the batch before authorization rather than promising its closure.
Do not use V-E01's already-resolved environment status merely to pad this batch.

## Candidate subsequent batches

These are an ordered discussion queue, not implementation authorization or closure
promises. Review contracts and feasibility before freezing each goal.

| Batch | Original issues | Reason to discuss together |
| --- | --- | --- |
| Regex contract and provider bindings | V-R04, V-R09; optionally V-R05 only if its ABI is resolved upfront | Reconcile filter replacement semantics and replacement-limit dispatch; keep callback ABI as a distinct decision. |
| Public platform and compatibility aliases | V-D12, V-D09 | Decide authoritative platform/environment contracts and the php::microtime compatibility surface, then prove admitted source paths. |
| Remaining boxed/selection operators | V-L09, V-L10 | Settle integer-kind bitwise/shift admission and homogeneous wrapper coalescing; separate implementation owners and focused state/effect proofs. |

V-E01 needs a separate evidence/status reconciliation: its ledger already says
resolved-environment but lacks the fully-closed repair status. Such a justified
closure is useful bookkeeping and should be labelled as such. It must not replace
planned compiler/runtime capability work merely to improve the displayed number.

After these batches, regroup the remaining normal-discussion items by dependency
and owner (layout declarations/queries, project/module integration, callable
boundaries, ownership/transfer policies). Do not pre-authorize architecture work
or assign misleading fixed throughput to those larger tasks.
