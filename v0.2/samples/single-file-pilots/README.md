# Single-file compiler and runtime pilots

[50 KB and 100 KB single-file samples and benchmark results](scaled/README.md)


Each application has one PHP-flavor Simple C++ source file, one module manifest, a small external input and its expected report. Each accepts input from `input.txt` in its working directory. Exit status 2 indicates an unreadable file. The samples use the v0.2 filesystem module.

| Application | Input and processing | Output |
| --- | --- | --- |
| Access log | Validate a gateway request, strip query parameters, group by service, and accumulate response and latency buckets. | Status counts, cache hit rate, percentile ceilings, slowest request and error budget. |
| Stock ledger | Apply receipts, holds, releases, shipments and physical counts in journal order. Reject duplicate documents, account for moving-average cost and handle shortages. | Per-SKU balances, sales, cost, margin and warehouse totals in integer cents. |
| Route table | Parse backend, rule and request directives. Resolve backend references in a second pass, check segment boundaries and select the most specific active route. | Diagnostics with line numbers, dispatch results and backend usage. |

All three use byte reads, stored byte lengths, string splitting and typed hash lookups in ordinary application code. These are existing runtime APIs. `workloads.py` supplies larger deterministic inputs and calculates the full expected reports independently of the executables. The small fixtures also exercise malformed records, duplicates and unresolved references.

## Reproduce

Start with a v0.2 compiler and runtime publication built from the same sources with filesystem enabled. To keep a developer's existing publication intact, use a separate checkout or source copy for these commands. From its `v0.2` directory run:

```sh
cmake --preset default -DSCPP_ENABLE_FILESYSTEM=ON
cmake --build --preset default --target scpp_filesystem
```

Run the benchmark script using a new output directory. Supply the v0.2 path if measuring a different checkout.

```sh
python3 -B bench.py /tmp/single-file-builds --v02 /path/to/v0.2
```

The script uses the project compiler's incremental line protocol, then follows its emitted manifest to compile and link the application's unit and generated entry adapter. Each fresh build starts a PHP process, uses new application objects and an already-built runtime, and runs ordinary Clang C++23 with libc++, mold and `-O2 -g0`. The two native translation units compile serially. Five measured rounds follow a warm-up round, with shuffled program order. The total wall clock starts before PHP launches and ends when linking completes. The reported preparation/emission time is measured inside the compiler. For the edit measurement the PHP process stays alive, a report label changes in the source, and the application object is recompiled and relinked with the existing adapter. The script checks the edited report. These timings describe this build harness; the compiler panel and its Ninja scheduler are a different invocation.

Copy the contents of the output's `remote` directory and `measure.py` to a Linux x86-64 benchmark host. Run from that directory with an available logical CPU:

```sh
python3 measure.py --cpu 8 > runtime.json
```

Each remote report executable must first reproduce the complete expected output. The timing binary loads the corpus once, warms up, and measures process CPU time over 20 passes with report generation enabled and stdout redirected to `/dev/null`. File reading and process startup are outside that timer. The access and stock corpora each contain 1,024 input rows. The routing corpus contains 450 rows, including 384 requests. Work is comparable within a family across builds. VmHWM is the executable's peak resident memory including setup. A separate executable counts scalar C++ `operator new` requests over one measured pass, after warm-up. That count includes library calls going through the wrapped symbol and measures cumulative requested bytes rather than live memory.

## Pilot measurements, 2026-11-24

The local build host was a Ryzen 5 PRO 4650G running WSL Ubuntu, PHP 8.5.7 and Clang 18.1.3. The prebuilt runtime used CMake Release (`-O3`) with filesystem enabled. The baseline used the working tree at `95bd15961`; the SHA-256 of `git diff -- v0.2/compiler v0.2/runtime v0.2/tools/api_generator` was `907e4e93bb334795c94c109705c72efdd5e07a190b2648745b2ee813469f2612`. Each measured file's SHA-256 is also recorded. This historical run explicitly used LLD. Subsequent fresh build comparisons use the project default, mold. No inline-carrier prototype was applied.

