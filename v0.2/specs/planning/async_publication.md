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
a published runtime async scalar parameter or runtime result. The aggregate has no base, interface, authored
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
locals, timer statements, echo, throw and return. Task storage, general nested await
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
