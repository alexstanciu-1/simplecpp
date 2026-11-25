# Runtime object cleanup
Doc Status: supporting

Status: implemented for constructed inline provider objects with a validated
destruction contract. The first proof uses ordinary calls, locals, temporaries,
branches, loops and scalar returns. [Copy construction](runtime_copy_construction.md)
now adds independently owned locals. Assignment, move operations, owned source
parameters/returns, smart pointers and exception unwinding remain unsupported.

The later [field-composition slice](lifecycle_contracts.md#first-compiler-implementation-bounded-composition)
adds compiler-owned source-record default construction, copying and destruction
through these same local/temporary cleanup obligations. The containing operation
cleans up its fields in reverse order; analysis does not also register independent
field owners. Broader operation roles remain documented future work.

## Simple C++ authority

Follow Simple C++ contracts, investigating only missing or unclear cases.
Its [normative generation rules](../../../../../simple_cpp_compiler/vendor/simple_cpp/generators/php/specs/rules.md)
map local variables to native C++ variables and source destructors to C++
destructors. Its [runtime contract](../../../../../simple_cpp_compiler/vendor/simple_cpp/runtime/specs/spec.md)
separates inline values from shared/unique/weak ownership, and its
[inline lifecycle test](../../../../../simple_cpp_compiler/vendor/simple_cpp/tests/runtime/lifecycle/level_01/runtime_lifecycle_002_value_copy_lifetime.cpp)
checks destruction on nested scope exit.

The unclear point was the lifetime of a temporary passed to a read-only call.
A focused strict-source probe through the toolchain in `tools/toolchain.json`
confirmed that a `string` parameter becomes `const string_t&`, and a return
expression containing `measure(text()) + measure(text())` stays one native C++
return expression. No intermediate statement ends those temporaries early.
The cleanup integration test compares the LLVM path with a native C++ oracle
using the same provider types and scoped expression shapes. This is a targeted
check of the selected native lifetime rules, not a full S2S equivalence claim.

The supported rules are:

- A local owns its directly constructed destination until its scope or callable exits.
- Destroy exited locals in reverse construction order.
- Borrowed access ends with the call; it does not destroy the receiver.
- Constructed expression temporaries live through the full expression and are
  destroyed in reverse construction order after its value has been obtained.
- Return expression temporaries are destroyed before local scope cleanup. The
  scalar return value has already been evaluated and remains unchanged by cleanup.
- Condition temporaries are destroyed before choosing the branch, including a
  loop condition that evaluates false. Loop-body locals are destroyed each iteration.
- A directly constructed local is not also a temporary requiring separate cleanup.

The bridge's existing `terminate` error policy remains the boundary. The user
explicitly accepted the following scope: **a failure crossing a runtime bridge is
fatal; recoverable exceptions and guaranteed cleanup during failure are deferred
for now.** Normal successful-path cleanup retains the guarantees described above.

## Deferred debt: failure cleanup and recoverable exceptions

Status: deferred by agreement, 2026-09-19. This is future runtime/language work,
not a blocker for the current foundations sequence or authorization to implement it.

When brought into scope, define and prove:

- Failure propagation across prepared runtime operations and generated callers,
  including the ABI/exception policy and consistency with S2S behavior.
- Cleanup of successfully constructed fields/elements after partial construction
  failure, without destroying unconstructed members or an incomplete whole object.
- Cleanup of live caller locals and temporaries along exceptional exits, and the
  effects of failed assignment on still-live objects.
- The selected guarantees for cleanup before fatal termination, separately from
  recoverable exceptions and handler behavior.

Native cleanup performed inside a Clang-generated operation does not guarantee
cleanup of its LLVM caller. Keep completed construction and operation failure
effects distinguishable in future designs; this debt does not require adding
unwinding machinery now. The [lifecycle investigation](lifecycle_contracts.md)
records the relevant native evidence and composition boundaries.

## Metadata and adapter

A type declares either `lifecycle.cleanup: "none"` or `lifecycle.destroy` naming
an operation for that exact type. The adapter verifies destruction's consumed
owned-object parameter, live-storage precondition, postcondition, exception policy
and physical `void(ptr)` ABI. It rejects missing/mismatched contracts and exposed
source destructor calls. No-cleanup types still require verified trivial destruction.

The normalized type lifetime contract retains one shared `runtime_lifecycle_operation` record.
Its exact provider/operation identity and link name come from metadata. Destructors
are implicit runtime operations; they need no invented source declaration or name.
Existing unexposed preparation types do not become source types automatically.

## Analysis and lowering

Lifetime analysis remains independent of execution instructions. Existing value
consumption records describe copying or borrowing; existing local lifetime rows
describe lexical exits. A separate flat `cleanup_obligation` dataset records only
objects needing destruction, in execution order within each reached block:

| Field | Meaning |
|---|---|
| `subject` | Local or expression temporary ID domain |
| `subject_id` | Existing checked local/value ID |
| `after_statement` | Full-expression or lexical exit boundary |
| `block_id` | The reached checked block containing that boundary |

The analysis worker keeps a private construction-order stack for managed
expression temporaries. It excludes direct local destinations and flushes remaining
temporaries at the full-expression end. Local cleanup follows the existing scope
and return analysis. Result validation checks obligation membership, completeness,
duplicates and boundary order against those established lifetime facts. At a shared
boundary it also requires temporaries before locals, reverse checked-call order
for temporaries and reverse initialization-statement order for locals. These ranks
are validation scratch data derived from existing contracts; no extra ordering
fields are retained and no second flow analysis is performed.

Lowering consumes the ordered obligations, resolves their existing storage slots,
and emits explicit `destroy` instructions. It verifies local/temporary provenance;
a temporary cleanup cannot target a local's destination. It does not rediscover
lifetimes from source syntax or borrow ends.

## Shared ABI path

Backend preparation produces a shared physical `abi_target` for ordinary calls and
implicit destruction. Source symbols and semantic signatures remain separate.
LLVM call formatting and module import/declaration assembly consume these same
ABI targets, indexed by exact link names. Cleanup is an ordinary ABI call at the
execution layer; it does not need a second provider-linking pipeline.

The backend rejects incompatible stack address spaces and verifies prepared ABI
shapes. Imports must reference the exact current backend target. Provider bitcode
is linked through the existing package path. No type names select compiler behavior.

## MT, incremental state and native memory shape

Workers read fixed checked bodies, type definitions and backend targets; mutable
stacks, maps and outputs stay private until joins. Cleanup preparation uses pure
operation/configuration inputs, with tool verification in the coordinator.
Execution remains serial and uses the same work units intended for future workers.
`LLVM_Backend::init()` selects `lifecycle_preparation_task` records carrying a shared
operation and fixed configuration, using the full-rebuild-or-stale policy.
`Callable_Preparer::prepare_lifecycle()` reads one task and returns a private
`lifecycle_preparation_result` with that task's provenance. It reads no previous
state and performs no tool operations. The coordinator verifies selected ABI
shapes; `Lifecycle_Join` accepts exactly one result per selected task in any order,
reuses current unselected targets and excludes removed operations. `Backend_Join`
then accepts the complete context. Task/result records are released after the
join; only shared ABI targets enter retained state. Actual threading is deferred.

Body edits invalidate their checked analysis and generated plans through existing
stage identities. Unchanged callers retain cleanup plans and native objects;
provider changes keep the existing whole-package invalidation boundary. The proof
uses separate sessions for helper-body reuse and managed-body replacement, each
with one full build and one body-edit increment. The managed edit adds a nested
local and another return-expression temporary under unchanged callable contracts.
Its analysis, lowering, emission and native object are replaced; unchanged callers,
unrelated managed functions and prior snapshots are retained. No recovery machinery
or new incremental edit category is added.

The native port should use small enum tags and IDs in typed linear cleanup/slot
containers, with shared type and ABI records. No definition or implementation is
copied into every cleanup row. Validation indexes and construction stacks are
worker/result-construction scratch data, not retained per-value objects. No native
memory or threading performance claim follows from the PHP implementation.

## Proof and next step

[`runtime_cleanup.php`](../../tests/integration/runtime_cleanup.php)
prepares two differently laid-out, noncopyable C++ types owning allocated resources.
Their construction/read/destruction trace and exit status match the native oracle.
The test includes nested temporaries, direct destination construction, discarded
results, block exits, void fallthrough, both return paths, condition temporaries,
repeated loop bodies and the two independent incremental scenarios above. Both
versions of the managed-body scenario match native C++ traces and exit status.
It checks fixed analysis workers and deterministic joins, missing-obligation
rejection and reordered obligations at one boundary (temporary/temporary,
local/local and temporary/local). It also rejects malformed destruction metadata
and unsupported copying while preserving accepted publication.

Cleanup preparation checks full and empty selection, partial replacement, reverse
worker completion, deterministic output and unchanged input snapshots. Its join
rejects missing, duplicate, unselected and stale task/results, incompatible ABI
shapes and reuse across changed provider/configuration facts. Removed operations
are omitted independently of selection.

The earlier no-cleanup and scalar ABI tests remain regression coverage.
[Literal construction and echo](runtime_string_literals.md) now consume this
cleanup infrastructure through ordinary metadata-selected calls.

The [copy-construction extension](runtime_copy_construction.md) generalizes the
implicit ABI preparation records and join to lifecycle operations. Destruction
remains a distinct role; copied locals enter the existing cleanup analysis as
independent owned objects. The shared preparation proof now lives in
[tests/support/lifecycle_support.php](../../tests/support/lifecycle_support.php)
and runs with both destruction-only and mixed copy/destruction packages.
