# Assessment of S2S common-edit compilation latency
Doc Status: planning

Date: 2026-09-19

Latest assessment (2026-09-20): all twelve runnable code cases from the frozen
history samples pass under general per-type publication, at 1.593–4.815 s native
medians. The coherent two-source case is 3.955 s; the large 28-source compatibility
cleanup remains a 32.680 s exception. Exact attribution and compiler traces now
identify required-runtime includes and location-data compilation as next tests.
See [s2s_compile_latency_minimal_coverage.md](s2s_compile_latency_minimal_coverage.md)
for evidence, exclusions and limits.

Technical handoff: [next-S2S semantic inputs and incremental C++ generation](s2s_next_generator_technical_handoff.md).
The user accepts the measured outcome, including the roughly 30-second compound
case (clarification 2026-09-20); further optimization is optional.

## Decision to make

Can an integrated, semantically informed S2S backend deliver an executable within
approximately 10 seconds after most common edits to a substantial Simple C++
application? Determine the required output structure, runtime interfaces, and
native build requirements before committing to migration.

This is an assessment plan, not an implementation commitment or speedup claim.
Assume complete, correct semantic information is available. Do not inspect or
modify `scpp_compiler_3`. Future analysis and C++ generation have a fixed assumed
budget of 1.5 seconds; they are not measured in this assessment.

The non-goals are integrated compiler implementation, full S2S migration, language changes, general runtime redesign,
interactive hot replacement, and proving every release build finishes in 10 seconds.

## Real workload and boundaries

Use `/home/alexv/__AI/simple_cpp_compiler/compiler/src` as the primary workload.
Read-only inspection found 452 PHS files and 59,246 physical lines, including
generated sources. These are inventory counts, not reachable compilation counts.
Its manifest uses the strict PHP profile, datetime/json/tasks modules, and
`project_unit_scoped_packs`.

Initial candidate large units:

- `compile/incremental/resident_source_unit_frontend_payload_tables.phs`: 4,015 lines.
- `compile/backend/llvm_text_from_plan.phs`: 3,523 lines.
- `compile/frontend_adapter/phs/frontend_model_builder.phs`: 2,987 lines.
- `compile/capabilities/type_capability_readiness.phs`: 2,541 lines.

Confirm reachability and actual C++ cost before selecting them as bottlenecks.
The existing `compiler/tools/run_compiler_build_timing_gate.sh` invokes the
repository's vendored S2S with `build --no-stan`. It is useful baseline evidence,
but cannot establish latency including required checking. Do not confuse the
compiler application's own incremental performance with the time to rebuild it.

Keep both external source repositories read-only. Run experiments on an isolated
snapshot with separate generated outputs, build directories, and compiler caches.
Audit scripts for absolute paths before using them. Never clear shared caches.
Do not run overlapping experiments against the same build outputs.

## 1. Freeze the experiment and define acceptance

Record source revisions and dirty-file content, vendored/current S2S revisions,
runtime revision, compiler/linker/cache/Ninja versions, exact flags, CPU/RAM,
filesystem and WSL limits, and background load. Preserve a manifest of inputs.
Respect the workload repository's 16-job build setting where supported and record
actual concurrency. Keep hardware and concurrency fixed across comparisons.

Primary metric: wall time from invoking Ninja to successfully linked executable,
including scheduling, compiler/cache startup, compilation and linking. The preferred native budget is
8.5 seconds; adding the assumed 1.5-second frontend gives the ten-second target. Report correctness-test time separately. Use a documented
development profile and report optimized builds separately.

Usability clarification from the user, 2026-09-20: ten seconds is a target, not a
hard usability cutoff. Occasional overruns can remain usable when limited to
particular situations and kept below the 20–30-second pain range. Assess duration,
frequency and edit type together. Do not optimize a rare modest overrun merely
to cross the ten-second line while leaving recurring long stalls unexplored.

Keep the original 8.5-second native target classification for historical comparison,
and add the usability interpretation separately. Report estimated end-to-end time
(native + assumed 1.5 seconds) when discussing the user's waiting time. No precise
acceptable overrun percentage or sharp secondary cutoff has been agreed; the
original proposed 95% target-hit criterion is not a user-approved requirement.
Correctness remains required regardless of duration.

Historical pain cases take priority over increasing the body-edit sample count.
See `s2s_compile_latency_historical_pain_cases.md` for GitHub issue and compiler
commit evidence. Required categories include public-helper additions, real
signature/caller changes, helper extraction/new sources, and shared row changes.
Until there is a defensible frequency distribution of real edits, report category
results rather than interpreting an artificial corpus pass rate as “most edits.”

## 2. Establish the existing baseline

First reproduce a correct build with the workload's vendored toolchain. Then try
the current Simple C++ toolchain as a separate baseline if compatible. Record any
compatibility blocker; do not silently repair application semantics or mix baselines.

Inventory participating sources, generated header/source sizes, includes, object
grouping, PCH contents, compile commands, runtime reuse, and actual rebuilt objects.
Run existing focused compiler smoke/fixture checks against the resulting binary.

Measure these states separately:

- Clean application build with runtime already prepared and an empty private cache.
- No-op build with existing outputs.
- New body edit with warm dependencies but a cache miss for changed compilation.
- Return to previously built source content, demonstrating cache-hit behavior.
- Runtime preparation and genuinely cold setup, outside the common-edit target.

Capture total wall time and stage timing, compile/link wall time, per-object cost,
rebuilt-object count, cache hit/miss counts, peak memory and swapping. Use compiler
traces in separate diagnostic runs so instrumentation does not bias headline times.
Measure a link-only rebuild to identify a possible latency floor.

