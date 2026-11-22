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
free functions with int/int64 results and zero or more required by-value int/int64 parameters. Direct local initializers, return
values and echo operands can consume the result. The producer body accepts braced if/else branches,
locals, timer statements, echo, throw and return. Task storage, general nested await
expressions, async methods, reference parameters and defaults are deferred. In an async body, awaited calls
may appear in a chain of && or || used as a local initializer, echo operand, return
value or branch condition. Both sides are prepared and watched. The emitted native
logical operator evaluates its RHS only when needed.

Inside an async function, a direct await can also supply an if/elseif condition or the
right side of a plain local assignment. These forms use ordinary truthiness and assignment
conversion. A standalone `await work();` resumes after the child completes and propagates
a child exception even when the caller discards its result.

Async producers can use ordinary statement calls, local compound assignments and
increments/decrements inside braced while, do-while and for loops. Loop conditions
accept direct and short-circuit awaits. Break and continue use the ordinary resolved
loop targets. Locals that survive suspension live in the native coroutine frame.
A terminal infinite loop still produces a coroutine even if its body never awaits.

The ordinary function symbol and dependency watches carry the async signature bit.
For a zero-argument call, a synchronous caller sees the incomplete task declaration and passes the producer
address to one out-of-line runtime bridge. Async units see the concrete task header
and emit direct co_await/co_return. The app emits no task templates. The all-flavor
acceptance test is in `compiler/tests/async_native.py`.

For parameterized producers the ordinary argument binder handles conversions and argument
evaluation. Value parameters belong to the coroutine frame across suspension. A blocking
call requests an ordinary scalar-returning wrapper defined in the producer's unit.
A suspending call requests the task signature. Two bits on existing watches select the
required entries and retire unused wrappers as callers change. A blocking-only export
contains just the scalar signature. A task signature needs only the task forward declaration. Coroutine definitions include the promise header, and timer expressions
separately request the timer header. The native dependency test verifies the split.
When the panel PCH option is enabled for an async application, only coroutine-producing
units use the cached promise header. The timer and scheduler are outside that PCH.
The cache retains the existing compiler qualification, dependency tracking and locking.
