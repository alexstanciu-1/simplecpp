# In-place inventory reconciliation

Doc Status: planning

Measured on commit `462113d00` on WSL2, AMD Ryzen 5 PRO 4650G, with the process and its worker threads allowed CPUs 0 and 2 (different reported physical cores). The simulated workload applies one signed adjustment per warehouse stock record, updates on-hand and available quantities, inventory value and revision, clears the adjustment and returns the subtotal. Each record has 16 inline 64-bit fields. The native driver checks its 128-byte layout at compile time.

The sequential baseline calls the compiled worker directly on the two halves in turn. The parallel version calls the compiled launch function on those same halves, including range admission, two new OS threads, join, result vector construction, reads and cleanup. Both operate in place on the same allocations. Each sample starts with reinitialized records and adjustments. Outside the timing window the driver checks both subtotals and every updated field and adjustment against an independent calculation. Both modes have warm caches for small inputs. The largest input occupies 68 MiB across both vectors.

| Records | Sequential median | Two-worker median | Sequential / two-worker | Two-worker wins |
|---:|---:|---:|---:|---:|
| 1,024 | 0.0194 ms | 0.1329 ms | 0.15× | 0/60 |
| 16,384 | 0.3414 ms | 0.4484 ms | 0.76× | 28/60 |
| 65,536 | 2.0666 ms | 1.7748 ms | 1.16× | 43/60 |
| 524,288 | 14.8645 ms | 13.0340 ms | 1.14× | 53/60 |

Two independent runs each used four warm-up pairs and 30 measured pairs per size. The order alternated within pairs. At 16,384 records the separate runs gave 0.60× and 1.11×. At 524,288 they gave 1.16× and 1.13×. The data supports a modest benefit for this larger workload and leaves the precise crossover uncertain. [Raw microsecond samples](samples.csv)

## Diagnostics

At 1,024 records, the adjustment ranges were changed from adjacent halves to [0, 512) and [511, 1023). The harness caught the runtime exception and printed its original message.

```text
Overlapping exclusive segments
```

In a separate fresh compilation the helper called by the worker was changed to return `$available * $unit_cost + $rounding`, where `$rounding` is a mutable global. The compiler reported the read site. The harness removed only the temporary directory prefix from the diagnostic.

```text
price.php:3:38: MT safety: worker cannot access global or static storage
```

## Reproduction

From the repository root, with the run 1 Clang runtime built, execute:

```sh
taskset -c 0,2 python3 -B v0.2/specs/planning/benchmarks/mt_segments/run.py > samples-run.csv
```

The driver uses fresh PHP compilation with Xdebug off, Clang 18.1.3 with libc++, C++23, -O3, no debug symbols, no PCH, no LTO, one compile job and the system ld. It reuses the built runtime archive. Application objects are fresh for each run. Build times are independent of the execution medians.

| Build phase, ms | Run 1 | Run 2 |
|---|---:|---:|
| PHP compilation and C++ publication | 130 | 101 |
| Clang frontend, cumulative trace | 2,490 | 1,716 |
| LLVM optimizer, cumulative trace | 204 | 87 |
| LLVM code-generation passes, cumulative trace | 166 | 53 |
| Clang backend including optimizer and code generation, cumulative trace | 392 | 150 |
| Link command wall time | 145 | 77 |
| Native build wall time | 3,468 | 2,207 |
| Overall build wall time | 3,598 | 2,308 |

The native build includes the measurement harness. Trace rows are nested and should not be summed. [Worker](work.php) · [Launch](launch.php) · [Timing and verification](measure.cpp)

## Three-way comparison with already-started workers

The current runner measures sequential, fresh and reused execution in each round. The earlier two-way samples above are historical. All six mode orders are used equally. Two independent builds/runs contributed 60 measured rounds per populated size and 240 rounds for an empty batch, after six warm-up rounds per run and size. Each size starts a separate process. CPU affinity, compiler flags, inputs and result verification match the earlier workload.

| Records | Sequential median, µs | Fresh median, µs | Reused median, µs | Rounds reuse beat fresh |
|---:|---:|---:|---:|---:|
| 0 | 0.14 | 112.64 | 69.39 | 237/240 |
| 1,024 | 19.44 | 116.71 | 90.28 | 56/60 |
| 16,384 | 309.80 | 303.10 | 398.76 | 5/60 |
| 65,536 | 1,675.85 | 1,425.72 | 1,727.83 | 12/60 |
| 524,288 | 12,616.75 | 10,060.90 | 10,656.40 | 20/60 |

The harness waits for both persistent threads to report ready before warm-up. Workers sleep on a condition variable and each takes one job per batch. The measured reused path includes dispatch, wakeup, waiting for both workers, and collecting results. Its one-time construction and final shutdown occur outside the timed region. Both threaded modes execute the compiled launch function and the committed runtime admission loop. In a temporary runtime build the harness inserts one dispatch switch after admission. The normal fresh-thread implementation handles the fresh branch. All three modes passed the per-record and subtotal checks.

The median within-round saving on an empty two-job batch was 44.81 µs. A separate probe with the same CPU affinity measured from immediately before creating the first thread until both new threads had entered their callbacks. It held both workers alive until release. Across 240 samples the median was 110.45 µs, with a middle 50% range of 102.65–124.02 µs. The startup probe and the empty-batch timings are separate experiments. The net saving with reuse includes the cost of waking and synchronizing the persistent pair. Loaded results do not support treating startup as a constant deduction. This experiment does not measure the cause of the larger-batch differences.

For the two three-way builds, respectively, PHP publication took 135/97 ms, native build wall time 5,159/4,253 ms and total build wall time 5,293/4,351 ms. Clang cumulative frontend time was 4,308/3,441 ms, optimizer 313/297 ms, code-generation passes 121/115 ms, and link wall time 75/76 ms. The application, harness and benchmark runtime translation unit were built fresh; other runtime code used the existing archive.

Reuse measurements use the reproduction command above. Run the isolated startup probe with:

```sh
clang++ -O3 -std=c++23 -stdlib=libc++ -pthread boot.cpp -o /tmp/mt-boot
taskset -c 0,2 /tmp/mt-boot > boot-samples.csv
```

[Three-way samples](reuse-samples.csv) · [Startup samples](boot-samples.csv) · [Executor](reuse.hpp)
