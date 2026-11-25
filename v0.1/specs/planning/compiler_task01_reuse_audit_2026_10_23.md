# TASK-01 joined shared-task reuse audit
Doc Status: planning

2026-10-23. Source inspection only; no implementation, native concurrency run or
bootstrap. Accepted scope remains joined task_run_publish_unordered and its associated
synchronous task_synchronize contract, with programmer-owned synchronization.

## Existing runtime and compiler foundations

- runtime/include/modules/tasks/tasks.hpp::run_publish_unordered already delegates
  to the existing bounded run executor, uses a per-invocation publication mutex,
  counts completed publications and returns after the underlying joined operation.
  Work runs outside the publication lock; publication runs under that lock on workers.
  The executor already has worker error capture and join/rethrow handling.
- publication_context.hpp uses a thread-local active batch mutex and RAII restoration.
  synchronize locks that mutex and invokes its callback synchronously. Publication
  and synchronize bodies disable the active context, so absent/nested use rejects
  rather than recursively locking. Batches do not share a global destination lock.
- tasks.json already registers generic input/result signatures, transfer_types T/R,
  and requires_transfer on both work and publish callbacks. synchronize is a plain
  synchronous void callback with no requires_transfer flag.
- Capture_Preparation already builds by-value capture environments, requires copy
  capability, records borrows, and recursively validates known nested closures.
  Shared handles are usable in ordinary closures; their task transfer is rejected.
- Runtime_Call_Preparation and Call_Preparation consume registered payload/callback
  contracts. Ordinary emission inserts guards for erased callbacks; CPP callback
  transport preserves permission across native-provider and prelowered wrappers.
- vector/shared-handle transport exists and is reusable; source task API uses shared
  source_work input/result and queue captures, not deep-copied graph records.
- tests/task_transfer.py already covers ordinary shared-capture callability plus
  rejection at transfer boundaries, erasure and ordinary/concrete modes. Existing
  tasks metadata/callback/submission/error/lifecycle tests provide fixtures; their
  presence does not prove this new admission or current concurrent correctness.

No new scheduler, worker pool, mutex implementation or joining mechanism is indicated.
Preserve partial publication on failure and join-before-return/rethrow. Do not infer
whole-compiler race freedom from these mechanisms or sequential PHP execution.

## Missing model and why changing one guard is wrong

1. **Payload admission policy.** Transfer_Preparation::classify currently recognizes
   scalar/enum/string owned values and selected recursively owned containers/wrappers;
   shared source objects fall through to forbidden. require_payload requires owned_value.
   Introduce a distinct joined-shared admission policy consumed from registered task
   contracts, rather than mark source_work or all class handles universally transferable.
   Preserve existing resource/unique/borrow exclusions and capability requirements.
2. **Capture permission with API scope.** prepared captures currently classify owned,
   callable or forbidden. Runtime callable_t and transfer_callback retain one bool;
   syntax.php and callback_transport.php carry the same bool through erasure, copying,
   adaptation and prelowering. A shared callback cannot simply receive true: that
   would let it pass other existing requires_transfer APIs.
3. **Policy-preserving erased transport.** Extend the existing permission carrier and
   required-policy check so the same callback is accepted by the joined shared API
   and rejected by isolated/detached APIs. Exact enum/flags representation is design
   work; no second callable abstraction is justified by this audit. Known closures
   reject statically; erased callable values keep an appropriate runtime guard.
   Nested callable captures must preserve the applicable permission transitively.
4. **Callback roles.** Work and publish are both worker-executed retained callbacks,
   although publication is serialized. synchronize is synchronous and nonescaping;
   it must not inherit an arbitrary cross-thread-transfer requirement. Reuse its
   runtime context/nesting checks. No arbitrary borrowed-capture expansion is approved.
5. **Observable proofs.** Native proofs must establish same-object inputs/results/
   captures, lifetime through joining, each work item dispatched once, serialized
   protected accesses/publication, joining on worker/publisher failure and continued
   rejection of the same shared callback at APIs outside the bounded domain.

