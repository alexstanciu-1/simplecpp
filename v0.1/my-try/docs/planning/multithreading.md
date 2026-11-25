# Multithreading and per-file preparation plan
Doc Status: planning

This document owns the active multithreading (MT) design discussion, with incremental
correctness as a constraint. The discussion began as incremental processing, but its
primary focus is file ownership, shared writes, synchronization and publication after
parse/collect. It records agreed constraints separately from proposed mechanisms.

Current execution belongs to [work queue](../lifecycle/work_queue.md),
[preparation](../../04_analyze/prepare/README.md) and
[lifecycle](../lifecycle/incremental.md). Remaining incremental work belongs to the
[incremental plan](incremental_strategy.md). This planning document does not claim
implemented parallel preparation or native concurrency evidence.

## Agreed boundaries and ownership (2026-10-02)

Discussion/docs only; implementation has not been authorized.

- Parse/collect must join before semantic lookup begins. This was already decided;
  retain it as a design constraint rather than reopening it in later discussions.
  Declaration membership and deletion reconciliation must be stable for lookup.
- Affected declarations settle before parallel body preparation and fragment
  rendering. This is a distinct boundary: collection establishes which declarations
  exist; declaration preparation establishes their effective signatures/layout facts.
- File workers compute and mutate their own facts. Declaration and body work
  identities continue to own incremental validity; file batching must not turn a
  body edit into whole-file semantic invalidation.

Open ownership question: catalog preparation's shared writes before deciding that
one coordinator must own all graph changes/readiness. Prefer segmentation that
allows most, ideally all, semantic traversal to read shared data without writing it.
A coordinator-owned graph is a proposal, not an agreed implementation requirement.

Initial implementation inventory (not an exhaustive write/lifetime audit):

| Data | Current preparation writes | Segmentation candidate / unresolved constraint |
| --- | --- | --- |
| Global scope membership and source-type declaration index | Deletion cleanup unregisters declarations and retires source-type links. | Finish reconciliation before semantic workers begin; freeze lookup membership during preparation. |
| Canonical type registry | `canonical()` lazily allocates concrete types; `intern_application()` mutates its application index and allocates canonical identities. | Establish concrete identities before parallel use. Constructed applications need an explicit interning/publication boundary; worker-local duplicate canonical identities are not acceptable. |
| Shared scope/name lookup observations | `observe()` creates shared lookup records and adds observers; detachment removes observers and empty records. | Workers record observations locally; merge/index them outside semantic traversal. Preserve missing and ambiguous lookup dependencies. |
| Reverse declaration dependencies | `depend()` inserts into another owner's dependents; detachment/retirement removes reverse links. | Consumer-owned outgoing edges during traversal; build/update reverse indexes at a coordination boundary. |
| Work readiness, versions and invalidation | Recursive prerequisite preparation, settlement and failure propagation mutate other owners and queues. | Single writer per file/work owner; prerequisite readiness and cross-file notifications need an explicit scheduling protocol. |
| Syntax facts, outgoing dependencies, diagnostics and preparation-change handoff | Rebuild/settlement mutate attached facts and per-source work state. | File-owned writes, provided no other worker reads facts while they are being replaced. Publish declaration completion before consumers proceed. |

`Type_Registry::use()` creates an occurrence value and reads the registry;
it does not intern a new type. Access through `Model` alone does not imply a shared
write. Conversely, a reverse dependency stored on a declaration is cross-file shared
mutation even though it is not a global root.

Accepted design direction from discussion: separate semantic facts, derived reverse
indexes and invocation-local scheduling state. During semantic traversal, file
workers should read published shared facts and write only file-owned facts and
observations. Outgoing dependencies belong to the consuming work owner; reverse
declaration and lookup indexes can be maintained from those observations outside
semantic traversal. Preserve missing/ambiguous lookup invalidation and failure/retry
observations. Exact merge, publication and scheduling protocols remain open; this
does not select a coordinator implementation or prove concurrency safety.

