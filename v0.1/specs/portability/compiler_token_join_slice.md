# Token acceptance
Doc Status: supporting

Token_Join accepts explicit source-buffer and token-buffer vectors, indexed by
logical file ID in typed maps. It preserves exact source-snapshot identity rather
than accepting equal path/version/content as a substitute. Result arrival order
does not determine output order: live source membership owns assembly order.

Selected results replace previous buffers; otherwise the previous buffer is read.
Removed files drop out, retained buffers remain shared, and every returned Token_Set
is a separate membership owner. Validation failures do not mutate previous sets.
Missing keyed entries and absent nullable buffers are rejected in separate guards
before access, avoiding the selected target's compound short-circuit limitation.

No new compiler functionality is added. This component uses the existing structural
converter and the token storage contracts. The PHP/native harness covers reversed arrival order, source-order JSON export,
deleted membership, retained identity, a distinct returned set, untouched previous
sets, incomplete/duplicate/foreign results, equal-content stale source identity,
stale task identity and missing retained buffers. Existing tokenization and lexical
update fixtures retain their broader host regression coverage.

The full lexical worker and diagnostics still require migration; this component
does not establish native tokenization of arbitrary source text.

Evidence: `specs/planning/compiler_migration/results/token-join-01/summary.json`.
Selected target: `2f0d667f38a35ff02ef77e813f409189cba2d032`. All seventeen
retained compiler fixtures pass. Twenty-three production files are now ready.
