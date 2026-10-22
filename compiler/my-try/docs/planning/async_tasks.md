# Async suspension and strict tasks integration
Doc Status: planning

Completed approved goal: S12 and V-R02. Baseline was 55/64 original issues closed.
Final acceptance closes both, reaching 57/64 (89.0625%), a gain of 3.125 percentage points.

## Current acceptance status — 2026-10-21

| Requirement | Current evidence and remaining work |
| --- | --- |
| S12 suspension, sequencing, cleanup and restrictions | Closed by the source/runtime/retained proofs below. |
| Runtime-owned task types, controls and all 21 API names | All 21 names and separate mixed/dynamic batch handles are installed; the acceptance inventory records signature boundaries and source/runtime proofs. Module opt-in is verified. |
| Typed vector/hash submissions and placement | Value/void work, contexts, prefilled/cross-carrier targets, independent int/string keys and inferred nullable keys have default-prelowered proof. Explicit nullable-string targets also have success/error/omission proof. |
| Error callbacks and publication | Replacement/omission, coordinator dispatch, ordered publication context/options and unordered synchronization proved in the bounded forms below. |
| Transfer restrictions | Typed payload/capture rejection and bounded scalar boxed task admission proved. Literal/index transport now passes fixture-free source tests; capture permission is unchanged. |
| Lifecycle acceptance | Native lifecycle evidence and production-source timeout, active cancellation, shutdown/error precedence and module opt-in pass. The subsequent V-D06 goal now proves source repeated-error joins for pool sizes 0/2; see finally_value_transfers.md. Earlier blocked checkpoints remain historical. |
| Final closure | Complete: 57/64 original issues closed. Boxed custom placement is explicitly unsupported in v0.2; separate source exception transport is not claimed. |

The [task acceptance inventory](task_acceptance_inventory.md) maps installed
signature groups to proof owners and remaining gates.

Historical checkpoints below retain their then-current boundaries; they do not
supersede this summary. The nullable identity/prelowering issue remains separate.

## Contract and owners

- S12 follows `specs/async_await.md`: await suspends inside async functions;
  synchronous/top-level await waits. Shared callable/expression preparation owns
  validity and lifetime restrictions. C++ specialization owns concrete transport.
  Async arguments and captures must survive suspension; unsafe references reject.
- V-R02 follows `specs/builtins/tasks/first_pass.md` and
  `unordered_publication.md`. Install opt-in tasks through existing metadata and
  provider owners. Preserve transfer restrictions and boxed families. Background
  callbacks require owned storage; synchronous callback adapters are not evidence
  of background safety.
- Bounded runtime repairs are authorized. A wider scheduler or transfer-model
  redesign requires discussion. No full matrix or native compiler rebuild.

## Acceptance inventory

1. Runtime-first: existing async suite plus owned values, nested suspension,
   sibling scheduling, exceptions and destruction; tasks concurrency, callback
   placement, background ownership, repeated join, failure and publication guards.
2. S12: original strict 4242 fixture, nested value/void await, synchronous waits,
   ordered snapshots and suspension, error/cleanup, reference rejections.
3. V-R02 API inventory: task_run, task_run_publish, task_start, task_join,
   task_cancel, task_done, task_status, task_progress, task_set_status,
   task_set_worker_pool_size, task_set_publish_try_lock, all eight publication
   measurements, task_run_publish_unordered and task_synchronize. Include current
   documented callback forms, result targets and runtime-owned handle contracts.
4. V-R02 behavior: bounded concurrency, empty inputs, cancellation, timeout,
   repeated joins/errors, shutdown joins, coordinator/worker/publication contexts,
   nested context restoration and transfer rejection. Prefer synchronization
   assertions to elapsed-time assumptions.
5. Default-prelowered production cold/warm builds on dev002; audit template-free
   application inputs. Retained edit/reject/repair/replay must agree with fresh
   preparation and publication. Preserve historical failures and new blockers.

Use the shared 12-slot runner. Commit coherent verified work and push each commit.
## Verified prerequisite — 2026-10-21

The existing callback transport now handles fixed value parameter lists and void
results. Provider adapters capture an owned callable rather than borrowing the
compiled helper's stack. This establishes transport lifetime, not permission to
transfer arbitrary captures or mutable objects across threads. Reference parameters,
reference results, variadics and borrowed erased captures remain restricted.

dev002 evidence:

- `20261021-async-tasks-runtime-01`: two ASan/UBSan runtime probes passed, including
  the existing async suite, owned suspended arguments, exception cleanup, bounded
  worker overlap, background capture lifetime, repeated joins/errors and publication
  context restrictions. This is not compiler async/task admission.
- `20261021-task-callbacks-02`: five tasks passed, covering four default-prelowered
  cold/warm source programs plus four rejected callback contracts and retained
  repair/replay/edited-versus-fresh agreement. A stored callback executes on a worker
  after its creating source function and compiled operation helper return.
- `20261021-task-callbacks-collection-regression`: all 25 existing focused tasks
  passed. No full matrix or native compiler build was run.

[Evidence archive](async-tasks-prerequisite-evidence.tar.gz), SHA-256:
`391611a8d0afef4c2821f7ef894435f00b630e588c3d6b309297865eb88fb886`.
It preserves command logs, source fixtures, publication manifests and upload hashes,
including the first four-case callback run before recovery coverage was added.

## Approved async backend prerequisite

Coroutine headers contain templates and cannot enter strict application inputs.
Existing snapshot IIFEs also cannot simply acquire `co_await`: suspension must
belong to the enclosing async computation. The user explicitly authorized extending
the existing compiled boundary for async implementations and adding suspension-aware
expression sequencing. Preserve lazy branches, snapshot lifetimes and retained
invalidation; do not disable prelowering or create another source/semantic registry.

## Verified async backend prerequisite — 2026-10-21

Shared preparation now distinguishes an async body's payload return from its
caller-visible, move-only internal task. Immediate await consumes that task;
reference parameters/results and source task storage remain rejected. Bare await
suspends inside async functions and waits in synchronous code. Explicit
`async_wait` remains synchronous in either context.

Default prelowering publishes coroutine bodies through the existing per-source
implementation grouping under `specializations/async/`. Application inputs retain
the template prohibition. Prepared suspension facts select an owned coroutine
scope for expression snapshots; ordinary expressions retain their existing scopes.
The runtime helper retains that scope until its immediately awaited child finishes.

dev002 evidence:

- `20261021-async-execution-07`: all 16 tasks passed. Includes the unchanged v0.1
  strict fixture (4242), value/void nesting, owned strings, ordered eager operands,
  lazy branches, containers, match, loops, conversions, explicit synchronous waits
  and scheduler identity across nested suspension. Production cold/warm builds
  preserve the application template prohibition and warm reuse. An ordinary-mode
  control agrees. Four production ASan/UBSan builds prove snapshot lifetime,
  container evaluation and exceptional cleanup.
- Retained rejection/repair/replay and async removal/payload edits agree with fresh
  in-memory artifact generation. This does not independently prove stale-file
  retirement in a previously published directory.
- `20261021-async-preparation-final`: shared facts and eleven invalid forms pass,
  with repair/replay and lexical helper shadowing.
- `20261021-async-runtime-final`: both runtime probes pass.
- `20261021-async-collection-regression`: all 25 focused tasks pass.

[Evidence archive](async-backend-evidence.tar.gz), SHA-256:
`4be8ac2f1677739852dc0139844aa040273e75743c186329f531e68119d9bf19`.
It includes earlier failed attempts, final logs, fixture sources, manifests and
the uploaded source hash manifest. No full matrix or native compiler rebuild ran.

Current status: callback and async backend prerequisites verified. Tasks module
admission/transfer/shutdown contracts and final issue closeout remain open. Named
free async functions are the bounded surface; async methods/closures are not added.
Existing default-prelowered throw/catch/finally transport remains separate V-D06
work; runtime exception propagation and cleanup are verified here without claiming
that syntax gap closed. The original ledger remains **55/64**; neither S12 nor
V-R02 is closed at this intermediate checkpoint.

