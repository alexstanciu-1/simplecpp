# Project parser planning, joining and reuse
Doc Status: supporting

`parse\Parser::parse(Lexical_Project $current, Frontend_Set $previous, bool $full)`
composes a fixed plan, independent file workers and an atomic join. Use an empty
Frontend_Set as the initial baseline. Valid output retains current input order and
entry index, with path lookup through find_path (minus one means absent). Invalid
source returns a failed set with no files, entry index minus one, and the first
selected failure's path, byte span and reason. The caller keeps its previous valid
set when an update fails. Contract misuse throws LogicException.

Four files preserve prototype responsibility boundaries: `data/store.php`,
`select_tasks.php`, `join.php` and `main_parse.php`. No discovery/tokenizer, converter,
framework or target implementation change is required for this component. The generic
compile Step lifecycle is not reintroduced here; later session/scheduling work can
compose these explicit typed operations.

## Identity and selection

The rewrite currently supplies fresh Source_Texts/Lexical_Project snapshots, without
persistent source IDs. Parser membership therefore uses exact supplied nonempty paths;
current positions are snapshot-local, not persistent file or semantic IDs. Normal
pipeline paths come from the existing canonical discovery/read stages. This owner
performs no filesystem lookup, path normalization or cross-path deduplication.
Duplicate paths and invalid entry positions are rejected before planning work.

Selection parses added files, changed source bytes, invalid lexical buffers and all
files on a forced rebuild. Removed paths are omitted by following current membership.
For unchanged bytes at the same path, the previous syntax arena and definition IDs
remain valid. Identical token objects reuse the exact Parse_Result. Fresh token
objects instead receive a new Parse_Result paired with the current tokens/source,
sharing the old immutable arena and copying the definition-ID vector. No retained
result is rebound in place. Changed whitespace still requires parsing because byte
spans change, even when logical comparison later reports equivalent syntax.

This is parser-level reuse. Discovery and tokenization still produce fresh observations;
this change does not claim end-to-end incremental source scanning/tokenization. The
parser compares source bytes directly, costing up to the unchanged input size. A
future measured snapshot/version cache belongs with its actual input owner, not an
invented identity inside this parser. Renames are removal plus addition.

## Worker/join contract

Parser_Selection returns a Parser_Plan whose current positions partition into task
indices and retained results. Treat the plan, token/source snapshots and published
syntax as immutable. Workers receive only the selected token buffers and call the
existing File_Parser. They do not access the join accumulator.

Frontend_Join captures a plan and validates it lazily: unique in-range tasks,
nonoverlapping retained positions, exact current token identity and complete current
membership. merge accepts a zero-based index/count segment of worker results. It
validates the whole segment before adoption, rejecting invalid ranges, duplicate,
unselected, removed, failed or stale-token outputs. Range checks reject invalid
indices before subtraction, avoiding dependence on short-circuit evaluation.

finish requires every selected result and produces current-order membership,
excluding removals. Incomplete finish preserves accepted segments so later results
can complete them. Repeated finish is deterministic and shares the same accepted
worker results; it may construct a new set container. Empty segments are allowed.
Frontend_Set checks file-root/entry/definition-index consistency, not every descendant
or semantic role. Accepted parsed results are trusted beyond that boundary.

The sequential Parser entry uses precisely these workers and join. A syntax/lexical
failure exposes diagnostics without partially publishing successful earlier files.
The previous snapshot remains available for repair. No native multithreaded execution
or scheduler policy is claimed by the independent-worker/segmented-join proofs.

## Validation

The focused tests adapt guarantees from the retained `parse_updates.php` and parser
lifecycle tests: cold/warm/full selection, changed/added/deleted/reordered membership,
exact worker identity, rebinding to fresh inputs, out-of-order segmented joins,
atomic rejection, stale and unselected results, lexical/syntax failure and repair.
They compare intended outcomes rather than replaying the old persistent-ID/session
model. Nine host serialization assertions additionally check input/retained-output
purity and unchanged prepared join state after rejected segments.

```sh
python3 compiler/tests/parser_project/run.py --results FRESH
python3 compiler/tests/parser_project/run.py --results FRESH_NATIVE --target-checkout TARGET
```

See [checkpoint evidence and timing](../planning/compiler_migration/results/parser-project-01/README.md).
The complete compiler CLI, generic session lifecycle and semantic stages remain
outside this component. src-runtime-preparation stays PHP as-is.
