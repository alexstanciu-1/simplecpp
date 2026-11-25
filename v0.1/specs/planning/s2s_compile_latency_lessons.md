# S2S edit-latency lessons and working build setup

Doc Status: planning

This is an experimental assessment, not a production build contract. Evidence and
raw timings are linked from `s2s_compile_latency_findings_2026_09_19.md`.

## Working setup for broader coverage

Use Clang 18, C++23, `-O0 -g1`, Ninja with a bounded parallel job count, mold, and
the final `latency-layout.ninja` output structure on this six-core/twelve-thread
WSL host. Freeze the job limit at **12** after the wider scheduling comparison: 32 ready
compilation units took median 10.628 seconds at 12 jobs, versus 11.754 at 16 and
11.507 at 32. Two runs per setting support a practical choice, not a universal optimum.
This is a repeatable working baseline, not a claim of a universal optimum.

Measure fresh edits with compiler caches disabled. Separately measure ccache
replay and misses. Depend mode has promising miss overhead, but the experiment's
`-MMD` setup needs a production system-header/toolchain invalidation contract before
it becomes a deployment recommendation. Keep frontend analysis/generation at the
agreed assumed 1.5 seconds; native timing includes scheduling and complete linking.

## Lessons to preserve

1. Avoid unnecessary dependency edges before increasing jobs. A false global
   header dependency can turn a small edit into a minute-long build.
2. Keep callable declarations separate from unrelated class declarations. Real
   signature changes must rebuild their users; new independent helpers need not.
3. Keep volatile declarations out of shared PCHs. A fast body-edit PCH can be a
   public-interface-edit trap.
4. Use stable implementation groups and generated names. Source-line insertions
   must not rename locals or move unrelated methods between native objects.
5. Separate diagnostic source locations from method bodies without changing
   reported source identity. Validate diagnostics as well as executable output.
6. Respect complete-type and object-lifetime dependencies. Outline special members
   where legal; never obtain low rebuild counts by leaving inconsistent layouts.
7. Ninja parallelism is across ready translation units. A limit of 32 cannot
   create 32 jobs from an eleven-object edit. Small edits often hit a critical
   path rather than a total CPU-capacity limit.
8. Linker threading and compiler-job counts are separate settings. Mold already
   links this application in a fraction of a second; its thread tuning is minor.
9. A cache replay is not a fresh edit. Record hits, misses, setup cost, and complete
   linked-program checks separately.
10. Broad real semantic changes may legitimately take longer. The target is common
    development edits, not a promise that changing 200+ consumers costs ten seconds.
11. Preserve the full program in each benchmark. Assert the rebuilt work set, verify
    unchanged objects where relevant, and execute witnesses of the changed behavior.
12. Distinguish analogues from historical reproductions. A new native file is not
    evidence that today's new-PHS discovery pipeline is incremental; a numeric
    mapping edit is not evidence of complete new numeric-type support.

## Generator feasibility boundary

The experiments assume a future resolved program model. The current production
S2S remains type-blind. A future emitter must own accurate type/callable dependency
metadata, stable symbol and partition identity, incremental discovery of new files,
and reproducible output. Experimental metadata and generated-code rewrites prove
bounded output feasibility; they do not implement that semantic pipeline.

## Additional coverage after settling concurrency

- Three-file numeric mapping changes: native median 4.050 seconds, four objects
  plus link. Stable declarations kept coordinated body changes local.
- New native MT helper file: native median 2.158 seconds, two objects plus link.
  Keeping the public interface and PCH stable makes native graph extension cheap.
- Both use 12 jobs, no compiler cache, three verified runs; estimated totals with
  frontend are 5.550 and 3.658 seconds. Their actual parallel widths are four and
  two jobs, respectively. More available threads would not make more ready work.
- Still unproven: complete source-file discovery for a new PHS helper, the full
  historical eight-file extraction, and a complete numeric capability addition.

## Coverage audit correction

The initial issue/doc selection was not exhaustive. A subsequent audit identified
an important two-file scoped-header correctness failure followed by a 453-object,
549.90-second broad fallback. Numeric mapping probes do not cover missing
transitive declarations. Review discovery coverage separately from experiment
coverage; track partial analogues explicitly. See
[s2s_compile_latency_next_edit_plan.md](s2s_compile_latency_next_edit_plan.md).