### Shared collection mechanisms — agreed constraints

For collections that must accept additions while other preparation workers read:
encapsulate concurrent append and indexed query only. No traversal, replacement,
removal or other collection operations are exposed. Published indexes stay stable;
readers see fully initialized entries, not merely reserved slots. Published record
mutation needs separate ownership. Storage must support safe reads during growth:
non-relocating storage or sufficient by-value reads are candidates, to be decided
at actual use; returning by value must itself obtain the value safely during append.
No storage implementation is selected by this discussion.

For submissions consumed by future stages, workers may build locally and submit
under the shared destination lock. All writers use that destination's lock. This
is separate from concurrent indexed access; later-stage traversal happens under its
own phase/ownership rules.

Initial preparation classification:

- Canonical type records (`Type_Registry::$types`, addressed by type ID) are the
  concrete candidate for concurrent append/index access if workers create applications
  during preparation. Existing immutable records remain readable while new ones are
  published. If creation is restricted to joins, concurrent append/read is unnecessary.
- Type definitions (`$definitions`, addressed by definition ID) have the same storage
  shape, but current preparation does not create definitions: built-ins initialize
  before it and source structures register during collection. Keep this read-only
  during preparation; future generated definitions would require a separate review.
- The application interning trie and concrete-definition-to-type map are lookup indexes,
  not append/index-only stores. Canonical application uniqueness needs a distinct
  synchronized find-or-create protocol; safe record append alone does not provide it.
- Source-type links, scope/name pools and provider metadata should be stable during
  preparation. Reverse dependencies and lookup observers require traversal/removal;
  they do not fit this restricted collection. File facts remain file-owned. Completion,
  dependency and diagnostic submissions are future-stage submissions, not reasons
  to introduce concurrent global record stores.

This inventory identifies one current candidate, not an approved implementation or
an exhaustive native concurrency audit. Identity allocation/publication and registry
reset boundaries must be preserved; numeric ID allocation must not expose an empty slot.

### Current preparation shared-write trace (2026-10-02)

Static inspection of the my-try PHP implementation, not a native race proof.
Scope: `File_Preparation`, `Preparation_Worker`, semantic preparation and its
scope/type/conversion/operator callees. Backend assembly and parked LLVM are excluded.
The current scheduler is sequential; entries below identify what would cross file
ownership if its work were dispatched concurrently, not bugs in today's execution.

Operations below describe logical membership, not a claim that the current containers
implement concurrent append. Proposed solutions are discussion candidates.
`Local?` means the proposed semantic worker path writes only file/invocation-owned
state (Yes), or still performs shared maintenance/publication (No). Yes does not
mean the later submission/merge is local. Class names are provisional responsibility
names, not existing classes or an agreed class decomposition.
Timing describes the proposed execution, not today's sequential call order.
Here preparation means semantic declaration/body work. A boundary between declaration
rounds or between declaration and body phases is during the overall preparation
stage, but outside parallel semantic traversal. Submissions may take the destination
lock during preparation; shared index reconciliation runs at the indicated boundaries.

