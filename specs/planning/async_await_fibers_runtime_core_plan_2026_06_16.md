# Async/Await, Fibers, and Runtime Core Plan
Doc Status: planning

Purpose: capture an initial design direction for adding lightweight async/await support to Simple C++ language frontends, with fibers deferred and without conflating async/await with the existing thread-backed `tasks` module.

This document is non-normative planning material. It records the current discussion shape and should not be treated as language or runtime authority until promoted into the appropriate top-level, runtime, and generator specs.

---

## 1. Current Context

Simple C++ already has a `tasks` module for thread-backed parallel work. The current tasks surface is documented under `specs/builtins/tasks/` and implemented under `runtime/include/modules/tasks/`.

That existing module should remain conceptually separate from async/await:

- `tasks`: thread-backed parallelism and worker-style execution
- `async/await`: user-facing asynchronous control flow, stackless and task-like
- shared runtime core: scheduler, resumable handles, completions, timers, wakeups, and bridges between async continuations and task completions
- `fibers`: deferred stackful cooperative suspension/resume feature

The existing `tasks` module is an opt-in, v1-alpha bounded batch API. It exposes `task_run(...)`, `task_start(...)`, `task_join(...)`, `task_cancel(...)`, `task_done(...)`, `task_status(...)`, `task_progress(...)`, and `task_set_status(...)`. It is designed for independent batch work over typed vectors, typed hashes, and table-shaped `mixed` / `dynamic` inputs. It is not a raw-thread API and it is not currently a general promise/future abstraction.

The PHP++ generator is currently a deterministic structural lowerer, not a semantic compiler. Any async design should avoid requiring full type inference in the generator for the first implementation slice.

The async/await runtime base should be language-neutral enough to serve both current PHS/PHP++ and current JSS v1-alpha. Frontend-specific syntax and diagnostics should live in each frontend layer; scheduling, coroutine task state, timers, completions, and cross-thread wakeups should live in shared runtime code.

---

## 2. Design Goals

The first design should:

- preserve the existing `tasks` module as the thread-backed parallelism surface
- introduce a neutral runtime base for stackless async/await that does not depend on fibers
- keep cooperative concurrency distinct from true parallel execution
- allow thread task completions to wake async continuations later
- support a small, testable v1 before adding broad syntax and IO integration
- keep strict PHP++ values explicit at typed boundaries
- support a familiar stackless async/await language experience where appropriate, especially for JSS, while still avoiding accidental full JavaScript or PHP semantic imports
- keep the runtime core usable by PHS and JSS frontend adapters

The design should not require all concurrency mechanisms to be implemented at once.

---

## 3. Conceptual Model

The long-term architecture should look like:

```text
runtime async core
  scheduler
  resumable handle abstraction
  completion/result storage
  timer and wakeup sources
  optional cancellation state
  built-in async timer support

tasks module
  thread-backed parallel work
  bounded worker batches
  task_batch handles from task_start(...)
  blocking task_join(...) result retrieval
  optional future awaitable adapter into async core

async/await language/runtime surface
  stackless coroutine/task values
  await continuation scheduling
  optional bridges to task_batch completion and later fiber-aware operations

fibers module
  deferred stackful cooperative execution
  manual suspend/resume surface
  later scheduler integration through resumable handles
```

In this model, the shared runtime core is not itself the public `tasks`, `fibers`, or `async` module. It is an internal substrate used by those layers.

Potential internal names:

- `scpp::async_core`
- `scpp::scheduler`
- `scpp::concurrency`

Initial preference: `scpp::async_core` for implementation clarity, while keeping public module names separate.

For v1, `scpp::async_core` should be ready-by-default in the runtime when the target compiler supports the required C++20 coroutine baseline. Fibers should not be part of the v1 readiness bar.

---

## 4. JavaScript Async/Await Reference Model

JavaScript async/await is useful as a mental model for stackless async:

- an `async function` returns a `Promise`
- `await` suspends the async function until the awaited promise settles
- the underlying runtime resumes the continuation through the event loop
- the running thread is not blocked
- the function is effectively lowered into a state machine

Important takeaway for Simple C++:

