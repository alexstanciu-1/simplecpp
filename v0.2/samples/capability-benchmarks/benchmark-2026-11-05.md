# Refreshed capability benchmarks — 2026-11-05

Doc Status: supporting

Times in milliseconds, **average / minimum** of five samples per workload.
Five warmed PHP compilations, five fresh application native builds, and five separate instrumented builds.
O2/g0, libc++, Ninja -j8, mold; no PCH/LTO/ccache; prebuilt runtime. One C++ TU per source, plus the entry adapter.

| Sample | Source KiB / C++ TUs | Generated C++ KiB | PHP total | Native wall | Linker |
| --- | ---: | ---: | ---: | ---: | ---: |
| small | 3.9 / 5 | 11.9 | 6.066 / 5.133 | 147.534 / 139.070 | 61.200 / 58.000 |
| medium | 48.6 / 16 | 131.2 | 73.138 / 66.956 | 350.593 / 332.250 | 66.800 / 59.000 |
| large | 488.7 / 68 | 1308.0 | 895.188 / 797.389 | 3425.983 / 2963.613 | 117.400 / 88.000 |

| Sample | Module | Read | Tokenize | Parse/collect | Symbols | Prepare | Lower/emit |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| small | 0.317 / 0.223 | 0.237 / 0.127 | 1.335 / 1.130 | 2.400 / 1.996 | 0.053 / 0.042 | 1.099 / 0.943 | 0.607 / 0.474 |
| medium | 0.441 / 0.390 | 1.728 / 1.444 | 21.389 / 17.854 | 27.630 / 25.039 | 0.270 / 0.223 | 15.555 / 13.839 | 6.001 / 5.551 |
| large | 0.904 / 0.770 | 8.862 / 7.990 | 235.847 / 203.415 | 303.589 / 278.165 | 2.938 / 2.312 | 243.757 / 211.291 | 98.604 / 81.757 |

PHP total includes measured orchestration, excludes process startup and writing emitted C++ files.

| Sample | Clang frontend | LLVM backend | LLVM optimizer | Code generation | Instrumented wall |
| --- | ---: | ---: | ---: | ---: | ---: |
| small | 64.492 / 53.004 | 71.073 / 59.850 | 33.894 / 30.197 | 27.222 / 21.291 | 169.676 / 151.698 |
| medium | 325.085 / 287.355 | 630.462 / 597.408 | 411.376 / 384.397 | 185.628 / 172.851 | 335.075 / 314.341 |
| large | 3985.668 / 2757.058 | 9564.891 / 6834.566 | 6573.956 / 4697.874 | 2740.903 / 1957.953 | 3097.241 / 2150.460 |

Trace categories accumulate across parallel TUs and overlap (optimizer/code generation are within backend). Do not sum them or compare their sum to build wall time. Native wall above excludes tracing overhead.

| Sample / edit | Frontend | Prepare | Total | Source reads | ASTs reused | Bodies prepared / reused |
| --- | ---: | ---: | ---: | ---: | ---: | --- |
| small / full | 5.379 / 4.257 | 1.130 / 0.987 | 6.509 / 5.266 | 4 | 0 | 12 / 0 |
| small / noop | 0.514 / 0.396 | 0.089 / 0.055 | 0.603 / 0.450 | 0 | 4 | 0 / 12 |
| small / body_edit | 0.967 / 0.707 | 0.270 / 0.181 | 1.237 / 0.888 | 1 | 3 | 2 / 10 |
| small / signature_error | 1.092 / 0.755 | 0.379 / 0.328 | 1.472 / 1.136 | 1 | 3 | expected diagnostic |
| small / recovery | 1.068 / 0.921 | 0.393 / 0.257 | 1.461 / 1.244 | 1 | 3 | 3 / 9 |
| small / noop_after | 0.448 / 0.334 | 0.089 / 0.059 | 0.537 / 0.394 | 0 | 4 | 0 / 12 |
| medium / full | 50.507 / 41.604 | 12.504 / 11.473 | 63.011 / 53.599 | 15 | 0 | 59 / 0 |
| medium / noop | 1.141 / 0.953 | 0.710 / 0.550 | 1.850 / 1.560 | 0 | 15 | 0 / 59 |
| medium / body_edit | 1.401 / 1.170 | 0.785 / 0.692 | 2.186 / 1.862 | 1 | 14 | 2 / 57 |
| medium / signature_error | 1.590 / 1.184 | 1.054 / 0.829 | 2.644 / 2.013 | 1 | 14 | expected diagnostic |
| medium / recovery | 1.464 / 1.137 | 1.357 / 1.134 | 2.821 / 2.271 | 1 | 14 | 3 / 56 |
| medium / noop_after | 1.020 / 0.775 | 0.579 / 0.386 | 1.599 / 1.162 | 0 | 15 | 0 / 59 |
| large / full | 871.334 / 681.292 | 273.843 / 244.352 | 1145.177 / 944.144 | 67 | 0 | 523 / 0 |
| large / noop | 9.176 / 7.678 | 32.649 / 7.809 | 41.825 / 15.992 | 0 | 67 | 0 / 523 |
| large / body_edit | 9.192 / 7.076 | 10.379 / 8.359 | 19.572 / 15.435 | 1 | 66 | 2 / 521 |
| large / signature_error | 9.724 / 7.347 | 13.295 / 10.073 | 23.019 / 18.256 | 1 | 66 | expected diagnostic |
| large / recovery | 9.436 / 8.043 | 21.943 / 15.316 | 31.379 / 23.359 | 1 | 66 | 3 / 520 |
| large / noop_after | 9.420 / 6.949 | 9.993 / 7.561 | 19.413 / 15.502 | 0 | 67 | 0 / 523 |

Five retained-session edit sequences per workload: full, no-op, body edit, incompatible signature, recovery, final no-op. Assertions verify source reads, AST/token identity, body reuse and caller invalidation. Edits use disposable copies.
Incremental timings stop after preparation; no incremental native-build speedup is claimed.

All 30 measured/traced executables matched expected stdout and exit code 8; measured executables contain no debug sections. Runtime includes use the public API.
The mixed-flavor workloads exercise current feature families, not every syntax permutation or rejection path. Focused tests retain those checks. The large workload repeats invoice functions; this is synthetic data.
This is a new workload baseline, not a like-for-like speed comparison against the older samples.
Raw samples, ranges, versions, source hashes, compiler hashes, logs, generated C++, and per-run Clang traces are retained in the benchmark output directory.

Raw run: `v0.2/build/capability-benchmarks-2026-11-05-refresh/`.