## Historical public-frontend edit: failed screening

A contemporary analogue of `60011d4c` adds a public frontend helper, a spelling
branch, two LLVM mapping branches, and a witness. Correctness passes, but the
native build takes **45.178 seconds**: one project PCH, 96 objects, and link.
Callable isolation currently covers selected owners only; it is not yet a general
public-method solution. Keeping unrelated callable declarations in the project
PCH remains a scaling trap. The next optimization must extend the existing
callable/dependency concept to frontend/backend owners, not add job count.
One screening run is sufficient to expose this structural miss; do not describe
it as a three-run latency estimate or as reproduction of the old missing-header
bug. The current graph compiled correctly; broad invalidation is the measured gap.

## New PHS helper: narrow generation is feasible

Actual new PHS helper lowering plus a redirected MT method and direct/indirect
witnesses takes native median 2.493 seconds across three trials. Only three objects
and link rebuild; PCH/runtime reuse is preserved. Explicit future metadata maps
new symbols to their own header and new source to its own object. This confirms
that new implementation discovery need not rewrite a global declaration set.
Automatic discovery and the full historical multi-consumer extraction remain
separate coverage gaps.

## Public-frontend failure resolved by extending the existing model

After isolating frontend callables and partitioning the LLVM implementation owner,
the exact same witnessed edit takes median **3.822s native**, versus the earlier
45.178s screening result. Seven objects plus link rebuild; neither PCH changes.
The lesson is to make dependency isolation systematic across owners. A narrow list
of optimized helper classes is a demonstration, not a general emitter policy.
Full expanded setup costs 271.501s and is recorded separately; improving common
edits can add cold-build work and must not conceal that tradeoff.

## Real flow checks strengthen edit evidence

- Shared counter field + existing profile writer + gate-summary reader: native
  median 6.574s, verified value flow 8:7:8, eight objects + link, no PCH rebuild.
- Numeric spelling feature: rejected by the baseline compiler, then accepted by
  the edited compiler; emitted i32/four-byte-aligned LLVM executes with exit 42.
  Native rebuild median 3.111s, seven objects + link, no PCH rebuild.
- Keep capability scope precise: the numeric case is a new source alias over an
  existing int32 representation, not a new numeric ABI or arithmetic family.

## Correct dependencies are necessary but insufficient

The actual seven-consumer extraction correctly avoids PCH invalidation yet takes
median 9.804s native. Large remaining implementation units dominate individual
jobs. The next improvement is applying the same stable partition owner to those
sources, not increasing concurrency or hiding the first compile behind cache hits.

## Goal outcome

Partitioning the six remaining consumers brings the same multi-file extraction
from native median 9.804s to **4.975s**, with eleven objects + link and no PCH
rebuild. Fewer objects is not itself the optimization target: smaller accurate
rebuild units can outperform fewer large ones. The setup cost (15.522s on the warm
expanded application) is separate. The final active profile is
`expanded_layout.py --partition-consumers`, with 744 linked objects.

The selected historical edit categories now have passing bounded feasibility
proofs. General emitter integration is future work. This is not a statistical
claim about every common edit, nor an implementation of future semantic analysis.
The original external source remains unchanged, temporary edit sources are removed,
the restored application is checked, and source diagnostics retain original identity.


## Hunting beyond the passing owner set

A callable-isolation win for selected classes does not generalize automatically
to the whole application. Adding one helper to the central model-table class
still produced a 141.744-second build (311 objects and a PCH) on the improved
layout. Audit other high-fanout declaration owners before claiming broad edit
coverage. Class layout changes and method-surface changes need separate queues:
the former can require real widespread recompilation, while the latter should
first be assessed for avoidable declaration and PCH dependencies.


## Two more proven native boundaries

- Extending the same callable-artifact concept to the central table owner reduced
  its helper-addition edit from 141.744 s to 2.847 s native median. This supports
  generalizing the concept in a typed emitter instead of accumulating special
  cases for individual source filenames.
- A reference-type layout change need not invalidate everyone who passes the
  object. Out-of-line allocation and typed field access reduced the counter edit
  from 181.194 s to 3.373 s median. Preserve real layout in one implementation;
  keep consumers dependent on field signatures, not offsets.
