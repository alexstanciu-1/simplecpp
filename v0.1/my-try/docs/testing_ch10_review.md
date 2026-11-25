# CH10 test-duration review — 2026-10-07
Doc Status: supporting

## Evidence and limits

Reviewed `/tmp/my-try-ch0809-native-20261006/logs-15/commands.json` and its
`summary.json`: successful acceptance wall time 1,589.296 seconds, 12 jobs, PCH,
CLI fallback. The 0.83-second native-build command reused cached output; it is not
a clean native build timing. Journals have individual durations but no interval
timestamps: concurrent durations below must not be summed into elapsed wall time.
Total development time was not instrumented, so no claim about the user's 30%
testing/work-time threshold can be made.

| Command group | Count | Aggregate process seconds |
| --- | ---: | ---: |
| Translation-unit compilation (including runtime object) | 2,918 | 4,658.63 |
| Linking or combined compile/link | 2,159 | 2,356.41 |
| PCH creation | 1 | 2.63 |
| Native compiler export/tests | 1,538 | 1,609.59 |
| PHP export/tests | 1,573 | 427.62 |
| Executions and other commands | 2,159 | 163.15 |

Clang has 5,078 invocations: 2,917 unit commands, 1,463 separate links and 698
remaining commands including combined builds/PCH/runtime. 3,606 use `-include-pch`;
links correctly do not. This is primarily a large generated-program workload,
not evidence of a slow native compiler build. The largest native export was
`data_large` at 64.921 seconds; isolate it before proposing compiler optimization.
All 1,573 PHP commands used CLI; FPM benefit cannot be quantified from this run.

## Exact explanation of the supplementary-probe increase

The runner excludes probes by exact emitted bytes plus exit code. Of the executed
supplementary probes, **471** match their already-executed same-name baseline after
normalizing only the artifact source-path identifier from `s2s.phs` to `main.phs`.
Thus **997 - 471 = 526**: the increase is explained by transport identity, not 471
new assertions. These duplicates account for 1,884 commands and 1,850.60 aggregate
process seconds (not an elapsed-time saving estimate). Other probes have distinct
instrumentation/outcomes and must remain. The complete S2S fixture manifest has
1,409 entries / 1,385 unique exact byte-and-exit pairs, another smaller deduplication
opportunity; do not add its saving to the first count without checking overlap.

Proposed bounded fix: derive a test-only normalized artifact identity for comparing
baseline and supplementary fixtures. Preserve every source unit's text, relative
include graph, assertions and expected exit. Prove duplicate elimination alongside
counterexamples with distinct assertions/exits. Do not rewrite production artifacts,
merge production units, or drop host/native parity. Not implemented in this review.

## Small matched PCH check

`/tmp/ch10-pch-review-2/` recompiles three identical exported programs with the same
compiler/flags, three concurrent tasks, PCH off then on. Each has two units and one
link; all six executions pass. Single samples, sequential modes and warm OS caches
mean this is illustrative, not a controlled benchmark.

| Fixture | No PCH task seconds | PCH task seconds |
| --- | ---: | ---: |
| class shortcut | 1.74 | 1.88 |
| struct shortcut | 2.06 | 1.97 |
| container constructor default | 3.80 | 2.49 |

PCH setup costs 2.496 seconds; per-run runtime-object setup costs 7.075–8.457
seconds, excluded from task rows. Execution is 0.003–0.004 seconds and links about
0.24–0.26 seconds. PCH helps the container-heavy case; tiny partitioned programs
still pay multiple compiler startups and links. Cache compatible runtime objects
and retain partitioned output; do not infer a universal PCH speedup from one fixture.

## Handoff

Review gate satisfied. Prioritize transport-aware fixture deduplication, then safe
runtime-object reuse and phase interval timestamps. Coordinate broader cache/PCH
optimization with CH15 debt. Native CH10 validation remains separate and must
include the chapter's dedicated record/method/constructor suites. No native compiler
rebuild, coverage reduction or runner behavior change was performed here.
