# Type-informed S2S compile-latency findings

Doc Status: planning

## Decision so far

Fast native compilation is credible for more than tiny body edits, provided the
emitter separates **callable declarations from class/layout declarations**, keeps
implementation units small, and preserves unchanged generated content. Merely
splitting C++ files while putting all declarations into one PCH fails public-helper
edits badly. The ten-second target is not established for an empirical majority
of edits, and genuinely broad shared changes remain an acceptable slower category.

Assume frontend analysis/generation = 1.5 seconds, as agreed. Native budget = 8.5
seconds from Ninja launch through executable readiness, including scheduling and
link. Measured development flags are Clang 18, `-O0 -g1`, mold, Ninja `-j16` on the
recorded Ryzen 4650G/WSL host. Cache launchers disabled except in the explicitly labeled cache studies. Original compiler source
and `scpp_compiler_3` are not modified; the latter is not consulted.

## Important follow-up: public frontend methods still exceed the budget

The historical `int32_local_return` commit `60011d4c` added a public frontend
helper as well as body branches. A contemporary analogue now tests that complete
edit shape: a new frontend public helper and spelling alias, explicit LLVM int32
alignment/type branches, and an entrypoint witness. It compiles correctly with
existing scoped packs and verifies `6:4:i32`, but takes **45.178 seconds native**
(**46.678 seconds** with the assumed frontend). One screening run rebuilt the
project PCH, **96 objects**, and the executable. PCH generation alone took 15.702s.

This is a failed budget case, not a three-trial estimate. The experiment does not
reproduce the old missing-declaration bug; it exposes remaining broad invalidation
in the current future-output prototype. Existing callable isolation covers only
selected owners, so adding a frontend method still changes the shared declaration
PCH. Earlier numeric body-only timings must not be presented as coverage of this
public-surface scenario. The next optimization is extending callable isolation and
stable implementation groups to the relevant owners, followed by rerunning this
same witnessed edit. Increasing thread count cannot remove the dependency problem.

## Historical cases drive the assessment

See [historical evidence](s2s_compile_latency_historical_pain_cases.md) for issues,
resolution comments, compiler commits, and distinctions between cold regeneration,
mode switches, orchestration cost, and actual edited-code native compilation.
The current workload includes fixes for earlier all-project invalidation. Old
398-second timings are historical motivation, not current baseline results.

## Results

All timings below are native seconds. Public-addition rows use three fresh edits
each, adding a public static method, changing its dispatcher, and exercising it
from the application entrypoint. Values are medians; ranges are observed minima
and maxima, not statistical confidence intervals.

| Edit | Existing output | Separate callable declarations | Combined declarations + small bodies |
|---|---:|---:|---:|
| Profiling-stage helper addition | 11.861 (11.639–11.890) | 5.361 (5.306–5.370) | 2.736 (2.692–2.855) |
| Token-kind helper addition | 9.781 (9.758–9.977) | 5.301 (5.212–5.323) | 2.770 (2.628–3.056) |
| Profiling signature change, 13 actual calls across six existing source files, plus entrypoint witness | 14.424 | 9.188 | 4.415 |

Signature row is one trial. It adds a minimum elapsed-time parameter, updates all
actual calls with zero to preserve their behavior, and verifies a nonzero minimum
through a new entrypoint call. Native outputs were 12, 10, and 12 respectively:
smaller compilation work matters more than merely minimizing object counts.

The earlier global-project-PCH public-helper experiment took 57.530 seconds,
versus 13.459 for its baseline. That proposal is rejected as a general solution.
The original 100-pair body-edit corpus remains supporting evidence only.

## What the new shape does

1. Two selected non-inherited static helper classes retain consistent class identity
   helpers, while their user methods become individually named native free functions.
   The Simple C++ source still uses its normal static-method syntax.
2. Each callable has its own stable declaration artifact; callers include only the
   ones they reference. Adding an unrelated callable leaves existing declarations
   and the native class identity unchanged.
3. Five real profiling consumers use retained groups of complete method bodies.
   Changed signatures invalidate their actual calling groups rather than whole
   large source files. No method body or application link input is discarded.
