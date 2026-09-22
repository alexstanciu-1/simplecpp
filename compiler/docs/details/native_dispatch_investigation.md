# Native dispatch and batching — 2026-09-11
Doc Status: supporting

The earlier **4.2 logical-CPU equivalents** described an instrumented run with
one Python wrapper per Clang invocation. It does **not** describe utilization
of the normal compiler: the wrapper substantially changes dispatch throughput.
An unwrapped object phase used about **10.4 logical-CPU equivalents**, with guest
CPU samples near 100% busy. There is no four-CPU ceiling here.

## Normal dispatch, CPU and I/O

A temporary copy of the current PHP compiler timed stream preparation,
`proc_open()` and the whole native object pool using `hrtime()` and `getrusage()`.
It used the real Clang directly with the existing 20-job queue. Guest `pidstat`,
`vmstat` and `iostat` sampled at one second. No production algorithm changed.
The same 5 MiB project compiled and executed correctly for cold/unchanged/body-edit
requests: exits 42/42/43, object replacements 669/0/1.

| Measurement | Observation |
|---|---:|
| Complete cold request, including PHP and tools | 15.029 s |
| Native object pool, 669 objects, excluding final link | 5.033 s |
| Child user CPU during pool | 23.209 CPU-seconds |
| Child system CPU during pool | 29.183 CPU-seconds |
| Child CPU / pool wall time | 10.41 logical-CPU equivalents |
| PHP user + system CPU during pool | 0.994 CPU-seconds |
| Sum of 669 `proc_open()` elapsed times, inside pool | 3.242 s |
| Sum of input/output stream preparation elapsed times, inside pool | 0.428 s |

Child usage includes the launcher, Clang and waited descendants. Launch timers
overlap child execution and are nested within the pool: do not add these times
to the 5.033-second pool or treat 3.242 seconds as removable idle time.

During the busy object intervals the guest reported about 43–45% user CPU,
55–57% system CPU and virtually zero idle/I/O wait. No swapping occurred.
Clang child block-input counters were zero, with about 6.6 MiB of block output;
this was an OS-cache-warm run. Disk samples during the busy intervals showed
roughly 1.2 MiB/s of writes and short queues. This is **not evidence of a
storage-bound LLVM workload**. Clang still performs file and memory-mapping
operations; zero disk wait does not mean zero kernel work.

The benchmark's own child CPU accounts for most of the observed load. There is
no evidence that unrelated processes explain the earlier 4.2 figure. Visibility
is limited: `pidstat` sees the execution namespace, and Windows process inspection
through PowerShell failed with WSL `UtilBindVsockAnyPort: socket failed 1`.
We therefore cannot claim all host applications were idle or exclude host
contention in other runs. The guest aggregate counters do show that the normal
object phase uses the available CPU capacity substantially.

The large PHP parent was an initial hypothesis, not a finding. Replaying the same
LLVM from a small Python coordinator took 5.043 seconds (median), essentially
matching the resident PHP pool. Launch overhead exists, but this comparison does
not support blaming the compiler's roughly 700 MiB resident snapshot.

## Batches of files: supported, but no demonstrated gain

The installed Clang 18.1.3 accepts:

```sh
clang -O0 -x ir -c first.ll second.ll third.ll
```

It produces separate objects, so batches need not change our per-file cache
boundaries. With several outputs, the driver cannot accept one common `-o` path;
a future implementation would need private staging and unambiguous output names.
Twenty such drivers can run concurrently, each processing its inputs sequentially.

