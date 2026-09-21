# Fixed-output history coverage study

Doc Status: planning

## Question and protocol

How consistently does the existing experimental S2S output meet ten-second
edit-to-executable readiness across different historical edit shapes?

Freeze the current output strategy: static callable isolation for the existing
selected owners, stable implementation partitions, profile-row isolation and the
shared-counter boundary. Keep Clang debug `-O0 -g1`, mold, Ninja twelve jobs and
compiler caches disabled. Do not add an optimization when a study case is slow.

Selection is the last fifteen non-merge commits touching `compiler/src` reachable
from pinned workload revision `9776900f6c305729ae6ef8474052363508913315`. No compile-
time filter was used. This is a recent commit sample from one project, not a
population sample of individual editor saves or all users.

For each case, reverse the exact source hunks into the current surrounding
program, establish and validate that baseline, reapply the hunks, then time the
native build through the full executable. Three equivalent trials are planned;
a first successful native build above twenty seconds ends that case's timings
with a single screening result, not a median. Force newly introduced object
outputs missing to avoid orphan-object reuse during repeated additions.

A result meets the target when native time is at most 8.5 seconds, plus the agreed
1.5-second future frontend allowance. Experimental Python/PHP regeneration and
before-hunk baseline setup are outside that native timing. Correctness checks
include full application smoke and a literal-return fixture compiled through LLVM
to native exit42 before/after; this is not exhaustive changed-feature validation.

Keep statuses separate: measured pass, measured slow, replay-incompatible,
textual-context limitation, and pending. Comment-only cases are reported separately
from executable-code edits. Do not report pending/unreplayable cases as passes,
or use passing cases alone as the denominator.

## Final fifteen-case inventory

**Nine executable-code cases were timed: three met the target and six exceeded it.**
The comment-only case performed no native compilation and is kept separate. Four
cases have nonmatching hunk context; one additional case has a semantically
incompatible before-state. No cases are silently dropped or counted as passes.

| Commit | Change | Result | Native seconds |
|---|---|---|---|
| 1a24a85d | Export backend call argument rows for FN proof | over target | 139.143 (single screen, n=1) |
| 1610af1a | Route literal return coverage through consumer plan | within target | 4.268 (median, n=3) |
| f1d3a846 | Retarget structure smoke away from fixtures | within target | 2.995 (median, n=3) |
| cec8c616 | Retarget direct call evidence wording | within target | 0.049 (median, n=3) |
| db047e10 | Retarget semantic gate to project runner | context mismatch | — |
| b54f2766 | Generalize call expression lookahead | within target | 2.622 (median, n=3) |
| 534d70a6 | Remove legacy case env fallbacks | over target | 177.867 (single screen, n=1) |
| e8e8a063 | Remove dead fixed argument LLVM materializer | over target | 141.295 (single screen, n=1) |
| b085d952 | Rename LLVM worker handoff as module composition | context mismatch | — |
| 5ce42535 | Rename backend LLVM source filename label | over target | 138.970 (single screen, n=1) |
| 43a83246 | Route caller function text through composition input | over target | 143.405 (single screen, n=1) |
| cf3c2d08 | Remove obsolete LLVM module composition delegates | over target | 139.094 (single screen, n=1) |
| 41392c03 | Route LLVM module composition through input rows | context mismatch | — |
| 4abe635f | Generalize scalar capability consumer plans | before-state incompatible | — |
| 9103637e | Correct definition-driven compiler drift | context mismatch | — |

All measured code cases passed full linking, smoke and literal-return LLVM/native
checks before and after. This is bounded validation, not an exhaustive check of
every changed feature. The slow cases use single successful screens above twenty
seconds; the fast cases use three trials. No code case straddled the threshold.

Textual eligibility is not semantic/build compatibility. `4abe635f` reverses away
`semantic_body_capability_consumers::append_literal_return`, but later code from
`1610af1a` still calls it. The C++ compiler rejects that before-state. A matching
historical/dependency-closed snapshot is required; this is not a performance fail.

For `f1d3a846`, the original `return_42` fixture was recovered from its historical
parent revision into the scratch fixture directory, so the old path could be
validated rather than failing because the assessment copy omitted that directory.
The source hunk itself was not adapted. Provenance/hashes are archived.