| Application | Source bytes | Emitted C++ bytes including entry | Our compiler ms | Clang frontend ms | LLVM backend ms | Linking ms | Fresh wall ms, median [Q1, Q3] | Body edit to executable ms |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| Access log | 9,533 | 28,844 | 20 | 80 | 166 | 68 | 455 [451, 455] | 326 |
| Stock ledger | 9,644 | 33,464 | 21 | 101 | 271 | 68 | 548 [546, 562] | 425 |
| Route table | 10,441 | 41,294 | 25 | 132 | 407 | 67 | 731 [702, 733] | 581 |

Our compiler time covers the in-process project run through C++ emission. For Clang, five additional shuffled rounds after warm-up used the same unedited C++ and flags with `-ftime-trace`. Each round sums `Total Frontend` and, separately, `Total Backend` across the application and entry adapter before taking the median. Frontend includes C++ analysis and initial IR generation. Backend includes LLVM optimization and native code generation. Linking and fresh wall times come from the original uninstrumented builds. Fresh wall time also includes PHP and compiler-driver startup and harness overhead, so the phase medians do not sum to the wall median. [Raw Clang phase times](results/clang-phases-wsl.json)

The runtime host was dev002, a Ryzen 9 5950X pinned to CPU 8. Results use nine timing processes and three separate allocation-counting processes per application. A row is one input line. For routing it can be a definition or a dispatch request.

| Application | Rows/pass | CPU µs/row, median [Q1, Q3] | CPU ms/pass | Median peak RSS KiB | Scalar new calls/pass | Requested bytes/pass |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| Access log | 1,024 | 1.212 [1.196, 1.218] | 1.24 | 2,816 | 12,697 | 871,108 |
| Stock ledger | 1,024 | 1.081 [1.080, 1.093] | 1.11 | 2,560 | 5,099 | 635,616 |
| Route table | 450 | 6.617 [6.559, 6.889] | 2.98 | 2,816 | 5,269 | 349,952 |

The routing workload scans up to 64 rules per request. The other workloads do different work per input line. Runtime figures serve as within-family baselines for future sizes. [Raw build samples](results/compile-wsl.json) · [Raw CPU and memory samples](results/runtime-dev002.json)

### Optimization level and link cost

Sizes below are the actual generated source files on disk, before preprocessing. The separate entry adapter is 205 bytes in every pilot. The Clang wall times below include two serial compiler invocations. Traced phase medians come from separate compilations.

| Application | Simple C++ bytes | Application C++ bytes (lines) | Clang wall ms O0 / O1 / O2 | Clang frontend ms O0 / O2 | LLVM backend ms O0 / O2 |
| --- | ---: | ---: | ---: | ---: | ---: |
| Access log | 9,533 | 28,639 (601) | 183 / 329 / 331 | 69 / 70 | 30 / 169 |
| Stock ledger | 9,644 | 33,259 (728) | 238 / 398 / 490 | 96 / 101 | 49 / 245 |
| Route table | 10,441 | 41,089 (844) | 293 / 550 / 605 | 132 / 120 | 68 / 423 |

All profiles used C++23, libc++, `-g0` and the same prebuilt runtime. Each passed both report fixtures. In five shuffled warm-cache rounds, `-O0` compiled fastest for iteration. Keep `-O2` for measuring optimized execution and allocation elimination.

| Link of O2 objects | Access log ms | Stock ledger ms | Route table ms |
| --- | ---: | ---: | ---: |
| clang++ with default LLD | 71 | 75 | 73 |
| Exact LLD command invoked directly | 43 | 44 | 47 |
| clang++ with mold, four threads | 56 | 54 | 56 |
| Exact mold command invoked directly | 22 | 23 | 24 |

The direct commands came from the corresponding driver's `-###` output and used the same startup objects, archives, system libraries and dynamic-loader settings. Timings include process launch. The fastest driver-based link tested used `--ld-path=/path/to/ld.mold -Wl,--threads=4`. Reproduce with `python3 -B compile_flags.py BUILD_OUTPUT V02_ROOT OUTPUT_JSON` after running `bench.py`. [Raw flag and linker samples](results/compile-flags-wsl.json)

