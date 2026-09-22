# First scalability run — 2026-09-11
Doc Status: supporting

The initial five [generated projects](../../examples/scalability/README.md) compiled,
linked and executed successfully in all three baseline trials: **45 verified
native compile requests**. The 5/10 MiB extension below adds 18 verified requests.
No production compiler changes were needed.

Environment: PHP 8.5.7, Clang 18.1.3, Linux x86-64 under WSL2, AMD Ryzen 5 PRO
4650G. Xdebug, CLI OPcache and JIT disabled; compiler execution serial; native
object compilation uses the existing `-O0` setting. Cold starts a new PHP process
and compiler session, without flushing filesystem caches.

## Measurements

Times are medians of three trials in seconds around `Compiler_Session::compile`.
They include native compilation/linking. Peak memory is the maximum PHP allocator
reservation across the three phases and trials; it excludes Clang/linker memory.

| Target | Actual source bytes | Files / named functions | Fresh native build | Unchanged | One body edit | Peak PHP MiB |
|---|---:|---:|---:|---:|---:|---:|
| 64 KiB | 65,545 | 11 / 268 | 0.508 | 0.009 | 0.174 | 12 |
| 128 KiB | 131,227 | 19 / 535 | 0.611 | 0.013 | 0.201 | 22 |
| 256 KiB | 262,345 | 36 / 1,068 | 0.855 | 0.029 | 0.297 | 40 |
| 512 KiB | 524,335 | 69 / 2,133 | 1.410 | 0.053 | 0.480 | 78 |
| 1 MiB | 1,048,807 | 136 / 4,265 | 2.848 | 0.116 | 0.877 | 151 |

The 1 MiB body-edit trials ranged from **0.844 to 1.002 seconds**. These numbers
are observations for this workload and machine, not a one-second latency guarantee.
The largest baseline contains 221,843 tokens, 158,023 AST nodes, 38,371 checked
statements, 68,212 lowered instructions and 3,112,616 bytes of LLVM text.

Every generated function is called. Bodies use shared scalar/void helper calls,
typed locals, copying, assignments, nested scopes, shadowing and returns. Size
comes from code, without padding comments. Baseline and unchanged executables
return 42; the body edit changes the observable native result to 43.

## Early findings

1. **No obvious scaling cliff in this range.** Source size grew 16-fold; median
   fresh-build time grew about 5.6-fold, with fixed tool preparation costs included.
   Memory and unchanged-update time grew roughly with workload size. This is
   encouraging evidence for this particular source shape, not proof about all
   compiler inputs.
2. **Unchanged reuse is real but still visits project data.** Every unchanged
   trial replaced zero frontends, checked bodies and lowered bodies, and reused
   the native artifact. The separate 1 MiB diagnostic launched zero external
   tools on this phase. Its remaining work is consistent with the known discovery,
   selection, validation and index-assembly passes; individual PHP hotspots were
   not profiled.
3. **One function edit still replaces its file's sibling results.** Exactly one
   frontend changed per trial, but body/lowering replacements were respectively
   10, 21, 10, 19 and 7 across the five sizes: all functions in each edited final
   group. The last group size varies because generation stops near the byte
   target. Other files' bodies were reused. This confirms the existing
   [callable reuse finding](performance_watchlist.md).
4. **Native work dominates this body increment.** In three separate instrumented
   1 MiB trials, only module compilation and linking ran after the edit. Together
   they took 0.718–0.783 seconds, about 73–75% of each instrumented update. The
   whole module is compiled even though only seven lowered bodies were replaced.
   Wrapper overhead makes instrumented totals unsuitable replacements for the
   baseline table; the remaining time also includes wrapper startup and polling,
   so it is not a pure PHP-stage measurement.
5. **Memory deserves tracking, without an immediate redesign.** The 1 MiB fresh
   build reserved 146 MiB at peak, rising to about 151 MiB during its increment.
   The prototype retains several representations as PHP objects/arrays and
   overlaps old/new snapshots. This test does not isolate each representation's
   cost or establish long-running memory stability.

Recommendation: continue capability work. These results do not justify an
urgent architecture refactor. Keep repeated name lookup, callable reuse and
native module granularity on the watchlist; retain this workload as a comparison
point before and after relevant changes. The full-fallback repeat-work finding
was not exercised by this body-only increment.

## Extension: 5 MiB and 10 MiB

Both larger samples passed all three trials, including fresh native compilation,
unchanged artifact reuse, and execution after the body edit. The compiler-source
fingerprint, PHP/Clang versions and benchmark settings match the initial series.
The same 2 GiB PHP limit and existing per-tool timeout were sufficient.

| Target | Actual source bytes | Files / named functions | Fresh native build | Unchanged | One body edit | Peak PHP MiB |
|---|---:|---:|---:|---:|---:|---:|
| 5 MiB | 5,243,107 | 669 / 21,315 | 16.343 | 1.105 | 5.013 | 727 |
| 10 MiB | 10,485,859 | 1,335 / 42,627 | 34.594 | 1.768 | 10.469 | 1,446 |

