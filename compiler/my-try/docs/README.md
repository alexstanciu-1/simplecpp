# my-try compiler
Doc Status: supporting

Optional [lowering diagnostics](architecture/lowering_report.md) expose artifact
sizes, direct consumers, fragment header contributions and outlining decisions.

PHP is the implementation source. The active v0.2 path is strict PHS/PHP++ → typed
syntax and collection → shared semantic preparation → C++ generation. Other
frontends should normalize toward the same PHS-shaped concepts. LLVM is a parked
experiment retained for regressions, not a second semantic development path.

Current repair checkpoint: [strict curl integration](planning/curl_integration.md),
43/64 original issues closed (67.1875%); supplementary BS-R01 also closed.

V-R03/V-E02 pass default-prelowered cold/warm builds on dev002, with all five
strict fixtures compiled and four local original output oracles verified against
v0.1. External HTTPS execution remains opt-in; wider transport gaps stay explicit.

## Reading map

The completed bounded [runtime module integration plan](planning/runtime_module_integration.md)
covers storage, members, protocols, signatures and build composition. Its
[closeout audit](planning/runtime_module_closeout.md) separates supported foundations
from deferred APIs and residual operation allowlists; extended testing is Chapter 15 debt.

Ordinary runtime type registration now uses the shared module metadata: `types` and
separate `cpp_types` records, including vector/hash. The [catalog contract](../../../specs/runtime_module_metadata.md#ordinary-runtime-type-declarations)
owns the shape; [focused tests](testing.md#reusing-the-runner) prove metadata-only
providers. Specialized semantics and remaining protocol-registration debt stay explicit.

Production shared assets now include JSON documents/nodes/diagnostics, file locks
and opt-in managed processes (six types, twenty calls and nine fields). Selected
module metadata supplies provider linkage through artifact publication to the native
cache/PCH consumer. See [asset checks](testing.md) and the current
[coverage inventory](planning/runtime_catalog_coverage.md).

| Need | Read |
| --- | --- |
| Language-wide verification plan | [Goals and triage](planning/language_verification_plan.md); [119 concrete checks](planning/language_verification_checks.md); [missing proofs](planning/language_verification_probes.md); [coverage map](planning/language_verification_checklist.md). Verification completed; see the [findings and repair follow-ups](planning/language_verification_report.md) and [overall repair progress](planning/language_repair_progress.md). |
| Next milestone: v0.1 source compatibility | [Baseline comparison, missing support, approved changes and acceptance criteria](planning/v0_1_compatibility_report.md); source behavior is the goal, not identical lowering. |
| Current compatibility focus: strict only | [Grouped causes, owners and strict replay manifest](planning/v0_1_compatibility_strict_causes.md); legacy-profile tests excluded from this view. |
| Full generated operator matrix | [22,816-case server audit, grouped blockers and intentional changes](planning/v0_1_compatibility_operator_matrix.md) |
| Scalar admission follow-up | [Bool equality, float arithmetic, S03 proof and resolved zero-division contract](planning/v0_1_compatibility_scalar_admission.md) |
| Operator compatibility checkpoint | [Strict replay, behavioral proofs and semantic differences](planning/v0_1_compatibility_operators.md) |
| Operating rules and checks | [AGENTS](../AGENTS.md), [code style](code_style.md), [test runners](testing.md) |
| Retained data and ownership | [Model](architecture/MODEL.md), [ownership](architecture/ownership.md) |
| Specialized syntax and traversal | [AST layout](architecture/ast_layout.md) |
| Incremental stages and failure handling | [Lifecycle](lifecycle/incremental.md), [work queue](lifecycle/work_queue.md) |
| Active multithreading design and shared-write inventory | [Multithreading plan](planning/multithreading.md) |
| Collection and preparation | [Analysis](../04_analyze/README.md), [preparation](../04_analyze/prepare/README.md) |
| Collection APIs | [Storage](storage/STORAGE.md) |
| Portability status and evidence limits | [Conversion review](portability/conversion_review.md) |
| Conversion quality and portable-PHP frontend discussion | [PHS elegance, native costs, lookup review and shared-model options](planning/conversion_quality.md) (planning only) |
| Preparation / C++ emission boundary audit | [Findings, evidence and open repair gates](planning/preparation_emission_audit.md) |
| Semantic diagnostics objective and next review | [Review workflow, case inventory and repair categories](planning/diagnostic_review.md) (planning; execution and repairs are separate steps) |
| Stage dependency and ownership audit | [Forward dependencies, shared structures and discussion groups](planning/stage_boundary_audit.md) |
| Stage-owned processing refactor | [Agreed no-visitor boundary, implementation batches and open questions](planning/stage_owned_processing.md) |
| v0.2 early rejection versus runtime guards | [Preparation diagnostic policy](../../../specs/v0_2_preparation_diagnostics.md) |
| Mandatory performance task once operational | [PCH findings, incremental builds and LLVM/IR investigation](planning/compile_performance.md) |
| Planned Clang latency and link-layout experiments | [2–3 s p80 target, future unit splitting, parallel scheduling, and Mold executable/shared-library comparison](planning/clang_units_and_mold.md) |
| Chapter 08 implementation roadmap | [Multi-file artifacts, staged migration, incremental native builds and runtime PCH](planning/incremental_cpp_artifacts.md) |
| C++ implementation-group assembly | [Structure and exact-output proof](planning/implementation_group_assembly_20261016.md) |
| Persistent generated output | [Coordinator publication, ownership, locking and recovery](architecture/output_publication.md) |
| Concrete C++ specialization | [Active end-to-end solidification TODO](../../../specs/planning/prelower_templates_solidification_todo_2026_10_20.md); [Accounting recipes and opt-in runtime ABI expressions, ownership and coverage](architecture/cpp_template_prelowering.md); [capability-driven generalization matrix](../../../specs/planning/runtime_template_capability_matrix_2026_10_19.md); [representation/ownership audit](../../../specs/planning/prelower_representation_audit_2026_10_19.md); [ABI/layout integration](../../../specs/planning/results/prelower_layout_integration_2026_10_19/README.md); [ordinary-expression proof](../../../specs/planning/results/prelower_expressions_2026_10_19/README.md); [nonempty initializer proof](../../../specs/planning/results/prelower_initializers_2026_10_19/README.md); [source payload and ownership proof](../../../specs/planning/results/prelower_source_payloads_2026_10_19/README.md); [source method transport proof](../../../specs/planning/results/prelower_methods_2026_10_19/README.md); [constructor transport proof](../../../specs/planning/results/prelower_constructors_2026_10_19/README.md); [concrete-field composition experiment](../../../specs/planning/results/prelower_composition_2026_10_19/README.md); [concrete argument binding integration](../../../specs/planning/results/prelower_substitution_2026_10_19/README.md); [ordinary source fields/signatures/member emission](../../../specs/planning/results/prelower_source_fields_2026_10_19/README.md); [source template arguments and shared headers](../../../specs/planning/results/prelower_source_arguments_2026_10_19/README.md) |
| Persistent native cache | [Staged outputs, dependencies, runtime PCH and bounded native build](architecture/native_cpp_build.md) |

| Native Ninja accounting measurements | [O2 native compiler, full/unchanged/body-edit timings and memory](planning/native_ninja_accounting_20261016.md) |
| Ninja/Mold comparison and eight-edit scaling | [Mold 8 threads, Ninja 12 jobs, WSL and dev002 timings/memory](planning/native_ninja_mold_20261016.md) |
| Configurable native linker acceptance | [Production Mold selection, link-only invalidation and native dev002 verification](planning/native_linker_configuration_20261016.md) |
| Complex edits and PCH integration experiment | [dev002 signature/layout/shared-header/runtime edits: rebuild sets, timing and memory](planning/native_edit_matrix_20261016.md) |
| Shared-handle dependency refinement | [Implementation and dev002 application edit comparison](planning/shared_handle_completeness_20261016.md) |
| Initialization header boundaries | [Typed/mixed/dynamic separation, compatibility umbrella and dev002 proof](planning/initialization_headers_20261016.md) |
| Optimization-aware lowering | [Constructor outlining](planning/optimization_policy_20261016.md), [shared-allocation outlining and dev002 measurements](planning/allocation_outlining_20261016.md) |
| Native build consumers | [Shared command recipes, optional Ninja execution, and staged native cache](architecture/native_cpp_build.md) |
| Namespace/import implementation | [Agreed language rules](../../../specs/namespaces_v0_2.md), [approved identity/body migration and current checkpoints](planning/namespace_identity_migration.md) |
| Remaining implementation debt | [Incremental/v0.2 plan](planning/incremental_strategy.md), [portability review](portability/REVIEW.md) |
| Value storage modifier semantics | [Registry normalization and AST spelling](../../../specs/type_use_modifiers.md) |
| Active type-model discussion | [Type model direction](planning/types.md) |
| Mixed/dynamic runtime design discussion | [Shared machinery, family separation and recorded mixed layout improvement](planning/mixed_dynamic_storage.md) |
| Active cast/conversion discussion | [Cast and conversion preparation](planning/casts.md) |
| Offset replacement compatibility | [S09 two signatures, insertion/no-op contract and unchanged strict surface](planning/s09_substr_replace.md) |
| Byte search compatibility | [S09 four search signatures, runtime isolation and call boundary](planning/s09_byte_search.md) |
| Exact-mixed type predicates | [S07 observation bindings, unchanged strict fixture and wider-type boundaries](planning/s07_mixed_predicates.md) |
| Single-statement conditional bodies | [S06 binding, scope, recovery and separate S09 blocker](planning/s06_unbraced_conditionals.md) |
| Scalar/carrier text contexts | [V-L11 / V-D14 contract, bounded proofs and deferred domains](planning/carrier_text.md) |
| Scalar float identity / S04 | [Two scalar forms, carrier reconciliation and unchanged process fixture](planning/float_identity.md) |
| V-L07 bounded completion | [Completion proof and wider gaps](planning/vl07_completion.md); [canonical inventory and dispositions](planning/vl07_inventory/README.md); [exact unresolved canonical signatures (none)](planning/vl07_inventory/remaining.csv) |
| Current scalar operators | [Bounded contracts, lowering and proof](s2s_scalar_operators.md); [guarded result_or_bool slice](planning/guarded_wrappers_02.md); [guarded float slice](planning/guarded_float.md); [guarded mixed numeric slice](planning/guarded_mixed.md); [guarded bool slice](planning/guarded_bool.md); [guarded string ordering](planning/guarded_string.md); [guarded cross-family operators](planning/guarded_cross.md); [guarded bool XOR](planning/guarded_bool_xor.md); [guarded spaceship](planning/guarded_spaceship.md); [numeric XOR](planning/numeric_xor.md); [nullable-int equality](planning/nullable_int_equality.md); [nullable bool/string equality](planning/nullable_bool_string_equality.md); [nullable-float equality](planning/nullable_float_equality.md); [Result equality](planning/result_equality.md); [false-sentinel bool equality](planning/false_bool_equality.md); [two-sentinel bool equality](planning/bool_sentinel_equality.md); [non-bool sentinel equality](planning/nonbool_sentinel_equality.md); [sentinel/plain equality repair](planning/sentinel_plain_equality.md); [carrier updates and V-R10](planning/carrier_updates.md); [int arithmetic compounds](planning/int_carrier_compounds.md); [int remainder/bitwise/shift compounds](planning/int_bitwise_compounds.md); [cross-family int compounds](planning/int_cross_compounds.md); [float compounds and core completion](planning/float_compounds.md); [mixed ordering O1](planning/mixed_ordering.md); [float-destination C2](planning/widening_compounds.md); [cross-family strict identity](planning/cross_identity.md); [mixed numeric strict identity](planning/mixed_identity.md); [bool/numeric strict identity](planning/bool_numeric_identity.md); [heterogeneous string strict identity](planning/string_other_identity.md); [mixed nullable/Result ordinary equality](planning/mixed_equality.md); [mixed sentinel ordinary equality](planning/sentinel_mixed_equality.md); [non-bool cross-family ordinary equality](planning/cross_equality.md); [mixed-numeric cross-family ordinary equality](planning/cross_mixed_equality.md) |
| Runtime-symbol catalog ingestion | [Per-module metadata](../../../specs/runtime_module_metadata.md) installs JSON functions, byte-counting `strlen`, observational `count`, the strings module's ready surface (52 helpers/71 signatures, two builder types and three constants), all 11 strict datetime helpers and 23 strict filesystem helpers. Shared [container-payload Result operations](../../../specs/error_values_and_take.md#typed-container-result-boundaries--v02) enable fs_scan with recursive copy checks, without new lowering. Selected JSON is read once; resets reuse typed metadata. Work proceeds module-by-module; see the [inventory, linkage boundary and unresolved contracts](planning/runtime_catalog_coverage.md). |
| Conditional wrapper extraction and error state | [Take checkpoint](catalog/05_dynamic_boundaries.md#conditional-take-and-no-error-values--2026-10-04), [language contract](../../../specs/error_values_and_take.md) |
| Optional regex catalog | [Module inventory](planning/runtime_catalog_coverage.md#regex-module-inventory--2026-10-09): ten helpers / 18 signatures, nested container Results and split constants through ordinary metadata. Explicit module selection and PCRE2 linkage required; defaults unchanged. Filter, callbacks, count outputs and limited replacement remain gated by documented prerequisites. |
| Environment/shell catalog | Core metadata exposes getenv/shell_exec as falseable string producers; empty values/output remain success. The [getenv contract](../../../specs/builtins/process/getenv.md) rejects NUL names before lookup. Shell capture preserves binary bytes. Resource/platform follow-ups remain [CH15 debt](catalog/15_blocking_debt.md#ch15-runtime-process-01--environmentshell-edge-and-resource-review). |
| Core utility catalog | Process-memory usage/peak and cli_argc/cli_argv/cli_args use existing metadata contracts. Generated executable startup initializes CLI state once through a tiny context header; argument queries return mixed value snapshots. Stable hashing remains held for [algorithm and persisted-key review](catalog/15_blocking_debt.md#ch15-runtime-hash-01--stable-hashing-implementation-and-persisted-key-compatibility); no hardcoded type additions. |
| Wrapper-left coalescing into mixed | [Consequences and two-step preparation](catalog/05_dynamic_boundaries.md#wrapper-left-mixed-coalescing--2026-10-04) |
| Explicit mixed/dynamic casts | [Bridge semantics and boundaries](catalog/05_dynamic_boundaries.md#explicit-mixeddynamic-bridges--2026-10-04) |
| Mixed numeric arithmetic | [Runtime dispatch, typed results and deferred updates](catalog/05_dynamic_boundaries.md#mixed-numeric-arithmetic--2026-10-04) |
| Mixed comparisons | [Equality, strict identity, ordering and empty-carrier repair](catalog/05_dynamic_boundaries.md#mixed-comparisons-and-empty-carrier-repair--2026-10-04) |
| Mixed numeric updates | [Stored-kind write-back and prefix/postfix snapshots](catalog/05_dynamic_boundaries.md#mixed-numeric-updates--2026-10-04) |
| Mixed logical operators | [Operand truthiness and short-circuit effects](catalog/05_dynamic_boundaries.md#mixed-short-circuit-logical-operators--2026-10-04) |
| Mixed text concatenation | [Text-context conversion and owned string results](catalog/05_dynamic_boundaries.md#mixed-and-scalar-text-concatenation--2026-10-04) |
| Text compound assignment | [String/mixed replacement and snapshots](catalog/05_dynamic_boundaries.md#text-concatenation-write-back--2026-10-04) |
| Mixed and dynamic | [Type model](catalog/05_dynamic_boundaries.md#mixed-and-dynamic-model-foundation--2026-10-04), [scalar boundaries](catalog/05_dynamic_boundaries.md#mixed-scalar-value-boundaries--2026-10-04), [casts and probes](catalog/05_dynamic_boundaries.md#mixed-casts-conditions-and-probes--2026-10-04), [selection joins](catalog/05_dynamic_boundaries.md#direct-mixed-selection-joins--2026-10-04), [language contract](../../../specs/dynamic_types.md) |
| Active operator discussion | [Operator preparation and resolution](planning/operators.md) |
| Data construction implementation direction | [Completed interface standard, adopted hybrid and confirmed stage boundary](planning/data_initialization.md) |
| Data notation parser/AST | [Shared PHP-like/JS-like syntax checkpoint](catalog/06_containers.md#shared-data-parser-and-ast--2026-10-05), [AST ownership](architecture/ast_layout.md#data-construction-syntax) |
| Callable model | [Source/runtime contracts and ownership](architecture/MODEL.md#callable-contracts), [Chapter 06 checkpoint](catalog/06_containers.md#callable-model-foundation--2026-10-05) |
| Called-type implementations | [Callable-instance ownership, lifecycle, demand scheduling and C++ helpers](planning/called_type_instances.md); static-context late binding and shared factories execute. |
| Registered JSON source APIs | [Runtime free functions, exact selection and checked results](catalog/06_containers.md#runtime-free-functions-and-json-source-integration--2026-10-05), [focused runner](testing.md#commands) |
| Initialization binding model | [Shared declarations and input roles](catalog/06_containers.md#initialization-binding-and-input-model--2026-10-05), [retained ownership](architecture/MODEL.md#initialization-contracts) |
| Built-in data construction | [Runtime adapters and shared C++ execution](catalog/06_containers.md#built-in-construction-execution--2026-10-05), [focused runner](testing.md#commands) |
| Container value reads | [Shared read checkpoint](catalog/06_containers.md#shared-container-value-reads--2026-10-05), [bracket contract](../../../specs/index_operator.md), [focused runner](testing.md#commands) |
| Direct container writes/appends | [Shared write checkpoint](catalog/06_containers.md#direct-container-writes-and-appends--2026-10-05), [write semantics](../../../specs/array_semantics.md#22-write-path) |
| Nested container writes | [Path preparation and failure guarantees](catalog/06_containers.md#nested-write-paths--2026-10-05), [write semantics](../../../specs/array_semantics.md#v02-nested-write-paths) |
| Lazy keyed probes/removal | [Presence, emptiness and no-creating removal](catalog/06_containers.md#lazy-keyed-probes-and-removal--2026-10-05), [probe contract](../../../specs/count_empty_isset_contract.md#v02-keyed-evaluation-order) |
| Value foreach | [Shared iteration checkpoint](catalog/06_containers.md#value-foreach--2026-10-05), [final-review mutation debt](catalog/15_blocking_debt.md#ch06-iter-01--mutation-policy), [value-iteration contract](../../../specs/array_semantics.md#v02-value-iteration), [focused runner](testing.md#commands) |
| Final catalog review | [Compact 52-group priorities/dependencies and proposed first slice](catalog/15_review.md); [detailed debt register](catalog/15_blocking_debt.md) |
| Reference foreach (disabled) | [Deferred runtime guard, lifetime and COW foundation](catalog/15_blocking_debt.md#ch06-ref-01--reference-foreach-safety-foundation) |
| Containers across calls | [Grouped integration proof](catalog/06_containers.md#container-function-boundaries--2026-10-05), [function-boundary contract](../../../specs/array_semantics.md#v02-function-boundaries), [focused runner](testing.md#commands) |
| Container defaults and legacy examples | [Known targets, key normalization and remaining gates](catalog/06_containers.md#construction-defaults-and-legacy-reconciliation--2026-10-05) |
| Contextual literal preparation | [Expected types, ordered entry conversions and deferred execution](catalog/06_containers.md#contextual-literal-preparation--2026-10-05) |
| Chapter 05 closeout and debt | [Bounded conclusion and reconciled contracts](catalog/05_dynamic_boundaries.md#positive-pass-conclusion--2026-10-04) |
| Current handoff | [CH12 bounded closeout](catalog/12_closures.md#bounded-closeout-and-current-handoff--2026-10-16): all 13 imported cards and selected callable extensions are covered within their bounds. Focused PHP/generated-program evidence, historical native follow-up and the current workspace smoke have distinct scopes; complete CH12 native parity/rejection/incremental coverage remains unclaimed. Existing CH15 deferrals and unsupported borrowing/wrapper boundaries remain unchanged. [CH13 bounded closeout](catalog/13_errors.md#bounded-closeout-and-native-gate--2026-10-16) passed PHS conversion/native compilation, 68 feature executions with PHP/native parity, 52 rejection/recovery cases and four native edit proofs. NOTE-047 is reconciled; expanded diagnostics/transfers/return families remain deferred. [CH14 bounded closeout](catalog/14_output_runtime.md#bounded-closeout-and-native-gate--2026-10-16) passed compiler PHS conversion/native build and focused native artifact, publication, runtime-linkage and incremental proofs. Legal shadowing is preserved; deferred diagnostic/runtime/cache extensions remain explicit. [CH15 reconciliation](catalog/15_review.md) proposes iteration-policy/guard-ownership design first; implementation awaits prioritization. |
| CH11 closeout | [CH11 reconciliation](catalog/11_inheritance.md#remaining-work-reconciliation--2026-10-08) resolves all 25 original progress rows within their bounds and adds six explicit ownership/type-access rows. Concrete/abstract/interface inheritance, parent construction, direct traits with instance/static members and constants, static called-type execution, derived data construction, shared/interface handles and class/struct weak upcasts execute. [Unique ancestry transfers](catalog/11_inheritance.md#unique-ancestry-transfers--2026-10-08) now consume moved values or owned call results through bases with proven virtual destruction. [Constructor visibility](catalog/11_inheritance.md#constructor-visibility--2026-10-08) now shares access checks and keyed allocation across new/data/base construction. [Nested trait uses](catalog/11_inheritance.md#nested-trait-uses--2026-10-08) now execute with origin deduplication; [abstract trait requirements](catalog/11_inheritance.md#abstract-trait-requirements--2026-10-08) now execute through separate requirement signatures and selected method decisions, including abstract propagation and reference-return cycle checks. [Checked object access](catalog/11_inheritance.md#checked-object-access--2026-10-08) now shares one prepared path for explicit shared casts and literal instanceof. [Shared selection joins](catalog/11_inheritance.md#shared-selection-joins--2026-10-08) now select a supplied base/interface for ternary, Elvis and coalescing without sibling inference. [Interface weak handles](catalog/11_inheritance.md#interface-weak-handles--2026-10-08) now reuse derivation, locking and ancestry conversion without new runtime carriers. [Shared cross-casts](catalog/11_inheritance.md#shared-cross-casts--2026-10-08) now admit potentially overlapping interface views through the existing checked-access path, with observed finality and polymorphism. [Non-public destructors](catalog/11_inheritance.md#non-public-destructors--2026-10-08) now use independent lifecycle work and preparation-time cleanup access checks. [Shared callable variance](planning/callable_variance.md) now executes through directional adaptations and distinct required/implementation dispatch bindings, with bridges defined in source implementation units; seven extensions are deferred in the CH15 scope register, inherited final-method interface forwarding is implemented and interface-qualified static dispatch is now explicitly deferred to CH15; trait constructors/destructors were explicitly deferred to [CH15](catalog/15_blocking_debt.md#ch15-trait-lifecycle-01--trait-constructors-and-destructors), with no automatic transfer of other limits. The current handoff supersedes historical checkpoint next steps. The requested [CH11 native checkpoint](portability/conversion_review.md#latest-native-checkpoint--2026-10-08-chapter-11) now passes cumulative validation, 1,569 chapter artifact comparisons and 518 generated-program executions; its documented rejection/incremental limits and STAN/LLVM exclusions remain explicit. |
| Earlier proposals and proof checkpoints | [Archive](archive/README.md) |

CH12 bounded reference captures now execute using the
[lexical lifetime-region prerequisite](planning/closure_foundation.md#reference-capture-prerequisite-discovered--2026-10-09).
Whole locals/parameters and eligible aliases retain readonly access and transitive
borrowing obligations. Local copies are lifetime-checked; erased callable conversion
rejects borrowing environments.

CH10's [reconciled inventory](catalog/10_objects.md#reconciliation--2026-10-07)
distinguishes completed duplicate cards, intentional gates and later chapter owners.
The [nullable normalization checkpoint](catalog/10_objects.md#nullable-handle-normalization--2026-10-07)
implements the agreed no-double-absence handle model. The [inline-record checkpoint](catalog/10_objects.md#nullable-inline-record-boundaries--2026-10-07)
adds construction, payload-aware copying, checked extraction and null comparisons.
The [record observations/collection-only emptiness checkpoint](catalog/10_objects.md#record-observation-and-collection-only-empty--2026-10-07)
adds noncopying `isset`/conditions and replaces absence-style `empty` consistently.
The [nullable selection checkpoint](catalog/10_objects.md#nullable-record-selection-joins--2026-10-07)
adds exact copyable-payload ternary/Elvis/coalescing joins. Property compound and
prefix/postfix updates now reuse the common mutation worker and existing property
places. Default-based property inference now shares declaration/default preparation,
including inferred-type dependency invalidation. Direct-local ownership shortcuts
now reuse typed construction. The [CH10 timing review](catalog/10_objects.md#ch10-perf-01--test-duration-review-before-native-build)
and the requested CH10 native compiler validation are complete.

Current guides describe responsibilities and invariants. Tests provide behavioral
evidence; root repository specs define language semantics. Archived documents are
historical, including statements of what was “current” at their recorded checkpoint.

## Source layout

| Directory | Owner |
| --- | --- |
| `01_prepare_inputs/` | Module configuration, recursive discovery and source reads |
| `02_tokenize/` | Byte tokenization and appended source/token storage |
| `03_parse/` | Typed AST, scopes, retained parsing and token cleanup |
| `03_parse/collect/` | Symbol registration called by the parser |
| `04_analyze/prepare/` | Shared semantic work and attached facts |
| `05_backend/cpp/` | Retained C++ fragments and final text |
| `05_backend/llvm/` | Parked LLVM preparation/emission |
| `06_native/` | Compilation/execution of generated programs |
| `compiler/` | Model, lifecycle, scheduling, publication and shared types |
| `helpers/`, `tools/`, `tests/` | Host collections, tooling and regression evidence |

See the [coordinator call map](../compiler/calls.md) for entrypoints.

## Running and checking

From the repository root:

```bash
php compiler/my-try/main.php --s2s SOURCE_DIRECTORY
php compiler/my-try/main.php --s2s SOURCE_DIRECTORY --entry main.phs
php compiler/my-try/main.php --s2s SOURCE_DIRECTORY --entry main.phs --output /absolute/output
php compiler/my-try/tests/tokenizer.php
```

The S2S mode prints a `scpp-cpp-artifacts-v1` JSON bundle to stdout and reports
failures on stderr. Its `artifacts` array contains `id`, `kind` (`translation_unit`
or `header`), relative `path` and final `text` for every output. Do not compile the
JSON as C++ or consume only its first artifact. Assembly emits callable/type headers,
per-source implementation groups and `entry.cpp`, including for single-source programs.
Multiple sources require `--entry` (module-relative); only that source may contain
top-level executable statements. The programmatic multi-module API uses explicit
logical names and `select_entry(module, path)`. Without a mode,
`main.php` runs the legacy sample/debug report, including native sample execution.
The browser accepts `mode=s2s&source=SOURCE_DIRECTORY&entry=main.phs`; the local Apache template
is [tools/apache/scpp-my-try.conf](../tools/apache/scpp-my-try.conf).

Optional CLI `--output` publishes content-aware disk artifacts through the
[locked ownership/recovery protocol](architecture/output_publication.md), while
still returning the JSON bundle. This does not build native objects or an executable.

Focused PHP tests are the default. Some fixtures require an existing temporary
output directory; inspect their entry before invoking them. The full runner
`python3 compiler/my-try/tests/run.py --results FRESH_DIRECTORY` and native compiler
validation are on demand. Both runners default to 12 continuously refilled worker
slots; use `--jobs N` to override. See [test runners](testing.md) for isolation and
evidence details. Running the compiler in PHP and compiling its output is
not proof that the compiler itself builds natively; see the portability review.

The latest requested [native gate](portability/conversion_review.md#chapter-14-native-gate--2026-10-16)
covers bounded Chapter 14 after compiler conversion, native build and focused tests.
The historical cumulative Chapter 05–11 checkpoint retains its own scope; Chapter 15
capabilities and broader CH12 native coverage remain outside this gate.

The [platform/alias slice](planning/platform_alias.md) covers explicit core
`php::microtime` aliases with observed source-type precedence, restored
`fs_is_windows`, and the environment-name contract. General namespaces and other
compatibility aliases remain separate.

The [V-L09/V-L10 closeout](planning/boxed_selection.md) adds homogeneous boxed
integer expressions and canonical-scalar wrapper coalescing with lazy payload
normalization. Its separate BS-R01 finding keeps unchecked primitive shift counts
explicit; it does not certify those counts or widen pairs/updates.

The [callback/composition closeout](planning/callback_composition.md) exposes both
strict regex callback APIs and repairs runtime CMake core/provider ownership.
Snapshots, errors and static/shared consumers pass on dev002; broader callback
lifetimes, count outputs and database integration remain separate.
