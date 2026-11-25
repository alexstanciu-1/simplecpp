# Native C++ arithmetic loop scaling
Doc Status: supporting

This benchmark builds loop.cpp directly with GCC and Clang on each Linux host using C++20, -O3 and -pthread. There are 24 independent chains of unsigned 64-bit arithmetic. Every inner-loop iteration depends on the preceding iteration in its chain. Each worker gets the same number of chains at every tested width. The only shared output is one cache-line-aligned result per worker, written after the loop. The main thread does its share and joins the other threads. Wall time includes thread launch, affinity setup, the loop, joins and the checksum reduction. Process startup and compilation are outside the timer.

Run from this directory:

```sh
python3 run.py > short.csv
python3 run.py --long > long.csv
python3 run.py --placement > placement.csv
```

The default sweep uses 4 million iterations per chain. The long sweep and dev002 placement control use 16 million. Both compilers and all placements are interleaved in a deterministic shuffled order. Round zero is warm-up. The ordinary sweeps have seven measured observations per cell, the placement sweep five. Speedup divides the median single-thread wall time by the median for that configuration within the same sweep, host and compiler. cpu_s is the sum of worker CPU time inside the computation. Dividing it by wall_s estimates the mean number of simultaneously scheduled software threads during a batch.

Spread uses one logical CPU from each reported physical core, then adds siblings if needed. The siblings control pins two workers to the two logical CPUs of the same reported core. The same-cpu control pins them both to one logical CPU. WSL2 reports guest topology. On dev002, cores 0–7 share one L3 and cores 8–15 share a second L3. The placement sweep compares the lower eight, upper eight and every other core across both groups.

## Median speedup by host and compiler

| Sweep | Host | Compiler | 1 thread (ms) | 2 | 4 | 6 | 8 |
|---|---|---|---:|---:|---:|---:|---:|
| Short | wsl | clang++ | 148.0 | 1.96× | 3.86× | 5.59× | 6.89× |
| Short | wsl | g++ | 150.0 | 1.97× | 3.85× | 5.06× | 6.22× |
| Short | dev002 | clang++ | 126.5 | 1.98× | 3.57× | 4.87× | 6.26× |
| Short | dev002 | g++ | 126.5 | 1.98× | 3.47× | 4.88× | 6.21× |
| Long | wsl | clang++ | 591.4 | 1.97× | 3.94× | 5.78× | 7.33× |
| Long | wsl | g++ | 590.1 | 1.94× | 3.89× | 5.58× | 6.94× |
| Long | dev002 | clang++ | 510.1 | 1.97× | 3.90× | 5.64× | 7.40× |
| Long | dev002 | g++ | 511.6 | 1.97× | 3.85× | 5.69× | 7.41× |

In the separate long dev002 placement control, eight workers reached 7.35–7.44× across the lower, upper and alternating masks with both compilers. The samples include normal machine activity. Clock frequencies and host scheduling were uncontrolled.

## Inventory-duration arithmetic batches

Run `python3 run.py --batch` for the 40,000 iterations/chain sweep. Add `--trace` for a separate timestamped build or `--fourfold` for 160,000 iterations/chain. Each sweep uses 31 measured rounds after warm-up, one fresh process per observation. Process creation is outside the timer. Thread creation, affinity setup (including the caller in the serial baseline), join and reduction are inside. This lifecycle differs from the inventory benchmark, which repeatedly dispatches batches in one process.

| Host | Compiler | Single-thread µs | 2-thread µs | 4-thread µs | 8-thread µs | 8-thread speedup at fourfold work |
|---|---|---:|---:|---:|---:|---:|
| WSL2 | Clang | 1604 | 1079 | 887 | 1047 | 4.00× |
| WSL2 | GCC | 1574 | 1080 | 829 | 1035 | 3.89× |
| dev002 | Clang | 1648 | 895 | 513 | 445 | 4.98× |
| dev002 | GCC | 1650 | 899 | 513 | 440 | 5.10× |

The traced CSVs report seconds from timer start to earliest kernel entry, earliest to latest kernel entry, and final kernel exit to timer stop. Start spread can overlap other workers' computation. These timings include scheduling and affinity effects. Each file also records summed worker CPU time. For example, in the small eight-thread Clang sweep on WSL2 the median first entry was 265 µs into the batch and the median start spread was 528 µs. Summed CPU time divided by wall time had a median of 1.40 simultaneous computing workers. On dev002 the corresponding values were 84 µs, 137 µs and 3.42 workers. Uninstrumented timings are the basis for the speedups.

[WSL small](wsl-batch.csv) · [WSL traced](wsl-batch-trace.csv) · [WSL fourfold](wsl-fourfold.csv) · [dev002 small](dev002-batch.csv) · [dev002 traced](dev002-batch-trace.csv) · [dev002 fourfold](dev002-fourfold.csv)

## Matched 600 ms single-thread baselines

Run `python3 run.py --600ms` on each host. Six direct single-thread probes (three from each compiler) set one iteration count per host. Both compilers, both caller roles and every worker count then use this exact amount of arithmetic. Round zero is warm-up. Reported times are medians of seven measured rounds in milliseconds. A sample comprises one dispatch. Launch, pinning, join and result collection are timed.

With a participating caller, N computing threads means the caller plus N−1 new threads. With a waiting caller, N fresh threads compute while the caller joins them. Both use the same mapping of compute jobs to CPUs. The one-thread participating case is the direct sequential baseline for speedup.

| Host | Compiler | Caller | 1 | 2 | 4 | 8 | 8-thread speedup |
|---|---|---|---:|---:|---:|---:|---:|
| wsl | clang++ | participate | 601.0 | 308.7 | 162.2 | 89.2 | 6.74× |
| wsl | clang++ | wait | 606.6 | 306.5 | 154.6 | 106.1 | 5.66× |
| wsl | g++ | participate | 614.3 | 318.5 | 160.4 | 83.2 | 7.38× |
| wsl | g++ | wait | 605.1 | 306.8 | 157.6 | 88.4 | 6.95× |
| dev002 | clang++ | participate | 602.5 | 302.4 | 155.2 | 80.5 | 7.49× |
| dev002 | clang++ | wait | 601.2 | 302.2 | 153.9 | 80.3 | 7.50× |
| dev002 | g++ | participate | 601.5 | 301.3 | 154.0 | 79.5 | 7.57× |
| dev002 | g++ | wait | 602.3 | 301.4 | 155.3 | 80.3 | 7.49× |

