# Compiler step entry points
Doc Status: supporting

One instance executes one phase. Constructors capture arguments only.

```text
created --init()--> ready --run()--> processed --finalize()--> finished
                           running while run() is active
init/run/finalize operation failure -> failed (terminal)
```

Wrong-time calls throw LogicException without changing status. status() and
supports_run() always work. result() and optional store() require finished status
and return the same concrete object on repeated calls. All current phase owners
implement Step and Runnable_Step. Store_Providing_Step is used only for an actual
store; markers do not change concrete result structures.

The ordered list below follows Compiler_Session and Phases. Input selection may
skip semantic work for valid published input. Unsupported increments repeat the
frontend sequence with full selection; all tasks still use the common path.
Type_Cache validity is queried during input preparation; candidate cache preparation
occurs immediately before type resolution.

| Order | Entry file / owner | Result (and store when present) |
|---|---|---|
| 1 | [main_read_manifest.php](../01_prepare_inputs/read_manifest/main_read_manifest.php) / Manifest_Reader | Project_Manifest |
| 2 | [main_load_runtime.php](../01_prepare_inputs/load_runtime/main_load_runtime.php) / Language_Types | Type_Catalog |
| 3 | [main_discover_sources.php](../01_prepare_inputs/read_sources/main_discover_sources.php) / Source_Discovery | Source_Set |
| 4 | [main_read_sources.php](../01_prepare_inputs/read_sources/main_read_sources.php) / Source_Reader | Source_Set |
| 5 | [main_tokenize.php](../02_tokenize/main_tokenize.php) / Tokenizer | Token_Set |
| 6 | [main_parse.php](../03_parse/main_parse.php) / Parser | Frontend_Set |
| 7 | [main_collect_symbols.php](../04_analyze/collect_symbols/main_collect_symbols.php) / Declaration_Collector | Symbol_Refresh / Symbol_Store |
| 8 | [main_prepare_entry.php](../04_analyze/resolve_types/main_prepare_entry.php) / Entry_Resolver | entry_contract |
| 9 | [main_resolve_symbols.php](../04_analyze/resolve_symbols/main_resolve_symbols.php) / Symbol_Resolver | Resolution_Set |
| 10 | [main_compare_symbols.php](../04_analyze/collect_symbols/main_compare_symbols.php) / Symbol_Comparer | Symbol_Refresh / Symbol_Store |
| 11 | [main_resolve_types.php](../04_analyze/resolve_types/main_resolve_types.php) / Type_Resolver | Type_Resolution / Type_Store |
| 12 | [main_check_bodies.php](../04_analyze/check_bodies/main_check_bodies.php) / Body_Checker | Body_Set |
| 13 | [main_analyze_lifetimes.php](../04_analyze/analyze_lifetimes/main_analyze_lifetimes.php) / Lifetime_Analyzer | Lifetime_Set |
| 14 | [main_prepare_backend.php](../05_generate_code/prepare_backend/main_prepare_backend.php) / LLVM_Backend | Backend_Context |
| 15 | [main_lower.php](../05_generate_code/lower/main_lower.php) / Lowerer | Lowered_Set |
| 16 | [main_native_entry.php](../05_generate_code/lower/main_native_entry.php) / Native_Entry | native_entry_plan |
| 17 | [main_emit_llvm.php](../05_generate_code/emit_llvm/main_emit_llvm.php) / LLVM_Emitter | Emitted_Function_Set |
| 18 | [main_assemble_modules.php](../05_generate_code/emit_llvm/main_assemble_modules.php) / Module_Assembler | Emitted_Program |
| 19 | [main_build_native.php](../06_build_output/build_native/main_build_native.php) / Native_Builder | Native_Candidate |

Type_Resolver runs the nested [Concrete_Preparation](../04_analyze/resolve_types/main_prepare_concrete.php)
lifecycle after selecting ordinary records and before concrete signature/local batches.
It owns shared record/application/member readiness, including ordinary method
declarations. Workers read a fixed private registry per batch; joins accept results
between batches. One retained Instance_Set is published at completion.
Its initialization runs the nested [Template_Checker](../04_analyze/check_templates/main_check_templates.php)
lifecycle before concrete demand work. That phase owns fixed definition tasks,
private permission results and `Template_Join`; accepted `Template_Set` results
are retained through `Instance_Set`. There are 21 current phase owners including
these two nested preparation phases.

Source discovery accepts directory batches during run() to discover child work;
finalize() establishes removals and entry membership. Native building joins objects
before linking during run(); finalize() exposes an unpublished candidate. The session
owns the separate publication transaction and cleanup policy.

Signature/local task helpers, worker classes, joins, stores, utilities,
Compiler_Session and LLVM_Toolchain do not receive artificial one-update lifecycles.
Actual threading and a native port remain deferred. Method and folder maps remain
shallow navigation aids.

See [coordinator calls](calls.md), [pipeline contracts](../../docs/compiler_pipeline.md),
and [coding style](../../docs/code_formatting.md#step-lifecycle).
