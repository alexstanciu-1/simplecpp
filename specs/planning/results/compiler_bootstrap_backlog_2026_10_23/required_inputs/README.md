# Required source inputs initialized at construction
Doc Status: planning

Candidate bd61ce027 plus this commit. Reuse ordinary PHP constructor promotion and
the converter's existing v0.2 set parameters. No staged-field annotation or new
compiler initialization inference. source_work requires source/record/position at
its only production construction site, before queue publication. Tokenizer receives
its source by promotion. token_list requires file/content at both production sites
(tokenization and private source snapshots); update three synthetic test sites.
Fresh token/range collections remain per-instance. No parent constructor is involved.

PHP tokenizer, publication/queue, retained parser and AST invariant checks pass.
The extended source_initialization fixture extracts the actual source_work/token_list
and token declarations; PHP, legacy native, ordinary and concrete v0.2 pass. Checks
cover source/record identity, queue position, snapshot bytes, adjacent shift spelling
and independent token storage, alongside previous source-record/cursor controls.
This does not rebuild the complete Tokenizer or compiler natively.
Initial fixture setup omitted the host Storage helper; adding its existing bootstrap
dependencies fixed that setup error without changing production behavior.

model.php fails at its hard-coded registry roots expectation before reaching the
changed synthetic token-list fixture. Reproduced using HEAD boot/test and all five
touched production files restored from HEAD in a temporary read-only source overlay
(/tmp/required-inputs-model-baseline-02). Other production files were unchanged.
The first overlay attempt had a temporary test-directory path error; corrected
paths reproduce the root assertion. This regression-suite reconciliation remains
open; do not claim the model suite passes.

Initial source_work/Tokenizer-only bootstrap: 75.41 s, 342352 KiB RSS; first file
initialization failure unchanged. Final token_list-inclusive bootstrap:
74.73 s / 341924 KiB RSS (333.9 MiB) / 312 MiB PHP heap. First blocker moves to
collection initialization. Aggregate diagnostic texts have no additions/removals,
because remaining declarations still report the same field-name messages. No fatal,
OOM, emission or native compiler rebuild; measurements end at preparation failure.
