# Provider append aliasing investigation
Doc Status: supporting

Status: isolated native proof and metadata design findings, 2026-09-20.
Compiler integration and source interior-reference exposure remain pending.
The [family signature model](source_native_contract.md#accepted-family-requirements-and-operation-signatures)
and [conditional borrowing decision](lifecycle_contracts.md#agreed-direction-shared-analysis-and-conditional-interior-borrowing)
remain authoritative for this compiler's design scope.

## Contract evidence

The configured runtime at commit `fc20d73d040c4e69758bcec0b1caf40c26755f72`
implements `scpp::vector_t<T>::append(const T&)` by calling `std::vector<T>::push_back`.
Its current native `at`/index accessors expose element references. However,
[Simple C++ native reference safety](../../../../../simple_cpp_compiler/vendor/simple_cpp/specs/native_reference_safety.md)
is normative and broadly forbids that exposure. This mismatch is not permission
for general source references; our already agreed conditional-borrow refinement
requires a bounded validity proof and equivalent future S2S behavior.

The C++ [sequence requirements](https://eel.is/c++draft/sequence.reqmts)
define copy insertion for the const-reference argument; they do not impose a
non-overlap precondition on single-element copy append. The
[argument rules](https://eel.is/c++draft/res.on.arguments) allow a unique-reference
assumption for rvalue-reference arguments, not this const-lvalue-reference call.
Together these support permitting copy-based self-append. This conclusion concerns
that overload, not moving from an element or overlapping range insertion.

The separate [vector invalidation rules](https://eel.is/c++draft/vector.modifiers)
say reallocation invalidates element references/pointers/iterators. Without
reallocation, existing elements before the insertion point remain valid. Therefore,
safe consumption of an input alias is not a promise to preserve the old borrow
after append. The native wrapper's object address is distinct from element storage.

## Focused executable proof

[Runner and witness](../../src-runtime-preparation/tests/sequence_aliasing/README.md)
use the actual runtime and existing sequence helper, with no extra temporary copy.
Clang 18.1.3 passed twelve cases in each of O0, O1 and O1 with ASan/UBSan:

| Dimension | Cases |
|---|---|
| Element | `int32_t`; heap-backed tracked value with destructive noexcept move |
| Argument | Independent object; first existing element; last existing element |
| Storage | Spare capacity; filled to observed capacity so append must grow |

All values survived, the intended capacity change occurred, and tracked lifetimes
balanced. The managed witness observed one new-element copy in every case; growth
also moved the four old elements. These counts describe this implementation/run,
not a metadata promise about allocation or movement counts. LeakSanitizer is disabled
under the ptrace sandbox; ASan/UBSan remain active and object counts are checked.

No old interior reference is read after growth. The test proves native behavior;
it is not a compiler source-to-LLVM proof, prepared-ABI proof or a substitute for
the earlier mixed source-operation adapter proof. It does not test move-append,
reentrant element callbacks, recoverable exceptions or concurrent mutation.

## Proposed metadata meaning

Keep two distinct facts under the operation contract, expressed using parameter
positions and resource relationships rather than a vector-name compiler branch:

1. **Input overlap guarantee:** the designated read-only input may refer to an
   element of the receiver. The operation preserves/consumes the input value safely
   across its internal changes; the caller need not insert an unconditional copy.
2. **Borrow invalidation effect:** the receiver's element storage may be invalidated.
   For the first integration, treat append as potentially invalidating old element
   borrows; capacity-sensitive preservation can be a later precision improvement.

The first fact applies only to the designated input/receiver relationship and the
call's own consumption. It does not waive validity while evaluating arguments,
permit arbitrary overlapping mutable arguments or authorize subsequent use of an
old element borrow. No `noalias` ABI attribute follows from these facts.

A call-scoped const address alone cannot express both facts. Imported semantics
must tell analysis when the callee takes responsibility for consuming this input
safely despite an internal invalidation. Ordinary outstanding borrows remain subject
to the receiver's invalidation effect. Do not globally disable borrow checks or
claim the pointer remains valid for every instruction until return.

The existing [ownership call owner](../../src/04_analyze/analyze_lifetimes/resource_aliasing.php)
checks mapped preconditions and applies effects from one pre-call state; it remains
the intended analysis owner. The precise imported contract representation and its
join validation need to be settled with provider-family integration. Do not add a
standalone self-append path or assume current summaries already encode this guarantee.

## Remaining boundary

The isolated sequence helper still documents distinct source storage. This probe
justifies revisiting that contract during integration; no production contract was
silently widened. Keep the initial public proof on independent borrowed values and
void results. When interior element inputs are exposed, prove the allowed overlap,
rejected post-invalidation uses and one incremental alias-contract replacement
through the actual compiler pipeline. Capability requirements and artifact reuse
remain separate parts of the family design.
