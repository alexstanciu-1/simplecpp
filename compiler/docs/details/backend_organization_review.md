# Backend preparation ownership review
Doc Status: supporting

Status: extraction implemented. `prepare_backend` owns preparation contracts,
workers/joins and the retained toolchain. `lower` owns execution plans, including
native-entry adaptation. The process runner is independent of both applications.
See the [current call map](../../src/05_generate_code/prepare_backend/calls.md).

The rationale below records the ownership decision; current placement is summarized
here instead of retaining a second proposed tree.

## Conclusion

Backend preparation has a sibling `prepare_backend/` folder under
`src/05_generate_code/`. It already has its own inputs, selection,
workers, join and retained result. Instruction lowering is a consumer of that
result. The folder exposes this existing boundary without changing
scheduling, language semantics, tool behavior or cache policy.

The smallest coherent extraction includes its backend data and shared LLVM
vocabulary. Moving only the backend entry would leave the new owner depending on
lowering for its own contracts.

## Current responsibilities

| Owner | Reads | Produces or changes | Consumers |
|---|---|---|---|
| [LLVM_Backend](../../src/05_generate_code/prepare_backend/main_prepare_backend.php) | Type resolution, verified target configuration, previous context and rebuild selection | Private callable bindings, accepted through `Backend_Join` into `Backend_Context` | Lowering; module/function emission; native entry preparation |
| [Native_Entry](../../src/05_generate_code/lower/main_native_entry.php) | Selected callable binding and verified native return width | Separate immutable native-entry adaptation plan | Module assembly |
| [LLVM_Toolchain](../../src/05_generate_code/prepare_backend/tools/toolchain.php) | Configured executables, policy source bytes, target/signature requirements and explicit object/link requests | Session-local successful-probe caches; target/link contracts; external tool outputs | Backend preparation, compiler coordinator and native building |
| [Tool_Process](../../tool_process/process.php) | One command, input, process-group launcher and timeout | Process execution, output and cancellation/cleanup | Toolchain service |
| [Lowering_Worker](../../src/05_generate_code/lower/body.php) | Checked body, lifetime analysis and fixed backend context | Private explicit instruction/control-flow plan | LLVM emission |

Preparation does not require checked bodies or lifetime results, even though the
current coordinator invokes it after lifetime analysis. Binding selection uses
configuration, signature and referenced type definitions. Body-only edits reuse
bindings; deleted contributions are removed independently of selection. The join
preserves an equal complete context's identity.

The toolchain is a session-owned service, not a body worker. Its object/link
methods are used during native building, not during callable preparation. Those
operations can remain on the same configured service after extraction; a
separate tool-service folder is not required merely because it has several
consumers. Mutable probe/process state must remain outside fixed-input workers.

## Accepted placement

- `prepare_backend/data/`: configuration, callable bindings, physical ABI targets,
  layout, lifecycle tasks, tool results and the accepted backend snapshot.
- `prepare_backend/`: phase, workers and joins; shared spelling/validity in `utilities/`.
- `prepare_backend/tools/toolchain.php`: the retained configured tool service.
- `lower/`: body instructions, slots, blocks and native-entry plans.
- `tool_process/`: bounded process execution shared with runtime preparation.

Consumers use the new namespaces directly, without compatibility aliases.
The backend preparation test follows its owner. `integer_adaptation` belongs to
backend ABI vocabulary because body conversions and native-entry adaptation share it.

## Scope, risks and validation

The existing model can already express this separation. The extraction touches
backend data, lowering, emission, native building, compiler coordination,
bootstrap loading, tests and documentation. It is a bounded ownership migration
across those consumers, not just a file rename.

The main risks are accidentally leaving a backend record under `lower`, creating
a dependency back to lowered plans, losing exact retained-object identities,
and breaking source-fingerprint or default configuration paths. The toolchain
currently fingerprints preparation, join, utility, entry and process sources;
the relocated source-invalidation test must follow that complete policy set.
Linker-only and scheduling-only changes must retain their existing distinct
invalidation behavior.

Before claiming the extraction complete, verify loading and public consumers;
run preparation tests for fixed inputs, reversed joins, signature/target changes,
source fingerprints and repair; then run the full suite for emitted plans,
native execution, object/link reuse, publication and process cancellation.
No performance claim or benchmark is needed for the organization change.

Splitting target probing from object/link execution, adding backend plugins or
moving the phase within coordinator execution remain separate design changes.
