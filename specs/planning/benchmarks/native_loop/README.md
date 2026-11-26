# Thread pool measurements on dev002

Doc Status: supporting

The full experiment and runners are preserved in commit `8b8a194ce` under `v0.2/specs/planning/benchmarks/native_loop/`. [Performance report](../../multithreading_performance.md)

## Adaptive-placement break-even on dev002

The eight active-pool participants and CPU 19 load kernel are identical to the delayed-load test. For each compiler, slice rotation and block, the onset pass is taken from the prior fixed/load trial in dev002-adaptive.csv. The benchmark appends an exact number of passes after that trigger and keeps the load running through completion. It does not communicate the onset to the placement detector. Detection, affinity changes, migration and subsequent working-set warm-up are timed. Setup and shutdown are excluded. Every process validates the final record state; the runner also checks the checksum within every matched pair.

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

[Initial paired trials](dev002-adaptive-horizon.csv) · [Follow-up paired trials](dev002-adaptive-horizon-extended.csv)

## Kernel-only versus whole-pool efficiency

Both compilers and wait policies run 5,801 short passes with the caller participating. On 181 passes per run, timestamp the record loop on every participant as well as the entire pass from dispatch to observed completion. For each sampled pass take the longest individual kernel duration K, sum of kernel durations S, and whole-pass duration P. Aggregate by averaging within each run, then taking medians across seven measured rounds. Serial baselines are per compiler and wait policy. The boundaries use elapsed wall time, so descheduling inside a kernel counts against that kernel.

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