| Code path / shared data | Appends / inserts | Updates | Removes | Local? | Timing relative to preparation | Proposed code class | Proposed technical solution |
| --- | --- | --- | --- | --- | --- | --- | --- |
| `observe()` / scope lookup index and observer sets | New scope/name/kind observation; consumer membership | Initializes candidate snapshot on creation | None here | Yes | During: local observations/submission. Merge at preparation boundaries or after; declaration observations must be available before dependent invalidation needs them. | `File_Preparation_Observations` → `Preparation_Lookup_Index` | Record observations in file-owned batches; submit under destination lock. Merge into reverse lookup index outside traversal. |
| `depend()` / declaration work and reverse edges | Lazy target work creation; consumer reverse membership | Consumer-owned dependency version | None here | Yes | Before: create work identities. During: local outgoing edges/submission. Reverse-index merge at required declaration boundaries; final merge after bodies. | `File_Preparation_Observations` → `Preparation_Dependency_Index` | Create work through its file owner before dispatch; record outgoing edges locally and merge reverse edges at a boundary. |
| `require_declaration()` -> `declaration()` / prerequisite work and facts | Work/facts if needed; change handoff entries | Target processing/ready state, rebuilt facts, member flags, version; recursively touches prerequisites | Declaration queue entry; old registrations during rebuild | Yes | During declaration preparation: prerequisite scheduling/completion. During bodies: read settled declaration facts. | `File_Preparation_Worker` + `Preparation_Scheduler` | Only owning file prepares target. Consumers read published completed facts; scheduler requests prerequisites and suspends/resumes declaration work. Ready reads must not mutate target state. Cycle protocol remains open. |
| `detach_dependencies()` / reverse edges and lookup index | None | Replaces consumer-owned outgoing collections | Consumer from target/lookup dependents; empty lookup from scope index | Yes | During: local observation replacement. Reconcile shared removals at declaration boundaries or after bodies, preserving failed-attempt observations. | `File_Preparation_Observations` → `Preparation_Dependency_Index` / `Preparation_Lookup_Index` | Consumer publishes replacement outgoing observations, including those retained after failure; reconcile reverse-index delta at a boundary. |
| Change/recovery notification -> `schedule()` / dependent work | Deduplicated pending queue membership | Consumer change/state and declaration flags | None directly | Yes | During declaration settlement; process events before affected bodies dispatch, possibly between declaration rounds. | `Preparation_Event_Inbox` → `Preparation_Scheduler` | Submit version/change events; scheduler selects consumers before body dispatch. Owning file applies retained state transitions. |
| `failure()` / dependency graph and work states | Failure-attempt membership; pending queue membership | Failure/message/change/state on owner and transitive consumers | None directly | Yes | During: local failure/submission. Propagate before dependent dispatch/completion; finalize body failures before preparation reports success/failure. | `Preparation_Event_Inbox` → `Preparation_Scheduler` | Submit failure events; propagate against stable reverse indexes at coordination boundary; preserve retry and unavailable-prerequisite state. |
| `changed_lookups()` / shared lookup snapshots | Pending work membership | Candidate snapshots and observer work state | None directly | No | Before semantic dispatch, after parse/collect and deletion reconciliation. | `Preparation_Lookup_Index` + `Preparation_Scheduler` | Reconcile dirty lookup pools after collection/deletion, before semantic dispatch; later lookup observations merge separately. |
| `intern_application()` / type store and application trie | Trie nodes, canonical type record | Leaf publication, ID allocator/exhaustion state, application count | None | No | During, when a new application is needed; existing identities are read-only. Optional earlier formation remains open. | `Canonical_Type_Store` + `Type_Application_Interner` | Concurrent append/index store for immutable canonical records; separate synchronized find-or-create for application uniqueness. Read-only existing-key lookup must be explicit and safe during index insertion. Lock scope/sharding remains open. |
| `canonical()` / concrete type store and definition-to-type map | Canonical type record; definition mapping on miss | ID allocator/exhaustion state | None | Yes | Before: initialization/collection creates identities. During: read-only lookup. | `Type_Registry` (read-only preparation API) | Eager identity creation during initialization/collection, already done for current built-ins/structs; use strict read-only lookup during preparation. Future late definitions need explicit publication. |
| Deletion / retirement / scope and dependency indexes | Retirement handoff; pending consumer membership | Deleted flags, detached work/facts, source state | Reverse/outgoing registrations, scope memberships, source-type links, collected rows, pending queue entries | No | Before semantic dispatch, after the successful parse/collect join. | `Preparation_Reconciliation` | Exclusive reconciliation before semantic dispatch: notify consumers while reverse links remain intact, then unlink. No workers reading retired mutable data during this boundary. |

