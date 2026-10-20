# Fixture repairs 02
Doc Status: planning

Authorized 2026-10-19. Completed and verified for V-H04, V-H08, V-H09 and V-H11.
The completed seven-fixture batch is baseline input, not work to repeat.

## Scope and acceptance

- H04: property effect result/store/call count 3:3:1; mixed and dynamic postfix
  snapshot 3:2; scalar-boxed indexing fails at runtime with original 7 unchanged.
  Use existing H01 observer support where needed. Preserve other negatives and
  incremental recovery; run the affected property/dynamic suites.
- H08: replace obsolete scalar concatenation rejection with exact native bytes
  a2|b1|c|d. Preserve invalid interpolation grammar/types and recovery checks.
- H09: generate exactly the 39 audited stable row IDs: 12 count_value,
  14 empty_keyed, 13 empty_value. Verify unique complete coverage, six preparation
  rejections with no artifacts and 33 runtime rejections with precise error/state
  observations. Execution is limited to these rows, positive controls and relevant
  generator regressions. No full matrix run. Preserve shared runtime contracts;
  v0.2 preparation policy must not overwrite v0.1 runtime expectations.
- H11: both unique-copy construction and assignment fixtures must fail compilation
  at the intended operation under GCC and Clang. Keep symbol/operation-specific
  checks and valid ownership controls; an unrelated compile error is not success.

## Execution boundaries

Use existing fixture/generator owners. No compiler/runtime fixes, new semantics,
other issue repairs or native compiler parity work. Preserve unrelated changes and
original audit evidence. Record independently exposed blockers without widening
scope. No follow-on goal starts automatically.

All test execution runs on alex-ai@dev002.travelfuse.ro using isolated snapshots,
shared runners and at most 32 total workers. No tests run on WSL. Record commands,
source/toolchain hashes, per-issue outcomes and remaining limitations. The
pre-repair file hashes and dirty worktree are in fixture_repairs_02_baseline.json.

Implementation order: H04, H08, H09, H11. Completion requires all four bounded
acceptance checks, affected-suite outcomes, and updated repair/issue reporting.

## Verified result — 2026-10-19

All four repairs passed on dev002. No remaining blocker was exposed within this
bounded batch. Compiler/runtime source hashes match the pre-batch snapshot (445
protected files); all 6,182 final snapshot files match the local tested sources.

| Issue | Change and proof |
| --- | --- |
| H04 | Class/struct effectful property updates assert `3:3:1`; mixed/dynamic postfix asserts result 3 and final value 2. Scalar indexing asserts the runtime error and original value 7. Property mutations passed 14 native programs; dynamic families passed 14 PHP suites and 64 native programs, retaining remaining negatives/recovery. |
| H08 | Removed only the obsolete scalar-concat negative and added exact native output `a2|b1|c|d`; remaining interpolation grammar/type/recovery assertions passed. |
| H09 | Six TSVs now seed exactly the 39 audited IDs. The existing emitter enables these runtime-negative probe families. Permanent generator regression checks exact IDs, no missing/duplicate seeds and 39 enabled emitted tests. The strict compiler adapter verifies six preparation rejections with empty artifacts and 33 runtime errors with unchanged payload/state. Count/empty positive controls and existing emitter regressions passed. |
| H11 | Two metadata expectations accept `deleted` while retaining the exact deleted constructor/assignment declaration. GCC 13.3 and Clang 18.1 each rejected both forbidden operations and passed the move/release/reset control. |

H09 preserves shared runtime semantics (`supported` / `throws` / `negative_runtime`).
Only the v0.2 test adapter expects earlier preparation rejection for six statically
invalid scalar `empty` cases. It derives literals and expressions from emitted tests,
then uses strict declarations. Family data validation checks completeness, but only
the selected 39 rows are emitted/executed; no full matrix execution occurred.

## Reproduction and evidence

Remote snapshots: `/home/alex-ai/fixture-repairs/20261019-03` for H04/H08/H11,
`/home/alex-ai/fixture-repairs/20261019-04` for H09. PHP 8.5.11, Python 3.12.3;
use `/home/alex-ai/language-verification/20261018/verification/modern-bin` on PATH.
PHP CLI fallback was used where the installed FPM version differed. All commands
used the shared runner. No tests ran on WSL and no native compiler rebuild was claimed.

Repeat affected suites from the repository root with fresh result directories:

```sh
python3 compiler/my-try/tests/records.py --suite property_mutations --results FRESH_PROPERTY
python3 compiler/my-try/tests/dynamic_families.py --results FRESH_DYNAMIC
python3 compiler/my-try/tests/records.py --suite interpolation --results FRESH_INTERPOLATION
python3 compiler/my-try/tests/probe_matrix.py --results FRESH_PROBES --jobs 12
```

H11's two-compiler harness is archived as
`20261019-03/verification/h11.py`; the corrected executed harness is preserved as
`20261019-03/verification/h11-02/executed-harness.py` in the evidence archive. The commands and stderr for all four forbidden
operations and both valid controls are in `verification/h11-02/*/logs`.

[Results](fixture_repairs_02_results.json) contain per-row outcomes, suite summaries,
toolchain versions and evidence hash. [Evidence](fixture_repairs_02_evidence.json.gz)
contains command logs, exact outputs, generated metadata and source-integrity reports.
Source manifests: [initial suites](fixture_repairs_02_source_01.json),
[initial H09 transfer](fixture_repairs_02_source_02.json),
[final H09 sources](fixture_repairs_02_source_final.json).

Two harness retries are preserved: H11 initially collided on a shared command-log
name; each compiler now has its own runner/log directory. H09 initially passed an
incomplete family to the completeness validator; validation now precedes selection.
Neither required a compiler or runtime change. Original verification audit counts and
failures remain historical evidence. Other language issues remain outside this goal.
