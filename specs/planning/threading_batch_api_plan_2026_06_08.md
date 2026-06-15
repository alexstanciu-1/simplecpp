# Threading Batch API Plan
Doc Status: planning

Date: 2026-06-08

## Purpose

Capture the first design direction for a lightweight cross-platform multithreading API for Simple C++ / PHP++ strict projects.

This note is planning only.
It does not change current language, runtime, or generator semantics by itself.

## Related Authorities And Context

Normative owners to define later:

- top-level user-visible threading/batch semantics under `specs/`
- runtime module contract under `runtime/specs/`
- PHP lowering and callable support rules under `generators/php/specs/`
- project build/runtime-module selection under `specs/project_build_v1.md`

Existing context:

- the runtime already uses C++ standard threading primitives internally in selected host code
- PHP++ strict authoring should remain explicit and should not expose full C++ thread machinery directly
- generated C++ remains a lowering/debugging artifact, not the user-facing source of truth
- existing strict builtin families use compact flat source names such as `fs_get`, `dt_sleep_ms`, `io_open`, and `regex_match`
- runtime-owned modules such as curl use typed handles/objects when the API has durable state

## Goal

Provide a small, fast API for running many independent items through a bounded number of worker threads.

The common scenario is:

1. the user has a `vector`, `hash`, or `mixed` collection of input data
2. the user chooses a worker count
3. workers consume items until all work is finished
4. each item is processed by a callback
5. results are collected into a return value
6. the default call blocks until complete
7. a background variant can return a batch handle while main execution continues

The API should be useful for performance work without making PHP++ users manage raw threads, mutexes, or condition variables.

## Native Substrate

The preferred implementation substrate is the C++ standard library:

- `std::jthread` when available
- `std::stop_token` for cooperative cancellation when available
- `std::mutex`
- `std::condition_variable`
- `std::atomic`

`std::jthread` does not provide a thread pool.
It starts and owns one operating-system thread.

The runtime should therefore create a small runtime-owned pool shape:

- create `N` worker threads once per batch
- feed many tasks through a shared queue
- avoid one native thread per input item
- join workers when the batch ends

If a supported toolchain lacks `std::jthread`, the runtime may use an internal RAII wrapper around `std::thread` while preserving the same public API.

## Core Design Direction

The first public surface should model a batch as a structured task group, not as raw thread management.

Preferred user-facing categories:

- blocking fixed batch, such as `task_run(...)`
- background fixed batch, such as `task_start(...)` plus `task_join(...)`
- later dynamic batch support, where workers may submit additional work through a restricted context
- status/progress snapshots for live batches
- optional execution time limits
- optional error handling callbacks

The public API should hide:

- native thread object lifetime
- locks
- condition variables
- raw result sharing
- unsafe thread termination
- general worker-to-worker communication channels

The first surface should avoid separate config/builder objects.
Prefer compact positional APIs for now, with the expectation that future named-argument support plus STAN validation can make call sites readable without multiplying function names.

During discussion, `parallel(...)` was used as shorthand for the batch/map operation.
To match the existing strict library surface, the proposed source family should use a compact module prefix.

Preferred family name:

- source names: `task_*`
- runtime module: `tasks`
- runtime namespace: `scpp::tasks`

Rationale:

- `task_*` matches the compact strict family style better than long `parallel_*` names
- the API models structured task batches, not arbitrary raw parallelism
- the name leaves room for future non-batch background jobs without inventing a second family
- the typed state object can naturally be called `task_batch`

Current compact public shape:

```text
task_run(items, workers, exec, index = null, result = null, error = null, timeout_ms = 0) -> result
task_start(items, workers, exec, index = null, result = null, error = null, timeout_ms = 0) -> task_batch
task_join(batch) -> result
task_cancel(batch) -> void
task_done(batch) -> bool
task_status(batch) -> string
task_progress(batch) -> task_progress_info
task_set_status(context, status) -> void
```

Later dynamic support may add:

```text
task_submit(batch_or_context, item) -> void
task_close(batch) -> void
```

## Language Surface Shape

The first-pass strict PHP++ surface should be flat and function-oriented, with typed runtime-owned objects only for durable state.

Proposed strict source names:

- `task_run(...)`
- `task_start(...)`
- `task_join(task_batch $batch)`
- `task_cancel(task_batch $batch)`
- `task_done(task_batch $batch)`
- `task_status(task_batch $batch)`
- `task_progress(task_batch $batch): task_progress_info`
- `task_set_status(task_context $ctx, string $status)`

