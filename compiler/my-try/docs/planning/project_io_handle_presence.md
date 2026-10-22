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

## VR08-H02 — implemented and verified

Prepared strict null comparisons now use the existing compiled presence operation
under default prelowering, rather than calling native has_value on concrete storage.
Both operand orders retain the correct source type and return the concrete boolean
representation. Ordinary lowering and semantic admission remain unchanged.

Dev002 acceptance covers strict `===` and `!==` against null in both operand
orders for nullable int, source shared/unique handles, IO resources, ui_app,
ui_window, ui_event and webview. Absent, populated, copied, cleared and temporary
values preserve snapshots and once-only evaluation. Closing IO/window/webview or
exiting the app preserves handle presence; explicit null assignment clears it.
Presence is not a test of resource usability.

- `20261022-handle-null-controls`: nullable/source-handle and unique-move controls.
- `20261022-handle-null-runtime-final`: IO and enabled GTK/WebKitGTK handle proofs.
- `20261022-handle-null-retained`: six publications, four invalid-comparison
  rejection/recovery cycles and fresh/retained equivalence.
- `20261022-handle-null-gui-regression`: native smoke plus four existing GUI cases,
  including the restored original `$view!==null` snapshot assertion.
- `20261022-handle-null-contracts`: existing nullable, ownership and runtime-handle
  PHP contract suites pass.

All executable publications above use default prelowering, forbid application
templates and pass cold/warm builds; warm builds compile zero units without linking.
GTK 3.24.41 and WebKitGTK 2.52.6 were used on dev002. No runtime implementation,
native compiler rebuild or full matrix change/run was needed.

Initial fixture failures remain archived: whitespace was needed between a generic
closing `>` and `=`, a closed IO read throws rather than returning an error Result,
and GUI stdout needed isolation from display diagnostics. Acceptance corrects these
fixture assumptions without changing runtime semantics.

## Closeout

Both supplementary findings are repaired. The original ledger stays at 64/64
strict-milestone dispositions: this adds no original issue closures. Legacy syntax,
VR08-H01, weak-handle extensions and other GUI platforms remain separate.

[Compact results](project_io_presence_results.json) and the
[evidence archive](project_io_presence_evidence/README.md) preserve final source
hashes, summaries, commands, historical fixture failures and publication evidence.
