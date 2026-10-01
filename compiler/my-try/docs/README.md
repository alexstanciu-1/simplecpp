# my-try compiler
Doc Status: supporting

PHP is the implementation source. The active v0.2 path is strict PHS/PHP++ → typed
syntax and collection → shared semantic preparation → C++ generation. Other
frontends should normalize toward the same PHS-shaped concepts. LLVM is a parked
experiment retained for regressions, not a second semantic development path.

## Reading map

| Need | Read |
| --- | --- |
| Operating rules and checks | [AGENTS](../AGENTS.md), [code style](code_style.md) |
| Retained data and ownership | [Model](architecture/MODEL.md), [ownership](architecture/ownership.md) |
| Specialized syntax and traversal | [AST layout](architecture/ast_layout.md) |
| Incremental stages and failure handling | [Lifecycle](lifecycle/incremental.md), [work queue](lifecycle/work_queue.md) |
| Collection and preparation | [Analysis](../04_analyze/README.md), [preparation](../04_analyze/prepare/README.md) |
| Collection APIs | [Storage](storage/STORAGE.md) |
| Portability status and evidence limits | [Conversion review](portability/conversion_review.md) |
| Remaining implementation debt | [Incremental/v0.2 plan](planning/incremental_strategy.md), [portability review](portability/REVIEW.md) |
| Active type-model discussion | [Type model direction](planning/types.md) |
| Next language feature | [Catalog](catalog/README.md) |
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
validation are on demand. Running the compiler in PHP and compiling its output is
not proof that the compiler itself builds natively; see the portability review.
