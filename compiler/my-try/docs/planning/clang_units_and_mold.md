# Clang unit latency, parallel builds, and Mold link layouts
Doc Status: planning

Recorded: 2026-10-16. User-approved planning direction; implementation deferred.
This extends the [compilation performance task](compile_performance.md) and
[Chapter 08 artifact roadmap](incremental_cpp_artifacts.md). It is not a language
spec, an implementation authorization, or a claim that the targets are achieved.

## Scope clarification — 2026-10-16

This work owns C++ lowering architecture and the shape of the generated build inputs:
fast full compilation, short individual Clang invocations, and small native rebuild
sets after edits. Treat compiler incremental processing and multithreading as
will-be-working capabilities owned elsewhere. Reviewing, repairing, or proving those
internals is not a prerequisite for this work. The previously observed retained
compiler crash remains an external issue, not a gate on lowering design discussion.

Cross-run object/PCH reuse is optional and must not materially slow this work or block
progress. If pursued here, **Ninja alone owns native output freshness and reuse between
runs**. Our side supplies stable generated files, commands, and dependency edges.
Do not develop a competing content-addressed object cache or cache-validity layer for
this exploration. The existing native consumer/cache is an implementation fact;
this plan neither removes it nor layers its reuse decisions beneath Ninja. A later
integration decision must keep the two paths' responsibilities explicit.

Distinguish a fresh compiler process using a retained build directory from a clean
build with no artifacts. Ninja can reuse the former's current valid outputs; an empty
build directory has nothing to reuse. Ordinary Ninja bookkeeping is not a historical
content cache and does not promise reuse of every prior version after Git revert.
Stable output paths, unchanged bytes/timestamps, stable commands, accurate dependencies,
and a persistent build directory enable this optional path. If integration is costly,
defer it and continue reviewing generated C++ using the existing compilation flow.

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

## Immediate architecture: explicit implementation-group assembly

