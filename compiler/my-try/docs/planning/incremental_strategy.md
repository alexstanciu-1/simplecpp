# Incremental and v0.2 follow-up plan
Doc Status: planning

The incremental path is implemented through C++ fragment generation. Current
behavior belongs to [lifecycle](../lifecycle/incremental.md), [model](../architecture/MODEL.md)
and [preparation](../../04_analyze/prepare/README.md). The
[original discussion](../archive/incremental_strategy_history.md) is historical;
its superseded proposals and completed tasks are not an implementation backlog.

## Top-priority v0.2 review: abstract-typed properties

Review properties declared as abstract classes or interfaces, including collection
elements/keys and trait-provided fields. Identify which genuinely store several
concrete roles and which erase a known specialization. Start with AST child/type
fields, collected-occurrence links, scope indexes, preparation work/dependency
records and C++ fragment records. Keep bodies as processing units, not symbols.

For each boundary, document the permitted concrete roles, ownership and nullability;
retain intentional polymorphism and narrow unnecessarily broad declarations. A
nullable concrete property represents absence, not multiple concrete object types.
Do not mechanically specialize every shared field or duplicate the symbol model.
Coordinate language implications with the
[inheritance-contract review](../catalog/11_inheritance.md#v02-contract-review-debt).
This is review debt; no property redesign is approved by this entry.

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

## C++ incremental follow-up

Deferred after the backend review (2026-09-29):

1. Replace full fragment-selection scans with explicit pending work from
   `preparation_changes`. Preserve scheduling for initial generation, cache recreation,
   missing fragments and failed-render retries, even without new semantic changes.
   Keep the current single-file output layout for this step.
2. Introduce retained output units with fragment membership, output/include dependencies
   and per-unit assembly/publication state. Rebuild only affected units; preserve
   unchanged file bytes/timestamps and remove obsolete generated files. Semantic
   dependency links do not replace output-unit dependencies. Partitioning and the
   disk writer are later implementation, not part of the current cache.

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
