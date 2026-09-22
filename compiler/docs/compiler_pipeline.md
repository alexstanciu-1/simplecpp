# Compiler pipeline
Doc Status: supporting

This document owns the processing and rebuild model. The process table describes
the current [PHP prototype](../README.md); deferred capabilities are
identified explicitly. The [organization note](code_organization.md) assigns
code ownership; [incremental rules](details/incremental_refresh_rules.md) define
eligibility in detail. The original PHP++ skeleton remains a historical reference.

## Resident execution

The compiler is a long-running in-memory process. Start it once, retain project
sessions and stage results, and process successive changes in that same process.
The prototype retains state across explicit `compile()` calls; an automatic
watch/request loop is not implemented yet. Each update is a bounded
transaction over a consistent input snapshot, followed by publication of results.

Each stage refreshes its retained outputs from changes in the inputs it actually
depends on, which can come from several earlier stages. The coordinator schedules
affected work; stages do not launch each other through recursive callbacks.
Changes include additions, modifications, removals, and invalidated results.
Propagate output changes to dependents; suppress propagation only when an
established comparison proves the facts relevant to those dependents unchanged.

Introduce dependency/update rules gradually. A rule must identify its inputs,
affected outputs, and how obsolete results and diagnostics are replaced. If a
rule is missing or uncertain, perform a fresh build. Also use a fresh build
when the change volume exceeds an explicit rebuild policy. Start conservatively;
introduce finer reuse and measured thresholds through later verified slices.

A full (fresh) build is an incremental build with all current work selected.
One flag in the current update context overrides individual work selection:

```text
foreach element in current_stage_inputs:
    if update.full_rebuild || element.needs_recompile:
        process(element)
```

These are the same loops and processing functions in both cases. No sweep is
needed to flag every record, and recomputing a symbol need not change its
identity. The initial build sets `full_rebuild`; the flag belongs to one update,
not the lifetime of the resident process. Removed contributions are reconciled
separately so old symbols, index entries, diagnostics, and outputs disappear.

The coordinator may set the flag when a phase discovers an unsupported change.
Revisit any earlier phase that skipped required work through its normal loop.
The flag stays fixed within a phase and can only escalate to full for that
update. The parse/resolve gate must establish supported downstream impact before
selective lowering; later discoveries still use the same fallback before
publication. Tasks do not mutate the flag. Never publish stale dependent
results alongside refreshed results.

## Six-item refresh recap

- Read/validate the manifest; any content change sets `full_rebuild`.
- Establish the current participating files and reconcile removals.
- Fully read/tokenize selected files: added/changed files, or all on full rebuild.
- Fully parse selected files into replacement syntax.
- Reconcile project symbols/indexes, compare declarations/bodies, and resolve
  selected work against the current declarations.
- An incrementally unsupported change sets `full_rebuild`; revisit skipped
  work as needed, then continue through the same semantic/backend stages.

This is the original six-item recap. It spans several processes in the table
below; it is not a separate six-stage compiler path.

