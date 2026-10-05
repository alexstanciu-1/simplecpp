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
| v0.2 early rejection versus runtime guards | [Preparation diagnostic policy](../../../specs/v0_2_preparation_diagnostics.md) |
| Mandatory performance task once operational | [PCH findings, incremental builds and LLVM/IR investigation](planning/compile_performance.md) |
| Remaining implementation debt | [Incremental/v0.2 plan](planning/incremental_strategy.md), [portability review](portability/REVIEW.md) |
| Value storage modifier semantics | [Registry normalization and AST spelling](../../../specs/type_use_modifiers.md) |
| Active type-model discussion | [Type model direction](planning/types.md) |
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
| Chapter 05 closeout and debt | [Bounded conclusion and reconciled contracts](catalog/05_dynamic_boundaries.md#positive-pass-conclusion--2026-10-04) |
| Next language feature | [Catalog](catalog/README.md), [chapter 06 agreed standard and grouping](catalog/06_containers.md#agreed-standard-and-revised-grouping--2026-10-05), [top-level data initialization standard](../../../specs/data_initialization.md) |
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
| `04_analyze/collect/` | Symbol registration called by the parser |
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
php compiler/my-try/tests/tokenizer.php
```

The S2S mode prints C++ to stdout and reports failures on stderr. Without a mode,
`main.php` runs the legacy sample/debug report, including native sample execution.
The browser accepts `mode=s2s&source=SOURCE_DIRECTORY`; the local Apache template
is [tools/apache/scpp-my-try.conf](../tools/apache/scpp-my-try.conf).

Focused PHP tests are the default. Some fixtures require an existing temporary
output directory; inspect their entry before invoking them. The full runner
`python3 compiler/my-try/tests/run.py --results FRESH_DIRECTORY` and native compiler
validation are on demand. Both runners default to 12 continuously refilled worker
slots; use `--jobs N` to override. See [test runners](testing.md) for isolation and
evidence details. Running the compiler in PHP and compiling its output is
not proof that the compiler itself builds natively; see the portability review.