- Access boundaries have a runtime cost: this debug counter loop measured 15.4%
  overhead. Select boundaries based on edit fanout and hotness; do not blindly
  hide all fields. Broad by-value ABI changes remain a different category.
- Transform generated output in staging and publish final bytes once. Otherwise
  removing includes after publication can create false timestamp invalidation.
- Repeated add/remove trials can reuse orphaned native objects even with ccache
  disabled. Force the newly introduced callable object missing when measuring a
  fresh-addition cost and verify identical native work sets across repetitions.


## Coordinated signatures need actual-caller granularity

A nine-signature, sixteen-call parser change rebuilt fourteen objects in 5.812 s
median native on the existing solutions layout. Body-only and one-helper wins can
therefore extend to a realistic coordinated interface edit without a new lowering
special case. Match correctness witnesses to supported baseline behavior: the
historical arithmetic fixture is currently backend-blocked, so the passing local-
return policy probe must remain labelled an analogue, not a full historical replay.


## Historical labels can hide different edit classes

- The 333-second arithmetic-labelled patch includes 12 source files and 20 fields
  across three value records. Decompose its native costs rather than assigning
  one dramatic historical wall time to a small modern helper probe.
- Cleanup-preflight FN-02 adds four public static helpers as well as body logic.
  Do not classify the recorded one-source rebuild as body-only.
- One value-record field edit still takes 146.265 s while preserving independent
  copies. Shared-object accessors are not automatically a correct treatment for
  value records; inspect complete-type dependencies before changing representation.
- The exact literal-readiness routing hunk takes 4.268 s native median without
  changing output policy. This independently selected history case adds evidence
  for ordinary implementation edits, without yet providing a coverage percentage.


## Feasibility is not coverage

The fixed 15-commit inventory produced nine timed code cases: three within target,
six over target; comments and five replay limits remain separate. Every slow case
changed a non-isolated public surface and rebuilt the PCH. Selected-owner speedups
cannot support a claim that the current output is consistently fast. The next
conceptual step is general callable/class ownership independent of file ownership,
followed by the same frozen-corpus comparison. A patch that applies textually may
still require earlier surrounding code: the capability-plan reversal conflicts
with a later literal-return caller and is not a measured latency failure.


## Uniform-helper coverage follow-up

Applying callable isolation to 113 eligible helper owners improves the fixed sample from 3/9 to 4/9 timed code cases within budget. It does not establish a winning general configuration: five remaining cases take 163.5–192.5 seconds, slower than earlier screens. The environment-helper edit improves from 177.867 seconds to a 2.402-second median.

The missing concept is finer artifact ownership. Twenty-one template-bearing helper owners contain 37 forwarding wrappers and 52 parameter normalizers; these need per-callable dependency boundaries while preserving reference mutation and runtime checks. One historical case also adds a new eight-field composition type, so it additionally needs per-type publication outside the global all-types PCH. Splitting more implementations while keeping those broad dependencies can increase rebuild cost.

See `s2s_compile_latency_uniform_helpers.md` and the `uniform-coverage` results archive for the full fixed-corpus comparison, exclusions and validation.


## Callable adapters improve coverage; type publication remains separate

The 134-owner adapter candidate moves the same sample from 4/9 to 7/9 runnable
code edits within budget. Export helpers, LLVM filename helpers, and obsolete
LLVM delegates fall to 4.434, 3.162, and 2.194 seconds native respectively.
The original and extracted forms pass all 52 normalizer checks, preserving typed
aliasing and the existing mixed-reference rejection. Integer normalizers reject
mixed directly; boolean/string normalizers reach a disabled bridge for matching
kinds. Header inspection alone had missed this distinction.

Exact-patch review also corrects the dead-materializer classification: it removes
two carrier fields as well as helpers. That combined edit still costs 237.076
seconds; adding the new composition type costs 290.232 seconds. Both invalidate
the shared PCH. Their actual type dependency closures, not their commit titles,
should drive the next experiment. More partitions amplify broad invalidation;
per-callable isolation and per-type publication must be evaluated together.
See `s2s_compile_latency_adapter_boundaries.md` for the complete assessment.


