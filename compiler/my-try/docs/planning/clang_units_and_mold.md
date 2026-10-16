# Clang unit latency, parallel builds, and Mold link layouts
Doc Status: planning

Recorded: 2026-10-16. User-approved planning direction; implementation deferred.
This extends the [compilation performance task](compile_performance.md) and
[Chapter 08 artifact roadmap](incremental_cpp_artifacts.md). It is not a language
spec, an implementation authorization, or a claim that the targets are achieved.

## Priority and target

Prioritize the latency of a single Clang object-compilation invocation. Parallel
throughput alone cannot repair a slow unit on the incremental critical path.
The development target is **at least 80% of actual object compilations finish within
2–3 seconds**: treat 3 seconds as the initial p80 ceiling and 2 seconds as the
preferred direction. Faster invocations are welcome. This is a percentile target,
not a 2–3 second arithmetic average or a whole-build latency promise.

Measure wall time from starting Clang to completion, excluding scheduler queue time,
PCH creation, dependency scans, and linking; report those costs separately. Cache
hits are not compilations and must not dilute the percentile. Record p50, p80, p95,
maximum, sample count, and the identities of slow units. Separate isolated latency
from latency under parallel load, and cold compilation from warm incremental work.
State optimization/debug settings, PCH state, tool versions, CPU/RAM, filesystem,
and competing host load for every comparison. Use representative units and edit
patterns rather than repeatedly sampling only one cheap body edit.

Assume an NVMe drive and a modern multicore CPU, with at least a 2023-era Ryzen-class
machine as the intended hardware class. Historical dev002 results are useful
comparison evidence, not proof of this hardware-specific target. Let the OS cache
files normally; distinguish cold artifact caches from cold filesystem caches.

## Future compilation-unit splitting

Keep current source-unit grouping as the starting point. Consider splitting costly
units into smaller, stable implementation groups when measurements justify it.
An authored source file need not map to exactly one C++ translation unit.

- The C++ backend owns grouping, stable artifact identity, and declaration/body
  placement. Shared semantic preparation does not acquire C++ scheduling policy.
- Preserve unrelated group names and membership across local edits; avoid global
  repartitioning that invalidates otherwise reusable objects.
- Keep ordinary bodies out of broadly shared headers where practical. Preserve
  required template definitions and useful inlining opportunities deliberately.
- Measure frontend/header/template cost separately from LLVM optimization/codegen.
  Smaller units can duplicate expensive frontend work and add process overhead.
- Do not default to one object per function or select a source-line threshold before
  measurement. Splitting around one expensive function cannot split that function's
  own compilation cost; such outliers need separate investigation.
- Compare rebuild scope, object/header counts, cache size, total CPU/memory, and
  application execution performance as well as per-invocation latency.

## Parallel orchestration to evaluate

Start with a consistent published C++ revision, a stable runtime PCH, and cached
per-unit objects. Independent Clang processes should run concurrently once their
actual inputs are ready. Multiple consumers can read a completed PCH concurrently;
independent PCHs can build concurrently, while dependent PCH chains must wait.
Do not add a global header/PCH barrier where only a particular branch depends on it.
Initially complete source publication before launching object compilations.

Evaluate Ninja as an execution scheduler versus a bounded worker pool in the native
builder. Scheduler selection is not settled by this plan. The native-build owner
retains authoritative action/cache identities and publication/recovery policy.
Define Ninja's timestamp/dependency bookkeeping boundary explicitly if selected;
do not introduce two competing cache-validity decisions. Workers use separate
staging outputs, atomically publish success, and cannot mark a mixed or failed
revision ready. Reused objects remain inputs to the final link.

Measure compile-job counts 1, 2, 4, 8, and 16, with a declared memory budget.
Control linker thread count independently: a Ninja job is a command, not a CPU
thread reservation. Avoid oversubscription when multiple links/builds overlap.
One-object edits primarily benefit from faster individual actions; many-object
edits and cold builds benefit from scheduling parallelism. NVMe does not eliminate
memory-bandwidth, CPU-cache, or aggregate-memory limits.

## Mold comparison: one executable versus up to 20 shared libraries

Compare the same application with many small object files under two layouts:

| Candidate | Intended incremental behavior |
| --- | --- |
| All application objects linked into one executable with Mold | Compile affected units, then relink the executable using new and cached objects. |
| Executable plus stable groups in up to 20 application shared libraries | Compile affected units and relink affected libraries; reuse other libraries and the executable when their dependencies allow it. |

Use stable subsystem groups, not one shared library per source unit. Evaluate a few
useful group counts (for example 2, 5, 10, and 20), not just the maximum. The cap
refers to application groups, excluding ordinary system/runtime shared libraries.
Static archives are not independently replaceable runtime groups and do not answer
this experiment. Retain ordinary ahead-of-time execution; no custom JIT runner.

Interface/ABI/layout changes may require rebuilding consumers across group boundaries.
Specify symbol visibility, ABI dependencies, runtime loading paths, initialization
order, cross-library exceptions/ownership, and coherent executable/library publication
before implementation. Do not assume that an unchanged exported name proves ABI
compatibility or that every library edit can leave the executable untouched.

Measure both startup (especially with warm OS caches) and steady-state application
performance. Shared-library calls, visibility/PIC choices, and reduced cross-unit
optimization can affect execution. Compare equivalent build settings and behavior;
record required layout-specific differences rather than attributing everything to
linking. Choose a grouped layout only if edit/build/run gains justify its runtime
and maintenance costs. No runtime regression budget has yet been agreed.

Mold integration belongs to the native-build owner and
[CH15-MOLD-01](../catalog/15_blocking_debt.md#ch15-mold-01--configurable-mold-linking).
Fingerprint the selected linker and relevant configuration in link-cache identity.
Tune 1, 2, 4, 8, 16 threads and the installed version's default using identical
object inputs within each layout. Do not assume more threads are faster for small
links. Keep normal executable-ready measurements distinct from `--no-fork` resource
accounting; report total CPU, peak memory, median/range, and time to a runnable
executable with all required libraries available. Separate residual post-readiness
cleanup from work that must finish before readiness.

## Future experiment sequence and decision gates

1. Resolve the observed retained-native repeated-body-edit crash and establish
   correct repeated edit/revert behavior before claiming sustained incremental success.
2. Establish per-unit latency distributions and parallel scaling on existing grouping.
3. Prototype stable splitting only for identified costly units; verify invalidation
   scope, cache reuse, behavior, and application performance.
4. Compare Mold's single-executable layout against grouped shared libraries using
   the same application and comparable small-unit inputs; tune link threads per layout.
5. Record the chosen scheduler, grouping policy, optimization profile, link layout,
   and measured tradeoffs before authorizing production integration.

Cover cold/no-op, one and multiple body edits, header/signature/layout edits, unit
addition/removal, Git revert, and failure/retry. Report request-to-C++ publication,
individual compilation and queue time, request-to-runnable-output, process completion,
startup, and steady-state runtime separately. Report object/PCH/link reuse and
aggregate memory alongside largest-process RSS. Keep profiles separate from normal
latency runs. Development optimization levels and optimized runtime reuse should be
compared without silently using release settings for only one candidate.

No compiler, scheduler, unit splitter, linker integration, shared-library packaging,
test, or benchmark is implemented or run by recording this plan. LLVM/IR caching,
direct LLVM lowering, LTO, and hot replacement of libraries in a running process
are outside this slice.