4. Source line numbers are separately compiled metadata, and generated local names
   are stable within each method. Inserting source lines does not rewrite all later
   native bodies. Runtime call-depth guards remain enabled.
5. The combined PCH excludes the selected volatile callable surfaces. Other project
   types/declarations remain in it, so this PCH is still sensitive to layout changes.

A full independent application build established consistent native class
identities before measurements. Baseline and separate-callable layouts have
independent object trees and runtime PCH artifacts. For the combined comparison,
changed shared inputs are explicitly invalidated before each measured build to
prevent one layout warming another. A shared-PCH setup mistake was caught and
fixed before recording the public-edit results; setup work is not counted as edits.

## Correctness evidence and limits

Every measured successful edit executes the existing structure smoke and twenty
source-method result witnesses, plus a check that the newly added behavior actually
runs. The signature result verifies `signature_probe=123456789`. A separately
linked diagnostic executable forces a real call-depth failure and verifies
`compiler_profile_events::stage_name`, its original source file, and source line 92
after free-function lowering. The instrumented entrypoint force-includes the full project declaration set so
it can call all twenty witnesses; all comparisons include that harness cost.
These are not timings of the unmodified production entrypoint.
This is focused behavioral evidence, not exhaustive
compiler correctness or a complete debugger/source-map implementation.

The bounded transformation validates its generated grammar and rejects unsupported
shapes. It does not establish support for overloads, virtual dispatch, inheritance,
arbitrary native C++, external ABI consumers, or all method-reference mechanisms.
The two-class lowering is experimental metadata, not production type inference.

## Why a resolved-AST generator can emit it

The owning future layer is the native artifact planner/code generator. It needs
resolved callable identities and call edges, source access/dispatch semantics,
complete-type requirements, body ownership, and source locations. It can emit these
artifacts directly without changing the language's class syntax. Semantic access
checks happen before lowering; native free functions are an implementation detail.

Production work must define stable overload-aware symbols, visibility/export ABI,
method references, deterministic initial group assignment plus stable edit-time
membership, removed-artifact handling, and accurate debug/source maps. The experiment
reuses existing coarse scoped type packs; a complete semantic generator could emit
narrower type dependencies too. It must not preserve incompatible class definitions
or skip real layout-dependent recompilation merely to improve timing.

No migration to the new compiler prototype is needed to establish this output
feasibility. A production migration/integration slice remains outside this task.

## Shared layout boundary

Adding a real `uint32` field to `CompilerProfileEventRow`, with a read/write
witness in the entrypoint, took **78.086 seconds / 164 outputs** in the combined
layout. Correctness checks passed. This is one trial, and demonstrates that
isolating callable surfaces alone does not isolate layout changes.

The next experiment isolated complete-type dependencies: move
`CompilerProjectRunReport` construction/destruction/copy/move definitions out of
its header, retain its vector-of-row storage, and include the row definition only
in implementations that need it. The project PCH builds with the row
incomplete; Ninja confirms it excludes the row header. A complete independent
application build passed, followed by the same field edit in **7.042 seconds /
8 outputs**, with the new field read/write witness and smoke checks passing. The transformation verifies the proposed noexcept moves against all
442 actual member types, instead of assuming exception specifications.

User clarification: it is acceptable for a genuine change affecting 200+ consumers
to require a broader rebuild. Do not expand the objective into making every layout
change finish under ten seconds. The row experiment isolates unnecessary include
dependencies; it is not a promise about changes whose true semantic reach is broad.

## Scheduling and changed-input compilation

All preceding measurements already used separate Clang invocations scheduled by
Ninja with a maximum of 16 concurrent jobs. Ninja evaluates the full executable's
graph but only invokes compilers for dirty translation units. “Dirty” includes
unchanged `.cpp` files whose real header/ABI dependencies changed; compiling only
textually edited source files would miss those required recompilations.

The additional scheduling probe replays the same profiling-signature edit with
1, 2, 4, 8, 12, and 16 jobs, in forward and reverse order. It records actual compiler
start/end intervals, peak overlap, exact rebuilt outputs, and verifies that every
other linked object's timestamp remains unchanged. This distinguishes useful
parallelism from requesting more jobs than the edit graph can supply.