## 3. Freeze a realistic edit corpus

Choose edits before optimization, using actual development history where available.
Select at least 20 distinct patches across small, medium, and large reachable units;
include the expensive units rather than selecting only convenient examples.

| Edit category | Purpose |
| --- | --- |
| Diagnostic string or local literal | Small observable body change |
| Branch/calculation in an existing function | Typical behavior edit |
| Existing method in a large implementation file | Stress unit granularity |
| Add a private helper and call it | Test symbol/output stability |
| Change two cooperating implementation files | Test a realistic multi-file change |
| Public signature change with caller updates | Measure legitimate contract fan-out |
| Widely used record layout change | Measure broad invalidation separately |
| Generated metadata or common runtime dependency change | Measure exceptional rebuilds |

Every patch must have an expected behavior or artifact assertion. Also run existing
focused regression fixtures. Each trial starts from the same successfully built
baseline. Ensure a changed object cannot hit a previous trial's cache in miss tests;
keep dependency artifacts warm. Test cache-hit variants separately.

Run at least five trials per common patch, interleave configuration order, and
report median, p95, maximum, category pass rates, and raw samples. Repeated timing
trials quantify noise; they do not replace diversity of edits. Label the empirical
percentile's limited sample size and expand near-threshold or noisy results.

## 4. Diagnose before changing output

Attribute native latency to scheduling/cache startup, generated-file churn, header parsing,
template instantiation, optimization/code generation, linking, or memory pressure.
Explain why each object rebuilds and why it consumes its observed compile time.
Check whether unchanged generated headers or grouping changes cause avoidable work.

Stop broad tooling experiments once the dominant costs are established. A cache
cannot prove a first-time edit is cheap; Ninja cannot compensate for a broad graph.

## 5. Test output the future backend could generate

Use copied generated C++ and bounded source-derived fixtures to build experimental
variants. Changes to generated output are explicitly experiments, never production
fixes. Keep the full application linked so real link cost and surrounding runtime
dependencies remain represented. Small fixtures supplement, not replace, that proof.

Test one change at a time, then combine winners:

1. Stable headers and write-if-changed publication for body edits.
2. Narrow declaration dependencies and valid forward declarations.
3. Split expensive implementation units into stable bounded groups; compare with
   existing grouping. Avoid both one huge unit and one process per tiny function.
4. Concrete selected calls/conversions where checked semantics justify them.
5. Centralized expensive instantiations or precompiled runtime operations where
   supported by truthful type, layout, ownership, and ABI contracts.

For each transformation document the required semantic facts, their existing or
missing owner, how a generator would reproduce it, and its invalidation rule.
Maintain an explicit blocker list. Do not claim narrow includes are possible when
current runtime headers cannot support them without a separate interface change.
Do not use handwritten constant results, removed work, disabled safety semantics,
or sample-specific substitutions to obtain speedups.

Compile and run equivalent behavior for each variant, including relevant evaluation
order, copy/destruction, error, and overflow behavior. Report any optimization or
runtime-performance tradeoff introduced by moving code out of headers.

## 6. Compare tool configurations selectively

Start with Clang and Ninja, runtime artifacts prepared, and ordinary object outputs.
Compare no cache, ccache, and sccache in isolated runs; do not stack caches. Verify
actual hits and misses rather than assuming launcher success means caching works.
Compare PCH on/off only with validated compatible flags and cache behavior.
Test an alternative linker if measured link time threatens the budget.

Only investigate C++ modules if repeated parsing remains dominant after narrowing
dependencies. Pin compiler/module/cache compatibility and charge module rebuilding
to edit latency. Reverify current official tool documentation during execution.

If CERN means Cling/ROOT, assess it as a separate resident-compilation experiment,
not as an interchangeable object cache. Include session startup/amortization and
the supported edit/redefinition model. It is not required for the first AOT verdict.

## 7. Establish generator feasibility without integration

Use this Simple C++ worktree on an experimental `codex/` branch. Supply verified
semantic facts through extracted or handwritten metadata. Hard-coded output and
direct generated-C++ changes are allowed as experiments. Prefer reproducing the
winning shape through a bounded experimental generation path across source edits.
Tie metadata to source snapshots and reject unsupported or stale facts.

For each winning transformation describe the required facts and a credible general
generation rule. No production integration or frontend latency measurement is
required. The verdict remains conditional on full semantic information and the
assumed 1.5-second analysis/generation budget.

## Decision gates and deliverables

1. **Baseline gate:** reproducible correct workload and trustworthy phase timings.
2. **Native feasibility gate:** realistic changed C++ units plus full application
   linking fit within 8.5 seconds including native-build overhead.
3. **Generation feasibility gate:** correct, stable output survives source edits and
   can be produced by a sensible generator with full semantic information.

Deliver a reproducible runner, input/toolchain manifest, patch corpus, correctness
checks, raw timing/trace files, and a comparison table listing total time, compile
time, link time, rebuilt objects, cache results, and memory per scenario/configuration.
Report cold/setup cost separately from recurring edit cost.

The final recommendation must distinguish:

- Demonstrated with the current build path.
- Demonstrated for feasible future C++ output; integration still required.
- Reproduced by the experimental generation path across source edits.
- Blocked by runtime interfaces, generated-code shape, linking, memory, or unavoidable
  dependency fan-out, with the smallest next experiment or design change named.

No migration recommendation should rely solely on no-op builds, warm-cache hits,
toy programs. The fixed frontend budget remains an explicit assumption.
