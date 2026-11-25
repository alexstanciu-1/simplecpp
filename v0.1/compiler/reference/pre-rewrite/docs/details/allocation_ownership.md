# Explicit allocation ownership
Doc Status: supporting

The first dynamic-storage slice proves local allocation owners. Runtime preparation
owns native allocation and matching release; compiler analysis owns the obligation
to release or transfer before every normal exit. It does not implement an allocator
or expose native addresses to source code.

This is a bounded prototype contract, not a claim that Simple C++ already defines
this source API. Typed element storage is now [proved](typed_storage_plan.md); [owning fields](owning_storage_fields.md) are also implemented; the
[growing list](source_list_plan.md) now proves their composition. See [the agreed direction](lifecycle_contracts.md#dynamic-storage-direction-and-implementation-status).

## Contracts and roles

| Owner | Responsibility |
| --- | --- |
| `src-runtime-preparation/resources.php` | Validate resource permissions and complete operation effects in local definitions. |
| `src-runtime-preparation/include/scpp_provider/allocation.hpp` | Native descriptor, checked aligned allocation, matching release and transfer. Clang measures layout and exports implementation. |
| `load_runtime/handlers/resources.php` | Validate imported metadata and normalize it into compiler contracts. |
| `type_model/data/resources.php` | Separate allocation obligations from object construction, copying and destruction. Effects refer to semantic parameter positions. |
| `check_bodies` | Check nominal argument types and mutable local access; retain the exact callable dependency supplying the effect. |
| `analyze_lifetimes/allocation_flow.php` | Solve resource states over checked control flow; validate transitions, active borrows and normal scope/function exits. |
| Existing lowering, ABI and emission | Emit ordinary prepared calls; no allocation-specific opcode or name dispatch. |

Every owner is an opaque inline, noncopyable runtime value with `resource:
"allocation"`. Zero-argument construction establishes an empty obligation.
Object cleanup and allocation release are independent: even a trivially destructible
descriptor must discharge its allocation. The original local-only slice rejected
resource fields; the [owning-field extension](owning_storage_fields.md) now supports
static field obligations and bounded source-call summaries through the same analysis.

## Effects

| Metadata effect | Preconditions | Successful result |
| --- | --- | --- |
| `acquire` | Empty owner; mutable call borrow | Owner holds one allocation |
| `inspect` | Owned allocation; const call borrow | Ownership unchanged |
| `release` | Existing empty or owned local; mutable call borrow | Empty owner; release on empty is allowed |
| `transfer` | Owned source; distinct empty destination of the same nominal type; mutable call borrows | Destination owns the allocation; source is empty |

`owner` and `destination` are zero-based semantic argument positions. Every resource
argument must be covered; scalar arguments can appear anywhere. Type names,
operation names and allocator implementation do not select compiler behavior.
Effects describe the provider's successful behavior; a native implementation must
honor its declaration, just as it must honor call-scoped non-retention.

For the default configured provider:

```text
$a allocation;
$b allocation;
allocation_acquire($a, 37, 64);
allocation_transfer($a, $b);
$size int = allocation_size($b);
allocation_release($b);
return $size;
```

The native provider accepts positive byte counts and positive power-of-two
alignments. It checks representability in native `size_t`, rounds small alignments
up to native fundamental alignment, and pairs aligned `operator new` with aligned
`operator delete`. Allocation failure or invalid dynamic facts cross the existing
fatal bridge boundary. Cleanup during failure remains deferred.

## Analysis, MT and updates

The existing selected lifetime body task owns this analysis. A private worklist
merges sparse local states at CFG entries until stable, then validates them.
Conflicting empty/owned states at a join are rejected in this slice, even if a
later release could handle either state. Loops converge over the same finite
state domain; analysis does not enumerate paths. Ordinary bodies retain no
allocation-state result.

Checked evaluation order also records pending argument borrows. A nested call
cannot release or transfer an allocation pinned by an outer element borrow. A
stable descriptor/object borrow does not itself pin that backing allocation.
A completed inspection ends its own borrow normally. Bounded source summaries
carry alias exclusions; general pointer-alias inference remains outside this slice.

`Analyzed_Body` retains named empty/owned states for STAN and debug exports, tied
to the exact checked body. Existing selected tasks and joins accept private
results, reject stale/missing/duplicate batches and reuse unchanged bodies. A
body edit replaces its ownership analysis; provider contract changes follow the
existing catalog invalidation path. No separate coordinator analysis is added.

## Proof and remaining work

[Allocation integration proof](../../tests/integration/allocation_ownership.php)
prepares the production owner and a differently named `malloc/free` owner with
reordered parameters. It proves real writable aligned storage, transfer, normal
release, balanced branches/loops, rejection of leaks/copies/invalid transfers,
nested borrow invalidation, malformed preparation/import metadata, native failure,
O1/ThinLTO, private task joins and one body-only incremental replacement with old
snapshot purity.

The [typed-storage slice](typed_storage_plan.md) binds an element type and checked
constructed-prefix operations, preserving compiler ownership of source element
lifecycle. Static [owning fields](owning_storage_fields.md), bounded source-call
summaries and the [growing list's copy/assignment](source_list_plan.md) are implemented.
Later slices also prove [resource-owner results](owned_source_results_plan.md) and
[managed storage/list elements](source_list_plan.md#managed-growing-list-proof).
General moves, smart pointers, dynamically indexed allocation owners and exceptional
cleanup remain deferred; none is inferred from raw pointers. See the [consolidated flow/call model](owning_storage_fields.md#flow-and-call-application)
for solving, validation and alias-application responsibilities.
