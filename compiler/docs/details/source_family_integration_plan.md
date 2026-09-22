# Source-defined arguments for native families
Doc Status: supporting

Status: all four gates implemented for the bounded automatic-record subset,
2026-09-21. End-to-end execution, balanced cleanup, one body increment and O0/O1/ThinLTO
are proved. The subsequent [custom lifecycle export slice](custom_source_exports.md)
adds explicit implementation verification; general moves and the other exclusions remain deferred.
Follows the [source/native contract](source_native_contract.md) and the completed
[ordinary runtime argument integration](provider_family_compiler_integration.md#ordinary-managed-runtime-arguments).

## Intended result and bounded proof

Compile a native family containing a source-defined managed record. The compiler
continues to own the record's fields and complete lifecycle operations; Clang owns
the native family and a payload adapter that calls those operations.

Start with automatically composed records whose existing field contracts provide
copy construction, copy assignment and cleanup. Use a default-constructible prepared
runtime field, plus a scalar, so the record can be created through current source
syntax. Repeat with a second compatible field arrangement, including a nested source
record. Family compatibility and operation requirements remain metadata declarations.
Neither the field type nor the family name selects compiler behavior.

The first proof needs family construction, copy-based append, owned copy-out, family
copy/assignment and cleanup. A body-only edit changes a source function's observable
result while preserving the source export contracts and reusing native artifacts.
Custom lifecycle body edits need a later proof: accepting their checked effects
before native export would otherwise enlarge this first scheduling migration.

Initial gate non-goals (custom bodies are now covered by the follow-up linked above):
custom lifecycle bodies in exported records, general source
moves, move assignment, classes/smart-pointer integration, interior references,
recursive incomplete types, hashing/equality, additional template capability syntax,
and generic source-list migration. Existing support outside native export remains
unchanged. Capability availability stays explicit; unsupported source move hooks are
not advertised or silently replaced with copy hooks. The selected native family
must genuinely compile its demanded operations under those declared capabilities.

## Why the current flow needs a coordinated change

| Current owner | Implemented fact | Required change |
|---|---|---|
| `resolve_types/Concrete_Preparation::prepare_family_types()` | Prepares a family before its dependent records/signatures continue. | Wait for accepted physical facts for any source arguments, through the existing readiness queue. |
| `prepare_backend/LLVM_Backend` | Selects and joins layouts after complete type resolution and body/lifetime checking. | Make the existing layout algorithm available for ready dependency batches earlier; final backend preparation consumes the same accepted facts. |
| `resolve_types/Lifecycle_Composition` | Owns complete source operation plans; link names use lineage-local type IDs. | Associate persistent project-scoped export identities with those operations without turning local IDs into native cache identities. |
| `families/Compiler_Bridge` and `Arguments` | Import real native types from accepted packages. | Accept a distinct source-payload description whose native adapter is not the source object's representation. |
| `Runtime_Preparation` / `Prepared_Request` | Accept self-contained packages and require a native link with no unresolved symbols. | Add an explicit project-module contract with authorized compiler imports, retaining the ordinary package guarantee. |
| `load_runtime/Package_Adapter` and `Input_Join` | Native argument imports bind owned opaque native types. | Bind source payload parameters/results to existing record definitions through explicit crossing contracts. |
| `emit_llvm/Lifecycle_Emission`, module assembly and native building | Emit complete source plans and link runtime packages. | Emit selected export entry points and validate that every project-module source import has one matching definition before final linking. |

Calling the whole backend early cannot solve this: its inputs require completed type
resolution. Reconstructing record layout inside runtime preparation would introduce
a second layout authority. Accepting unresolved symbols in ordinary packages would
weaken an established guarantee. These are model boundaries, not missing type names.

## Recommended implementation gates

### 1. Shared physical preparation, before adding source arguments — implemented

Extract the existing layout selection/worker/join lifecycle into a reusable owner in
`prepare_backend`, retaining `storage_layout` as the accepted result. It must accept
an explicitly selected, dependency-complete type subset; final preparation uses it
for the remaining required types. `LLVM_Backend` and early native preparation share
this owner and algorithm.

Adapt `layout_task` so an early worker reads a fixed dependency view, rather than a
`Type_Store` that the coordinator subsequently extends. Capture referenced accepted
definitions/fields and target configuration once per batch. Do not clone a whole
compiler snapshot per record or add a new general graph/scheduling framework.

The existing `Concrete_Preparation` queue reports unmet physical prerequisites;
the coordinator schedules ready target work and joins results before dependent
family work. Semantic readiness and physical readiness are separate facts about the
same canonical type. Dependencies such as source record -> prepared native field
and native family -> source element layout use that same queue. Unsupported cycles
produce a diagnostic.

First checkpoint: existing scalar/plain/managed/nested record layouts and ordinary
native family compilation still use the common path; reverse-completion joins,
stale target/definition rejection and one body increment pass. No source-family
support is claimed at this checkpoint.

### 2. Exact source export identities and operation contracts — implemented

Keep source identity projection with resolution and coordinator-owned project
configuration. Use the exact project/declaration/tagged-argument keys already agreed
in the source/native contract. A project key and configurable native output root
must be explicit inputs; absolute workspace paths and session IDs are not portable
export identities.

Backend preparation owns compact export records referencing accepted source identity,
layout, target, lifecycle capabilities and prepared import ABI. One record per source
type and operation is shared across demands. Preparation consumes these records
through an adapter; it never receives the AST or a mutable type store.

All six lifecycle role states remain explicit, even where this slice supports fewer
available roles. For the initial automatically composed subset, accepted constituent
contracts justify the complete operation. Record dependencies containing custom bodies
are diagnosed as unsupported for export until their checked effects can participate.

Stable external symbols may be thin compiler-owned export entries calling existing
complete lifecycle definitions. Such entries only forward and carry exact export
provenance; they must not duplicate field composition. This preserves existing
lineage-local operation names while making native imports stable across compatible
compiler rebuilds. Prefer this bounded association over rewriting every internal
lifecycle identity merely for artifact reuse.

Checkpoint: distinct same-layout source types have distinct exports; stale lineage,
layout and ABI associations reject. Rebinding a stable export to a compatible current
compiler definition does not alter native specialization identity.

### 3. Project-module preparation and explicit payload crossings — implemented

Add a versioned source/project request and result boundary. Reuse artifact generation,
reservations and publication mechanics where their meaning is identical. Keep
self-contained package acceptance distinct from project-module acceptance with source
link obligations. No dummy source definitions satisfy preparation's link check.

The generated adapter contains aligned payload storage and forwarding special members.
The source export supplies its layout and available operation imports. Clang validates
size, alignment, stride and payload offset against the accepted facts.

Provider metadata explicitly authorizes the adapter profile and describes each
payload crossing. Copy-in constructs a real adapter from a borrowed source payload;
copy-out invokes the compiler operation into caller storage. Neither crossing casts
a source record address to a C++ adapter reference. Native declaration/argument
composition remains shared with ordinary and nested native types; the crossing
semantics remain explicit where those representations differ.

Preparation results report authorized source imports used by the generated native
artifacts, including transitive requirements. Private-result acceptance checks exact
selection, type/profile/layout/ABI, import authorization, operation coverage and
artifact integrity. Unsupported native requirements fail before publication.

Checkpoint: generated family artifacts and adapter imports pass isolated acceptance
and rejection checks through the production boundary. Source behavior is not
implemented in a second C++ record declaration.

### 4. Compiler import, emission and final link closure — implemented

Bind accepted family storage and callables through the common canonical type model.
Source argument/result types remain source record definitions. The adapter describes
physical crossings; ordinary body checking still sees the existing semantic borrow
and owned-result contracts.

Select required complete source operations/export entries as emission roots. Reuse
the existing lifecycle emitter and its dependency tracking. Final closure acceptance
matches every declared source import to exactly one current compiler definition,
including identity, contract provenance, effects and physical ABI. Then perform the
actual native link with the accepted project and runtime modules.

Keep project artifacts separate from shared runtime packages. Shared/project scope
follows all argument dependencies, including nested family arguments. Preserve one
stable output slot per exact specialization/context and existing reader protection.
A body-only edit reuses compatible native artifacts and relinks compiler definitions.
No repeated-increment, rollback or actual-threading requirement is added.

Checkpoint: both source shapes compile and execute copy-in/out, assignment and cleanup;
observed allocations balance. O0/O1 and ThinLTO use the same imports. One incremental
attempt changes the output while preserving previous snapshots and native artifacts.

## Validation and risk

Run focused tests after each gate; run the full suite once after consolidation,
with the existing ten-worker runner. Re-run broader checks only for new changes or
failures. This migration has higher validation cost than the preceding native-only
argument extension: ordering, storage, lifecycle exports and linking all participate.

Main rejection proofs: unavailable capability; distinct types with equal layout;
missing, duplicate or unauthorized source imports; wrong ABI/layout/target/profile;
stale selection/lineage/contracts; invalid source-payload/native-object crossing;
and a by-value or unsupported incomplete-type dependency cycle. Fixed workers read
unchanged inputs and return private results; joins must accept out-of-order completion
without accepting stale work.

The highest implementation risk is readiness ordering. Gate 1 isolates it before
introducing native payload adaptation. The highest correctness risk is double or
missing lifecycle ownership. Explicit crossings and import closure address it; native
link success alone is insufficient. This is an execution-correctness proof, not an
execution-performance equivalence claim.

## Gate 1 implementation checkpoint

`Layout_Coordinator` in `prepare_backend` owns one update's accepted layouts. The
compiler passes it through type resolution and final backend preparation. After a
ready record batch joins, its physical subset is measured and joined before the
existing queue publishes readiness. Final preparation selects remaining roots and
reuses the exact early results. No artificial, unused queue category is added;
source-export prerequisites belong to the later gates.

`Layout_Input` contains only reachable storage dependencies, with shared immutable
`layout_dependency` nodes. Workers retain no mutable Type_Store. Accepted layouts
retain their type lineage and reachable dependency graph; nested changes invalidate
reuse even when the root definition is unchanged. Nodes are shared across batches,
and the transient root index/coordinator are not retained in compiler snapshots.

The [focused layout proof](../../tests/05_generate_code/prepare_backend/layout_preparation.php)
checks store growth after selection, out-of-order joins, stale target/definition/
lineage rejection, nested dependency invalidation, subset completion, shared nodes
and one retained layout attempt. Existing source-record, array and managed-lifecycle
integration proofs cover source-to-native execution and body-only increments.
This checkpoint introduces no source argument adapter or project-module support.

Validation: 104/104 compiler fixtures passed with ten workers in 123.6 seconds.
Focused source-record, array, managed-lifecycle and join checks passed before the
single full run. PHP syntax, brace/doc-comment, whitespace and documentation file-link
checks passed. Standalone runtime preparation was unchanged and was not rerun.

## Gate 2 implementation checkpoint

The selected export boundary is implemented independently of native-family demand
routing. A coordinator supplies `compile\native_project` (explicit project key,
source root and configurable native output root), accepted compiler outputs and
concrete record roots. No current Compiler_Session option exports every declaration,
and this gate does not publish files or claim source arguments already work in native
families. Gates 3–4 will wire the boundary into family demand and final linking.

`resolve_types\Source_Identities` projects exact source module/declaration keys and
ordered, tagged type/integer arguments. Native and language origins use their existing
owners. Runtime aliases of language primitives preserve the language identity. Nested
arguments embed structured components rather than repeatedly escaping encoded strings.
Local IDs and absolute roots do not enter portable keys. The projection service is
batch-local; export tasks retain only keys and accepted semantic/physical records.

`Lifecycle_Composition::complete_operation()` reuses existing complete plans. For
plain records it materializes the already-permitted primitive field behavior through
the same composition algorithm; cleanup-free destruction has a justified empty plan.
It does not change the record's language permissions. Custom lifecycle bodies anywhere
in the reachable field graph reject export. Missing field support remains unsupported;
the current model does not distinguish semantic deletion from an unexposed provider
operation, so absence is never mislabeled `forbidden`.

`prepare_backend\Source_Export_Preparation` captures current layout/dependency
provenance, selects before ABI work, and prepares one private result per demanded type.
Every result has all six roles. Default/copy construction, copy assignment and cleanup
are available only with complete operations. Move construction/assignment are explicitly
unsupported in this profile. Role semantics state object pre/poststates, source access,
aliasing, call-scoped payload addresses and fatal/no-unwind failure. They do not invent
physical alias or readonly attributes.

Available operations associate a reversibly named external pointer ABI with the exact
current complete implementation. `Source_Export_Join` accepts selected results in any
order, checks coverage/ABI/provenance, shares unchanged outputs and excludes removed
demands. Backend layouts retain ordered fields, offsets, size/alignment (size is the
array stride) and the shared dependency graph; each dependency has its portable type
identity. No worker/result retains an AST, symbol store or mutable canonical store.

The [focused export proof](../../tests/05_generate_code/prepare_backend/source_exports.php)
starts with real managed-field source compilation, then consumes its accepted outputs
through this boundary. It covers same-layout nominal separation, nested and multi-argument
source templates, field-composition reuse, empty cleanup, rejected custom-field bodies,
private/reversed joins, stale ABI/target/lineage, one body increment, and a relocated
fresh build with changed local allocation order. Stable external symbols survive while
internal implementation associations change. This is contract preparation and rebinding;
export entry emission and native specialization reuse require gates 3–4.

Validation: the full ten-worker run passed 104/105 fixtures in 126.9 seconds. Its
only failure was a missing interface-inventory entry for `Source_Export_Join`.
After adding that proof, both the inventory and source-export fixtures passed their
focused rerun. Existing automatic/custom lifecycle focused proofs also passed.
All prototype PHP syntax and changed-file formatting/comment/link checks passed.
No standalone preparation rerun was needed; native preparation code was unchanged
in this gate. All 105 fixtures are verified across the full run and focused recheck.

## Gate 3 implementation checkpoint

The [project preparation owner](../../src-runtime-preparation/project/calls.md)
now consumes gate-2 exports through `Source_Adapter`. It projects typed portable source,
import and module records, including field/lifecycle provenance for invalidation.
Native tasks retain no compiler stores, syntax, numeric IDs or internal operation
links. Exact project/source identities remain independent of content checksums.

Family metadata authorizes `source_profiles` per formal slot, `source_payload:
"copy_in"` on borrowed formal parameters, and `source_payload_result: "copy_out"` on
owned formal results. These native permissions supplement existing generic semantic
requirements; favorable concrete types grant no extra source-template permissions.
The existing argument normalization, declaration composition, selection, private worker,
join, reservation and allocated stable-slot publication serve these project requests.

`Adapter` generates a distinct aligned-byte payload class whose special members call
compiler imports. It checks size, alignment, stride and zero payload offset through
Clang assertions. Unavailable special members are deleted; moves cannot silently use
copies. Copy-in creates a live native adapter from a borrowed source payload. Copy-out
calls the compiler copy operation into uninitialized source caller storage, then native
scope cleanup destroys the temporary. Source addresses are never native adapter
references. There is no C++ duplicate of source field lifecycle implementation.

`Runtime_Preparation` distinguishes self-contained runtime packages from project
modules. Ordinary packages retain their native no-undefined link check. Project modules
instead carry authenticated version-one `project.json` contracts and the actual required
compiler imports per `.ll`, ordinary bitcode, full-LTO bitcode and ThinLTO bitcode.
Generation and acceptance verify exact source identities, target, declaration ABI and
absence of local source definitions for every variant. Unauthorized source symbols
reject. Project modules explicitly do not claim native link closure; standard-library
and other native references are also checked by the eventual final link. No placeholder
source function satisfies a preparation check.

Ordinary package import rejects project modules. Ordinary native descriptions cannot
carry source obligations; this gate does not publish source-dependent family results
as ordinary reusable native descriptions. Gate 4 must supply the explicit compiler
project-result adapter, demand routing, source export entries and final link acceptance.
The current gate is an explicit production preparation boundary, not end-to-end
`vector<MySourceStruct>` compiler support.

The [project proof](../../tests/integration/source_project_preparation.php)
uses real compiler exports for a managed source record and a nested source record.
It checks native family construction, copy/assignment, copy-in/out artifacts, authorized
imports across all variants, wrong ABI/unknown symbol rejection, profile/crossing/project
authorization, ordinary importer rejection, private reversed joins and native artifact
reuse after one source body increment. Source execution remains covered by the shared
compiler fixture; actual execution linked through project imports is gate 4's proof.

Validation: 106/106 compiler fixtures passed with ten workers in 137.3 seconds;
standalone preparation passed 170 ordinary and 54 family checks. Focused project,
source-export and ordinary managed-family proofs passed before the final run.
PHP syntax, brace/doc-comment, whitespace and documentation file-link checks passed.
No project-module execution or execution-performance claim is made at this gate.

## Gate 4 implementation checkpoint

`Compiler_Session` accepts an optional explicit `native_project` with the portable
project namespace, source root and configurable project-native output root.
`Source_Export_Coordinator` prepares demanded source arguments at the existing ready
concrete frontier. It reuses the layout owner, fixed export workers and export join;
retained family results carry export associations across the one supported increment.
Automatically composed managed, nested and cleanup-free records use this same path.

`Compiler_Bridge` gathers direct and transitive source obligations before selecting
native work. Project scope and output follow those obligations, including a native
family nested inside another native family. Project native descriptions have an
explicit reader requiring current source contracts; ordinary native descriptions
still reject source dependencies. Neither semantic type names nor concrete family
names select the implementation.

The common `Package_Adapter` accepts project modules only with an explicit typed
`project_binding`. `Project_Import` checks the authenticated receipt against source
identity, layout, capabilities, ABI and effects. Payload rows bind the existing
source record definition, including call-scoped const borrowing and owned results.
They never allocate a second opaque definition or acquire another lifecycle owner.
`Family_Operations` resolves dependent result production from the accepted argument
itself, equally for source and native types. Final reservations revalidate project
contexts along with ordinary packages and hold readers through linking.

Backend `Project_Exports` prepares one fixed `source_linkage` per phase. Required
source imports are retained as normalized package contracts by the adapter; the
backend checks current source layouts against one batch dependency graph. Imports
add complete operation plans to the existing lifecycle emission roots,
including primitive copies and empty cleanup that ordinary source calls need not
materialize. The normal lifecycle ABI workers, join and emitter handle those plans.
The selected entry-module worker emits stable forwarding entry points; it contains
no second field-composition algorithm. `Module_Join` requires exactly one compatible
emitted definition per source import before the common native builder links all
accepted source, project and runtime modules.

The [execution proof](../../tests/integration/source_family_execution.php)
compiles copy-in/out, container copy/assignment and cleanup for a managed record,
a nested source record, a cleanup-free record and a nested native family. Instrumented
native field allocations balance after source and container cleanup. One body edit
changes execution while preserving old snapshots and native artifact bytes/mtimes.
The same source exports execute at O0, O1 and ThinLTO. Missing/duplicate final exports,
incompatible import ABI and ordinary-reader acceptance of project modules reject.
These are correctness and reuse proofs, not execution-performance equivalence claims.

Full-suite validation is recorded in the foundation tracker's current-status section.
The [gates 1–4 consolidation review](source_family_consolidation.md) records the
boundary cleanup and the retained execution, scheduling and incremental guarantees.

## Approval boundary and smaller options

The user approved the coordinated migration beginning with gate 1 and continued
through gates 2–3, then authorized gate 4 after reviewing its boundary and committing
the preparation work. The bounded compiler integration is now implemented.
The initial plan alone did not authorize implementation; subsequent confirmations
authorized the coordinated migration. Custom source lifecycle exports were separately approved and are recorded in the
[follow-up slice](custom_source_exports.md); the other explicit non-goals remain deferred.

Gate-4 validation: all 107 compiler fixtures passed with ten workers in 151.2 seconds;
standalone preparation passed 170 ordinary and 54 family checks. The focused execution
proof passed before the full run and after the final receipt-lock ordering adjustment. PHP syntax, changed-file braces/doc-comments,
whitespace and documentation links passed. Implementation remains bounded by the
explicit non-goals above.
