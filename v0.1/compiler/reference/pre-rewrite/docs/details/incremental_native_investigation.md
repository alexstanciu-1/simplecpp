# Incremental investigation — 2026-09-11
Doc Status: supporting

Status: original investigation below; file-to-file emission and retained objects
were subsequently authorized and implemented. See the current
[native executable contract](native_executable.md). GC observations below remain
diagnostic evidence, not an adopted GC policy.
The subsequent [dispatch investigation](native_dispatch_investigation.md) corrects
interpretation of the 4.2-CPU diagnostic below: per-invocation wrappers reduce
throughput. The normal object phase used about 10.4 logical-CPU equivalents;
ordinary Clang 18 multi-file batches showed no improvement.
Continue serial PHP execution. No general dependency graph, threading, LTO,
cross-session object cache or broader incremental categories in this proposal.

## Where the update time goes

Following the [native tool measurements](scalability_first_run.md), two temporary
compiler copies timed coordinator calls on the 5 MiB fixture. The second also
recorded PHP cycle-collector counters/time. Both passed cold/unchanged/body-edit
native execution checks (42/42/43); the edit replaced exactly one checked and
lowered body. These are diagnostic observations, not new three-trial baselines.

Selected measurements from the second run, in seconds:

| Work | Unchanged | Body edit |
|---|---:|---:|
| Complete compile request | 1.014 | 4.292 |
| File discovery | 0.005 | 0.004 |
| Name resolution, including selection/join | 0.128 | 0.146 |
| Type resolution, including selection/join | 0.193 | 0.188 |
| Body checking, including selection/join | 0.075 | 0.089 |
| Lowering, including selection/join | 0.482 | 0.462 |
| Of lowering: PHP cycle collection | 0.457 | 0.438 |
| LLVM emission, including module assembly | 0.016 | 0.067 |
| Native preparation, including tools | 0.003 | 3.208 |

The lowering phase triggered a collection on each update which collected zero
garbage; excluding collector time leaves about 0.025/0.024 seconds there. This
does not justify adding lowering dependencies or redesigning its traversal.
It also does not prove that disabling GC indefinitely would be safe. Any GC
policy change needs separate repeated-update/failure and memory-growth evidence.
The cold diagnostic spent about 5.23 seconds in collection across measured phases,
also collecting zero garbage. PHP runtime costs must be distinguished from
compiler algorithms before drawing conclusions for the eventual native port.