## Type absence must be stable too

Keeping the unchanged composition class outside the shared PCH reduces its
historical addition from 290.232 seconds to a 3.899-second native median. Five
objects and the link rebuild; no field accessors or representation changes are
introduced. The fixed corpus advances to 8/9 runnable code cases within budget,
with all seven previous passes retained. The remaining existing-carrier layout
edit takes a 331.598-second screen and still rebuilds the PCH.

The first draft failed because its absent-type path skipped stale-forward cleanup.
Presence and absence now share normalization, with a regression proof that shared
headers remain byte-identical. Full class-identity, aliasing, composition-output
and executable checks pass. See `s2s_compile_latency_type_publication.md` for
coverage limits, development evidence and the next shared-carrier experiment.


## Source-header fan-out can hide small layout dependency sets

Publishing the two unchanged LLVM carrier definitions only to their consumers
reduces the historical field-removal replay from a 331.598-second screen to a
3.191-second native median. Eight objects and link rebuild; no PCH rebuild occurs.
Standalone-header, identity, shared-reference and vector-content proofs pass.
A common metadata-driven type-publication owner now serves all three tested types.

The fixed corpus advances to **9/9 runnable code cases within budget**, retaining
all eight prior passes. Native medians are 1.612–3.191 seconds; estimated totals
are 3.112–4.691 seconds. Four context exclusions and one incompatible before-state
remain; the earlier by-value layout exception is outside this corpus. This is
stronger feasibility/coverage evidence, not a universal latency guarantee.
See `s2s_compile_latency_shared_carriers.md` for the full table and limitations.


## Value layout does not require a global rebuild

The by-value field/writer probe now takes **6.117 seconds native** (three trials),
or **7.617 seconds** with the assumed frontend, compared with the earlier
146.265-second screen on the older output policy. Twenty objects plus link rebuild;
no PCH rebuild occurs. Exact struct definitions, independent row copies, stored
values and table/vector copies are preserved. The containing table explicitly
includes its complete row type outside the project PCH. No accessor indirection
or alternative runtime representation was needed.

The combined policy retains **9/9 runnable historical code cases within budget**,
with native medians 1.573–3.821 seconds. Four context exclusions and one incompatible
before-state remain. The separate value probe is not an extra historical replay.
Final adapter, identity, copy, diagnostics, ownership and source-integrity checks
pass. This resolves the representative value-layout exception, while the original
333-second, twelve-source/three-record compound change remains unmeasured under
this policy. See `s2s_compile_latency_value_publication.md` for evidence and limits.


## Compound value edits need expression dependencies

The three-record/twenty-field analogue of historical `7d58f273` takes 169.197 s
with the prior policy. Publishing the unchanged provider descriptor alongside
the trait row/table reduces it to an 8.275 s native median (9.775 s estimated
including frontend): 25 objects plus link, no PCH. All added field/writer/copy
witnesses pass. Headroom is small; this is near the target, not comfortably below.

The first conversion missed an inferred value consumer: returned temporaries and
`auto` vector iteration needed the type without spelling its name. Explicit
value-producer dependencies in the common publisher add that consumer. This is
concrete evidence that a resolved AST must drive dependency ownership.

The historical int64 subtraction fixture is blocked by the current runner; a
literal control passes. Baseline-versus-regression attribution is unresolved,
and the frozen corpus has not been rerun under the new candidate. Keep these
limits explicit. See `s2s_compile_latency_compound_values.md` for failed development
evidence, accepted timings, correctness scope and next assessment.


## Provider candidate retains coverage; historical block is preexisting

The frozen fifteen-commit sample retains **9/9 runnable code cases within budget**
under provider publication: 1.676–3.369 s native medians, or 3.176–4.869 s including
the assumed frontend. All thirty code/comment trials avoid PCH rebuilds. Four
context limits and one incompatible before-state are unchanged. Final copy,
identity, adapter, diagnostics, ownership and source-integrity checks pass.

A rebuilt previous-policy executable and the restored candidate produce identical
rows for the historical int64 subtraction fixture: backend_text / reason5 / blocked.
Thus the blockage predates this publication change; it remains unproven historical
feature coverage. The separate compound analogue remains near the ten-second
limit at 9.775 s estimated total and is not an additional historical replay.
See `s2s_compile_latency_provider_coverage.md` for the completed comparison.


