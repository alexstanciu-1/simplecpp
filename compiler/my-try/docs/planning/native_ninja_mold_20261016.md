# Native Ninja + Mold accounting comparison — 2026-10-16
Doc Status: supporting

Continuation of the [O2 native Ninja checkpoint](native_ninja_accounting_20261016.md). Compiler revision `e928792d`; the only authored native-driver change is a boolean request switch selecting 8 or 12 Ninja jobs. Its rebuilt `-O2` binary, Clang 18.1.3, Ninja 1.11.1 and Mold 3.0.0 binaries are identical across hosts (SHA-256 evidence retained). Application optimization remains `-O0`, with the same runtime PCH, implementation grouping and other flags. GNU BFD is version 2.42 on both hosts, but its binary hashes differ.

Fixture: **528,088 bytes / 93 PHS files**, publishing 292 artifacts (2,389,172 bytes) and linking 93 generated plus 3 runtime-provider objects. Each full build starts with fresh publication and build directories; OS caches are allowed to remain warm. Every scenario starts a new native compiler process. Reuse belongs to Ninja, not retained compiler state.

## Host and experiment boundaries

- WSL: Ryzen 5 PRO 4650G, 12 logical CPUs, WSL2 kernel 6.6.87.2. Initial competing load reached about 24. Trial 1 is retained but excluded from the comparison: it was heavily contended and overlapped preparation/compression of the remote input archive. The table uses trials 2–4, after that preparation completed. Residual system load remains uncontrolled.
- dev002: Ryzen 9 5950X, 32 logical CPUs, native Linux 6.8.0-85. Load was about 0.7 before testing and 1.8 after. The table uses all three trials. Hardware and host load differ, so this is not an isolated measurement of WSL overhead.
- Full/unchanged/original body-edit comparison: Ninja 8 jobs for both linkers. Scaling cases: Ninja 12 jobs, Mold 8 threads. Twelve is the maximum number of concurrent Ninja commands; only eight objects are dirty in the eight-edit case.
- Mold is selected by a benchmark Clang wrapper, replacing only `-fuse-ld=/usr/bin/ld` and adding `-Wl,--threads=8` on link commands. Each configuration uses separate caches, and the actual tool hashes/argv are recorded. This does **not** close CH15-MOLD-01: production linker selection and actual-linker cache identity remain deferred.

## End-to-end comparison

Median totals, seconds. Ranges contain all three selected observations. Savings are descriptive; the WSL load variability prevents attributing the whole difference to the linker.

| Host | Case | GNU ld median (range) | Mold / 8 threads median (range) | Difference |
| --- | --- | ---: | ---: | ---: |
| WSL | full | 33.526 (33.134–36.282) | 35.463 (33.287–53.304) | -1.937 s / -5.8% |
| WSL | unchanged | 1.465 (1.433–1.501) | 1.413 (1.393–1.648) | +0.053 s / +3.6% |
| WSL | body-edit | 4.699 (4.197–4.867) | 3.345 (3.262–3.522) | +1.354 s / +28.8% |
| dev002 | full | 12.101 (11.981–12.425) | 11.630 (11.579–11.743) | +0.471 s / +3.9% |
| dev002 | unchanged | 0.826 (0.803–0.855) | 0.861 (0.836–0.868) | -0.035 s / -4.3% |
| dev002 | body-edit | 2.249 (2.195–2.280) | 1.782 (1.782–1.818) | +0.468 s / +20.8% |

## Stage and memory detail

Each timing row is the actual run with median total duration for its host/configuration/case; component medians are not mixed. Compiler column is cumulative request → C++ ready. Compile elapsed is the union of PCH/object tool intervals, not summed parallel job durations. “Other” is total minus compiler/compile/link, including launch, graph/cache work, gaps, status checks and process exit; it is distributed, not all after link. Executable-ready column is successful link-driver exit, before final build status. Unchanged runs create no new executable. RSS is the **worst sampled tree RSS across the three runs**, not necessarily that representative run.

