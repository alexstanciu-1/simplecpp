# Full native benchmark: my-try-simple — 2026-11-04

Doc Status: supporting

Compiler revision: `40a046e72`, plus benchmark harness/report additions only. Local WSL, Ryzen 5 PRO 4650G (6 physical / 12 logical CPUs), PHP 8.5.7, Xdebug absent, CLI OPcache disabled. Clang 18.1.3, mold 2.40.4.

257 PHP-flavor source files, 42,195 bytes, 256 public functions and one implicit entry become **258 C++ translation units** including the executable adapter. This is a supported-syntax sample mirroring compiler directories, not a self-hosting compiler.

## Measurement boundaries

Ninja -j4, mold, -g0, libc++, no LTO/ccache. Existing runtime archive excluded from rebuild. Warm filesystem caches; fresh application objects each run. PCH validation enabled. Independent trace runs; trace sums are cumulative elapsed compiler phases, not CPU or parallel wall time. Link timings include the Clang driver. Ninja edge timestamps have millisecond resolution. End-to-end is measured PHP process plus native build, including module loading and emitted-file writes.

Five samples per configuration, rotated configuration order. Every measured run reloads and compiles PHP sources, writes all emitted C++ files, recompiles all application objects, and links the executable. Existing Ninja graphs and dependency metadata are reused. Setup, cleanup, verification and instrumentation are outside the timing. No incremental native reuse is claimed.

Fresh PCH means the runtime-header PCH is rebuilt within each timed native build. Reused PCH is built during untimed warm-up and its timestamp is verified unchanged. Application sources are never part of the PCH.

The first attempted run was discarded: deleting Ninja logs also deleted command history and rebuilt the reused PCH. The corrected run preserves the log and reads the latest timing record for each output.

## Complete native and end-to-end times

All values are milliseconds; brackets show min–max. End-to-end includes the PHP subprocess/startup, module loading, compiler stages, generated-file writes and native build. It excludes rebuilding the existing runtime archive.

| Opt | Runtime PCH | PHP process median | Native median [range] | Total median [range] |
| --- | --- | ---: | ---: | ---: |
| O0 | no-pch | 90.71 | 2799.38 [2751.88–2823.93] | 2890.09 [2841.64–2915.79] |
| O0 | fresh-pch | 91.28 | 2918.83 [2883.82–3037.36] | 3010.88 [2975.10–3126.80] |
| O0 | reused-pch | 91.66 | 2862.64 [2837.76–2928.14] | 2954.30 [2928.06–3023.08] |
| O1 | no-pch | 92.36 | 3004.26 [2901.85–3089.16] | 3098.15 [2993.79–3181.52] |
| O1 | fresh-pch | 95.13 | 3072.69 [3036.02–3619.56] | 3167.82 [3126.36–3722.84] |
| O1 | reused-pch | 90.77 | 3080.50 [2969.51–3137.68] | 3171.27 [3059.07–3229.20] |
| O2 | no-pch | 90.55 | 2942.51 [2905.86–3004.18] | 3032.48 [2996.41–3102.56] |
| O2 | fresh-pch | 93.55 | 3105.33 [3096.68–3300.98] | 3200.35 [3187.46–3395.69] |
| O2 | reused-pch | 94.71 | 3066.46 [3032.44–3325.69] | 3164.40 [3124.46–3420.39] |

## Native build components

Compile window is the elapsed span containing all application compile jobs at `-j4`. Compile sum adds per-process durations and can exceed wall time. Link is the measured link command, including Clang driver startup and mold. PCH is a separate build edge and can overlap independent compilation; do not add these medians to reconstruct wall time.

| Opt | PCH | Compile window median | Compile duration sum median | Link median [range] | PCH build median |
| --- | --- | ---: | ---: | ---: | ---: |
| O0 | no-pch | 2723.00 | 10847.00 | 72.00 [70.00–77.00] | 0.00 |
| O0 | fresh-pch | 2839.00 | 11119.00 | 72.00 [69.00–75.00] | 48.00 |
| O0 | reused-pch | 2786.00 | 11042.00 | 71.00 [70.00–72.00] | 0.00 |
| O1 | no-pch | 2924.00 | 11591.00 | 72.00 [69.00–76.00] | 0.00 |
| O1 | fresh-pch | 2994.00 | 11719.00 | 72.00 [71.00–88.00] | 48.00 |
| O1 | reused-pch | 2999.00 | 11941.00 | 71.00 [69.00–72.00] | 0.00 |
| O2 | no-pch | 2866.00 | 11378.00 | 71.00 [70.00–72.00] | 0.00 |
| O2 | fresh-pch | 3023.00 | 11837.00 | 76.00 [71.00–81.00] | 49.00 |
| O2 | reused-pch | 2988.00 | 11914.00 | 71.00 [71.00–79.00] | 0.00 |

