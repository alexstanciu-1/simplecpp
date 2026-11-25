# Accounting compiler benchmarks — 2026-10-20
Doc Status: planning

The compiler executable is the validated O2/no-debug native request driver from
`81913306`'s native acceptance. Application, middleware and runtime objects are
O0/no-debug. Measurements use the same accounting sources and verified outputs.

## Method

`measure.py` compares fresh PHP CLI and native processes running the same
`S2S_Proof::chapter14` request. Five measured rounds follow one discarded warmup;
order alternates. Cases run serially, with no benchmark suites overlapping.
Publication uses a fresh output directory every time. It includes process startup,
catalog loading, source parsing/preparation, C++ generation, disk publication and
artifact encoding. In prelowered mode the shared harness additionally repeats
preparation/emission and verifies identical output. Therefore comparisons between
PHP and native within a mode are matched; ordinary versus prelowered publication
is not a single-pass lowering-cost comparison. Filesystem caches are warm.

The warm-build measurement calls the same production native builder from each
engine with the existing validated cache. Every run must compile/link nothing.
It includes dependency/cache validation and subprocess startup; this is not a
retained incremental compiler session or an edited-source benchmark. PHP uses
fresh CLI processes, not the FPM/OPcache execution model.

`units.py` adapts the existing retained application-compilation benchmark. Three
rounds compile all 16 application units sequentially and with 12 jobs. Ordinary
C++ uses a reusable runtime PCH; strict prelowered application inputs use no PCH.
Existing runtime/middleware objects and layouts are reused. PCH creation, semantic
lowering, publication, dependency scanning and template-policy validation are
outside these application-compilation timings. Link time is recorded separately;
every resulting executable must match accepted accounting output. Compilation
uses Clang directly, without the timing proxy. Mode/order alternate across rounds.

Raw logs are retained under `/tmp/native-compiler-benchmark-20261020` and
`/tmp/native-unit-benchmark-20261020`. Summaries retain individual samples, ranges,
source/unit identities, toolchain and executable identity. These are local WSL
wall-clock measurements, not isolated-machine or cold-filesystem results.

## Reproduction

```sh
python3 specs/planning/results/native_compiler_benchmark_2026_10_20/measure.py \
  --native-workspace /tmp/prelower-native-compiler-20261020 \
  --acceptance /tmp/prelower-native-compiler-20261020/logs-17/prelower-accounting \
  --results /tmp/native-compiler-benchmark-20261020
python3 specs/planning/results/native_compiler_benchmark_2026_10_20/units.py \
  --acceptance /tmp/prelower-native-compiler-20261020/logs-17/prelower-accounting \
  --results /tmp/native-unit-benchmark-20261020
```

## Compiler results

Medians of five measured runs (one warmup excluded):

| Request | PHP CLI | Native O2 | Speedup |
|---|---:|---:|---:|
| ordinary publication | 3.777 s | 0.365 s | 10.34× |
| prelowered publication | 6.261 s | 1.378 s | 4.54× |
| ordinary warm-build | 6.144 s | 4.646 s | 1.32× |
| prelowered warm-build | 5.632 s | 3.830 s | 1.47× |

All publication outputs are byte-identical across engines and rounds; manifests
match native acceptance. All warm builds report zero compilation/link/PCH work.
The final executables retain the accepted 998/0 accounting output. The summaries
include min/max ranges. Do not compare ordinary/prelowered publication as equal
work: only prelowered requests include the additional replay check.

## O0 application compilation results

Medians of three measured rounds; existing support objects and PCH reused:

| Compilation | Ordinary with PCH | Prelowered | Speedup |
|---|---:|---:|---:|
| 16 units, sequential | 24.152 s | 2.913 s | 8.29× |
| 16 units, 12 jobs | 5.841 s | 0.636 s | 9.19× |
| slowest unit, sequential | 3.147 s | 0.336 s | 9.37× |
| slowest unit, 12 jobs | 5.831 s | 0.601 s | 9.70× |

Slowest units are selected by their median duration: `src/closing_checks.phs`
for ordinary, `src/checks.phs` for prelowered. The same `closing_checks.phs`
unit drops from 3.147 s to 0.290 s sequentially (10.85×). Parallel per-unit
wall times include CPU contention and should not be treated as isolated latency.
Median linking is 0.51–0.54 s, recorded separately from compilation.
All 12 linked executables match the accepted 998/0 accounting output.

The native compiler materially reduces generation/publication time. Prepared
interfaces retain the substantial application-compilation benefit even against
ordinary C++ with reusable PCH. Warm-build validation remains a noticeable cost;
this benchmark does not implement or measure retained incremental compilation.
These separate measurements must not be added into a claimed fresh-build total:
they reuse support artifacts and exclude their creation.