Proposed later dynamic source names:

- `task_submit(task_batch|task_context $target, T $item)`
- `task_close(task_batch $batch)`

Proposed source-visible classes:

- `task_batch`
- `task_context`
- `task_progress_info`
- `task_error`

`task_batch` is the background batch handle.
It should be a runtime-owned object/handle and not a user-constructed data class.

`task_context` is passed to worker callbacks when the callback accepts it.
It should expose only worker-safe operations such as status update, stop check, and later dynamic submit.

`task_progress_info` is a small object linked to the batch.
It should expose snapshot-style read access while counters remain runtime-owned.

`task_error` is a small read-only object passed to the optional coordinator-side error callback.
It should carry enough structured data for ordinary error handling without exposing internal exception machinery.

Method-style accessors may be considered later, but the first-pass surface should prefer flat functions for consistency with existing builtin families.

First-pass `task_progress_info` shape:

```text
task_progress_info
  total(): int
  completed(): int
  queued(): int
  active(): int
  errors(): int
  stop_requested(): bool
  status(): string
```

First-pass `task_error` shape:

```text
task_error
  message: string
  kind: string
  key: mixed
  worker_id: int
  timeout: bool
  source_file: string
  source_line: int
```

Where unavailable fields should return neutral values rather than forcing nullable handling in the first pass, unless the owning spec later chooses nullable accessors.

Module visibility:

- module name: `tasks`
- likely opt-in for the first pass
- strict source names should map directly to `scpp::tasks::*`
- no legacy PHP compatibility surface is required for the first pass
- disabled-module calls should fail clearly during build/configuration

## Runtime Organization Shape

Follow the existing runtime module layout.

Proposed files:

```text
runtime/include/modules/tasks/tasks.hpp
runtime/include/modules/tasks/tasks.cpp
runtime/include/scpp/tasks.hpp
```

The public umbrella should stay tiny, matching `scpp/curl.hpp`, `scpp/datetime.hpp`, `scpp/fs.hpp`, and `scpp/regex.hpp`:

```cpp
#pragma once

#include "modules/tasks/tasks.hpp"
```

The implementation should live in `namespace scpp::tasks`.

Suggested C++ type names:

```cpp
namespace scpp::tasks {

class batch final;
class context final;
class progress_info final;
class error final;

} // namespace scpp::tasks

namespace scpp {

using task_batch = tasks::batch;
using task_context = tasks::context;
using task_progress_info = tasks::progress_info;
using task_error = tasks::error;

} // namespace scpp
```

This mirrors the curl module style where durable runtime-owned state lives under the module namespace and source-visible aliases are exposed under `scpp`.

Suggested strict symbol registry mappings:

```json
{
  "task_run": "tasks::run",
  "task_start": "tasks::start",
  "task_join": "tasks::join",
  "task_cancel": "tasks::cancel",
  "task_done": "tasks::done",
  "task_status": "tasks::status",
  "task_progress": "tasks::progress",
  "task_set_status": "tasks::set_status"
}
```

Later dynamic mappings:

```json
{
  "task_submit": "tasks::submit",
  "task_close": "tasks::close"
}
```

No first-pass `runtime/include/lang/php/php_tasks.hpp` wrapper is required if strict is the only exposed surface.
Add a PHP wrapper layer only if a future legacy compatibility surface is intentionally designed.

Because `tasks` is likely opt-in at first, the module should have clear disabled-module behavior.
Two acceptable patterns exist in the runtime today:

- build/configuration rejects the call because the module is not enabled
- module header provides `SCPP_HAS_TASKS` disabled stubs returning descriptive `error_t` values

For `tasks`, prefer build/configuration failure for missing module selection, plus runtime disabled stubs only if the build system needs a uniform include surface.

Header dependency posture:

- keep standard threading includes in `tasks.hpp` only when required for public type layout
- otherwise hide them in `tasks.cpp`
- prefer pimpl/shared internal state for `task_batch` if it keeps `<thread>`, `<mutex>`, and `<condition_variable>` out of most generated translation units
- expose only Simple C++ runtime types in public signatures: `int_t`, `bool_t`, `string_t`, `mixed_t`, typed containers, `shared_p<T>`, `result<T>` where appropriate
- keep raw `std::jthread`, `std::thread`, `std::mutex`, and queue internals out of generated-code-facing signatures

