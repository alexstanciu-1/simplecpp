# Backend preparation call map
Doc Status: supporting

Compiler_Session runs final backend preparation after lifetime analysis and before
lowering. Its update-scoped Layout_Coordinator also serves ready record batches
during Concrete_Preparation, before dependent type work continues.
The backend owners retain authority over selected target/layout/ABI preparation.
Their tools are retained services; lowering consumes the accepted Backend_Context without running probes.

The following are lifecycle calls in order, not calls between siblings.

```text
LLVM_Backend                                  main_prepare_backend.php
  init() -> LLVM_Toolchain::configuration(); verify_runtime() [if runtime input set]
         -> [action] capture shared layout service; select signature, lifecycle and native storage tasks
  run() -> Storage_Preparation::prepare()     storage.php [each native primitive]
        -> Layout_Coordinator::prepare()     layout_coordinator.php [remaining required roots]
        -> Callable_Preparer::prepare_callable(); prepare_lifecycle() [each task]
        -> LLVM_Toolchain::verify_signature() [selected physical signatures]
  finalize() -> Lifecycle_Join::join()        lifecycle_join.php
             -> Storage_Join::join()          storage_join.php
             -> Backend_Join::join()          backend_join.php
  result() => Backend_Context                  data/context.php
```

Workers produce private results from fixed inputs. Joins accept complete selected
results, reject stale or duplicate provenance, retain current unselected contracts
and omit removed contributions. Imported record measurements constrain the same
selected target layouts used by source records.

## Shared layout work

```text
Compiler_Session -> Layout_Coordinator [one update; prior layouts + fixed rebuild flag]
  Concrete_Preparation::prepare_records() -> Record_Join::join(); Layout_Coordinator::prepare(ready roots)
  LLVM_Backend::run() -> Layout_Coordinator::prepare(all currently required roots)

Layout_Coordinator::prepare()                   layout_coordinator.php
  -> Layout_Preparation::capture(); subset()   layout.php [fixed reachable dependencies]
  -> Layout_Preparation::select(); prepare()   [selected tasks only]
  -> Layout_Join::join()                       layout_join.php
  -> [action] retain accepted facts and shared dependency nodes for this update
```

Workers receive Layout_Input, not Type_Store. Its dependency nodes contain shared
accepted definitions/fields and child references. Nodes are reused across batches;
each accepted layout retains only its reachable graph and exact type lineage.
Selection checks nested dependencies as well as root identity and target. No AST,
coordinator or mutable canonical store enters worker or retained layout contracts.

Already accepted roots reuse their exact objects within the update, including a full
rebuild. Other roots use the fixed full/changed selection policy against prior layouts.
The final requested membership omits removed contributions. Backend_Context retains
the resulting facts; the coordinator service itself is not retained in snapshots.

## Contracts and services

| File | Owner or role |
|---|---|
| `data/configuration.php` | Verified target configuration |
| `data/bindings.php` | Semantic callable bindings and parameters |
| `data/abi.php` | Physical ABI targets and integer adaptations |
| `data/layout.php` | Shared dependency nodes/views, tasks, private results and accepted measurements |
| `layout_coordinator.php` | Update-scoped early/final layout selection and acceptance |
| `data/storage.php` | Selected native prefix ABI tasks and private results |
| `data/lifecycle.php` | Selected lifecycle work and private results |
| `data/tools.php` | Object compilation results and link configuration |
| `data/context.php` | Indexed accepted backend snapshot |
| `utilities/callable_contract.php` | Fixed-input validity and link-name policy |
| `utilities/llvm_types.php` | Shared primitive LLVM spelling |
| `tools/toolchain.php` | Session-owned tool configuration, probes and native tool calls |

LLVM_Toolchain is also used by native building. Layout workers and the toolchain
use the independent [Tool_Process](../../../tool_process/calls.md) service.
Neither service owns body lowering or source semantic decisions. Native-entry
plan construction remains in `../lower/main_native_entry.php`.

Callable preparation consumes accepted const/mutable borrow contracts as one-pointer
parameters. Source reference restrictions belong to type resolution; provider borrow
permissions come through the adapter. Opaque objects and records use the same address
ABI, including managed records and method receivers. Record storage still requires its
accepted target layout and any provider-native layout constraints. No aggregate value
ABI classification is introduced. Imported const integer borrows use the same pointer
ABI; `callable_parameter` rejects mutable integer borrowing. Scalar temporary storage
belongs to lowering, after ordinary argument conversion and lifetime checking.

`LLVM_Toolchain::configuration()` calls `policy_fingerprint()` to discover sorted
PHP policy sources under this owner, excluding `tools/`, plus the native-entry
adapter. File membership and content invalidate ABI preparation together. Toolchain
and process-runner implementation bytes separately invalidate execution configuration;
unchanged inputs reuse verified probes.

