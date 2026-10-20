# Prelower representation mechanisms and ownership audit
Doc Status: planning

Date: 2026-10-19. Baseline: `39e9139b`, branch `v0.2/prelower_templates`.
This is a source-level design audit, not a new implementation or performance proof.
Scope: relevant type, preparation, C++ emission, publication and native-build owners
in `compiler/my-try/`. It is not a line-by-line audit of the whole compiler; LLVM,
parser correctness and unrelated runtime behavior are outside this review.

## Recommendation for discussion

Start with two storage mechanisms and separate transport rules. Preserve the original
runtime implementation and one actual stored object. A facade must not acquire a
second heap object or materialize a second value just to make a call.

1. **Direct representation.** Keep an already suitable concrete C++ representation
   unchanged. Scalar value adaptation may be available where the existing operation
   is a value operation; it is not an implementation of a reference operation.
2. **In-place opaque representation.** A concrete application owner contains storage
   for the original runtime object. Construction, destruction and permitted copy/move
   operations execute out of line against that object. Size and alignment must come
   from the target toolchain and runtime configuration, never guessed constants.
   Existing runtime allocations remain; the boundary adds no separate allocation.

Borrowed transport is not a third owning storage type. Readonly/mutable operations
must access the original object in place. A returned element borrow must designate
that actual element and retain the existing prepared lifetime restrictions. Do not
turn a borrowed element into a temporary concrete owner or reinterpret one wrapper
class as another merely because their sizes agree.

Shared, unique and weak handles retain their existing ownership semantics. They can
use these storage mechanisms; they do not need another ownership system. Required
source-level copies or shared-owner retention are not redundant boundary overhead.
Value results should construct in their final destination where possible. Existing
by-value runtime APIs still need individual ABI proofs: an out-parameter wrapper does
not by itself eliminate a move/copy performed inside the called API.

## Existing owners: reuse rather than duplicate

Paths below are relative to `compiler/my-try/`.

| Concern | Existing owner/evidence | Consequence |
|---|---|---|
| Type identity, applied arguments, capabilities | `compiler/types/`, `compiler/types/queries.php`; capability preparation | Keep one registry. Do not introduce a specialization type system or second capability cache. |
| Inline versus shared/unique/weak storage | `Type_Queries::record_storage`, `record_payload`, `is_nominal_handle` | Consume these decisions; backend physical layout is a separate concern. |
| Generic runtime signatures and substitution | `compiler/callables/structures.php`, prepared runtime type bindings | Reuse canonical arguments and selected signatures, not a new template matcher. |
| Value boundary operation | `04_analyze/prepare/semantics/value_boundaries.php` | Existing copy/consume/empty/reference/observation decisions govern transport. |
| Borrow origin and lifetime | prepared places/borrows/regions, reference and lifetime preparation | No engine-owned borrow analysis. Whole-parameter reference-return contracts do not automatically describe container-interior borrows. |
| Legal explicit moves | `04_analyze/prepare/semantics/moves.php` | Preserve the current whole-local/parameter unique-transfer rule; do not infer new source move permissions from a C++ move constructor. |
| Conversion selection | `04_analyze/conversions/model.php` and preparation | ABI transport is not another semantic conversion resolver. |
| Callable adaptation | prepared callable adaptation and its preparation | Reuse semantic compatibility; do not conflate it with physical opaque-storage adaptation. |
| Indexing, iteration, initialization | existing contracts, selected prepared operations | Extend their C++ bindings where necessary, rather than add engine operation-name lookup. |
| C++ type mapping | `05_backend/cpp/types.php`, `type_bindings_catalog.php`, `cpp_type` | Extend this owner for physical representation. Do not add a competing type-mapping registry. |
| C++ callable mapping | `05_backend/cpp/callable_bindings.php` | Attach invocation/transport information to existing semantic identities. |
| Ordered calls and receiver retention | `05_backend/cpp/declarations.php` | Preserve argument order, receiver checks and owner lifetime; centralize adapter transport here. |
| Request references and replay | `05_backend/cpp/specializations.php`, fragment structures | Keep the compact invocation-local entry ID and canonical cached requests. No retained engine worker. |
| Helper specialization | `05_backend/cpp/compiler_functions.php` | Existing enum-helper artifacts are precedent, not a general runtime-template engine. |
| Callable instances | `compiler/callables/instance_structures.php` | These own source method working bodies; do not reuse them for runtime ABI specializations or create cloned ASTs. |
| Persistent artifacts and objects | output publication and `06_native/` | Reuse manifests, dependency validation and action caching; no parallel specialization object cache. |

## What is genuinely missing

The existing `prepared_type_lifecycle` contains destructor visibility, not a physical
constructor/copy/move/destructor ABI description. Preserve its semantic role. Extend
backend bindings for the implementation of legal lifecycle operations; do not turn
that prepared fact into a native-layout record.