## Verified tasks handle/control bindings — 2026-10-21

`runtime/metadata/modules/tasks.json` now selects the existing tasks provider,
`SCPP_HAS_TASKS=1` and pthread linkage through ordinary module build metadata.
It declares four runtime-owned shared types, seven readonly error fields, seven
progress observation methods and the sixteen fixed free functions:

- join, cancel, done, status, progress;
- set_status, set_worker_pool_size, set_publish_try_lock;
- all eight publication measurements.

The remaining five free names are **not yet admitted**: task_run,
task_run_publish, task_start, task_run_publish_unordered and task_synchronize.
Their callback/context/transfer contracts, result-placement forms, background
retention and shutdown integration remain required goal work. The test-only batch
factory proves transport of existing runtime handles, not source batch admission.

The existing concrete-member owner now transports zero-argument shared runtime
methods behind the compiled boundary. Shared methods with arguments remain an
explicit gap because the receiver check must precede argument evaluation. The
existing property owner adds bool/mixed reads; it does not add mutable bool/mixed
property transport. No new runtime behavior was needed for this checkpoint.

`20261021-tasks-metadata-04` passes all five tasks: four production cold/warm
programs and seven rejection/repair/replay cases. They cover repeated joins,
progress/status, context status updates, error fields, pool/measurement controls,
wrong handles, readonly writes and source construction rejection.
`20261021-tasks-member-regression-02` passes ordinary/concrete source lifecycle,
method/layout edits and warm reuse. Its first attempt exposed the obsolete
owned-callable rejection expectation from before the callback prerequisite;
the fixture now checks supported storage and invalid-return rejection/recovery.

[Evidence archive](tasks-metadata-evidence.tar.gz), SHA-256:
`5e516203b95c6f95530bf4669d97027ffe04c5f02562382ee52f8aaeee407d40`.
Historical attempts and upload hashes are included. Overall closure remains 55/64.

## Verified background shutdown prerequisite — 2026-10-21

All seven runtime background-start implementations now retain their batch through
the existing tasks provider until explicit join or process-entry cleanup. Explicit
join retires retention even when rethrowing a cached worker failure. Shutdown
drains outstanding batches, including batches created by work it is joining, then
stops the worker pool. It drains after failures and reports the first failure.

The existing C++ entry-support owner invokes shutdown after the application body
returns and on application exceptions. A body exception remains primary if cleanup
also fails. Default-prelowered and ordinary tasks programs share that entry owner;
there is no scheduler redesign or new source cleanup helper.

Dev002 `20261021-tasks-shutdown-entry-02` passes eleven tasks: existing handle and
contract checks plus production cold/warm return, worker-failure and body-failure
cases in both lowering modes. `20261021-tasks-shutdown-runtime-02` passes three
ASan/UBSan probes, including existing async/task controls and a latch-controlled
lost-handle lifetime test, nested cleanup, repeated shutdown and draining remaining
work after failure. This supersedes the pending shutdown prerequisite above.

[Evidence archive](tasks-shutdown-evidence.tar.gz), SHA-256:
`d3dab89e4eec3681bac03469d38fd4b44bca6e76602ef34360643953ff137437`.
Source hashes and earlier attempts are preserved. Source batch admission remains
open; neither original issue is closed here.

### Authorized transfer-model prerequisite

Closure-to-callable conversion checks borrowed lifetimes and copyability, then
stores only function-signature identity. Owned capture storage is not proof of
transferability: shared mutable handles and resources can still be owned captures.
After erasure, the signature cannot recover this distinction across copies,
returns or parameters.

The requested extension is a bounded transfer capability owned by existing
callable preparation and carried by callable storage/transport, checked at task
submission. Direct capture checks must observe anonymous-body dependencies;
typed callable transport must preserve the established capability. No general
thread-safety analysis or scheduler changes are proposed. The user authorized this bounded transfer-model extension on 2026-10-21. Do not admit all owned captures or claim completion by rejecting every
stored callable.

## S12 closeout — 2026-10-21

S12 is now **implemented and verified** within the approved named-function async
scope. LV28.001 and LV28.003 are proved by the unchanged strict v0.1 fixture;
LV28.002 is proved by source value/void nesting, scheduler identity, lifetime,
exception cleanup and eager/lazy snapshot cases, including four sanitizer builds.
Preparation verifies reference restrictions and task-consumer boundaries. These
are the production/default-prelowering proofs archived above, not runtime-only
inferences. Wider async methods/closures/task storage and V-D06 exception-syntax
transport remain explicit separate limits.

The final missing publication check now passes in
`20261021-async-publication-01` on dev002. Invalid async returns make the existing
published directory unready; repair and replay restore identical artifacts.
Removing async deletes its compiled implementation unit. Payload changes and
restoration agree byte-for-byte with fresh disk publication, with manifest hashes
checked against every published file. The paired preparation suite also passes.

[Publication evidence](async-publication-evidence.tar.gz), SHA-256:
`31c1709472f81a87193c0791874d041961a393c8b906560f70fa19ffdd99349a`.
Reproduce both checks with `python3 compiler/my-try/tests/async_preparation.py
--results FRESH_DIRECTORY` on dev002.

Current overall status: **56/64 closed (87.5%), one partial**. V-R02 remains partial
and the combined goal remains active. Its authorized transfer prerequisite and remaining
five batch/callback APIs are not removed from acceptance. Earlier 55/64 entries
above are dated intermediate checkpoints.

## Callable transfer capability checkpoint — 2026-10-21

The authorized prerequisite now has a verified vertical path. Capture preparation
classifies copyable scalar/string/enum values and recursively owned vector/hash
and nullable/result payloads separately from borrowed/shared/resource captures.
A captured callable requires its value's retained permission. A closure stores
that result with its owned environment; typed erasure, copies, parameters and
returns preserve it in `scpp::callable_t`. Moved-from callable values cannot claim
transfer permission. Ordinary invocation is unaffected by a denied permission.

The provider adapter preserves both the exact callback signature and permission.
The first generic adapter hid its signature from collection traits; that failure
is retained and the corrected fixed-signature adapter passes the regressions.
The runtime guard rejects denied transfer before the probe starts a worker. This
is a capture-transfer capability, not a proof of general body/thread safety.
Unknown capture domains remain closed: arbitrary records, boxed captures and
containers of callable values need their own verified inspection rules. No new
permission for mutable shared objects or resources is introduced.

Dev002 results:

- `20261021-task-transfer-source-03`: eleven tasks pass, covering default-prelowered
  and ordinary cold/warm builds, nested snapshots, typed parameter/return/copy
  transport, owned containers, shared-capture transfer rejection, and retained
  failure/repair/replay/fresh agreement.
- `20261021-task-transfer-callback-regression`: all five existing callback tasks
  pass; `20261021-task-transfer-collection-regression-03`: all 25 tasks pass.
- `20261021-task-transfer-runtime-03`: all four ASan/UBSan runtime probes pass,
  including default/denied/allowed callables, copying, moves, parameter/return
  preservation, worker execution, void and string signatures.

[Evidence archive](task-transfer-evidence.tar.gz), SHA-256:
`1a119e6b0a73a2257b8500b7c34cf52bdf4bf9f280f19aa18450bdd8df6b17d2`.
The task API submission contracts still need to consume this capability; the
probe is not a claim that the five remaining batch/callback APIs are admitted.
V-R02 remains partial. The approval blocker has been cleared.


## Provider transfer admission checkpoint — 2026-10-21

The existing runtime parameter contract now carries `requires_transfer` through
startup loading, registration, generic instantiation and shared call preparation.
Known forbidden closure environments reject during preparation. Erased callable
values are checked after all argument snapshots and before provider invocation;
ordinary and default-prelowered calls use the same sequencing owner. The probe
provider deliberately contains no guard, proving that admission owns enforcement.
This is independent of callback signature compatibility and provider copyability.

