# Compiler-checked multithreading in v0.2

Doc Status: planning

The first workload is a joined batch of workers that mutate existing collection slots in place. Offsets and lengths come from the running program. A worker may receive several segments and return an owned result. The design should preserve slot identity even for large inline records. Source spelling remains open.

## Agreed compiler boundaries

| Responsibility | Input and output |
|---|---|
| MT provisioning | Use normal semantic preparation to resolve the worker, bind arguments and results, and identify each input's access mode and lifetime through join. For a segment, resolve its backing expression, element type, offset and length expressions. These facts describe the requested access. |
| MT safety checking | Consume prepared facts to check what the worker and its reachable callees can access, what aliases the caller can use while grants are live, and whether a view or slot reference can escape. Report a source diagnostic where safety cannot be established. |
| Runtime admission and join | Evaluate the requested ranges, identify the actual backing storage, check bounds and conflicts, and acquire the whole batch before starting any worker. Keep storage alive and structurally stable until all started workers finish, including after failure. |

Keep three responsibilities distinct in the safety checker's source code. The **traversal** visits prepared bodies and identifies accesses, calls, escapes and their source sites. The **rules** interpret those operations against the available permissions and produce effects or diagnostics. They should be testable without constructing an AST. The **scheduler** selects bodies, combines callee effects at call sites and publishes diagnostics. For example, a helper that writes through its first parameter is admissible when that parameter binds to the worker's granted segment.

Traversal can feed rules directly. A second retained copy of every body operation is unnecessary. Each body check should read stable prepared facts and write only its own result so independent checks can run concurrently later. Recursive call groups require a common settled result. Unknown call targets or native effects require a safety contract or a diagnostic.

Scheduling remains open. The proposed baseline is a serial check after preparation and before C++ emission, with separately measured traversal and checking costs. Collecting local effects during the existing preparation walk remains an option. Either schedule uses the same safety rules.

## Run 1 for discussion — joined vector segments

Use fresh compilation only. Proposed fixture: two workers each receive a range of inline records and a matching range of integer adjustments from a second vector. Each worker updates its records in place and returns a subtotal. The parent reads both results and the original records after join.

- **Admission.** Use half-open ranges. Validate offset and length against the vector size without arithmetic overflow. Adjacent ranges are valid. Detect overlapping exclusive ranges even when submitted through different names for the same backing. An invalid request anywhere in the batch starts zero workers.
- **Isolation.** Start with inline fields that carry independent values. A slot grant alone gives no exclusive access to a shared mutable pointee. Keep the parent containers unavailable to the caller through join. Reject any alias path the compiler and runtime cannot account for. A worker can update an element through its bounded view. Resizing the backing or retaining the view beyond join must fail.
- **Evidence.** Use a native test that observes overlapping worker lifetimes, matches worker slot addresses to the original slots, checks both subtotals and confirms untouched slots retain their values. Include a rejected overlapping batch with a worker-entry counter of zero, a helper that accesses mutable global state, and an attempted escaping view. On worker failure, verify all workers have stopped and the parent regains access. Completed in-place writes can remain visible.

## Later backlog

- Hash segments need a defined offset over live entries, stable iteration during the grant, and a measured cost for locating the range. Extend to whole vectors and hashes, strings, moved unique values, shared handles and ordinary by-value inputs under explicit transfer rules. A shared handle needs a separate argument for safe access to its pointee.
- Incremental MT checking is deferred beyond run 1. Fresh checking is the reference result. Later, inventory dependencies on callee effects, changing dispatch targets, nested field ownership and native contracts. Measure invalidation fan-out and lookup/update costs before selecting an index. A B-tree is a candidate to evaluate if the access pattern justifies it.
- Use the v0.1 task runtime and my-try transfer checker as design references. Review their assumptions before adopting any mechanism. [Task contract](../../../specs/builtins/tasks/first_pass.md) · [Transfer analysis](../../../compiler/my-try/04_analyze/prepare/semantics/transfer.php)

## Run 1 implementation and verification

Fresh compilation binds `mt_join(work(segment(rows, offset, length), segment(adjustments, offset, length)), ...)`. One or more direct workers each take two required by-value `segment<T>` parameters and return an integer. The batch returns a vector of integers in submission order. Offsets and lengths are integer locals or literals. A segment supports checked indexing, field writes to inline slots, and cardinality queries. Eligible elements are numeric or boolean scalars and plain inline records composed of eligible fields. Hashes, heap ownership and background work remain in the later backlog.

