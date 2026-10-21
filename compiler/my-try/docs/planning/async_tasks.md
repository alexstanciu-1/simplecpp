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

## Verified async backend prerequisite — 2026-10-21

Shared preparation now distinguishes an async body's payload return from its
caller-visible, move-only internal task. Immediate await consumes that task;
reference parameters/results and source task storage remain rejected. Bare await
suspends inside async functions and waits in synchronous code. Explicit
`async_wait` remains synchronous in either context.

Default prelowering publishes coroutine bodies through the existing per-source
implementation grouping under `specializations/async/`. Application inputs retain
the template prohibition. Prepared suspension facts select an owned coroutine
scope for expression snapshots; ordinary expressions retain their existing scopes.
The runtime helper retains that scope until its immediately awaited child finishes.

dev002 evidence:

- `20261021-async-execution-07`: all 16 tasks passed. Includes the unchanged v0.1
  strict fixture (4242), value/void nesting, owned strings, ordered eager operands,
  lazy branches, containers, match, loops, conversions, explicit synchronous waits
  and scheduler identity across nested suspension. Production cold/warm builds
  preserve the application template prohibition and warm reuse. An ordinary-mode
  control agrees. Four production ASan/UBSan builds prove snapshot lifetime,
  container evaluation and exceptional cleanup.
- Retained rejection/repair/replay and async removal/payload edits agree with fresh
  in-memory artifact generation. This does not independently prove stale-file
  retirement in a previously published directory.
- `20261021-async-preparation-final`: shared facts and eleven invalid forms pass,
  with repair/replay and lexical helper shadowing.
- `20261021-async-runtime-final`: both runtime probes pass.
- `20261021-async-collection-regression`: all 25 focused tasks pass.

[Evidence archive](async-backend-evidence.tar.gz), SHA-256:
`4be8ac2f1677739852dc0139844aa040273e75743c186329f531e68119d9bf19`.
It includes earlier failed attempts, final logs, fixture sources, manifests and
the uploaded source hash manifest. No full matrix or native compiler rebuild ran.

Current status: callback and async backend prerequisites verified. Tasks module
admission/transfer/shutdown contracts and final issue closeout remain open. Named
free async functions are the bounded surface; async methods/closures are not added.
Existing default-prelowered throw/catch/finally transport remains separate V-D06
work; runtime exception propagation and cleanup are verified here without claiming
that syntax gap closed. The original ledger remains **55/64**; neither S12 nor
V-R02 is closed at this intermediate checkpoint.

## Verified tasks handle/control bindings — 2026-10-21

`runtime/metadata/modules/tasks.json` now selects the existing tasks provider,
`SCPP_HAS_TASKS=1` and pthread linkage through ordinary module build metadata.
It declares four runtime-owned shared types, seven readonly error fields, seven
progress observation methods and the sixteen fixed free functions:

- join, cancel, done, status, progress;
- set_status, set_worker_pool_size, set_publish_try_lock;
- all eight publication measurements.

The remaining five free names are **not yet admitted**: task_run,
task_run_publish, task_start, task_run_publish_unordered and task_synchronize.
Their callback/context/transfer contracts, result-placement forms, background
retention and shutdown integration remain required goal work. The test-only batch
factory proves transport of existing runtime handles, not source batch admission.

The existing concrete-member owner now transports zero-argument shared runtime
methods behind the compiled boundary. Shared methods with arguments remain an
explicit gap because the receiver check must precede argument evaluation. The
existing property owner adds bool/mixed reads; it does not add mutable bool/mixed
property transport. No new runtime behavior was needed for this checkpoint.

`20261021-tasks-metadata-04` passes all five tasks: four production cold/warm
programs and seven rejection/repair/replay cases. They cover repeated joins,
progress/status, context status updates, error fields, pool/measurement controls,
wrong handles, readonly writes and source construction rejection.
`20261021-tasks-member-regression-02` passes ordinary/concrete source lifecycle,
method/layout edits and warm reuse. Its first attempt exposed the obsolete
owned-callable rejection expectation from before the callback prerequisite;
the fixture now checks supported storage and invalid-return rejection/recovery.

[Evidence archive](tasks-metadata-evidence.tar.gz), SHA-256:
`5e516203b95c6f95530bf4669d97027ffe04c5f02562382ee52f8aaeee407d40`.
Historical attempts and upload hashes are included. Overall closure remains 55/64.

## Verified background shutdown prerequisite — 2026-10-21

All seven runtime background-start implementations now retain their batch through
the existing tasks provider until explicit join or process-entry cleanup. Explicit
join retires retention even when rethrowing a cached worker failure. Shutdown
drains outstanding batches, including batches created by work it is joining, then
stops the worker pool. It drains after failures and reports the first failure.

The existing C++ entry-support owner invokes shutdown after the application body
returns and on application exceptions. A body exception remains primary if cleanup
also fails. Default-prelowered and ordinary tasks programs share that entry owner;
there is no scheduler redesign or new source cleanup helper.

Dev002 `20261021-tasks-shutdown-entry-02` passes eleven tasks: existing handle and
contract checks plus production cold/warm return, worker-failure and body-failure
cases in both lowering modes. `20261021-tasks-shutdown-runtime-02` passes three
ASan/UBSan probes, including existing async/task controls and a latch-controlled
lost-handle lifetime test, nested cleanup, repeated shutdown and draining remaining
work after failure. This supersedes the pending shutdown prerequisite above.

[Evidence archive](tasks-shutdown-evidence.tar.gz), SHA-256:
`d3dab89e4eec3681bac03469d38fd4b44bca6e76602ef34360643953ff137437`.
Source hashes and earlier attempts are preserved. Source batch admission remains
open; neither original issue is closed here.

### Pending transfer-model decision

Closure-to-callable conversion checks borrowed lifetimes and copyability, then
stores only function-signature identity. Owned capture storage is not proof of
transferability: shared mutable handles and resources can still be owned captures.
After erasure, the signature cannot recover this distinction across copies,
returns or parameters.

The requested extension is a bounded transfer capability owned by existing
callable preparation and carried by callable storage/transport, checked at task
submission. Direct capture checks must observe anonymous-body dependencies;
typed callable transport must preserve the established capability. No general
thread-safety analysis or scheduler changes are proposed. This extends the
transfer model and awaits the user's decision under the goal's explicit redesign
boundary. Do not admit all owned captures or claim completion by rejecting every
stored callable.