`20261021-task-admission-03` passes all 15 tasks on dev002: production cold/warm
runs in both modes, fixed and generic contracts, argument-side effects before
value-dependent rejection, nested callable snapshots, six invalid metadata shapes,
and retained rejection/repair/replay/fresh agreement. The four catalog regressions
in `20261021-task-admission-catalog` also pass. The initial startup-validation
failure is retained: the corrected registration uses its private catalog instead
of reading the global model before initialization.

[Admission evidence](task-admission-evidence.tar.gz), SHA-256:
`8644ed83b4d5bf207bc322671ddabb06fe849b6c362228d7436829f7685bd23a`.
Reproduce with `tests/task_transfer.py --results FRESH_DIRECTORY` and
`tests/runtime_catalog.py --results FRESH_DIRECTORY` from this compiler directory.

V-R02 remains partial: `task_run`, `task_start`, `task_run_publish`,
`task_run_publish_unordered` and `task_synchronize` still require source admission
and integration proofs. The transfer prerequisite is implemented, not an approval
blocker. Overall closure remains **56/64 (87.5%)**.


## Task synchronization admission — 2026-10-21

`task_synchronize(function<void()>)` now uses ordinary task-module metadata and
existing owned callback transport. It executes synchronously in the active worker
context; it does not acquire a cross-thread capture permission. Existing borrowed
callable erasure restrictions remain unchanged. Three default-prelowered production
cold/warm checks pass on dev002: repeated valid synchronization, outside-context
rejection and recursive-context rejection. The positive test establishes the runtime
worker context through an explicitly test-only provider; real unordered task source
integration remains required. Earlier sanitizer probes cover runtime context restore.

[Evidence](task-synchronize-evidence.tar.gz), SHA-256:
`aca37026e6cdf7cd3f0ceb08c24b3b527be8b53dff62eb740138a7934f0b4162`.
Reproduce with `tests/task_submission.py --results FRESH_DIRECTORY`.

The user also authorized bounded generic payload-transfer contracts and a void-to-null
worker-result mapping. These extend existing metadata/preparation owners; no scheduler
redesign or general thread-safety analysis is authorized. Four batch APIs remain open;
V-R02 remains partial and overall closure remains 56/64.


## Typed batch admission checkpoint — 2026-10-21

The approved payload and native-provider prerequisites are implemented through the
existing runtime generic-contract, shared preparation, C++ binding and representation
owners. `transfer_types` checks inferred payload types independently of capture
permission. Output-only void-to-null substitution gives void workers null entries.
A shared bounded transfer classifier owns both capture and payload classification.
The opted-in native binding policy recursively adapts vectors, hashes and wrappers;
callbacks retain their exact signature and capture permission. Task handles preserve
identity. No scheduler or runtime implementation change was required in this slice.

Initial admitted signatures: vector/hash `task_run` and `task_start`, each with
one-argument or item/context workers (eight signatures); vector `task_run_publish`
with one-argument or item/context work and a one-argument batch publisher (two);
vector `task_run_publish_unordered` with one-argument work/publish (one). Together
with `task_synchronize`, all five names now have a source path. This is not the
complete V-R02 acceptance surface.

The 23-task execution/recovery run `20261021-task-submission-06` passes on dev002
with production default-prelowered cold/warm builds. Final validation adds malformed
metadata rejection and sanitizer lifetime/cleanup probes; results are recorded below.
The 25 focused collection regressions in `20261021-task-native-collection` pass.
Historical attempts retain fixture spacing errors, null capability/mapping failures,
provider representation mismatches and the corrected hash entry accessor.

A separate pre-existing null-comparison lowering issue was exposed: nullable `===
null` leaks `scpp/generated/operators.hpp` into template-free application inputs.
The original failures remain in `task-submission-05`; state proofs use coalescing.
This slice does not repair equality or claim that the failure disappeared.

Remaining acceptance at the initial typed-submission checkpoint (subsequent progress below):

- Custom index callbacks (key preservation, collisions, nullable append), prefilled
  result targets and vector sparse/negative-key rejection.
- Replacement/omission error handlers, timeout shapes and repeated-error joins.
- Publisher context arity, batching options and all admitted signature controls.
- Boxed mixed/dynamic inputs and output-family reconciliation; wider boxed/nested
  resource transfer remains forbidden without a proved policy.
- Cancellation/concurrency integration, retained publication checks and final API
  coverage audit. Existing runtime-only proofs are not a substitute for these.

Overall remains **56/64 closed (87.5%), V-R02 partial**. Both prerequisite approvals
are resolved; there is no pending permission request for this checkpoint.


Final typed checkpoint evidence:

- `task-submission-07`: all 22 production execution cases pass. Its new malformed
  metadata task initially accessed the model before initialization; that test setup
  was corrected and the full contracts task passes in `task-submission-sanitized`.
- `task-submission-sanitized`: all six tasks pass, including five production
  ASan/UBSan builds/runs for background ownership, worker-error cleanup, nested and
  nullable payload transport, and unordered worker synchronization.
- `task-native-catalog`: all four startup/callable/execution regressions pass.

[Typed batch evidence](task-typed-batches-evidence.tar.gz), SHA-256:
`aea40e6f1a71291e9b4382920b5f27f37f9344456ea4b3e96b4f64b594843af3`.
The archive includes failed attempts, publication manifests, source fixtures and
final uploaded source hashes. Reproduce using `tests/task_submission.py --results
FRESH_DIRECTORY`; add `--sanitize` and repeated `--case NAME` for the five sanitizer
cases above. No full matrix or native compiler rebuild was run.


## Error callback execution prerequisite — 2026-10-21

Before registering the error-handler forms, a focused ASan/UBSan runtime probe on
dev002 confirmed three contract mismatches against `specs/builtins/tasks/first_pass.md`:
vector and hash handlers run inside worker loops, not on the calling coordinator;
ordered publication does not invoke a void-returning error handler. Replacement
values are correct in the tested vector/hash cases, so the mismatch is specifically
execution context and omitted invocation, not general callback transport.

`tests/task_error_context.py` records observations and explicit expected/observed
mismatches; a successful probe run does not mean semantic conformance. The observed
values are `vector_error_on_coordinator=false`, `hash_error_on_coordinator=false`,
`publish_void_handler_calls=0` (expected true, true, one respectively).

[Runtime prerequisite evidence](task-error-context-evidence.tar.gz), SHA-256:
`e7f40ae9250e8101185db232ae2f89700a3eb920bd838766776ceae6a77cf2a8`.

Approved repair: extend the existing batch
execution owner with a coordinator error-event handoff while workers run. Worker
catch paths publish item/error identity; coordinator processing invokes handlers,
returns replacement/omission outcomes and requests cooperative stop if a handler
throws. Both batch-local threads and the existing worker pool must use this path.
Ordered publication must advance omitted positions without suppressing later
successful values. Background batches use their existing coordinator thread.
No new public scheduler API or general thread-safety analysis is proposed.

This crosses the current adapter boundary: `tasks.hpp` vector/hash/publish loops,
`tasks.cpp` worker execution/join coordination and shutdown/cancellation interaction
must agree. Merely moving handlers after join delays handler-failure cancellation;
retaining worker callbacks changes the normative context and capture rules. Verify
thread identity, replacement/omission, handler failure, timeout, cooperative stop,
nested batches, pool and non-pool execution, repeated joins and shutdown, then source
cold/warm and retained/default-prelowered parity on dev002. The current goal requires
discussion before widening scheduler/transfer flow; the user authorized this bounded
coordinator dispatch extension on 2026-10-21.


### Coordinator dispatch implementation

