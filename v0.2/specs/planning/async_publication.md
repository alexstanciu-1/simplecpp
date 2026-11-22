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
runtime object. The async header stays outside scalar, output, entry and PCH headers.
The archive acceptance fixture is in `tests/async_runtime/`. Earlier cross-file
experiments are historical comparisons using the legacy scheduler.

**Next compiler slice.** Record async on the function declaration, reuse ordinary cross-unit
lookup and signature watches, and prepare direct await as the eventual int64 result. Emit
the task entry for awaiting callers and a concrete integer bridge at a synchronous boundary.
Verify that a private body edit permits reuse of unrelated native objects.