Work per chain was 16,276,385 iterations on WSL2 and 18,885,560 on dev002. All 128 observations per host produced a consistent checksum for that host's iteration count.

[WSL2 samples](wsl-600ms.csv) · [dev002 samples](dev002-600ms.csv)

## One field read per record

Run `python3 run.py --records > record.csv`. The benchmark keeps 65,536 inventory-sized records (128 bytes each) in an 8 MiB array. The main thread initializes the array before timing. The field kernel reads the first unsigned 64-bit field of every record once per pass. The index control supplies the identical value from the loop counter. Both feed that value into the same shift, multiply and rotate recurrence. State carries over between passes. The compiler-generated inner loops inspected on WSL2 use a memory-source XOR for the field and register-source XOR for the control under both Clang 18 and GCC 13.

The calibration targets 600 ms for the single-thread field kernel. The pass count is fixed across widths and controls for each host/compiler combination. Each global pass accounts for exactly 65,536 record visits across all workers. Workers process all passes over their own contiguous slices, without a barrier per pass. Numbers include one fresh dispatch and join per sample, with the caller doing one share. Round zero is warm-up. Each cell is the median of seven measured samples. Field and index kernel checksums match for each pass count.

| Host | Compiler | Kernel | Passes | Single-thread total (ms) | 1 (µs/pass) | 2 (µs/pass) | 4 (µs/pass) | 8 (µs/pass) |
|---|---|---|---:|---:|---:|---:|---:|---:|
| wsl | clang++ | field | 1118 | 652.4 | 583.53 (1.00×) | 276.69 (2.11×) | 92.34 (6.32×) | 32.20 (18.12×) |
| wsl | clang++ | index | 1118 | 133.3 | 119.19 (1.00×) | 60.13 (1.98×) | 30.66 (3.89×) | 20.52 (5.81×) |
| wsl | g++ | field | 963 | 619.6 | 643.42 (1.00×) | 259.60 (2.48×) | 86.52 (7.44×) | 30.49 (21.10×) |
| wsl | g++ | index | 963 | 113.3 | 117.64 (1.00×) | 59.40 (1.98×) | 31.37 (3.75×) | 17.13 (6.87×) |
| dev002 | clang++ | field | 5878 | 597.6 | 101.67 (1.00×) | 52.46 (1.94×) | 27.01 (3.76×) | 15.35 (6.62×) |
| dev002 | clang++ | index | 5878 | 592.6 | 100.82 (1.00×) | 50.57 (1.99×) | 25.93 (3.89×) | 13.42 (7.51×) |
| dev002 | g++ | field | 5878 | 597.7 | 101.69 (1.00×) | 51.45 (1.98×) | 26.55 (3.83×) | 15.93 (6.38×) |
| dev002 | g++ | index | 5878 | 590.8 | 100.51 (1.00×) | 50.46 (1.99×) | 25.94 (3.87×) | 13.45 (7.47×) |

The locality control uses `python3 run.py --locality record.csv`. It interleaves the original one-thread sweep, a one-thread traversal that completes every pass over one 1 MiB slice before moving to the next slice, and the eight-thread sweep with those same slices. All three compute identical checksums. Hardware cache misses and memory bandwidth were not measured.

[WSL2 samples](wsl-record.csv) · [dev002 samples](dev002-record.csv) · [WSL2 locality control](wsl-record-locality.csv) · [dev002 locality control](dev002-record-locality.csv)

## One in-place write on dev002

Reproduce the measured comparison with `python3 run.py --writes --passes 5801`. Both kernels read the 64-bit sku field and feed its old value to the original arithmetic recurrence. The read/write kernel also stores old value + 1 to that same field once per record per pass. Each sample starts with sku equal to the record index. After timing, the program checks every field against the expected final value. The runner verifies checksum agreement across widths and compilers for each kernel.

An initial calibration sweep provided per-pass timings. The final sweep uses 5,801 passes for both modes, both compilers and all thread counts to target 600 ms sequentially. The 1/2/4/8 columns give median microseconds per complete 65,536-record pass with within-mode speedup in parentheses. The caller participates, workers have exclusive contiguous slices on distinct physical cores, and one launch and join are included per sample. The two modes and compilers are interleaved for seven measured rounds after warm-up.

| Compiler | Kernel | Passes | 1-thread total (ms) | 1 | 2 | 4 | 8 |
|---|---|---:|---:|---:|---:|---:|---:|
| clang++ | field | 5801 | 582.6 | 100.43 (1.00×) | 51.20 (1.96×) | 26.62 (3.77×) | 14.98 (6.70×) |
| clang++ | write | 5801 | 581.1 | 100.17 (1.00×) | 51.46 (1.95×) | 26.33 (3.80×) | 15.36 (6.52×) |
| g++ | field | 5801 | 583.2 | 100.53 (1.00×) | 50.29 (2.00×) | 26.06 (3.86×) | 15.28 (6.58×) |
| g++ | write | 5801 | 592.6 | 102.15 (1.00×) | 50.96 (2.00×) | 26.17 (3.90×) | 15.47 (6.60×) |

Optimized dev002 assembly for both compilers shows a store of the incremented value to the record in the inner loop. [Raw read-only and read/write samples](dev002-record-write.csv).

## Fresh-thread dispatch interval on dev002