| Maximum jobs | Native median, two runs | Observed compiler-job peak |
|---:|---:|---:|
| 1 | 22.852 s | 1 |
| 2 | 12.235 s | 2 |
| 4 | 7.551 s | 4 |
| 8 | 5.419 s | 8 |
| 12 | 4.820 s | 11 |
| 16 | 4.532 s | 11 |

Every trial rebuilt the same eleven objects plus link. The other **522 of 533
linked objects** retained their timestamps. Settled and restored no-op builds
invoked no compilers and took 0.045 and 0.044 seconds. All trials passed the smoke,
20 witnesses, and changed-signature check. The edited source and active native
layout were restored and checked afterward.

Keep the current 16-job setting for this host and workload; the 12/16 difference
is too small and the sample too short to claim a universal optimum. Smaller job
counts materially restrict this edit. A one-object edit cannot exploit extra jobs,
which is why output granularity matters alongside parallel scheduling. These
job-count measurements use the final row-isolated layout; they are not additional
samples of the earlier 4.415-second intermediate-layout result.

[Ninja's manual](https://ninja-build.org/manual.html) documents parallel scheduling
and compiler-discovered header dependencies. Clang's [ThinLTO documentation](https://clang.llvm.org/docs/ThinLTO.html)
describes parallel optimization backends at link time; that is a separate optimized
build feature, not a switch needed by this `-O0` edit loop. No ThinLTO timing claim
is made here.

## Linker comparison on the full executable

Forced relinks reuse exactly the same complete set of compiled objects. Three
trials per configuration alternate forward/reverse/forward order; every trial
verifies that the only native output is the executable and runs all smoke checks.
These are link-only measurements, not independent complete source-edit timings.

| Linker | Median native relink | Range, three trials |
|---|---:|---:|
| mold, existing default | 0.273 s | 0.256–0.964 s |
| mold, one thread | 0.398 s | 0.397–0.422 s |
| mold, four threads | 0.235 s | 0.234–0.254 s |
| mold, eight threads | 0.240 s | 0.235–0.243 s |
| LLD | 0.357 s | 0.353–0.400 s |
| GNU ld.bfd | 1.586 s | 1.578–2.032 s |

Mold was already active in previous edit experiments, so switching to it cannot
be counted as a new improvement over those results. Against GNU ld, it saves about
1.3 seconds here. Four/eight linker threads improve the median only by roughly
0.04 seconds versus mold's default; compilation dependency structure remains the
larger opportunity. The first default-mold trial is retained as a 0.964-second
outlier; its cause was not isolated. Three trials do not establish a universal
linker/thread-count ranking. Versions: mold 2.40.4, LLD 18.1.3, GNU ld 2.42.

## Compiler cache: fresh work versus exact replay

The profiling-signature case also has a bounded ccache 4.9.1 experiment. Eleven
selected objects use separate output paths and timestamp-free PCHs; the remaining
522 objects retain the same definitions and existing objects. PCH setup is excluded
from edit timing. Direct Clang uses the same PCHs and flags as the cache variants.
Each round uses a newly created private cache, then deletes only the eleven test
objects and executable for two exact replays. Every run verifies all twelve native
outputs and executes the complete application, including the signature witness.

| Configuration | Native median | Range, three trials |
|---|---:|---:|
| Direct Clang, cache-compatible PCHs | 6.159 s | 5.803–6.625 s |
| ccache, empty cache | 8.109 s | 6.891–8.476 s |
| ccache, first exact replay | 0.365 s | 0.363–0.368 s |
| ccache, second exact replay | 0.351 s | 0.350–0.354 s |

Every empty-cache trial recorded eleven misses; each replay added eleven direct
hits. Cache hits offer a substantial benefit when revisiting previously compiled
code. The cold-cache median adds about 1.95 seconds relative to this direct median;
with the assumed 1.5-second frontend, the slowest cold trial reaches 9.976 seconds,
leaving almost no margin. Do not substitute cache-hit numbers for new-edit latency.
These direct measurements are slower than the earlier scheduling session; use the
paired direct runs here, not the earlier 4.532-second result, to assess cache cost.
The experiment does not isolate why the absolute direct timings differ.

The [ccache 4.9 manual](https://ccache.dev/manual/4.9.html#_precompiled_headers)
requires special PCH handling. This bounded experiment uses timestamp-free PCHs
and `pch_defines,time_macros`; no time macros were found in the generated mirror
or runtime include tree. This configuration is not permission to ignore arbitrary
user macro semantics in a future generator. No shared cache was cleared. The
measurements include Ninja scheduling, cache overhead, compilation/retrieval, and
full linking, but exclude frontend generation and correctness execution.

### Follow-up: reduce cache-miss overhead with depend mode

A second three-round series enables `depend_mode = true`, keeping the same work
set, flags, PCHs, private-cache protocol, and full-program verification.

| Configuration | Native median | Range, three trials |
|---|---:|---:|
| Accompanying direct Clang | 4.615 s | 4.343–4.707 s |
| Depend-mode ccache, empty cache | 5.126 s | 4.762–5.425 s |
| First exact replay | 0.306 s | 0.306–0.323 s |
| Second exact replay | 0.297 s | 0.297–0.299 s |

Statistics again show eleven misses on each cold run and eleven additional direct
hits on each replay, with no preprocessor cache lookups. The cold-cache median is
about 0.51 seconds above its accompanying direct median, versus about 1.95 seconds
in the default-cache series. Absolute direct timings also changed between series,
so do not attribute the entire 8.109-to-5.126-second difference to this setting.
The depend-mode cold median plus assumed frontend is **6.626 seconds**; its slowest
trial totals **6.925 seconds**. This remains evidence for this edit and host, not
a guarantee for all edits.

The [documented depend mode](https://ccache.dev/manual/4.9.html#_the_depend_mode)
avoids a separate preprocessing pass on misses. With this experiment's `-MMD`,
system-header changes are excluded from dependency manifests. A production design
must use `-MD` or an explicit toolchain/system-header identity. This series held
the toolchain fixed; it does not validate such invalidation. Sccache, distributed
compilation, and optimized builds have not been benchmarked here.

## Settling the job limit before broader edit coverage

An additional real-signature series requested 12/16/24/32 jobs twice. Every run
still peaked at eleven compiler jobs, as required by the eleven-object graph.
Native medians were 5.881/7.093/5.651/5.150 seconds respectively, but these values
cannot show a parallelism benefit: actual concurrency was identical. Run variation
is substantial, so the smallest value is not a reason to select `-j32`.

A separate stress test deliberately makes **32 existing small callable bodies**
dirty, with caches disabled. Their contents do not change; this is a scheduling
capacity experiment, not a representative source-edit claim. Two passes use
opposite job-count order, assert exactly 32 object outputs plus link, and execute
the full application's checks each time.

| Maximum jobs | Native median | Trials | Sum of compiler elapsed times, median |
|---:|---:|---|---:|
| 8 | 11.458 s | 10.672 / 12.245 s | 88.824 s |
| 12 | 10.628 s | 10.872 / 10.385 s | 113.768 s |
| 16 | 11.754 s | 12.482 / 11.026 s | 171.528 s |
| 24 | 11.192 s | 11.274 / 11.109 s | 220.081 s |
| 32 | 11.507 s | 11.335 / 11.678 s | 332.550 s |

Observed job peaks match every requested limit. The summed durations are wall time
per compiler task, **not CPU seconds**. They show increasing contention without a
wall-time benefit from 24/32 jobs. Twelve has the lowest median in this small sample
and can also run all eleven signature-edit objects concurrently.

**Working decision: freeze Ninja `-j12` for subsequent coverage on this host**, with
Clang 18 `-O0 -g1`, existing mold defaults, and caches disabled for fresh-edit proof.
This supersedes the earlier provisional `-j16` recommendation, not its measurements.
It is a practical baseline, not proof of a global optimum for every edit or machine.
Cache depend mode remains an optional separately measured path pending a complete
system-header/toolchain identity contract. There is no reason to delay edit coverage
for more thread-count tuning.

## Broader cases using the settled 12-job baseline

Both new cases use caches disabled and mold, three native builds each, with
identical work-set assertions and full smoke/witness execution. Timings include
linking. Source/generation setup, restoration, and verification are not timed.

| Edit analogue | Native median (range) | Native work | Plus assumed frontend |
|---|---|---|---:|
| Coordinated numeric mappings | 4.050 s (4.034–4.149) | 4 objects + link | 5.550 s |
| Extract MT helper into new native file | 2.158 s (2.155–2.340) | 2 objects + link | 3.658 s |

The numeric case adds witnessed experimental ID branches in `type_ref_identity`,
`type_traits`, and `primitive_abi_adapter_matrix`, plus the entrypoint. The witness
checks source type spelling, numeric-kind description, and LLVM type spelling.
Existing declarations remain stable. This tests three real implementation owners
with coordinated mapping changes; it is **not** a full numeric-type addition, and
it does not replay historical int16 compiler commit `6764cad0` verbatim.

The extraction moves actual MT heartbeat key construction from a stable generated
partition into a new native implementation file, leaving its original guarded
method wrapper calling the extracted function. The existing public class, source
guard, and PCH remain intact. The application calls the wrapper and checks the exact
key. That witness is prepared before the extraction timing, so its entrypoint does
not inflate the measured extraction work. The new object and changed partition
link into the full application; no unrelated objects or PCH rebuild.

This extraction directly tests permitted future-output reshaping. It **does not**
exercise today's PHS file inventory, new class discovery, or the complete historical
MT helper extraction across eight source files. A future generator must make new
implementation discovery incremental to achieve the same outcome. No new native
class-layout definition is introduced, avoiding incompatible reused object types.

The numeric source edits and extraction are restored afterward; all original
external source hashes still match. These cases expand evidence without claiming
that every historical pain category is now fully reproduced.

## Actual new-PHS helper extraction with explicit future metadata

The follow-up now creates and transpiles `latency_heartbeat_format.phs`, moves the
real MT heartbeat key construction into it, redirects the existing method, and
adds direct and indirect entrypoint witnesses. Correctness passes in three trials:
**2.475 / 2.493 / 2.506 seconds native**, median **2.493 seconds**, estimated total
**3.993 seconds** including the assumed frontend. Each run compiles exactly the
new helper, the changed heartbeat partition, and main, then links. There are no
PCH or runtime rebuilds.

The source-to-object edge and symbol-to-header dependencies are supplied explicitly
as permitted future semantic metadata. The new helper header is included only in
its users, not appended to the shared PCH. This extends the earlier native-only
extraction to actual new PHS generation, but does not measure today's automatic
file discovery or the full historical eight-consumer extraction. The production
MT wrapper and direct entrypoint call are the two exercised call sites. Scratch
sources, generated files for the added helper, and the active executable are
restored afterward.

## Expanded callable isolation resolves the public-frontend failure

The same witnessed public-helper/frontend/backend edit is replayed using the same
emitter concepts with `frontend_model_builder` added to callable isolation and
`llvm_text_from_plan` added to stable implementation partitioning. Three native
runs take **3.822 / 4.001 / 3.318 seconds**, median **3.822 seconds**. Estimated
median including the assumed frontend is **5.322 seconds**. All complete-program
checks pass and all trials rebuild the same seven objects plus link, with no PCH
rebuild. The prior 45.178-second result remains a one-run screening comparison.

Rebuilt objects are the new frontend helper, changed frontend lookup, two LLVM
implementation groups, their two source-location data objects, and main. This
explains the gain: unrelated declarations and bodies remain stable, rather than
more threads or cached compilation results being used.

The expanded graph has an independent generated mirror, objects, runtime PCH, and
project PCH. Its full first setup takes **271.501 seconds**, 711 native outputs.
Setup is deliberately not counted as an edit and is not advertised as improved
cold-build performance. The complete setup prevents mixing different native class
definitions from the earlier and expanded layouts.

The emitter helpers now accept explicit owner lists instead of introducing a new
special-case generation algorithm. Static non-inherited helper classes remain the
bounded supported lowering shape; instance methods, virtual dispatch, arbitrary
C++ templates, and general automatic semantic discovery are not established by
this experiment.

## Shared counter through existing writer and reader

The expanded-layout row test now adds `processed_count`, initializes it in the
existing `compiler_profile_events::append_event` writer, and consumes it in
`gate_profile_helpers::from_profile_event`. The witness creates a report, appends
an event, copies the real vector element, and creates a gate summary. It verifies
`processed_count:input_count:output_count = 8:7:8`, so this is not merely a field
access from main.

Three native runs take **6.523 / 6.574 / 6.792 seconds**, median **6.574 seconds**,
estimated median total **8.074 seconds**. The same eight objects plus link rebuild
each time; no PCH or runtime rebuild is present. The report's outlined lifetime
implementation remains on the critical path. This is meaningful row-layout and
writer/reader evidence, not a guarantee for arbitrary 200-consumer ABI changes.

## Numeric edit validated through the compiler pipeline and LLVM execution

A separate contemporary numeric case adds a public frontend helper and a new
source spelling `latency_i32`, updates both frontend lookup routes, and adds
historical-shaped explicit int32 alignment/type branches in the LLVM owner.
Before the edit, the actual compiler pipeline rejects the fixture. After the edit,
it accepts a function returning that type and a local variable of that type,
emits an i32 function with four-byte alignment, and the LLVM is compiled and run:
**exit code 42**, as expected, in all three trials.

Native compiler rebuilds take **3.172 / 2.888 / 3.111 seconds**, median **3.111
seconds**, estimated median total **4.611 seconds**. Seven objects plus link,
identical work sets, no PCH rebuild, caches disabled. The fixture's compiler-pipeline
run and LLVM compilation/execution are correctness checks outside the timed
rebuild of the compiler executable.

This is an end-to-end numeric alias feature using the existing int32 representation.
The backend branches preserve its existing meaning. It is not implementation of a
brand-new numeric representation, arithmetic ABI, or complete new integer family.
That boundary is explicit so a successful alias test is not overstated.

## Multi-consumer extraction: remaining large units exceed the budget

The larger extraction moves the actual `mt_publication_metrics::record_worker_gate`
body into a new PHS owner, preserves its existing public wrapper, and redirects all
eight production call sites across seven files. Both direct and wrapper calls are
exercised with ready and blocked inputs, verifying counters `1:0:1`. The full
application remains linked and its smoke/witness suite passes.

With the first expanded layout, three native runs take **9.814 / 9.369 / 9.804
seconds**, median **9.804 seconds**, estimated total **11.304 seconds**. Ten objects
plus link rebuild with no PCH rebuild. The slowest tasks are large unpartitioned
consumer implementations, notably capability readiness. This misses the native
budget despite narrow header dependencies. It is a separate granularity limit,
not another PCH invalidation failure.

The follow-up applies the existing stable partition emitter to the six remaining
large consumers. This changes implementation-object ownership only: native class
definitions and PCH inputs stay identical. Its one-time setup is recorded separately
before the same extraction is replayed.

## Partitioned multi-consumer extraction meets the budget

Applying the existing partition emitter to the six remaining consumers costs
**15.522 seconds** of one-time preparation/build in this warm application (separate
from edit timing). It reuses the existing PCH and unchanged native class definitions.
The exact extraction is replayed three times: **5.701 / 4.821 / 4.975 seconds native**,
median **4.975 seconds**, estimated median total **6.475 seconds**. Every trial checks
the same eleven objects plus link and verifies direct/wrapper counter values and
full-app smoke witnesses. No PCH or runtime rebuild is present.

The extra native unit compared with the ten-object unpartitioned case reflects two
changed methods in different stable groups. Smaller required compilations win over
minimizing the number of objects. The final graph links 744 objects; only eleven
are required for this extraction. The final application is rebuilt from restored
scratch sources, Ninja reports no work, and external source hashes still match.

The expanded frontend diagnostic check also forces a real call-depth exception and
verifies the original `frontend_model_builder::declaration_kind_function_id` name,
original PHS path, and source line 8 after free-function lowering.

## Assessment outcome and remaining scope

The bounded feasibility goal is achieved: the tested historical edit shapes can
produce a complete correct executable within the native budget when the future
emitter isolates callable surfaces, emits stable implementation groups and source
metadata, and respects complete-type/lifetime dependencies. The two newly exposed
failures were resolved by extending those existing concepts, not by hiding builds
behind cache hits or dropping dependencies.

| Latest edit shape | Native median | With assumed 1.5s frontend |
|---|---:|---:|
| Public frontend helper + frontend/backend changes | 3.822 s | 5.322 s |
| Shared row field + real writer/reader changes | 6.574 s | 8.074 s |
| Numeric source alias through emitted LLVM execution | 3.111 s | 4.611 s |
| New PHS MT helper + seven production consumer files | 4.975 s | 6.475 s |

Each row has three cache-disabled measurements, full compiler linking, and relevant
execution witnesses. These are deliberately chosen case studies, not a random edit
sample or evidence that 95% of every developer's edits will meet ten seconds.
A genuine change with hundreds of required consumers may take longer, as agreed.

The next phase would be implementation of a general emitter policy, rather than
more special-case patches: resolved callable/type dependencies; stable symbol,
partition and diagnostic identity; narrow new-file publication; correct complete
object layouts and lifetime operations; and content-preserving output writes.
The current production S2S is still type-blind. Automatic discovery, general
instance/virtual semantics, a brand-new numeric ABI, optimized builds, distributed
compilation and production cache recovery remain outside this bounded proof.

Frontend time remains assumed, not demonstrated. The full expanded setup cost is
271.501s; later consumer partition setup is separately recorded. Working settings
are Clang 18, `-O0 -g1`, mold, and Ninja `-j12` on this host. Ccache results are
reported separately, with the documented PCH/toolchain-invalidation constraints.

Raw measurements and integrity checks are in
[the result directory](../../tools/compile_latency/results/2026-09-19/).
Reproduction scripts and bounded assumptions are described in
[the experiment README](../../tools/compile_latency/README.md).
Decisions and emitter requirements are tracked in [the lessons log](s2s_compile_latency_lessons.md).


## Follow-up: deliberate search for edits above twenty seconds

The earlier passing matrix remains a bounded feasibility proof. Broader hunting
found a new **141.744-second** native edit on that same final partitioned layout:
a public helper addition in `frontend_model_tables`, two redirected frontend
callers, and a behavioral witness. It rebuilt 311 objects plus the project PCH
and link, at peak concurrency 12. Full application correctness and restoration
checks passed. No shared model data layout changed. This is a high-priority
remaining dependency-isolation opportunity; increasing jobs or changing the
linker alone does not explain away the fanout.

The ranked historical queue, raw evidence, and further screening results are in
[s2s_compile_latency_over20_hunt.md](s2s_compile_latency_over20_hunt.md).


A second screen, adding a shared `FrontendModelKernelCounters` field with a real
writer/reader, took **181.194 seconds**, rebuilding 372 objects plus a project PCH
and link. The full application and new counter witness passed. This is a genuine
native layout change and belongs in the accepted broad-rebuild category pending
an audit of which complete-type dependencies are actually necessary. Both new
figures are single screening trials, not medians. Neither invalidates the earlier
bounded passing cases; both rule out treating those cases as universal coverage.


## Treatment of the new large failures

Extending callable isolation to `frontend_model_tables` and adding a selective
factory/per-field reference-accessor boundary for `FrontendModelKernelCounters`
reduced the exact helper edit to **2.847 s median native** and the exact counter
layout edit to **3.373 s median native**, three final trials each. Every trial
rebuilt five objects and linked the full 864-object application, with no PCH
rebuild. Including the assumed frontend gives **4.347 / 4.873 s** respectively.
The one-time conversion build took 189.766 s and is excluded from edit timings.

Counter field access was 15.4% slower in a focused debug tight-loop test (12.29 ms
extra over five million updates); this is a selective emission tradeoff, not a
whole-application slowdown estimate. Diagnostics, aliases, all application
witnesses, source restoration and the final no-op build passed. Details and the
mapping to remaining historical cases are in the over-twenty-second solutions note.


## Follow-up: coordinated signature propagation

Nine parser signatures and sixteen calls across the frontend and incremental
parser proof source now have a measured current-workload analogue: **5.812 s
median native**, **7.312 s** including the assumed frontend, three trials with
fourteen objects plus full link and no PCH rebuild. Accepted LLVM exits with 42;
the changed policy rejects the paired fixture at the frontend. The original
arithmetic fixture is backend-blocked even in the retained original-object layout,
so this is not an exact historical int64-plus replay. See
[s2s_compile_latency_signature_propagation.md](s2s_compile_latency_signature_propagation.md)
for evidence, limitations, and remaining coverage.


## Historical coverage follow-up: one exact hunk and one value-layout exception

The int64-minus/type-traits commit changes 12 source files, introduces a 419-line
helper owner and adds 20 fields across three value records. It is a mixed change,
not a single small arithmetic edit. A current one-field `TypeTraitRow` analogue
with a real writer and independent-copy/vector witness took **146.265 s** native
(one screen; 429 objects + PCH + link). Genuine by-value layout propagation remains
an exception candidate, and incidental PCH fanout has not yet been isolated.

Separately, the exact `1610af1a` literal-readiness routing hunk was replayed in the
current program: **4.268 s median native**, **5.768 s** with assumed frontend,
three trials, one object + full link, no PCH. Before and after LLVM executions exit
42. This commit came from an archived 15-commit recent source-history sample with
no compile-time filtering. It does not establish a sample-wide success percentage.

See [s2s_compile_latency_historical_patterns.md](s2s_compile_latency_historical_patterns.md)
for the patch inventory, cleanup-preflight classification, evidence and next cases.


## Broader coverage changes the consistency conclusion

The frozen-output study now accounts for all 15 independently selected recent
source commits. Nine executable-code cases were timed: **three within target**
(native medians 2.622–4.268 s) and **six over target** (single native screens
138.970–177.867 s). One comment-only case required no native compilation. Four
commits have context mismatches, and one has an incompatible historical before-state.

All six slow replays rebuilt the project PCH and changed public surfaces outside
the selected isolated owner set. The feasibility demonstrations remain valid;
**consistent application across the program is not yet demonstrated**. The current
prototype isolates four callable owners, while a structural inventory identifies
103 simple candidates (not a semantic eligibility proof), plus more complex
mixed-owner files. Do not turn the passing-commit fraction into a normal editor-save
success estimate: this is a small, refactoring-heavy commit sample with replay limits.

See [s2s_compile_latency_coverage_study.md](s2s_compile_latency_coverage_study.md) for
the complete matrix, protocol, validation limits and archived evidence. Next apply
verified ownership/dependency rules consistently, then rerun this unchanged corpus;
do not keep adding only bespoke passing cases.


## Completed uniform-helper candidate assessment

The same 15-commit sample was replayed with 113 structurally eligible helper owners and stable implementation buckets. Result: four of nine timed code edits within the native budget; five over budget at 163.5–192.5 seconds; one comment-only no-compilation case; four context exclusions and one semantic replay incompatibility. The candidate fixes the prior 178-second environment-helper case (2.402-second median) but regresses the remaining slow screens. It is not promoted as an optimal general configuration.

All measured cases retain full-link, smoke and literal-LLVM validation; final source integrity, no-op build, source diagnostic and multi-owner regression proof pass. Original compiler sources remain unchanged. The next candidate must include callable adapter closures and narrow publication of newly introduced types. Details: `s2s_compile_latency_uniform_helpers.md`; raw evidence: `tools/compile_latency/results/2026-09-19/uniform-coverage/`.


## Callable adapter coverage assessment

The same fixed corpus improves to **seven of nine runnable code edits within the
native budget** when callable ownership includes required normalization adapters.
Three formerly slow helper edits now take native medians of 4.434, 3.162 and
2.194 seconds. Two type/layout edits remain slow at 237.076 and 290.232 seconds.
The latter still rebuild shared PCH consumers; this configuration is not yet a
solution for type publication. Four textual exclusions, one incompatible
before-state and the separate comment-only case remain visible.

The detailed adapter assessment records semantic proofs, the corrected
helper-versus-field-removal classification, full comparison and archival checks:
`s2s_compile_latency_adapter_boundaries.md`. This strengthens common-edit
feasibility without establishing a population-wide save-time guarantee.


## Consumer-specific type publication improves coverage

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


## Shared carrier publication completes the runnable corpus

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