```text
async/await should be cooperative and continuation-based.
It should not imply OS threads or parallel execution.
```

JavaScript promises should not be copied directly as the strict PHP++ or JSS surface, but the state-machine/continuation model maps well to C++20 coroutines and is the right user-experience target for both PHS and JSS async/await syntax.

---

## 5. PHP Fibers Reference Model

PHP fibers are useful as a mental model for stackful cooperative execution:

- a fiber wraps a callable
- `start()` enters the fiber
- `Fiber::suspend(...)` parks the whole fiber stack
- `resume(...)` continues from the suspension point
- fibers do not provide an event loop, async IO, promises, or scheduler by themselves

Important takeaway for Simple C++:

```text
fibers are a lower-level suspend/resume primitive.
async/await should be the friendlier application-facing model.
```

True PHP-like fibers require stackful runtime support. They cannot be fully simulated by plain C++20 coroutines without making every suspending call coroutine-aware.

Planning decision: fibers are deferred until after the lightweight async/await core is implemented and proven.

---

## 6. C++ Runtime Strategy

### 6.1 Shared Runtime Core

The shared base should include small abstractions similar to:

```cpp
namespace scpp::async_core {
	enum class state {
		pending,
		running,
		suspended,
		completed,
		failed,
		cancelled
	};

	class resumable {
	public:
		virtual ~resumable() = default;
		virtual void resume() = 0;
	};

	class scheduler {
	public:
		void enqueue(shared_p<resumable> item);
		void run();
	};
}
```

The exact C++ spelling should follow the existing runtime conventions when implementation begins. The planning point is that the scheduler should resume an abstract handle rather than care whether that handle is a coroutine, fiber, or task-completion continuation.

### 6.2 Async/Await Backend

Async/await should preferably use C++20 coroutines.

Conceptual lowering:

```php
async function fetch_count(): int {
	await async_sleep_ms(100);
	return 42;
}
```

could map to a C++ coroutine shape like:

```cpp
scpp::async_core::task<int_t> fetch_count() {
	co_await scpp::async_core::sleep_ms(100);
	co_return 42;
}
```

The runtime `task<T>` type would implement the C++ coroutine protocol through `promise_type`, `await_ready`, `await_suspend`, and `await_resume`.

Expected semantics:

- an async function starts or schedules according to the selected task policy
- `await` suspends only the current async function
- locals that cross `await` live in the coroutine frame
- runtime errors inside an async task propagate at `await`
- cancellation can be added later if the core leaves room for it
- async timers should be available as a default runtime capability
- no OS thread should be created merely because a coroutine awaits

### 6.3 Fiber Backend

True stackful fibers are deferred. When revisited, they will need a context-switching backend.

Possible implementation options:

- Boost.Context / Boost.Fiber
- platform APIs such as Windows fibers where appropriate
- `ucontext`-style APIs where available, with portability concerns
- a custom context-switch layer, only if the maintenance cost is accepted

Fibers should eventually expose stackful suspend/resume behavior. Suspension should be possible from inside nested ordinary calls within the fiber stack.

The future fiber implementation should integrate with the same scheduler by wrapping a fiber context in a `resumable` adapter.

### 6.4 Task Bridge

The existing `tasks` module should not become the async core.

Instead, tasks can later provide an awaitable adapter around `task_batch` completion:

```text
task_start(...) creates a background task_batch
  -> async adapter observes task_done(...) or waits off-scheduler
  -> task_join(...) collects the cached result/error without blocking the async scheduler thread
  -> adapter enqueues continuation into async core scheduler
  -> await resumes with result or rethrows failure
```

This would allow a future PHP++ shape such as:

```php
$batch = task_start($items, 4, function (int $item): int {
	return $item * 2;
});

$result mixed = await $batch;
```

without making all async work thread-backed.

Planning constraint: direct `await task_join($batch)` would be a blocking call wrapped in async syntax, not a true non-blocking await. A real task bridge needs either a scheduler-aware completion signal from the tasks coordinator or a small adapter that waits away from the scheduler thread and posts completion back into `async_core`.

---