| Host / linker | Case | C++ ready | Compile elapsed | Link | Other | Exe ready | Total | Peak tree MiB |
| --- | --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| WSL / gnu | full | 1.296 | 30.921 | 1.083 | 0.227 | 33.447 | 33.526 | 1563.5 |
| WSL / gnu | unchanged | 1.312 | 0.000 | 0.000 | 0.153 | — | 1.465 | 76.1 |
| WSL / gnu | body-edit | 1.354 | 1.928 | 1.151 | 0.266 | 4.608 | 4.699 | 342.8 |
| WSL / mold | full | 1.293 | 33.711 | 0.200 | 0.259 | 35.365 | 35.463 | 1636.9 |
| WSL / mold | unchanged | 1.274 | 0.000 | 0.000 | 0.138 | — | 1.413 | 76.0 |
| WSL / mold | body-edit | 1.255 | 1.674 | 0.192 | 0.224 | 3.266 | 3.345 | 308.8 |
| dev002 / gnu | full | 0.774 | 10.659 | 0.492 | 0.176 | 12.005 | 12.101 | 1539.1 |
| dev002 / gnu | unchanged | 0.747 | 0.000 | 0.000 | 0.079 | — | 0.826 | 75.3 |
| dev002 / gnu | body-edit | 0.790 | 0.775 | 0.499 | 0.186 | 2.154 | 2.249 | 339.8 |
| dev002 / mold | full | 0.739 | 10.663 | 0.059 | 0.169 | 11.559 | 11.630 | 1546.2 |
| dev002 / mold | unchanged | 0.773 | 0.000 | 0.000 | 0.089 | — | 0.861 | 74.8 |
| dev002 / mold | body-edit | 0.738 | 0.826 | 0.050 | 0.168 | 1.706 | 1.782 | 302.7 |

Detailed setup, synchronization/parsing, semantic preparation, emission/publication, native-build durations, compiler VmHWM and raw timestamps are preserved per run in [summary.json](native_ninja_mold_20261016/summary.json). Normal measurements add no Clang profiling flags, so compile time here is combined frontend/backend.

## Eight body edits with Ninja 12 jobs

Two batches were exercised: (1) the original self-test output-string edit plus seven check-function diagnostic strings; (2) eight comparable business-class implementations: allocation_driver, asset_revision, cash_forecast_item, collection_case, customer_credit, customer_profile, department_budget and employee_advance. The second batch changes an ID-validation message inside each method body; declarations/layouts stay unchanged. Its single-edit control changes allocation_driver alone. Git restores edits between cases and on exit.

| Host / batch | One edit total | Eight edits total | Eight compile elapsed | Eight summed compile time | Peak concurrent Clang | Eight peak tree MiB |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| WSL / mixed | 3.354 | 3.738 | 2.043 | 9.241 | 8 | 1348.1 |
| WSL / business classes | 5.799 | 7.388 | 3.086 | 20.622 | 8 | 1365.5 |
| dev002 / mixed | 1.795 | 1.812 | 0.825 | 3.196 | 8 | 1341.8 |
| dev002 / business classes | 1.348 | 1.327 | 0.373 | 2.931 | 8 | 1359.8 |

Every eight-edit case compiled **8 objects and reused 88**, reused the PCH, and linked once. All eight Clang jobs overlapped. The current graph already supplies the necessary independent object edges; no scheduler, incremental-internals or grouping change was needed. This proves scaling for these concrete batches, not arbitrary eight edits: header/PCH changes, unequal unit costs, memory pressure and competing CPU load can extend the critical path. The slowest individual compile still sets a floor.

## Validation and measurement limitations

- Full builds: one PCH, 96 objects, one link. Unchanged: zero compiler/linker actions. Original body edit: one object, 95 reused, one link. Every full publication matched all 292 artifact paths/hashes from the independent PHP reference.
- Every measured build and restoration build passed the accounting executable self-test (998 baseline + 857 large-fixture checks). Fixtures are restored with Git. No compiler incremental/MT internals were modified.
- The native compiler driver and builder run natively at `-O2`; the shared converter/framework and legacy S2S rebuilt only the changed driver object and compiler executable with `--no-stan`. Remote runs use the copied identical binary with its matching runtime via LD_LIBRARY_PATH.
- Peak memory is sampled process-tree RSS at nominal 10 ms intervals; it double-counts shared pages and may miss short-lived peaks. It is not PSS. Stage markers have filesystem timestamp quantization.
- Mold normal fork behavior is preserved in primary timings. Parent wait4 resource fields are not reliable total Mold CPU/RSS. A separate three-run replay adds `--no-fork` only for linker resource accounting; these numbers are excluded from primary timing tables. Its argv, wall/user/system time and peak RSS are archived separately.

## Evidence and next boundary

[Scripts, summaries and provenance](native_ninja_mold_20261016/README.md) retain the exact experiment. The practical configuration is one Ninja graph, `jobs=12`, unchanged runtime PCH, and one Mold link with `--threads=8` after dirty objects finish. Mold makes linking a small fraction here; no shared-library layout is needed to achieve the observed eight-edit overlap. Production Mold configuration/cache identity remains CH15-MOLD-01, automatic unit splitting and multi-file classes remain deferred.