The batch execution owner now drains synchronous worker error requests on the
calling coordinator, or the retained background coordinator for `task_start`.
Workers retain their item/event until acknowledgement. Handler results return to
existing collection/publication owners; handler failure requests cooperative stop
before propagating. Local workers and the reusable pool share the dispatch path.
Nested batches inside handlers use local workers to avoid waiting on a saturated
pool whose workers are awaiting that handler. Ordered publication explicitly tracks
omitted positions, so a void handler does not strand subsequent successful values.

This adds no public scheduling API or broader transfer permission. Source error
handler overload admission remains part of the unfinished V-R02 integration.

Verified on dev002:

- `20261021-task-coordinator-02`: all five runtime ASan/UBSan tasks pass.
  Coordinator coverage includes vector/hash replacement, omission, ordered
  publication with both try-lock settings, unknown exceptions, nested batches,
  handler-failure stop, timeout, cancellation, repeated joins/errors and shutdown,
  with local threads and the reusable pool.
- `20261021-task-coordinator-source`: all 23 default-prelowered production
  execution/recovery tasks pass, including cold/warm publication and contracts.
- `20261021-task-coordinator-context`: the historical observation probe now reports
  no contract mismatches. Original failing evidence above remains unchanged.

[Coordinator repair evidence](task-coordinator-evidence.tar.gz), SHA-256:
`b35557e4d57b45fe149994a4e0bc08fa4eab4cae49278fb5def1bfdbc0b82216`. Uploaded implementation/test hashes were verified on dev002.
No full matrix or native compiler rebuild was run. V-R02 remains partial; overall
closure remains 56/64 pending its remaining source acceptance inventory.


### Optional task placeholders — 2026-10-21

All eight typed vector/hash run/start contracts now accept the documented optional
null index/result/error slots and integer timeout, using existing metadata defaults
and ordered call preparation. Metadata resolves `null` to the canonical sentinel;
exact null argument conversion is permitted, while standalone null source storage
remains rejected. Provider defaults use the existing prepared literal path.
No new retained representation or runtime behavior was introduced.

`20261021-task-null-03` on dev002 passes all 25 production default-prelowered tasks,
including explicit placeholders, omitted defaults, cold/warm reuse, malformed null
metadata rejection and retained reject/repair/replay/fresh parity. The initial
explicit-null conversion failure remains in the evidence; it is resolved by the
exact argument conversion, not by relaxing source declaration rules.

[Optional placeholder evidence](task-null-evidence.tar.gz), SHA-256:
`63c9be3e18c28a67227efb35a22fe277b3e142f4596347b29c94620195424ace`. Final uploaded source hashes were verified on dev002.
Custom index/result/error callbacks remain outstanding; accepting null placeholders
does not close those forms or V-R02. Overall remains 56/64 closed.


### Custom placement retains input identity — 2026-10-21

The runtime-first custom-index probe exposed an existing omission defect: a dense
vector result was paired with the original input at the same dense position, so
omitting item 0 caused item 1's value to use item 0's custom key. Hash custom
placement also visited omitted input keys through a mutating result lookup.

The existing vector worker owner now returns its position-preserving optional
slots to custom placement. Default dense placement compacts only at its final
boundary. Void callbacks use optional null slots, preserving the distinction
between completed and absent work. Hash placement checks result membership before
index invocation. Both synchronous and background custom-target paths use this
rule; no scheduler or transfer-model change is introduced.

The focused runtime probe covers original item identity after omission, coordinator
index context, vector/hash background placement, prefilled targets, duplicate
keys, nullable append and negative/sparse vector-key rejection. These runtime
proofs precede source metadata admission; they do not close V-R02.

Verified on dev002: `task-placement-03` reports no placement mismatches and its
sanitizer assertions pass; all five `task-placement-runtime` suites pass. The
25-case default-prelowered source regression initially passed 23 cases and exposed
a missing void-slot specialization in this refactor. Corrected optional-null slots
pass both affected cold/warm programs plus contracts in `task-placement-source-02`.
The original omission observation and intermediate failures are preserved.

[Placement evidence](task-placement-evidence.tar.gz), SHA-256:
`e1466feb6d2d86be1ff132f93c762b0ba6671acdedcac77c1ee3b2e0a5ab3a6c`. Final uploaded source hashes were verified. No full matrix or
native compiler rebuild was run; original issue closure remains 56/64.


### Prefilled typed result targets — 2026-10-21

Existing generic runtime metadata now admits 16 same-carrier result-target
contracts: vector/hash input, run/start, one-argument/context worker, and value/void
worker results. Value targets match the worker payload exactly; void targets use
canonical null containers inferred from existing void batches. Required targets
retain explicit null index placeholders, followed by optional null error handler
and timeout. Both input and result payloads retain the existing transfer policy.

Vector targets append after prefilled elements; hash targets preserve existing keys
unless produced keys overwrite them. Source tests verify that caller targets remain
unchanged, background string result lifetime, and all 16 signature combinations.
Mismatched target payloads and void-to-int targets reject during preparation.
Custom-index, handler, cross-carrier-target and boxed-input admission remain open;
these same-carrier proofs do not close V-R02 or imply support for those forms.


Dev002 evidence: `task-target-02` passed 41/45 checks and exposed an older hash-void
worker-buffer defect (`pair<Key,void>`). The common runtime `task_value<void>` now
maps to null, replacing duplicated vector/hash/slot specializations. All four
failed hash-void contracts plus vector-void controls and retained contracts pass
in `task-target-void` (seven checks). All five runtime sanitizer suites pass in
`task-target-runtime`. `task-target-sanitized` passes two production sanitizer
programs and target-based retained reject/repair/replay/fresh checks. Across these
runs all 16 target contracts have default-prelowered cold/warm evidence.

[Target evidence](task-target-evidence.tar.gz), SHA-256:
`39b4d49d40bfb1b2def56ebd02f4e6967a9e315a60ca6a71c907fc568bd103cc`. Final source hashes were verified on dev002. Historical failures
remain archived. No full matrix or native compiler rebuild; V-R02 remains partial
and original issue closure remains 56/64.


### Indexed typed targets — 2026-10-21

Existing metadata now describes 24 indexed-target contracts: the 16 same-carrier
run/start, worker-context and void/value variants, plus nullable-int index results
for the eight vector-target variants. Index callbacks receive the original item;
vector keys are canonical int (or nullable int), and hash keys match the declared
key type. Default target and error-handler combinations remain separate work.

Synchronous index callbacks execute on the calling coordinator and do not cross
a thread boundary; they may retain an owned handle to caller-visible state.
Background index callbacks cross into the retained coordinator and therefore carry
`requires_transfer`. Worker callback and payload checks remain unchanged. Borrowed
callback transport has not been widened. The source probes cover coordinator
mutation, duplicate overwrite, unchanged input targets, nullable append, invalid
negative/sparse keys and background rejection of shared-object captures.


Dev002 `task-index-01` passes all 72 checks, including every indexed-target signature
and previous task source regressions, using default-prelowered cold/warm builds.
`task-index-sanitized` passes two production sanitizer cases (coordinator state
capture and background nullable index) plus retained reject/repair/replay and
indexed edit-versus-fresh parity. Final uploaded source hashes were verified.

[Indexed-target evidence](task-index-evidence.tar.gz), SHA-256:
`43ab680af5c679ef100d4af02886c3afb88384612559103d5eae3dc5e865d5e0`. This slice changes metadata/tests only, adds no runtime behavior,
and runs no full matrix or native compiler rebuild. V-R02 remains partial; overall
original issue closure remains 56/64.


### Inferred keyed result targets — 2026-10-21

Sixteen metadata contracts admit int/string custom keys without an explicit result
target: vector/hash input, run/start and one-argument/context workers. Generic
worker results retain void-to-null output mapping. Background index callbacks
retain capture-transfer requirements; synchronous coordinator callbacks do not
cross a thread boundary. Unsupported key types reject during preparation.