## Clang frontend and LLVM detail

One separate instrumented rebuild per configuration, summed over all 258 application translation units. These are cumulative elapsed phase durations, not CPU-time measurements or parallel wall time. Backend includes LLVM optimization and machine-code generation. Optimizer and CodeGenPasses are nested detail and must not be added to Backend. PCH creation is excluded from these application totals; its build time is above.

| Opt | PCH | Clang Frontend | LLVM Backend | Optimizer (nested) | CodeGenPasses (nested) |
| --- | --- | ---: | ---: | ---: | ---: |
| O0 | no-pch | 452.37 | 643.07 | 15.91 | 387.80 |
| O0 | fresh-pch | 402.69 | 648.54 | 16.77 | 391.74 |
| O0 | reused-pch | 390.66 | 640.64 | 16.79 | 387.05 |
| O1 | no-pch | 463.73 | 1254.66 | 419.28 | 409.11 |
| O1 | fresh-pch | 436.30 | 1327.62 | 442.04 | 433.17 |
| O1 | reused-pch | 409.21 | 1243.94 | 423.74 | 398.69 |
| O2 | no-pch | 466.18 | 1274.38 | 447.48 | 399.63 |
| O2 | fresh-pch | 407.81 | 1277.75 | 453.77 | 398.05 |
| O2 | reused-pch | 405.53 | 1266.06 | 443.03 | 397.33 |

## mold internal linker profile

Separate instrumented run, milliseconds. These values exclude the Clang driver; the repeated link-command medians above include it.

| Opt | PCH | mold wall | mold user CPU | mold system CPU |
| --- | --- | ---: | ---: | ---: |
| O0 | no-pch | 24.00 | 41.00 | 153.00 |
| O0 | fresh-pch | 24.00 | 28.00 | 152.00 |
| O0 | reused-pch | 29.00 | 34.00 | 189.00 |
| O1 | no-pch | 26.00 | 41.00 | 169.00 |
| O1 | fresh-pch | 27.00 | 60.00 | 117.00 |
| O1 | reused-pch | 24.00 | 38.00 | 152.00 |
| O2 | no-pch | 27.00 | 66.00 | 132.00 |
| O2 | fresh-pch | 27.00 | 54.00 | 156.00 |
| O2 | reused-pch | 24.00 | 87.00 | 100.00 |

mold `--perf` detail is retained in each configuration’s `trace-build.log`; it is instrumented linker evidence, separate from the repeated uninstrumented link timings above.

## Correctness and artifacts

Every measured executable printed exactly `my-try-simple: OK\n` and exited with status 8. All binaries were checked for absence of `.debug_` sections. All builds used `-g0`, and no application object was reused.

Application sources were copied to the benchmark directory before compilation; original sample sources and previous benchmark reports are preserved.

Full raw samples, Ninja logs/graphs, generated C++, executables, per-unit Clang JSON traces and mold performance output are retained at:

/home/alexv/__AI/simple_cpp/simple_cpp_02/v0.2/build/my-try-simple-native-2026-11-04-verified

[Portable results summary and raw timing samples](benchmark-2026-11-04-native.json).

```sh
python3 -B v0.2/samples/my-try-simple/native-benchmark.py \
  --output v0.2/build/my-try-simple-native-new-run --samples 5
```

The output directory must not already exist. The previous ~24 ms staged total stopped at C++ text emission and is not the complete compilation time.

## Interpretation

At O2 without PCH, the complete build median is 3,032 ms (about 3.03 seconds),
including 2,943 ms of native work. The PHP process median is about 91 ms including
startup/boot, module loading, source compilation and generated-file writes; it is
not comparable to the earlier ~24 ms internal compiler-stage-only total.

The PCH reduces traced frontend work modestly, but does not improve overall wall
time on this many-tiny-files workload. At O2, Frontend plus Backend account for
about 1,741 ms of cumulative traced work, versus roughly 11,378 ms of cumulative
application compile-command durations in the uninstrumented runs. Separate-run
instrumentation prevents treating that difference as a precise decomposition,
but it suggests substantial per-process/driver/scheduling/I/O cost outside those
phases. There are 258 compiler invocations; grouping generated units is a sensible
future experiment, not an optimization performed in this benchmark.
