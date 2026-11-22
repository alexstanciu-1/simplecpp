# Publishing async calls across source files

Doc Status: planning

**Runtime prerequisite implemented.** The default v0.2 publication supplies a concrete
int64 task in its own header. The runtime archive owns frame destruction, exception
propagation and a cooperative loop for direct nested awaits. One waiting chain needs
one pending continuation and timer. A positive delay suspends the coroutine, and the
synchronous driver waits before resuming it. Independent runnable tasks, external wakeups,
cancellation and user-stored tasks are separate contracts.

The ordinary caller sees the blocking function signature. Coroutine units see the concrete
promise and await operations. Scheduler, clock and thread headers remain private to the
runtime object. Scalar, output and entry headers remain independent of async.
The archive acceptance fixture is in `tests/async_runtime/`. Earlier cross-file
experiments are historical comparisons using the legacy scheduler.

**First compiler slice implemented.** PHP and JS accept `async function answer(): int`;
C++ accepts `async int answer()`. All three use `await answer()`. The slice covers
no-argument free functions with int/int64 results. Direct local initializers, return
values and echo operands can consume the result. The straight-line producer body accepts
locals, timer statements, echo, throw and return. Task storage, general nested await
expressions, async methods and parameters are deferred.

The ordinary function symbol and dependency watches carry the async signature bit.
A synchronous caller sees the incomplete task declaration and passes the producer
address to one out-of-line runtime bridge. Async units see the concrete task header
and emit direct co_await/co_return. The app emits no task templates. The all-flavor
acceptance test is in `compiler/tests/async_native.py`.

Async exports include only the task forward declaration. Blocking consumers add the
wait bridge. Coroutine definitions include the promise header, and timer expressions
separately request the timer header. The native dependency test verifies the split.
When the panel PCH option is enabled for an async application, only coroutine-producing
units use the cached promise header. The timer and scheduler are outside that PCH.
The cache retains the existing compiler qualification, dependency tracking and locking.