The caller waits inside the launch. A worker can access its granted slots through checked direct helpers. The checker rejects mutable global access, unaudited operations and helper contracts that might let a view escape. Each reachable body is inspected once per fresh check. The compiler supplies the backing allocation, native element stride, vector size and bounds. The runtime validates all four ranges for a two-worker batch before starting either thread, including comparisons across different input positions. In-place writes survive worker failure. All started workers join before the caller receives a result or exception.

The Clang regression uses a PHP launch and helper/worker pairs written in PHP, C++, JS and Python. It checks both vectors after mutation, ordered results, untouched neighbors, transitive diagnostics, overlapping worker lifetimes and addresses of the original slots. Conflicting aliases and overflowing ranges yield zero entries. The failure case checks parent access after join. Run with `ctest -R '^compiler_mt_segments_native$'` in the configured build. [Compiler fixture](../../compiler/tests/mt_segments_native.py) · [Native probe](../../compiler/tests/mt_segments_probe.cpp)

## Run 2 — variable worker arity

A direct joined worker accepts one or more required, by-value vector segments and returns an integer. Workers in a batch may have different signatures. The compiler checks each argument against its corresponding parameter and emits a local grant array and a typed call for each worker. One runtime job representation handles every arity. Admission checks all grants across the whole batch before dispatch. The native regression covers mixed one- and three-input workers, ordered returns, in-place updates, and a conflict on the last argument that starts zero workers.

## Pool configuration

The native runtime now keeps one process-wide pool for joined segment batches. A single-job join runs on the caller. Larger joins admit the entire batch first, then use the caller and available pool workers to execute contiguous groups of jobs. Worker threads start on demand and are reused until process shutdown. The first join that needs more capacity pays its startup cost. The background worker limit is one less than the reported hardware concurrency, with capacity for at least one background worker. Independent callers can use the pool concurrently and process their own jobs when all workers are busy.

Set `SCPP_MT_WAIT=sleep` (default) or `SCPP_MT_WAIT=active` before starting the program. The first admitted multi-job join reads the setting. Sleeping workers block between batches. Active workers continue polling while idle, including between parallel phases. This mode is intended for processes with dedicated CPU capacity. Each job has its own result and exception slot. The caller waits for its batch and releases the workers before propagating the first failure in submission order.

[Short performance report and recommended first pool](multithreading_performance.md)

## Checkpoint — 2026-11-24

All five planned capabilities are implemented on `codex/multithreading` through
`adbb0dd1e`: scalar inputs by value, read-only vector segments, balanced ranges,
runtime-sized batches with group and per-job worker caps, and hash segments in
live insertion order. Hash tests cover deletion and reinsertion, in-place value
updates, read-only keys, and a late overlap that rejects the batch with zero
worker entries. Joined jobs currently return integers in submission order.
[Current language contract](../../../specs/compiler_entry_v0_2.md#hash-segments) · [One-hour verification and evidence](benchmarks/mt_segments/2026-11-24-hour/README.md)

### Proposed priorities when work resumes

| Priority | Capability | Estimated complexity and first design question |
|---|---|---|
| P1 | Richer joined results | Medium for a scalar or isolated inline record. Medium–high for owned strings or vectors, which need cleanup if a sibling throws. How should a group expose results when jobs return different types? |
| P2 | Richer inputs | Medium for a string borrowed read-only through join or an isolated record passed by value. High for moving a unique value. Who owns it if a later grant fails admission? |
| P3 | Serialized publication during execution | High. Identify a pass that needs results visible before join, then define destination access, publication order and failure behavior. |
| P4 | Background batch handles | High. Grants must survive the caller resuming. Progress is medium once handles exist. Cancellation and timeouts with in-place writes are high because partial updates remain observable. |
| P5 | Shared mutable objects | Very high. A safety contract must cover aliases and helper calls that reach the same object under synchronization. |

Suggested first slice for discussion: let one repeated job return an isolated
inline record per partition. Use a scan returning `scanned`, `matched` and
`first_match`, and verify that the caller receives the records in partition
order even when workers finish out of order. A later variable-output example
is a worker-owned vector of diagnostics per partition, merged after join.

Track incremental MT safety separately (high complexity). The first acceptance
case is a helper edit that changes an otherwise untouched worker's safety:
incremental checking must give the same acceptance or diagnostic as a fresh compile.
