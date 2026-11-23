# Publishing async calls across source files

Doc Status: planning

The runtime profile enables the shared frame, timer and cooperative scheduler with
`async_frames`. A synchronous wait drives one chain of continuations and restores
the enclosing loop on exit. Scheduler, clock and thread headers are private to the runtime.

### Concrete task ownership

Preparation checks the resolved return type for async storage support. Source module
selection still controls access to optional types. The runtime type registry derives
eligibility from concrete compiler types; there is no separate list of task exports.
Enums, passive records and application collections use their settled type identities.

Each suspending unit requests a concrete task for its result and the results it awaits.
The existing concrete recipe emits one application header per canonical identity in
the application namespace. The program publisher merges requests across units, retains
headers demanded by unchanged units and retires the header when its last producer goes away.
A synchronous project emits zero task shapes. Runtime profiles that used an
`async_results` list select `"async_frames": true` instead. Concrete task and wait
headers are application artifacts; a native consumer can use the same recipe. Inline task methods use the erased frame ABI.
The value promise owns its payload and checks nonthrowing default construction, move and
destruction. The void promise has no payload.

Blocking callers use ordinary producer-owned bridges. The concrete promise stays in
coroutine units. Native test consumers request fixture-private task headers from the same
recipe and link the normal runtime archive. The frame-boundary fixture verifies nested
waits and cleanup on failure.

### Async result categories and representative proofs

| Category | Samples | Status and owner |
| --- | --- | --- |
| Completion and trivial values | `void`, `bool`, `int64` | On demand. Void omits storage. Null, bool, byte/uint8, float64 and the active integer widths use the value recipe. Optional widths require the numeric module in each source project. |
| Owned runtime values | `string` | On demand. Empty, inline and heap-backed strings cover both wait paths and throwing producers. |
| Published concrete collections | `vector<int>`, `hash<int,int>` | On demand. Use the same move contract and their existing concrete identities. |
| Published carriers | `nullable<int>` | On demand. All four runtime carrier families support bool, int64, string and vector<string>. The task transfers the entire carrier, including its alternate state. |
| Boxed runtime values | `mixed`, `dynamic` | On demand. Frame transfer preserves existing copy-on-write and shared-container semantics. |
| Application-defined values | `TokenKind` enum, inline `Point` | Enums and passive inline structs use the same application-owned task recipe, keyed by namespace and declaration. |
| Application-owned specializations and handles | `vector<Point>`, `shared<Point>` | Application vectors, hashes and explicit shared struct owners publish through the concrete result lookup. Vector and hash copies preserve element value semantics. Strong owner copies retain allocation identity. Weak struct results use the same task recipe. An expired observer retains its control block across await and locks to an empty owner. A default observer also locks to an empty owner and reports is_empty(). Class presence contracts remain deferred. |
| Inline arrays and values without an empty state | `fixed_array<byte,4>`, a record requiring constructor arguments | Require a returnable representation or construction-on-return storage. Raw C++ arrays cannot be returned by value. |
| Borrowed or execution-bound values | capturing callback, live yield cursor | Require an escape/lifetime or single-consumer contract first. Application publication alone cannot establish safety. |

Inline struct results use the existing eagerly initialized promise payload. Signature
preparation checks the direct fields once per prepared producer signature. Each field
must have an implicit empty default and an enum or runtime type already admitted as
an owned runtime async value. The aggregate has no base, interface, authored
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
free functions with eligible results and owned by-value parameters. Published runtime values reuse their existing owned value representation for parameters, including active numeric
widths, collections, carriers and boxed values. Application collections and strong/weak struct handles also pass
by value. Collection copies and handle identity follow ordinary call semantics. Defaults use ordinary binding rules.
Enums and concrete inline structs with eligible fields can cross the same parameter boundary.
Inline structs use the field and lifecycle checks above, but may declare a constructor and field defaults:
the frame receives an initialized value. Plain field assignments use ordinary write checks in an async body.
The ordinary argument binder checks conversions. The blocking bridge moves nontrivial owned values into the coroutine frame.
The frame keeps its arguments alive across suspension and releases them on completion or exception.
An async void producer completes through an empty return or fallthrough. A standalone
await can discard any eligible result in synchronous and async bodies. Using a void result as
a value produces the ordinary void-call diagnostic. All task results use one scheduler
and the same generated implementation for continuation, frame destruction and exceptions. Direct local initializers, return
values and echo operands can consume the result. The producer body accepts braced if/else branches,
locals, lexical blocks, timer statements, echo, throw and return. Task storage, general nested await
expressions, async methods, and reference parameters are deferred. In an async body, awaited calls
may appear under logical negation, in either conditional-expression arm or its condition,
or in a chain of && or || used as a local initializer, echo operand, return
value or branch condition. Both sides are prepared and watched. The emitted native
logical operator evaluates its RHS only when needed.

