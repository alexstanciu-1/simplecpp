# Token input constructor promotion — 2026-10-24
Doc Status: planning

Base b82446ec7 plus candidate hashes. User approved promoting Parser_Run,
Token_Cleanup and collected_file tokens inputs. Reuse analysis: existing private
promotion, set initialization and shared-handle transport suffice. The supplied
handle exists before construction; no nullable/accessor protocol is needed.
Parser_Run token cleanup/append, range reset, position calculation and later
collection operations remain in order. No parameter rebinding or observation of
the partially constructed worker occurs in those first operations. collected_file
keeps set_tokens for later updates. No language/converter/runtime changes.

## Proofs and limits

Five actual host tests pass: tokenizer, token_generations, token_cleanup, indexing
and reference_model. They cover retained syntax/work, token append/compaction,
malformed-input recovery, repeated updates and fresh/incremental equivalence.

Extracted production native fixture passes PHP, legacy, ordinary and concrete
routes. Native task durations 29.85/33.74/33.80 s. It uses actual token records,
token_list and Token_Buffer, collected_file construction/rebinding, and Token_Cleanup
construction/index remapping. Parser_Run constructor extraction ends immediately
after initial position calculation; later scope/collection construction still has
independent bootstrap gates. AST-wide cleanup is a fixture stand-in and dependency
records are doubles. The full cleanup algorithm is proved by host tests separately.
Proof checks exact token-list and row identity, append offsets, cursor placement,
collection rebinding, visibility of mutations and fresh-input construction.
This is not a full native compiler build.

First attempt passed both v0.2 modes but legacy rejected fixture-only method
position colliding with its field. Renamed the test accessor current_position;
second attempt passed all modes. No production workaround. Shared runner fell back
to CLI because FPM socket binding was unavailable.

## Bootstrap

92.14 s wall / 355240 KiB peak RSS (346.91 MiB) / 324 MiB PHP heap.
Task 100.55 s includes conversion/setup. Native proof tasks overlapped the beginning
of this run; timing is observational, not an isolated performance comparison.
Removed tokens set-initialization diagnostic. Newly exposed source_scope set check;
first remaining diagnostic is root requiring set initialization before constructor
body (already present in prior aggregate). Stop before either fix. No complete
bootstrap claim.