Run `python3 run.py --dispatch`. Both schedules use the same compiled read/write inner loop, 65,536 records and 5,801 passes. Each worker retains its own arithmetic state and record slice across dispatches. In the batch schedule the workers run all passes independently. In the per-pass schedule the caller starts N−1 fresh threads per pass, computes its own slice and joins every thread before starting the next pass. At one worker the caller runs inline in both cases. Each new worker sets its affinity on entry and the caller pins itself once. Initialization and per-record validation are outside the wall timer. The measured difference includes launches, affinity, scheduling, per-pass joins and any resulting locality changes. The CSV cpu_s column measures process CPU time across threads.

Times are medians of seven interleaved measured rounds after one warm-up round. The extra microseconds per pass are the difference between the two median totals divided by 5,801. Speedups are relative to the one-thread median of the same compiler and dispatch schedule.

| Compiler | Workers | Batch ms | Per-pass ms | Batch µs/pass | Per-pass µs/pass | Extra µs/pass | Batch speedup | Per-pass speedup |
|---|---:|---:|---:|---:|---:|---:|---:|---:|
| clang++ | 1 | 595.5 | 595.6 | 102.65 | 102.67 | +0.02 | 1.00× | 1.00× |
| clang++ | 2 | 303.4 | 826.0 | 52.30 | 142.39 | +90.08 | 1.96× | 0.72× |
| clang++ | 4 | 157.1 | 735.7 | 27.08 | 126.82 | +99.74 | 3.79× | 0.81× |
| clang++ | 8 | 91.2 | 1245.1 | 15.72 | 214.64 | +198.92 | 6.53× | 0.48× |
| g++ | 1 | 598.4 | 603.2 | 103.15 | 103.98 | +0.82 | 1.00× | 1.00× |
| g++ | 2 | 299.9 | 795.9 | 51.70 | 137.20 | +85.50 | 2.00× | 0.76× |
| g++ | 4 | 166.3 | 740.0 | 28.67 | 127.57 | +98.90 | 3.60× | 0.82× |
| g++ | 8 | 102.2 | 1243.5 | 17.62 | 214.36 | +196.74 | 5.85× | 0.49× |

All samples matched the original read/write checksum and passed final per-record validation. [Raw dev002 dispatch samples](dev002-dispatch.csv).

## Prestarted workers on dev002

Run `python3 run.py --pool` to interleave all three schedules. Each performs 5,801 passes over the same 65,536 records with the caller handling one slice. The pool starts and pins N−1 workers before timing. On each pass the caller signals the workers using a condition variable, processes its own slice, and waits for all slices to finish. The last worker signals completion. Shutdown and final field validation are outside the timer. At one thread all schedules run inline. Each worker keeps its arithmetic state and record slice across passes. Every sample must reproduce the same checksum.

Times are median wall-clock microseconds per full pass for seven measured rounds after warm-up. Saved time is fresh minus pool. The remaining difference is pool minus batch. Pool speedup uses its own one-thread baseline. Batch runs all passes in one dispatch with no inter-pass synchronization.

| Compiler | Threads | Batch µs | Fresh µs | Pool µs | Pool speedup | Saved µs | Remaining µs |
|---|---:|---:|---:|---:|---:|---:|---:|
| clang++ | 1 | 102.79 | 102.75 | 101.97 | 1.00× | +0.78 | -0.83 |
| clang++ | 2 | 52.04 | 134.99 | 60.13 | 1.70× | +74.87 | +8.09 |
| clang++ | 4 | 26.43 | 126.98 | 34.81 | 2.93× | +92.18 | +8.38 |
| clang++ | 8 | 15.21 | 214.57 | 27.29 | 3.74× | +187.28 | +12.07 |
| g++ | 1 | 103.36 | 103.69 | 103.42 | 1.00× | +0.27 | +0.06 |
| g++ | 2 | 53.12 | 136.03 | 59.81 | 1.73× | +76.22 | +6.68 |
| g++ | 4 | 26.78 | 130.71 | 35.16 | 2.94× | +95.55 | +8.38 |
| g++ | 8 | 16.34 | 223.54 | 28.84 | 3.59× | +194.70 | +12.51 |

All 192 samples matched the prior dispatch experiment checksum and each sample validated all updated fields. [Raw dev002 samples](dev002-pool.csv).

## Dispatch and completion profile on dev002

Both sweeps run 5,801 passes with 65,536 records. Pool workers are pinned and ready before timing, and shutdown joins occur afterward. The caller computes one slice in each pass. The active-wait variant publishes one generation number with release/acquire atomics. Each worker polls with the x86 PAUSE instruction and publishes completion in its own cache-line-aligned slot. The caller polls for completion before publishing the next pass. This variant assumes dedicated cores during the timed sequence of passes.

Run `python3 run.py --compare-pool` for uninstrumented wall and process CPU times for batch, blocking pool, and active-wait pool. Run `python3 run.py --profile-pool` separately for the two pools and fresh threads. Every 32nd pass (182 per sample) records the three sequential wall-time intervals on the caller: dispatch, its own record computation, and completion wait. For fresh threads the wait includes the OS thread joins. For each background worker the profile measures elapsed time from caller dispatch start to kernel entry and CPU time inside the kernel. Worker times overlap the caller intervals. The wait_cpu_us column is the caller's thread CPU time during its completion wait. Clock reads contribute overhead to the profiled samples. Raw rows contain per-sample means for those positions. Use uninstrumented samples for speedup and total CPU comparisons.

[Uninstrumented samples](dev002-pool-active.csv) · [Profile samples](dev002-pool-profile.csv)

