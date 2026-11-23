# Publishing async calls across source files

Doc Status: planning

**Runtime prerequisite implemented.** The default v0.2 publication supplies concrete
tasks for the selected results in separate headers. The runtime archive owns frame destruction, exception
propagation and a cooperative loop for direct nested awaits. One waiting chain needs
one pending continuation and timer. A positive delay suspends the coroutine, and the
synchronous driver waits before resuming it. Independent runnable tasks, external wakeups,
cancellation and user-stored tasks are separate contracts.

The publication's `async_results` list selects concrete tasks. The generator resolves each
value's native type through the existing concrete-type contract. One recipe supplies
the value or void promise and the shared frame operations. The same plan generates the
compiler lookup from canonical result type to C++ task name and header. A function keeps
its existing return-type fact. Each non-void promise owns an initially empty payload.
Native compile-time checks require nonthrowing default construction, move construction,
move assignment and destruction. The return hook takes its argument by value, so the
source expression keeps its ordinary copy semantics. It moves that argument into the
promise. After checking for failure, await-resume moves the payload into the caller.
The source protocol consumes each task once. Blocking callers include only the wait
declaration for the selected result. The umbrella wait header remains available to C++.

### Compiler task lookup

Preparation and lowering both call `async_task()` on the resolved return type. A
missing binding rejects the signature during preparation. Runtime bindings come from
the generated publication registry. Enums and eligible inline structs supply their
canonical declaration key. A successful nominal binding belongs to that type generation;
layout invalidation clears it before signatures are checked again. The binding selects
the prototype, complete promise and blocking entry. Each suspending unit collects its
own application task demand and feeds the existing concrete-type adapter and recipe.

### Task-to-runtime boundary

The generator emits one implementation unit for each published task. Each task keeps its
typed promise, result and exception handling. Its implementation includes
`scpp/async/frame.hpp` for three operations on erased coroutine handles. Scheduling a
child requires an active loop. Final suspension schedules a saved continuation without
throwing. A synchronous wait drives a root to completion and restores the enclosing
loop on exit. Only the runtime implementation sees the loop, clocks and threads.

The `async_frame_boundary` test awaits again after both successful and throwing nested
synchronous waits and checks guard destruction. Its optional positive iteration count
measures nanoseconds per immediate child await, including frame lifetime and the return
handoff. For an external-link proof, compile generated `src/async_int64.cpp` with only
the public includes, remove `async_int64.cpp.o` from a temporary copy of the prebuilt
archive, then link the test object, standalone methods object and archive in that order.

### Async result categories and representative proofs

| Category | Samples | Status and owner |
| --- | --- | --- |
| Completion and trivial values | `void`, `bool`, `int64` | Published. Void omits storage. Scalars use the value recipe. |
| Owned runtime values | `string` | Published. Empty, inline and heap-backed strings cover both wait paths and throwing producers. |
| Published concrete collections | `vector<int>`, `hash<int,int>` | Published. Use the same move contract and their existing concrete identities. |
| Published carriers | `nullable<int>` | Published. The payload includes its presence state. Another carrier specialization needs its own publication and proof. |
| Boxed runtime values | `mixed`, `dynamic` | Published. Frame transfer preserves existing copy-on-write and shared-container semantics. |
| Application-defined values | `TokenKind` enum, inline `Point` | Enums and passive inline structs use the same application-owned task recipe, keyed by namespace and declaration. |
| Application-owned specializations and handles | `vector<Point>`, `shared<Point>` | Application vectors, hashes and explicit shared struct owners publish through the concrete result lookup. Vector and hash copies preserve element value semantics. Strong owner copies retain allocation identity. Weak struct results use the same task recipe. An expired observer retains its control block across await and locks to an empty owner. A default observer also locks to an empty owner and reports is_empty(). Class presence contracts remain deferred. |
| Inline arrays and values without an empty state | `fixed_array<byte,4>`, a record requiring constructor arguments | Require a returnable representation or construction-on-return storage. Raw C++ arrays cannot be returned by value. |
| Borrowed or execution-bound values | capturing callback, live yield cursor | Require an escape/lifetime or single-consumer contract first. Application publication alone cannot establish safety. |

Inline struct results use the existing eagerly initialized promise payload. Signature
preparation checks the direct fields once per prepared producer signature. Each field
must have an implicit empty default and an enum or runtime type already admitted as
an async value parameter or result. The aggregate has no base, interface, authored
constructor or destructor. This keeps starting and abandoning a task free of
user-authored payload lifecycle calls. Native recipe assertions still check nonthrowing
default construction, move construction, move assignment and destruction. A heap-backed
string field exercises ownership across suspension, copying and exception unwinding.

Application publication must connect resolved type identity, generated headers, affected
source dependencies and incremental retirement. The prebuilt runtime cannot compile a
task implementation that needs an application-defined payload before that payload exists.

The ordinary caller sees the blocking function signature. Coroutine units see the concrete
promise and await operations. Scheduler, clock and thread headers remain private to the
runtime object. Scalar, output and entry headers remain independent of async.
The archive acceptance fixture is in `tests/async_runtime/`. Earlier cross-file
experiments are historical comparisons using the legacy scheduler.

**First compiler slice implemented.** PHP and JS accept `async function answer(): int`;
C++ accepts `async int answer()`. All three use `await answer()`. The slice covers
free functions with published results and by-value default scalar parameters (bool, byte/uint8, int/int64, float/float64 and string), including trailing literal defaults.
The ordinary argument binder checks conversions. The blocking bridge transfers its owned string into the coroutine frame.
The frame keeps it alive across suspension and releases it on completion or exception.
An async void producer completes through an empty return or fallthrough. A standalone
await can discard any published result in synchronous and async bodies. Using a void result as
a value produces the ordinary void-call diagnostic. All task results use one scheduler
and the same generated implementation for continuation, frame destruction and exceptions. Direct local initializers, return
values and echo operands can consume the result. The producer body accepts braced if/else branches,
locals, timer statements, echo, throw and return. Task storage, general nested await
expressions, async methods, and reference parameters are deferred. In an async body, awaited calls
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

Async producers may await inside a try block and handle language exceptions with the
ordinary ordered typed catches. A handler can return, throw or rethrow. A subsequent
await may run after control leaves the handler. Suspension inside a handler and async
finally blocks are diagnosed during preparation. The existing task rethrows the saved
child exception at await-resume, preserving its identity for typed dispatch.

The ordinary function symbol and dependency watches carry the async signature bit.
For a producer declared with zero parameters, a synchronous caller sees the incomplete task declaration and passes the producer
address to one out-of-line runtime bridge. Async units see the concrete task header
and emit direct co_await/co_return. The app emits no task templates. The all-flavor
acceptance test is in `compiler/tests/async_native.py`.

For parameterized producers the ordinary argument binder handles conversions and argument
evaluation. Omitted arguments use the prepared literal and its conversion at the call site, including a call that omits every argument. Native signatures retain their full arity. Value parameters belong to the coroutine frame across suspension. A blocking
call requests an ordinary scalar-returning wrapper defined in the producer's unit.
A suspending call requests the task signature. Two bits on existing watches select the
required entries and retire unused wrappers as callers change. A blocking-only export
contains just the scalar signature. A task signature needs only the task forward declaration. Coroutine definitions include the promise header, and timer expressions
separately request the timer header. The native dependency test verifies the split.
When the panel PCH option is enabled for an async application, only coroutine-producing
units use the cached coroutine and exception headers. The timer and scheduler are outside that PCH.
The cache retains the existing compiler qualification, dependency tracking and locking.
