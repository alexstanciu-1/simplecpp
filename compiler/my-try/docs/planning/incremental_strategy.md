# Incremental and v0.2 follow-up plan
Doc Status: planning

The incremental path is implemented through C++ fragment generation. Current
behavior belongs to [lifecycle](../lifecycle/incremental.md), [model](../architecture/MODEL.md)
and [preparation](../../04_analyze/prepare/README.md). The
[original discussion](../archive/incremental_strategy_history.md) is historical;
its superseded proposals and completed tasks are not an implementation backlog.

## Remaining incremental work

| Area | Deferred work / boundary |
| --- | --- |
| Change detection | mtime + size can miss equal-size edits with unchanged timestamps; notifications force reads. Stronger detection is deferred. |
| Error classification | Separate internal `RuntimeException` bugs from expected source diagnostics. Unexpected escaping exceptions already force a rebuild. |
| Error continuation | Review read/lexical failures and downstream boundaries case by case. Parsing already finishes other files then blocks preparation after join errors. |
| Lifetime | Explicit reverse unlinking is implemented. Review broader AST/scope cycles, obsolete source/module tombstones and strong observers; weak dependency storage is not required now. |
| Compaction | Sparse occurrence and duplicate-key positions grow without reuse; compact only with an observer/identity policy. |
| Token buffers | Appending and deferred remapping are implemented. Review cleanup scheduling, retained memory under repeated failures and latency after measurement. |
| Work selection | Selection still scans declarations and observed lookup pools. Consider changed-only inputs without losing missing/ambiguous-name dependencies. |
| Invalidation | Refine conservative record invalidation only after the existing behavior is well tested. |
| Output | Partition retained fragments into headers/implementation units, independent of source-file boundaries; preserve stable names and content-based publication. Current output is one `main.cpp`, without a disk writer/Ninja integration. |
| Validation | Rigorous incremental and native testing remains deferred. Focused tests do not establish exhaustive correctness or performance. |

For the later validation pass, compare fresh/incremental output and retained identities
across no-op notifications, moved declarations, body/signature/layout edits, missing
and ambiguous lookups, deletion/reappearance, failure/recovery and repeated increments.
Measure scanned/parsed files, prepared work and rerendered fragments separately from
output equality. Native concurrency/lifetime behavior requires native evidence.

## Agreed debts

- Keep general validation/STAN and reserved-name enforcement in the later pass.
  Ordinary parent lookup currently reaches LANGUAGE+RUNTIME without a special
  reserved-name rule; this is not unrestricted-shadowing language policy.
- Templates await review. Add explicit `use` captures to normal functions/methods
  in a future slice aligned with lambda capture semantics; retain the
  [v0.2 catalog item](../catalog/04_functions.md#deferred-v02-planning-explicit-captures-for-functions-and-methods).
- Reconcile nested declaration relationships such as extends/implements as their
  syntax arrives. Preserve file execution/namespace ordering rather than assuming
  all future declarations can always be grouped without consequence.
- Extend calls with typed target/argument forms when indirect/member/static calls
  arrive; add keyed container entries and append targets when needed. Workers must
  model lazy evaluation and distinguish assignable syntax from reference eligibility.
  The [expression](../archive/expression_model_audit.md) and
  [non-expression](../archive/non_expression_structure_audit.md) audits retain the
  detailed capability inventories; their migration prerequisites are historical.
- Review punctuation representation, inspection allocation, dispatch ordering versus
  lookup tables, native devirtualization and memory layout as optimization work.
- Keep narrowing `object_cast` even after matching type/kind guards for the current
  toolchain; revisit with the new S2S lowering. Traits currently group repeated
  helpers; reevaluate only when a better concrete representation is needed.
- Parked LLVM token-indexed name/template maps remain debt. Do not consolidate them
  now or add semantics independently; adapt LLVM to shared facts before resuming it.

Missing catalog kinds do not require speculative AST classes today. Each new slice
must preserve typed ownership, stable names, operation-specific traversal and the
single incremental processing path. The catalog remains the feature discussion queue.