| Compiler | Workers | Schedule | Wall µs/pass | Process CPU ms/run |
|---|---:|---|---:|---:|
| clang++ | 1 | batch | 103.10 | 597.99 |
| clang++ | 1 | pool | 103.54 | 600.56 |
| clang++ | 1 | active | 104.89 | 608.37 |
| clang++ | 2 | batch | 52.25 | 603.70 |
| clang++ | 2 | pool | 60.78 | 653.69 |
| clang++ | 2 | active | 53.23 | 617.31 |
| clang++ | 4 | batch | 26.68 | 611.77 |
| clang++ | 4 | pool | 35.97 | 697.31 |
| clang++ | 4 | active | 27.57 | 638.97 |
| clang++ | 8 | batch | 16.13 | 719.80 |
| clang++ | 8 | pool | 28.13 | 940.67 |
| clang++ | 8 | active | 15.94 | 736.42 |
| g++ | 1 | batch | 103.61 | 601.00 |
| g++ | 1 | pool | 104.03 | 603.05 |
| g++ | 1 | active | 105.55 | 612.21 |
| g++ | 2 | batch | 52.84 | 603.01 |
| g++ | 2 | pool | 59.58 | 644.77 |
| g++ | 2 | active | 53.10 | 615.50 |
| g++ | 4 | batch | 29.08 | 637.37 |
| g++ | 4 | pool | 38.05 | 711.81 |
| g++ | 4 | active | 31.26 | 722.47 |
| g++ | 8 | batch | 18.22 | 746.00 |
| g++ | 8 | pool | 28.82 | 927.41 |
| g++ | 8 | active | 18.38 | 855.22 |

| Compiler | Workers | Schedule | Dispatch µs | Caller kernel µs | Completion µs | CPU during wait µs | Worker start delay µs | Worker kernel CPU µs |
|---|---:|---|---:|---:|---:|---:|---:|---:|
| clang++ | 2 | pool | 0.72 | 52.50 | 9.77 | 3.38 | 4.47 | 53.73 |
| clang++ | 2 | active | 0.14 | 53.03 | 1.63 | 1.51 | 0.10 | 53.72 |
| clang++ | 2 | per_pass | 11.52 | 52.27 | 77.99 | 4.01 | 77.45 | 55.01 |
| clang++ | 4 | pool | 1.06 | 26.99 | 10.50 | 3.44 | 4.33 | 27.84 |
| clang++ | 4 | active | 0.17 | 26.91 | 2.82 | 2.69 | 0.12 | 28.05 |
| clang++ | 4 | per_pass | 28.25 | 26.53 | 74.69 | 8.31 | 78.99 | 29.37 |
| clang++ | 8 | pool | 1.92 | 14.88 | 12.83 | 3.71 | 5.25 | 17.13 |
| clang++ | 8 | active | 0.20 | 14.69 | 3.27 | 3.13 | 0.20 | 15.80 |
| clang++ | 8 | per_pass | 90.63 | 13.86 | 117.03 | 32.16 | 150.43 | 16.85 |
| g++ | 2 | pool | 0.73 | 53.10 | 8.49 | 3.49 | 4.11 | 53.61 |
| g++ | 2 | active | 0.15 | 52.67 | 1.66 | 1.52 | 0.09 | 53.56 |
| g++ | 2 | per_pass | 12.33 | 52.74 | 91.33 | 4.17 | 82.25 | 64.62 |
| g++ | 4 | pool | 1.08 | 26.95 | 11.52 | 3.64 | 4.40 | 29.00 |
| g++ | 4 | active | 0.16 | 26.89 | 2.42 | 2.27 | 0.12 | 28.44 |
| g++ | 4 | per_pass | 28.87 | 26.54 | 78.80 | 8.40 | 79.65 | 30.92 |
| g++ | 8 | pool | 2.02 | 15.40 | 14.87 | 3.97 | 5.23 | 17.75 |
| g++ | 8 | active | 0.18 | 15.21 | 5.29 | 5.09 | 0.23 | 16.81 |
| g++ | 8 | per_pass | 90.98 | 13.46 | 121.24 | 31.75 | 149.65 | 16.86 |

## Eight synchronized traversals on dev002

Run `python3 run.py --eight-passes`. Each pass visits all 65,536 records once, increments one field and feeds its prior value into a chain of dependent integer operations. Preliminary single-thread probes from both compilers choose one common number of arithmetic steps per record for approximately 600 ms total work. Every measured configuration uses that count. This synthetic workload models the traversal and synchronization pattern of eight compiler passes. Parsing, symbol resolution and compiler dependency traversal are outside its scope.

The caller participates, with N−1 pinned active-wait workers ready before the timer starts. Each pass includes publication, work and a wait for completion before the next pass. Pool setup, shutdown joins and field validation are outside the timer. Results are median total wall time across seven shuffled rounds after warm-up. Mean time per pass divides that total by eight.

| Compiler | Threads | Total ms | Mean ms/pass | Speedup |
|---|---:|---:|---:|---:|
| clang++ | 1 | 602.72 | 75.34 | 1.00× |
| clang++ | 2 | 301.97 | 37.75 | 2.00× |
| clang++ | 4 | 152.53 | 19.07 | 3.95× |
| clang++ | 8 | 80.82 | 10.10 | 7.46× |
| g++ | 1 | 601.05 | 75.13 | 1.00× |
| g++ | 2 | 301.54 | 37.69 | 1.99× |
| g++ | 4 | 155.79 | 19.47 | 3.86× |
| g++ | 8 | 81.00 | 10.12 | 7.42× |

Every sample validated all final field values and reproduced the same checksum. [Raw dev002 samples](dev002-eight-passes.csv).

## Short-task completion barriers on dev002