This is a small permission-model extension across metadata, shared preparation,
callable runtime transport and C++ emission. It is not metadata-only and cannot be
honestly represented by merely deleting require_payload/require_transfer checks.
Before implementation, present the bounded owner/representation design. If the
existing carrier cannot express it without a wider callable redesign, stop and
request scope review under repository rules.

## Overlap / dependencies

- TASK-02: separate source race fix. Correct retained-type lookup synchronization
  before a concurrent compiler bootstrap. It can land independently and is not
  replaced by task permission changes. No automatic read/write race analysis.
- NULL-01 / OWN-01: reuse settled ownership/presence queries; nonnullable shared
  source records provide the first task proof. Do not rebuild nullable semantics
  inside transfer policy or grant weak/unique/resource behavior accidentally.
- SELF-01: compiler task callbacks capture queue/work values, so ordinary shared
  payload proofs do not require changing borrowed $this capture. Owning self captured
  into a separate local may use SELF-01 where actually needed; implicit capture
  semantics must not silently change.
- CH15 registry audit: existing vector/hash transfer branches are inventory evidence.
  TASK-01 policy must not add source class-name exceptions. Do not silently broaden
  this slice into remediation of every task/container family across the compiler.
- BIND/AST: no new task API registration or iterator/virtual protocol is required
  for the minimal shared-record task proof. Full compiler execution has its other
  existing dependencies; isolated task proofs can proceed without all of them.

## Revised workload

Revise TASK-01 from **8–15x to 8–12x**. Strong runtime and transport reuse lowers the
upper range, but the erased-permission limitation prevents calling this a 2x guard
change. This is relative volume, not time, and TASK-02 is separately estimated.

Overlapping work components:
- Policy/metadata and static payload/capture admission: roughly 2–3x.
- Erased callable permission, guards and ordinary/prelowered transport: roughly 3–4x.
- Native concurrency/failure/lifetime, rejection and retained proofs plus docs: 3–5x.

Preferred slices after authorization:
1. Define required-policy versus callback-permission facts with backwards-compatible
   rejection for existing isolated APIs; prove a known shared input/result/capture.
2. Carry facts through erased/nested callbacks and both lowering modes; demonstrate
   acceptance in the joined API and rejection elsewhere using the same callable.
3. Integrate synchronize's existing execution role and native publication/join/failure
   proofs; land TASK-02 before the compiler's first native concurrent bootstrap.

No changes to scheduling, cancellation/rollback, arbitrary shared resource transfer,
background graphs or global race-freedom analysis. Runtime API protocols remain,
while current v0.2 shared-admission restrictions require an explicit bounded contract
amendment. Neither this audit nor the estimate authorizes implementation.

## Design disposition — 2026-10-23