Lifecycle selection and acceptance include imported operations and compiler-owned
source operations from the accepted type store. They share physical address ABIs;
the backend prepares no source behavior. Layout_Preparation selects explicit native
alignment measurement when the field graph contains opaque runtime storage.
Native_Layout (native_layout.php) supplies layout-only C++ shells; the worker verifies
primitive size/alignment against LLVM before accepting the measured record offsets.
No C++ source lifecycle functions are generated by this layout probe.

Backend_Join::lifecycle_bodies() resolves each custom-body declaration/concrete-type
pair against the accepted callable bindings. Backend_Context retains and validates
the complete operation's body ABI import, including the exact const same-type
source of a copy or assignment body; no source body is generated here.
LLVM_Types::lifecycle_arity() selects two addresses for either operation, without
conflating their semantic roles.

Typed storage adds demanded scalar element layouts to the same target measurement
path as records. Native prefix primitives are prepared independently of concrete
source element types; Storage_Join accepts exact primitive/configuration provenance.
The emitter consumes these targets and measurements without running new probes.

Runtime_Input_Set supplies all imported callables, lifecycle and storage contracts.
Backend_Context validates external call bindings by exact provider/operation identity.
The toolchain verifies the set's common target/link context against its configuration.

Callable_Contract::result_passing() supplies the shared result ABI policy for selection
and acceptance: owned source results use a hidden destination and physical void return;
imported ABIs retain their validated explicit contracts. Copy and move construction
use the same selected two-address lifecycle work units and joins.

## Selected source export boundary

```text
export coordinator (explicit project context + accepted identities/types/layouts + roots)
  -> Source_Export_Preparation::capture()       source_export_preparation.php
       -> Source_Identities::for_type()          ../../04_analyze/resolve_types/source_identities.php
       -> Lifecycle_Composition::complete_operation() ../../04_analyze/resolve_types/lifecycle_composition.php
  -> Source_Export_Preparation::select()
  -> [each selected type] Source_Export_Preparation::prepare()
       -> Callable_Preparer::prepare_lifecycle() callable.php
  -> Source_Export_Join::join()                 source_export_join.php
       -> Source_Export_Preparation::validate(); current()
  -> [action] share accepted source_type_export records
```

`data/source_exports.php` owns fixed tasks, six-role capability states and import/
implementation associations. Tasks retain accepted layout/dependency rows and exact
portable keys, never syntax or mutable stores. Output records are read-only inputs
for `src-runtime-preparation/project/Source_Adapter`. Role semantics authorize call-scoped
payload access; ABI preparation does not infer optimization attributes from them.

This selected boundary serves compiler source-dependent family demands, as shown
below. The [integration gates](../../../docs/details/source_family_integration_plan.md)
record the initial automatic-record subset; [custom export verification](../../../docs/details/custom_source_exports.md)
extends it without moving body checking into early preparation.

## Source/native compiler integration

```text
Compiler_Session -> Source_Export_Coordinator             source_export_coordinator.php
  Concrete_Preparation -> tasks()
    -> Source_Identities; Layout_Coordinator::prepare()
    -> Source_Export_Preparation::capture(); select(); prepare()
    -> Source_Export_Join::join()                         fixed private export results
LLVM_Backend::init()
  -> Project_Exports::prepare()                          project_exports.php
       -> [action] merge Runtime_Package::source_imports (adapter-normalized obligations)
       -> Layout_Preparation::capture() [one graph for all source roots]
       -> [action] validate retained layouts against current lineage/dependencies
       -> Export_Verification::prepare()                 export_verification.php
          -> [action] capture current body/lifetime/ownership associations; select changed checks
          -> Export_Worker::prepare(); Export_Join::join() ../../04_analyze/analyze_lifetimes/
       -> [action] merge native-demanded and ordinary lifecycle roots
  -> [action] select common lifecycle ABI work
LLVM_Backend::finalize()
  -> Lifecycle_Join::join(); Backend_Join::join()
       -> Backend_Context                               shared source_linkage operations/entries
```

`data/source_exports.php` also owns `source_linkage`, one fixed backend-phase result.
Export demands can require primitive complete copies or empty cleanup absent from
ordinary type lifecycle roots. They use the existing complete plans and ABI workers;
source semantics are never reconstructed from native layout. Stable exports forward
to those implementations during entry-module assembly.
The backend consumes normalized import records; receipt parsing stays in package
import. Repeated references to the same accepted layout share its validation.

`source_linkage::verifications` is separate from declared package imports. Every
advertised available operation needs current evidence, even if a native variant does
not import it yet. `Backend_Context` requires matching evidence for emitted exports.
Body edits invalidate this evidence while compatible native packages remain reusable.
