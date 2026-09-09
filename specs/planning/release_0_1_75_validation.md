# Release 0.1.75 validation
Doc Status: planning

Date: 2026-09-09. Release source: `release/0.1.75` from `develop` merge
`d2394624` (approved integration PR #227). That merge has the exact tree of
validated candidate `208b6015`. Release changes are limited to VERSION.txt,
CHANGELOG.md version/date bookkeeping, and this evidence record.

## Final candidate results

Production code was validated at `f2163acf`. Commit `208b6015` changes only the
JSS diagnostic test and adds full/fast analysis parity coverage; the complete
JSS frontend suite was rerun successfully afterward.

| Check | Result |
|---|---|
| Curated runtime baseline | 102/102 passed |
| Runtime address/undefined/leak sanitizers | 102/102 passed |
| Strict PHP++ | 124/124 passed |
| Legacy PHP++ | 246/247 passed; deferred top-level integer return defect only |
| Native CMake/CTest with mysqli, regex, curl | 26/29 passed; exactly the three deferred native defects |
| All CLI/tool scripts | 50/50 passed after the resolutions below |
| Pre-tokenizer checks | Passed |
| Both repo-local Agent Skill validators | Passed |
| Hosted candidate and integration PR CI | Linux, Windows, macOS, Android passed |

The CLI run includes managed struct fields, 20 async PHS/JSS projects, full
JSS sample/project flows, task worker pools and background errors, strict JSON
safety, source/runtime diagnostics, module enforcement, build grouping/cache/
planner behavior, runtime artifact maintenance, and UI/WebView checks. Strict
and legacy runtimes were rebuilt through the public CLI before their suites.
External-network opt-in integrations remain excluded.

Initial failures resolved during final validation:

- The build benchmark exceeded its unchanged 180-second timeout during concurrent
  compilation. The unchanged test passed under lighter load after runtime/native
  builds completed. Assertions and timeout were not weakened.
- The JSS test expected the old member-access code for missing modules. Commit
  `208b6015` expects `frontend_runtime_module` and verifies that full and fast
  analysis preserve the same missing-module explanation. Its complete suite passes.

Local logs and result inventories: `/tmp/scpp-final-candidate/` on the validation
host. Hosted evidence: [final candidate CI](https://github.com/alexstanciu-1/simplecpp/actions/runs/34319138305).
Integration review and final validation summary: [PR #227](https://github.com/alexstanciu-1/simplecpp/pull/227).

## Explicit exceptions

The existing top-level integer-return, nullable increment, result/error-sentinel
comparison, and mixed bool/int equality-contract defects remain deferred. Their
failing tests remain enabled and unchanged. Owners, alternatives, and completion
criteria are in [release_debt_disposition_2026_09_09.md](release_debt_disposition_2026_09_09.md).
The release notes retain these known limitations; this is not an all-green claim
for the legacy/native suites.

## Release branch review

- Reconfirmed that release production code and tests match the validated candidate.
- Reviewed `.agents/skills/*` on the release branch: JSON checked-result migration,
  managed struct fields and cross-file literal limitation, reusable runtime builds,
  and diagnostic guidance remain current. No further skill edit is needed.
- Checked version/date bookkeeping and release-note extraction from CHANGELOG.md.
- Full suite evidence is carried forward because the release adds no code changes.
  Release-PR CI must pass before merging and tagging from main.
