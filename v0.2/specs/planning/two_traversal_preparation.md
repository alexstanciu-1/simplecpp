# Thread-safety plan through lowering

Doc Status: planning
Date: 2026-10-30

This records the ownership and execution model agreed with the user through lowering.
Concrete shared lowering needs remain subject to individual review. This is a design contract
for upcoming work, not a claim that the current implementation has been audited or
proven thread-safe, and not authorization to implement a scheduler or refactor.

PHP remains a sequential bootstrap implementation. Do not restore Parallel runtimes,
transport serialization or fact export/import solely to accommodate PHP interpreter
isolation. See the [current execution guide](../../compiler/docs/execution.md).

## Common ownership rules

- A persistent native worker pool serves the incremental compiler. A phase join
  waits for its jobs; it does not destroy the pool.
- Each source context has at most one writing job at a time. Source bytes, tokens,
  AST, local symbol collection and private indexes outlive reuse of a pool worker.
  Retained compiler data belongs to the source context, not reusable thread scratch.
- Mutable data has one owner. Foreign workers read only data already settled and
  immutable for the current compilation generation.
- Worker-private indexes contain references to original declarations, not cloned
  AST nodes. Only the owning worker/job accesses these indexes. Owning an index
  does not protect its referenced objects; their lifetime and readiness still matter.
- The pool queue, job completion and coordinator publication require proper native
  synchronization. The claim of lock-free worker processing excludes that machinery;
  it is not a claim that the whole compiler is a formally lock-free algorithm.
- A barrier must establish visibility of completed writes. An ordinary unsynchronized
  completion flag is insufficient. No consumer proceeds on partially completed facts.
- Shared configuration read by workers is immutable before dispatch. No hidden
  shared mutation through caches, name interning, canonical-type registries or lookup.

## Phase 1: source-local frontend

Each job receives one source file and performs:

```text
read file -> tokenize -> parse while collecting symbols locally
```

Everything produced remains exclusively owned by that source context. The job needs
no mutable external compiler data and must not ask another worker for its data.
Source-local diagnostics accumulate with the job. Shared immutable configuration is
allowed; parsing must not directly mutate a shared symbol registry. It may notify
the coordinator of discovered declarations as described below.

Join all frontend jobs. On successful completion, the coordinator alone publishes
the global symbol index. Index entries identify existing source-owned declarations;
publication does not imply their semantic contracts are prepared. Missing/ambiguous
names are diagnostics, and duplicate declarations must not be silently overwritten.

The sequential PHP frontend now performs source-local file reading, tokenization
and parser-invoked collection. `Parser::parse()` and the parse/collect frontend APIs return one completed
`ast_file_node`, containing both syntax and local collection. Collection no longer makes
a separate pass over the finished statement list. Explicit no-argument void functions
now exercise early discovery, pending coordinator publication, settled signatures
and independent body preparation. The native pool is not implemented.

### Early declaration notifications

The agreed refinement permits a worker to notify the coordinator as soon as it
discovers a global declaration, such as a class. This means **identity discovered**,
not **declaration complete** or **contract prepared**.

The notification carries the index key, declaration identity, owning source and
compilation generation, plus a stable object reference/handle. These identifying
fields must already be initialized and remain stable. The referenced object must
not move or be destroyed while pending notifications or index entries refer to it.

The coordinator is the sole writer of a pending global index. In native execution
it can consume notifications on its separate thread while source jobs continue.
It uses the notification's immutable identifying data without dereferencing AST
fields the owner is still constructing. Native message delivery requires proper
synchronization; notification delivery does not publish subsequent AST writes.

PHP uses the same logical notification boundary as an ordinary direct call into
the coordinator on its single thread. No background thread, queue, serialization
or fact export/import is needed. Notification handling only records the candidate;
it must not re-enter parsing or start semantic preparation. The source context
retains ownership of its AST and local symbol collection.

Index construction may therefore overlap frontend work, but availability does not:

1. Record discovered identities in the pending index, retaining duplicate candidates.
2. Finish/join all frontend jobs and account for every source's success or failure.
3. Drain all notifications for that phase before completing publication. Queue
   emptiness alone is insufficient while producers may still send notifications.
4. On successful frontend completion, expose the completed index to relationship
   resolution. Failed or stale-generation candidates must never become usable.