[Raw phase/GC measurements](../../benchmarks/scalability/results/2026-09-11-phase-gc.json)
record the method and source fingerprint. `gc_status()` supplies cumulative
collector time; the probe subtracts phase entry/exit values. Its counters are
documented in the [PHP manual](https://www.php.net/manual/en/function.gc-status.php).
Nested name-selection/join timings and GC subsets must not be added twice.

## Dependencies: use them only where necessary

- [Body checking](../../src/04_analyze/check_bodies/main_check_bodies.php) already retains
  distinct signature/type dependencies, sharing authoritative contracts. It does
  not need another copy of the same dependency dataset.
- [Name resolution](../../src/04_analyze/resolve_symbols/main_resolve_symbols.php) still visits
  every call binding, extracts its source spelling and looks up its target in
  both selection and join. This is real repeat work, but not the main delay.
  If addressed, first consider a collection-owned indication that the name-to-ID
  mapping is unchanged. Under the current exact-name rules, unchanged syntax and
  an unchanged mapping need no per-use lookup. Signature/type validity remains
  the responsibility of later stages. Do not equate a body edit with a changed
  name mapping or use a body-change flag to skip unknown dependencies.
- If finer dependencies become necessary, retain distinct lookup dependencies
  only on results containing project symbol uses. Do not add lists to all AST
  nodes or build a reverse graph in anticipation of unsupported changes.
- Cross-file emission needs declarations of referenced functions. Derive these
  from existing resolved call contracts while processing changed emitted work;
  retain only the references needed to assemble/reuse modules. Do not rescan
  unchanged ASTs or repeat every project declaration in every LLVM module.

## Proposed backend boundary: one source file to one LLVM module/object

Keep the project-wide symbol model. Group emitted definitions by source-file
identity, with the native entry adapter in the manifest entry file's module.
Files with no emitted definitions need no empty native compilation task.

```text
src/main.phs    -> main.ll    -> main.o
src/helpers.phs -> helpers.ll -> helpers.o
src/worker.phs  -> worker.ll  -> worker.o
                              -> link executable
```

A body edit rebuilds its containing module/object and relinks using all current
objects. A caller in another file remains reusable when the callee's contract
is unchanged; the callee's body is not a caller object dependency without
cross-module optimization. Unsupported declaration changes still use full
selection through the same module/object path.

This requires explicit changes in three existing owners:

1. **Backend preparation (`lower/`).** Source-file ownership must be available
   through a public contract. Project functions currently use `internal` linkage;
   cross-object references need suitable external linkage and matching
   declarations/calling conventions. Linkage/visibility policy stays here, not
   in emitter special cases. See [LLVM linkage rules](https://llvm.org/docs/LangRef.html#linkage-types).
2. **Emission (`emit_llvm/`).** Keep reusable function IR, but assemble a set of
   file modules with their referenced declarations and fixed backend facts.
   Retain unchanged module results without rebuilding one project-wide IR string.
3. **Native building (`build_native/`).** Retain owned object files across accepted
   generations; today staging cleanup deletes `module.o`. Select stale/missing
   objects, compile each into private storage, then link the complete current
   object set. Failed compilation/linking must preserve old objects/executable;
   obsolete objects must not appear in the new link. Object storage lifetime must
   respect retained snapshots and candidates.

Session composition, debug exports and relevant tests must follow the new result
contracts. This crossed ownership boundaries; the user subsequently authorized this
file-to-file slice.

The first proof should use three files with cross-file calls, verify object reuse
after a body edit, zero tool work when unchanged, full selection, missing-object
recovery and failure followed by repair. Then rerun larger timing samples.
File-to-file partitioning is easy to reason about, but 669/1,335 files mean many
Clang startups during serial full builds. Full-build time could regress; measure
that tradeoff before considering stable groups of files. Do not add grouping or
parallel execution preemptively.

## Implemented proof and measurements

The authorized file-to-file slice is implemented. Native building uses
select/compile/join work units, executing serially. Cached objects are shared
file owners retained by accepted snapshots; failed attempts discard only their
new objects. A missing executable can relink without object recompilation.
Missing/tampered objects rebuild individually. Debug output is per file, and
the entry adapter remains in the manifest entry file's module.

Three fresh-process 5 MiB trials verified cold output 42, unchanged output 42
with zero tools, and body-edit output 43. Each cold build compiled 669 objects;
each edit compiled exactly one. Times are medians in seconds from the same
diagnostic-wrapper protocol as the original native measurements:

| Body-edit work | Previous single module | File modules |
|---|---:|---:|
| Complete compile request | 4.163 | 1.582 |
| Clang object compilation | 2.976 | 0.046 |
| Linking | 0.084 | 0.348 |

The body-edit median fell about 62%. Linking now reads hundreds of objects and
costs more, while object generation is restricted to the changed file. Unchanged
requests still take about 1.04 seconds; general PHP selection/validation was not
optimized in this slice. Peak PHP allocation across the traced run was about
724 MiB. These observations apply to this source family/machine.

Serial full builds regress: the instrumented cold median is 87.33 seconds
(83.59–87.78), including 669 Python wrapper startups. Total real Clang object
invocation time had a 31.68-second median; wrapper/polling and PHP work account
for additional time. A separate **unwrapped** cold build in the GC study took
51.65 seconds and verified exit 42. That is one observation, not a new
three-trial uninstrumented baseline. Keep the file-to-file model as requested;
grouping and parallelism remain deferred. Do not treat wrapper overhead as
production Clang work or promise the previous cold-build latency.

[File-module raw results](../../benchmarks/scalability/results/2026-09-11-file-modules-5mb.json)
preserve each trial and invocation. The measured implementation preceded final
API naming/comment cleanup and explicit private object permissions; those final
changes were regression-tested separately.

## GC scope experiment: no production policy change

One resident 5 MiB session then performed six body edits, alternating ordinary
GC with `gc_disable()` scoped around compilation and restoration immediately
afterward. The probe explicitly drained pending collection after each request
so moving work outside the compile timer remained visible. Pre-request collection
matched the earlier benchmark preparation and was recorded separately.

| Experimental mode (three edits each) | Compile median | Explicit post-compile collection median | Median of compile + post-collection |
|---|---:|---:|---:|
| Ordinary GC | 1.590 | 0.689 | 2.337 |
| GC suspended during compile | 1.093 | 0.692 | 1.757 |

Ordinary requests triggered one automatic collection during compilation; scoped
suspension triggered none. Both modes collected zero garbage in the measured
compile/drain intervals. Retained used memory was essentially stable across
these six edits, which does not establish general long-running memory safety.
All executable results (43 through 48) were verified.

The controlled protocol shows that avoiding an extra collection pass can save
work **when a boundary collection is already required**. However, the explicit
post-request drain is experimental, not our production policy. Merely suspending
GC improves the compile timer while leaving a roughly 0.66–0.77-second drain
in these trials. This does not justify globally disabling GC or introducing a
resident scheduling policy in this slice. Production GC behavior stays unchanged;
do not claim the experimental 1.09-second timer as delivered performance.

[GC-scope raw results](../../benchmarks/scalability/results/2026-09-11-gc-scope.json)
include compile, pre/post collection, GC counters, retained memory and exits.

## Twenty concurrent external compilations

The subsequent user-authorized experiment sets `compile_jobs: 20` in
`tools/backend.json`. This limits concurrent external Clang invocations; PHP
compiler workers remain serial. Full builds and increments dispatch the same
selected file-module jobs. Linking waits for the complete object set. Scheduling
changes alone preserve the backend context and existing object/executable cache.
See the [process lifecycle and platform requirements](native_executable.md#concurrent-native-compilation).

Three **unwrapped** 5 MiB trials all compiled and executed correctly:

| Request | Median seconds | Range seconds | Object compilations |
|---|---:|---:|---:|
| Cold | 21.597 | 20.501–21.985 | 669 |
| Unchanged | 1.199 | 1.038–1.535 | 0 |
| Body edit | 1.413 | 1.409–1.548 | 1 |

Compared with the earlier single unwrapped serial cold observation of 51.647
seconds, the new cold median is about 58% lower (roughly 2.4 times as fast).
This comparison is not a matched three-trial serial baseline. A one-file edit
has no object-level parallelism available; its timing changes should not be
attributed to running 20 compilers. The runner also now polls at one millisecond
through a shared process lifecycle. Production GC behavior remains unchanged.

One additional instrumented run verified 669/0/1 object compilations and native
exit statuses 42/42/43. Its cold request took 32.283 seconds including forwarding
wrapper overhead. Monotonic real-Clang intervals showed a peak of **15 overlapping
invocations** in the 20-slot queue. The object interval spanned 18.370 seconds;
summed Clang/descendant user+system CPU was 76.212 seconds, equivalent to about
4.15 CPU-seconds per wall second over that interval, excluding PHP and wrapper CPU.
This is Clang CPU usage expressed in logical-CPU equivalents, not a count of
physical cores or overall machine utilization. The request timings in the table
above include PHP, Clang, linking and publication.
Small jobs can finish during dispatch; a limit of 20 does not guarantee 20
simultaneously executing Clang processes throughout a build.

Summed object invocation elapsed time was 120.413 seconds and must **not** be
reported as native wall time: those intervals overlap. The largest individual
process RSS peak was 139.6 MiB; this does not measure the concurrent process
tree's aggregate memory peak. The body-edit object took 0.039 seconds and its
link took 0.280 seconds in that diagnostic trial.

The full regression suite and dedicated concurrency proof passed. The latter
uses an explicit barrier to prove 20 simultaneously active jobs, a three-job
increment, cancellation of failed jobs and descendants, timeout cleanup, repair,
unchanged reuse and positive-limit validation. No worker serialization or
background PHP execution was introduced.

Raw results: [unwrapped three-trial run](../../benchmarks/scalability/results/2026-09-11-parallel20-5mb.json)
and [instrumented overlap/CPU run](../../benchmarks/scalability/results/2026-09-11-parallel20-tools-5mb.json).
The final launcher-output protection addition was verified separately after
measurement; it does not change object generation or scheduling.

## Repeat with 20 and 40 jobs

After the user closed other applications, the same 5 MiB project was measured
again: three unwrapped trials and one instrumented trial at 20 jobs, then the
same sequence at 40. No benchmark runs overlapped. The fixture contains 669 source
files and 21,315 named functions. WSL exposes six physical cores / twelve logical
CPUs, with all twelve allowed by this process's affinity. This does not establish
how much host CPU capacity was available throughout each trial.

**Total compiler request times**, including PHP stages, Clang and linking:

| Limit | Cold median (range), seconds | Unchanged median (range), seconds | Body edit median (range), seconds |
|---|---:|---:|---:|
| 20 | 15.823 (15.601–18.451) | 0.888 (0.848–0.920) | 1.187 (1.159–1.218) |
| 40 | 15.528 (14.639–19.452) | 0.857 (0.806–0.859) | 1.138 (1.100–1.217) |

The repeat at 20 is about 27% faster than the earlier 21.597-second cold median.
The 40-job median is only 1.9% faster than the new 20-job median, inside broad
overlapping trial ranges. These ordered trials do not isolate application load,
OS cache effects or other environmental variation. They do not establish a
reliable benefit from 40 jobs. The repository setting is restored to **20**.
Unchanged uses no native tools; the body edit compiles one object, so neither
request has object-level parallel work that can benefit from the larger limit.

**Separate instrumented Clang measurements** (one trial per limit):

| Limit | Total request with wrappers | Object invocation interval | Clang/descendant CPU seconds | Average logical-CPU equivalents | Peak overlapping Clang invocations | Link invocation |
|---|---:|---:|---:|---:|---:|---:|
| 20 | 23.667 s | 13.073 s | 54.875 | 4.20 | 15 | 0.276 s |
| 40 | 22.639 s | 12.229 s | 52.250 | 4.27 | 32 | 0.256 s |

The object interval spans first real-Clang start to last completion, not the
entire native stage. CPU equivalents divide summed child CPU by that interval;
PHP and wrapper CPU are excluded. Overlap counts include processes waiting as
well as executing. Doubling the queue increased overlap substantially, but
barely changed average Clang CPU usage. This does not identify the underlying
waiting/dispatch bottleneck; it shows that more outstanding jobs alone did not
produce proportional CPU use. A future investigation should distinguish process
launch, scheduling and I/O costs before changing the compiler model.
That [follow-up is now recorded](native_dispatch_investigation.md): these wrapper
measurements must not be used to infer CPU utilization of the unwrapped compiler.

Summed overlapping object elapsed times were 87.634 / 165.788 seconds; neither
is wall time. Each body-edit object took about 0.035 seconds, followed by a
0.262 / 0.255-second link. Instrumentation adds Python wrapper startup per
invocation, so use the unwrapped table for total compiler comparisons.

The benchmark now records GC counter/time deltas around `compile()` without
changing production collection. Every unchanged request in the six unwrapped
trials triggered one collection and collected zero garbage. Median GC time was
0.370 seconds at 20 jobs and 0.344 at 40; the median remainder of each request
was 0.515 / 0.506 seconds. Thus the roughly 0.9-second unchanged request includes
a substantial PHP collector traversal plus retained-result validation, index
assembly and artifact integrity checks. It is not Clang recompilation. The
earlier phase breakdown above identifies those compiler traversals; no new
per-stage profile was taken for this repeat. Pre-request GC remains outside the
compile timer as documented in the benchmark protocol.

All eight trials verified native exits 42/42/43 and object replacement counts
669/0/1; both instrumented unchanged requests launched zero tools. Peak PHP
allocator use was 724 MiB in unwrapped runs. No production algorithm changed.
The source fingerprint includes `tools/backend.json`, so the recorded compiler
hash differs between limits even though only scheduling configuration changed.

Raw reports: [20 jobs](../../benchmarks/scalability/results/2026-09-11-retest20-5mb.json),
[40 jobs](../../benchmarks/scalability/results/2026-09-11-retest40-5mb.json),
[20-job tool measurements](../../benchmarks/scalability/results/2026-09-11-retest20-tools-5mb.json),
[40-job tool measurements](../../benchmarks/scalability/results/2026-09-11-retest40-tools-5mb.json).