The frontend has two ownership levels: per-file syntax snapshots, then a
separate project semantic model of symbols and resolved references. Symbol and
body change summaries belong to the project stage; the parser replaces whole
file ASTs. Source origins remain attached to project records. Collection builds
the candidate project index before selected references are resolved against it.
See [ownership and root details](details/incremental_refresh_rules.md#two-ownership-levels).

The project stage catalogs changes separately from rebuild decisions. Track an
element's own change, a child-change summary, and individual child changes where
useful; a changed child does not itself prescribe rebuilding the parent.
[Catalog rules](details/incremental_refresh_rules.md#catalog-changes-separately-from-reactions)
define this design independently of gradually supported reactions.

## Initial change rules

| Input/change | Initial action |
|---|---|
| Initial update or manifest path/content change | Full project rebuild, including configuration and file discovery. |
| Other configuration, provider metadata, or dependency change | Full rebuild until an explicit rule supports it. |
| Backend target/toolchain configuration change | Refresh backend contracts; a changed backend context invalidates lowering/emission and native output without redoing unchanged language semantics. |
| Linker selection/executable change | Relink current objects under the new link contract; preserve semantic, LLVM and object results. |
| No source/manifest/catalog/backend change after publication | Share accepted semantic/LLVM stage results without traversing them; still validate native artifacts and repair missing/tampered outputs through the common path. |
| Added or changed source file | Read, tokenize, and parse the whole file; compare previous and new syntax/declarations. |
| Removed source file | Invalidate its contributions and dependents; full rebuild until removal propagation is supported. |
| Callable body changed, definition unchanged | Supported for the current subset: refresh its file AST bindings and affected semantic/lowered results, reuse valid unchanged functions, and rebuild native output when needed. |
| Function renamed or moved to another namespace | Delete the old declaration and add the new one; full rebuild under the initial rules. |
| File moved | Observed as removal plus addition; all rename detection is deferred. |
| Unchanged source file | Retain tokens/syntax unless `full_rebuild` is set; semantic results may still be affected by other inputs. |
| Unsupported change | Full rebuild in the resident process. A change-volume threshold remains future policy. |

After parsing and affected resolution, decide whether all downstream effects
have supported refresh rules before selective lowering. An unsupported change
can select a full rebuild earlier. The current verified category is executable-body changes under unchanged
callable definitions (including the selected file entry). Other changes select a full
rebuild until explicitly supported; a general incremental dependency resolver
is not required first. See [refresh rules](details/incremental_refresh_rules.md).

## Shared processing responsibilities

The table describes the **current PHP prototype**, checked against
[`Compiler_Session::compile()`](../src/compile/compile.php) and
[`Phases`](../src/compile/phases.php). Folder links point into
`src/` within the [numbered groups](../README.md); process
classes appear beside each folder and link to their
implementation. Several processes can share one folder. `Phases` wires stage
selection, worker execution and coordinator joins.

**After** lists the processes whose completed results are prerequisites. Earlier
transitive prerequisites also apply. A retained result counts as complete only
when valid for this update; selected work must finish its coordinator join.
These dependencies describe readiness, not a promise of concurrent execution.
The driver is serial and sometimes waits for unrelated earlier work too—for
example, final backend preparation runs after lifetime analysis. Backend-owned
layout preparation also serves ready record batches during concrete type resolution;
final preparation reuses those exact accepted facts and completes remaining roots.

| Process | Folder / process class | After | Responsibility |
|---|---|---|---|
| Begin update | [compile](../src/compile) — [Compiler_Session](../src/compile/compile.php), [Project_Lock](../src/compile/lock.php) | Compile request on a session | Reserve the project, prevent concurrent compilation, and select the published/observed baseline. |
| Read manifest | [read_manifest](../src/01_prepare_inputs/read_manifest) — [Manifest_Reader](../src/01_prepare_inputs/read_manifest/main_read_manifest.php) | Begin update | Read and validate source folders and selected entry; detect manifest changes. |
| Load language catalog | [load_runtime](../src/01_prepare_inputs/load_runtime) — [Language_Types](../src/01_prepare_inputs/load_runtime/main_load_runtime.php) | Begin update | Read/reuse authoritative named scalar definitions, literal/entry defaults and lifetime contracts. Runtime ABI and operation imports remain deferred. |
| Discover sources | [read_sources](../src/01_prepare_inputs/read_sources) — [Source_Discovery](../src/01_prepare_inputs/read_sources/main_discover_sources.php) | Read manifest | Scan configured folders recursively; reconcile one project file dataset using paths, mtime and size, including removals. |
| Prepare refresh decision | [compile](../src/compile) — [Compiler_Session](../src/compile/compile.php), [Input_Selection](../src/compile/inputs.php)<br/>[resolve_types](../src/04_analyze/resolve_types) — [Type_Cache](../src/04_analyze/resolve_types/utilities/type_cache.php) | Read manifest; Load language catalog; Discover sources | Establish `Update_Context` and query type-cache validity before frontend selection; the session requests full work for initial/configuration/provider changes and removals. |
| Read source snapshots | [read_sources](../src/01_prepare_inputs/read_sources) — [Source_Reader](../src/01_prepare_inputs/read_sources/main_read_sources.php) | Prepare refresh decision | Select files, read and validate exact bytes against scan facts, then join immutable source buffers. |
| Tokenize | [tokenize](../src/02_tokenize) — [Tokenizer](../src/02_tokenize/main_tokenize.php) | Read source snapshots | Tokenize selected files in full; retain source spans and join token buffers. |
| Parse | [parse](../src/03_parse) — [Parser](../src/03_parse/main_parse.php), [File_Parser](../src/03_parse/parse_file.php), [Frontend_Join](../src/03_parse/join.php) | Tokenize | Build flat per-file ASTs with definitions and one entry block; preserve locals, blocks, typed parameters and ordered call arguments. Join replacement frontends. |
| Collect project symbols | [collect_symbols](../src/04_analyze/collect_symbols) — [Declaration_Collector](../src/04_analyze/collect_symbols/main_collect_symbols.php) | Parse | Extract function/file-entry declarations, match project identities, diagnose duplicates and reconcile the candidate index, including removals. |
| Select language entry | [resolve_types](../src/04_analyze/resolve_types) — [Entry_Resolver](../src/04_analyze/resolve_types/main_prepare_entry.php) | Collect project symbols; Load language catalog | Bind the manifest-selected file body to its language entry contract; reject executable top-level code elsewhere until initializer ordering is supported. |
| Resolve names and scopes | [resolve_symbols](../src/04_analyze/resolve_symbols) — [Symbol_Resolver](../src/04_analyze/resolve_symbols/main_resolve_symbols.php), [Resolution_Worker](../src/04_analyze/resolve_symbols/body.php) | Collect project symbols | Bind source/provider declaration references, template parameter scopes, annotations, constants, project calls and runtime locals against fixed symbols/catalog; retain the AST. Argument typing and concrete instantiation are separate. See [binding contracts](details/template_bindings.md). |
| Compare symbols and admit increment | [collect_symbols](../src/04_analyze/collect_symbols) — [Symbol_Comparer](../src/04_analyze/collect_symbols/main_compare_symbols.php)<br/>[compile](../src/compile) — [Input_Selection](../src/compile/inputs.php) | Resolve names and scopes | Compare previous/current definitions and bodies, then apply supported update rules. Unsupported changes set `full_rebuild` and revisit frontend work through the same loop. |
| Resolve declared types | [resolve_types](../src/04_analyze/resolve_types) — [Type_Resolver](../src/04_analyze/resolve_types/main_resolve_types.php), [Signature_Resolver](../src/04_analyze/resolve_types/signatures.php), [Local_Type_Resolver](../src/04_analyze/resolve_types/locals.php) | Compare symbols and admit increment; Select language entry | Prepare the private type cache using the admitted selection; follow accepted return/parameter/body-local annotation bindings and literal defaults to shared type IDs. Signature parameter IDs supply the local binding prefix; both joins finish before the snapshot is returned. |
| Check and build typed bodies | [check_bodies](../src/04_analyze/check_bodies) — [Body_Checker](../src/04_analyze/check_bodies/main_check_bodies.php), [Body_Worker](../src/04_analyze/check_bodies/body.php) | Resolve declared types | Check literals, calls, local reads/writes, returns and supported conversions; establish initialized entry parameters and checked left-to-right argument ranges; produce typed statements, values (including unary conversions), calls and scope ranges. |
| Analyze lifetimes | [analyze_lifetimes](../src/04_analyze/analyze_lifetimes) — [Lifetime_Analyzer](../src/04_analyze/analyze_lifetimes/main_analyze_lifetimes.php), [Lifetime_Worker](../src/04_analyze/analyze_lifetimes/body.php) | Check and build typed bodies | Establish the reachable prefix, scalar copying, local initialization and scope/return exits using explicit lifetime contracts. Incoming parameters start at entry; temporaries remain live until their consuming call, conversion or statement boundary. |
| Prepare backend contracts | [lower](../src/05_generate_code/lower) — [LLVM_Backend](../src/05_generate_code/prepare_backend/main_prepare_backend.php), [LLVM_Toolchain](../src/05_generate_code/prepare_backend/tools/toolchain.php) | Resolve declared types | Verify toolchain/target primitives and prepare shared callable linkage, ordered scalar parameter and calling-convention contracts. Backend changes invalidate dependent lowering. |
| Lower bodies | [lower](../src/05_generate_code/lower) — [Lowerer](../src/05_generate_code/lower/main_lower.php), [Lowering_Worker](../src/05_generate_code/lower/body.php) | Analyze lifetimes; Prepare backend contracts | Build selected callable plans: values, parameter initialization/local slots, ordered call operands, constants, conversions, loads/stores and block return terminators. |
| Prepare native entry | [lower](../src/05_generate_code/lower) — [Native_Entry](../src/05_generate_code/lower/main_native_entry.php), [LLVM_Toolchain](../src/05_generate_code/prepare_backend/tools/toolchain.php) | Prepare backend contracts | Probe the hosted entry ABI and prepare adaptation from the language entry result to the native return type. |
| Emit LLVM | [emit_llvm](../src/05_generate_code/emit_llvm) — [LLVM_Emitter](../src/05_generate_code/emit_llvm/main_emit_llvm.php), [Module_Assembler](../src/05_generate_code/emit_llvm/main_assemble_modules.php) | Lower bodies; Prepare native entry | Select/emit/join function IR, then select/assemble/join independent file modules with required external declarations, target attributes and native entry adaptation. Joins validate and retain results; workers build text. |
| Build or reuse native artifact | [build_native](../src/06_build_output/build_native) — [Native_Builder](../src/06_build_output/build_native/main_build_native.php)<br/>[lower](../src/05_generate_code/lower) — [LLVM_Toolchain](../src/05_generate_code/prepare_backend/tools/toolchain.php) | Emit LLVM; native output requested | Validate the destination and artifact reuse. Select stale/missing file objects, compile them with the configured bounded Clang process queue and retain unchanged objects; link a staged executable and fingerprint it. |
| Accept update results | [compile](../src/compile) — [Compiler_Session](../src/compile/compile.php)<br/>[build_native](../src/06_build_output/build_native) — [Native_Candidate](../src/06_build_output/build_native/result.php) | Emit LLVM for inspection; Build or reuse native artifact for native output | Inspection adopts `observed`. Native requests ask the source owner for an acknowledged snapshot, publish the artifact and adopt `observed`/`published`. Failures preserve accepted state; release the update's project reservation. |

Selection is part of **every** processing phase, not a single pass after source
reads. `full_rebuild` is fixed while a phase's workers execute. If the incremental
gate escalates it, the coordinator repeats required earlier work before advancing.
Symbol comparison itself needs only collected symbols; the combined admission
boundary follows name resolution so unresolved input cannot pass that gate.

Current native output uses one LLVM module per contributing source file. Selected
file-assembly workers build changed modules; native selection compiles only stale
or missing objects. Full rebuild selects all current work through the same stages. Inspection already includes backend preparation and
LLVM emission; it stops before native object compilation/linking. Running the
resulting executable is a separate action. Physical tombstone cleanup remains a
deferred `Compiler_Session` maintenance boundary, not an implemented final pass.

Explicit source instances and literal integer constants now precede concrete
signature/local work within type resolution; see [instance preparation](details/explicit_instantiation.md).
The broader design still includes general evaluation and provider families, richer
operations and control flow, broader ownership, dependency propagation and
optimization. These extend their responsible processes; existing cleanup, runtime
ABI and module-partitioning proofs remain implemented within their documented subsets.
The [historical responsibility inventory](details/compiler_pipeline_comparison.md#historical-responsibility-ids)
preserves the old comparison's 1–22 mappings; it does not number this table.

## Continuing responsibilities

These are standing design obligations; the process table above identifies the
implemented work. This inventory does not claim every service is complete.

| ID | Owner | Responsibility |
|---|---|---|
| C1 | Session/transaction | Resident state, refresh rules, per-update full-rebuild flag, deletion reconciliation, consistent publication and failure handling. |
| C2 | Scheduler/publication | One/many-worker contracts, immutable phase inputs, non-overlapping writes, explicit joins and deterministic publication of real results. |
| C3 | Storage/identity/caches | Compact owned data, stable identities, indexes, complete cache inputs and bounded retention. |
| C4 | Diagnostics/readiness | Source-anchored errors and explicit acceptance/blockers at each stage. |
| C5 | Queries/debug/exports | Shared compiler facts, preserved source mappings and on-demand readable views. |
| C6 | Measurements | Actual timings, work/reuse, memory and optional instrumentation. |
| C7 | Metadata/native boundary | Provider authority, ABI contracts, runtime symbols and native tool execution. |
| C8 | External tools | Build the compiler, generate catalogs, test, inspect and explicitly run resulting programs. |

The table describes readiness dependencies. Current workers join complete phase
batches; finer overlap is future scheduling work. Body checking and typed-body
construction are already combined. A general dependency queue or fixed-point
framework is not implemented; unsupported propagation selects full rebuild.

One worker and many workers use the same partitions and joins: independent
files for tokenization/parsing, dependency-ready symbol work, and independent
resolved bodies for lowering. Concurrent tasks do not mutate shared growable
tables; safety comes from work partitioning and phase boundaries.
Debug exports observe completed stage facts on request; they do not trigger a
second compilation path or execute the resulting program.

As update rules are added, compare refreshed results with a fresh build of the
same final inputs, including removals and diagnostics. The prototype already proves
body-edit reuse and native failure/repair in one session; added declarations and
other unsupported change categories continue to select full work.

The [historical review](details/compiler_pipeline_review.md)
preserves earlier boundary analysis and future acceptance ideas. The
[old-compiler comparison](details/compiler_pipeline_comparison.md)
records mapping coverage and evidence. Current initial scope and gates live in
the [first-slice plan](details/first_slice.md); historical inventories do not
require implementing every capability before it.


### Composed lifecycle work

Concrete type preparation derives immutable default/copy/destruction plans from
accepted fields; ordinary nested records participate in the existing readiness
queue. Backend-owned Layout_Coordinator accepts fixed ready layout subsets before
record readiness is published; final backend preparation completes layouts and
operation ABIs through the same owners. LLVM emission
selects changed type-operation definitions separately from changed source bodies,
then joins both outputs before module assembly. The entry module owns generated
lifecycle definitions once. An unchanged body-level use shares those contracts;
a contract change uses the existing conservative full-rebuild policy. See
[the bounded composition contract](details/lifecycle_contracts.md#first-compiler-implementation-bounded-composition).

## Definition permissions before specialization

Concrete_Preparation initialization runs `Template_Checker` after name resolution.
One selected task checks each source template definition against its declared
contract, independently of concrete demand. Private results join into `Template_Set`,
retained by `Instance_Set`; exact declaration, binding and catalog dependencies
control reuse. Argument acceptance then checks concrete lifecycle capabilities;
body checking still owns concrete operations. See the
[process map](../src/04_analyze/check_templates/calls.md) and
[bounded scope](details/generic_contract_implementation_plan.md).
