# Unordered task publication
Doc Status: normative

`task_run_publish_unordered(items, workers, work, publish): int` accepts a typed
vector, a positive int worker limit, a one-argument work callback returning a value,
and a one-argument publish callback receiving that value. The publish return value
is ignored. No standard PHP compatibility is claimed.

Kept from task_run: bounded thread-backed execution, join before return/rethrow,
and cooperative dispatch stop on an uncaught worker error. Modified from
task_run_publish: each completed result invokes publish under a batch-local mutex
without waiting for earlier input positions. Dropped in this bounded operation:
context callbacks, replacement error handlers, timeouts, batching and hash inputs.

Success returns the number of published results. Invalid worker limits reject,
even for empty input. Empty input returns zero without callbacks. Work runs outside
the publication lock. Publication is serialized for this invocation only. Caller
must prevent concurrent runs from sharing an unprotected destination. Worker or
publisher failure joins all started work before rethrowing; already published
results remain visible. No publication-order or rollback guarantee is made.

Placement: runtime/include/modules/tasks/tasks.hpp, beside the existing task family.
Implementation reuses task_run worker lifecycle and adds one batch-local mutex;
no new thread pool. Generic callbacks require a header template. One instantiation
per callback pair adds compile work; no compile-time saving is claimed. Strict
runtime registry and shallow STAN signatures expose the operation; portable PHP
provides sequential execution with matching callback/error boundaries.


## Worker-side synchronized callback

`task_synchronize(callback): void` invokes a zero-argument callback synchronously
under the active `task_run_publish_unordered` batch's publication mutex. Work may
call it repeatedly before returning its final result. It shares serialization
with the final publish callback; it does not queue another task or expose a mutex.

It is valid only while executing that batch's work callback. Calls outside work,
inside a publish callback, or recursively inside a synchronized callback reject.
An exception releases the lock and restores the context before propagating.
Batch context is thread-local; nested batch execution restores the caller's context.
The callback must not wait for other work in its batch: it holds the publication lock.
Separate batches have separate locks, so the caller must still prevent overlapping
batches from writing the same destination. PHP invokes the callback sequentially
and enforces the same context boundaries.

This permits parser workers to register shared global symbols during parsing,
while keeping local parsing outside the lock. Consumers must wait for the complete
parse/collection join before resolving those symbols.
