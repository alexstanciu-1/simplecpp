# Convertible-PHP rewrite reset
Doc Status: supporting

The user authorized the reset before restarting manifest reading. The reset
preserves tools, runtime support, skills and accumulated evidence; it does not
rewrite a compiler component or introduce functionality.

## Preserved checkpoint

- Commit: `623402d05e066bb5bef12c1439472a0a7f376b10`.
- Local reference branch: `v0.2/pre-rewrite-reference` (no remote push).
- Disk reference: `compiler/reference/pre-rewrite/` contains the former compiler
  tree, including implementation, runtime preparation, test corpus, tools, docs,
  examples, original-source reference and bundled history.
- Every one of its 2,987 previously tracked compiler files was byte-verified after
  relocation. Hash inventory: `results/rewrite-reset-01/preservation.json`.
- Compiler-bound portability harness/oracles moved to
  `tests/portability/reference/pre-rewrite/`. Their original paths and runnable
  environment can be recovered from the Git checkpoint. Independent converter,
  runtime and capability tests remain active.

The archived source is frozen reference, not a second compiler under development.
Its relative launch paths were not rewritten to make a second runnable workspace.
For replay, create an isolated checkout of the reference commit; the original
external prototype remains untouched. Existing historical docs/evidence describe
those checkpoints and must not be read as current rewrite readiness.

## Active state

`compiler/src/` is a clean stage scaffold, following the prototype's numbered
pipeline. `compiler/src-runtime-preparation/` and `compiler/tests/` are empty
scaffolds for later implementation. There is no active compiler bootstrap/CLI yet.
The active `compiler/portability.json` has zero files. Retained tool configuration
includes the unchanged a1a1babd implementation pin; it does not establish compiler
readiness by itself.

The default validation driver now runs independent framework checks and explicitly
reports `compiler_ready_files: 0`, `compiler_status: not_started`. A requested
compiler native proof fails with a clear message. The active compiler proof
entrypoint also fails explicitly; it does not run an empty project or the old
39-file fixture. Register the first stage's real behavioral proof before repopulating
the ready manifest. Other native capability proofs remain selectable.

The prior 39-file cumulative result is historical, not reset, deleted or relabeled
as new coverage. Future stage integration should compare meaningful outcomes and
reuse the old corpus deliberately rather than automatically inherit every internal
identity or byte-output expectation.

## Next component

Project manifest reading and normalized project records, then path resolution and
file discovery, verified source reads, tokenization and parsing. Check #240's actual
candidate/API status when selecting the JSON boundary; preserve schema distinctions.
Independent input-preparation work may continue if native JSON proof is blocked.
The current request stops at reset and verification; manifest implementation is next.
