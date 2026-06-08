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

- blocking fixed batch, such as `parallel(...)`
- background fixed batch, such as `parallel_start(...)` plus `parallel_join(...)`
- dynamic batch, where workers may submit additional work through a restricted context
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

## Fixed Batch Scenario

A fixed batch starts with a known finite collection of input items.

Default behavior:

- `vector` input preserves numeric position
- `hash` input preserves key
- `mixed` array/table input preserves the enumerable key when possible
- a custom index callback may choose the return key/index
- a custom coordinator callback may customize how results are merged

Sketch:

```php
$result = parallel($items, 8, function (mixed $item): mixed {
	return process_item($item);
});
```

With custom indexing:

```php
$result = parallel(
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
function (T $item, parallel_context $ctx): R
```

The short form should also be considered:

```php
function (T $item): R
```

For fixed map-style batches, the default result shape can be derived from `R` and the input collection kind.

Tentative compact signature:

```text
parallel(
  items,
  workers,
  exec,
  index = null,
  merge = null,
  error = null,
  timeout_ms = 0
) -> result
```

Where:

- `items` is the input collection
- `workers` is the bounded worker count
- `exec` is the required worker callback
- `index` optionally computes the result key/index
- `merge` optionally customizes coordinator-side result merging
- `error` optionally handles item errors
- `timeout_ms` optionally sets a cooperative batch timeout, with `0` meaning no timeout

Future named arguments should make advanced calls readable:

```php
$result = parallel(
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
- submit more work in dynamic batch mode through a restricted context
- check cooperative stop state

Worker callbacks should not:

- mutate shared PHP++ result state
- mutate shared non-thread-safe runtime objects
- depend on unsynchronized shared mutable captures

Coordinator callbacks may:

- compute or normalize result keys
- merge result packets into the final return value
- perform custom result handling where one-thread ownership is required

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

In that mode, each input element is transferred to exactly one worker with exclusive access for the duration of processing.
The worker may mutate the item without locks because no other thread, including the main thread, may access that same item while it is owned by the worker.

This supports expensive data sets where deep-copying each item before work and copying it back afterward would defeat the point of threading.

Tentative rule:

```text
default mode:
  worker receives isolated/copy-safe item

explicit mutable item mode:
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

## Dynamic Batch Scenario

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
$batch = parallel_batch_create(8, function (mixed $item, parallel_context $ctx): mixed {
	if (has_child($item)) {
		parallel_submit($ctx, next_item($item));
	}

	return process_item($item);
});

parallel_submit($batch, $root);
parallel_close($batch);

$result = parallel_join($batch);
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

Worker-side status updates should go through the batch context.
The runtime should copy or atomically replace the status value so readers get a safe snapshot.

Sketch:

