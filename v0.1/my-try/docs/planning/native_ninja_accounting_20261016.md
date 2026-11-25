# Native Ninja accounting comparison — 2026-10-16
Doc Status: supporting

Native compiler and Ninja builder rebuilt at **`-O2`**, with existing `-g` retained.
Base revision `03cf89a0` plus the authored portability fixes recorded below. Exact
source/toolchain hashes and all observations are in [the evidence](native_ninja_accounting_20261016/README.md).

Host: WSL2, AMD Ryzen 5 PRO 4650G, 12 logical CPUs. Compiler frontend: 12 jobs;
Ninja: 8 jobs. Accounting application: `-O0`, runtime PCH enabled, GNU `/usr/bin/ld`.
OS caches were not dropped. These are local measurements, not dev002 measurements
or a guarantee for a newer Ryzen system.

The fixture contains **528,088 bytes (515.7 KiB), 93 authored `.phs` files**.
It publishes **292 artifacts / 2,389,172 bytes**, including **93 translation units**.
Native linking uses 93 generated objects plus 3 runtime-provider objects.
The executable is **9,842,056 bytes**. Current grouping and compiler flags are preserved.

## Scope and method

Each scenario starts a **fresh native compiler process**. The unchanged and body-edit
runs reuse only the Ninja build directory; this is not a retained-compiler incremental
or MT-internals benchmark. Each full run uses a fresh publication and native directory.
The body edit changes one output string in `src/self_test.phs`, then Git restores it.
Three complete trials were run after a faster trace replay exposed first-run timing
variability. The tables below use the **median-total actual run for each scenario**,
not independently mixed component medians.

Compiler boundaries are measured using timestamped marker writes around setup,
sync/parse, preparation and emission/publication. Filesystem timestamps here are
quantized at approximately 4 ms; sub-millisecond precision is not implied.
Clang wrappers record monotonic durations and wait4 resource usage without adding
Clang profiling flags to the primary runs. A 10 ms nominal /proc sampler observes
process-tree RSS and native-compiler VmHWM. Sampling and wrapper overhead are included.
Tree RSS sums resident sets, including shared-page double counting; it is not PSS
or exact physical memory. Short peaks may fall between samples.

## Stage timings

All values are seconds. Parallel Clang time below is the **union of active tool
intervals**, not the sum of overlapping jobs. Distributed orchestration overhead
includes pre-launch work, gaps between tools and final status work; it is not all
work performed after linking.

| Stage | Full | Unchanged | Body edit |
| --- | ---: | ---: | ---: |
| Setup / publication preflight | 0.020 | 0.028 | 0.024 |
| Sync and parsing | 0.232 | 0.156 | 0.140 |
| Semantic preparation | 1.188 | 0.768 | 0.848 |
| C++ emission and publication | 0.516 | 0.432 | 0.436 |
| Request → C++ ready | 1.957 | 1.384 | 1.448 |
| Native orchestration, distributed | 0.246 | 0.120 | 0.214 |
| PCH + object compilation, active elapsed | 37.154 | 0.000 | 1.817 |
| Linking | 1.156 | 0.000 | 1.460 |
| Ready status → process exit | 0.027 | 0.031 | 0.037 |
| **Total through process exit** | 40.540 | 1.535 | 4.977 |

Request-to-C++-ready is a cumulative checkpoint, not an additional stage. Startup
before the first marker is included in that checkpoint and the total.

| Observation | Full | Unchanged | Body edit |
| --- | ---: | ---: | ---: |
| Total range, three trials | 40.050–47.484 s | 1.480–1.644 s | 4.527–6.329 s |
| Worst sampled process-tree RSS | 1570.3 MiB | 76.3 MiB | 343.0 MiB |
| Worst observed compiler VmHWM | 70.7 MiB | 71.4 MiB | 71.7 MiB |
| Objects compiled / reused | 96 / 0 | 0 / 96 | 1 / 95 |
| PCH built | 1 | 0 | 0 |
| Link commands | 1 | 0 | 1 |

For the representative runs, the successful linker exits at **40.460 s**
after the full request and **4.876 s** after the body-edit request.
Build readiness is recorded at 40.513 / 1.504 / 4.940 s.
The unchanged executable already exists; its timestamp is preserved. Readiness
verification is distinct from creating a new executable.

## Clang details

PCH generation overlaps runtime-provider compilation. Its individual durations
were 7.20, 4.54 and 4.29 s across the full trials. In the representative full trial,
generated-object wall durations sum to 194.28 s and runtime-provider durations to
22.19 s; neither sum should be added to end-to-end elapsed time.

For generated application objects, nearest-rank p80 was **3.14 / 2.85 / 2.73 s**.
**74 / 77 / 77 of 93** compiles finished within 3 seconds. Thus the 80% target was
met in two trials and narrowly missed in the first. The slowest object still took
**9.29–10.15 s**. This is evidence for the deferred splitting discussion, not a
change to current grouping or a promise that the target is universally met.

The edited object took **3.42 / 1.82 / 1.79 s** in the primary trials. A separate
isolated `-ftime-trace` replay measured **1.382 s Clang frontend**, **0.307 s LLVM
backend**, and **0.020 s unclassified compiler work** (1.76 s process wall time).
The backend includes 0.019 s optimizer and 0.285 s code-generation-pass subsets;
do not add nested subsets again. The replay is not an additive decomposition of
any primary build, especially the slower first observation.

## Validation and fixes

- All nine measured builds executed the accounting self-test successfully: 998
  baseline checks plus 857 large-fixture checks, zero failures.
- All three full native publications matched the independent PHP reference:
  identical paths and SHA-256 values for all 292 artifacts.
- All **94 CLI cases** passed after measurement. Native publication addition/removal
  also passed, exercising absent before/after journal digests.
- The existing PHP publication/recovery fixture passed.

Native conversion exposed a reserved `$char` local and unsupported `(string)` cast
in the Ninja adapter. Authored PHP now uses `$byte_text` and ordinary concatenation.
The native measurement entrypoint also needed an explicitly typed empty vector.
No generated C++ was patched.

The first native accounting attempt failed before Ninja because journal construction
used `$map[$path] ?? ""` on a missing typed-map key. Legacy S2S lowered the first
operand to throwing `hash_t::at`. Explicit `isset` guards now select the existing
empty-digest absence representation in `Output_Publication`. This is a local portable
authoring repair; publication policy and compiler incremental/MT internals are unchanged.
Native publication now runs directly, without the historical PHP publication workaround.

The compiler was built using the existing converter/framework and legacy S2S with
`--no-stan`, as required by my-try policy. This is bounded native compiler/Ninja and
accounting evidence, not a new whole-language or exhaustive incremental/MT checkpoint.