Queues above belong to the current invocation-local worker, not global roots. They
would become shared scheduling data if one worker were reused concurrently; separate
file workers alone do not eliminate writes to retained target/observer records.

Concrete ordinary body example: `prepare_call()` resolves through `Scope_Lookup`
(shared observations), then `require_declaration()` (reverse edge and target state
mutation), then reads the prepared signature. A declaration-first barrier alone does
not make this existing body path read-only. Field access follows the same pattern
through `require_record()`; local named source types also require declaration work.

Writes that remain local under one writer per file: syntax attached facts and cleanup,
new literal/operator/conversion decisions, context locals, outgoing dependency/version
observations, the selected owner's own state and its source `preparation_changes`.
They cross ownership today only when recursive prerequisite processing enters another
file. Preparing multiple bodies of the same file concurrently would need additional
rules for per-source handoff writes; that is not the agreed per-file execution shape.

Read-only shared paths inspected: registry `type()`, `definition()`, `use()` and
`has_capability()`; source-declaration lookup; scope candidate getters; published
signature/field reads. Conversion and operator selection build local result records;
their built-in identity helpers retain the `canonical()` caveat above. A language
mutation expression prepares a local decision; it does not mutate global compiler
storage merely because the source operation is a write.

Implication for the proposed mechanisms: only canonical record storage fits concurrent
append/index access directly. Interning needs its own uniqueness protocol. Observation,
dependency and invalidation events can be submissions, but their current synchronous
effects must be assigned to a later boundary before replacing them. Recursive target
preparation/readiness is the principal remaining cross-file execution responsibility.

### Canonical type creation — open discussion

The registry is the current identity owner. Built-in concrete types are eagerly
created at initialization. Source structure registration also already creates its
concrete canonical identity (`registered_type_catalog::define_source_structure`).
Thus `canonical()` is potentially mutating, but ordinary lookup of these registered
types should reuse existing identities. Establishing a strict read-only lookup API
would make that boundary explicit rather than relying on lazy creation not firing.

Constructed applications are currently interned during type preparation. Their
identity depends on the template definition and ordered canonical arguments,
including representation modifiers and completed default arguments. Equivalent
requests across files must converge to one identity. Formation/contract validation
and completed layout/body preparation are distinct responsibilities.

Two publication options remain open: workers may discover and validate application
requests locally for interning at a type-publication boundary, or use synchronized
find-or-create during preparation with concurrent indexed access to published type
records as described above. No option has been selected. Existing applications use
read-only lookup.
Nested applications may need staged publication or structural requests; no duplicate
worker-local canonical IDs and no eager enumeration of all type combinations.
Declaration and body work can both discover types, so the declaration/body boundary
alone cannot freeze the registry for every future body feature. Discuss whether
explicit type syntax can be resolved in advance, and how future inference or
specialization requests are handled without a registry write in semantic traversal.

Specify when each data segment becomes readable and who may write it.
Do not assume that per-file dependencies are acyclic: declaration-level readiness
must preserve valid mutual function calls and diagnose real by-value layout cycles.
Non-goals: implementation, LLVM changes, output partitioning and overlapping compiler
sessions. Native race/lifetime claims remain unproved.

## C++ backend multithreading — code review and proposal (2026-10-02)

Static inspection of `05_backend/cpp/` plus `Compiler::cpp()` and lifecycle reset.
Discussion only; no implementation or native MT proof. Rendering is substantially
more isolated than semantic preparation: `CPP_Syntax`, `CPP_Declarations` and
`CPP_Types` read syntax/prepared facts and build private strings/representation values.
Canonical type access here uses the read-only `Type_Preparation::canonical()` wrapper
around registry `type()`, not the registry's potentially allocating `canonical()`.
Bindings and source-type links are read-only. No interning or semantic lookup occurs.

Today `CPP_Generator::generate(prepared_file)` combines selection, retained-cache
mutation, rendering, assembly and handoff consumption. `render()` creates a private
context and fragment, then replaces `cpp_program.fragments[owner]`. Record rendering
sets `expand_records = false`, so it records dependencies instead of embedding other
record text. Temporary numbering is per rendering context; headers are local.

