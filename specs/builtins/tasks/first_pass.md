# Tasks builtins - first pass
Doc Status: normative

This page defines the first-pass Prism++ / Simple C++ tasks builtin intake scope.

The `tasks` family is runtime-owned and lives under `namespace scpp::tasks`.

Release status: v1-alpha / experimental.
The module is opt-in and intended for independent batch work.
Shared mutable object transfer, worker communication, and STAN thread-safety enforcement remain outside the alpha contract.
Reusable worker-pool backing is included only as a runtime-owned implementation substrate; it does not change the public `task_run` / `task_start` source signatures or expose raw thread management.

The first-pass source surface is strict PHP++ only:

- flat `task_*` source names
- direct strict registry mapping to `scpp::tasks::*`
- no legacy PHP compatibility wrapper layer

## Module policy

- module name: `tasks`
- current build policy: opt-in
- backend: C++ standard library threading, preferring `std::jthread` when available
- no external native package dependency is required
- disabled or missing module use must fail clearly during build/configuration
- disabled `task_*` helper calls should remain link-safe where the uniform runtime surface requires stubs
- invalid/null task batch handles must report task-specific diagnostics

## Header split

Tasks support should be kept out of generic `php.hpp` growth.

Use dedicated headers:

- runtime module header: `runtime/include/modules/tasks/tasks.hpp`
- public umbrella header: `runtime/include/scpp/tasks.hpp`

## Runtime organization

The shared runtime implementation should live in:

```text
runtime/include/modules/tasks/tasks.hpp
runtime/include/modules/tasks/tasks.cpp
runtime/include/scpp/tasks.hpp
```

Runtime namespace:

```cpp
namespace scpp::tasks {
}
```

Source-visible aliases:

```cpp
namespace scpp {
using task_batch = tasks::batch;
using task_context = tasks::context;
using task_progress_info = tasks::progress_info;
using task_error = tasks::error;
}
```

## First-pass source surface

```text
task_run(items, workers, exec, index = null, result = null, error = null, timeout_ms = 0) -> result
task_start(items, workers, exec, index = null, result = null, error = null, timeout_ms = 0) -> task_batch
task_join(task_batch $batch) -> result
task_cancel(task_batch $batch): void
task_done(task_batch $batch): bool
task_status(task_batch $batch): string
task_progress(task_batch $batch): task_progress_info
task_set_status(task_context $ctx, string $status): void
task_set_worker_pool_size(workers): void
```

The exact generic type spelling for `items`, callback parameters, callback returns, and result collection is owned by the implementation/spec pass that wires callable lowering.

Conceptual worker callback shapes:

```php
function (T $item): R
function (T $item, task_context $ctx): R
```

The first implementation supports both worker callback shapes.

## Object surface

`task_batch`:

- runtime-owned background batch handle
- returned by `task_start`
- consumed by `task_join`, `task_cancel`, `task_done`, `task_status`, and `task_progress`
- not user-constructed in the first pass

`task_context`:

- runtime-owned worker callback context
- passed to worker callbacks that accept it
- exposes worker-safe operations such as status update and later dynamic submit
- not user-constructed

`task_progress_info`:

- small object linked to batch state
- exposes snapshot-style read access to runtime-owned counters
- first-pass fields/methods should cover total, completed, queued, active, errors, stop-requested, and status

`task_error`:

- small read-only error event object
- passed to the optional coordinator-side error callback
- should include message, kind, input key/index, worker id when available, timeout flag, and source location when available

## Behavior

`task_run`:

- starts a bounded worker batch
- blocks until work completes or an unhandled error/timeout stops the batch
- returns a result collection
- creates at most `workers` native worker threads for the batch
- must not create one native thread per item
- may reuse a configured runtime-owned worker pool instead of creating batch-local worker threads

`task_start`:

- starts a bounded worker batch in the background
- returns a `task_batch` handle immediately
- live background batches are joined by generated main cleanup if the user does not join them explicitly
- may use the configured runtime-owned worker pool for the batch's internal worker loops while keeping the background coordinator handle semantics unchanged

`task_join`:

- waits for completion
- returns the batch result
- reports unhandled worker errors on the joining/main/coordinator thread
- should be idempotent after completion and may return a cached result
- returns a boxed `mixed` table for background vector/hash results in the current runtime representation
- rethrows captured background errors on repeated joins

`task_cancel`:

- requests cooperative cancellation
- wakes blocked workers
- does not force-kill native threads
- is a no-op after completion

