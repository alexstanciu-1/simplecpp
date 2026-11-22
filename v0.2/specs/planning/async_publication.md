# Publishing async calls across source files

1. **Native proof.** Compile the synchronous caller, awaiting parent and suspending child separately. The public header declares a concrete synchronous entry. A private declaration names the concrete integer task used by the awaiting parent. Only async implementation units include the coroutine runtime. The fixture uses the existing runtime as the oracle for suspension, exception propagation and destruction.
2. **Compiler integration.** Record the logical result on the async function declaration. At an await site use normal cross-unit symbol resolution and select the asynchronous entry. Emit the private declaration only for units that use it. Emit a blocking bridge only at an explicitly supported synchronous boundary. A private body edit should leave unrelated callers eligible for reuse.
3. **Compile-cost check.** Measure public callers and private implementations separately before extending result families. The private integer task alias still exposes the runtime template definition to async implementation units. If that cost warrants it, prove a concrete out-of-line task implementation before expanding publication.

The first proof covers an integer result. Scheduling policy, cancellation and user-stored tasks are separate decisions.
