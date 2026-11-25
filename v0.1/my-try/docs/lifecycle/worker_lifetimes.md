# Worker lifetime
Doc Status: supporting

Required inputs must exist before use. Public reusable entries create fresh transient
workers where invocation state must not leak. No placeholder AST, dummy required
field or analysis bypass is part of this contract.

| Entry | Lifetime |
| --- | --- |
| `Tokenizer(file)` | Fixed source; each `tokenize()` captures bytes and returns a new scan result |
| `Parser(tokens, scope, previous, global)` | `init()` selects input; each `parse()` creates its own `Parser_Run` |
| `Preparation_Worker` | Schedules retained work; one isolated context per rebuild, seeded with parameters for a function body |
| `File_Preparation` | Standalone adapter into the same preparation scheduler |
| `LLVM_Legacy_Template_Checker` | Transient context with a file index; independent symbolic locals per file/template worker |
| `LLVM_Preparation` | Fresh per-program registry, pending instances and type preparation |
| `LLVM_Generator` | Independent function workers, blocks, temporary counters and initialized-local sets |

Retained syntax/work/facts never store workers or invocation contexts. Reuse the
public entry rather than running an internal one-shot worker twice. Old handles
may survive removal, but are not thereby current compiler data. Mutable incremental
parsing does not promise immutable prior results or transactional rollback.

[Ownership](../architecture/ownership.md) defines publication and observer rules;
[work queues](work_queue.md) define joining/locking. Historical native counts belong
to [portability evidence](../portability/conversion_review.md), not this contract.
