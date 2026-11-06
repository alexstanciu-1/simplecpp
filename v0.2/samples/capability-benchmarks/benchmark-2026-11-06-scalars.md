# Capability benchmarks

Doc Status: supporting

Times in milliseconds, **average / minimum** of five samples per workload.
Five warmed PHP compilations, five fresh application native builds, and five separate instrumented builds.
O2/g0, libc++, Ninja -j8, mold; no PCH/LTO/ccache; prebuilt runtime. One C++ TU per source, plus the entry adapter.

| Sample | Source KiB / C++ TUs | Generated C++ KiB | PHP total | Native wall | Linker |
| --- | ---: | ---: | ---: | ---: | ---: |
| small | 3.9 / 5 | 11.1 | 5.322 / 5.123 | 142.728 / 139.406 | 60.400 / 57.000 |
| medium | 48.6 / 16 | 118.3 | 64.733 / 62.204 | 325.730 / 307.074 | 67.600 / 63.000 |
| large | 488.7 / 68 | 1164.7 | 915.204 / 849.698 | 1925.458 / 1855.355 | 71.800 / 66.000 |

| Sample | Module | Read | Tokenize | Parse/collect | Symbols | Prepare | Lower/emit |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| small | 0.194 / 0.173 | 0.124 / 0.113 | 1.160 / 1.083 | 2.171 / 2.069 | 0.043 / 0.040 | 1.085 / 1.065 | 0.537 / 0.513 |
| medium | 0.369 / 0.355 | 1.093 / 0.925 | 15.212 / 14.342 | 25.896 / 25.146 | 0.234 / 0.223 | 15.762 / 15.306 | 6.106 / 5.630 |
| large | 0.809 / 0.755 | 8.406 / 8.007 | 216.648 / 185.071 | 320.201 / 296.347 | 2.650 / 2.448 | 269.765 / 250.294 | 96.173 / 88.332 |

PHP total includes measured orchestration, excludes process startup and writing emitted C++ files.

| Sample | Clang frontend | LLVM backend | LLVM optimizer | Code generation | Instrumented wall |
| --- | ---: | ---: | ---: | ---: | ---: |
| small | 54.316 / 50.883 | 58.337 / 54.553 | 28.506 / 26.456 | 20.957 / 19.455 | 143.166 / 140.143 |
| medium | 294.736 / 281.597 | 562.969 / 548.416 | 370.554 / 363.510 | 161.923 / 156.009 | 308.604 / 301.369 |
| large | 2617.762 / 2347.477 | 6393.285 / 5640.800 | 4341.579 / 3777.810 | 1875.038 / 1699.335 | 2096.403 / 1900.097 |

Trace categories accumulate across parallel TUs and overlap (optimizer/code generation are within backend). Do not sum them or compare their sum to build wall time. Native wall above excludes tracing overhead.

| Sample / edit | Frontend | Prepare | Total | Source reads | ASTs reused | Bodies prepared / reused |
| --- | ---: | ---: | ---: | ---: | ---: | --- |
| small / full | 3.790 / 3.698 | 1.025 / 0.984 | 4.815 / 4.744 | 4 | 0 | 12 / 0 |
| small / noop | 0.227 / 0.207 | 0.060 / 0.051 | 0.286 / 0.258 | 0 | 4 | 0 / 12 |
| small / body_edit | 0.583 / 0.537 | 0.157 / 0.144 | 0.740 / 0.681 | 1 | 3 | 2 / 10 |
| small / signature_error | 0.580 / 0.514 | 0.237 / 0.212 | 0.817 / 0.732 | 1 | 3 | expected diagnostic |
| small / recovery | 0.590 / 0.518 | 0.251 / 0.224 | 0.840 / 0.773 | 1 | 3 | 3 / 9 |
| small / noop_after | 0.192 / 0.170 | 0.054 / 0.047 | 0.245 / 0.216 | 0 | 4 | 0 / 12 |
| medium / full | 44.677 / 41.977 | 16.006 / 14.113 | 60.683 / 56.090 | 15 | 0 | 59 / 0 |
| medium / noop | 0.507 / 0.465 | 0.666 / 0.590 | 1.173 / 1.084 | 0 | 15 | 0 / 59 |
| medium / body_edit | 0.882 / 0.831 | 0.723 / 0.586 | 1.605 / 1.417 | 1 | 14 | 2 / 57 |
| medium / signature_error | 0.916 / 0.740 | 0.973 / 0.791 | 1.889 / 1.547 | 1 | 14 | expected diagnostic |
| medium / recovery | 0.898 / 0.760 | 1.583 / 1.324 | 2.481 / 2.084 | 1 | 14 | 3 / 56 |
| medium / noop_after | 0.445 / 0.377 | 0.667 / 0.464 | 1.112 / 0.842 | 0 | 15 | 0 / 59 |
| large / full | 658.148 / 599.599 | 246.909 / 218.690 | 905.057 / 818.289 | 67 | 0 | 523 / 0 |
| large / noop | 1.529 / 1.318 | 29.275 / 8.223 | 30.804 / 9.615 | 0 | 67 | 0 / 523 |
| large / body_edit | 1.882 / 1.812 | 8.655 / 7.961 | 10.537 / 9.998 | 1 | 66 | 2 / 521 |
| large / signature_error | 1.993 / 1.781 | 10.997 / 9.932 | 12.991 / 11.864 | 1 | 66 | expected diagnostic |
| large / recovery | 1.880 / 1.751 | 17.190 / 16.331 | 19.070 / 18.211 | 1 | 66 | 3 / 520 |
| large / noop_after | 1.456 / 1.368 | 7.964 / 7.678 | 9.420 / 9.070 | 0 | 67 | 0 / 523 |

Five retained-session edit sequences per workload: full, no-op, body edit, incompatible signature, recovery, final no-op. Assertions verify source reads, AST/token identity, body reuse and caller invalidation. Edits use disposable copies.
Incremental timings stop after preparation; no incremental native-build speedup is claimed.

All 30 measured/traced executables matched expected stdout and exit code 8; measured executables contain no debug sections. Runtime includes use the public API.
The frozen mixed-flavor workloads are not exhaustive coverage of the new scalar operations. Dedicated compiler proofs cover those operations. The large workload repeats invoice functions; this is synthetic data.
This run measures the unchanged workload; it is not a controlled before/after speed comparison.
Raw samples, ranges, versions, source hashes, compiler hashes, logs, generated C++, and per-run Clang traces are retained in the benchmark output directory.

Run: 2026-11-06, active-scalar completion. Raw artifacts: v0.2/build/scalar-completion-benchmark-2026-11-06-final.
