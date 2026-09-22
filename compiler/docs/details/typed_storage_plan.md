# Typed dynamic storage
Doc Status: supporting

Status: implemented local typed-storage slice, following the approved coordinated
migration. The [local allocation slice](allocation_ownership.md) remains supported.
[Owning fields](owning_storage_fields.md) and the later
[managed-element lifecycle extension](managed_element_storage_plan.md) are implemented. The [growing source-list proof](source_list_plan.md) now composes these contracts.

## Model extended by this slice

The earlier owner had an opaque native layout and an allocation obligation, but
no static element type. Its prepared operations have concrete signatures and
empty/owned effects. Adding element access to those calls alone would lose the
relationship between the owner, the element type and the live element range.

Checked `place` records start at a local and project through fields, fixed arrays
and now typed dynamic storage. LLVM emission implements those same locations.
The new element projection retains its allocation owner and static element type. General source function results also do not yet
carry borrowed addresses; adding an unchecked pointer result is not a substitute.

The existing template work prepares source definitions. A configured typed-storage
family needs a normalized definition/argument path; it must not be recognized by
its source name or fabricated as a source struct. This is a bounded prerequisite
for storage, not completion of native `vector<T>` specialization consumption.

## Implemented contract

Use a metadata-declared storage family with one type argument. `storage<T>`
is illustrative spelling; configuration supplies the actual name. Concrete
identity includes the family identity and the exact element type. Two instances
may share descriptor layout and native allocation helpers while remaining distinct
semantic types. Reuse the existing definition/argument identity owners.

The native descriptor owns address, capacity and constructed-prefix count. It also
retains the allocation facts needed for matching release. The element type remains
static in the compiler. Element size, stride and alignment come from accepted
backend layout, never source literals or host PHP sizes. Raw addresses and mutable
counts are not ordinary source fields.

Clang prepares descriptor and allocation primitives once per runtime/target
configuration. The compiler selects concrete element operations. No C++ definition
of a source element, or native reimplementation of its lifecycle, is required for
this storage primitive. Provider template specialization remains a later step.

### Operations and successful transitions

| Operation role | Required state | Result |
| --- | --- | --- |
| Empty construction | Uninitialized descriptor object | Empty owner, capacity/count zero |
| Allocate slots | Empty owner; positive capacity; valid checked allocation size | Owned allocation, count zero |
| Construct next | Owned allocation; count below capacity; eligible element construction/copy | Compiler initializes slot at count, then commits count + 1 |
| Access live element | Owned allocation; index in `[0, count)` | Typed access associated with that owner; no ownership transfer |
| End last element | Owned allocation; count positive; no conflicting borrow | Compiler ends the last element lifetime, then commits count - 1 |
| Release | No live elements and no conflicting borrow | Native matching release; empty owner; empty release remains allowed |
| Transfer | Distinct empty destination of the same concrete storage type; no conflicting borrow | Allocation and both counts move together; source becomes empty |

These are compiler-understood semantic roles with metadata-selected native
primitives. They are not arbitrary instruction sequences embedded in JSON.
Preparing a slot, initializing it and publishing the new count form one checked
operation; source code cannot manually publish an uninitialized element.

The initial proof used value-copy/no-cleanup elements. The later
[managed-element extension](managed_element_storage_plan.md) also accepts supported
copy constructors and destruction for runtime values and source records. End-element
remains an explicit lifetime/count transition when cleanup emits no code. Elements
with compiler-tracked allocation-owner obligations remain unsupported when indexed.

Dynamic bounds, positive capacity, allocation-size overflow and prefix-count
preconditions are checked at execution. Invalid input follows the agreed fatal
failure contract. Static analysis checks exact types, owner state and the validity
of access through its whole use. It need not prove arbitrary integer ranges or
enumerate execution paths. Normal exits still require discharged allocations.

## Affected owners and migration

| Owner | Change |
| --- | --- |
| Runtime preparation and package adapter | Declare/validate the storage family and native primitive roles, descriptor ABI and target integer contracts. |
| Shared type model, binding and concrete preparation | Bind a configured family to its element argument; retain exact concrete identity and capabilities through selected tasks/joins. |
| Body checking and checked locations | Represent typed storage operations and element locations with explicit owner provenance. Preserve evaluation order and existing local/field/array meaning. |
| Lifetime analysis | Extend allocation effects with prefix operations and element-access validity; retain private per-body outputs and fixed-point joins. |
| Backend preparation, lowering and emission | Supply accepted element layout, consume prepared primitive ABIs and emit compiler-owned element operations/count commits. |