## 7. Initial User-Facing Surface Options

The product direction is a stackless async/await language surface for PHS and JSS.

The safest implementation slice may still expose a library-level async surface before full syntax:

```php
$task AsyncTask<int> = async_spawn(compute_value);
$value int = await_task($task);
```

Then a later syntax slice can introduce:

```php
async function compute_value(): int {
	await async_sleep_ms(10);
	return 7;
}

$value int = await compute_value();
```

Potential strict-profile names are intentionally not finalized here.

The JSS-facing surface can use familiar script-style spelling, while PHS should keep strict-profile type and builtin naming conventions. Both should lower to the same runtime task/awaitable core. JSS should remain a typed script-style compiled frontend, not JavaScript compatibility mode.

Open naming questions:

- should public async values be named `AsyncTask<T>`, `Future<T>`, `Awaitable<T>`, or another local convention?
- should `task_batch` itself become awaitable, or should await require an explicit adapter such as `task_await($batch)` / `async_from_task($batch)`?
- should `await` be a keyword, a builtin-like form, or initially a function?
- should top-level entrypoints support `await`, or should `await` require an async function?
- should `async_sleep_ms` live in a future async surface, or should `dt_sleep_ms` gain an async sibling?

---

## 8. Performance and Memory Expectations

The shared async core should be lightweight. Most cost should come from the specific frontend using it.

Expected async core costs per suspended operation:

- small resumable handle or pointer
- completion/result slot
- scheduler queue node
- optional timer/wakeup record
- optional cancellation state

Target properties:

- O(1) ready-queue enqueue/dequeue
- no OS thread per async operation
- low lock overhead in single-thread scheduler mode
- thread-safe enqueue path for task completion callbacks
- timer support through a min-heap, timing wheel, or similarly bounded scheduler structure
- room for pooling operation records if profiling later shows allocation pressure

Expected relative profiles:

```text
C++20 coroutine async/await:
  low memory per suspended task
  good scaling for many waiting operations
  stackless, so only coroutine-aware functions can suspend

stackful fibers:
  higher memory due to per-fiber stacks
  suspension can happen from deep ordinary calls
  useful as an advanced primitive, not ideal as the default for every async function

thread-backed tasks:
  highest memory and scheduling cost
  real parallelism
  best for CPU-bound or blocking work moved to workers
```

A reasonable long-term expectation:

```text
10k-100k waiting coroutine tasks: plausible target
10k-100k fibers: not the primary target
thread tasks: bounded by worker pool size
```

These are planning expectations, not benchmark claims.

---

## 9. Suggested Implementation Phases

### Current Branch Status: `codex/async-await-core`

As of the initial implementation branch, the shared runtime prototype exists under `runtime/include/scpp/async_core.hpp` and is included by the default runtime aggregate. The prototype currently provides:

- `scpp::async_core::scheduler` with a ready queue, timer queue, `run()`, `run_until(...)`, and thread-safe external wakeups
- `scpp::async_core::task<T>` / `task<void>` as C++20 coroutine task values
- `scpp::async_core::sleep_ms(...)` / `sleep_for(...)` for timer-backed suspension
- `scpp::async_core::yield_now()` for cooperative rescheduling
- `scpp::async_core::ready_task(...)` / `ready_task()` for already-completed async values
- `scpp::async_core::spawn(task<T>&)` for starting child async work on the active scheduler
- `scpp::async_core::sync_wait(...)` for blocking a synchronous caller until a root async task completes

Native runtime coverage currently validates immediate completion, nested await, timer ordering, cooperative yield ordering, ready tasks, exception propagation, cross-thread wakeups, and missing-scheduler diagnostics.

The remaining major work is language surface integration:

