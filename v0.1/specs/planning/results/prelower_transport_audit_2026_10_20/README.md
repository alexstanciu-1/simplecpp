# Prelowering transport and lifecycle audit — 2026-10-20

Doc Status: planning

Reviewed implementation: `19d54313`. This is a source/documentation audit, not a
new execution or benchmark. No compiler/runtime behavior or retained metadata changes.

The existing owners provide a coherent physical boundary. Preparation selects
operations, argument value boundaries and receiver lifetime. Ordinary emission
sequences them; C++ adapters transport their concrete representations. The engine
interns demands, assembles interfaces and orders layout dependencies. Runtime code
retains algorithms and native lifecycle behavior. No new registry or family-specific
engine processing is needed for the audited contracts.

## Inspection and evidence

Paths below are relative to `compiler/my-try/`.

| Area inspected | Finding | Existing proof |
|---|---|---|
| `05_backend/cpp/declarations.php`, `source_calls.php` | Selected calls preserve typed snapshots/references, consume categories, defaults and receiver lifetime. Source transport explicitly rejects reference results, variadics and observation parameters. | Source lifecycle and ordinary/concrete counter comparison |
| `05_backend/cpp/runtime_operations.php` | Inputs borrow existing storage; mutable access follows prepared boundaries. Owned results construct in final storage, or return an already-concrete provider result. Observation operands stay in the full expression. Value/default transport and single-observation transport have different admission limits. | Runtime-call/output proofs; combined header/counter acceptance |
| `05_backend/cpp/concrete_members.php` | Demanded member interfaces forward to native outer storage with concrete arguments. Only void-returning, nonvariadic runtime members are currently admitted. | Member and nested-container proofs |
| `05_backend/cpp/opaque_representation.php`, `initializer_buffer.php` | Native objects are constructed/destructed, not copied as bytes. Probes supply layout and traits; capability facts independently restrict copying. Buffer adapters preserve the ordinary lvalue/prvalue categories and runtime cleanup. | Initializer failure/order counters; source lifecycle's five failure stages |
| `05_backend/cpp/specializations.php`, `06_native/cpp_build.php` | Generated layout dependencies are ordered or rejected as cycles. Existing native actions compile/link/run probes and cache their output; metadata contains no guessed layout. | Layout-cache/provider mutation/failure/recovery acceptance |

The [interoperability guide](../../../../runtime/specs/concrete_type_interoperability.md#transport-and-lifecycle-contract)
now records these fixed protocols and provider obligations. The
[current coverage table](../../runtime_template_capability_matrix_2026_10_19.md#current-coverage-contract--2026-10-20)
now reflects completed public-option/header/PCH enforcement, fresh accounting
acceptance, and the migrated source lifecycle proof. Its older implementation
checkpoints remain dated history.

## Limits retained

- Registered representation metadata does not establish arbitrary provider operation
  compatibility. Provider C++ must compile against the selected physical interfaces;
  strict application closure validation remains mandatory.
- Runtime member value/reference results, reference-result/variadic call transport,
  mixed observation/value inputs and multi-input observation transport remain gaps.
  Observation-path defaults/conversions are rejected; ordinary value-path prepared
  defaults are supported. Source call observations are a separate explicit rejection.
- Boxed write-back, broader provider composition, callback/exception transport and
  unresolved generated header/layout cycles retain the coverage table's boundaries.
- Clang probes currently execute target code. Cross-target execution support is not
  established by this audit or the host-native acceptance.
- No-extra-boundary-allocation/copy evidence applies to tested O0 interactions. It
  does not imply that the runtime itself never allocates, or prove every combination.

Validation baseline: the [combined acceptance](../prelower_acceptance_2026_10_20/README.md)
passed all eight phases, including accounting 998/0 in each mode and complete
16-unit template-free closure. Those runs are reused as evidence, not relabeled as
fresh tests for this documentation-only slice. This slice checked the documentation
changes and local links; it did not rerun native builds.

Next bounded work: reconcile the remaining metadata-validation checklist against
its negative/recovery proofs, then close the demand/replay/publication checklist
with explicit evidence. Broader runtime coverage and optimization remain deferred.