Timing below is relative to backend generation, after required semantic preparation
has completed. Class names are provisional; `Local?` refers to the proposed operation.

| Code path / data | Appends / inserts | Updates | Removes | Local? | Timing | Proposed code class | Proposed technical solution |
| --- | --- | --- | --- | --- | --- | --- | --- |
| `generate()` fragment selection | Missing cache entries; definition/body work lists | Marks outdated fragments dirty | Deleted fragments | No | Before render dispatch | `CPP_Fragment_Selection` | Exclusive cache selection; seal work with owner/version, preserve missing-cache and failed-render retries. Workers do not mutate the shared cache. |
| `render()` / syntax, declarations and type representation | Private fragment, headers and record dependencies | Private text, return type and temporary counter | None | Yes | During parallel rendering | `CPP_Fragment_Renderer` | Render selected owners using one private context per fragment; batch by source file. Read stable prepared facts and type/binding metadata. Return complete replacement fragments. |
| Current `render()` final cache replacement | New owner membership if absent | Replaces owner fragment | Superseded fragment reference | No | After render join; submission may happen during rendering | `CPP_Fragment_Publication` | Submit file-local results under destination lock, or collect task results; merge replacements under exclusive cache ownership after join. Preserve completed render results for retry if later work/assembly fails. |
| `assemble_record()` and output sections | Private record-state entries and include union | Private records/prototypes/functions/entry text | None | Yes | After fragment publication | `CPP_Output_Assembler` | One assembler reads the stable cache, orders record dependencies and assembles existing single-output bytes. Completion order must not determine output order. |
| Successful `generate()` / source handoff | None | Replaces `preparation_changes` with empty set | Consumed changes/retirement handoff | No | After successful assembly | `CPP_Output_Publication` | Acknowledge only changes incorporated into completed output. Preserve all pending handoffs on render/assembly failure. |
| `Compiler::cpp()` / `Model::$cpp_files` and reset | Completed output artifact | Replaces artifact root; unexpected failure sets rebuild flag | Previous completed artifacts; deleted cache entries via reset | No | Reset before rendering; publish after successful assembly | `CPP_Output_Publication` / existing lifecycle | Outer lifecycle owns reset and final artifact publication; workers never publish completed program output. |

Proposed flow: completed preparation -> exclusive fragment selection -> parallel
file batches of private fragment rendering -> join/cache merge -> deterministic
assembly -> output publication and handoff acknowledgement. Rendering needs neither
the concurrent append/index store nor concurrent cache traversal/mutation. Case 2
(future-stage submission) is sufficient for its results.

Once facts are stable, a function-body render reads its prepared signature directly;
it does not read the signature's C++ fragment. Record dependencies affect assembly,
not render readiness. Therefore the backend's current definition-then-body render
loops need not imply an MT rendering barrier between those fragment kinds. This is
distinct from the agreed semantic declaration-before-body boundary.

Current scope limitation: `Compiler::cpp()` explicitly requires exactly one prepared
source. Parallel fragments within that source are a bounded backend option. Parallel
per-file batches across sources require a separately agreed multi-source assembly
model; do not silently call `generate()` concurrently for several files. It emits
`main.cpp` with one entry body, owns a shared cache, and consumes each source handoff.
Entry-body ordering/composition, cross-file names and complete record/include closure
need decisions. No output partitioning, new entry semantics or naming redesign is
authorized by this review.

Before implementation, specify cache/result ownership on partial failure, version
validity between selection and publication, deterministic include/fragment order,
and whether preparation/rendering overlap is allowed. Initial recommendation is no
overlap with mutation/cleanup of facts read by renderers. Existing incremental C++
tests are useful behavioral evidence; native MT checks must additionally vary worker
completion order, render failures/retries and verify identical output/cache reuse.
