# Release 0.1.77 validation
Doc Status: planning

Date: 2026-09-22. Consolidated release for #231, #232 and #233.
Production source: seven commits from `394164c0` through
`361b1e9752817cd5924a9117fba806c5928bb406`, integrated through PR #234.
Release preparation changes only version/release documentation and agent guidance.

## Evidence

- Implementation CI passed on the final production commit:
  https://github.com/alexstanciu-1/simplecpp/actions/runs/35687394614
- Integration PR #234 CI passed on Linux, macOS, Windows and Android:
  https://github.com/alexstanciu-1/simplecpp/actions/runs/35688161316
- Fresh strict/PHS nested-vector field build/run regression passed.
- Focused collection typing, runtime type declarations, inheritance references,
  and strict runtime catalog tests passed on the consolidated production tree.
- Native collection/adapters, process, file-lock, filesystem and snapshot checks
  passed. The #231–#233 issue comments preserve the original detailed mutation,
  cleanup, type rejection and source/native fixture evidence.
- Strict/PHS build/run regressions previously passed for collections, processes,
  file locks, runtime alias signatures, nested vector fields, qualified
  inheritance, host paths and snapshots on this same production tree.
- Both repo-local agent skills reviewed. Strict PHP++ guidance updated for the
  new APIs and lowering fixes; JSS guidance explicitly avoids inferring reserved
  helper spellings or frontend coverage from PHS support.
- Both skills passed the skill-creator quick validator; `git diff --check` passed.
- Release PR CI must pass before merge and publication. General hosted Windows
  smoke coverage does not prove native execution of the new host-fact helper.

## Release checks and limits

VERSION.txt and CHANGELOG.md select 0.1.77. The published release body is extracted
from the checked-in changelog. Tag v0.1.77 must point to the release merge on main;
develop must receive the release bookkeeping afterward.

New OS resource backends are Linux-only. Snapshot checks preserve whole-second
mtime/size limitations and do not promise atomic reads. Process deadlines require
polling and capture uses regular files. Existing cross-static-type object identity
behavior remains unchanged. No v0.2 migration workspace changes or new JSS API
coverage are included. See the per-family normative contracts for exact limits.