In ordinary synchronous bodies and at top level, a direct async call consumed by await is a blocking
expression. It can participate in arithmetic, arguments and lazy branches using ordinary expression lowering.

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
A blocking call of any arity requests an ordinary value-returning wrapper in the
producer's unit. A suspending call requests the task signature. Two existing watch
roles select the required entries and retire unused wrappers as callers change.
Coroutine units include their promise headers and use the selective coroutine PCH.
Timer expressions separately request the timer header. The all-flavor acceptance
test is in `compiler/tests/async_native.py`.

### Remaining source and storage contracts

The following review concerns the v0.2 cooperative, direct-call contract. The executable
proofs for owned arguments, blocking expressions, lazy expressions and lexical blocks
are in `compiler/tests/async.php`, with incremental and independent fresh executables
built by `compiler/tests/async_native.py`. The entries below distinguish source probes
from native constraints and design questions.

#### Explicit blocking calls

Implemented in all three flavors. Ordinary function lookup watches the source entry first. A missing bare unimported or root-qualified `async_wait` can bind the direct producer operand to its blocking bridge. Explicit imports and other qualified paths keep exact lookup. Keyword `await` still selects suspension from the enclosing function. Both forms use the same producer binding and C++ invocation path. The retained-session fixture exercises adding and deleting a shadow, import deletion and recovery, and sibling-namespace object reuse.

#### Eager expressions and call arguments

Implemented for eager operators and ordinary source functions with by-value parameters. The existing binder selects argument conversions. Lowering stages converted operands in the current coroutine and places a short-circuit RHS's steps inside its branch. A nested scalar result can leave a temporary scope after its owners have been released. For example, the all-flavor fixture compares a temporary shared owner with null as the first argument and observes destruction before the second producer starts.

Supported statement sites include returns, discarded calls, echo operands, plain assignments, trivial local initializers and every condition of an if/else-if chain. A later condition with prerequisite statements opens a nested else block, so preceding matches skip its work. Direct awaits retain their earlier admission. Expanded expressions in loop headers and ternary arms need a point to emit prerequisite statements on the correct path. Nontrivial initializers and nested staged results need storage that can be initialized in an inner scope without extra source-visible construction or destruction. These forms receive diagnostics pending a separate design. A direct awaited string can already be passed by value.

The native fixture checks ordered argument effects, once-only eager addition, a skipped or taken RHS, a captured field changed by the next child, narrowing failure before the next child starts, native destruction order and incremental recovery.

#### Suspending catch handlers

The frontend tests reject both timer and child suspension inside a handler. Clang and GCC independently rejected a native co_await in catch (...). Move execution outside the native catch and retain the selected exception with its original identity across suspension. Exercise typed dispatch, rethrow and replacement exceptions.

#### Finally

The tracked frontend fixture rejects even a finally containing only echo. Cpp_Emitter::exception_region emits a finalizer lambda and may run the protected region inside a pending-exit lambda. Existing replay targets ordinary returns. Supporting the general form requires coroutine-aware exit replay and cleanup, including a finalizer that throws or suspends. Verify nested returns, break/continue and destruction after failure.

#### Methods and callable adaptation

Free-function task and blocking entry points are published by producer and dependency role. Instance methods additionally need a receiver that survives suspension. Decide whether inline receivers copy or borrow, and whether shared receivers pin their allocation. Static methods avoid the receiver question but need dual entry publication through existing static-method specialization. Interface/virtual calls and async callable adaptation also need async signature compatibility. Start with one cross-file static method proof before changing instance dispatch.

#### Task values and references

Bare task-producing calls reject on all three flavors. Task variables, parameters, capturing callbacks and live cursors would expose the frame outside direct consumption. Define single-consumer transfer, abandoned-frame cleanup and borrowing before adding these forms. The current source contract expressly requires owned parameters and rejects reference results. The all-flavor reference-parameter rejection is a regression check. Scheduler cancellation, cross-thread tasks and fibers are separate language/runtime contracts.

#### Remaining payload storage

The current promise eagerly default-constructs its payload. A record with required constructor arguments or an authored destructor needs construction on successful return and destruction only when initialized. Reuse the concrete recipe with explicitly tracked storage lifetime if approved. A fixed_array result separately needs a returnable native representation; raw arrays cannot be returned by value. Class owners need a proof that required presence survives frame transfer, distinct from the existing empty shared struct handle. Nested passive record layouts need transitive eligibility and invalidation before the direct-field restriction can be relaxed.

#### Statement coverage

The async preparation whitelist still excludes foreach and several ordinary storage-write and control-flow forms. Ordinary foreach can retain a reference to a source place through the loop. Before lifting the whitelist as a group, establish which traversal receivers remain owned across a suspension and which writes introduce expression snapshots. Prove mutation through another owner during an await. Keep synchronous yield and coroutine suspension as separate execution protocols.