Likely internal type shape:

```cpp
class batch final {
public:
	shared_p<detail::batch_state> state;
};

class context final {
public:
	shared_p<detail::batch_state> state;
	int_t worker_id = int_t(0);
};

class progress_info final {
public:
	shared_p<detail::batch_state> state;
	// accessors read snapshots from state
};

class error final {
public:
	string_t message;
	string_t kind;
	mixed_t key;
	int_t worker_id = int_t(0);
	bool_t timeout = bool_t(false);
	string_t source_file;
	int_t source_line = int_t(0);
};
```

The final header may choose a stricter private-state representation, but the public surface should preserve the same ownership idea:

- `task_batch`, `task_context`, and `task_progress_info` are handles/views over runtime-owned batch state
- `task_error` is a copyable read-only event object
- worker pool internals stay private

## Fixed Batch Scenario

A fixed batch starts with a known finite collection of input items.

Default behavior:

- `vector` input preserves numeric position
- `hash` input preserves key
- `mixed` array/table input preserves the enumerable key when possible
- a custom index callback may choose the return key/index
- an optional result target collection may receive output values
- custom index callbacks run coordinator-side in the first pass
- duplicate custom keys overwrite earlier values silently in the first pass; a warning surface is deferred

Sketch:

```php
$result = task_run($items, 8, function (mixed $item): mixed {
	return process_item($item);
});
```

With custom indexing:

```php
$result = task_run(
	$items,
	8,
	function (mixed $item): mixed {
		return process_item($item);
	},
	function (mixed $item): string {
		return item_id($item);
	}
);
```

These sketches are not final syntax.
Callable support, strict-mode type rules, and module naming need separate specification.

Callbacks should preserve user types where possible.
The API should not force all callback signatures through `mixed`.

Preferred conceptual worker shape:

```php
function (T $item, task_context $ctx): R
```

The short form should also be considered:

```php
function (T $item): R
```

For fixed map-style batches, the default result shape can be derived from `R` and the input collection kind.

First-pass compact signature:

```text
task_run(
  items,
  workers,
  exec,
  index = null,
  result = null,
  error = null,
  timeout_ms = 0
) -> result
```

Where:

- `items` is the input collection
- `workers` is the bounded worker count
- `exec` is the required worker callback
- `index` optionally computes the result key/index
- `result` optionally supplies a keyable target collection to receive output
- `error` optionally handles item errors
- `timeout_ms` optionally sets a cooperative batch timeout, with `0` meaning no timeout

Future named arguments should make advanced calls readable:

```php
$result = task_run(
	items: $items,
	workers: 8,
	exec: $exec,
	error: $on_error,
	timeout_ms: 5000
);
```

STAN should validate supported names, order, callback arity, and omitted optional arguments once named-argument support exists.

## Callback Ownership Rules

The API should avoid asking users to make arbitrary lambdas "thread-safe" by hand.

Preferred split:

- worker callbacks run on worker threads
- coordinator callbacks run on one coordinator/runtime-owned thread
- mutable result state is owned by the coordinator

Worker callbacks may:

- read the item passed to them
- use local values
- use read-only captured data, if callable capture rules later support this safely
- return an isolated result value
- later, submit more work in dynamic batch mode through a restricted context
- check cooperative stop state

Worker callbacks should not:

- mutate shared PHP++ result state
- mutate shared non-thread-safe runtime objects
- depend on unsynchronized shared mutable captures

Coordinator callbacks may:

- compute or normalize result keys
- insert result packets into the final return value
- perform default result handling where one-thread ownership is required

This keeps result collection thread-safe by construction.

## Thread Boundary Rules

The first safe default should be copy/freeze oriented.

Values that cross into a worker by default should be limited to values the runtime can safely copy, freeze, or otherwise isolate.

Likely safe-by-default values:

- scalar values
- strings
- `null`
- typed containers whose element types are also safe to isolate

Likely restricted by default:

- mutable object handles
- references/aliases into shared state
- open files
- curl handles
- database handles
- other runtime resources with thread-affine or externally shared state

However, performance-sensitive workloads may need an explicit mutable item mode.

There is no first-pass user-facing option for this mode yet.
The intended direction is that any mutable item handling inside a worker should be invisible to the user-facing API unless a later design proves that an explicit surface is needed.