Incremental replacement must respect the existing reader/lifetime boundary. A late
notification from an earlier generation cannot enter the current index. Arrival
order must not determine name-resolution results or diagnostic ordering.

Allow notification batching without requiring one synchronized queue operation per
symbol. Batch size and native queue mechanics are implementation choices for later;
the sequential PHP path should remain simple. The void-function slice implements
direct PHP discovery into an invocation-local pending index, checked against the
completed source inventory before publication. Native asynchronous notification and
overlapping generations remain unimplemented.

## Phase 2: declaration preparation, first semantic traversal

### A. Resolve direct relationships

Each owner resolves inheritance, trait-use and implemented/extended-interface names
through the published immutable symbol index. It fills its own preallocated reference
slots, initially null, linking to existing declaration identities.

During this step workers do not consume relationship slots or member indexes that
another worker is still populating. Join relationship resolution before dependent
processing begins. Keep no relationship, unresolved relationship and failed resolution
distinguishable; a null reference alone is not a readiness/failure protocol.

Writing once over null is not itself a synchronization guarantee. Exclusive ownership
of writes and the publication/barrier ordering provide safety.

### B. Build private indexes and settle declaration contracts

Each owner builds its effective property/method/member indexes using local declarations
and references to inherited declarations. It may traverse the settled relationship
graph and immutable declaration inventories; it must not access another worker's
private member index.

Where preparation requires another declaration's semantic facts, those prerequisites
must already be settled and published before the consumer runs. Schedule/order work
by actual declaration prerequisites, not by whole-file dependencies. Independently
owned destinations do not make reads of unfinished foreign facts safe.

For example, file A can contain A1 needing B1 and independent A2, while file B contains
B2 needing A2 and independent B1. File-level scheduling appears cyclic; declaration
work can start with A2 and B1. Distinguish identity, signature, ancestry and layout
requirements instead of requiring full preparation whenever a type is named.

Settle the contracts required by executable bodies: parameter/return types, property
types, relevant modifiers and access rules, inheritance/interface obligations,
override validation and trait conflicts. Layout and constant values must be ready
where required. Combining member inventories is subject to these semantic rules,
not ordinary overwrite-by-name map insertion. Actual dependency cycles must diagnose,
not result in indefinite waits or hidden recursive preparation.

Join successful declaration preparation. Shared declaration contracts and referenced
data are now immutable for body consumers. Private indexes stay with their owners.

## Phase 3: executable-body preparation, second semantic traversal

- Each body has exactly one writing owner. The owner may populate local bindings,
  expression facts, conversions and other body-owned semantic data.
- Foreign workers do not inspect or prepare that body. Calls, including recursion,
  use the callee's settled signature/contract, not its executable statements.
- Top-level executable code is conceptually a function body and follows the same
  ownership rules. This describes the target model, not a new AST implementation.
- Shared declaration contracts and other published data remain unchanged. Workers
  use their own indexes and read only settled external facts.
- Lookup cannot silently start declaration preparation. Body preparation cannot
  mutate shared registries or publish new shared declarations immediately.
- Diagnostics and newly discovered work accumulate locally. A feature requiring new
  specialization/shared declarations must return a demand to the coordinator at a
  barrier and receive a separately reviewed protocol; it is not implicitly allowed
  to break the frozen-contract boundary.
- Features deriving public contracts from bodies, such as inferred return types,
  require separate handling before independent body preparation. They are not covered
  by the assumption that all contracts are already settled.

After all body preparation succeeds, join and freeze the resulting facts before
lowering consumes them. A failed prerequisite or phase must prevent dependent work
from consuming partial results; error handling must not leave jobs waiting forever.

## Attached facts and global indexing

The proposed model attaches typed prepared facts to the AST nodes they describe,
without changing authored syntax. Processing remains in stage workers; AST storage
must not run preparation algorithms. The global index locates declaration identities
linked to source-owned nodes; ordinary references connect consumers to settled facts.
There is no PHP-only export/import requirement.

A successful preparation result must still distinguish a complete file/body from
partially populated nodes after failure. Function contracts and body facts are now
attached to their respective owners. Existing integer expression/entry facts still
use separate records referencing syntax; extending attachment to those remains pending.

## Incremental lifetime boundary