- PHS/PHP++ now has an initial PHP-parser-compatible surface: `/** @async */ function f(): T { ... }` lowers to `scpp::async_core::task<T>`, statement-form `async_sleep_ms(...)` lowers to `co_await scpp::async_core::sleep_ms(...)`, ordinary `return` inside the async function lowers to `co_return`, and `async_wait(...)` lowers to `scpp::async_core::sync_wait(...)`.
- This PHS surface is intentionally first-slice and narrow. `async_wait(...)` is currently treated conservatively as `mixed` by STAN, so typed-boundary unwrapping such as assigning `async_wait(async_int())` directly into an `int` local remains future static-analysis work.
- JSS can parse familiar `async` / `await` spelling, but it currently lowers through PHS, so JSS syntax should not be enabled until the PHS/generator path has an accepted representation.
- The thread-backed `tasks` module remains separate; no task-batch await bridge has been implemented yet.
- Fibers remain deferred.

### Phase 0: Specification and Ownership

- create top-level planning and then normative spec draft for lightweight async/await semantics
- identify runtime module boundaries and public naming
- audit the existing `tasks` module API and implementation
- document `tasks` if current thread-backed behavior lacks sufficient user/operator docs
- decide whether C++20 is an acceptable baseline for coroutine-backed async
- identify PHS and JSS frontend requirements separately from shared runtime requirements

### Phase 1: Runtime Async Core Prototype

- add an internal scheduler/resumable/completion core
- add a simple timer or sleep wakeup source as ready-by-default async timer support
- add focused C++ runtime tests for enqueue, resume ordering, completion, and timer wakeup
- keep this independent of PHP++ syntax at first

### Phase 2: Coroutine Task Prototype

- add a C++ `task<T>` coroutine type
- support `co_await` on sleep and completed task values
- propagate returned values and runtime errors through await
- validate memory and allocation behavior with small stress tests

### Phase 3: PHP++ Library Surface

- expose a minimal strict-profile async API without new syntax where possible
- add generator/runtime symbol entries for the chosen functions/types
- validate with focused `.phs` samples and `scpp build` / `scpp run`

### Phase 4: PHS Async/Await Syntax

- add parser/generator support for `async function` and `await`
- keep lowering structural where possible
- add STAN checks for invalid await placement and async return contracts
- document the behavior in the appropriate top-level and generator specs

### Phase 5: JSS Async/Await Syntax

- define JSS async/await source semantics against the same shared runtime core
- keep JavaScript-like syntax familiar without promising full JavaScript runtime behavior
- validate equivalent runtime behavior across PHS and JSS samples

### Phase 6: Task Await Bridge

- adapt existing `task_batch` handles into the async core awaitable model
- avoid blocking the async scheduler thread while waiting for `task_join(...)`
- decide whether the bridge requires new task coordinator completion hooks or an external waiting adapter
- ensure task completion can safely enqueue scheduler continuations
- document whether `await $batch`, `task_await($batch)`, or the JSS equivalent is supported and what result/error/cancellation behavior means

### Deferred Phase: Fibers

- select and integrate a stackful backend
- expose fiber creation, start, suspend, resume, and state inspection
- integrate fiber resumables with the shared scheduler
- document differences from async/await and from PHP fibers where Simple C++ intentionally diverges

---

## 10. Open Decisions

- Is C++20 coroutine support acceptable across the supported compiler/toolchain matrix?
- Should the async scheduler be single-threaded by default, with explicit cross-thread enqueue support?
- What is the public distinction between `Task<T>`, existing task module types, `Future<T>`, and async task values?
- Should the existing `task_batch` type be awaitable directly, or should awaitability be opt-in through a separate adapter?
- Should top-level PHP++ code be allowed to use `await` through an implicit async main?
- Should JSS top-level `await` be supported immediately, or only inside async functions for the first slice?
- What cancellation model, if any, belongs in v1?
- How should runtime errors inside async tasks be represented before await?
- Which IO families should become async-aware first, if any?
- Does `tasks` need a scheduler-aware completion hook, or is an adapter thread/waiter acceptable for the first bridge?

---

## 11. Current Recommendation

Start with the shared runtime async core, async timers, and coroutine-backed async task prototype.

Do not make thread-backed `tasks` the base abstraction. Instead, let `task_batch` later bridge into the async core as one awaitable completion source, with care to avoid blocking the async scheduler thread.

Treat fibers as a later lower-level stackful feature. They can share scheduling infrastructure eventually, but they should not be part of the first implementation or the default mechanism for PHS/JSS async functions.