Times are medians in seconds; memory is the maximum allocated PHP peak across
phases/trials, rounded to MiB, excluding Clang/linker processes. The 10 MiB fresh
trials ranged from 28.642 to 36.641 seconds; body edits ranged from 9.448 to
11.176 seconds. Host timing variation remains visible with only three trials.

The 10 MiB baseline has 2,217,866 tokens, 1,579,815 AST nodes and 31,371,607 bytes
of LLVM text. Both larger samples happen to have just one function in their
final group. Each edit therefore replaced one frontend and exactly one checked
and lowered body. All unchanged trials replaced zero frontends/bodies/lowered
bodies and reused the native artifact. Executable statuses remained 42 before
the edit and 43 afterward.

These runs expose a meaningful latency limit for the prototype: unchanged
requests alone now exceed one second, and native body increments take roughly
five and ten seconds. Fresh compilation and peak memory approximately double
from 5 to 10 MiB, without a compilation failure or abrupt memory-growth jump.
The earlier subsecond increment result does not extend to these larger projects.

A useful next investigation is profiling unchanged-build discovery, selection,
validation and index assembly. Zero body replacements do not make that work
free. These totals do not identify which PHP operation dominates. The separate
native-tool measurements below establish the Clang/link contribution without
profiling individual PHP operations. No speculative compiler fix was introduced
for the benchmark.

Run the extension with:

```sh
python3 benchmarks/scalability/run.py --sizes 5mb 10mb \
  --results build/scalability/large_run.json
```

The [large-run raw data](../../benchmarks/scalability/results/2026-09-11-large.json)
preserves every trial and its source/environment fingerprints.

## Separate native tool time and memory

Three additional instrumented trials per size measured each real Clang invocation
separately. All 18 compile requests passed executable verification (42, 42, 43
per trial). Source and compiler fingerprints match the uninstrumented large run.
No production compiler settings or code changed for this measurement.

Times below are medians in seconds; RSS is the maximum across three invocations.
Object compilation consumes the complete emitted LLVM module, including after
a one-function edit.

| Source size | Phase | Clang object time | Link time | Object peak RSS MiB | Link peak RSS MiB |
|---|---|---:|---:|---:|---:|
| 5 MiB | Fresh | 3.006 | 0.097 | 267.6 | 67.0 |
| 5 MiB | Body edit | 2.976 | 0.084 | 267.6 | 67.0 |
| 10 MiB | Fresh | 6.375 | 0.117 | 457.9 | 67.0 |
| 10 MiB | Body edit | 6.445 | 0.127 | 457.8 | 67.0 |

Every unchanged request launched **zero external tools**. Fresh builds also ran
five small version/target/return probes, recorded individually in the raw data;
body edits ran only object compilation and linking.

Native work accounted for 73–74% of each instrumented 5 MiB body edit and 66–79%
at 10 MiB. Object compilation is the main native cost; linking is comparatively
small. Clang body-edit times ranged from 2.970–3.045 seconds at 5 MiB and
6.270–7.091 seconds at 10 MiB. Instrumented total body-edit medians were
4.163/8.435 seconds. Host variation is visible; do not subtract these tool times
from the earlier uninstrumented totals to estimate PHP time.

The fresh forwarding wrapper times the real Clang invocation, excluding Python
startup. Linux child resource accounting records user/system CPU and peak RSS,
including waited descendants. Link measurements include the Clang driver and
the linker it launches. RSS is the **largest per-process high-water mark within
that invocation**, not simultaneous process-tree memory or an isolated linker
peak. It excludes PHP and the parent wrapper; adding it to PHP allocator peaks
would not establish combined peak memory. CPU measurements are retained in the
raw data alongside elapsed time.

Reproduce with:

```sh
python3 benchmarks/scalability/run.py --sizes 5mb 10mb --trace-tools \
  --results build/scalability/large_tool_resources.json
```

The [native resource raw data](../../benchmarks/scalability/results/2026-09-11-large-tools.json)
includes every invocation, measurement definitions and environment fingerprints.

## Reproduction and scope

The [benchmark README](../../benchmarks/scalability/README.md) defines
the method and commands. [Baseline raw data](../../benchmarks/scalability/results/2026-09-11-baseline.json)
contains all trials, exact fingerprints, counts and memory values;
[tool diagnostic data](../../benchmarks/scalability/results/2026-09-11-tools.json)
contains the separate Clang timings. Bootstrap, sample preparation, debug export
and running the executable are excluded from compile time.

This is one synthetic family: many small functions plus a growing entry body,
flat call depth and shallow nested scopes. It does not test parameters, generic
types, branches/loops, deep trees, many successive updates, deletion cleanup,
multithreading, or debug-export scalability. Three trials provide an early
baseline, not a statistical performance study.


## Follow-up implementation

The original numbers above describe the single-module compiler at that time.
File-based LLVM modules and retained objects were subsequently implemented;
see the [incremental investigation](incremental_native_investigation.md) and
[native executable contract](native_executable.md). Do not use the original
whole-module attribution as a description of the new path.
