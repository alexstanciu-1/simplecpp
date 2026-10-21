# Async suspension and strict tasks integration
Doc Status: planning

Active approved goal: S12 and V-R02. Baseline is 55/64 original issues closed;
neither issue is closed by this plan or by runtime-only probes.

## Contract and owners

- S12 follows `specs/async_await.md`: await suspends inside async functions;
  synchronous/top-level await waits. Shared callable/expression preparation owns
  validity and lifetime restrictions. C++ specialization owns concrete transport.
  Async arguments and captures must survive suspension; unsafe references reject.
- V-R02 follows `specs/builtins/tasks/first_pass.md` and
  `unordered_publication.md`. Install opt-in tasks through existing metadata and
  provider owners. Preserve transfer restrictions and boxed families. Background
  callbacks require owned storage; synchronous callback adapters are not evidence
  of background safety.
- Bounded runtime repairs are authorized. A wider scheduler or transfer-model
  redesign requires discussion. No full matrix or native compiler rebuild.

## Acceptance inventory

1. Runtime-first: existing async suite plus owned values, nested suspension,
   sibling scheduling, exceptions and destruction; tasks concurrency, callback
   placement, background ownership, repeated join, failure and publication guards.
2. S12: original strict 4242 fixture, nested value/void await, synchronous waits,
   ordered snapshots and suspension, error/cleanup, reference rejections.
3. V-R02 API inventory: task_run, task_run_publish, task_start, task_join,
   task_cancel, task_done, task_status, task_progress, task_set_status,
   task_set_worker_pool_size, task_set_publish_try_lock, all eight publication
   measurements, task_run_publish_unordered and task_synchronize. Include current
   documented callback forms, result targets and runtime-owned handle contracts.
4. V-R02 behavior: bounded concurrency, empty inputs, cancellation, timeout,
   repeated joins/errors, shutdown joins, coordinator/worker/publication contexts,
   nested context restoration and transfer rejection. Prefer synchronization
   assertions to elapsed-time assumptions.
5. Default-prelowered production cold/warm builds on dev002; audit template-free
   application inputs. Retained edit/reject/repair/replay must agree with fresh
   preparation and publication. Preserve historical failures and new blockers.

Use the shared 12-slot runner. Commit coherent verified work and push each commit.
## Verified prerequisite — 2026-10-21

The existing callback transport now handles fixed value parameter lists and void
results. Provider adapters capture an owned callable rather than borrowing the
compiled helper's stack. This establishes transport lifetime, not permission to
transfer arbitrary captures or mutable objects across threads. Reference parameters,
reference results, variadics and borrowed erased captures remain restricted.

dev002 evidence:

- `20261021-async-tasks-runtime-01`: two ASan/UBSan runtime probes passed, including
  the existing async suite, owned suspended arguments, exception cleanup, bounded
  worker overlap, background capture lifetime, repeated joins/errors and publication
  context restrictions. This is not compiler async/task admission.
- `20261021-task-callbacks-02`: five tasks passed, covering four default-prelowered
  cold/warm source programs plus four rejected callback contracts and retained
  repair/replay/edited-versus-fresh agreement. A stored callback executes on a worker
  after its creating source function and compiled operation helper return.
- `20261021-task-callbacks-collection-regression`: all 25 existing focused tasks
  passed. No full matrix or native compiler build was run.

[Evidence archive](async-tasks-prerequisite-evidence.tar.gz), SHA-256:
`391611a8d0afef4c2821f7ef894435f00b630e588c3d6b309297865eb88fb886`.
It preserves command logs, source fixtures, publication manifests and upload hashes,
including the first four-case callback run before recovery coverage was added.

## Approved async backend prerequisite

Coroutine headers contain templates and cannot enter strict application inputs.
Existing snapshot IIFEs also cannot simply acquire `co_await`: suspension must
belong to the enclosing async computation. The user explicitly authorized extending
the existing compiled boundary for async implementations and adding suspension-aware
expression sequencing. Preserve lazy branches, snapshot lifetimes and retained
invalidation; do not disable prelowering or create another source/semantic registry.

Current status: callback prerequisite verified. Async semantic/backend integration
and tasks module admission/transfer/shutdown contracts remain open. The original
ledger remains **55/64**; neither S12 nor V-R02 is closed.