```php
$batch = parallel_start($items, 8, function (mixed $item, parallel_context $ctx): mixed {
	parallel_status($ctx, "processing");
	return process_item($item);
});

$status string = parallel_status_get($batch);
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

The limit is a cooperative batch-level timeout, not a hard per-thread kill.

Preferred behavior:

1. runtime records a deadline for the batch
2. queue waits and worker loops observe the deadline
3. once the deadline expires, the batch requests stop
4. workers finish at safe observation points
5. the batch joins
6. the coordinator reports a timeout error unless an error handler converts it

Sketch:

```php
$result = parallel($items, 8, function (mixed $item): mixed {
	return process_item($item);
}, null, null, null, 5000);
```

With future named arguments:

```php
$result = parallel(
	items: $items,
	workers: 8,
	exec: $exec,
	timeout_ms: 5000
);
```

Timeouts should not guarantee immediate interruption of arbitrary user code.
They guarantee that the runtime requests stop and no new work should be started after the deadline is observed.

## Error Handling

Worker callback errors need an explicit policy.

Default behavior:

- the first unhandled worker error is captured by the runtime
- the batch requests cooperative stop
- workers are joined
- the error is re-thrown or re-reported on the main/coordinator thread

This keeps the default behavior simple: if no error handler is provided, the user sees the failure at the blocking call or at `parallel_join(...)`.

Optional error callback:

- may be provided to customize per-item error handling
- should run on the coordinator thread by default
- may choose to convert an error into a result value
- may choose to continue, cancel the batch, or record a custom result shape

Sketch:

```php
$result = parallel(
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

Open policy choices:

- first error stops the batch vs collect all errors
- error handler can continue vs must cancel
- whether partial results are returned on handled errors
- how timeout is represented to the error handler

## Performance Requirements

The API must stay light and fast.
Threads are being used for performance, so the default path should avoid avoidable overhead.

Initial performance posture:

- one worker pool per batch is acceptable for substantial work
- no native thread per item
- one shared input queue
- result packets should be small
- result merge should be coordinator-owned
- avoid broad locking around the user callback
- avoid unnecessary dynamic boxing when typed containers can preserve shape
- worker count should be bounded and explicit

Possible later optimization:

- reusable named/global worker pools for repeated batches
- sharded result sinks for very large result sets
- specialized typed paths for `vector<T>` and `hash<T>`
- bounded queues for backpressure

## Open Questions

1. What is the final runtime module name: `parallel`, `threading`, `tasks`, or another name?
2. What callable/lambda surface is available enough for a first public API?
3. Which callbacks may run on worker threads, and which must be coordinator-only?
4. Should custom index computation run worker-side as a pure callback or coordinator-side for stricter safety?
5. What values are allowed to cross a thread boundary in v1?
6. How should `mixed` values be copied, frozen, or rejected when sent to workers?
7. Should dynamic task submission be available in the first release or follow the fixed `parallel(...)` batch first?
8. What should happen when a worker callback throws or triggers a runtime error?
9. How should partial results be exposed after cancellation or error?
10. Should background batches be registered globally, per runtime context, or per generated entrypoint guard?
11. What is the exact status/progress snapshot shape?
12. Should timeout remain the final optional positional argument, or should a second intuitive function name exist for timeout-heavy code?
13. Should the optional error callback run only on the coordinator thread?
14. Can an error callback continue the batch, or does any worker error always stop the batch?
15. What named-argument checks should STAN perform for `parallel(...)` and `parallel_start(...)`?

## Pre-Implementation Decisions

Current working decisions before implementation:

1. Use the C++ standard library as the native substrate, preferring `std::jthread` when available.
2. Use a runtime-owned worker pool per batch; do not create one native thread per item.
3. Keep the first public API compact: prefer `parallel(...)` and `parallel_start(...)` with optional positional arguments.
4. Avoid config/builder objects for the first surface.
5. Plan for future named arguments plus STAN validation to make advanced calls readable.
6. Keep general worker-to-worker communication/channels out of the first pass.
7. Result mutation is coordinator-owned by default.
8. Worker callbacks may be typed as `function (T $item, parallel_context $ctx): R`; they are not restricted to `mixed`.
9. Default thread-boundary behavior should use isolated/copy-safe values.
10. Add an explicit mutable item mode for performance-sensitive cases where each worker gets exclusive mutable access to an item.
11. Explore STAN advisory checks for exclusive-access safety before making mutable item mode broad.
12. Default error policy: first unhandled worker error stops the batch, requests cooperative stop, joins workers, and reports the error on `parallel(...)` or `parallel_join(...)`.
13. Optional error handler runs coordinator-side by default and may convert an item error into a result value.
14. Default result shape preserves input position/key.
15. Custom index creates an explicitly keyed result shape; duplicate key behavior must be specified before implementation.
16. Background batch join should be idempotent and may return a cached result after completion.
17. Live background batches are registered for generated main cleanup.
18. Generated main cleanup joins live batches on normal exit and requests stop then joins on caught error paths.
19. Cancellation is cooperative; do not expose unsafe thread kill.
20. Cancel after done is a no-op.

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
parallel(collection, worker_count, callback) -> result collection
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
- custom coordinator merge