Agreed scope on 2026-10-16: prepare a proper split structure in C++ lowering so
Ninja, Clang, and Mold can consume clear artifact boundaries. Preserve current
source-based grouping; do not change it to one group per class or namespace now.
Automatic splitting and multi-file classes are separately deferred in
[CH15-CPP-GROUP-SPLIT-01](../catalog/15_blocking_debt.md#ch15-cpp-group-split-01--automatic-implementation-unit-splitting)
and [CH15-PARTIAL-TYPE-01](../catalog/15_blocking_debt.md#ch15-partial-type-01--multi-file-class-declarations).
Neither deferred feature blocks the immediate structure or toolchain integration.

The proposed local backend separation is:

1. Render existing prepared declaration/body fragments with their C++ requirements.
2. Assemble named implementation groups containing the appropriate body fragments,
   storage definitions, dispatch definitions, and their header/forward-declaration
   requirements. Initially retain today's source membership, ordering, and paths.
3. Materialize declaration headers, group translation units, entry, and helper
   artifacts through the existing artifact/publication protocol.
4. Let the build integration consume artifact paths, kinds, and dependencies to
   describe PCH, object, and link actions. Keep commands and scheduling out of
   semantic preparation and expression rendering.

Group records should be simple backend-owned data; assembly behavior belongs to a
backend worker. Reuse existing signature/body fragments and dependency facts rather
than building a second semantic model or an additional cache. Keep declaration/header
assembly separate from implementation-body assembly, and keep entry/helper identities
explicit. Do not require a generalized partitioning framework for this first slice.

Preserve generated semantics, output identities, source grouping, and the publication
contract. The intended initial change is a clear owner for group assembly, not an
increase in compilation units or a promised timing improvement. No group should
imply a shared-library boundary: future link groups may collect many object units.

The 2–3 second p80 target remains a performance goal. Automatic splitting is one
future way to approach it, not an immediate acceptance gate. Header/template exposure
and expression complexity remain discussion candidates, not part of this structural
slice by default. No implementation is performed by this planning update.

## Parallel orchestration to evaluate

Start with a consistent published C++ revision, a stable runtime PCH, and cached
per-unit objects. Independent Clang processes should run concurrently once their
actual inputs are ready. Multiple consumers can read a completed PCH concurrently;
independent PCHs can build concurrently, while dependent PCH chains must wait.
Do not add a global header/PCH barrier where only a particular branch depends on it.
Initially complete source publication before launching object compilations.

Ninja is the intended owner if optional between-run reuse is pursued, not merely a
scheduler under a second cache authority. A future adapter would describe commands,
outputs, and dependencies; Ninja would determine freshness and schedule necessary
actions. No new worker-pool implementation or concurrency correctness audit belongs
to this slice. Artifact publication must still provide consistent inputs and completed
outputs, but redesigning its recovery protocol is outside scope.

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
Represent the selected linker and relevant configuration in the chosen build path's
inputs/commands so a change triggers the necessary link; do not add a new cache layer.
Tune 1, 2, 4, 8, 16 threads and the installed version's default using identical
object inputs within each layout. Do not assume more threads are faster for small
links. Keep normal executable-ready measurements distinct from `--no-fork` resource
accounting; report total CPU, peak memory, median/range, and time to a runnable
executable with all required libraries available. Separate residual post-readiness
cleanup from work that must finish before readiness.

## Future experiment sequence and decision gates

1. Review current grouping, header visibility, generated expression structure, and
   runtime/PCH exposure without entering compiler incremental or MT internals.
2. Establish per-unit latency distributions and build throughput on existing grouping
   when measurements are separately authorized; cross-run Ninja reuse is optional.
3. Prepare explicit implementation-group assembly while preserving existing output
   grouping. Automatic splitting and multi-file classes remain CH15 debt.
4. Compare Mold's single-executable layout against grouped shared libraries using
   the same application and comparable small-unit inputs; tune link threads per layout.
5. Record the chosen scheduler, grouping policy, optimization profile, link layout,
   and measured tradeoffs before authorizing production integration.

For future lowering comparisons, cover clean builds, fresh processes with retained
artifacts when available, and representative body/header/signature/layout edits.
Measure changed native inputs and rebuild spread; do not turn these experiments into
a compiler incremental/MT correctness campaign or make such a campaign a prerequisite. Report request-to-C++ publication,
individual compilation and queue time, request-to-runnable-output, process completion,
startup, and steady-state runtime separately. Report object/PCH/link reuse and
aggregate memory alongside largest-process RSS. Keep profiles separate from normal
latency runs. Development optimization levels and optimized runtime reuse should be
compared without silently using release settings for only one candidate.

No compiler, scheduler, unit splitter, linker integration, shared-library packaging,
test, or benchmark is implemented or run by recording this plan. LLVM/IR caching,
direct LLVM lowering, LTO, and hot replacement of libraries in a running process
are outside this slice.

## Initial lowering review — 2026-10-16

Read-only inspection of the recovered compiler at `d6ac157c`; no new timing claims.

- `05_backend/cpp/artifacts.php` derives stable callable/type paths from qualified
  names and implementation paths from module/source identity. `generate.php` emits
  narrow callable/type headers, per-source implementation units, separate entry,
  and specialized helper artifacts. Preserve this foundation.
- `generate_program()` already holds separate signature/body fragments, but combines
  all bodies for a source into one unit and unions their include sets. A future named
  implementation-group assembly step could reuse those fragments and compute includes
  per group. Large source files currently have no explicit compilation-cost bound.
- `types.php` distinguishes declaration from complete-definition requirements, while
  template arguments and callable payload types conservatively request completeness.
  Review representative dependency expansion before proposing narrower runtime binding
  contracts; do not blindly replace complete includes with forward declarations.
- Ordinary operator lowering and the runtime PCH include the broad generated
  `scpp/generated/operators.hpp` (1,366 lines at this checkpoint, with scalar, mixed,
  wrapper, and container dependencies). Narrow runtime surfaces are a candidate;
  the runtime generator owns changes to that generated header. PCH coverage already
  amortizes some work, so source inspection alone does not establish the net saving.
- Eager binary operations and calls use immediately invoked lambdas and temporaries
  to preserve evaluation order, value snapshots, references, and lifetime. Consider
  a backend-owned expression result plus ordered statements as a future shape, with
  lazy branches and expression-only contexts handled explicitly. This is a conceptual
  alternative, not permission to remove sequencing or introduce semantic analysis in
  the emitter; its size and compile/runtime benefit remain unmeasured.

Immediate direction: preserve existing boundaries and isolate implementation-group
assembly as a clear backend owner. Defer automatic split policies and multi-file
classes to CH15; no split thresholds are selected now. Toolchain integration should
not depend on those features, broad runtime changes, or shared-library packaging.
