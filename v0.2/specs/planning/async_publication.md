# Publishing async calls across source files

Doc Status: planning

The runtime profile enables the shared frame, timer and cooperative scheduler with
`async_frames`. A synchronous wait drives one chain of continuations and restores
the enclosing loop on exit. Scheduler, clock and thread headers are private to the runtime.

### Concrete task ownership

Preparation checks the resolved return type for async storage support. Source module
selection still controls access to optional types. The runtime type registry derives
eligibility from concrete compiler types; there is no separate list of task exports.
Enums, eligible inline records and application collections use their settled type identities.

Each suspending unit requests a concrete task for its result and the results it awaits.
The existing concrete recipe emits one application header per canonical identity in
the application namespace. The program publisher merges requests across units, retains
headers demanded by unchanged units and retires the header when its last producer goes away.
A synchronous project emits zero task shapes. Runtime profiles that used an
`async_results` list select `"async_frames": true` instead. Concrete task and wait
headers are application artifacts; a native consumer can use the same recipe. Inline task methods use the erased frame ABI.
The value promise constructs its payload on successful return and checks nonthrowing move construction and destruction. The void promise has no payload.

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
| Application-defined values | `TokenKind` enum, inline `Point` | Enums and eligible inline structs use the same application-owned task recipe, keyed by namespace and declaration. |
| Application-owned specializations and handles | `vector<Point>`, `shared<Point>` | Application vectors, hashes and explicit shared struct owners publish through the concrete result lookup. Vector and hash copies preserve element value semantics. Strong owner copies retain allocation identity. Weak struct results use the same task recipe. An expired observer retains its control block across await and locks to an empty owner. A default observer also locks to an empty owner and reports is_empty(). Required class results preserve allocation identity. Optional class results preserve absence. The ordinary required-destination check runs before completing a required result. |
| Inline arrays | `fixed_array<byte,4>` | Require a returnable representation. Raw C++ arrays cannot be returned by value. |
| Borrowed or execution-bound values | capturing callback, live yield cursor | Require an escape/lifetime or single-consumer contract first. Application publication alone cannot establish safety. |

Inline struct parameters and results use the same ownership check. The check follows inline fields and base subobjects through their settled layouts. Handles retain their existing allocation or observer. Fixed byte arrays move as part of their enclosing record. An authored destructor on a returned record must be accessible to the promise and obeys the ordinary nonthrowing contract. The promise starts with inactive storage. A successful return constructs the payload once, and the frame destroys that instance after delivery. A producer exception reaches the caller before extraction. The native fixture counts constructions, destructions and resource releases for a move-only result with a required constructor.

Application publication must connect resolved type identity, generated headers, affected
source dependencies and incremental retirement. The prebuilt runtime cannot compile a
task implementation that needs an application-defined payload before that payload exists.

The ordinary caller sees the blocking function signature. Coroutine units see the concrete
promise and await operations. Scheduler, clock and thread headers remain private to the
runtime object. Scalar, output and entry headers remain independent of async.
The archive acceptance fixture is in `tests/async_runtime/`. Earlier cross-file
experiments are historical comparisons using the legacy scheduler.

PHP and JS accept `async function answer(): int`;
C++ accepts `async int answer()`. All three use `await answer()`. The slice covers
free functions with eligible results and owned by-value parameters. Published runtime values reuse their existing owned value representation for parameters, including active numeric
widths, collections, carriers and boxed values. Application collections and strong/weak struct handles also pass
by value. Collection copies and handle identity follow ordinary call semantics. Defaults use ordinary binding rules.
Enums and concrete inline structs with eligible fields can cross the same parameter boundary.
Inline struct parameters use the same field and lifecycle checks as results. The frame receives an initialized argument. Plain field assignments use ordinary write checks in an async body.
The ordinary argument binder checks conversions. The blocking bridge moves nontrivial owned values into the coroutine frame.
The frame keeps its arguments alive across suspension and releases them on completion or exception.
An async void producer completes through an empty return or fallthrough. A standalone
await can discard any eligible result in synchronous and async bodies. Using a void result as
a value produces the ordinary void-call diagnostic. All task results use one scheduler
and the same generated implementation for continuation, frame destruction and exceptions. Direct local initializers, return
values and echo operands can consume the result. The producer body accepts braced if/else branches,
locals, lexical blocks, timer statements, echo, throw and return. Task storage, async method declarations and reference parameters on async producers are deferred. In an async body, awaited calls
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
ordinary ordered typed catches. A handler can suspend, return, throw or rethrow.
A finally block may suspend while a return, loop transfer or exception is pending. The existing task rethrows the saved
child exception at await-resume, preserving its identity for typed dispatch.

The ordinary function symbol and dependency watches carry the async signature bit.
A blocking call of any arity requests an ordinary value-returning wrapper in the
producer's unit. A suspending call requests the task signature. Two existing watch
roles select the required entries and retire unused wrappers as callers change.
Coroutine units include their promise headers and use the selective coroutine PCH.
Timer expressions separately request the timer header. The all-flavor acceptance
test is in `compiler/tests/async_native.py`.

### Implementation coverage and remaining boundaries

The following review concerns the v0.2 cooperative, direct-call contract. The executable
proofs for owned arguments, blocking expressions, lazy expressions and lexical blocks
are in `compiler/tests/async.php`, with incremental and independent fresh executables
built by `compiler/tests/async_native.py`. The entries below distinguish source probes
from native constraints and design questions.

#### Explicit blocking calls

