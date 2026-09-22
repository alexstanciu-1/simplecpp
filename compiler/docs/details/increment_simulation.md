# Increment simulation
Doc Status: supporting

```sh
src/.prism/build/main project/project.json --simulate-increment project-edited/ --debug=json
```

The manifest's containing directory is the project folder. The edited directory
must be a distinct, non-nested copy with the same manifest filename. Simulation
runs twice in one process at the same absolute manifest/source paths. It retains
the first input snapshot for the second refresh; it does not publish a fake
compiler generation or clear unfinished frontend work.

The [simulation driver](../../reference/original-phpp/src/simulate_increment/run.phs) performs:

1. Reserve `<project-folder>.scpp-simulation/` beside the project and write its
   `journal.jsonl` recovery note.
2. Run the common manifest/discovery prefix on the original project.
3. Rename the original folder to `.scpp-simulation/original` inside that reservation.
4. Rename the edited folder to the original project path.
5. Run the same prefix against the retained first inputs.
6. Rename the installed copy back to the edited path, then the backup back to
   the original project path. Remove the journal after restoration.

The [swap owner](../../reference/original-phpp/src/simulate_increment/store.phs) appends and flushes a
stage record before each rename and records completion afterward. It never
intentionally overwrites an occupied destination. Normal completion and unwound
exceptions restore the folders through the same methods. A killed/crashed process
can leave the folders swapped; the journal remains outside both trees.

Use exclusive access to both folders during simulation. Cross-filesystem renames
or platform restrictions on open directories can fail; there is no copy fallback.
Only the two named trees move; configured external source folders stay in place.
Keep unchanged files' timestamps when preparing the edited copy. Renames preserve
metadata, so the existing [mtime/size checks and timing limitation](incremental_refresh_rules.md#inputs-and-comparison-baseline)
still apply. No hashes, timestamp rewriting, or synthetic dirty flags are added.

## Recovery after a process crash

Both ordinary CLI runs and new simulations refuse a pending journal for the
named project folder. Stop the previous process before recovery. The first
complete journal line identifies the absolute `project`, `edited`, and `backup`
paths and includes restoration instructions. Later lines record progress.

Inspect actual directory presence: a crash may happen on either side of a rename
after its intent was recorded. Ignore a partial final journal line.

| Project | Edited | Backup | Restoration |
| --- | --- | --- | --- |
| Present | Present | Absent | Already restored, or no rename started. |
| Absent | Present | Present | Rename backup to project. |
| Present | Absent | Present | Rename project to edited, then backup to project. |
| Any other combination | | | Stop and inspect; do not overwrite anything. |

After both folders are back and the backup is absent, remove `journal.jsonl` and
its empty directory. If the process died before completing the initial note,
no project rename started. If it died after removing the journal, an empty
reservation can remain after restoration. Never recursively delete the
reservation: it may contain the original project. Writes are flushed/closed for
process-crash recovery; power-loss durability has not been established.

## Current proof boundary

With `--debug=json`, output is one object containing `runs`, an ordered pair of
input snapshots with `manifest`, `sources`, and the early `full_rebuild` decision.
The initial run selects full work. Manifest changes and removals select full
work on the second run through the normal coordinator rules. A false decision
is provisional until the future parse/resolve gate establishes semantic impact.
Without debug, successful execution is quiet. Output is emitted after restoration.

In the active PHP prototype, each run also reads source snapshots, tokenizes and
parses, collects project symbols, resolves call names, compares logical syntax,
resolves signatures, checks callable bodies and analyzes scalar lifetimes. The result reports `completed: false`,
`stopped_before: "build_native"`, with source metadata, token and AST exports
under `inputs`, declaration/change exports under `symbols`, and bindings under
`resolutions`, shared types/signatures under `types`, typed bodies under `bodies`, and per-value lifetime facts under `lifetimes`. The
[prototype checks](../../tests/simulate_increment/increment_simulation.py) exercise lexical
updates, completed change classifications and the same recovery boundaries. Broader type resolution, native startup/backend work and publication
remain unfinished. In the original PHP++ reference, source reading/tokenization
are still absent. Its [checks](../../reference/original-phpp/tests/increment_simulation.py) verify
actual two-scan behavior and restoration, plus forced process termination after
each swap boundary using the production swap owner in a native test probe.