In a future internal/runtime mode, each input element may be transferred to exactly one worker with exclusive access for the duration of processing.
The worker may mutate the item without locks because no other thread, including the main thread, may access that same item while it is owned by the worker.

This supports expensive data sets where deep-copying each item before work and copying it back afterward would defeat the point of threading.

Tentative rule:

```text
default mode:
  worker receives isolated/copy-safe item

future/internal mutable item mode:
  worker receives exclusive mutable access to one item
  the owning collection/item slot is unavailable to other execution paths until the worker returns
  result placement remains coordinator-owned
```

This needs deeper design.

Potential STAN support:

- warn when mutable item mode is used with aliases that may still be reachable on the main thread
- warn when a worker callback captures or accesses the source collection while mutable item mode is active
- warn when a mutable item escapes into another background batch or shared global
- validate that each item slot is handed to at most one worker
- distinguish exclusive item mutation from shared result mutation

This would be advisory at first unless the language/runtime grows a stronger ownership model.
The runtime still needs its own safety rules for values that cannot be safely transferred even with exclusive item ownership.

For the first implementation, trust the user-facing callback code and return to this topic after the basic threading implementation exists.
Thread input/output transfer semantics should be handled as a dedicated follow-up, not solved prematurely inside the first API shape.

## Later Dynamic Batch Scenario

A dynamic batch lets a worker add more tasks while the batch is running.

This is useful for:

- graph traversal
- recursive filesystem walking
- crawlers
- dependency expansion
- queue-driven work
- producer-fed future `yield` style input

Dynamic batches should use an explicit context argument rather than exposing the full batch handle inside workers.

Sketch:

```php
$batch = task_start([], 8, function (mixed $item, task_context $ctx): mixed {
	if (has_child($item)) {
		task_submit($ctx, next_item($item));
	}

	return process_item($item);
});

task_submit($batch, $root);
task_close($batch);

$result = task_join($batch);
```

Completion rule:

```text
batch is complete when:
  input is closed
  queued task count is zero
  active worker count is zero
  pending submitted task count is zero
```

The runtime-owned queue and pending counter are the synchronization mechanism.
Workers may submit new tasks, but they do not share the return value.

## Status And Progress

Live background batches and long blocking batches should expose cheap runtime-owned status/progress snapshots.

The intent is communication from a worker to the observer without exposing arbitrary shared mutable state.

Initial useful fields:

- short status string
- known total item count, when fixed
- completed item count
- queued item count
- active worker count
- error count
- stop-requested flag

`task_progress(batch)` should return a small progress class linked to the batch.
The object may expose snapshot-style accessors while the underlying counters remain runtime-owned.

Worker-side status updates should go through the batch context.
The runtime should copy or atomically replace the status value so readers get a safe snapshot.

Sketch:

```php
$batch = task_start($items, 8, function (mixed $item, task_context $ctx): mixed {
	task_set_status($ctx, "processing");
	return process_item($item);
});

$status string = task_status($batch);
```

Status should be deliberately small.
It is not a general message bus.

## Background Batch Lifetime

Background batches must be structured.

If user code starts a background batch and never joins it explicitly, generated program cleanup must join or stop/join it before process exit.

The generated main wrapper should have a runtime guard equivalent to:

```text
try:
  run user entrypoint
catch:
  record runtime/user error
cleanup:
  handle all live background batches
```

The implementation may use C++ RAII rather than a literal `finally`.

Default cleanup policy:

- normal program end: join live batches
- runtime/user error caught by generated main wrapper: request cooperative stop, wake workers, then join
- manual cancellation: request cooperative stop, wake workers, then join

The first API should not expose unsafe per-thread kill.
Portable C++ has no safe thread kill, and killing a thread can leave runtime state, locks, files, or result structures corrupted.

## Cancellation

Cancellation should be cooperative.

Preferred model:

- batch has a stop-requested state
- workers periodically observe the state
- queue waits are interruptible by stop/wakeup
- dynamic submission fails or is ignored once stopping begins, subject to final spec
- cleanup requests stop before joining on error paths

Potential later policy knobs:

- join
- cancel then join
- cancel then join with timeout
- terminate whole process after a hard timeout

Do not add per-thread forced termination as a normal public API.

## Execution Time Limits

Batches should support an optional execution time limit.

