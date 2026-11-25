# Uncommitted-work preservation — 2026-10-20
Doc Status: planning

Base revision: `43a2b4ed`. This cleanup preserves the five previously untracked
files byte-for-byte, and archives the one uncommitted test change before restoring
the committed test. Original SHA-256 hashes are in `original_hashes.json`.
No branches were merged, deleted or pushed as part of this step.

## Preserved drafts

- `compiler/my-try/docs/agent_cheat_sheet.md`: explicitly labelled draft and not
  ready for agent routing. Its inventory is not current architectural authority.
- `specs/planning/documentation_evidence_commit_groups_2026_10_18.md`: historical
  planning snapshot. Its file counts and proposed groups describe October 18,
  not the current worktree or a list of work still to commit.
- `compiler/src/04_analyze/analyze_lifetimes/data/exports.php`, `export_worker.php`
  and `export_join.php`: unfinished lifetime-export verification draft. Retained
  at their original paths; no integration, registration or implementation changes
  were made. Their classes currently reference each other, but no consumers were
  found elsewhere in `compiler/src` or `compiler/tests`. Syntax checks are not a
  behavioral or native-portability acceptance claim. Future integration requires
  review of ownership, contracts and meaningful behavioral proofs.

## Cleanup-test restoration

`dependency_cleanup.patch` preserves the uncommitted change against the base
revision. It replaced checks of every observed lookup (including shared subscribers
and orphan cleanup) with a check of only the last lookup and assumed it had no
remaining subscribers. That assumption fails for a legitimately shared lookup.
The stronger committed test has been restored exactly; no compiler change is needed.
Apply the archived patch only deliberately for investigation, not as an accepted fix.

Validation results are retained in `validation.json` after the focused shared-runner
checks. This preservation commit does not claim the unfinished drafts are working.