However, **Clang 18 disables integrated execution when there is more than one
job**. Each file gets a separate compiler child process even when
`-fintegrated-cc1` is explicitly supplied. The
[Clang 18.1.3 driver rule](https://github.com/llvm/llvm-project/blob/llvmorg-18.1.3/clang/lib/Driver/Driver.cpp#L4509-L4513)
and its [sequential job loop](https://github.com/llvm/llvm-project/blob/llvmorg-18.1.3/clang/lib/Driver/Compilation.cpp#L221-L238)
explain the behavior. A local 32-file probe observed 32 child process IDs both
with and without that flag. Thus fewer driver launches do not remove per-file
compiler startup on this toolchain.

The same 669 real LLVM files were replayed with at most 20 concurrent drivers.
Three trials per batch size all produced 669 nonempty objects, linked and exited
42. Input export, final linking and executable verification were outside these
**object-compilation-only** timers:

| Files per driver | Driver launches | Median seconds | Range seconds |
|---|---:|---:|---:|
| 1 | 669 | 5.043 | 5.014–5.128 |
| 8 | 84 | 5.546 | 5.313–5.778 |
| 32 | 21 | 5.446 | 5.159–6.181 |

These ordered trials demonstrate feasibility, not a benefit from batching. They
are isolated object replays, not new complete-compiler totals or an implemented
incremental batch scheduler. Each driver still executes its compiler children
serially; uneven final batches can also leave fewer active workers near the end.

## Startup cost and the next decision

A further diagnostic launched `clang --version` 669 times with 20 concurrent
processes. Its three-trial median was **4.201 seconds**, with roughly 29 seconds
of child system CPU and zero child block-input counts per trial. Real object
compilation took 5.043 seconds in the comparable small-coordinator replay.
This strongly suggests repeated process/library initialization and kernel work
are a major cost for our small modules. It is not an exact subtractive attribution:
the work, scheduling and timing distribution differ between the two experiments.
No kernel-level profile was taken to separate individual system-call costs.

Keep **20 jobs and one file per invocation** for now. Ordinary multi-file Clang
batches do not offer a measured improvement on the installed version. If reducing
startup cost becomes necessary, evaluate an actual reusable LLVM backend process
or the planned LLVM API integration, preserving separate module/object identities.
That is a distinct implementation slice; do not introduce it or merge source
modules just to inflate the CPU-utilization number.

For future measurements, collect aggregate child CPU around the **unwrapped**
object pool. Keep per-invocation wrappers as diagnostics with a stated observer
effect, not as a proxy for production CPU utilization.

Evidence: [dispatch measurements](../../benchmarks/scalability/results/2026-09-11-native-dispatch.json),
[guest system samples](../../benchmarks/scalability/results/2026-09-11-native-system-samples.json),
[batch replays](../../benchmarks/scalability/results/2026-09-11-native-batches.json),
[driver child observations](../../benchmarks/scalability/results/2026-09-11-clang-driver-children.json),
[startup probes](../../benchmarks/scalability/results/2026-09-11-clang-startup.json).
Reproduction commands and diagnostic scope are in the
[benchmark README](../../benchmarks/scalability/README.md).

## PHP-only confirmation

The compiler already launches Clang using PHP `proc_open()`, polls with
`proc_get_status()` and reaps with `proc_close()` through `Tool_Process`.
The ordinary Python runner starts the PHP benchmark once per trial; it does not
wrap each Clang call unless `--trace-tools` is supplied. No production launcher
switch is needed.

At the user's request, the shell launched PHP directly for both microbenchmarks
and three complete compiler trials. No Python process ran in these tests. The
tool microbenchmarks used pre-exported real LLVM, with up to 20 active handles
through the production process owner, including its `setsid` launcher. Object
verification, linking and checking exit 42 were outside the object timers.

| PHP-only workload | Median seconds | Range seconds |
|---|---:|---:|
| 669 version commands, 20 parallel | 6.610 | 6.134–7.973 |
| 669 object compilations, 20 parallel, excluding link | 6.819 | 6.805–7.670 |
| Complete cold compiler request | 18.167 | 15.376–19.539 |
| Unchanged compiler request | 1.077 | 0.861–1.227 |
| One-body-edit compiler request | 1.189 | 1.184–1.615 |

The object trials averaged about 10.6 logical-CPU equivalents. All three object
replays exited 42; all nine complete compiler requests verified exits 42/42/43
and object replacement counts 669/0/1. These are different timing scopes:
the standalone object replay is not a stage breakdown of the complete trials.

Timing conditions varied during this session: cold requests fell from 19.539 to
15.376 seconds, with GC time also falling from 5.098 to 4.013 seconds. The higher
microbenchmark timings versus the preceding Python replay are not evidence of
a language-caused slowdown. Those experiments ran at different times and the
Python replay also omitted the production stream/launcher lifecycle.

Five subsequent alternating single-command shell/PHP checks gave shell times
of 0.03–0.04 seconds (`/usr/bin/time`, rounded to hundredths) and PHP direct
`proc_open()` times of 0.040–0.047 seconds, median 0.041 seconds. The initial
five PHP single-command probes earlier in the session took 0.044–0.074 seconds.
The user's shell observation of 0.033 seconds concerns **one** invocation; the
earlier 4.201-second result concerned **669**, with up to 20 concurrent processes.
Concurrent throughput need not scale linearly from one isolated launch.

[PHP-only raw measurements](../../benchmarks/scalability/results/2026-09-11-php-only.json)
include the individual trials and single-command comparison. Production code and
the 20-job configuration remain unchanged.

## Seven-group compiler breakdown

Three fresh PHP-only trials profiled the same 5 MiB fixture with 20 parallel
Clang jobs. Stage timers were added only to a temporary compiler copy. The
numbers below are **arithmetic means**, so each column accounts for the entire
mean `compile()` time (apart from displayed rounding). They are new measurements,
not a retroactive subdivision of the earlier 18.167/1.077/1.189-second medians.

| Group | Full build, s | Unchanged, s | Body edit, s |
|---|---:|---:|---:|
| Manifest, source scan/read and tokenization | 0.716 | 0.005 | 0.004 |
| Parsing, symbol collection/resolution and change comparison | 2.682 | 0.187 | 0.165 |
| Type resolution, body checking and lifetime analysis | 4.761 | 0.308 | 0.296 |
| Backend preparation and lowering | 2.043 | 0.560 | 0.498 |
| LLVM emission and native entry adapter | 0.985 | 0.036 | 0.033 |
| Native cache checks and object compilation | 6.691 | 0.032 | 0.071 |
| Linking, publication and remaining coordination | 0.336 | 0.001 | 0.317 |
| **Complete compile call** | **18.215** | **1.129** | **1.385** |

The first group includes the language catalog read. The second includes entry
selection and the incremental comparison; the admission decision itself and
other untimed coordinator bookkeeping are included in the final group.
Backend preparation includes its target/ABI tool probes. Native preparation is
timed as a whole, then its nested link duration is subtracted and assigned to
the final group. The final group also includes publication and the residual
between measured stages and the complete request, including lock lifecycle.
No overlapping child times are summed into wall time.

GC is **already included** where it ran: mean collection time was 4.778 seconds
for full builds, 0.475 for unchanged requests and 0.418 for body edits. On each
unchanged request that collection occurred in lowering; it accounts for most
of that column's backend/lowering group. This is not evidence of renewed body
lowering work. Unchanged requests replaced zero objects and reused their exact
artifact; their native/link groups consist of checks and coordination.

All nine requests verified executable exits 42/42/43 and object replacement
counts 669/0/1. Full-request totals ranged from 17.845 to 18.665 seconds;
unchanged totals ranged from 0.986 to 1.344, and body edits from 1.281 to 1.516.
Benchmark setup, PHP startup/shutdown and executable verification remain outside
the `compile()` timer. Production sources and GC policy were not modified.

[Raw stage/group timings](../../benchmarks/scalability/results/2026-09-11-grouped-stages.json)
retain each trial, all individual stages and their nested GC counters. Reproduce
with `XDEBUG_MODE=off php benchmarks/scalability/profile_groups.php`.

## Linker selection and unchanged reuse

Implemented after the investigation above. Multi-CPU execution includes separate
processes as well as threads. PHP semantic work stays serial; the existing
bounded Clang queue uses the same selected file jobs for full builds and
increments. Linking follows their join and can use the linker's own workers.
Non-goals: PHP threading, changing the GC policy, a new dependency framework,
and a persistent/incremental linker implementation.

The session now stops semantic processing after complete discovery proves no
pending file work and manifest, language catalog and verified backend inputs
remain current. It shares entire accepted stage results, with fresh scan facts
and an empty change catalog. Native selection/repair/publication remains the
common path. Missing/tampered artifacts cannot be mistaken for a successful
unchanged output.

`LLVM_Toolchain` separately owns a fixed linker contract. A changed linker
selection or executable stat identity invalidates the executable, preserving
objects and semantic/LLVM results. `tools/backend.json` now selects `mold`;
null/omission follows Clang's default. Explicit failures never fall back.
Clang still generates objects and acts as the linker driver, supplying target
startup/runtime inputs. It invokes the selected linker on **all current
objects**; this is faster relinking, not incremental final linking.

Five trials per linker rotated order while sharing the same 669 real native
objects from the 5 MiB fixture. PHP `Tool_Process` launched Clang with explicit
`--ld-path`; no Python or forwarding wrapper. Every executable returned 42.
Timers include the Clang driver and linker, but exclude object preparation,
hashing, publication and verification. Installed versions were Clang/LLD 18.1.3,
GNU ld 2.42 and Mold 2.40.4.

| Linker | Median invocation, s |
|---|---:|
| GNU ld (BFD) | 0.261 |
| LLD | 0.158 |
| Mold | 0.112 |

Mold reduced this invocation by about 57% versus BFD on this workload. Both
[Mold](https://github.com/rui314/mold) and [LLD](https://lld.llvm.org/) provide
alternatives to the system linker. This measurement justifies selecting Mold
here, not assuming the same speedup on every target or project.
[Raw linker trials](../../benchmarks/scalability/results/2026-09-11-linkers.json).

Three subsequent PHP-only compiler trials used the same seven-group protocol as
above, including its pre-request GC preparation. These are complete `compile()`
times, not just tool times. Arithmetic means, seconds:

| Group | Full | Unchanged | Body edit |
|---|---:|---:|---:|
| Inputs, scan and tokenization | 0.683 | 0.004 | 0.004 |
| Parsing and symbols | 2.425 | 0 | 0.147 |
| Types, body checks and lifetimes | 3.779 | 0 | 0.241 |
| Backend preparation and lowering | 1.782 | 0 | 0.380 |
| LLVM emission and entry adapter | 0.864 | 0 | 0.027 |
| Native cache checks and object compilation | 4.734 | 0.018 | 0.056 |
| Linking, publication and coordination | 0.107 | 0.003 | 0.107 |
| **Total** | **14.374** | **0.024** | **0.963** |

Before these changes the matching protocol measured 18.215 / 1.129 / 1.385 s.
The unchanged improvement follows the removed traversals. Do not attribute the
entire full/body-edit difference to the changes: native object time and PHP GC
also varied between runs. The isolated linker comparison is stronger evidence
for the linker improvement. All trials verified exits 42/42/43 and object
replacement counts 669/0/1.

The body-edit native breakdown is now explicit:

| Work | Mean, ms |
|---|---:|
| Select/validate retained objects, join results and prepare object paths | 14.876 |
| Compile/seal the changed object | 37.272 |
| Clang plus Mold invocation | 104.658 |
| Hash the staged executable | 3.434 |
| Publish executable | 1.742 |
| Prepare/adopt publication snapshots, excluding executable publication | 0.053 |

These are measured scopes, not an exhaustive partition of total request time;
small configuration/workspace/coordination costs remain outside them. The seven
main groups above are the complete partition. The older group-six convention
includes the new executable hash, while group seven includes linking and
publication; do not add nested measurements twice. There is no large
publication/bookkeeping cost to refactor here. Unchanged integrity validation
still reads cached object and executable contents; it accounts for most of the
remaining 24 ms.

GC accounts for 0.316 s of the body edit's 0.380 s backend/lowering group. The
remaining approximately 0.064 s includes actual preparation, selection and
joins; the collector cost is not evidence of expensive per-body lowering.
An additional resident-session test deliberately made **no** calls to force or
disable GC: cold build, eight edits each followed by unchanged, invalid edit,
and repair. Cold compile took 16.011 s, then releasing old result readers took
0.439 s, including another 0.437 s collection outside `compile()`. Body edits
ranged from 0.658 to 1.485 s: requests without collection took about 0.66 s;
others included 0.396–0.815 s of collection. The collector found zero cycles.
Unchanged requests took 0.024–0.026 s and triggered no collections. Retained PHP
memory remained about 698 MiB across successful updates. Invalid input preserved
the published snapshot; repair produced the expected exit 51.

The zero-cycle result does not prove every future compiler/error path is
acyclic. Disabling GC globally would change the resident process's memory
contract; forcing collection outside the request would merely move its cost.
Keep the policy unchanged. The repeat demonstrates that the expensive collection
is real and variable, rather than a reason to redesign lowering now.

Proofs: full prototype regression suite, the new `native_reuse.php`, existing
parallel dispatch/cancellation proofs, and all measured executable checks.
Raw reports: [new grouped stages](../../benchmarks/scalability/results/2026-09-11-reuse-mold-groups.json),
[natural resident updates](../../benchmarks/scalability/results/2026-09-11-resident-updates.json).
