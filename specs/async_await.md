# Async calls and suspension

Doc Status: normative

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