Implemented in all three flavors. Ordinary function lookup watches the source entry first. A missing bare unimported or root-qualified `async_wait` can bind the direct producer operand to its blocking bridge. Explicit imports and other qualified paths keep exact lookup. Keyword `await` still selects suspension from the enclosing function. Both forms use the same producer binding and C++ invocation path. The retained-session fixture exercises adding and deleting a shadow, import deletion and recovery, and sibling-namespace object reuse.

#### Eager expressions and call arguments

Implemented for eager operators, ordinary source calls and published runtime adapters. Source methods retain their selected receiver during argument suspension. An ordinary mutable reference binds the exact named slot throughout argument evaluation. The existing binder selects argument conversions. Lowering stages converted operands in the current coroutine and places a short-circuit RHS's steps inside its branch. A nested scalar result can leave a temporary scope after its owners have been released. For example, the all-flavor fixture compares a temporary shared owner with null as the first argument and observes destruction before the second producer starts.

Supported statement sites include returns, discarded calls, echo operands, plain assignments, local initializers and every condition of an if/else-if chain. A later condition with prerequisite statements opens a nested else block, so preceding matches skip its work. Direct awaits retain their earlier admission. A while condition with prerequisite statements evaluates those steps at the top of each iteration, including after continue. Condition temporaries leave scope before the body starts. A staged do-while skips its test on first entry. Fallthrough and continue reach the test after body locals leave scope, while break skips it. A staged for condition executes its comma-separated items in order at the top of each iteration. After fallthrough or continue, the update runs before the next test. A staged update uses a first-iteration flag to skip all update items on entry, then runs them in source order after body locals leave scope. Break skips the update. In a for initializer, staged calls, plain assignments and declarations run once in written order within the header scope. Direct ternary arms retain native conditional evaluation. A selected arm with prerequisites constructs its result inside the branch. Locals, nested expressions and ordered argument snapshots share the staged result storage rule. Scalars use ordinary assignment. For a nontrivial value the emitter reserves inactive storage, constructs the value after operand evaluation succeeds, and destroys it when its scope ends. Nested selections can initialize the same destination directly.

The native fixture checks ordered argument effects, once-only eager addition, a skipped or taken RHS, a captured field changed by the next child, narrowing failure before the next child starts, native destruction order and incremental recovery.

#### Suspending catch handlers

When a handler may suspend, the native catch retains the language exception owner and exits. The coroutine then selects the typed arm and executes its body with ordinary lexical storage. The retained owner supplies bare rethrow and unmatched propagation. Reassigning the catch binding affects an explicit throw of that binding. A throw escaping an awaited child in the selected arm unwinds the handler locals and skips sibling arms.

#### Finally

A protected return constructs its converted value in inactive storage before leaving the protected block. A loop transfer records its resolved target. Control leaves the protected scope, destroying its locals, then executes the finalizer in the same coroutine. A native exception escaping the protected work is retained across cleanup. Normal completion replays the pending exit through enclosing regions. A throw escaping the finalizer replaces that exit and releases any saved return. The native fixture exercises a group of nested finalizers, a suspending finalizer replacing a shared return, and a runtime bounds error that propagates after finalization.

#### Methods and callable adaptation

Free-function task and blocking entry points are published by producer and dependency role. Instance methods additionally need a receiver that survives suspension. Decide whether inline receivers copy or borrow, and whether shared receivers pin their allocation. Static methods avoid the receiver question but need dual entry publication through existing static-method specialization. Interface/virtual calls and async callable adaptation also need async signature compatibility. Start with one cross-file static method proof before changing instance dispatch.

#### Task values and references

Bare task-producing calls reject on all three flavors. Task variables, parameters, capturing callbacks and live cursors would expose the frame outside direct consumption. Define single-consumer transfer, abandoned-frame cleanup and borrowing before adding these forms. The current source contract expressly requires owned parameters and rejects reference results. The all-flavor reference-parameter rejection is a regression check. Scheduler cancellation, cross-thread tasks and fibers are separate language/runtime contracts.

#### Remaining payload storage

Construction on successful return covers owned application records, including inherited and nested state, and class handles with required or optional presence. A standalone fixed_array result still needs a returnable native representation.

#### Statement coverage

Async producers admit ordinary foreach using the ordinary synchronous iteration protocol for vectors, hashes, mixed, dynamic and user-defined Iterator receivers. Keyed traversal follows the receiver’s existing contract. The body can suspend between advances. Preparation shares the generator receiver check for local storage, temporaries and retained fields. The existing foreach emitter binds the receiver once and uses the ordinary iteration protocol. Native coroutine locals keep the receiver and traversal state alive across await. The fixture checks a temporary vector, keyed local traversal, field replacement through an awaited child's shared owner, continue, break and child failure. A heap-backed string retains the fetched value when the awaited child replaces the vector field. A record element keeps its shared owner alive after clearing the source vector, then releases it on break or exception.

Hash iteration skips entries removed during an await. A boxed receiver changed to a scalar fails at the next advance and releases its retained owner. A temporary custom iterator survives the await and publishes into an existing destination only on success. Nested yield cursors and asynchronous advance remain deferred. Async bodies use ordinary statement preparation, including switch and ordinary storage writes between suspension points. Local, field, indexed and append writes can suspend during operand evaluation. Indexed compounds preserve the selected collection, key and converted old value, then recheck the element after the RHS completes. A plain store follows its receiver’s existing conversion and bounds-check order. Switch selectors, match subjects and selected arms can suspend. Coalescing checks presence once and evaluates an awaited fallback only when absent, including in a local coalescing assignment. Awaited timer, constructor, collection, subscript and foreach operands run in the enclosing coroutine. See the [completion backlog](async_completion_backlog.md). Synchronous yield retains its concrete cursor protocol.
