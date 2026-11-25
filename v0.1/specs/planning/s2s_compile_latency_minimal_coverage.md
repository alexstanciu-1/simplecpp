# Per-type policy: historical coverage and compound-change audit
Doc Status: planning

Date: 2026-09-20

## Completed coverage

All twelve runnable code cases from the two frozen history samples pass under the
new general per-type policy. The 36 native trials all meet the original 8.5-second
native target, with no PCH rebuilds. Code-case medians range from **1.593 to
4.815 seconds**, or **3.093 to 6.315 seconds** including the assumed frontend.
The slowest individual trial is 5.151 seconds native, or 6.651 seconds total.

| Sample | Runnable code cases | Native median range | Other results |
| --- | ---: | ---: | --- |
| Original fifteen commits | 9/9 pass | 1.871–4.815 s | One comment-only no-op; four context limits; one incompatible before-state |
| Independent next fifteen | 3/3 pass | 1.593–3.358 s | Twelve context limits |

The comment-only case has a 0.082-second native median. The incompatible
`4abe635f` before-state fails with the same missing `append_literal_return` member
as the previous policy. The twelve cases are runnable exact-context historical
edits, not a probability estimate for arbitrary editor saves. The two coherent
historical replays use a separate protocol and retain their earlier measurements.

Each accepted code case passes full linking, before/after smoke and literal
LLVM/native exit42 checks. These generic witnesses do not exhaustively validate
every changed compiler feature. Sources are restored after every case. The
Clang/debug/mold/cache-disabled/twelve-job setup is unchanged. Native scheduling
and linking are timed; generation is excluded, and its allowance remains an
assumed 1.5 seconds. Output-policy and replay-driver hashes were frozen during
the sample. Earlier-policy timings are retained separately: some ordinary cases
are slower with the new shape, but all tested cases retain substantial headroom.

## What remains in the compound exception

Exact generated snapshots and dependency manifests match the previous timed
`9103637e` reconstruction. That replay remains **32.680 seconds native**, a single
successful slow screen, not a newly measured median. Its 78 objects divide as
follows:

| Generated-input category | Objects | Aggregate compiler-job elapsed time |
| --- | ---: | ---: |
| Body or other C++ changes | 41 | 184.509 s |
| Function-signature-only changes | 15 | 76.131 s |
| Location metadata | 14 | 46.346 s |
| New C++ unit | 1 | 2.844 s |
| Unchanged C++ with changed dependencies | 7 | 40.229 s |

There are no includes-only or whitespace/comment-only object changes in this
classification. Categories compare exact text, then selected structural text;
they do not establish semantic equivalence or prove that every body change is
unavoidable. Job elapsed times include contention and are not CPU measurements.

All seven unchanged callers have real changed callable headers in their include
paths. Six consume `source_units::table_from_manifest`; the seventh consumes two
frontend-payload helpers. Their C++ parameter changes replace
`const shared_p<T>&` with `shared_p<T>`. These are real interface/ABI changes,
not parameter-name churn or unrelated type imports. Skipping those caller builds
would not be correct under the current C++ interface. A different stable calling
convention would need explicit aliasing, ownership and lifetime proofs.

Only two type definitions appear/change (`ScalarConditionOperand` and
`ControlFlowLocalWriteSet`); neither explains the seven unchanged callers.
The general type-publication boundary has removed the prior global invalidation.

## Historical context matters

A bounded source-method audit finds 202 signature-only changes, 101 body-or-mixed
changes and seven added methods. It recognizes single-line public static PHS
method signatures and compares exact body text; it is not a full semantic parser.

The exact historical plan addition records three combined activities: condition
and dataflow row ownership changes, replacing long chained string materializers,
and mechanical `const T &$`-to-value signature cleanup to bypass then-current
parsing limitations. Its 194544 ms Ninja entry followed a clean build; that log
entry is not an incremental benchmark. Our 32.680-second number is the separately
measured coherent forward edit. This is a substantial compatibility/ownership
cleanup, not a representative single-method save. Its real frequency in ordinary
development remains unmeasured. On 2026-09-20 the user explicitly accepted this
approximately 30-second result as well as the common-edit results; further
optimization of this case is optional for the accepted assessment.

## Compiler traces identify the next experiment

Two isolated diagnostic compiles use the captured historical units/headers, the
same compiler flags and runtime-only PCH. They are not native save-latency trials.
Clang reports:

| Unit | ExecuteCompiler | Frontend | Backend |
| --- | ---: | ---: | ---: |
| Frontend-payload group 30 | 2.631 s | 2.365 s | 0.238 s |
| Backend-entry group 1 | 2.432 s | 2.062 s | 0.348 s |

Frontend work dominates at roughly 85–90%. Function-instantiation totals are
1.535/1.369 seconds, and class-instantiation totals are 0.717/0.667 seconds.
These trace categories overlap; do not add them. Repeated large events include
standard formatting and a callback-map specialization used by PHP regex adapters.
The earlier commentary's generic registry label was inaccurate; source inspection
identifies `lang/php/php_regex.hpp` callback adaptation.

The actual benchmark runtime umbrella includes that PHP regex header even with
`SCPP_HAS_REGEX=0`. It also gathers a broad runtime surface. These observations
justify an experiment with narrower required-runtime headers and reduced repeated
template work. They do not establish which other headers can safely be omitted
or predict an end-to-end speedup. The 14 primitive location-data units also merit
a lightweight compile rule without the full runtime PCH. Neither optimization
has been implemented or timed in this round.

Next audit actual runtime dependencies of the two traced units and the location
data, then test a separate candidate using existing narrower headers where
possible. Preserve original definitions, diagnostics and full-program behavior.
Do not treat changed parameter-passing semantics as an ignorable rebuild trigger.
Broader excluded history, including full `7d58f273`, remains outstanding.

## Evidence and restoration

`run_coverage_replay.py --minimal-types` selects the minimal mirror/executable;
prior defaults are unchanged. `run_coherent_history.py --inspect-only` captures
only reachable generated files and labels its result inspected, with no new
historical timing/correctness claim. The analyzer requires byte-identical graphs
and dependency manifests relative to the timed states before attributing work.

Final source-integrity, baseline-graph, idempotence, no-op, representation and
adapter checks are recorded alongside the results. Original source remains
read-only. See [the complete evidence archive](../../tools/compile_latency/results/2026-09-20/minimal-coverage/)
for sample exclusions, all trials, exact generated/header diffs, source audit,
compiler traces, frozen policy snapshots and restoration checks.