The runtime-first probe found absent background default-target overloads. Two
bounded adapters now construct the inferred hash target and delegate to the
existing retained custom-target path; no new scheduler, lifetime or publication
owner is introduced. Source coverage exercises all 32 value/void combinations,
duplicate-key overwrite, int/string output lookup, background joins, callback
errors and neighboring overload controls.

Nullable custom keys without an explicit target remain open: the inferred key
family must account for append keys as well as explicit keys, especially nullable
string keys. Explicit nullable-int vector targets are already proved separately.
This checkpoint does not decide that additional result-shape policy or close the
remaining error-handler, cross-carrier-target or boxed-input acceptance work.


Dev002 `task-default-index-source` passes all 37 focused default-prelowered
cold/warm checks. `task-default-index-errors` passes synchronous/background index
exception cleanup with production sanitizers plus retained target-shape edit,
reject/repair/replay and fresh parity. `task-default-index-04` passes enabled and
disabled runtime sanitizer probes; the adapters reuse disabled-target diagnostics.
The initial missing-overload failure and duplicate test-harness runtime-build
attempt remain archived. Final uploaded source hashes were verified.

[Inferred-target evidence](task-default-index-evidence.tar.gz), SHA-256:
`663c1f7d735de3182ecf0821ec8def392d8578b9588a5135951e73dbd0669030`. No full matrix or native compiler rebuild was run. V-R02 remains
partial and overall original issue closure remains 56/64.


### Error-handler result relation prerequisite — authorized 2026-10-21

The dev002 `task-error-contract-01` probe confirms that separate generic handler
returns `R` and `void` admit omission for a value worker but collide for a void
worker: both overloads match exactly. Treating this as successful compilation,
choosing an arbitrary candidate or enumerating payload types would conceal the
missing relation. A completely unconstrained handler return would instead admit
incompatible replacements until C++ compilation.

Proposed bounded model: one handler return variable `E`, with an explicit metadata
requirement `replacement_results: [{"result":"R","replacement":"E"}]`. The
existing metadata reader/registration owners resolve the variable names into a
typed requirement in `runtime_generic_contract`. Selected-call preparation checks
`E == R || E == void` before publication. It does not change overload ranking,
introduce coercions or create a general constraint language. Void workers therefore
accept void handlers, while non-void workers accept exact replacements or omission.
Existing transfer requirements remain independent: background handlers must be
transferable; synchronous coordinator handlers do not cross a thread boundary.

This new retained semantic relation was explicitly authorized under my-try's local
rules. The implementation checkpoint is recorded below. Its validation scope is:
malformed references, wrong replacement types, all void/value cases, wrappers,
retained reject/repair/fresh parity and default-prelowered runtime error handling
on dev002. Existing coordinator dispatch, omission identity and shutdown repairs
remain the execution owners; this proposal adds no scheduler or transfer redesign.
The approval is resolved; production admission is recorded below.

[Error contract probe evidence](task-error-contract-evidence.tar.gz), SHA-256:
`685eea384580b362aec5e68d93c9ab79c93d34ead706b77459ef29b3d0f83b72`. The passing probe records a semantic mismatch; it is not
conformance evidence or an issue closure.


### Ordered publication options — 2026-10-21

Both worker arities now accept publishers with or without task context, explicit
null error placeholders, timeout and maximum batch size. The eight combinations
of worker/publisher context and default/capped batches pass default-prelowered
cold/warm builds on dev002 (`task-publish-options`: 11 checks including existing
publication controls and retained contracts). The capped cases prove one-element
batches and input-order output. Publisher captures retain transfer restrictions;
context callbacks use existing runtime transport and status operations. No runtime
implementation changed.

[Publication option evidence](task-publish-options-evidence.tar.gz), SHA-256:
`4db101afe18b44b817c78997230350937fa184a3e10e40c3dc702e22d5d23dc2`. Uploaded source hashes were verified. Error-handler admission is
still outstanding. Its bounded result relation was explicitly authorized after
the proposal above; that approval is resolved and implementation follows next.
V-R02 remains partial, with 56/64 original issues closed.


### Approved error-result relation implemented — 2026-10-21

`replacement_results` now resolves named variables through the existing startup
contract owner into typed position pairs. Selected-call preparation admits an
exact replacement or void omission. Overload selection, result conversion and
transfer checks are unchanged; no backend or runtime implementation changed.

Dev002 `task-error-relation-02` passes 11 checks: exact/void/nullable admission,
wrong replacement and void-worker/value-handler rejection, malformed metadata,
retained rejection/repair/replay and void-edit fresh parity, and three executable
replacement/omission/void-handler cold/warm programs using default prelowering.
The original ambiguity probe remains runnable without `--relation` and its
historical evidence is preserved. Production task overload wiring remains next;
this prerequisite alone does not complete error-handler API admission or V-R02.

[Error-result relation evidence](task-error-relation-evidence.tar.gz), SHA-256:
`445d56055694d56b3517db7330ff44096eeebb70b6764ddda73f6eb1a4ce5774`. Final uploaded source hashes were verified; no full matrix or
native compiler rebuild. Overall remains 56/64 closed, V-R02 partial.


### Production error-handler admission — 2026-10-21

All 68 existing typed run/start/ordered-publish forms now have error-handler
counterparts. Generic handlers return exactly the worker result or void omission;
concrete void-worker targets require void handlers. Background handlers retain
capture-transfer checks. Synchronous handlers execute on the caller coordinator
and may retain owned shared state without crossing a thread boundary. Borrowed
callback transport remains restricted.

Inferred-key synchronous results now delegate to the existing explicit-target
placement owner, carrying the handler through the same path. This removes the
separate placement loops and shares enabled/disabled behavior. Scheduling and
error handoff remain with the previously verified coordinator dispatcher.

Dev002 default-prelowered verification passed:

- `task-error-submission-02`: 147 cold/warm cases, including 140 generated
  actual-handler-invocation cases covering the 68 signatures and seven independent
  replacement, omission, original-key, error-event and exception oracles.
- `task-error-lifetime`: two cases proving caller-coordinator mutation and owned
  background capture lifetime with repeated joins.
- `task-error-payloads`: four string, float, nullable replacement/input cases.
- `task-error-controls`: three neighboring source controls plus retained contract
  checks, including wrong replacements, invalid background captures and value/void
  handler edits with fresh parity.
- `task-error-index-runtime`: enabled and disabled sanitizer probes, including
  omitted vector/hash inputs with inferred keys.

The initial seven-case focused pass is also retained. The production handler
suite now contains 153 cases; the 147-case pass and six subsequent cases together
cover that set. Warm builds compile/link zero units. Source hashes were verified
against the remote checkout. Source cases are not claimed as sanitizer runs.

[Production handler evidence](task-error-submission-evidence.tar.gz), SHA-256:
`7cb657c321ea117f7d10a1c102fd7a5189cbca7adbd96f994fb1ef563c13afac`.

V-R02 remains partial: cross-carrier result targets, nullable inferred-key policy,
boxed inputs and final source timeout/cancellation/shutdown acceptance remain open.
The separate nullable identity/prelowering gap and historical evidence are preserved.
No full matrix or native compiler rebuild was run. Overall remains 56/64 closed.


### Cross-carrier target runtime probe — 2026-10-21

Dev002 `task-cross-target-01` passes with address/undefined sanitizers and leak
checking. Existing runtime owners support vector-to-integer-key-hash append and
indexed placement, nullable integer-key append, numeric-key hash-to-vector
placement, error replacement, both background directions, sparse-key rejection
and original target preservation. No runtime implementation change was needed.
This is runtime evidence only: production source admission and default-prelowered
cold/warm proofs remain next. String-key targets and other combinations are not
inferred from this probe. V-R02 remains partial.

[Cross-target runtime evidence](task-cross-target-evidence.tar.gz), SHA-256:
`938627348669b97f5e4284e0cffb8b504025c15d89c3bcc6b3d4b5886b2f7c58`. Uploaded probe hashes were verified.


