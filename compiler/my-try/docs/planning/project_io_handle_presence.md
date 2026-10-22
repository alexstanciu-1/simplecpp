# Project IO selection and concrete handle presence
Doc Status: planning

## Agreed goal — 2026-10-22

Repair S02-H01 and VR08-H02 through existing project-selection and compiled
presence owners. Preserve snapshots, once-only evaluation and closed-versus-absent
handle semantics. Verify on dev002 with default prelowering. No runtime changes,
full matrix, other modules/platforms, weak-handle extensions or VR08-H01 repair.
Commit and push each checkpoint; bring wider ownership changes back for discussion.

## S02-H01 — implemented and verified

The shared project configuration validator now admits explicit `io` selection.
The my-try project adapter loads the existing module metadata and core dependency;
no IO metadata copy, new registry or separate provider compilation is added.
Default json/filesystem/datetime selection remains unchanged, and unknown module
names still reject. IO remains opt-in.

Dev002 evidence under `/home/alex-ai/curl/`:

- `20261022-s02-project-01`: all six declaration-only S02 companions pass through
  `--project`, matching the original output/error/exit oracles. All publications
  forbid application templates; warm builds compile zero units without linking.
- `20261022-project-io-selection`: IO-only selection, omission rejection, unready
  build rejection, explicit empty selection, unknown-name rejection, unchanged
  defaults and IO restoration pass through one publication/cache. Runtime provider
  sources are unique and core/runtime.cpp occurs exactly once. Each positive state
  runs cold/warm; warm builds compile zero units without linking.

This closes the project-entry gap. It does not enable legacy comment declarations;
S02's strict-milestone exclusion and original compatibility evidence remain intact.

## VR08-H02 — implementation under verification

Prepared strict null comparisons now use the existing compiled presence operation
under default prelowering, rather than calling native has_value on concrete storage.
Both operand orders retain the correct source type and return the concrete boolean
representation. Ordinary lowering and semantic admission remain unchanged.

Initial controls pass for nullable int (including present zero), source shared
handles, unique moves and temporary/once-only results. IO/GUI fixtures are being
verified separately, including closed handles remaining present until cleared.
A control fixture needed whitespace before `=` after a generic type. Another
incorrectly expected an error Result from reading a closed IO resource; the runtime
correctly throws. These fixture mistakes do not authorize runtime changes.