Run `python3 run.py --peer-barrier` for interleaved uninstrumented measurements and `python3 run.py --tail-trace` separately for a low-instrumentation breakdown. Both use the 5,801 short passes, pool setup and teardown outside timing, and independently pinned workers with persistent slices. The active reference has the caller collect completions and publish each new pass. The dissemination variant starts the team once and synchronizes workers with peer signals after every pass. At eight threads it uses three communication rounds. Each worker acquires a partner signal before releasing the next round; monotonic step numbers allow flag reuse. Both enforce a full pass boundary. Peer continuation assumes the next pass is already known to the team. [Barrier algorithms and pseudocode](https://cs.rochester.edu/research/synchronization/pseudocode/ss.html).

The active-reference trace timestamps the caller's completion-wait entry, each background kernel's return (just before signalling), and the caller's wait exit every 32nd pass. Outstanding time runs until the last observed kernel return if that happens after the caller's work finishes. The rest runs from that point until the caller observes completion. These are wall intervals and include timestamp overhead and possible descheduling. They are collected separately from the uninstrumented performance comparison. Results use medians over seven rounds after warm-up.

| Compiler | Workers | Caller barrier µs/pass | Peer barrier µs/pass | Peer faster rounds | Outstanding µs | After final kernel µs |
|---|---:|---:|---:|---:|---:|---:|
| clang++ | 1 | 102.79 | 102.78 | 4/7 | 0.000 | 0.023 |
| clang++ | 2 | 52.97 | 52.87 | 2/7 | 0.170 | 0.076 |
| clang++ | 4 | 27.73 | 27.50 | 5/7 | 1.095 | 0.090 |
| clang++ | 8 | 16.37 | 16.26 | 4/7 | 1.857 | 0.111 |
| g++ | 1 | 111.26 | 111.79 | 3/7 | 0.000 | 0.024 |
| g++ | 2 | 56.38 | 57.54 | 2/7 | 5.259 | 0.072 |
| g++ | 4 | 31.81 | 34.21 | 3/7 | 5.643 | 0.105 |
| g++ | 8 | 19.17 | 19.10 | 5/7 | 4.410 | 0.121 |

All samples validated every record and matched the previous workload checksum. [Barrier samples](dev002-short-barriers.csv) · [Completion-wait samples](dev002-short-tail.csv).

## Rotating the last-finisher probe on dev002

Run `python3 rotation.py >rotation.csv` on dev002, then gzip the CSV and run `python3 rotation_summary.py rotation.csv.gz`. All runs use the eight-thread active pool, 5,801 passes, the original 65,536-record kernel and physical cores 0–7. Worker 0 dispatches and computes. For worker w, CPU is (w + cpu_shift) mod 8 and slice is (w + slice_shift) mod 8. Both offsets vary independently over all eight values. Every worker therefore runs every slice on every core. The 128 compiler/placement combinations run once per shuffled block, repeated three times. Each assignment stays fixed for all 5,801 passes, and each process allocates a fresh array.

The unweighted kernel timestamps all eight entrants and finishers every 32nd pass after pass zero, relative to the caller's pre-publication timestamp. Post-timing output retains the worker, slice and CPU mapping. A last finisher has the latest measured kernel end (before publishing completion). Exact ties share credit. The summary also reports finishes at least 1 µs behind the runner-up and the fraction of last finishers that started last or had the longest elapsed kernel time. Kernel elapsed time includes any descheduling. The 181 sampled passes within each process share the same placement and machine conditions.

| Compiler | Core 3 last, first / second / third block | Core 3 last overall | Lowest–highest slice share | Last finisher also longest elapsed kernel |
|---|---:|---:|---:|---:|
| Clang 18 | 10.7% / 46.1% / 80.8% | 45.9% | 9.0–14.4% | 74.9% |
| GCC 13 | 17.6% / 48.5% / 83.3% | 49.8% | 10.1–14.4% | 69.7% |

Each compiler contributed 34,752 sampled passes. Core 7 was most often last in the first block with both compilers. Core 3 dominated the third block despite the randomized rotations. With GCC, core 3 was last by at least 1 µs on 41.1% of all passes; with Clang it met that margin on 2.5%. This measured placement effect varied with time. The cause of the core slowdown was not instrumented. [Compressed per-worker timestamps](dev002-rotation.csv.gz) · [Summary script](rotation_summary.py).

## Core frequency, SMT activity and scheduling during rotation

Run `python3 rotation.py --telemetry hardware.csv >sched-rotation.csv` on dev002; gzip both CSVs and pass the sampled-pass CSV then the hardware CSV to `rotation_telemetry_summary.py` (SciPy). This repeats the same assignments, shuffled blocks and 181 sampled passes per run. All eight workers record start, finish, thread CPU time, and involuntary and voluntary context-switch deltas around each sampled kernel. The thread CPU and switch probes enclose the kernel timestamps and add overhead. Whole-worker CPU versus elapsed time and switch deltas cover the active sequence including spin waiting. Wall minus thread CPU is an off-CPU estimate; an involuntary switch directly records a scheduling event. Pool startup and teardown are outside timing.

On each side of the timed loop, read each benchmark CPU's hardware CPPC delivered/reference counters and the busy/total tick fields of its SMT sibling in /proc/stat. Compute average delivered MHz as nominal MHz × reference_perf / nominal_perf × delta_delivered / delta_reference. CPU 19 is the verified sibling of core 3. Sibling busy excludes idle and iowait and avoids double counting guest time. Linux reports these counters in 10 ms units on this host. Frequency and sibling busy are run-window measurements; neither resolves an individual kernel. The two hardware snapshots bracket the loop with a small sequential read skew. [Linux CPPC feedback-counter documentation](https://docs.kernel.org/admin-guide/acpi/cppc_sysfs.html).

In the repeat core 3 finished last on 4.7% of Clang and 6.2% of GCC sampled passes. Cores 0 and 1 together accounted for 60.3% and 73.4%, respectively. The association with CPU 19 is summarized below. Last share averages each run's 181 probes. GHz and kernel times are medians of run-level measurements.

| Build | CPU 19 busy | Runs | Core 3 last | Core 3 GHz | Core 3 kernel µs |
|---|---|---:|---:|---:|---:|
| Clang | Zero busy ticks | 165 | 2.2% | 4.335 | 13.59 |
| Clang | Above zero, below 50% | 18 | 12.1% | 4.310 | 13.98 |
| Clang | At least 50% | 9 | 36.5% | 4.282 | 14.07 |
| GCC | Zero busy ticks | 157 | 1.3% | 4.068 | 14.43 |
| GCC | Above zero, below 50% | 24 | 8.2% | 4.084 | 14.78 |
| GCC | At least 50% | 11 | 71.2% | 4.126 | 17.71 |

Among last finishes on core 3, 0.24% (Clang) and 0% (GCC) coincided with a kernel-bracketing context switch of that worker. Median whole-worker off-CPU fraction for core 3 was indistinguishable from zero at this precision; bracketing the wall interval by CPU-clock reads can produce tiny negative estimates. This is observational, with 9 and 11 high-activity runs and scheduler probes that add overhead. The old run did not capture the hardware data.

[Worker samples and scheduler probes](dev002-rotation-sched.csv.gz) · [Per-core hardware and whole-worker counters](dev002-rotation-hardware.csv.gz)

## Controlled SMT contention on dev002

Reproduce with `python3 smt_test.py`. Eight workers stay pinned to CPUs 0–7, including the caller on CPU 0. Each trial uses the active pool for 5,801 passes over 65,536 records. Within each matched triplet the build, worker roles and slice assignments are fixed. The load conditions use zero added threads, one thread on CPU 19 (sibling of core 3), or one on CPU 8 (another physical core outside the team's L3 group). The latter two link the same GCC-built register-only integer kernel with four independent arithmetic chains. The added thread is ready before timing, and the controller reads its thread CPU clock across the timed interval. Shutdown and record validation are outside timing. Other host load is uncontrolled.

Each compiler runs 48 triplets: eight slice rotations across six shuffled blocks. For each compiler/rotation all six condition orders occur once. Kernel entries and exits are sampled for all workers on 181 passes per trial. The denominator of each percentage is 8,688 sampled passes; ties split last-place credit. Clear-last requires core 3 to finish at least 1 µs after the runner-up. Kernel and start times are medians of per-run medians. Trial duration and load occupancy are medians across runs.

| Compiler | Load | Core 3 last | Core 3 clear-last | Core 3 kernel µs | Core 3 start µs | Trial ms | Added thread occupancy |
|---|---|---:|---:|---:|---:|---:|---:|
| clang++ | none | 7.8% | 0.0% | 12.94 | 0.16 | 81.7 | 0.0% |
| clang++ | cpu8 | 9.5% | 0.1% | 12.96 | 0.17 | 81.7 | 100.0% |
| clang++ | cpu19 | 79.1% | 9.5% | 13.97 | 0.16 | 86.1 | 100.0% |
| g++ | none | 11.8% | 0.5% | 14.49 | 0.17 | 95.7 | 0.0% |
| g++ | cpu8 | 11.7% | 0.6% | 14.88 | 0.17 | 97.4 | 100.0% |
| g++ | cpu19 | 92.1% | 84.6% | 18.95 | 0.14 | 112.8 | 100.0% |

All 288 trials matched the reference checksum and passed per-record verification. [Matched trial results](dev002-smt-load.csv).

## Moving a worker away from a loaded SMT sibling on dev002

Run `python3 smt_test.py --relocate`. The benchmark selects one spare physical core from CPUs 8–15 using the lowest maximum busy fraction of its two logical CPUs in a two-second prescreen. It holds that choice fixed throughout. Four conditions cross worker 3's placement (original core or spare) with no added load or the identical controlled load on CPU 19. Only worker 3 changes CPU. All available spare cores are in the other L3 group. The moved/no-load control measures the net effect of the placement change.

Each compiler runs 64 matched sets: eight slice rotations and eight blocks. Each four-condition sequence is position- and predecessor-balanced across each four-block cycle. Pool setup and shutdown remain outside timing. Wall and worker kernel times are medians, and last-finisher shares use 181 sampled passes per trial. Spare and sibling busy fractions are /proc/stat tick deltas across the process invocation, including setup. Normal host activity is uncontrolled.

| Compiler | Condition | Total ms | Worker 3 last | Worker 3 kernel µs |
|---|---|---:|---:|---:|
| clang++ | home_none | 87.67 | 6.0% | 13.52 |
| clang++ | home_cpu19 | 91.63 | 73.6% | 14.63 |
| clang++ | spare_none | 89.43 | 37.9% | 13.45 |
| clang++ | spare_cpu19 | 89.88 | 35.1% | 13.71 |
| g++ | home_none | 104.40 | 8.1% | 15.44 |
| g++ | home_cpu19 | 115.31 | 85.7% | 19.36 |
| g++ | spare_none | 106.46 | 22.6% | 15.37 |
| g++ | spare_cpu19 | 107.25 | 22.7% | 15.31 |

CPU 13 / sibling 29 were 0.5% / 0% busy in the prescreen. All 512 trials matched the reference checksum and passed per-record validation. [Matched relocation trials](dev002-smt-relocate.csv).

## Adaptive placement after delayed SMT load on dev002

Run `python3 adaptive_test.py`. Each matched set uses the same eight pinned participants, 5,801 passes, slice shift and synthetic onset pass (uniformly drawn between 1,934 and 2,900). CPU 19's pinned load helper sleeps before onset, wakes on an atomic signal and reports when it starts computing. Fixed and adaptive conditions run both with and without the added load. Each compiler has 64 matched sets with balanced orders. Pool setup and shutdown are excluded. All observations, spare selection, changing affinity on worker 3, subsequent working-set warm-up, and all pass completion waits are inside the wall timer.

Every 512 passes, until a move, the caller samples /proc/stat across the logical CPUs. Across two polling intervals, CPU 19 must have accumulated at least two busy ticks and been at least half busy. The detector chooses the first unused physical core (8–15) for which both SMT siblings recorded zero busy ticks and both tick denominators are positive. Only worker 3 changes affinity, before its next slice, with at most one migration in each run. The detector does not receive the load schedule. CPU observations have 10 ms accounting granularity and the spare cores are in a different L3 group. No-added-load trials can still encounter real host contention.

| Build | Policy and condition | Median total ms | Moves |
|---|---|---:|---:|
| clang++ | fixed_quiet | 89.90 | 0/64 |
| clang++ | adapt_quiet | 90.63 | 1/64 |
| clang++ | fixed_load | 90.16 | 0/64 |
| clang++ | adapt_load | 91.78 | 64/64 |
| g++ | fixed_quiet | 100.90 | 0/64 |
| g++ | adapt_quiet | 101.81 | 0/64 |
| g++ | fixed_load | 105.46 | 0/64 |
| g++ | adapt_load | 107.37 | 64/64 |

[Matched full-run and migration measurements](dev002-adaptive.csv). Negative decision and migration timestamps indicate that no move occurred. Load CPU time is measured separately and is included in process CPU time.

## Adaptive-placement break-even on dev002

Run `python3 adaptive_horizon.py`. The eight active-pool participants and CPU 19 load kernel are identical to the delayed-load test. For each compiler, slice rotation and block, the onset pass is taken from the prior fixed/load trial in dev002-adaptive.csv. The benchmark appends an exact number of passes after that trigger and keeps the load running through completion. It does not communicate the onset to the placement detector. Detection, affinity changes, migration and subsequent working-set warm-up are timed. Setup and shutdown are excluded. Every process validates the final record state; the runner also checks the checksum within every matched pair.

The initial six horizons have 32 pairs per compiler: eight rotations in each of four blocks. The separate follow-up (run with `--extended`) probes 12,288 and 65,536 passes with 16 pairs per compiler using two more blocks from the original onset schedule. Horizons and builds are interleaved in random order within each block; fixed/adaptive order is counterbalanced. Savings compare total wall times within pairs, including the entire period preceding load onset. Fixed post-onset time is measured from the load thread's actual start. The pointwise order-statistic intervals describe paired-median uncertainty under independent pairs. Shared-host activity can correlate successive pairs and the intervals do not adjust for testing multiple horizons.
| Build | Passes after onset | Fixed post-onset ms | Fixed total ms | Adaptive total ms | Paired saving ms [95% interval] | Wins | Moves |
|---|---:|---:|---:|---:|---:|---:|---:|
| clang++ | 1,024 | 14.7 | 51.9 | 51.6 | -1.42 [-2.24, -0.12] | 10/32 | 5/32 |
| clang++ | 2,048 | 29.2 | 66.9 | 66.0 | -0.03 [-1.17, +2.59] | 16/32 | 28/32 |
| clang++ | 4,096 | 60.5 | 97.0 | 96.5 | +0.10 [-1.37, +1.38] | 16/32 | 32/32 |
| clang++ | 8,192 | 119.4 | 157.0 | 155.4 | +1.21 [-0.42, +5.41] | 19/32 | 32/32 |
| clang++ | 12,288 | 180.2 | 218.3 | 207.2 | +10.07 [+0.71, +12.72] | 12/16 | 16/16 |
| clang++ | 16,384 | 235.1 | 271.7 | 268.7 | +3.78 [+1.44, +5.60] | 24/32 | 32/32 |
| clang++ | 32,768 | 469.8 | 510.5 | 497.6 | +11.45 [+6.65, +18.48] | 26/32 | 32/32 |
| clang++ | 65,536 | 951.6 | 987.1 | 969.5 | +25.95 [+16.59, +33.17] | 13/16 | 16/16 |
| g++ | 1,024 | 16.3 | 54.1 | 55.7 | +0.79 [-1.96, +2.48] | 18/32 | 10/32 |
| g++ | 2,048 | 31.9 | 70.7 | 71.4 | -0.70 [-1.54, +1.36] | 14/32 | 30/32 |
| g++ | 4,096 | 64.0 | 103.0 | 102.4 | +1.02 [-1.89, +2.59] | 18/32 | 32/32 |
| g++ | 8,192 | 127.3 | 168.9 | 163.3 | +0.61 [-3.89, +7.12] | 17/32 | 32/32 |
| g++ | 12,288 | 194.3 | 233.6 | 239.8 | -4.81 [-6.33, +1.29] | 7/16 | 16/16 |
| g++ | 16,384 | 254.9 | 291.9 | 286.4 | +5.46 [+0.66, +13.96] | 22/32 | 32/32 |
| g++ | 32,768 | 511.9 | 545.4 | 551.1 | -4.37 [-12.13, +5.00] | 15/32 | 32/32 |
| g++ | 65,536 | 1111.2 | 1148.7 | 1159.4 | -19.96 [-33.58, -1.73] | 4/16 | 16/16 |

[Initial paired trials](dev002-adaptive-horizon.csv) · [Follow-up paired trials](dev002-adaptive-horizon-extended.csv) · [Summary calculation](adaptive_horizon_summary.py)

## Kernel-only versus whole-pool efficiency

Run `python3 run.py --efficiency > samples.csv` on dev002. The runner prints thread-only and whole-pool efficiency together on stderr. Regenerate the table from saved samples with `python3 efficiency_summary.py samples.csv`. Both compilers and wait policies run 5,801 short passes with the caller participating. On 181 passes per run, timestamp the record loop on every participant as well as the entire pass from dispatch to observed completion. For each sampled pass take the longest individual kernel duration K, sum of kernel durations S, and whole-pass duration P. Aggregate by averaging within each run, then taking medians across seven measured rounds. Serial baselines are per compiler and wait policy. The boundaries use elapsed wall time, so descheduling inside a kernel counts against that kernel.

Kernel-only parallel efficiency = K₁/(N × K). It includes a slow slice's effect on completion. The ratio K/P shows the fraction of elapsed pass time retained after exposing start skew, dispatch and completion overhead. P−K is that exposed wall-time gap; synchronization overlapping a longer kernel is already hidden. Whole-pool efficiency = P₁/(N × P). To separate throughput from imbalance inside the kernel, K₁/S measures aggregate worker productivity and S/(N × K) measures balance. Their product gives kernel-only efficiency. These are scaling metrics; process CPU utilization measures something different.

| Compiler | Policy | Threads | Longest kernel µs | Whole pass µs | Gap µs | Kernel-only | K/P | Whole-pool | Productivity | Balance |
|---|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| clang++ | pool | 1 | 104.84 | 104.96 | 0.12 | 100.0% | 99.9% | 100.0% | 100.0% | 100.0% |
| clang++ | pool | 2 | 52.95 | 61.31 | 8.36 | 99.0% | 86.4% | 85.6% | 100.0% | 99.0% |
| clang++ | pool | 4 | 28.04 | 36.87 | 8.83 | 93.5% | 76.1% | 71.2% | 97.6% | 95.8% |
| clang++ | pool | 8 | 17.67 | 27.73 | 10.06 | 74.2% | 63.7% | 47.3% | 82.4% | 90.0% |
| clang++ | active | 1 | 104.07 | 104.17 | 0.10 | 100.0% | 99.9% | 100.0% | 100.0% | 100.0% |
| clang++ | active | 2 | 52.75 | 52.96 | 0.21 | 98.7% | 99.6% | 98.3% | 99.7% | 99.0% |
| clang++ | active | 4 | 27.49 | 27.69 | 0.20 | 94.7% | 99.3% | 94.1% | 97.8% | 96.8% |
| clang++ | active | 8 | 16.10 | 16.46 | 0.36 | 80.8% | 97.8% | 79.1% | 85.6% | 94.4% |
| g++ | pool | 1 | 103.91 | 104.02 | 0.11 | 100.0% | 99.9% | 100.0% | 100.0% | 100.0% |
| g++ | pool | 2 | 59.44 | 65.90 | 6.46 | 87.4% | 90.2% | 78.9% | 93.6% | 93.4% |
| g++ | pool | 4 | 28.53 | 37.53 | 9.00 | 91.1% | 76.0% | 69.3% | 94.6% | 96.3% |
| g++ | pool | 8 | 18.86 | 31.29 | 12.44 | 68.9% | 60.3% | 41.6% | 78.9% | 87.3% |
| g++ | active | 1 | 106.49 | 106.62 | 0.12 | 100.0% | 99.9% | 100.0% | 100.0% | 100.0% |
| g++ | active | 2 | 60.00 | 60.15 | 0.15 | 88.7% | 99.7% | 88.6% | 95.0% | 93.4% |
| g++ | active | 4 | 29.96 | 30.20 | 0.24 | 88.9% | 99.2% | 88.3% | 96.6% | 92.0% |
| g++ | active | 8 | 18.32 | 18.68 | 0.36 | 72.7% | 98.1% | 71.3% | 84.6% | 85.9% |

[Raw dev002 efficiency samples](dev002-efficiency.csv).

## Common-overlap throughput with two fixed-size vectors

Run `python3 two_vectors.py >samples.csv` on dev002 and `python3 two_vectors_summary.py samples.csv`. Each of eight synchronized passes reads one field from each of 65,536 128-byte input records and writes one 64-bit result per record into an independent output vector. Both vectors are allocated and initialized before launching the team. Each pinned participant retains the same disjoint contiguous output range and corresponding read-only input range. The light transform does one dependent shift/multiply/rotate iteration per record; the compute variant calibrates more iterations for about 600 ms serial time across all eight passes. Each process checks every final result after timing. This is native C++ using ranges assigned by the harness.

End-to-end time comes from independent trials with timing only around the entire job. In separate trials each worker timestamps progress every 32 records into a private preallocated buffer. For each pass, common overlap runs from latest entry to earliest finish. Blocks wholly inside the interval give a lower bound on records completed; including both partially intersecting boundary blocks gives an upper bound. Sum records and common intervals across passes before calculating records/second. The denominator for efficiency is N times the identically instrumented serial record rate. Coverage divides common time by time from first entry to last finish. These are elapsed intervals; OS preemption within a worker's loop is possible. The bounds express checkpoint resolution, not statistical confidence. Use seven measured medians per cell after a warm-up round; the timed intervals exclude vector preparation and pool setup/teardown.
| Work | Compiler | Threads | Total ms | Speedup | Overlap M records/s | Overlap efficiency | Common fraction of kernel window |
|---|---|---:|---:|---:|---:|---:|---:|
| compute | clang++ | 1 | 606.292 | 1.00× | 0.868–0.868 | 100.0–100.0% | 100.0% |
| compute | clang++ | 2 | 307.395 | 1.97× | 1.723–1.725 | 99.2–99.3% | 98.5% |
| compute | clang++ | 4 | 156.252 | 3.88× | 3.408–3.418 | 98.1–98.4% | 95.9% |
| compute | clang++ | 8 | 80.962 | 7.49× | 6.699–6.747 | 96.4–97.1% | 93.9% |
| compute | g++ | 1 | 606.677 | 1.00× | 0.864–0.864 | 100.0–100.0% | 100.0% |
| compute | g++ | 2 | 306.350 | 1.98× | 1.717–1.719 | 99.4–99.5% | 97.5% |
| compute | g++ | 4 | 156.603 | 3.87× | 3.436–3.446 | 99.5–99.7% | 97.0% |
| compute | g++ | 8 | 80.794 | 7.51× | 6.678–6.725 | 96.7–97.3% | 93.4% |
| light | clang++ | 1 | 2.549 | 1.00× | 389.309–389.309 | 100.0–100.0% | 100.0% |
| light | clang++ | 2 | 1.211 | 2.10× | 695.729–696.432 | 89.4–89.4% | 92.1% |
| light | clang++ | 4 | 0.799 | 3.19× | 604.809–607.237 | 38.8–39.0% | 89.9% |
| light | clang++ | 8 | 0.645 | 3.95× | 1018.958–1027.440 | 32.7–33.0% | 80.8% |
| light | g++ | 1 | 0.862 | 1.00× | 343.883–343.883 | 100.0–100.0% | 100.0% |
| light | g++ | 2 | 1.129 | 0.76× | 697.516–698.254 | 101.4–101.5% | 88.2% |
| light | g++ | 4 | 0.709 | 1.22× | 811.693–814.384 | 59.0–59.2% | 80.3% |
| light | g++ | 8 | 0.514 | 1.68× | 888.727–896.399 | 32.3–32.6% | 72.7% |

[Raw dev002 samples](dev002-two-vectors.csv).