The limit is cooperative, not a hard per-thread kill.
When observed by a worker, timeout should behave like a per-item worker error / throw-like event.

Preferred behavior:

1. runtime records a deadline for the batch
2. queue waits and worker loops observe the deadline
3. once the deadline expires, a worker observes a timeout event before starting or continuing an item
4. if no error handler converts the timeout, the batch requests stop
5. workers finish at safe observation points
6. the batch joins
7. the coordinator reports the timeout on `task_run(...)` or `task_join(...)`

Sketch:

```php
$result = task_run($items, 8, function (mixed $item): mixed {
	return process_item($item);
}, null, null, null, 5000);
```

With future named arguments:

```php
$result = task_run(
	items: $items,
	workers: 8,
	exec: $exec,
	timeout_ms: 5000
);
```

Timeouts should not guarantee immediate interruption of arbitrary user code.
They guarantee that the runtime creates a timeout event at observation points and no new work should be started after an unhandled timeout is observed.

## Error Handling

Worker callback errors need an explicit policy.

Default behavior:

- the first unhandled worker error is captured by the runtime
- the batch requests cooperative stop
- workers are joined
- the error is re-thrown or re-reported on the main/coordinator thread

This keeps the default behavior simple: if no error handler is provided, the user sees the failure at the blocking call or at `task_join(...)`.

Optional error callback:

- may be provided to customize per-item error handling
- should run on the coordinator thread by default
- may choose to convert an error into a result value
- may choose to continue, cancel the batch, or record a custom result shape

Sketch:

```php
$result = task_run(
	$items,
	8,
	function (mixed $item): mixed {
		return process_item($item);
	},
	null,
	null,
	function (mixed $item, mixed $error): mixed {
		return fallback_for($item, $error);
	}
);
```

The exact error value type needs later specification.

Initial error value direction:

- use a small runtime-owned error object/class rather than a loose hash
- include at least message, kind, item key/index, worker id if available, and timeout flag
- preserve the original runtime/source location when available
- keep it copyable/read-only from the user error callback
- pass it as the second argument to the coordinator-side error callback
- allow later fields without changing the basic callback shape

Open policy choices:

- first unhandled error stops the batch vs collect all errors
- handled item errors continue by default
- whether partial results are returned on handled errors
- exact final fields and method names for the error object

## Performance Requirements

The API must stay light and fast.
Threads are being used for performance, so the default path should avoid avoidable overhead.

Initial performance posture:

- one worker pool per batch is acceptable for substantial work
- no native thread per item
- one shared input queue
- result packets should be small
- result insertion should be coordinator-owned
- avoid broad locking around the user callback
- avoid unnecessary dynamic boxing when typed containers can preserve shape
- worker count should be bounded and explicit

Possible later optimization:

- reusable named/global worker pools for repeated batches
- sharded result sinks for very large result sets
- specialized typed paths for `vector<T>` and `hash<T>`
- bounded queues for backpressure

## Open Questions

Status as of the first implementation pass:

- Resolved for first pass: 1, 2, 3, 6, 7, 8, 9, and 10.
- Deferred by design: 4, 5, 11, 12, and 13.

1. Is the proposed runtime module name `tasks` final?
2. What callable/lambda surface is available enough for a first public API?
3. Which callbacks may run on worker threads, and which must be coordinator-only?
4. What values are allowed to cross a thread boundary in v1?
5. How should `mixed` values be copied, frozen, or rejected when sent to workers?
6. Should dynamic task submission be available in the first release or follow the fixed `task_run(...)` batch first?
7. How should partial results be exposed after cancellation or error?
8. Should background batches be registered globally, per runtime context, or per generated entrypoint guard?
9. What are the exact fields and methods for the progress class?
10. Should timeout remain the final optional positional argument, or should a second intuitive function name exist for timeout-heavy code?
11. How should per-worker timeout events be represented to the result collector?
12. What named-argument checks should STAN perform for `task_run(...)` and `task_start(...)`?
13. After the first threading implementation, how should input/output transfer over threads be specified?

First-pass resolutions:

