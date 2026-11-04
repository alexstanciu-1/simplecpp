# First namespace slice: native proof

Doc Status: supporting

All three flavors and a mixed-flavor project emitted equivalent C++ and executed successfully. Each program has six translation units and prints `app`, `lib`, `lib`, `root` on separate lines, exiting with status 8.

Settings: O2, g0, Ninja -j8, mold, libc++, no PCH/LTO, existing runtime archive. Single-run correctness timings on local WSL, not a comparative benchmark. Each flavor was built in a fresh directory. Debug sections were checked absent.

Frontend/LLVM columns are summed phase durations from a separate instrumented run, not parallel wall time. Link-command time includes the Clang driver; mold time is its separate internal instrumented wall time. Do not add trace durations to build wall time.

| Fixture | Native wall ms | Clang frontend ms | LLVM backend ms | Link command ms | mold internal ms |
| --- | ---: | ---: | ---: | ---: | ---: |
| cpp | 820.62 | 40.82 | 40.18 | 151 | 23.00 |
| php | 177.43 | 52.00 | 45.67 | 78 | 21.00 |
| js | 146.28 | 37.78 | 32.92 | 70 | 25.00 |
| mixed | 156.71 | 39.52 | 46.88 | 68 | 21.00 |

The PHP test-and-fixture-emission process took 59.53 ms. It runs multiple positive/negative/incremental proofs and emits all four fixtures; it is not a single compiler-invocation time.

Raw traces, Ninja graphs/logs, binaries and results remain under `v0.2/build/namespace-slice-proof-2026-11-04-final/`.

Validation: all 23 pre-existing compiler CTests passed (the Unix-socket frontend-service test required an outside-sandbox rerun). After configuring the newly registered tests, namespace, dependency-watch, frontend, function, incremental-preparation and style checks passed. Style covers 76 handwritten PHP files.