## Ten seconds is a target, not a usability cliff

User clarification, 2026-09-20: limited overruns can remain usable if they do not
reach the 20–30-second pain range. Future assessments should weigh frequency,
edit shape and duration together, while retaining the original target-hit metric
for comparison. A rare modest overrun is lower priority than recurring long stalls.
Use native plus the assumed 1.5-second frontend for waiting-time interpretation;
correctness requirements are unchanged.


## Independent history and coherent exceptions, 2026-09-20

The next fifteen history commits yield three exact-context code replays at
1.617–2.640 s native (three-trial medians), with twelve exclusions retained.
Full-source reconstruction recovers two previously excluded edits: `9103637e`
(28 sources, 193.489 s) and `41392c03` (two sources, 194.008 s). Both are successful
single slow screens with before/after smoke and literal LLVM/native checks.
They rebuild the PCH and 603/588 objects respectively. A small source edit can
still expose a missing publication boundary and cause a broad rebuild.

Three newly introduced data classes are the next private-publication candidates,
not a measured fix. Historical metadata must describe historical fields; the
initial before-setup failure and corrected projection are retained. Final proofs,
source restoration and exact baseline-graph comparison pass. See
`s2s_compile_latency_history_expansion.md` for evidence and limitations.


## General per-type publication measured, 2026-09-20

The new candidate publishes all 485 current project types individually, with
local callable declarations, derived per-unit includes and a runtime-only PCH.
No aggregate application-type inventory is reachable. The general extractor
covers inferred signature types, inline dependencies and nested member chains;
it remains conservative structural metadata rather than a resolved AST.

Coherent `41392c03` improves from 194.008 s native to a 3.955 s median, rebuilding
seven objects plus link. Coherent `9103637e` improves from 193.489 s to a 32.680 s
successful screen, rebuilding 78 objects plus link. Neither rebuilds a PCH.
Both pass before/after smoke and literal LLVM/native checks. Final representation,
adapter, source-integrity, idempotence and no-op checks pass.

The compound case remains outside the usable zone. Seventy-one rebuilt objects
have changed C++ inputs; seven have unchanged C++ but changed dependencies.
This does not establish that every rebuild is necessary or every input change
is a body change. Its 350.059 seconds of aggregate compiler-job elapsed time
suggests limited scheduling-only headroom at twelve jobs. See
`s2s_compile_latency_minimal_type_publication.md` for evidence and limitations.


## Per-type coverage and compound attribution complete, 2026-09-20

All twelve runnable code cases across the frozen 15+15 history samples pass under
the general policy: 36/36 trials within target, native medians 1.593–4.815 s,
maximum individual native trial 5.151 s, no PCH rebuilds. The comment-only case is
a no-op. Sixteen context exclusions and the same incompatible before-state remain
explicit; these counts are not an arbitrary-save probability estimate.

The prior 32.680 s compound replay now has exact generated-input attribution:
41 body/other objects, 15 signature-only objects, 14 primitive location-data
objects, one new object and seven unchanged callers. All seven callers depend
on real const-reference-to-value C++ ABI changes; none is explained by unrelated
type publication. Two isolated Clang traces spend about 85–90% in the frontend,
with substantial template work, including PHP regex callback adapters pulled in
by the runtime umbrella despite the disabled regex backend. Narrower runtime
includes and lighter location-data compilation are next hypotheses, not measured
speedups. Final correctness, restoration, idempotence and no-op checks pass.
See `s2s_compile_latency_minimal_coverage.md` for evidence and scope.


## Accepted outcome and next-S2S handoff, 2026-09-20

The user accepts the measured results, including the roughly 30-second compound
case. Earlier entries describing that case as outside the acceptable zone record
the previous preference; this clarification supersedes that interpretation.
Ten seconds remains a common-edit target. Further compound/runtime optimization
is optional, not a prerequisite for adopting the demonstrated architecture.

See [the next-S2S technical handoff](s2s_next_generator_technical_handoff.md) for
required semantic inputs, artifact/dependency ownership, output/build rules,
correctness constraints, validation deliverables and evidence references.
