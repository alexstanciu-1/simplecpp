# Small source-defined list
Doc Status: supporting

Status: source-function record borrowing and the [fixed-array source-list proof](fixed_array_list_plan.md)
are implemented. [Local allocation ownership](allocation_ownership.md) is also
implemented, together with [typed element storage](typed_storage_plan.md) and
[static owning fields](owning_storage_fields.md). The growing source-list proof is
implemented, following the approved [binary-operation migration](binary_operations.md).
For that growing case, the user agreed to an initially noncopyable list, with
ownership transfer for buffer replacement. Copy construction and assignment are now proved for the initial element subset.

## Agreed generic-contract migration

The revised [default generic contract](generic_type_contract.md) makes bare `<T>`
require copying, assignment and valid automatic cleanup. It fits this list's
copy-based algorithm while excluding unique owners and other noncopyable elements.
Default-contract enforcement is implemented. The user approved preserving these
proofs with concrete element records while deferring the growing list’s generic
migration: storage does not yet support dynamically indexed compiler-tracked
allocation-owner obligations, which the default baseline does not exclude. The
algorithm remains source-written; test-only fixture expansion generates ordinary
concrete declarations. [Managed slot lifecycle](managed_element_storage_plan.md) and
[owned source results](owned_source_results_plan.md) are now implemented in their
bounded subsets. The concrete managed growing-list proof is now implemented; see
[its evidence and remaining boundary](#managed-growing-list-proof). The fixed-array proof's zero-initialization
requirement is additional to the default baseline and is now
[parked review debt](generic_type_contract.md#deferred-review-debt), not a prerequisite
for the growing-list/default-generic design.

## Intended proof

Keep list behavior in source: append, length and checked element reads. A growing
list may allocate replacement storage on every append, copy existing elements,
append the new value, release old storage and update its length. Runtime/provider
code may supply general storage primitives; it must not implement the list's append
algorithm in place of the source proof.

Start with element contracts that permit value copying and require no element
cleanup. The allocation itself still needs an ownership and release contract.
Select eligible types through capabilities, not a list of fixture type names.
Prove at least two concrete element types through the same compiler paths. A
shared generic definition remains a later capability/storage integration proof.

## Existing foundation and concrete gaps

| Concern | Existing owner and gap |
|---|---|
| Specialization | [Instance preparation](explicit_instantiation.md) already handles ordered type/value arguments, concrete identities and demanded source bodies. No new instantiation model is needed for a list. |
| Source calls | [Parameter_Contracts](../../src/04_analyze/resolve_types/utilities/parameter_contracts.php) accepts explicit plain-record references; owned record/object source parameters and results remain unsupported. |
| Parameter use | [Callable contracts](../../src/04_analyze/type_model/data/semantic_calls.php) describe value, const/mutable record borrowing and byte spans. Canonical signatures now carry the passing modes for every source/provider consumer. |
| Element storage | Fixed-array fields, checked indexing and ordered projected locations are implemented. Local allocation ownership is implemented; typed element/prefix access and static owning fields are implemented. |
| Record lifetime | [Record_Definitions](../../src/04_analyze/resolve_types/record_definitions.php) composes field construction/copy/cleanup and custom source lifecycle bodies. Static allocation resource fields now use composed obligations and source ownership summaries. Copying its address must not produce two independent owners. |
| Source member behavior | Public source methods use owned declaration identities and demanded concrete receivers through ordinary borrowed signatures. |
| Counters and checks | The list needs consistent counter/index types, comparisons, bounds and size-overflow checks. Existing struct-field eligibility and integer-conversion rules remain authoritative; do not silently enable `int` fields or implicit narrowing to make the example compile. |

## Recommended sequence

### 1. Source-function record borrowing — complete

Generalize semantic parameter passing so ordinary source functions, instantiated
source functions and imported functions expose the same passing contract.
Preserve provider-specific ABI facts at their existing boundary. Extend source
parameter syntax/bindings with the confirmed Simple C++ forms `Type &$name` and
`const Type &$name`; existing value parameters keep `$name Type`.

Support call-scoped const and mutable record access. A borrowed parameter aliases
the caller's initialized storage, preserves const restrictions, does not take
ownership and does not destroy the caller's value. Keep aliasing rules consistent
with Simple C++; do not introduce exclusive-borrow semantics by assumption.

Affected owners: parsing/bindings, signature preparation, checked locations and
calls, lifetime analysis, parameter lowering and LLVM call/function emission.
The broad change is making source parameter passing explicit instead of deriving
it solely from an external ABI contract.

Proof: an ordinary record and a template record passed to source functions; const
reads, visible mutation through mutable access, unchanged value-copy behavior,
rejected const writes/unsupported escapes, fixed workers and joins, native output
and one ordinary body-edit increment. Run the existing suite because all calls
share these contracts.

This completed first implementation unlocks reusable list
operations without simultaneously implementing dynamic storage or member methods.

### 2. Fixed-array fields and source methods — complete

Prove the source-defined fixed-capacity list before dynamic storage.
[Model assessment, required migration and validation](fixed_array_list_plan.md).
The fixed-array object stores elements inline; append does not allocate.
Ordinary value-copy/no-cleanup semantics remain available when its fields permit them.

### 3. Dynamic element storage and its ownership — complete for the bounded subset

The implemented storage contract covers allocation, count/capacity, indexed
read/write, initialization, replacement and release. Target layout owns element
size/alignment; the source list does not guess them. Size overflow, bounds failure
and allocation failure must have explicit behavior.

Join any provider-backed storage primitives through the runtime adapter and
preparation tool. Represent allocation ownership and derive containing-record
cleanup/copy restrictions in the shared type/lifetime model. The agreed first
list is noncopyable. Buffer replacement explicitly transfers the new allocation
to its owner and releases the old allocation; it must leave exactly one owner
and prevent a second release by the previous owner. Other ownership transfers
remain unsupported until their contracts are implemented. This is separate from
per-element managed lifecycle, now implemented for runtime values and source records.
Dynamically indexed compiler-tracked allocation owners, including nested source-owned
lists, remain deferred.

General integer operations/conversions needed for counters and bounds must also
use their normal owners. No allocation IDs hidden in integer fields, global list
tables, sample-specific capacities, intentional leaks or prebuilt vector wrappers.

### 4. Growing source-list composition — complete

Growing list operations use the source methods established by the fixed-array
proof. Empty state, multiple appends, order, length, checked reads, release,
two element types and one body-edit increment are proved. Methods share the common callable
and borrowing path.

Provider `vector<T>` consumption was a later milestone and is now
[implemented through the shared family boundary](provider_family_compiler_integration.md).
It shares concrete type/callable contracts without fabricating source ASTs or
assuming its native layout matches this test list.

## Follow-up debt: general list copying

This item concerns the dynamically allocated owning list. It does not prohibit
ordinary independent copying of the first inline fixed-array test structure.

- [x] Custom copy construction through the shared source lifecycle model, with
  independent storage, two element types, const source borrowing and composed
  cleanup. The prototype spelling is documented under
  [custom copy construction](lifecycle_contracts.md#custom-copy-construction).
- [x] Copy assignment: allocate/copy replacement first, then release the destination
  and transfer replacement storage. The same source algorithm handles self-assignment.
  This closes the initial list-copying debt for value-copy/no-cleanup elements;
  the [managed-element extension](#managed-growing-list-proof) now also proves these operations for managed elements.
- The shared type/lifetime model must authorize copying through explicit
  capabilities and implementations. No list-name checks or shallow copying of
  an owning buffer. Do not require managed elements merely to prove copying of
  the initial value-copy/no-cleanup element subset.
- Completion evidence: independently owned backing storage, preserved element
  values/order, mutation isolation, assignment releasing the previous destination,
  defined self-assignment behavior, and exactly-once release on scope/early-return
  exits. Include two element types, fixed workers/joins, retained-snapshot purity
  and one body-edit increment. State the failure behavior of allocation/copying.

## Implemented growing proof

The source loop uses the common binary-operation path: existing addition and
same-type integer `<`, with a configured boolean result. See
[binary syntax, selection and lowering](binary_operations.md).

### Source algorithm

Keep the proof deliberately simple:

- Construct an owned allocation with one unused slot and zero live elements.
  This represents an empty list while keeping the allocation state owned; it
  uses the existing summaries without requiring count-dependent state inference.
- On append, read the live count, allocate `count + 1` replacement slots, copy
  each old element in forward order using an index and `<`, then append the new
  value. Pop old elements, release old storage, and transfer the replacement.
- Read length from the descriptor's existing live count; do not duplicate it
  in another source field. Indexed reads retain the native bounds check.
- Destruction pops the live elements and releases the allocation. No source
  subtraction, managed-element operations or general move/copy support is needed.

The current signed counter addition wraps; at its maximum, `count + 1` becomes
negative and native allocation rejects it before allocation. Existing native
extent/byte-size checks remain authoritative. Failure remains fatal.

The current proof uses two eligible scalar element types through concrete source records.
Plain records and managed runtime/source values are now eligible storage elements,
subject to the [indexed-owner restriction](managed_element_storage_plan.md). This
list fixture still exercises concrete scalar elements. Owned source results and
provider-family consumption have separate proofs; owned source parameters remain deferred.

## Evidence and remaining boundaries

[Growing-list integration test](../../tests/integration/growing_list.php)
runs concrete source lists for `int32` and `uint8`, using a metadata-renamed
storage family and operation names. It proves empty state, append order/count,
scalar self-append, indexed mutation, destruction, early return and discarded
temporaries. A native observer requires thirteen allocations and thirteen
matching releases, with no outstanding allocation. Empty and one-past-count reads
fail through the native bounds check.

Both normal compilation and O1/ThinLTO execute the list. Reordered ownership tasks
join to the same summaries. One method-body edit changes execution while retaining
instance identities, layouts, native storage preparation, unchanged summary meanings
and the old snapshot. Template application bindings retain declaration snapshots;
editing the declaration file refreshes those bindings and dependent checked bodies.
This proof does not promise their object-identity reuse.

The initial noncopyable fixture still rejects copying. A second source definition
adds a source `__copy_construct` body: allocate fresh storage and copy the live
prefix. Two concrete scalar-element records and an enclosing automatic copy preserve values
and mutation isolation, with nine matched allocations/releases. Ordinary const
record parameters inspect the source; early return releases each object once.
One copier-body edit changes output while retaining type/layout/runtime identities
and the old snapshot. O1/ThinLTO and ownership-join rejection checks also pass.

A third definition adds source `__copy_assign`: allocate replacement storage,
copy the source prefix, clear/release the live destination, then transfer the
replacement. Two concrete scalar-element records, direct and forwarded self-assignment,
automatic enclosing assignment and projected destinations execute through the same
contracts. The native observer requires seventeen matched allocations/releases.
An assignment-body increment retains type/layout/runtime identities and the prior
snapshot. Normal linking and O1/ThinLTO both execute the proof.

Stable owning-object arguments survive backing-storage replacement; element borrows
still prevent invalidation. Source access-order constraints reject unsafe aliased
calls, including through forwarding. A separate body edit moves a read after release:
although its final ownership states stay equal, the new alias constraint invalidates
and rejects the unchanged caller. Reordered ownership work uses the same joins.

The initial noncopyable fixture and wrong-element insertion still fail explicitly.
Assignment from a constructed managed temporary remains unsupported; this slice
assigns from existing source objects. Later slices now cover
[managed elements](#managed-growing-list-proof), [owned source results](owned_source_results_plan.md),
[runtime assignment metadata](lifecycle_contracts.md#copy-assignment) and
[native provider specialization](provider_family_compiler_integration.md).
Owned source parameters remain deferred. The proof adds no list recognition or
native append algorithm to the compiler.

### Custom-copy verification checkpoint

All 91 fixtures are verified across the full suite run (10 jobs, 265.2 seconds)
and focused reruns after correcting two test expectations/inventory entries.
The final growing-list proof includes const-source preservation, alias invalidation,
rejected incomplete/altered ownership results and equal-summary reuse after the
copier body edit. Changed PHP files pass lint and brace checks; document links
and patch whitespace checks pass.

### Assignment verification checkpoint

All 91 fixtures are verified across the full suite run (10 jobs, 76.1 seconds)
and focused reruns after updating the expected assignment-consumption label.
Final focused checks cover the list, ownership joins, field composition, custom
copy/assignment independence and zero-default values with custom destruction.
Native execution, O1/ThinLTO, retained snapshots and one body increment are covered;
these correctness timings are not an execution-performance claim. Runtime
preparation implementation and its metadata schema were unchanged.

### Owned result boundary

The [owned-result slice](owned_source_results_plan.md) now returns the custom-copy
list through source functions. The callee constructs an independent caller-owned
result and destroys its local; return resource summaries preserve the new allocation
obligations. The native proof covers forwarding, copying a const local borrow, a
returned enclosing record, discarded results and one producer-body edit. Temporary
record borrowing and generic list migration remain deferred.

## Managed growing-list proof

[The focused integration fixture](../../tests/integration/managed_growing_list.php)
uses the same source-written replacement algorithm with two concrete element types:
a metadata-provided heap-owning object without default-construction permission, and
a source record containing a managed runtime field. Concrete declarations are test
data; the compiler receives no generic exemption or type-name case.

Append accepts a call-scoped const borrow, constructs replacement copies, appends
the new value, then destroys/releases the old prefix and transfers the allocation.
Copy construction allocates independent storage; assignment copies the entire source
before touching the destination, including self-assignment. `get()` returns an owned
copy through the existing result path; `set()` uses ordinary element assignment.
No new list operation or native ABI was added to the compiler.

The proof found and repaired a body-dependency omission: `clear()` may destroy elements
without another expression naming their type. Body checking now retains implicit
element dependencies for dynamic storage and fixed arrays through one collection
method. Existing full/incremental workers, joins and reuse checks remain the owners.

Evidence includes growth order, mutation isolation, list and element copying/assignment,
self-assignment, clear/reuse, owned reads, early-return cleanup, no construction of spare
capacity, twenty matched backing-allocation acquisitions/releases, aligned live-object
identity and balanced element allocations. Ownership and lowering work accepts reversed
completion without changing retained inputs. One body edit changes execution while
reusing backend/native artifacts and preserving the old snapshot. O0/O1 and ThinLTO
execute the same accepted modules.

Direct `list.append(list.data[0])` is still rejected: the element borrow lasts for
the whole call, during which append invalidates its backing allocation. Materializing
an owned copy with `get()` before append is supported. Relaxing this requires a
separately agreed borrow contract/proof, not an exception for append's spelling.

Generic migration remains deferred. The default baseline still admits types whose
compiler-tracked allocation-owner fields cannot be analyzed at dynamic indices.
Next, discuss that indexed-ownership boundary before widening generic acceptance;
concrete managed success must not grant extra definition-level permissions.

Validation checkpoint: 110/110 compiler fixtures passed with ten workers in
168.3 seconds, including the existing scalar list and managed slot tests. Shared
fixture setup now observes element and backing-buffer ownership without changing
native preparation code. Changed-file comment/style, syntax, whitespace and
documentation file-link checks pass. No execution-performance claim is made.
