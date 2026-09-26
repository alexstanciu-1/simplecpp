# Worker initialization and reuse

Current native checkpoint: the normal STAN-enabled build passes 142 PHP/native
comparisons (48 valid programs executed, 94 rejection/recovery cases). See
`compiler/my-try/docs/portability/native_adaptations.md` for changes and limitations. Earlier
pending-build notes below describe historical checkpoints; advisory STAN diagnostics
remain and the verified target pin has not changed.
Doc Status: supporting

Required inputs are supplied at construction. Reusable entry points create a fresh
worker for each invocation whose transient state must not leak into the next run.
No placeholder AST, optional-required field, or initialization-analysis bypass is
used. These changes do not add compiler language features or change Model ownership.

| Entry point | State lifetime |
| --- | --- |
| `new Tokenizer(file)` | Required source exists immediately. `tokenize()` captures current content and creates a fresh token list. `init(file)` can select the next source. |
| `new Parser(tokens, target_scope)` | Required tokens exist immediately; target scope remains explicitly optional. `init()` selects the next input. Each `parse()` creates a `Parser_Run` with its real syntax result, scope and collector initialized. |
| `Template_Checker::check(files, policy)` | Creates a complete `template_check_context` once, sharing its file index and policy across fresh `Template_File_Checker` workers. Symbolic locals/bindings reset per template. |
| `LLVM_Preparation::prepare_program(sources, policy)` | Creates `LLVM_Preparation_Run` with a fresh registry, work queue, indexes and prepared struct types. A failed invocation cannot retain state in the reusable facade. |
| `LLVM_Generator::generate(files, policy)` | Builds module output locally and creates one `LLVM_Function_Generator` per function. Each owns its actual initial block, temporary counter and initialized-local set. |

The `_Run` and per-function/file workers are implementation entry points for one
invocation. Reuse the public facade instead of invoking an internal run twice.
The shared template context is transient data, not part of Model. Its index points
to the invocation's prepared files; it does not copy records or the index per file.

Returned records remain usable after subsequent successful or failed invocations.
Parser's supplied scope retains the existing collector publication behavior; this
does not introduce transactional rollback across an entire compilation.

## Validation

PHP tokenizer, AST and model tests pass, including scanner/parser reuse, failed
preparation recovery, and generator reuse after an entry-validation failure.
All 19 LLVM fixture outputs match the preceding PHP checkpoint. The 28 call,
reference, array, struct and template programs also compile and run with their
expected native exit codes; their compiler is still PHP.

On the PR #244 candidate plus the existing local overlays, normal native build
analysis dropped from 79 diagnostics to 6, with **zero required-field initialization
diagnostics**. Explicit typed locals stabilize the selected parser scope and
prepared struct index before field assignment. The remaining diagnostics concern
return paths and enum names, not initialization. Full native compiler success is
still pending; see the saved lifecycle checkpoint under
`specs/planning/compiler_migration/results/my-try-lifecycle-01`.
