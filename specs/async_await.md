# Async/Await
Doc Status: normative

Status: alpha

Purpose: define the current lightweight async/await contract for Prism++ / Simple C++ source surfaces.

This document covers stackless async/await in the legacy PHS/PHP++ and JSS frontends and the v0.2 PHP, C++, JS and Python syntax flavors. Fibers and the thread-backed `tasks` bridge are intentionally out of scope for this contract.

## Model

Async/await is cooperative, stackless control flow backed by the shared runtime async core.

- An async free function produces a task carrying its declared result. The legacy frontend uses `scpp::async_core::task<T>`. In v0.2, the compiler publishes a concrete task for each demanded result type.
- `await` on an async task suspends the current async function, or blocks a synchronous caller through `async_wait(...)` when used at synchronous/top-level source sites.
- `await async_sleep_ms(ms)` is a timer suspension point and is valid only inside an async function.
- Async/await does not imply parallel execution and does not create an operating-system thread per async operation.

The implementation is based on C++ coroutine support in the target toolchain. The current runtime build baseline is C++23, which includes the C++20 coroutine facility used by this feature.

## PHS / PHP++ Surface

PHS supports:

```php
async function compute_value(): int {
	await async_sleep_ms(1);
	return 42;
}

$value int = await compute_value();
echo $value, "\n";
```

Lowering contract:

- `async function f(): T` declares a task-producing callable whose eventual value has type `T`. The legacy frontend uses a parser-compatible `/** @async */` marker; v0.2 retains this distinction in callable preparation.
- `return` inside an async function lowers to `co_return`.
- `await async_sleep_ms(ms)` inside an async function lowers to `co_await scpp::async_core::sleep_ms(...)`.
- Expression-level `await expr` suspends inside an async function. At synchronous/top-level sites it waits synchronously. Nested calls, lazy branches and operand snapshots preserve source evaluation order across suspension.
- In v0.2, the built-in `async_wait(producer(...))` calls the producer’s blocking bridge, including inside an async function. Ordinary source lookup wins before argument checking. A bare unimported name uses the built-in if its namespace has no matching declaration. A root-qualified call tries the root declaration first. Other qualified names and explicit imports bind to their exact targets. A discarded wait may consume an async void result.

## v0.2 direct-call contract

In all four v0.2 syntax flavors, task-producing calls must be consumed directly
by `await` or `async_wait`; task variables, task parameters and async callable
adaptation remain separate work. Async parameters must be owned values; reference
parameters and reference results reject. Local values and their cleanup remain
alive across suspension. The `tasks` module's cross-thread transfer rules do not
apply to cooperative async calls.

The Python flavor uses typed `async def` declarations and indented suites. A
top-level direct await uses the same blocking bridge as the other flavors.

```python
async def child(n: int) -> int:
    await async_sleep_ms(1)
    return n + 1

async def parent() -> int:
    return await child(41)

return await parent()
```

An ordinary call inside an async body may suspend while evaluating its receiver
or arguments. A selected method receiver remains alive across later arguments.
A mutable reference parameter binds the same named storage across an awaited
sibling. Required conversions, ordered effects and local cleanup follow the
ordinary expression contract. A lazy operator or selection evaluates only the
chosen operand or arm. Collection literals complete entries in source order
and destroy completed entries if a later input fails.

A field write retains the selected owner. An indexed compound update preserves
the selected collection storage, key and converted old value across the right
operand, then checks the destination on write-back. Synchronous iteration can
continue after an await in the loop body under the receiver's ordinary traversal
and lifetime rules. Async iteration, nested generator cursors, cancellation,
cross-thread execution, async method declarations and standalone fixed-array
results are outside this milestone.

A v0.2 producer constructs its owned result on successful return. Starting or
abandoning a task does not default-construct a result. If evaluating the return
value throws, both await and the blocking bridge propagate the exception. Native
transfer and destruction of an admitted payload must be nonthrowing. For an inline
record, its fields and base subobjects travel with the value. A class handle
preserves allocation identity and the presence contract of the declared result.

In v0.2 a typed catch handler can suspend. Its binding and locals survive the
await. Bare `throw;` propagates the exception originally caught by that handler,
including after reassignment of the binding.

A v0.2 `finally` block may suspend. Protected locals leave scope before the
finalizer starts. A return saves its converted value before cleanup, and a loop
transfer retains its resolved destination. A finalizer that completes normally
resumes the pending exit. An exception escaping the finalizer replaces it and
releases any saved value. Nested finalizers run from inner to outer. The ordinary
rules for exits from a finalizer continue to apply. [Exception contract](exceptions_v0_2.md)

Default prelowering keeps coroutine implementations behind the compiled boundary
and exports concrete task storage to template-free application units. Snapshot
scopes containing suspension retain their own locals and closure until completion;
ordinary expressions do not acquire extra suspension points. This describes the
v0.2 implementation contract, not a claim that unrelated prelowering gaps are closed.

`async_sleep_ms(...)` is a suspension primitive. It must be used inside an async function. Synchronous code should await an async task value instead.

## JSS Surface

JSS supports the same first-slice semantics with typed script-style spelling:

```js
async function computeValue(): int {
    await async_sleep_ms(1);
    return 42;
}

let value: int = await computeValue();
print(value, "\n");
```

The legacy JSS frontend lowers through the PHS async surface:

- `async function` emits the PHS `@async` representation.
- `await async_sleep_ms(...)` emits the PHS timer statement form.
- expression-level `await value` emits `async_wait(value)`.

This is not JavaScript promise compatibility. There is no JavaScript event loop, promise object model, `Promise` API, or JavaScript module async semantics in this alpha contract.

## Runtime Surface

The legacy runtime substrate lives under `scpp::async_core` and currently includes:

- `scheduler`
- `task<T>` and `task<void>`
- `sleep_ms(...)` / `sleep_for(...)`
- `yield_now()`
- `ready_task(...)`
- `spawn(task<T>&)`
- `sync_wait(task<T>)`

The runtime core is ready-by-default in the runtime aggregate. It is not the `tasks` module and does not expose raw threads.

## Static Analysis

Current STAN support is intentionally narrow:

- direct `async_wait(async_function())` unwrapping is recognized for typed-boundary checks
- JSS same-file async function return types are summarized for first-slice expression checks
- async helpers are known function symbols

Broader async task value modeling is not part of this first slice. Future work may add richer STAN support for storing task values, passing task values across function boundaries, validating awaitability, and modeling runtime error/cancellation states before await.

## Tasks And Fibers

The existing `tasks` module remains the thread-backed parallel work API. It is separate from async/await.

No `task_batch` await bridge is part of the current async/await contract. A future bridge should avoid blocking the async scheduler thread while waiting for thread-backed work.

Fibers are deferred. They require stackful cooperative runtime support and are not part of the current default async function mechanism.

## Validation

Feature validation should include:

```bash
php tests/tools/test_scpp_async_core_language.php
php tests/tools/test_scpp_async_surface_projects.php
php tests/tools/test_scpp_jss_frontend_first_slice.php
ctest --test-dir /tmp/scpp_async_core_build -R scpp_test_async_core --output-on-failure
```
