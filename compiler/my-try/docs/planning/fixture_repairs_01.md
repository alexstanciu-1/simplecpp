# Fixture repairs 01
Doc Status: planning

Completed 2026-10-19. The authorized seven-issue batch is implemented and verified
on dev002. No compiler/runtime fixes or other issue repairs were made.

## Changes and acceptance

| Issue | Repair | Evidence |
| --- | --- | --- |
| V-H01 | Update exact S2S entry expectation; opt the 20 named renamed-entry observers into the existing test artifact boundary helper | All 20 native observers pass; helper checks preserve source catches, observer catches, ordinary production entry and uninstrumented transport in single/partitioned forms |
| V-H02 | Include Exception registration in five catalog totals | Original type-catalog assertions pass, including identity/reuse and demand-only specialization |
| V-H03 | Update coalesce and conditional-join diagnostic expectations | Original rejection, withheld-output, independent-body and repair checks pass; nullable suite executes 99 generated programs |
| V-H05 | Substitute the runtime-catalog path in the test driver | Artifact/host transport agreement, invalid-source rejection and eight PCH/non-PCH generated-program runs pass |
| V-H06 | Use native string value comparison in fixtures/container_calls.php | Indexed-container state observer passes with the same expected value and container assertions |
| V-H07 | Include complete table definitions in JSON observers | Both mixed/dynamic failure observers pass; complete JSON suite passes |
| V-H12 | Remove the obsolete closure-expression parser negative | Original parser suite passes; remaining structural/grammar/incremental assertions retained |

The H01 change is owned by tools/cpp_artifacts.py, the existing test transport
helper. Explicit test-only markers authorize removal of the marked outer production
catch; source handlers are preserved. Production compiler emission is unchanged.

## Verification

All test execution ran on alex-ai@dev002.travelfuse.ro. Two suites at a time used
at most 24 workers through the shared runners. PHP 8.5.11, Clang 18.1.3 and Python
3.12.3 were recorded; the runner used CLI fallback because CLI/FPM versions differ.

| Suite | Final outcome |
| --- | --- |
| Full S2S export and native execution | 1,409/1,409 cases passed |
| Type catalog and data parser | Both original PHP suites passed |
| Artifact transport | Passed, including eight generated-program runs |
| Nullable | Passed: 11 PHP suites and 99 generated programs |
| JSON API | Passed: nine PHP suites and seven generated programs |
| Control flow | Passed: 12 PHP suites and 135 generated programs |
| Functions | Passed: 12 PHP suites and 77 generated programs |
| Runtime strings, generics, source and handles | All four runners passed |

These suite counts overlap; they are not a unique aggregate coverage total.

The initial nullable attempt failed because only its coalesce diagnostic had been
updated. The archived H03 adaptation also corrected the conditional-join message.
That second correction was applied and verified in a separate snapshot. The first
attempt summary intentionally remains failed; it was not overwritten or relabelled.

## Provenance

- First snapshot: `/home/alex-ai/fixture-repairs/20261019-01`.
- H03 follow-up: `/home/alex-ai/fixture-repairs/20261019-02`, differing only in nullable.php.
- Both 6,178-file snapshots match their recorded hashes. Compiler/runtime source
  hashes match the completed verification baseline; pre-existing unrelated edits
  remain intact.
- [Pre-repair worktree record](fixture_repairs_01_baseline.json).
- [First snapshot](fixture_repairs_01_source.json) and [follow-up snapshot](fixture_repairs_01_source_retry.json).
- [Per-issue results and exact commands](fixture_repairs_01_results.json).
- [Archived logs, source integrity and toolchain records](fixture_repairs_01_evidence.json.gz).

No blockers remain for these seven repairs. H04/operator fixture migration,
production JSON metadata V-R01, matrix seeds and catalog prose remain separate work.
JSON observer success does not repair production provider linkage. Generated-program
execution does not establish native compiler parity. No follow-on goal starts automatically.