No general target sizeof/alignment fact was found in the reviewed type, preparation
and C++ backend PHP sources. Existing declaration/complete-type requirements describe
header visibility, not numerical layout. Opaque inline storage therefore needs a
backend/native-build layout artifact protocol. Its identity must include concrete
type arguments, representation policy, runtime/header inputs and native ABI options.
Do not call Clang from semantic preparation or add a semantic capability named
"layout known". This is a design prerequisite, not an already implemented mechanism.

The hard case is nested storage: a runtime `vector<OriginalInteger>` contains original
integer objects, not concrete application wrapper objects. A method returning an
element cannot honestly return a reference to the latter. Likewise an existing
provider accepting one vector instantiation cannot accept a different instantiation
without a real adaptation. Two directions need a bounded proof:

- Keep original runtime element storage and use internal borrowed-address bindings
  for operations/projections, with existing prepared lifetime facts.
- Instantiate the runtime over the canonical concrete element representation, where
  existing runtime traits and provider signatures support it.

Neither is universally established by the accounting experiment. Do not solve this
with vector-specific conversions or assume capabilities alone supply C++ trait
specializations. The first direction best preserves existing provider signatures;
its integration with generated source functions, references and nested fields must
be demonstrated before adopting a general retained representation protocol.

## Duplication and gaps found

1. **Confirmed architectural debt:** `CPP_Specializations::request` selects integers
   and vector explicitly, and `CPP_Concrete_Recipes` implements vector storage and
   lifecycle itself. This was the bounded performance proof. Do not extend it into
   one compiler implementation per runtime family; replace it through runtime-owned
   bindings once the general boundary is proved.
2. **Confirmed parallel emission paths:** `CPP_Concrete_Operations` has concrete
   indexing/iteration/arithmetic paths alongside ordinary emission. Preserve shared
   prepared decisions and converge transport/dispatch as the new binding path lands.
   Do not move runtime algorithms into the engine to make these paths converge.
3. **Overlapping query logic:** `Capability_Facts` and `Capability_Preparation` both
   select source/runtime capability answers. Their completion/dependency behavior is
   different. A shared settled-fact reader may reduce duplication, but replacing
   preparation queries with backend reads would lose scheduling/dependency duties.
4. **Potential context propagation gap:** `generate_call` requests
   `CPP_Types::payload($receiver->record_type)` without the generation context while
   other receiver paths pass it. Investigate with a prelowered receiver test; this
   audit does not claim a reproduced failure.
5. **Copy-count risk in current calls:** `selected_call` materializes a typed local
   and passes a named lvalue for `copy_value`. A by-value target can therefore have
   another copy boundary already in the baseline. Measure actual counters before
   attributing overhead to prelowering; a wrapper must not add another snapshot.
   `observed_call` already provides readonly non-retaining access and should be reused.

## Native mechanism proof checkpoint

The [counter and nested-reference proof](results/prelower_boundary_2026_10_19/README.md)
now passes for direct versus opaque storage at O0/O2, with sanitizers and optional
copy elision disabled. It validates the native mechanism, including partial-copy
cleanup and a negative control. The subsequent [API integration](results/prelower_layout_integration_2026_10_19/README.md)
adds native layout actions and a bounded prepared-observation bridge through existing
bindings. The [ordinary-expression checkpoint](results/prelower_expressions_2026_10_19/README.md)
now adds operation replay and shared nested-path lowering for the bounded runtime ABI mode.

## Integration proof, before broader implementation

Keep this as a backend/binding proof, not a new AST or semantic pass. Use one generic
transport implementation across integer wrappers, a container, nested elements and
an existing ownership handle. Type selection belongs to registry/binding data.

- Verify exact target size/alignment and construction/destruction in place.
- Count allocations, copies, moves, destruction and required owner retention against
  the current runtime path. Include failure/exception cleanup, not just success.
- Prove mutable and readonly element access preserves identity; include a nested
  element, aliasing append and reference invalidation under existing rules.
- Exercise a real runtime provider boundary and a generated source-function boundary,
  including borrowed input and owned output. This catches incompatible nested types.
- Prove cold/warm/edit artifact reuse through the existing publisher/native builder.
- Only then compare application and support compilation separately, with debug off.

A layout-artifact bootstrap or a general borrowed representation may touch multiple
owners. Agree that protocol before coding the cross-owner change; do not silently
expand the first slice. No new source capabilities, lifetime semantics, heap boxing,
parallel registry, background engine thread or LLVM work is part of this proposal.

[Capability matrix](runtime_template_capability_matrix_2026_10_19.md) ·
[Implementation plan](cpp_template_prelowering_plan_2026_10_19.md)
