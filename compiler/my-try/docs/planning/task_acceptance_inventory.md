# Task acceptance inventory
Doc Status: planning

Current scope audit for S12/V-R02, 2026-10-21. Normative contracts are
[task first pass](../../../../specs/builtins/tasks/first_pass.md) and
[unordered publication](../../../../specs/builtins/tasks/unordered_publication.md).
The [owning goal report](async_tasks.md) records dated dev002 evidence and approvals.
This inventory does not replace those contracts or count metadata rows as execution proof.

## Installed source contracts

At `ac4d8cd85`, `runtime/metadata/modules/tasks.json` contains 399 free-function
rows across 21 names, plus seven progress methods; all callable IDs are unique.
Its SHA-256 is `0dffbfdba92041353c6d7b3d72157ca4963be7766d6c1a27254b2b400adfe740`.
Generic rows cover type families, not one concrete payload signature per row.

| API group | Metadata rows | Supported boundary | Evidence owner |
| --- | ---: | --- | --- |
| `task_run` | 184 | 84 vector, 92 hash, 4 mixed and 4 dynamic rows; worker context/error variants | `task_submission.py`, `task_error_submission.py`, cross-carrier and nullable-index suites, `task_boxed_submission.py` |
| `task_start` | 184 | Same input groups; transferable background callbacks, family-preserving handles | Same suites, callable transfer and lifecycle proofs |
| `task_run_publish` | 8 | Typed vector, optional work/publish contexts, handler omission/replacement, timeout and batch cap | Submission/error suites, coordinator runtime proof, lifecycle metrics |
| `task_run_publish_unordered` | 1 generic | Typed vector, one-argument value work and serialized publisher | Submission suite and runtime context/lifecycle proofs |
| `task_synchronize` | 1 generic | Zero-argument void callback in unordered work only | Source context guards and runtime nested/context restoration proofs |
| join/cancel/done/status/progress | 10 | Two handle overloads per name: mixed-backed and dynamic-backed batches | Boxed/lifecycle suites and runtime repeated joins |
| set status/pool size/publish try-lock | 3 | Existing worker context and process controls | Metadata/submission/lifecycle suites |
| Eight publication measurements | 8 | Existing process measurement contract | Metadata suite; actual capped publication in lifecycle suite |
| Progress methods | 7 | total/completed/queued/active/errors/stop-requested/status | Metadata and lifecycle suites |

Typed run/start rows include value/void workers, defaults, context callbacks,
pre-populated targets, inferred/custom placement, independent int/string hash keys,
nullable keys, cross-carrier targets and exact-result-or-void error handlers.
The generic transfer contract covers admitted owned payloads; it does not authorize
shared/resource captures. Boxed rows are deliberately the approved scalar-table
snapshot slice: default placement, matching-family results and null index/target
slots. They do not establish boxed custom-index/result-target support. Whether that gap is
in this goal or explicitly deferred is a pending user decision; the spec does not
clearly exclude it, so it is not classified as intentional by this audit.

Unordered publication intentionally excludes contexts, timeout, replacement handlers,
batching and hash input, as its own normative contract states. Source exception
syntax, general thread-safety analysis, shared mutable transfer, merge callbacks,
wider async methods/closures and async task storage remain separate scope items.

## Completion gates

| Gate | Evidence assessment / next action |
| --- | --- |
| S12 suspended await, sequencing, cleanup and restrictions | Closed by the source/runtime/retained publication evidence in the owning report. |
| 21 API names and runtime-owned types | Installed and audited; source proofs exist by the groups above. Generic domains are not claimed exhaustively tested. |
| Worker/coordinator error policy and transfer | Runtime and source checks prove the approved typed and boxed slices; capture restrictions remain unchanged. |
| Source timeout, active cancellation and shutdown | Proved with default prelowering on dev002. Cancellation's source test uses status plus a bounded delay; deterministic concurrency assertions remain in native proofs. |
| Module opt-in and immutable startup configuration | Proved in separate fresh compiler processes; metadata hot reload is rejected. |
| Boxed source construction/index usability | Bounded repair verified by 112 fixture-free source execution cases plus retained edits; see the approved boxed prelowering checkpoint. |
| Repeated-error source join | Real boxed worker/handler failures retain and rethrow on repeated joins in both pool modes under ASan/UBSan; source throw/catch transport remains explicitly separate. Expected source rejection is not an execution pass. |
| Boxed custom placement scope | Null index/target slots only; user decision pending on custom callbacks and prefilled targets. Do not close the goal by silently excluding them. |
| Retained rejection/repair/fresh parity | Existing task/boxed contract suites cover admission, replacement, type-family edits and recovery; new transport edits require focused retained checks. |
| Wider project workflow | `prism.json` default worker-pool configuration belongs to existing `bin/project_services.php`; this audit verifies compiler module requirements and runtime controls, not a fresh end-to-end project configuration proof. |

No full operator matrix or native compiler rebuild is part of this goal. Closing
V-R02 still requires reconciling the open source transport gates and the explicitly
bounded signature scope with the original acceptance contract; the issue remains
partial at this checkpoint.

## Boxed placement design notes — awaiting scope decision

Read-only inspection confirms that current boxed run/start overloads have literal
`null_t` index/target slots. `run_boxed_snapshot` assembles a fresh same-family table
from packed/keyed worker results. Therefore adding metadata alone cannot expose
custom placement; a runtime-owned placement path is required.

If the user keeps this work in the goal, the discussion should settle these cases
before implementation:

| Destination | Proposed contract to discuss |
| --- | --- |
| No target, custom index | Keep the input's boxed result family. Index callback receives the original snapshotted item, runs on the coordinator, and returns int/string or nullable variants. Null appends; duplicate keys overwrite in input order. |
| Same-family boxed target | Snapshot/validate its scalar entries before launching work, construct an independent result table, preserve prefilled entries unless overwritten. In particular, a dynamic target must not leak shared mutable caller state into background work. |
| Explicit typed vector/hash target | Reuse exact scalar worker-result/target compatibility and existing vector bounds/hash key rules. The explicit target owns the return shape; background typed targets use the existing mixed-backed batch convention. Avoid silently extracting arbitrary boxed worker results into typed slots. |
| Opposite boxed-family target | Requires an explicit policy decision; do not infer permission to cross mixed/dynamic container families merely from the target argument. |

Placement must consume success slots retaining original input identity, as the
existing typed custom-placement path already does. It must not index a compacted
result vector after void-handler omission. Reuse worker execution, coordinator
error dispatch, nullable-key handling, batch state and retention owners; this does
not justify a scheduler redesign. Source generic-contract/preparation and provider
transport should remain the existing owners.

Focused verification would cover packed/associative inputs, run/start, contexts,
replacement/omission, omitted-first-item identity, duplicate keys, nullable append,
prefilled values, invalid vector keys, target/input snapshots, key/handler failures,
pool/non-pool lifecycle and retained reject/repair/fresh publication. Use dev002
with default prelowering, preserve source errors and existing transfer restrictions,
and do not run a full operator matrix. These are proposed checks, not evidence of
support or an authorization to implement the unresolved contract.