The user accepted the bounded API-policy versus callback-permission design in the
[decision backlog](compiler_portability_decisions_2026_10_23.md#task-01-bounded-policypermission-design-accepted--2026-10-23).
Metadata selects isolated versus joined-shared requirements; callback permission is
preserved through erasure and checked at the consumer without upgrading it. Existing
synchronize context/lifetime rules remain separate. Representation details and proofs
remain implementation work; no implementation authorized. Estimate remains 8–12x.

## TASK-01 consumer trace at implementation restart

Source inspection at `6d7a3fd13` confirms the following coordinated edit surface.
This is reuse evidence, not a passing execution proof. The later accepted-backlog
implementation goal authorizes implementation of the bounded design above; the
historical statements that implementation was paused describe the discussion stage.

| Owner | Existing path to reuse | Required preservation |
| --- | --- | --- |
| Runtime catalog | `reader.php`, `structures.php`, `patterns.php`, `register.php` | Existing `requires_transfer` remains isolated admission; reject invalid policy declarations rather than silently weakening them. Carry policy through both concrete registration and generic substitution. |
| Callable contracts | `compiler/callables/structures.php`, `catalog.php` | Required callback policy belongs to parameter contracts. Exact erased-contract comparison and fixed-value/default restrictions must include it. Environment permissions remain separate from signature identity. |
| Payload preparation | `Runtime_Call_Preparation` checks `transfer_positions` before constructing the substituted signature | Apply the registered policy at this existing check. Changing callback checking alone cannot admit shared input/result records. |
| Capture preparation | `Transfer_Preparation`, `Capture_Preparation` | Retain copy and borrow checks; distinguish shared-only admission from isolated admission and recursively preserve nested callable restrictions. |
| Call preparation | `Call_Preparation` consumes parameter `requires_transfer` | Known closures reject statically under the consumer's required policy; erased values retain a runtime check. |
| Ordinary lowering | `syntax.php`, `declarations.php` | Closure environment construction, erasure and generated provider guards must use the same permission carrier. Calling a joined API must never upgrade that carrier. |
| Concrete transport | `callback_transport.php` | Carry permissions through constructor ABI, provider adaptation and generated query/guard forwarding; the present Boolean constructor/query would discard a second permission. |
| Runtime carrier | `scpp/callable_t.hpp` | Both `callable_t` and `transfer_callback` preserve permissions. Empty and moved-from erased callables must not gain permission; copies preserve it. |

Implementation representation should use the existing carrier with explicit
isolated/joined permission bits, intersected across captures; no second callable
abstraction is needed. The generated concrete facade must carry the full permission
value, not reconstruct it from the historical isolated Boolean query. Keep the old
isolated query/guard meaning when introducing policy-aware consumers.

First behavioral proof must exercise one shared-capturing callback ordinarily,
through the joined API, and through a rejecting isolated API, in both lowering modes.
Add nested erased-callable controls so a copy/provider round trip cannot elevate
permissions. Re-run the pending retained-lookup v0.2 proofs after admission works.
No production changes, new bootstrap measurement or task execution are claimed by
this trace. Scheduler, publication context and synchronization ownership remain unchanged.

## TASK-02 implementation boundary revalidated

At the current checkout, Symbol_Collector already owns the publication-mode decision:
register_source_type calls define_source_type directly when global is null, otherwise
inside task_synchronize. Reuse that same decision for a private retained-type snapshot
helper called by previous_member_owner and previous_enum. The helper should copy the
vector returned by scope::source_types_named while holding the batch lock, then return
it before unseen/kind/syntax reconciliation. This keeps mutable source work outside
the critical section and leaves Scope/Source_Type_Declarations as data/query owners.
Neither caller is currently inside a synchronized callback; avoid acquiring the lock
inside define_source_type or publish, which already run within writer critical sections.

This is an existing source synchronization protocol, not TASK-01 semantic permission.
Native proof must exercise the extracted production helper alongside registrations
under the same scheduler lock, plus full retained parse/standalone PHP controls.
Do not equate the sequential PHP proof with concurrent safety or mark TASK-02 done
before the native overlap proof is available. No implementation in this audit update.

## TASK-02 source implementation checkpoint

Symbol_Collector::retained_types now snapshots under the writer's existing batch lock,
with a direct standalone path. source_type_snapshot is an invocation-local result
carrier because the portable converter accepts by-value callback captures only.
No new retained state, lock ownership or semantic task permission. Parser regressions
and actual retained class/enum/new registration/failure/retry tests pass. An extracted
production-helper/native-hash fixture passes legacy native with a four-callback barrier
and ThreadSanitizer; the reader-unlocked negative control produces a data race.
Only generated fixture scheduling/control instrumentation is modified; production
helper and scope/registry policy remain source-authored.

Both v0.2 modes still reject the fixture's shared callback captures. TASK-02 stays
partially complete until TASK-01 permits the accepted joined shared pattern and this
fixture executes in both modes. The task runtime library was not itself instrumented
in this sanitizer run; generated lookup/hash/read/write/callback code was instrumented.