The pool persists across compilations, but published data cannot be cleared, replaced
or freed while readers still use it. Initially, finish the current generation's jobs
and readers before invalidating affected facts or beginning the next update. Preserve
referenced data until those readers finish. Rebuild/invalidate private indexes when
their referenced declarations change. Overlapping generations and their reclamation
mechanisms remain outside this agreed plan.

## Phase 4: lowering

Lowering begins only after successful body preparation joins and its facts are frozen.
Each job reads its source's AST and prepared facts. Foreign reads use settled,
immutable declaration contracts, never foreign executable bodies. Lowering does
not mutate semantic facts or trigger preparation.

Each job exclusively owns its generated representation, fragments and dependency
lists. After lowering jobs join successfully, the coordinator assembles and publishes
their results. No worker directly modifies a shared output registry.

If lowering discovers a need for a shared artifact, use the same notification concept
as early declaration discovery: an ordinary coordinator call in sequential PHP and
synchronized asynchronous delivery in native execution. The coordinator owns pending
shared requests/results; notification alone does not make an unfinished artifact
available to consumers.

Review each concrete kind of shared item when it appears. Generated helpers,
specializations and naming entries are examples to investigate, not requirements
to implement now. Decide identity, ownership, duplicate handling, readiness and
whether another job actually needs to consume the result for that specific case.
If consumption introduces a dependency, explicitly design its publication boundary
before admitting it; do not introduce hidden waits, shared mutation or preparation
re-entry inside lowering.

The agreed direction is private lowering plus coordinator-owned shared publication.
Detailed shared-artifact protocols and output-assembly behavior will be resolved
one concrete case at a time. No generic shared-lowering framework is authorized
merely by these notes.

## Donor findings and performance constraints

The user reports that preparation consumed roughly 90%+ of compilation in their
`my-try` workload. This review did not reproduce that measurement or establish a
profile-based cause. Retain its semantic concepts, not its preparation engine.

Useful evidence:

- [Compiler coordinator](../../../compiler/my-try/compiler/compile.php): collection
  joins before preparation; prepared-program success precedes C++ generation.
- [Model](../../../compiler/my-try/docs/architecture/MODEL.md): distinct declaration
  and body identities, source ownership and retained facts.
- [Incremental preparation tests](../../../compiler/my-try/tests/incremental_preparation.php):
  missing-name recovery, new ambiguity, invalid by-value cycles and legal recursion.

Cost risks visible in the donor's
[Preparation_Worker](../../../compiler/my-try/04_analyze/prepare/worker.php):

- Serial contract processing before parallel body batches.
- Recursive dependency visits even when a requested contract is unchanged.
- Linear searches through private lookup observations.
- Scans of retained lookup observations and reconstruction of candidate sets.
- Bodies attempted in parallel then retried serially on unsettled demands;
  [materialization rounds](../../../compiler/my-try/compiler/preparation.php) can
  restart preparation coordination.

These are inspection findings, not measured attribution of the reported cost.
Do not copy subscription, retirement, materialization or shared-global machinery
without a concrete need. Do not trade repeated computation for one remote lookup
per AST node. Prefer local settled facts and batched publication where appropriate.

## Verification required when implemented

- Check every mutable field's owner, every cross-owner read's readiness boundary,
  and whether apparently read-only queries mutate shared state.
- Verify source data survives pool-worker reuse and is not exposed before join.
- Verify early notifications only populate a pending index, all phase notifications
  are accounted for before publication, and failed/stale candidates remain unusable.
- Verify direct PHP notification handling and eventual native queued delivery share
  the same discovery/readiness semantics, independent of arrival order.
- Verify relationship and declaration dependencies cannot consume partial facts;
  private indexes reference valid original declarations without foreign index access.
- Verify body preparation reads frozen contracts and does not inspect foreign bodies.
- Verify lowering preserves semantic facts, owns its output privately and routes
  shared needs through the coordinator without exposing unfinished results.
- Verify failure blocks dependent consumers and incremental replacement waits for readers.
- Compare deterministic facts, diagnostics and output across execution orders when
  native concurrency exists; use native concurrency/lifetime checks at that point.

Current PHP checks establish integer-entry and void-function semantics, early
discovery/readiness boundaries, signature-only call resolution, retained identities,
deterministic C++ output and failure-safe publication. They do not prove the proposed native model.
Classes, inheritance, richer contracts and their dependency proofs remain future
work. Historical PHP MT results are linked from the execution guide.
