# Managed elements in typed dynamic storage
Doc Status: supporting

Status: implemented bounded storage slice. Managed runtime values and source records
use ordinary selected lifecycle instructions at checked native element addresses.
The [concrete managed growing-list proof](source_list_plan.md#managed-growing-list-proof)
is also implemented. Generic migration and indexed allocation-owning elements remain deferred.

## Contract

Use the existing metadata-defined storage family. Allocation creates capacity but
no live elements; no default-construction capability is required from the element.
The compiler selects actual copy/assignment/destruction implementations from the
element's accepted lifetime contract. Runtime element implementations use their
prepared ABIs; source elements retain their compiler-owned complete operations.

| Operation | Successful transition |
|---|---|
| Push | Check next-slot capacity, copy-construct there, then commit the new live count. |
| Pop | Locate the checked last live element, destroy it, then decrement the count. |
| Indexed assignment | Assign to a live element through the existing selected assignment contract. |
| Release | Require zero live elements, then free allocation through the native bridge. |
| Transfer | Move the allocation and live count together; do not copy or destroy its elements. |

Failure remains fatal. Partial-construction recovery and exception cleanup are not
introduced. Source list methods continue to own append loops, replacement policy,
clearing and release. Storage operations own each individual slot transition.

## Ownership and implementation

The prepared primitives already provide `next`, `commit`, `count`, checked `at`,
`pop`, release and transfer. A managed pop can obtain `count - 1`, use checked `at`,
invoke destruction, and then use `pop`. No new native primitive or descriptor layout
is currently needed. Exact native symbols and counter types still come from metadata.

| Owner | Implemented responsibility |
|---|---|
| `resolve_types/Storage_Definitions` | Check supported element capabilities; select const-address passing for managed push inputs. Preserve exact family/element identity. |
| Checking and lifetime analysis | Preserve owner-associated indexed locations, construction versus assignment, and access validity throughout calls. Diagnose unsupported indexed allocation ownership explicitly. |
| Backend preparation and lowering | Bind selected element lifecycle targets before emission. Use `storage_address` for construction/destruction destinations, including both local storage and checked internal dynamic slots. |
| LLVM emission | Consume those selected operations and native prefix primitives in order. Scalar copying and empty cleanup remain the trivial cases of the same semantic operations. |

`Storage_Lowering::lower_storage_transition()` emits a paired `storage_begin` /
`storage_end` around ordinary `copy_construct` or `destroy` instructions. The same
`Local_Lowering::construct_at()` and lifecycle-target selection serve local and
dynamic storage. Scalar/plain-record value copies use a null ABI target, validated
against the element's trivial-copy contract; cleanup-free pop has no middle operation.

Emission consumes semantic arguments once at begin, asks the native bridge for a
checked slot, and records its type/address under a body-local ID. Ordinary lifecycle
emission consumes that destination, then end commits/pops the count and retires the
internal address. Begin/end must pair within one block. Internal addresses are not
source pointers. No source type names, native symbol guesses, descriptor offsets or
element lifecycle dispatch were added to the storage emitter.

Only body workers gained instructions; their fixed task inputs, private outputs, joins
and retained replacement boundaries are unchanged. Native preparation is unchanged.

## Bounded proof and exclusions

The integration fixture `tests/integration/managed_storage.php` proves storage operations using a managed runtime value and a source record
containing a managed runtime field, through the same metadata-driven family:

- Multiple pushes, independent copied contents, indexed copy/read and assignment.
- Reverse-order element destruction, exactly-once cleanup, empty storage release,
  and populated allocation transfer without element lifecycle calls.
- Wrong-type operations, invalid count/bounds, release with live elements and
  invalidated borrows reject through existing static/native boundaries.
- Existing scalar/plain-record storage remains supported through the common path.
- Fixed selected inputs, private outputs/joins, retained snapshot purity and one
  body increment; O0/O1/ThinLTO execution and focused allocation/event observations.

The [source-written growing list](source_list_plan.md#managed-growing-list-proof) now
uses this storage with concrete managed elements. Owned source parameters, general
movement, exception recovery and a new capability language remain outside this proof.
Source list insertion borrows existing managed values.

**Generic migration remains a separate acceptance gate.** The current analyzer tracks
allocation-owning fields at statically named locations; dynamically indexed owners
are explicitly unsupported. Managed opaque runtime objects do not require the same
per-field allocation-state analysis. Supporting those objects does not prove support
for arbitrary records with compiler-tracked allocation-owner fields.

Before enabling the generic list, its storage requirements must be satisfiable from
the declared baseline alone. Resolve the indexed ownership gap or diagnose the
unsupported integration; do not infer extra permissions from favorable concrete
arguments or add a generic-fixture exception.

## Validation and remaining risks

The main risks are shallow copying, destruction without the correct element address,
publishing a live count before construction completes, duplicate cleanup, and losing
borrow-owner provenance. Generalizing destination operands also touches ordinary
local/temporary/result lifecycle use, so preserve those cases in focused regression
proofs and run one ten-worker full suite after integration. Native preparation suites
need rerunning only if their implementation or exposure contracts change.

The native observer checks object identity, alignment, independent allocated payloads,
exact lifecycle event order and balanced allocation counts. A second nominal runtime
type exposes no default constructor: capacity allocation and copy insertion still work.
Native guards fail before invalid-slot lifecycle calls; an active managed-element
argument prevents owner release. Existing scalar/plain-record, runtime-copy and owned
result proofs remain regression coverage for the destination migration.

Storage-only checkpoint: all 109 compiler fixtures passed with ten workers in
169.7 seconds. Changed PHP files pass syntax and brace/doc-comment checks; relative
documentation file links and diff whitespace pass. Runtime preparation code and
contracts were unchanged, so its standalone suites were not repeated. These are
correctness and reuse proofs, not a new execution-performance benchmark.

The subsequent [managed growing-list checkpoint](source_list_plan.md#managed-growing-list-proof)
passes 110/110 fixtures and closes the pop-only body's implicit element-dependency gap.
