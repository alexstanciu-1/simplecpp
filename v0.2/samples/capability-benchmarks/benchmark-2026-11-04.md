# Capability benchmark results

Doc Status: supporting

All times are milliseconds. PHP: five warmed in-process samples; native: three fresh application builds after a warm-up.
O2/g0, libc++, mold, Ninja -j8, no PCH/LTO/ccache; prebuilt runtime. Each emitted source stays a separate translation unit.

| Sample | Sources / C++ TUs | Source KiB | Read | Tokenize | Parse/collect | Symbols | Prepare | Lower/emit | PHP total | Native median [min–max] | Link median |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| small | 4 / 5 | 2.7 | 0.126 | 0.808 | 1.236 | 0.034 | 0.644 | 0.374 | 3.732 | 185.628 [169.184–197.065] | 70.000 |
| medium | 15 / 16 | 27.1 | 1.628 | 10.685 | 17.189 | 0.173 | 7.773 | 2.631 | 37.020 | 515.917 [470.079–538.421] | 78.000 |
| large | 67 / 68 | 267.8 | 6.167 | 92.141 | 117.137 | 1.793 | 111.898 | 40.886 | 373.143 | 2137.976 [2100.136–2436.683] | 67.000 |

PHP total includes module loading and measured orchestration, excludes writing C++ files and process startup. Stage ranges, native samples and provenance are in [benchmark-2026-11-04.json](benchmark-2026-11-04.json); raw PHP samples are in [benchmark-2026-11-04-php-raw.json](benchmark-2026-11-04-php-raw.json).

| Sample | Clang frontend | LLVM backend | Optimizer | Code generation | Trace build wall |
| --- | ---: | ---: | ---: | ---: | ---: |
| small | 81.770 | 82.501 | 46.111 | 24.550 | 200.971 |
| medium | 493.837 | 835.261 | 588.052 | 198.222 | 446.510 |
| large | 3380.059 | 6697.883 | 5033.599 | 1497.999 | 2531.154 |

Clang traces are a separate instrumented build. Categories overlap and accumulate across parallel translation units; they cannot be added to obtain wall time.

| Sample / edit | Frontend median | Preparation median | Total median | Source reads | Reused ASTs | Bodies prepared / reused |
| --- | ---: | ---: | ---: | ---: | ---: | --- |
| small / full | 3.089 | 0.657 | 3.749 | 4 | 0 | 12 / 0 |
| small / noop | 0.345 | 0.045 | 0.390 | 0 | 4 | 0 / 12 |
| small / body_edit | 0.482 | 0.089 | 0.575 | 1 | 3 | 2 / 10 |
| small / signature_error | 0.502 | 0.318 | 0.693 | 1 | 3 | expected diagnostic |
| small / recovery | 0.575 | 0.175 | 0.775 | 1 | 3 | 3 / 9 |
| small / noop_after | 0.310 | 0.041 | 0.350 | 0 | 4 | 0 / 12 |
| medium / full | 26.916 | 6.067 | 33.122 | 15 | 0 | 59 / 0 |
| medium / noop | 1.034 | 0.358 | 1.393 | 0 | 15 | 0 / 59 |
| medium / body_edit | 1.069 | 0.556 | 1.801 | 1 | 14 | 2 / 57 |
| medium / signature_error | 1.382 | 0.999 | 2.381 | 1 | 14 | expected diagnostic |
| medium / recovery | 1.383 | 1.316 | 2.700 | 1 | 14 | 3 / 56 |
| medium / noop_after | 0.876 | 0.345 | 1.206 | 0 | 15 | 0 / 59 |
| large / full | 293.762 | 94.718 | 392.241 | 67 | 0 | 523 / 0 |
| large / noop | 6.644 | 4.509 | 11.153 | 0 | 67 | 0 / 523 |
| large / body_edit | 6.462 | 4.302 | 10.764 | 1 | 66 | 2 / 521 |
| large / signature_error | 6.171 | 5.953 | 12.058 | 1 | 66 | expected diagnostic |
| large / recovery | 7.153 | 11.057 | 18.209 | 1 | 66 | 3 / 520 |
| large / noop_after | 7.303 | 5.744 | 12.518 | 0 | 67 | 0 / 523 |

Edits occur only in disposable copies. Body edits preserve signatures; signature edits change an imported string-returning function to int and must reject its string consumer. Recovery restores the function. No-op passes read zero sources; unchanged AST/token objects retain identity.

Incremental timings cover loading/tokenize/parse/index and preparation only. Native emission/build remains full-build; no incremental native-build speedup is claimed.

All measured and traced executables matched expected stdout and exit code 8. Overflow/zero-error cases remain in focused tests; these samples stay in the defined arithmetic domain.

These are supported-subset synthetic workloads, not full production applications. The large workload repeats generated invoice functions. No LTO is used; cross-TU calls prevent whole-program constant folding, but ordinary local optimization still applies.

Host: WSL2, Ryzen 5 PRO 4650G (6 cores/12 logical CPUs), PHP 8.5.7 NTS, Clang 18.1.3, Ninja 1.11.1, mold 2.40.4. Xdebug disabled.

Raw generated C++, build logs, executables and Clang traces: `v0.2/build/capability-benchmarks-2026-11-04-semantic-edits/`.

Preparation is about 30% of the large staged total in this run (ratio of medians), not the historical my-try 90%+. This does not establish future scaling or production performance.