`task_done`:

- returns whether the batch has completed

`task_status`:

- returns a short status string snapshot for the batch

`task_progress`:

- returns a `task_progress_info` object linked to the batch

`task_set_status`:

- updates the batch status from a worker context
- copies or atomically replaces the short status value

## Result placement

Default result placement:

- vector input preserves numeric position
- hash input preserves key
- table-shaped mixed/dynamic input is accepted through runtime shape checks
- packed mixed/dynamic tables behave as vector-like input
- associative mixed/dynamic tables behave as hash-like input
- non-table mixed/dynamic input is rejected as an invalid task input transfer

Custom index behavior:

- custom index callbacks run coordinator-side in the first pass
- custom index creates an explicitly keyed result shape
- duplicate custom keys overwrite earlier values silently in the first pass; a warning surface is deferred

Custom result behavior:

- result insertion is coordinator-owned
- worker callbacks do not mutate shared result state
- custom merge callbacks are postponed; first pass uses optional keyable result targets
- result-target writes occur after worker execution has produced values or error-handler replacement values
- result targets may be pre-populated; existing values remain unless a produced key overwrites them
- nullable custom-index keys append when the key is `null`
- for vector result targets, appending is allowed and keyed overwrite is allowed for existing positions; sparse writes are rejected
- negative numeric vector result keys are rejected

## Error policy

Default behavior:

- first unhandled worker error stops the batch
- the runtime requests cooperative stop
- workers are joined
- the error is reported on `task_run` or `task_join`

Handled item errors:

- optional error callback runs coordinator-side in the first pass
- the callback receives the item and a `task_error`
- if the error callback returns a replacement result, the batch continues by default
- if the error callback itself errors, the batch stops and reports that error
- replacement-result error callbacks preserve the failed item key/index when using default placement
- replacement-result error callbacks use custom result placement when custom index/result target arguments are present
- if an error callback returns `void` for a non-void result batch, the failed item is omitted

Timeout:

- timeout is cooperative
- timeout should act per worker like an error/throw-like event
- timeout does not guarantee immediate interruption of arbitrary user code
- timeout is observed at runtime-controlled checkpoints, including after an item callback returns and before a worker takes another item
- a timeout discovered after an item callback returns is converted to `task_error` with `kind = "timeout"` and `timeout = true` when an error callback is present
- if no error callback handles the timeout, the batch requests cooperative stop, joins workers, and reports the timeout on `task_run` or `task_join`
- arbitrary running user code is not force-killed

## Thread boundary policy

The first-pass public API does not expose raw threads, mutexes, condition variables, or forced thread termination.

## Reusable worker-pool backing

The tasks runtime may keep a process-owned default worker pool for repeated task
batches and other thread-consuming runtime/compiler paths.

First-pass worker-pool contract:

- the pool is runtime-owned and is not a raw-thread API
- existing `task_run` and `task_start` source signatures are unchanged
- `task_set_worker_pool_size(workers)` sets the runtime-owned default pool
  keepalive target for later task batches; non-positive values disable pool use
  for future batches
- if no reusable pool is configured, task batches use the existing batch-local
  worker creation/join path
- projects may set `runtime.tasks.default_worker_pool_size` in `prism.json` to
  configure the process-owned default pool at runtime startup; non-positive
  values mean no configured startup pool
- `runtime.tasks.default_worker_pool_size` requires the `tasks` runtime module
  to be enabled and should be compiled into a project-local runtime artifact
  when nonzero so shared runtime caches are not polluted by project-specific
  worker counts
- a later `task_set_worker_pool_size(workers)` call overrides the startup
  keepalive target for the running process
- when a reusable pool is configured, task batches enqueue one closure per
  logical batch worker, not one closure per input item
- logical worker closures still pull items through the batch-owned queue/index
  state, preserving current result ordering, progress, cancellation, timeout,
  and error publication behavior
- the configured keepalive worker count is a target for reusable idle workers
- reducing the keepalive target must not interrupt a worker that is currently
  executing a live batch closure
- workers that become idle after a target reduction may retire instead of
  remaining alive
- setting the keepalive target to zero disables pool use for future batches and
  lets currently live workers finish before retiring
- nested task batches entered from a reusable-pool worker may fall back to
  batch-local worker creation to avoid pool starvation/deadlock
- shutdown is structured: runtime cleanup wakes idle workers and joins workers
  after queued/running closures finish