- `tasks` is the module name and `task_*` is the strict source prefix.
- The first public callable surface supports `task_run(...)`, `task_start(...)`, `task_join(...)`, `task_cancel(...)`, `task_done(...)`, `task_status(...)`, `task_progress(...)`, and `task_set_status(...)`.
- Worker callbacks run on worker threads; custom-index and error-handler callbacks are coordinator-owned for the first pass.
- Dynamic task submission is deferred.
- Partial completed vector results are returned after cooperative cancellation; unhandled errors and timeouts report through `task_run(...)` or `task_join(...)`.
- Background batch state is runtime-owned and accessible through `task_batch` plus progress/status helpers.
- `task_progress_info` exposes `total`, `completed`, `queued`, `active`, `errors`, `stop_requested`, and `status`.
- Timeout remains the final optional positional argument for the first pass.

## Pre-Implementation Decisions

Current working decisions before implementation:

1. Use the C++ standard library as the native substrate, preferring `std::jthread` when available.
2. Use a runtime-owned worker pool per batch; do not create one native thread per item.
3. Keep the first public API compact: prefer `task_run(...)` and `task_start(...)` with optional positional arguments.
4. Avoid config/builder objects for the first surface.
5. Plan for future named arguments plus STAN validation to make advanced calls readable.
6. Keep general worker-to-worker communication/channels out of the first pass.
7. Result mutation is coordinator-owned by default.
8. Worker callbacks may be typed as `function (T $item, task_context $ctx): R`; they are not restricted to `mixed`.
9. Default thread-boundary behavior should use isolated/copy-safe values.
10. Do not add a first-pass public option for exclusive mutable item access.
11. Keep any mutable item handling inside a worker invisible to the user-facing API for now.
12. Explore STAN advisory checks for exclusive-access safety after the basic threading implementation exists.
13. Default error policy: first unhandled worker error stops the batch, requests cooperative stop, joins workers, and reports the error on `task_run(...)` or `task_join(...)`.
14. Optional error handler runs coordinator-side in the first pass and may convert an item error into a result value.
15. Handled item errors continue by default.
16. Timeout should act per worker like an error/throw-like event.
17. Default result shape preserves input position/key.
18. Custom index runs coordinator-side in the first pass.
19. Custom index creates an explicitly keyed result shape; duplicate custom keys overwrite earlier values silently in the first pass.
20. Optional result targets may be pre-populated keyable collections; produced values are inserted by coordinator-owned default placement.
21. `task_progress(batch)` returns a small `task_progress_info` class linked to the batch.
22. Worker errors are passed to the error callback as a small read-only runtime error object/class.
23. Background batch join should be idempotent and may return a cached result after completion.
24. Live background batches are registered for generated main cleanup.
25. Generated main cleanup joins live batches on normal exit and requests stop then joins on caught error paths.
26. Cancellation is cooperative; do not expose unsafe thread kill.
27. Cancel after done is a no-op.

## Next To Do After First Thread Implementation

Specify input/output transfer over threads.

Follow-up planning note:

- [specs/planning/threading_transfer_semantics_followup_2026_06_15.md](/home/alexv/__AI/simple_cpp/simple_cpp_02/specs/planning/threading_transfer_semantics_followup_2026_06_15.md)

This follow-up should decide:

- how input items are copied, moved, frozen, borrowed, or otherwise isolated
- what happens to large mutable values that are expensive to copy
- whether exclusive item ownership can be proven or only advised
- what STAN can detect about aliases, captures, escaping values, and source collection access
- how worker-returned values are materialized into the coordinator-owned result
- whether any source collection mutation is observable after a batch returns
- which runtime resources are forbidden from crossing thread boundaries
- how `mixed` values are represented safely across worker boundaries
- how task runtime failures such as timeout are represented to strict source `try/catch`, including whether `catch (Exception $e)` should catch runtime-owned task errors or whether a dedicated public error type is needed

## Non-Goals For First Pass

- exposing raw `std::thread` / `std::jthread`
- exposing mutexes or condition variables to PHP++ users
- forced thread killing
- shared mutable result writes from worker callbacks
- general worker-to-worker communication / channels
- scheduler priorities
- platform-specific thread controls
- coroutine or event-loop semantics
- async networking

## Suggested First Milestone

Define and implement a blocking fixed batch API first.

The smallest useful milestone is:

```text
task_run(collection, worker_count, callback) -> result collection
```

With default result placement:

- preserve vector index
- preserve hash key
- return one result per input item

Then add:

- background handle start/join
- cancellation
- status/progress snapshots
- execution time limits
- optional error handler
- dynamic submission
- custom coordinator merge, if later needed beyond result targets
