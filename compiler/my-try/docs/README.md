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
| Called-type implementations | [Callable-instance ownership, lifecycle, demand scheduling and C++ helpers](planning/called_type_instances.md); static-context late binding and shared factories execute. |
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
| Current handoff | [CH11 reconciliation](catalog/11_inheritance.md#remaining-work-reconciliation--2026-10-08) resolves all 25 original progress rows within their bounds and adds six explicit ownership/type-access rows. Concrete/abstract/interface inheritance, parent construction, direct traits with instance/static members and constants, static called-type execution, derived data construction, shared/interface handles and class/struct weak upcasts execute. [Unique ancestry transfers](catalog/11_inheritance.md#unique-ancestry-transfers--2026-10-08) now consume moved values or owned call results through bases with proven virtual destruction. [Constructor visibility](catalog/11_inheritance.md#constructor-visibility--2026-10-08) now shares access checks and keyed allocation across new/data/base construction. [Nested trait uses](catalog/11_inheritance.md#nested-trait-uses--2026-10-08) now execute with origin deduplication; [abstract trait requirements](catalog/11_inheritance.md#abstract-trait-requirements--2026-10-08) now execute through separate requirement signatures and selected method decisions, including abstract propagation and reference-return cycle checks. [Checked object access](catalog/11_inheritance.md#checked-object-access--2026-10-08) now shares one prepared path for explicit shared casts and literal instanceof. [Shared selection joins](catalog/11_inheritance.md#shared-selection-joins--2026-10-08) now select a supplied base/interface for ternary, Elvis and coalescing without sibling inference. [Interface weak handles](catalog/11_inheritance.md#interface-weak-handles--2026-10-08) now reuse derivation, locking and ancestry conversion without new runtime carriers. [Shared cross-casts](catalog/11_inheritance.md#shared-cross-casts--2026-10-08) now admit potentially overlapping interface views through the existing checked-access path, with observed finality and polymorphism. The selected remaining CH11 queue is non-public destructors and variance/covariant returns; seven extensions are deferred in the CH15 scope register, while two dispatch limits remain undecided; trait constructors/destructors were explicitly deferred to [CH15](catalog/15_blocking_debt.md#ch15-trait-lifecycle-01--trait-constructors-and-destructors), with no automatic transfer of other limits. The current handoff supersedes historical checkpoint next steps. Latest native compiler evidence remains [CH10](portability/conversion_review.md#latest-native-checkpoint--2026-10-07-chapter-10); CH11 has PHP-host and generated-C++ evidence, with native compiler validation outstanding. |
| Earlier proposals and proof checkpoints | [Archive](archive/README.md) |

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

The latest requested [native checkpoint](portability/conversion_review.md#latest-native-checkpoint--2026-10-07-chapter-10)
covers the bounded Chapter 05–10 implementation through class/struct programs. Deferred
Chapter 15 capabilities remain outside that proof.
