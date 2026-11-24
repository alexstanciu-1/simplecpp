# Single-file operational workloads at 50 KB and 100 KB

Each directory under `50k` and `100k` contains one v0.2 PHP-flavor source file and a filesystem module manifest. Run in a directory containing `input.txt`. The original 10 KB inputs and expected reports still apply. Put `# dashboard` on the first line to enable detailed reporting.

| Family | Detailed reports in the 50 KB application | Additional stateful analysis at 100 KB |
| --- | --- | --- |
| Access log | Response status, method, cache outcome, route class, latency, response length and path depth; summaries of elapsed time and transfer size. | Group consecutive slow or server-error responses into incidents; report longest streak, affected latency and peak latency. |
| Stock ledger | Movement and posting decision, order size, stock and reservation levels, available units and unit price after each posting decision. | Track stockouts and recoveries per SKU, attempts refused at zero availability and the busiest SKU. |
| Route table | Method, resolved rule type, method-specific versus wildcard, path depth and size, fraction of path matched and number of candidate rules. | Track switches between backend targets, gaps in coverage and per-backend request counts. |

These are generated reporting applications. The original parsing, business logic and output remain present. Additional source consists mainly of compiled, named cross-tab reports. The number of saved reports ranges from 21–23 at 50 KB to 31–33 at 100 KB. Each occupied cell holds count, sum, minimum and maximum. The 100 KB apps retain all report definitions from the 50 KB apps. Data rows and expected output live outside the program. The resulting workload is branch- and string-heavy; this matters when interpreting compiler scaling or comparing it with a hand-authored program of the same byte size.

`catalog.py` defines the available dimensions. `generate.py` selects complete reports within the size band, factors their predicates into nested branches, and emits the actual standalone sources. `extended.py` supplies the stateful analyses. Regenerate with `python3 -B generate.py`. The test oracle classifies records with Python data structures and accumulates keyed statistics independently of the generated branch trees.

## Measurements

Run `benchmark.py V02_ROOT OUTPUT_JSON --php /usr/bin/php8.5 --size 50`, then repeat with `--size 100`, using the same filesystem-enabled runtime as the pilots. Each level gets one warm-up and five fresh builds in shuffled order. A new PHP process starts each build. The native application and entry adapter compile serially as C++23 with libc++, `-g0`, and the selected optimization level; mold links the two objects and prebuilt runtime libraries. The total wall clock stops when linking returns. Separate traced compiles record Clang frontend and LLVM backend time outside the total timer.

For every built binary the runner checks three complete reports: the original small fixture, the larger shared task and the full dashboard. The latter two use identical data rows. The shared case omits the dashboard header. Runtime uses one preloaded corpus per process, one warm-up pass and eight measured passes. The executable still renders reports to stdout redirected to `/dev/null`. Process CPU measurements and the separate scalar C++ allocation-counting measurements run on logical CPU 8. Peak RSS includes process setup. Allocation bytes count cumulative requests through the wrapped scalar operator new and can exceed the peak resident size.

## dev002 build and execution results

Fresh builds used PHP 8.5.12, Clang 18.1.3 and mold 2.30.0 on tf-dev002. The C++ size includes the 205-byte native entry adapter. Each row selects the actual build with the median total out of five. The PHP and native compiler process timings plus link and harness times account for invocation to executable. Frontend and LLVM timings come from separate traced compiles of that same emitted C++ and are included here to explain native compile cost. Units are milliseconds unless marked otherwise.

| App | Size | Source B | Lowered C++ B | Level | Our compiler | Other PHP | Clang wall | Clang frontend | LLVM backend | Link | Invocation → EXE |
| --- | ---: | ---: | ---: | --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| access-log | 50 | 49935 | 183518 | -O0 | 54 | 65 | 379 | 166 | 188 | 30 | 528 |
| access-log | 50 | 49935 | 183518 | -O2 | 54 | 63 | 1407 | 171 | 1234 | 27 | 1551 |
| stock-ledger | 50 | 50551 | 172607 | -O0 | 53 | 70 | 339 | 149 | 156 | 29 | 491 |
| stock-ledger | 50 | 50551 | 172607 | -O2 | 53 | 64 | 1307 | 158 | 1144 | 27 | 1451 |
| route-table | 50 | 49888 | 182446 | -O0 | 53 | 65 | 380 | 186 | 181 | 30 | 528 |
| route-table | 50 | 49888 | 182446 | -O2 | 53 | 64 | 1427 | 179 | 1267 | 27 | 1571 |
| access-log | 100 | 104605 | 365191 | -O0 | 104 | 62 | 710 | 313 | 390 | 31 | 907 |
| access-log | 100 | 104605 | 365191 | -O2 | 101 | 68 | 2931 | 337 | 2663 | 32 | 3133 |
| stock-ledger | 100 | 99534 | 310907 | -O0 | 94 | 60 | 557 | 239 | 292 | 32 | 743 |
| stock-ledger | 100 | 99534 | 310907 | -O2 | 94 | 65 | 2390 | 263 | 2215 | 30 | 2578 |
| route-table | 100 | 97684 | 325518 | -O0 | 93 | 60 | 622 | 265 | 322 | 32 | 808 |
| route-table | 100 | 97684 | 325518 | -O2 | 95 | 62 | 2567 | 281 | 2304 | 30 | 2755 |

### Execution and memory

CPU milliseconds per pass are medians over five processes. Peak RSS is the median high-water mark of the timing processes. Allocation counts and cumulative requested bytes are from separate single-pass measurements. Shared and full runs process the same 1,024 log or journal rows, or 450 route-table rows (384 test requests).