### Integer-key cross-carrier source admission — 2026-10-21

The existing metadata owner now admits 96 additional contracts: vector input to
integer-key hash targets and integer-key hash input to vector targets, for
run/start, both worker arities, value/void workers, null/int/nullable-int index
callbacks and optional exact/void error handlers. Existing contracts and C++
bindings are unchanged. No runtime or compiler implementation changed.

Prefilled targets retain their values unless overwritten. Nullable keys append;
non-null vector keys retain negative/sparse rejection. Background callbacks retain
transfer checks, and result snapshots use the existing native-payload bridge.
String-key cross-carrier targets and nullable inferred-key policy remain separate
acceptance work; this checkpoint does not close V-R02.

Dev002 `task-cross-source-02` passed all 120 production default-prelowered
cold/warm cases. Warm builds compiled/linked zero units. `task-cross-controls` passed both existing target controls and
retained invalid-key/type rejection, repair/replay and cross-carrier edit/fresh
parity. The first source run exposed invalid numeric object-literal fixtures and
an incorrect void-error omission expectation; these fixture failures are retained.

[Cross-target source evidence](task-cross-source-evidence.tar.gz), SHA-256:
`c766cfbd125fcacee3db4561fc9e2fb0e566712d8912b87767623f3a9f492006`. Final uploaded hashes were verified. No full matrix or native compiler rebuild.


### Explicit keyed-target completion — 2026-10-21

Sixteen existing indexed hash contracts now infer input and output key types
independently. Forty-eight additional contracts admit vector input to string-key
hash targets and string-key hash input to int/nullable-int indexed vector targets.
They reuse the existing placement, transfer and provider owners; no runtime or
compiler implementation changed. Numeric object-key literal syntax is not added.

The extended dev002 runtime probe (`task-cross-target-02`) passes sanitizers for
string targets, hash rekeying, reverse vector placement and background joins.
Source verification passed all 80 default-prelowered cold/warm cases
(`task-keyed-source-01`); warm builds compiled/linked zero units.
`task-keyed-controls` passes existing hash/void targets and retained rekeying and
string-target edits with fresh parity, together with existing reject/repair tests.

Remaining placement work includes nullable custom keys for hash targets and
inferred targets. Boxed input needs its own runtime audit: inspection finds an
explicit mixed adapter but no corresponding dynamic adapter, and mixed background
execution must be checked against batch cancellation/progress state. These are
unverified concerns, not conclusions from this typed-target checkpoint. V-R02
remains partial; no full matrix or native compiler rebuild was requested.

[Keyed-target evidence](task-keyed-evidence.tar.gz), SHA-256:
`d04b0bc88e36b6b3eaa6b56d0b08d48ef63a1eb5bc10a4783442e9fb95956175`. Final uploaded source hashes were verified.


### Nullable integer hash targets — 2026-10-21

Sixteen additional hash-input contracts accept nullable-int index callbacks with
integer-key hash targets: run/start, both worker arities, value/void workers and
optional exact/void error handlers. Present keys overwrite; absent keys append
using the existing hash append owner. No compiler or runtime implementation changed.

The dev002 sanitizer probe confirms nullable-int append after existing integer
keys. It also probes nullable-string keys: present keys work, but null append
throws `hash_t::append requires integer keys`. This agrees with the typed hash
contract; this checkpoint does not invent numeric-to-string append or admit a
heterogeneous typed-key family. Nullable-string source admission and inferred
nullable-key result shapes remain open for explicit reconciliation.

`task-nullable-target-controls` passes retained nullable-hash edit/fresh parity,
existing rejection/recovery checks and the existing target-hash cold/warm control.
The first source pass proves all 20 absent-key cases; eight present-key fixtures
looked up the append position instead of the overwrite key. The fixture is repaired
and all 20 present-key cases passed separately. Together the runs prove all 40
source cases using default prelowering and cold/warm builds; warm builds compile
and link zero units. Original evidence is kept.
V-R02 remains partial.

[Nullable-target evidence](task-nullable-target-evidence.tar.gz), SHA-256:
`356bae7cfc20836255bf4b84e422a4e7214c4af34ab2e3bc5453c857ab9dcecb`. Final uploaded hashes were verified; no full matrix or native compiler rebuild.


### Inferred nullable-key result shape — 2026-10-21

The runtime-first probe found that inferred nullable indexes instantiated
`hash<T,nullable<K>>`, rejected by the typed-key contract. A task-owned key-shape
trait now maps an optional index to its payload key type. This is distinct from
the void-worker-to-null value mapping. All inferred run/start declarations and
adapters reuse it; placement and scheduler owners remain unchanged.

Thirty-two source contracts now admit nullable-int/string custom indexes without
an explicit target. A present key is stored; absent integer keys append; absent
string keys raise the existing typed-hash append error. No heterogeneous keys or
numeric-to-string conversion are introduced. The tasks spec now records this
interaction with the existing typed-hash contract. Explicit nullable-string target
source admission remains separate; its runtime behavior was already probed.

Dev002 `task-nullable-index-03` passes enabled/disabled sanitizer checks, including
integer append after explicit key 7 and nullable-string present/error paths.
Retained result-shape edits and existing reject/repair checks pass. Production
verification covers all 128 default-prelowered cold/warm cases: the first run
passed 120 and exposed eight invalid post-failure fixture lookups; the corrected
16-case synchronous/background string-error rerun passes. Warm builds compile/link
zero units. Original runtime compile failure and fixture failures remain preserved.
V-R02 is not closed by this checkpoint.

[Inferred nullable-key evidence](task-nullable-index-evidence.tar.gz), SHA-256:
`d03313d36a8be5537c48bb58197183c0b99a6a2d2c92b01dc4885032d0d48225`. Final source hashes verified; no full matrix or native compiler rebuild.


### Explicit nullable-string targets — 2026-10-21

Thirty-two contracts complete the explicit nullable-string target forms for
vector/hash input, run/start, value/void work, worker context and exact/void error
handlers. Present keys overwrite the named target slot; null keys raise the
existing typed-hash append error. Omitted failed items do not invoke placement,
so they preserve the target without raising that append error. Existing target
snapshots and background transfer restrictions remain intact. No compiler or
runtime implementation changed in this checkpoint.

Dev002 production default-prelowered verification passed all 80 cold/warm
cases; warm builds compiled/linked zero units. `task-nullable-string-controls` passes retained target edit/fresh parity,
existing reject/repair checks and a neighboring hash-target program. Runtime
boundary proofs are retained in the nullable-target and nullable-index archives.

[Explicit nullable-string evidence](task-nullable-string-evidence.tar.gz), SHA-256:
`ab0c1df9ba734aed4b0a288cb976211890de76232abb476c6728fecde77383d2`. Final source hashes verified; no full matrix or native compiler rebuild.


### Boxed-input runtime audit and shared batch state — 2026-10-21

`task-boxed-02` is a diagnostic probe, not conformance: basic mixed tables execute
and scalar inputs reject, but worker contexts use a different state object from
the background batch. The batch reports total/completed zero after two completed
items. The C++ overload availability probe also finds no dynamic-input entrypoint.
The initial probe authoring errors (dynamic alias arguments and boxed construction)
are preserved separately.

The bounded runtime repair introduces `run_mixed_with_state` alongside the existing
vector/hash execution owners. Blocking calls supply fresh state; background calls
supply their retained batch state. Startup counters initialize before launch and
result publication uses the existing lifecycle lock. No new scheduler, source
admission or transfer permission is introduced.

`task-boxed-03` passes ASan/UBSan with leak checking. It proves state identity,
total/completed counts and deterministic cancellation after the first item for
both packed and associative mixed inputs. Native test callbacks deliberately use
synchronized borrowed test state; this does not authorize source borrowed captures.
The dynamic overload remains absent. These are runtime proofs, not default-
prelowered boxed-input source proofs.

