# my-try compiler
Doc Status: supporting

PHP is the implementation source. The active v0.2 path is strict PHS/PHP++ → typed
syntax and collection → shared semantic preparation → C++ generation. Other
frontends should normalize toward the same PHS-shaped concepts. LLVM is a parked
experiment retained for regressions, not a second semantic development path.

## Reading map

| Need | Read |
| --- | --- |
| Operating rules and checks | [AGENTS](../AGENTS.md), [code style](code_style.md), [test runners](testing.md) |
| Retained data and ownership | [Model](architecture/MODEL.md), [ownership](architecture/ownership.md) |
| Specialized syntax and traversal | [AST layout](architecture/ast_layout.md) |
| Incremental stages and failure handling | [Lifecycle](lifecycle/incremental.md), [work queue](lifecycle/work_queue.md) |
| Active multithreading design and shared-write inventory | [Multithreading plan](planning/multithreading.md) |
| Collection and preparation | [Analysis](../04_analyze/README.md), [preparation](../04_analyze/prepare/README.md) |
| Collection APIs | [Storage](storage/STORAGE.md) |
| Portability status and evidence limits | [Conversion review](portability/conversion_review.md) |
| Preparation / C++ emission boundary audit | [Findings, evidence and open repair gates](planning/preparation_emission_audit.md) |
| Stage dependency and ownership audit | [Forward dependencies, shared structures and discussion groups](planning/stage_boundary_audit.md) |
| Stage-owned processing refactor | [Agreed no-visitor boundary, implementation batches and open questions](planning/stage_owned_processing.md) |
| v0.2 early rejection versus runtime guards | [Preparation diagnostic policy](../../../specs/v0_2_preparation_diagnostics.md) |
| Mandatory performance task once operational | [PCH findings, incremental builds and LLVM/IR investigation](planning/compile_performance.md) |
| Chapter 08 implementation roadmap | [Multi-file artifacts, staged migration, incremental native builds and runtime PCH](planning/incremental_cpp_artifacts.md) |
| Persistent generated output | [Coordinator publication, ownership, locking and recovery](architecture/output_publication.md) |
| Persistent native cache | [Staged outputs, dependencies, runtime PCH and bounded native build](architecture/native_cpp_build.md) |
| Namespace/import implementation | [Agreed language rules](../../../specs/namespaces_v0_2.md), [approved identity/body migration and current checkpoints](planning/namespace_identity_migration.md) |
| Remaining implementation debt | [Incremental/v0.2 plan](planning/incremental_strategy.md), [portability review](portability/REVIEW.md) |
| Value storage modifier semantics | [Registry normalization and AST spelling](../../../specs/type_use_modifiers.md) |
| Active type-model discussion | [Type model direction](planning/types.md) |
| Mixed/dynamic runtime design discussion | [Shared machinery, family separation and recorded mixed layout improvement](planning/mixed_dynamic_storage.md) |
| Active cast/conversion discussion | [Cast and conversion preparation](planning/casts.md) |
| Current scalar operators | [Bounded contracts, lowering and proof](s2s_scalar_operators.md) |
| Conditional wrapper extraction and error state | [Take checkpoint](catalog/05_dynamic_boundaries.md#conditional-take-and-no-error-values--2026-10-04), [language contract](../../../specs/error_values_and_take.md) |
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
| Registered JSON source APIs | [Runtime free functions, exact selection and checked results](catalog/06_containers.md#runtime-free-functions-and-json-source-integration--2026-10-05), [focused runner](testing.md#commands) |
| Initialization binding model | [Shared declarations and input roles](catalog/06_containers.md#initialization-binding-and-input-model--2026-10-05), [retained ownership](architecture/MODEL.md#initialization-contracts) |
| Built-in data construction | [Runtime adapters and shared C++ execution](catalog/06_containers.md#built-in-construction-execution--2026-10-05), [focused runner](testing.md#commands) |
| Container value reads | [Shared read checkpoint](catalog/06_containers.md#shared-container-value-reads--2026-10-05), [bracket contract](../../../specs/index_operator.md), [focused runner](testing.md#commands) |
| Direct container writes/appends | [Shared write checkpoint](catalog/06_containers.md#direct-container-writes-and-appends--2026-10-05), [write semantics](../../../specs/array_semantics.md#22-write-path) |
| Nested container writes | [Path preparation and failure guarantees](catalog/06_containers.md#nested-write-paths--2026-10-05), [write semantics](../../../specs/array_semantics.md#v02-nested-write-paths) |
| Lazy keyed probes/removal | [Presence, emptiness and no-creating removal](catalog/06_containers.md#lazy-keyed-probes-and-removal--2026-10-05), [probe contract](../../../specs/count_empty_isset_contract.md#v02-keyed-evaluation-order) |
| Value foreach | [Shared iteration checkpoint](catalog/06_containers.md#value-foreach--2026-10-05), [final-review mutation debt](catalog/15_blocking_debt.md#ch06-iter-01--mutation-policy), [value-iteration contract](../../../specs/array_semantics.md#v02-value-iteration), [focused runner](testing.md#commands) |
| Final catalog review | [Accumulated blocking debt after the feature chapters and rest break](catalog/15_blocking_debt.md) |
| Reference foreach (disabled) | [Deferred runtime guard, lifetime and COW foundation](catalog/15_blocking_debt.md#ch06-ref-01--reference-foreach-safety-foundation) |
| Containers across calls | [Grouped integration proof](catalog/06_containers.md#container-function-boundaries--2026-10-05), [function-boundary contract](../../../specs/array_semantics.md#v02-function-boundaries), [focused runner](testing.md#commands) |
| Container defaults and legacy examples | [Known targets, key normalization and remaining gates](catalog/06_containers.md#construction-defaults-and-legacy-reconciliation--2026-10-05) |
| Contextual literal preparation | [Expected types, ordered entry conversions and deferred execution](catalog/06_containers.md#contextual-literal-preparation--2026-10-05) |
| Chapter 05 closeout and debt | [Bounded conclusion and reconciled contracts](catalog/05_dynamic_boundaries.md#positive-pass-conclusion--2026-10-04) |
| Current handoff | CH08/09 [cumulative native validation passed](portability/conversion_review.md#latest-native-checkpoint--2026-10-07-chapter-0809). CH10's records, defaults, `data` construction, constructors, instance methods, shared reference/variadic parameters and [static members](catalog/10_objects.md#static-members--2026-10-07) pass focused PHP-host/generated-program checks. Method reference returns and instance-qualified static calls remain CH10 follow-ups; inheritance-dependent behavior is ordered in CH11. The [test-duration review](catalog/10_objects.md#ch10-perf-01--test-duration-review-before-native-build) precedes CH10 native validation. [Chapter 15 debt](catalog/15_blocking_debt.md) remains |
| Earlier proposals and proof checkpoints | [Archive](archive/README.md) |

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

The latest requested [native checkpoint](portability/conversion_review.md#latest-native-checkpoint--2026-10-07-chapter-0809)
covers the bounded Chapter 05–09 implementation through partitioned namespace/constant/enum programs. Deferred
Chapter 15 capabilities remain outside that proof.