### Invocation to executable with the project default linker

Five local WSL rounds after warm-up, using mold with its default thread settings. Total wall time starts immediately before invoking PHP and stops when the link command returns successfully. It includes PHP startup, compiler and linker driver overhead, and both native units. Clang phase tracing and output verification run outside the timer. Each binary passed the small and large report checks. Reproduce with `python3 -B build_levels.py V02_ROOT OUTPUT_JSON`. [Raw per-build measurements](results/build-levels-wsl.json)

| Application | Flags | Our compiler ms | Clang frontend ms | LLVM backend ms | Link ms | Total ms | dev002 CPU ms/pass |
| --- | --- | ---: | ---: | ---: | ---: | ---: | ---: |
| access-log | -O0 | 20 | 65 | 31 | 61 | 333 | 1.10 |
| access-log | -O1 | 20 | 67 | 138 | 61 | 443 | 0.69 |
| access-log | -O2 | 20 | 67 | 158 | 61 | 464 | 0.68 |
| stock-ledger | -O0 | 21 | 90 | 37 | 63 | 371 | 0.98 |
| stock-ledger | -O1 | 21 | 91 | 199 | 59 | 527 | 0.59 |
| stock-ledger | -O2 | 21 | 91 | 227 | 60 | 547 | 0.60 |
| route-table | -O0 | 25 | 114 | 57 | 62 | 435 | 4.11 |
| route-table | -O1 | 26 | 112 | 330 | 66 | 684 | 1.64 |
| route-table | -O2 | 25 | 116 | 374 | 58 | 721 | 1.66 |

The final column measures binary execution on dev002, logical CPU 8, with the prebuilt runtime and 20 warmed workload passes per timing sample. Builds and build tracings ran on WSL. [Raw execution measurements](results/run-levels-dev002.json)

### Full builds on dev002

The working-tree PHP compiler, its generated publication and the matching prebuilt runtime archives were staged on tf-dev002. PHP 8.5.12, Clang 18.1.3 and mold 2.30.0 ran on that host. The process used the staged libc++ headers and libraries. No LTO or native debug information was requested. Five shuffled rounds per application and optimization level followed one warm-up; both expected reports were checked for each executable.

Each row is the **actual build with the median total** of its five runs. Milliseconds from left to right account for the same uninstrumented invocation. PHP overhead includes interpreter startup, bootstrap, protocol and shutdown. Clang wall covers both serial native compiler invocations. Link wall includes the driver and mold.

| Application | Level | Our compiler | Remaining PHP wall | Clang wall | Link wall | Harness | Invocation → EXE |
| --- | --- | ---: | ---: | ---: | ---: | ---: | ---: |
| access-log | -O0 | 12.0 | 58.7 | 106.9 | 31.3 | 0.3 | 209.2 |
| access-log | -O2 | 11.7 | 65.1 | 181.7 | 30.9 | 0.4 | 289.8 |
| stock-ledger | -O0 | 12.3 | 64.8 | 129.9 | 31.4 | 0.3 | 238.7 |
| stock-ledger | -O2 | 11.9 | 58.3 | 242.8 | 32.0 | 0.4 | 345.3 |
| route-table | -O0 | 15.1 | 58.4 | 155.2 | 31.2 | 0.3 | 260.2 |
| route-table | -O2 | 14.7 | 63.5 | 334.5 | 32.1 | 0.4 | 445.2 |

Clang's separately traced compilations provide frontend and backend breakdowns. For the route-table O2 row, 70.1 ms frontend and 226.1 ms backend fit inside 298.6 ms of traced ExecuteCompiler time and 334.1 ms of traced process wall. The uninstrumented Clang processes took 334.5 ms, including 31.5 ms for the entry adapter. As an independent startup control, median command wall over nine runs was 29.5 ms for an empty configured PHP process, 62.8 ms for loading the compiler bootstrap and exiting, and 9.2 ms for empty PHP with INI disabled. [Per-run process and Clang phase timings](results/build-levels-dev002.json) · [Startup controls](results/startup-dev002.json)