The bounded boxed-family integration below was subsequently authorized.
The current `task_batch` stores a mixed result and `task_join` returns mixed. Dynamic
containers cannot be put into it through an implicit mixed conversion. A proposed
bounded extension is scalar-element boxed snapshots with runtime rejection of
nested/shared/resource payloads, plus a dynamic-result batch handle reusing the
same execution/lifecycle owners and a family-preserving join overload. This would
change runtime-owned type metadata and provider transport, so it was discussed
before implementation. Existing typed/mixed batch signatures would remain stable.
The alternative is to defer dynamic background support explicitly rather than
silently convert families. Independent source lifecycle verification can continue.

[Boxed-state evidence](task-boxed-state-evidence.tar.gz), SHA-256:
`c3ac7766e60e416ba004fad09cadfedd84af53abc6bf4ffbd5c3f1e52b72c13e`. Final source hashes verified; no full matrix or native compiler rebuild.


### Production timeout integration — 2026-10-21

`task-lifecycle-timeout-01` passes four default-prelowered cold/warm source cases:
blocking/background execution, each with handled and unhandled timeout. Existing
`dt_sleep_ms` induces the timeout; a metadata-only test module selects production
tasks and datetime modules. Handlers observe `timeout=true`, `kind="timeout"` and
supply replacement 42; unhandled timeouts propagate on run/join. Warm builds compile
and link zero units. No implementation changed. These tests establish timeout
integration, not a hard scheduling deadline or arbitrary-code interruption.

[Source timeout evidence](task-lifecycle-timeout-evidence.tar.gz), SHA-256:
`8dea8751643d78cecc6b0000bf1b6a373cf7598a3fad93c5b2daa9e5d05a3836`. Fixture hashes were verified. The boxed-family prerequisite is now authorized;
independent lifecycle verification remains available. V-R02 is partial.


### Authorized boxed-family lifecycle foundation — 2026-10-21

The user authorized scalar-element boxed snapshots and family-preserving dynamic
batch results, with shared lifecycle owners and runtime rejection of nested/shared/
resource payloads. No approval remains pending for this bounded prerequisite.

`batch_lifecycle` now owns state, coordinator, join/error bookkeeping and retention.
Existing `batch` retains mixed result storage; `dynamic_batch` owns dynamic result
storage. Controls, shutdown and one wait/join lifecycle reuse the common owner.
Family-specific join overloads only retrieve their own result. Source type metadata
and boxed execution adapters are still outstanding; capture permission is unchanged.

Dev002 `task-family-lifecycle-02` passes ASan/UBSan and leak checking for dynamic
table repeated joins, repeated errors, lost-handle shutdown and the existing mixed
state/cancellation assertions. Test-only native construction deliberately isolates
lifecycle proof from unimplemented dynamic submission; it is not source admission.
All five `task-family-lifecycle-regression` runtime groups pass (callback transfer,
async core, shutdown, ownership and coordinator dispatch). The four production
`task-family-source-controls` timeout programs pass default-prelowered cold/warm
builds. `task-family-disabled` verifies ordinary inferred-key controls and disabled
dynamic join diagnostics. V-R02 remains partial.

[Shared lifecycle evidence](task-family-lifecycle-evidence.tar.gz), SHA-256:
`71dbfe1cb9db9aea13a79f5d505d37dd36d37409e5569a128f44794fc497a746`. Final source hashes verified; no full matrix or native compiler rebuild.


### Scalar boxed runtime adapters — 2026-10-21

Mixed/dynamic entrypoints now use one boxed snapshot adapter over the existing
vector/hash execution owners. It validates all input elements before dispatch and
copies scalar values into an owned packed/keyed snapshot before background launch.
Family traits own only input/output representation and batch-result type. Worker
and error-handler adapters reject nested results before transport; family-specific
assembly retains keys and the mixed/dynamic distinction. Shared/weak mixed roots
and nested table elements reject. Existing capture classification is unchanged.

`task-boxed-adapters-03` passes ASan/UBSan with leak checking for both families:
caller mutation after launch cannot change the snapshot, associative keys survive,
void results remain null, nested inputs reject before any callback, nested worker
and handler results reject, scalar error replacement works, repeated joins preserve
results, and shared mixed roots reject. The prior lifecycle/cancellation tests remain
in the same probe. Enabled/disabled checks and all five existing runtime regression
groups pass; all four default-prelowered timeout controls pass cold/warm builds.

This is runtime integration, not boxed source admission. Metadata must still admit
the new dynamic handle and the exact scalar/same-family result domain. The existing
`transfer_types` requirement permits recursively owned containers, so it cannot be
silently reused as a scalar-only boxed-result requirement or globally relaxed for
mixed/dynamic. Implement that bounded result-domain contract through the existing
generic-contract/preparation owners under the approved boxed integration. Preserve
static rejection of incompatible families and container result types; valid boxed
values still require runtime scalar checks. No further approval is pending.

The shared-root test in `task-boxed-adapters-02` exposed that `table_if()` also
returns shared storage. The family input owner now requires the owned mixed table
kind explicitly; the failing assertion remains archived.

[Boxed adapter evidence](task-boxed-adapters-evidence.tar.gz), SHA-256:
`61226d6f94f0b7c63b3e1314b271d5f703b32cbcefeb37d4252c8e76195d19b4`. Final source hashes verified; no full matrix or native compiler rebuild.

## Boxed task source admission checkpoint — 2026-10-21

Installed 16 mixed/dynamic run/start signatures (with/without worker context and
error handler), a runtime-owned `task_dynamic_batch`, and five dynamic lifecycle
control overloads. A named `boxed_results` generic contract resolves its family
once at registration and checks selected inferred results during shared preparation.
It accepts canonical bool/int/float/string, void and the matching boxed family;
containers, wrappers, fixed-width types and the opposite family reject before
publication. Existing exact replacement-or-void requirements constrain handlers.
General payload and capture-transfer classification is unchanged.

Dev002 default-prelowered evidence:

- `20261021-task-boxed-source-03`: all 64 source execution cases passed, cold/warm,
  covering both families, run/start, worker contexts, six result forms and nested
  input/worker-result rejection. The retained task failed due to a fixture's
  `vector<int>=>` tokenization; the corrected fixture passes in the later runs.
- `...-04`: all 24 replacement/omission/nested-handler cases and retained checks
  passed. Five malformed metadata cases, eight invalid result types, rejection /
  repair / replay and family/void edits versus fresh publication are covered.
- `...-05`: dynamic caller-mutation snapshots, repeated joins, done/status/progress
  and cancellation after completion passed. This is not active-cancellation proof.
- `...-06`: mixed caller-variable replacement snapshots and the same controls
  passed. Native mixed mutation evidence remains in the prior runtime checkpoint.
- `20261021-task-boxed-typed-regression`: existing typed task metadata rejection,
  repair/replay and placement edits versus fresh publication passed.

Thus 90 distinct execution cases passed across the focused runs; these are not a
full matrix or native compiler proof. All successful warm builds compiled/linked
zero files, and publication manifests forbid application templates. Native fixture
functions supply input tables and read/mutate dynamic values; production task
metadata, callbacks, handle transport and lifecycle calls are used unchanged.

Separate general boxed-operation blockers exposed by these tests:

1. `...-01`: mixed literal initialization passes concrete wrapper elements to a
   native `initializer_list<mixed_t>`; dynamic scalar literal construction lacks
   an adapter. Both fail before task execution.
2. `...-02`: dynamic index read unwraps the receiver but leaves its mixed key in
   concrete storage, so the native `null_index_read` call cannot bind.
3. `...-05`: mixed index store similarly leaves key/value arguments in concrete
   storage while unwrapping the receiver.