Generalize shared concepts only where their meaning is the same. A fixed array's
capacity and an allocation's constructed count are different bounds; an object
borrow and an allocation-invalidating mutation need distinct effects. Preserve
ordinary calls and local allocation behavior through the migration.

Existing full/incremental selection remains the only pipeline. Family arguments,
provider contracts, element capabilities and layout are dependencies. A source
body edit must reuse unchanged prepared runtime artifacts and concrete types;
changed contracts follow the existing rebuild boundary.

## Bounded proof and risks

Prove local storage of two eligible element types through the same metadata family:
a scalar and a plain source record. Allocate several slots, construct in order,
read/copy through checked element locations, end elements in reverse order,
transfer a populated allocation and release after ending its elements.

Include wrong-type insertion/transfer, uninitialized/out-of-range access, release
with live elements, borrow invalidation and size-overflow failures. Prove native
execution, O1/ThinLTO, selected private work and joins, unchanged snapshot purity
and one body-edit incremental replacement. Run focused checks during development
and the shared regression suite once the migration is integrated, with 10 workers.

Principal risks are exposing an address without its owner, accepting an element
before construction commits, shallow-copying ownership, and using a stale element
layout. The proof must check these boundaries, not merely execute a scalar sample.

## Source surface and implementation owners

This is an explicitly agreed prototype extension. Configuration names the family
and its free template functions; the compiler understands their semantic roles.
Type arguments remain explicit on calls. Element indexing uses the existing
checked-location path.

```phs
const VALUE: int32 = 7;
$s storage<int32>;
storage_allocate<int32>($s, 3);
storage_push<int32>($s, VALUE);
$result int = $s[0];
storage_pop<int32>($s);
storage_release<int32>($s);
return $result;
```

`push` constructs a copy in the next slot before publishing its count.
`pop` ends the last element using its selected destructor when required. Trivial
scalar/plain-record copying and empty cleanup use the same lifecycle boundary. `count` can observe empty or allocated owners. These calls
neither allocate source-visible raw pointers nor expose descriptor fields.

Paths below are relative to ``.

| Path / owner | Responsibility |
| --- | --- |
| `src-runtime-preparation/storage.php`, `definitions/element_storage.json`, `include/scpp_provider/element_storage.hpp` | Validate the exposure protocol and export native allocation/prefix mechanics through Clang. |
| `src/01_prepare_inputs/load_runtime/handlers/storage.php` / `Package_Adapter` | Validate semantic and physical ABI roles, import the family, and keep raw native addresses private. |
| `src/04_analyze/type_model/data/storage.php` | Immutable family, concrete element and primitive contracts. |
| `src/04_analyze/instantiate/` and `resolve_types/storage.php` | Select argument work; join exact family/element identities; derive concrete signatures. Source helper templates use the same registry. |
| `src/04_analyze/check_bodies/handlers/places.php` | Associate indexed element places with their owner and static element type. |
| `src/04_analyze/analyze_lifetimes/allocation_flow.php` | Require owned allocation for access/mutation and preserve pending-borrow checks and normal-exit obligations. |
| `src/05_generate_code/prepare_backend/layout.php`, `storage.php` | Select target element layout and native ABI tasks, then accept private results at joins. |
| `src/05_generate_code/lower/handlers/storage.php`, `emit_llvm/handlers/storage.php` | Lower prefix transitions around ordinary selected copy/destruction instructions; emit checked native addresses and count commits. |

## Verification and boundaries

[Typed-storage integration proof](../../tests/integration/typed_storage.php)
renames the family and all source operations through JSON. It executes scalar and
record elements (including fixed-array fields), dependent source-template calls,
record copies and call-scoped borrowing, populated transfer, explicit removal and
release. It checks O0 and O1/ThinLTO, rejected type/copy/owner uses, dynamic prefix
and overflow failures, metadata rejection, reordered/missing/duplicate/malformed
ABI results, snapshot purity and one body increment reusing native preparation.

Object borrows into storage retain their allocation owner through a call. Mutation
roles conservatively invalidate outstanding borrows; current mutation calls return
void, which limits where nested mutations can appear in valid source expressions.
The existing allocation proof independently checks nested invalidation effects.

The later [owning-field slice](owning_storage_fields.md) adds field obligations and
source ownership summaries; the [source-list proof](source_list_plan.md) composes
them; list copy/assignment is proved for concrete scalar elements. The later
[managed-element slice](managed_element_storage_plan.md) extends slot lifecycle.
Native family consumption has its separate [source/native boundary](source_native_contract.md).
This storage family remains a native storage protocol, distinct from provider `vector<T>`. Fatal failure does
not promise cleanup or recovery.
