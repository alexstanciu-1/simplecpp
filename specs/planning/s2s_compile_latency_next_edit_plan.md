# Historical coverage audit and next edit plan

Doc Status: planning

## Current usability criterion — user clarification, 2026-09-20

Aim for roughly ten seconds end-to-end. Limited situations above that target can
still be usable if they stay below the 20–30-second pain range. Prioritize recurring
slow edits and larger stalls; judge occasional modest overruns by their frequency
and edit shape. Do not treat every ten-second target miss as an unusable result.
Keep archived native timings and target classifications unchanged, and report
usability separately with the assumed 1.5-second frontend included. No exact
allowable overrun frequency or secondary hard cutoff has been specified.

## Coverage answer at audit start, 2026-09-19

No: discovery is not exhaustive, and discovery must not be confused with measured
coverage. The saved GitHub search contains 92 Simple C++ issue entries and one
compiler issue entry; it is a search snapshot, not proof that every relevant issue
or comment was reviewed. Initial detailed evidence centered on #215, #218, #219,
#220, #224, and #96. The repository has additional active and archived documents,
logs, and historical revisions that need systematic triage.

## Coverage matrix

| Case family | Current evidence | Next action |
|---|---|---|
| Public profiling/token helper additions | Full-app analogous edits measured | Retain as regression cases |
| Signature and caller changes | Real profiling helper, thirteen callers measured | Extend to reference and return-type changes |
| Shared row layout | Field + existing writer + gate-summary reader measured, native median 6.574s; vector-to-summary witness 8:7:8 | Bounded flow case complete; genuine wide ABI changes may still be slower |
| Source-line insertion/generated naming | Stable locals and diagnostic location witnesses measured | Retain |
| MT helper extraction (#219) | Actual new PHS gate owner and eight production call sites across seven files measured; final native median 4.975s | Bounded analogue complete; explicit future discovery metadata; historical diff archived, not replayed verbatim |
| Numeric capability (`6764cad0` / `60011d4c`) | Public helper + both frontend routes + LLVM mapping branches; new alias rejected before, accepted after, emitted LLVM exits 42; native median 3.111s | Coordinated edit shape covered; a new numeric ABI is outside this case |
| Scoped dependency correctness, int32 local return | Historical-shaped public helper + frontend/backend analogue passes correctness but takes 45.178s, 96 objects + PCH + link | Expanded owner isolation now passes three trials at native median 3.822s with seven objects + link and no PCH; original missing-header failure not reproduced |
| Debug/release state separation (#216/#217) | Bodies/comments audited; implementation recorded despite open status | Configuration transition excluded from ordinary edit timing; no O3 build claim |
| Runtime reuse/stale artifacts (#162/#163/#126) | Bodies/comments audited and archived; lock regression/fix and recovery behavior distinguished | Recovery/CLI robustness excluded from native fresh-edit proof; runtime held fixed |
| Project module artifacts (#221) | Bodies/comments reviewed; published surfaces and remaining transitive/method granularity debt identified | Existing semantic direction recorded; no module implementation claim |
| Setup transition versus no-op frontend cost | Newly audited August 8 log: 201.51s transition, 17.46s no-op | Keep separate from source edits and assumed frontend budget |

## New source evidence

`compiler/docs/v2_current_state_summary.md`, July 29 resume state, and
`compiler/docs/v2_debt_register_2026_07_20.md` document the `int32_local_return`
change in `frontend_model_builder.phs` and `llvm_text_from_plan.phs`. Scoped packs
omitted required generated types, including LoweringStep and frontend row types.
The successful broad fallback took 549.90 seconds **wall time**, not an isolated
native timing. This is a correctness/dependency-closure case, not just fanout.

`compiler/docs/future/90_percent_functionality/00_inventory/implementation_progress_2026_08_05.md`
contains an August 8 measurement: 0 sources transpiled, 176 pack headers changed,
186 objects plus link, 201.51s wall and 137.248s Ninja after a vendor transition.
The next no-change build was 17.46s wall but only 0.028s Ninja. The former is setup
state invalidation; the latter is frontend overhead excluded by our agreed 1.5s
future-frontend assumption. Neither is a normal native edit-latency result.

## Execution plan

1. Finish an evidence inventory across both repositories' relevant issue bodies
   and comments, active/archive docs, available logs/misc artifacts, and selected
   git history. Record source, revision/date, trigger, build state, timing boundary,
   resolution evidence, and whether an existing test actually covers it. Preserve
   raw search results so the scope of discovery is reviewable. Group duplicates.
2. Prioritize scoped-dependency correctness, new-source/helper extraction, full
   numeric capability changes, and real shared-counter writer/reader updates.
   Use historical diffs to choose faithful contemporary analogues; use an isolated
   historical snapshot only when source evolution makes an analogue misleading.
3. Establish a correct warm build for each case. Apply edits in scratch, generate
   the feasible future output shape, compile only dirty native units, link the full
   application, and exercise changed behavior. Do not bypass missing dependencies.
4. Measure at least three fresh compilations per case with caches disabled. Record
   native time, rebuilt objects, PCH/runtime invalidation, critical-path units,
   actual concurrency, and correctness. Add the assumed 1.5s frontend separately.
5. If a case exceeds 8.5s native, classify true semantic breadth versus avoidable
   dependency churn, oversized units, setup transitions, or linking. Improve the
   output concept when justified; accept genuinely broad rebuilds as agreed.
6. Update the findings, lessons, and this coverage matrix after each case. Mark
   cases measured, partially covered, excluded with reason, or still untested.

Use the settled Clang/mold setup and twelve Ninja jobs on this host. Twelve is
its available logical CPU count, not its six physical cores. On other machines,
start from process-available logical CPUs and account for memory/cgroup limits;
our measurements do not prove CPU-count jobs universally optimal.

Original compiler repository stays read-only. This plan introduces no semantic
compiler integration, production build-system changes, or frontend timing work.

Follow-up audit: bodies/comments for #126/#162/#163/#216/#217/#221 and three historical source diffs are now archived and classified in the historical-pain report. Nine candidate active/archive documents and 102 matching lines are preserved. This does not claim exhaustive miscellaneous-log or git-history coverage.

Completed: expanded callable isolation, the counter writer/reader flow, an end-to-end numeric alias fixture, and multi-consumer PHS extraction. Each final case has three cache-disabled, correctly linked and executed trials below 8.5s native. Failed intermediate cases and setup costs remain recorded. General semantic-emitter integration and a statistically representative edit distribution are future work, not hidden unfinished benchmark steps.

## Goal completion boundary

The prioritized bounded assessment is complete. The scope was output feasibility under assumed resolved metadata and 1.5s frontend time, not production integration or exhaustive enumeration of every historical artifact. Original sources remain read-only; final scratch edits are restored. See the findings report for conclusions and limitations.


## Follow-up coverage after the bounded goal

The historical-pattern study adds a by-value trait layout screen (**146.265 s**,
copy semantics verified), an exact literal-readiness source hunk (**4.268 s median**,
LLVM exit42 before and after), and an unfiltered 15-commit recent-source candidate
pool. Next priorities are complete-type/PCH dependence for value records, helper
removal/rename (`b54f2766`), and staged composition-input refactoring (`43a83246`).
See the historical-patterns note; the original bounded goal remains complete,
while general everyday-edit coverage is still unestablished.


## Fixed-corpus coverage study completed

All 15 recent source commits now have explicit outcomes: nine timed code cases
(three within target, six over target), one comment-only no-recompile case, four
context mismatches and one semantically incompatible before-state. See the coverage
study note. The next comparison should generalize validated artifact ownership and
dependency rules across eligible owners, preserve this corpus unchanged, and keep
value-layout/ABI exceptions separate. Save-level workload sampling remains necessary
before estimating normal-development frequency. The current selected-owner prototype
has demonstrated feasibility but has not demonstrated consistent whole-program coverage.


## Uniform-helper follow-up complete

The broader helper rule has now been assessed against the complete fixed corpus. It improves one additional case, but five slow cases remain and regress. Do not treat this candidate as the stabilized optimum.

The next bounded candidate should (1) isolate each callable together with its required normalization adapters, preserving typed-reference behavior and the required rejection of mixed-to-native references; (2) publish the new composition data type through consumer-specific dependencies rather than an all-types PCH; and (3) rerun the unchanged corpus plus the separate existing value-layout exception. See `s2s_compile_latency_uniform_helpers.md` for the evidence and precise non-goals.


## Adapter coverage follow-up

The callable-plus-adapter policy has completed the fixed replay: seven of nine
runnable code cases pass the native budget, with two type/layout misses. The
next bounded implementation experiment should publish the unchanged
`BackendFunctionCompositionInput` definition only to complete-type consumers,
retaining shared-handle signatures and class identity. First prove construction,
field writes/reads, aliasing, and the actual composition output. Then replay the
new-type historical patch and the fixed corpus under an explicitly separate
policy. That draft is now implemented and assessed; see the type-publication follow-up below.

After that, inspect the two existing shared carrier layouts changed by
`e8e8a063`. Its field removals were previously misclassified as helper-only work.
Do not apply shared-handle assumptions to the separate by-value `TypeTraitRow`
exception. Preserve all prior timings and replay limits. The next generator
boundary is complete-type dependency ownership; production S2S integration and
runtime representation changes remain outside this assessment.


## Type-publication follow-up complete

The new composition type now takes a 3.899-second native median, with five
objects and link and no PCH rebuild. The fixed corpus improves from 7/9 to 8/9
runnable code cases within budget, retaining all seven prior passes. The existing
carrier-field removal remains slow at 331.598 seconds. Next generalize the
per-type publication owner to the two carriers in that patch, using explicit
field/dependency metadata and proofs for shared references and vector contents.
Keep the independent by-value exception separate. Details and the failed
absence-path lesson: `s2s_compile_latency_type_publication.md`.


## Shared carrier follow-up complete

The two shared carriers now use the common type-publication owner with explicit
field and dependency metadata. Their historical field removal takes 3.191 seconds
native, and all nine runnable cases in the fixed corpus pass without PCH rebuilds.
The next useful boundary is the existing by-value `TypeTraitRow` exception:
inspect its complete-type dependency closure, preserve independent copy/vector
semantics, and establish which rebuilds are necessary before changing output.
Then revisit excluded historical changes in coherent surroundings. Do not infer
population-wide save latency from the passing-commit fraction. Production S2S
integration remains outside this assessment.


Post-carrier read-only audit: `TypeTraitRow` has direct mentions in 18 active
translation units, plus a `TypeTraitTable` value-struct header containing
`vector_t<TypeTraitRow>`. The next slice must handle value-struct publication and
that table dependency explicitly, preserving independent copies. These lexical
counts motivate the experiment; they do not prove a complete dependency closure.


## Value-trait follow-up complete; next compound historical edit

The representative field/writer edit now takes 6.117 seconds native while retaining
native value semantics. The frozen historical corpus retains all nine code-case
passes. Final correctness and source-integrity checks passed; see
`s2s_compile_latency_value_publication.md`.

Next prioritize `7d58f273`: establish coherent before/after surroundings for the
coordinated twelve-source change, including twenty fields across three records
and the new helper owner. Measure the compound edit and meaningful stages under
the frozen combined policy before considering another output change. Preserve
copy/vector semantics, full linking and functional witnesses. Any incompatible
replay stays explicit; current-code analogues must be labeled separately. The
recorded 333-second build/gate cycle is historical motivation, not native baseline.
Afterward revisit remaining excluded history in coherent surroundings and broaden
the independent sample. Production semantic/S2S integration remains out of scope.


## Compound-layout follow-up: candidate measured, validation limits remain

The twelve-source patch cannot replay exactly in the current surroundings. Its
three-record/twenty-field analogue improves from 169.197 s to 8.275 s native with
provider publication and explicit value-producing-call dependencies. See
`s2s_compile_latency_compound_values.md` for the distinction between the analogue
and the full historical edit.

Next establish the old-policy result for the exact historical int64 subtraction
fixture, which is currently blocked, then rerun the fixed historical corpus under
the provider candidate. Its literal control and copy/adapter/identity proofs pass,
but neither the blocked fixture nor prior 9/9 coverage should be presented as
validated for this candidate. Preserve the broad twelve-file historical replay
as an outstanding coverage gap. Keep native jobs at CPU-core count (12 here).


## Provider coverage and fixture attribution complete

Both deferred checks above are now complete. The blocked subtraction fixture has
identical backend_text/reason5 results under the previous policy and candidate;
all nine runnable fixed-corpus code cases pass under the candidate. See
`s2s_compile_latency_provider_coverage.md` for evidence and limits.

The next assessment should expand the independent history sample and reconstruct
coherent surroundings for useful excluded/compound cases. Keep the original
fifteen-case results frozen, preserve source and feature exclusions, and avoid
counting the twenty-field analogue as the full twelve-source feature change.
Near-target compound edits deserve explicit headroom reporting. Production
semantic/S2S integration and unrelated compiler feature fixes remain out of scope.


## History expansion complete; next isolate newly introduced classes

Three further independent code cases meet the target. Two coherent historical
reconstructions now expose ~194 s exceptions with PCH fan-out; see
`s2s_compile_latency_history_expansion.md`. Apply the existing private-publication
concept to `BackendModuleCompositionInput`, `ScalarConditionOperand` and
`ControlFlowLocalWriteSet`, with historically correct field metadata and complete
semantic dependencies. Preserve exact definitions and shared-reference behavior.
First repeat the two-source `41392c03` replay; then repeat the 28-source
`9103637e` replay. Capture before/after graphs to distinguish required work from
incidental dependency fan-out. This optimization is not yet implemented or timed.
Keep the full twelve-source `7d58f273` replay and other exclusions explicit.


## User correction: dependency-minimal publication is the experiment contract

The next candidate must remove aggregate project-type declarations/definitions
from consumers and the shared PCH. Generate per-type headers and only the local
forward declarations or complete-definition includes required by each unit.
Do not retain one all-types header, or grouped type headers that introduce
unrelated dependencies. A shared runtime-only PCH remains possible if it contains
no changing application-type inventory.

This supersedes the three-named-class scope above: those historical cases are
regression witnesses for a general dependency-driven publication policy. Existing
measurements used only partial isolation and still expose shared project types;
the ~194 s cases do not measure the fully narrowed policy requested here.
Resolved dependency metadata must cover inferred return values, by-value members,
inheritance and template/inline requirements, not merely lexical type mentions.
Use the existing publication owner for the experimental transformation; preserve
representations and program behavior. Verify dependency closure and absence of
aggregate project-type includes before repeating the historical timings.


## General publication implemented; next validate breadth and compound cost

The complete observed type inventory now uses individual headers and per-unit
dependencies; both coherent replays are measured. The two-source case is 3.955 s
native median, while the 28-source case remains 32.680 s (single successful slow
screen). See `s2s_compile_latency_minimal_type_publication.md`.

Next rerun the frozen runnable history samples under this new policy; previous
9/9 and 3/3 results belong to the earlier policy. Then inspect the compound edit's
71 changed active C++ inputs, separating body/signature changes from include-list
and diagnostic-metadata churn. Seven further objects rebuilt through dependencies;
verify those edges before proposing narrower output. Keep the full twelve-source
`7d58f273` replay and other exclusions explicit. The latest case is not a modest
usable overrun, and its frequency in ordinary development remains unknown.


## Coverage and compound audit complete; next required-runtime boundaries

The twelve runnable frozen code cases pass under the general per-type policy.
Exact compound snapshots attribute its remaining cost to body changes, actual
signature/ABI changes, location data and corresponding callers. See
`s2s_compile_latency_minimal_coverage.md` for counts and Clang traces.

Next inspect the runtime dependencies of the two traced units. Prefer existing
narrower headers in copied output and a separate candidate graph/PCH; prove
required declarations, unchanged representations and full-program behavior before
accepting latency comparisons. Backend-disabled status alone does not prove an
adapter header can be removed: check actual direct and transitive use. Preserve
the frozen full-runtime-PCH policy as the comparison baseline.

Separately test a lightweight compilation path for primitive location-data
objects, preserving every symbol/value and diagnostic mapping. Do not expect this
alone to solve the entire compound case. Do not erase const-reference/value
semantics to avoid caller rebuilds. Production runtime/semantic integration stays
out of scope; any required wide ownership refactor must be reported before work.
The remaining historical exclusions and full `7d58f273` replay remain study gaps.


## Accepted assessment and handoff, 2026-09-20

The user accepts the results including the approximately 30-second compound edit.
The runtime/location-data experiments above are optional follow-ups, not blockers
to moving ahead with the next S2S. The primary handoff is now
[s2s_next_generator_technical_handoff.md](s2s_next_generator_technical_handoff.md),
which defines the semantic information and emission/build contracts to provide.
