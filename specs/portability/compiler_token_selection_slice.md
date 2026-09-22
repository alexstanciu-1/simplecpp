# Token task selection
Doc Status: supporting

Token_Selection owns the pure selection algorithm extracted from Tokenizer. The
existing phase delegates to it; scheduling, worker execution and finalization remain
with the phase. No compiler functionality or converter capability is introduced.

Full rebuild selects every live snapshot in source order. Incremental selection
selects absent previous tokens or a different exact source-buffer object. Equal
contents are insufficient for reuse. Pending semantic work is independent of lexical
validity. Deleted rows are skipped, and missing source buffers still throw even on
full rebuild.

The previous nullable member comparison is expressed with separate guards. Full
rebuild avoids the previous-store lookup; missing previous tokens select immediately;
otherwise selection compares exact source identity. Tasks share immutable snapshots
and do not mutate either input owner.

The cumulative PHP/native harness checks empty, full, cold and warm selection,
source order and exact identity, equal-content replacement, retained original state,
deleted membership, and missing snapshots in full and incremental modes. Retained
lexical-update and variable-token fixtures now exercise the extracted owner directly.

The lexical worker and Source_Error remain outside the ready set pending their own
proofs, including target issue #233. This extraction does not claim a native complete
Tokenizer phase.

Evidence: `specs/planning/compiler_migration/results/token-selection-01/summary.json`.
PHP/native validation passes on selected target `2f0d667f38a35ff02ef77e813f409189cba2d032`,
as do all seventeen retained compiler fixtures. Twenty-four production files are ready.