The preliminary `b54f2766` run did not force newly introduced native objects to be
missing. It is archived separately and excluded; the final series compiles all six
objects on every trial. `1610af1a` reuses the prior exact-hunk series on this same
output strategy rather than manufacturing a second measurement.

## Evidence and interpretation

Preserve complete statuses, native step timings, setup times, restoration checks
and validation logs. Record the extent of before/after correctness, including
cases whose changed feature still needs a stronger dedicated witness.

The earlier 146.265-second by-value trait layout screen is external stress-case
evidence, not one of these fifteen commits. Keep it visible as an exception
candidate without mixing it into the sample denominator. Similarly, prior passing
synthetic/helper/signature experiments support feasibility but do not inflate
this history sample's success count.

## Critical limit of the frozen policy

The experimental callable emitter currently targets selected helper owners; it
is not yet a generic whole-program policy for every eligible static helper class.
Therefore a slow method edit in another owner is a **policy-application coverage
gap**, not evidence that this edit inherently requires broad recompilation or that
a future uniformly applied typed emitter cannot meet the target. Report this
separately from genuine by-value ABI dependencies. These results measure the
current experimental output configuration, not an implementation that already
applies all proposed rules consistently across the program.

An additional ownership limit matters for LLVM edits: one source file contains
both helper methods and data-carrier classes. The current per-source experimental
rewriter must not be assumed safe for every such file. A general typed emitter
needs callable/class ownership independent of source-file ownership, preserving
unselected carrier methods and class identity while publishing isolated helper
artifacts. The 103-owner structural inventory excludes these more complex files;
it is neither full semantic eligibility nor a complete optimization target list.

## How to interpret the study

These are two separate questions:

1. Can the output concepts make representative edits fast? Earlier experiments
   and the passing history replays provide bounded evidence that they can.
2. Does the present experimental configuration apply those concepts consistently?
   Slow public-surface replays show that it does not. It still isolates only four
   selected callable owners, with a broader structural candidate inventory of 103.

Neither the number of candidate owners nor the fraction of passing commits is a
forecast of everyday developer experience. This recent history window is rich in
refactoring/interface edits. A defensible next comparison applies the verified
policy consistently to semantically eligible owners, reruns this unchanged corpus,
then adds an independently selected save-level/body-edit workload and reports the
remaining value-layout/export/template/inheritance exceptions explicitly.


## Conclusions from this fixed configuration

- The fast code medians are **2.622–4.268 s native**, or **4.122–5.768 s** including
  the assumed frontend. The isolated parser extraction/rename is among them.
- The six slow screens are **138.970–177.867 s native**. Each rebuilt the project
  PCH; none of the fast code cases did. Their affected public surfaces are outside
  the selected isolated owner set. The exact per-case native steps are archived.
- This therefore establishes a **consistency gap in the current prototype**.
  It does not overturn the feasibility evidence or prove these edits inherently
  require minutes. Consistent application of the emission policy remains unproven.
- Do not quote “three out of nine” as a normal-development success probability.
  The sample is small, recent, refactoring-heavy and at commit granularity, and
  only semantically replayable cases could be timed. Five replay limits remain.

The next implementation assessment should make callable/class artifact ownership
independent of source-file ownership and apply it consistently to validated eligible
owners, then rerun **this unchanged corpus**. Keep the by-value trait layout screen
as a separate exception study. Add save-level workload sampling before claiming
that most normal edits meet ten seconds.

Artifacts: `tools/compile_latency/results/2026-09-19/coverage-study/` contains the
full case status matrix, raw measurements, native step logs, before/after LLVM,
validation/restoration output, incompatibility diagnostics and fixture provenance.


Final integrity audit passed: all ten newly executed cases, including the incompatible
before-state attempt, restored their sources and passed restored smoke checks.
The prior literal-hunk case remains archived separately. External source hashes
match; scratch non-entrypoint sources match with no extra PHS files; final Ninja
reports no work; the original model-table source diagnostic identity/line passed.


## Follow-up comparison

The original policy and results above remain frozen. A broader 113-helper candidate has now completed the same corpus: 4/9 timed code cases within budget, with five slower over-budget screens. The comparison and distinct adapter/type-publication exceptions are documented in `s2s_compile_latency_uniform_helpers.md`; its evidence is archived separately under `uniform-coverage`.