| App | Size | Level | Shared CPU ms | Full CPU ms | Shared / full peak RSS KiB | Shared / full new calls | Shared / full new KiB |
| --- | ---: | --- | ---: | ---: | ---: | ---: | ---: |
| access-log | 50 | -O0 | 1.08 | 6.06 | 3328 / 3328 | 12697 / 36686 | 851 / 1655 |
| access-log | 50 | -O2 | 0.68 | 2.78 | 3328 / 3072 | 12697 / 36686 | 851 / 1655 |
| stock-ledger | 50 | -O0 | 1.00 | 6.60 | 3328 / 3328 | 5089 / 44815 | 617 / 1976 |
| stock-ledger | 50 | -O2 | 0.59 | 3.00 | 3072 / 3072 | 5089 / 44815 | 617 / 1976 |
| route-table | 50 | -O0 | 4.35 | 6.37 | 3328 / 3328 | 5263 / 19505 | 342 / 842 |
| route-table | 50 | -O2 | 1.69 | 2.63 | 3072 / 3072 | 5263 / 19505 | 342 / 842 |
| access-log | 100 | -O0 | 1.06 | 8.98 | 3328 / 3584 | 12697 / 52324 | 851 / 2167 |
| access-log | 100 | -O2 | 0.68 | 4.01 | 3328 / 3328 | 12697 / 52324 | 851 / 2167 |
| stock-ledger | 100 | -O0 | 1.01 | 10.10 | 3328 / 3328 | 5089 / 63019 | 617 / 2697 |
| stock-ledger | 100 | -O2 | 0.61 | 4.64 | 3328 / 3328 | 5089 / 63019 | 617 / 2697 |
| route-table | 100 | -O0 | 4.05 | 7.75 | 3328 / 3328 | 5263 / 27344 | 342 / 1134 |
| route-table | 100 | -O2 | 1.77 | 3.43 | 3328 / 3328 | 5263 / 27344 | 342 / 1134 |

[50 KB raw results](../results/scaled-50-dev002.json) · [100 KB raw results](../results/scaled-100-dev002.json)

## Ninja experiment: 100 KB access log

The three configurations used the same 104,605-byte Simple C++ input and the same staged Clang 18, libc++, runtime archives and mold on dev002. All native compiles used ordinary `-O2 -g0`, with separate application and entry objects. A fresh PHP process emitted the original C++ for each build. For the split builds the probe distributed its 31 report function bodies among four units by emitted byte weight, with the remaining application functions together in the first unit. The emitted declarations and inline type definitions are shared through a 26,876-byte header. A single definition of `observe` stays in the first unit.

Elapsed time starts before PHP invocation, includes probe publication and Ninja startup, and ends when the link finishes. Five shuffled rounds per configuration followed a warm-up. Native CPU is cumulative user + system time of Ninja and its descendants. Peak native build memory is the Ninja process group’s sampled proportional set size (PSS) in a separate warm-up run per configuration. It is an approximate peak.

| Application units × Ninja jobs | Fresh invocation → EXE ms, median [Q1, Q3] | Native wall ms | Native CPU ms | Peak native PSS MiB | Edited invocation → EXE ms, median [Q1, Q3] | Shared / full execution CPU ms |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| one-j4 | 3066 [3062, 3083] | 2903 | 2918 | 135 | 3047 [3038, 3091] | 0.684 / 3.971 |
| four-j1 | 3249 [3222, 3254] | 3075 | 3067 | 107 | 944 [931, 948] | 0.687 / 3.960 |
| four-j4 | 990 [987, 994] | 820 | 3091 | 176 | 947 [943, 965] | 0.676 / 3.998 |

The edit changes the emitted label of one occupied dashboard cell. The compiler runs in a fresh process and the probe preserves timestamps of byte-identical native inputs. The correctness check requires both the original shared report and the changed full report. Ninja logs record the individual object and link intervals. Execution uses the same application objects relinked with the existing in-memory timing driver on logical CPU 8, seven shuffled processes per configuration and 24 workload passes per process. Serial and parallel four-unit builds have the same native sources.

Reproduce with `python3 -B ninja_split.py V02_ROOT OUTPUT_JSON --php /usr/bin/php8.5` from the scaled sample folder, with Ninja and mold on PATH and the matching filesystem-enabled runtime. [Raw build, edge, memory and execution measurements](../results/ninja-split-dev002.json)

### Narrow header for report partitions

The 26,876-byte preamble includes 9,500 bytes of owner declarations, 9,220 bytes of inline owner definitions, and about 6,226 bytes of application prototypes. Three partitions contain only reports. The narrow header copies the complete owner types for Dashboard and ReportEvent, used inline ownership operations, the ReportEvent layout, and the observe declaration. The dashboard payload is forward-declared. The first partition still uses the broad preamble. Header-only preprocessing with Clang 18 and the matching local libc++ publication produced 153,296 versus 6,721 bytes (`-E -P`).

| Four application units, Ninja -j4 | Header in the three report units | Fresh invocation → EXE ms | Edited invocation → EXE ms | Native CPU ms, fresh | Peak native PSS MiB | Shared / full CPU ms per pass |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| four-j4 | 26876 | 986 | 942 | 3103 | 175 | 0.685 / 3.995 |
| narrow-j4 | 2750 | 987 | 908 | 2982 | 173 | 0.682 / 4.045 |

Five new interleaved rounds compare headers at otherwise identical partitioning and flags. Both binaries passed the small, shared and full reports before and after the edit. Use `--compare-headers` with the same runner to repeat this second experiment. [Raw header comparison](../results/ninja-headers-dev002.json)

[Single-unit lowering audit of the 100 KB access-log sample](LOWERING_AUDIT.md)