Mutable input/output transfer over threads is intentionally deferred.
For the first implementation, avoid adding a public mutable-item mode.
The later mutable/exclusive item model is expected to be a separate design project involving runtime ownership, allocator behavior, S2S lowering, and STAN guidance/enforcement.

Mixed/dynamic input is currently limited to runtime-recognized table shapes.
Broader transfer rules for nested dynamic values, runtime resources, and shared mutable objects are deferred.

## Performance policy

The tasks layer should stay light enough that it remains useful for performance-sensitive batch work.

First-pass expectations:

- `task_start` starts coordinator-owned background work and returns without waiting for worker item completion
- `task_join` is the blocking wait point for background batches
- worker callbacks do not hold the shared result mutex while running user code
- optional error handlers do not hold the shared result mutex while running user code
- result insertion locks are scoped to the smallest result-recording operation
- fixed vector result placement avoids a post-join sort in the implemented path
- fixed vector `void` result placement avoids per-item result mutex writes in the implemented path
- hash workers read prepared input slots by reference in the implemented path, avoiding an extra local key/item copy before callback dispatch
- numeric progress counters and worker index allocation use relaxed atomics in the implemented path; lifecycle flags remain separately synchronized
- background join result boxing moves freshly produced vector/hash result values where the implemented path owns the temporary result
- the runtime avoids exposing or requiring user-visible locks for ordinary result placement

Current validation includes a monotonic-time probe that checks `task_start` returns before sleeping worker items complete and that `task_join` observes parallel execution rather than serial item execution.

## Implemented and validated first-pass surface

The current implementation and focused task module test cover:

- explicit opt-in `tasks` runtime module plumbing
- link-safe disabled-module diagnostics for base, custom-index, and result-target `task_run` / `task_start` call shapes
- blocking `task_run` for vector and hash inputs
- background `task_start` plus `task_join` for vector and hash inputs
- conservative table-shaped mixed/dynamic input for base `task_run` and `task_start`
- `task_cancel`, `task_done`, `task_status`, `task_progress`, and `task_set_status`
- worker callbacks with and without `task_context`
- default vector position preservation and hash key preservation
- custom-index callbacks for vector/hash inputs with numeric and string keys
- duplicate custom keys overwriting silently
- pre-populated vector/hash result targets
- nullable custom-index keys appending into result targets
- handled worker errors producing replacement results for blocking and background result-target paths
- unhandled worker errors reporting on `task_run` or `task_join`
- repeated `task_join` rethrowing cached background errors
- cooperative cancellation with partial completed vector results
- cooperative timeout diagnostics for blocking and background batches
- catchable timeout replacement through `task_error`
- readable progress/status snapshots while live and after handled source-catchable background failure
- vector result-target contract errors for negative and sparse numeric keys
- a monotonic-time probe that rejects obvious serial execution or blocking `task_start`
- reusable worker-pool probes covering configured reuse, disabled fallback,
  runtime target reduction while live work completes, and benchmark-style
  repeated-batch timing with and without the reusable pool

Follow-up design must specify:

- how input items are copied, moved, frozen, borrowed, or otherwise isolated
- how `mixed` values cross worker boundaries
- what runtime resources are forbidden from crossing worker boundaries
- what STAN can advise about aliases, captures, and escaping values

Planning note:

- [specs/planning/threading_transfer_semantics_followup_2026_06_15.md](/home/alexv/__AI/simple_cpp/simple_cpp_02/specs/planning/threading_transfer_semantics_followup_2026_06_15.md)

## Contract narrowing

This first pass intentionally does not aim for a full general concurrency framework.

Non-goals:

- raw `std::thread` / `std::jthread` exposure
- user-visible mutexes or condition variables
- worker-to-worker communication channels
- forced native thread killing
- scheduler priorities
- event-loop or coroutine semantics
- async networking
- legacy PHP compatibility

## Minimum test matrix

- `tasks` module can be enabled explicitly
- missing/disabled `tasks` module fails clearly when `task_*` is used
- fixed vector batch preserves order
- worker count is bounded
- unhandled worker error reports on main/coordinator thread
- handled item error continues
- fixed `task_run` replacement-result error handler preserves the failed item key/index
- duplicate custom keys overwrite silently
- background `task_start` + `task_join` returns result
- `task_cancel` after done is no-op
- timeout reports through the saved runtime diagnostic path
- status/progress reads succeed while a batch is live
- status/progress reads succeed after failed background joins when the failure is catchable in strict source
- vector result-target key contract errors are reported clearly
