# Performance and memory watchlist
Doc Status: supporting

Status: initial scalability measurements recorded; optimization remains deferred.
The [incremental investigation](incremental_native_investigation.md) separates
PHP cycle-collector time from lowering work and proposes file-based native units;
file-based units and retained objects are now implemented. A subsequent
20-process Clang queue reduced the measured 5 MiB cold time; PHP semantic workers
and GC policy remain unchanged.
The [first scalability run](scalability_first_run.md)
records that all sizes through 10 MiB compiled, with real
unchanged reuse, file-wide sibling replacement after a body edit, and native
work dominating the instrumented 1/5/10 MiB increments. Separate 5/10 MiB tool
measurements attribute most native time to object compilation, with linking
comparatively small. At 5/10 MiB, unchanged baseline builds
took about 1.10/1.77 seconds despite full body/artifact reuse. The
[subsequent unchanged gate and Mold comparison](native_dispatch_investigation.md#linker-selection-and-unchanged-reuse)
now measure 0.024 s for an unchanged 5 MiB request: semantic traversals are
skipped, native integrity checks remain. The updated 10 MiB case has not been
remeasured. Body edits still pay project-wide validation/joins and variable PHP
GC; native publication/bookkeeping is small. GC policy remains unchanged.
The [organization principle](../code_organization.md#performance-and-memory)
applies to new work now. These findings do not authorize a refactor or change
the supported incremental rules.

The [addition/control-flow cost check](operations_and_control_flow.md#focused-php-cost-check)
records the stronger contracts' overhead on the existing 1 MiB input: checking,
lifetimes and lowering together measured 706 ms before and 888 ms after, with
2.85 MiB additional post-compile PHP memory. This is a three-process diagnostic
per version, not end-to-end timing or attribution to a particular algorithm.
Branch-heavy dataflow scaling and further optimization remain unmeasured.

LLVM file assembly now has independent selected tasks after function emission;
joins validate/retain results without building text. This establishes a future
multi-CPU work boundary, not a measured parallel speedup. Execution stays serial.
The remaining project-wide scans/joins, sibling-function invalidation and
directory-task granularity remain optimization opportunities. Added declarations
and other unsupported changes still select the agreed full fallback.

| Finding | Current work and consequence | Possible later direction |
|---|---|---|
| Repeated name lookup on unchanged code during an edit | [Name-resolution selection and join](../../src/04_analyze/resolve_symbols/main_resolve_symbols.php) both revisit retained call bindings, extract their spelling and look them up again. Repeated calls to the same function repeat the lookup. Entirely unchanged published inputs now skip this stage. | Collection could expose relevant declaration changes; resolution could retain unique lookup dependencies and validate them once. Best first optimization candidate during consolidation because ownership is relatively contained. |
| One edited function refreshes its file's other functions | Replacing a file AST invalidates [name bindings](../../src/04_analyze/resolve_symbols/main_resolve_symbols.php) and [signature associations](../../src/04_analyze/resolve_types/signatures.php) for unchanged functions in that file. This cascades into body checking, lifetime analysis and lowering. Logical comparison identifies unchanged bodies but does not preserve their semantic results. | Carefully separate reusable callable meaning from source-version anchors. Simply keeping old AST references would be incorrect. Defer this broader model change. |
| Full fallback repeats frontend work | When incremental admission rejects a change, the [session loop](../../src/compile/compile.php) repeats reading, tokenization, parsing and resolution with full selection. Files processed in the first attempt are processed again. | Deliberate conservative behavior today. Later assess whether already prepared frontend results can remain valid across escalation, without weakening the full-rebuild contract. |
| Native compilation granularity | [Native building](../../src/06_build_output/build_native/main_build_native.php) compiles one object per changed source file and retains unchanged objects. A bounded external queue now permits 20 jobs. Linking still visits all objects and each compiled file pays startup cost. | The [dispatch investigation](native_dispatch_investigation.md) found near-full guest CPU usage without wrappers and no disk-wait bottleneck. Clang 18 multi-file batches still launch per-file compiler children and showed no gain. Retain 20 jobs; assess true reusable backend execution only as a separate slice. |

Struct-specific measurement targets are also deferred: exact field-name lookup
currently scans the record's ordered fields, and layout preparation launches one
private Clang probe per selected plain record. Records containing opaque fields
now need a native alignment probe and an LLVM primitive-compatibility probe. Both preserve shared facts and body-edit
reuse. Measure wider records and larger declaration sets before adding indexes
or batching; neither change is needed for the bounded plain-record proof.

Memory consideration: the resident compiler retains source, tokens, AST,
semantic records, lowered instructions and LLVM text. Previous and candidate
versions overlap during updates. Shared references already avoid copying whole
upstream datasets; preserve that sharing when preparing consumer data. PHP's
object-per-record allocation remains an accepted prototype compromise.

Track these while continuing compiler capabilities. Avoid new duplication in
touched code; defer performance refactoring until its benefit and validation
scope are clear. Short structural accessors are a lower priority than the
findings above.

## Focused execution-performance proof for imported combinations

Status: the [isolated source-record/vector proof](source_specialization_proof.md)
has run. Correctness and allocation parity passed. The user accepted the measured
20–31% small-vector lifecycle overhead at `-O1`, including LTO cases, for the fast
development compiler; reducing that gap is not a prerequisite for integration.
Ownership-wrapper measurements remain pending. This acceptance concerns the tested
boundary, not an unlimited allowance for future regressions.
This measures the generated program, separately from compiler speed/memory.
Apply it to the first source/imported-type integration proof and representative
ownership-wrapper operations when those families enter scope. It does not require
implementing all families before proceeding.

Compare prepared-ABI use of a source-type specialization with equivalent direct
C++ using the same Simple C++ runtime, concrete type, target and toolchain. Match
optimization and LTO settings between paths. Cover `-O0` and the intended `-O1`
path; report LTO/ThinLTO cases separately, without assuming bridge elimination.

Use a bounded workload with an observable checked result. Inspect generated code
and measure repeated execution, allocations and copy/move/ownership operations.
Separate the runtime type's inherent costs from added bridge calls, unnecessary
copies/refcount changes, allocation/indirection and lost optimization opportunities.
Include frequent small accesses as well as construction/cleanup so allocation cost
does not hide access overhead. Instrument operation counts separately where the
instrumentation could distort timing.

Record configuration, workload, measurements and limits before claiming negligible
overhead. Discuss any material regression before accepting the integration design;
no numerical threshold or performance-parity result has been agreed yet. Preserve
inline storage for `value_p<T>` and avoid extra wrapper allocations merely because
a type is imported. See the [type ownership direction](clang_lifecycle_composition.md#type-ownership-and-imported-combinations)
and [existing LTO evidence](llvm_lto_readiness.md).