These failures remain archived and unfixed; successful fixture-backed task checks
do not close them. Repair belongs to general initializer/conversion/runtime-operation
transport owners and must not be hidden in task-specific adapters. Source boxed
construction/index usability and the final lifecycle/module/API audit remain open.
V-R02 remains partial and overall progress remains 56/64 (87.5%).

[Evidence archive](task-boxed-source-evidence.tar.gz), SHA-256:
`f5258050e0c5135cd3ea3998533beea553001aa843def5b9c64b4a29190da939`.
The archive preserves the failed probes, corrected fixtures, logs, publication
manifests and final verified source hashes, including the initial contracts-only
run that lacked the dev002 PHP PATH setup.

## Production lifecycle acceptance checkpoint — 2026-10-21

Nine additional source execution cases passed on dev002 with default prelowering,
cold/warm builds and zero warm compile/link work:

- Active cancellation with pool sizes 0 and 2: main observes worker status before
  cancelling; retained progress observes stop requested, fewer than all 100 items
  completed, zero active workers and a completed batch. The worker uses a bounded
  delay; this is source integration evidence, supplementing the deterministic native
  cancellation proof, not a scheduling/timing guarantee.
- Dropped handles with pool sizes 0 and 2: main prints before delayed work, and
  generated cleanup joins the work before process exit.
- Unhandled background errors with both pool settings report at shutdown; when
  main also fails, its division error retains precedence over the worker modulo error.
- Publication caps batches at one item, reports three published items/three batches,
  and exposes all eight measurement APIs with nonnegative timing/counter values.

The metadata audit checks all 21 free API names and task build requirements.
Separate fresh processes prove task names absent without opt-in and available with
opt-in; changing startup catalogs after compilation rejects without changing output.
Initial audit attempts incorrectly treated compiler reset as permission to change
startup metadata; those harness failures are preserved, and no compiler change was
made. Final corrected module checks pass in `...-04` and `...-05`.

The repeated-error source probes remain **blocked**, not accepted tests: both fail
at publication with `prelower_templates: throw operation requires concrete transport`.
`CPP_Syntax::generate_protected` also explicitly rejects catch transport in this
mode. The harness records these as `known_blockers`; its successful expected-rejection
checks do not prove repeated-error source behavior. Native repeated joins/errors
remain independently verified in the earlier runtime checkpoint. This is the
previously deferred general exception-transport prerequisite, not a task scheduler
failure, and has not been silently folded into task implementation.

[Evidence archive](task-lifecycle-acceptance-evidence.tar.gz), SHA-256:
`6640f8eeaaeba8a2241425115eaba22d42142f2d3ee394b8e4c584c95c368784`.
Includes `20261021-task-lifecycle-acceptance-01` through `-05`, source fixtures,
publication manifests, success/failure logs and final source hashes.

Remaining completion audit: reconcile documented signature boundaries against the
metadata and current evidence; keep boxed initializer/index and source exception
transport gaps explicit. V-R02 remains partial; overall closure is 56/64.

## Approved boxed prelowering repair — 2026-10-21

The user authorized repairs through the existing initializer, conversion and
concrete-index owners; exception transport remains separate. No runtime changes.

- Boxed scalar/null construction now consistently selects the existing compiled
  conversion operation. Shared preparation still owns the admitted scalar domain.
- Boxed initializer buffers use native boxed elements, including keyed entries;
  generic vector/hash buffers retain substituted concrete elements.
- Boxed reads/writes adapt all native operands and preserve owned read results.
- Nested store/append executes as one compiled path operation, reusing
  `CPP_Index_Write::resolve_path` for validation before mutable descent. No native
  child reference is reinterpreted as a concrete wrapper reference or allowed to
  escape. Source key/RHS evaluation remains in the existing sequencing owner.

Dev002 `20261021-boxed-prelower-02` passed **100 source execution cases** without
native input/access fixtures, including all 90 task cases and ten literal/index
cases. `...-03` passed **12 additional cases**: initializer order, mixed copy-on-write,
dynamic sharing, nested append, key/RHS order, root replacement during RHS, and
ordinary/boxed typed-container initializer regressions. Both runs used default
prelowering, verified template-free application manifests and zero warm compile/link
work. Retained tests additionally cover nested mixed/dynamic edits and typed
initializer edits versus fresh output, plus admission rejection/repair/replay.

`...-01` preserves the initial nested-write failure: per-step helpers attempted to
return native child references as wrapper references. The final whole-path helper
repairs that ownership error; no runtime algorithm or source semantics changed.
Indexed updates/compounds, broader keyed probes and additional extraction domains
are not claimed by this store/append/read slice.

[Evidence archive](boxed-prelower-evidence.tar.gz), SHA-256:
`f9c18ead4f096e2544d3ee5792b27d12396ba5efc7a6b7cde9a72a01eed746d1`.
Includes original failures, final fixture-free sources/module descriptors, logs,
publication manifests and verified source hashes. The earlier fixture-backed
checkpoint remains historical evidence, not the final source-acceptance boundary.

The bounded boxed-source prerequisite is complete. The final goal audit must
reconcile the explicitly separate source exception restriction and signature scope;
V-R02 is not marked closed by this checkpoint.

## Final evidence audit and remaining scope decision — 2026-10-21

All 33 SHA-256 archive references in this report matched their committed files.
The audit inspected saved summaries and execution/publication logs, including
S12's disk rejection/repair/retirement/fresh proof, rather than treating metadata
rows or historical prose as proof. A remaining weak runtime check was strengthened:
`verify_boxed_family` now starts actual failing workers and actual failing handlers,
then joins each twice, for mixed/dynamic and pool sizes 0/2. This replaces reliance
on manually injected failure state for the full boxed start/join route (the earlier
low-level lifecycle test remains). Dev002 ASan/UBSan and leak checking pass.
The initial native probe used the source helper name rather than the provider's
`configure_default_worker_pool`; that authoring failure is preserved.

[Final runtime audit evidence](task-final-audit-evidence.tar.gz), SHA-256:
`35fbd114ea73d5d59f25c755da31c98b565a663a25a4a02120f546462b3ffab3`.

Source throw/catch/finally prelowering remains explicitly separate, as agreed;
no claim is made that the source repeated-error fixture executes. Native error
retention plus source unhandled-error propagation are distinct proved facts.

One acceptance ambiguity still needs a user decision: typed vector/hash inputs
support custom index callbacks and prefilled targets; boxed mixed/dynamic inputs
currently expose null index/target placeholders only. The normative general
placement prose does not explicitly exclude boxed inputs. The earlier bounded
boxed snapshot approval did not settle all placement/result-family combinations.
Consequently this audit does **not** silently classify boxed custom placement as
an intentional language restriction or declare V-R02 complete. The user has been
asked whether to retain it in this goal for design/implementation or explicitly
defer it and close the verified bounded scope. No implementation is authorized
by the audit itself, and V-R02 remains partial pending that decision.


## Acceptance closeout — 2026-10-21

The user explicitly chose to keep boxed custom index callbacks and prefilled
result targets unsupported in v0.2, and to finish this goal without that extension.
Both `v0.1.77` and `release/0.1.78` have identical relevant task code: boxed
run/start accept null index/target slots only. Their implementation summary limits
boxed input to base calls and custom placement to typed vector/hash inputs.
The normative task contract now makes this boundary explicit. The earlier open
scope question and unadopted design proposal remain historical evidence.

The acceptance inventory reconciles all 21 names and the approved signature groups
with dev002 default-prelowered source, runtime, lifecycle and retained/fresh proofs.
The preceding checkpoint audited 33 archive references; this documentation-only
closeout rechecked all 34 references, including the final runtime audit archive,
without rerunning compilation. S12 and
V-R02 are implemented and verified within the agreed scope: 57/64 issues resolved,
zero partial and seven open. Source exception transport, wider project workflow
proof and transfer domains excluded above remain explicit; no passing source
throw/catch proof, exhaustive generic-domain proof or full matrix is inferred.
