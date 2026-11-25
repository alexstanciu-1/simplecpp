# Joined MT tests and dev002 benchmarks

Doc Status: supporting

Tested commit `adbb0dd1e` on 2026-11-24. Monitoring began at 12:35:11 UTC. Report generated at 13:35:33 UTC.

## Correctness

- 152 fresh `compiler_mt_segments_native` runs passed. Each compiled the four frontend fixtures and ran native checks with both sleep and active waiting. The suite covers scalar snapshots, balanced ranges, per-job and group caps, overlap rejection, vectors, hashes and worker failure.
- All ten adjacent compiler, collection, generator, and hash storage tests passed.
- On dev002 the repository native MT probe ran ten times per compiler and pool policy, 40 runs total. Each observed three simultaneous scans and two simultaneous sibling jobs with group cap five and scan cap three.
- A native differential test replayed 600,000 seeded four-job scenarios under each pool policy (1,200,000 batches in total) while deleting, replacing and reinserting hash keys. Against an independent insertion-order model, 855,490 succeeded, 247,480 rejected an overlap and 97,030 rejected bounds. Every rejection verified zero worker entries and unchanged values.
- The randomized run used Clang AddressSanitizer and UndefinedBehaviorSanitizer. Leak checking ran separately with Valgrind under both policies. Both Memcheck runs reported zero errors and zero bytes held at exit.
- Four simultaneous join callers reused the same read grant and injected worker errors. AddressSanitizer/UndefinedBehaviorSanitizer and Clang ThreadSanitizer each completed 20,000 joins over the two policies. Clang ThreadSanitizer also checked 200,000 randomized batches. GCC ThreadSanitizer failed during startup on both hosts with an unexpected memory mapping.

## Fixed-size scalar workloads

All timings are from dev002. Clang 18.1.3 and GCC 13.3.0 each built the emitted C++ and the production MT runtime with C++23 and O3. Processes were restricted to CPUs 0 through 7, one hardware thread from each physical core, with ordinary scheduler placement within that set. Timings include admission, dispatch and join. Initialization, pool startup and full-result verification run outside the timer. The scalar driver also checks partition counts inside the timed loop. Each benchmark process ran independently and compiler/policy/worker configurations were shuffled.

4,800 verified samples cover 65,536 live entries, eight passes and two intensities. The heavy pass applies 365 dependent arithmetic updates per entry. The light pass applies one. A sparse hash was built with 131,072 keys and every other key deleted. The scaling baseline in this table is the same one-worker joined implementation. Times are pooled medians in milliseconds per eight passes.

| Compiler | Pool | Layout | Steps | 1 worker | 2 | 4 | 8 | 8-worker speedup |
|---|---|---|---|---|---|---|---|---|
| clang++ | sleep | vector | 1 | 2.458 | 1.286 | 0.657 | 0.542 | 4.54× |
| clang++ | sleep | vector | 365 | 626.779 | 312.336 | 156.319 | 81.385 | 7.70× |
| clang++ | sleep | hash | 1 | 2.524 | 1.705 | 1.114 | 0.897 | 2.81× |
| clang++ | sleep | hash | 365 | 620.266 | 312.653 | 157.813 | 80.903 | 7.67× |
| clang++ | sleep | holes | 1 | 3.040 | 2.645 | 1.492 | 1.264 | 2.41× |
| clang++ | sleep | holes | 365 | 624.773 | 312.217 | 157.243 | 80.693 | 7.74× |
| clang++ | active | vector | 1 | 2.381 | 1.220 | 0.630 | 0.453 | 5.26× |
| clang++ | active | vector | 365 | 623.190 | 312.770 | 157.561 | 79.128 | 7.88× |
| clang++ | active | hash | 1 | 2.477 | 1.694 | 1.011 | 0.875 | 2.83× |
| clang++ | active | hash | 365 | 621.516 | 313.728 | 157.452 | 81.131 | 7.66× |
| clang++ | active | holes | 1 | 2.945 | 2.102 | 1.340 | 1.101 | 2.67× |
| clang++ | active | holes | 365 | 621.032 | 313.931 | 156.736 | 81.419 | 7.63× |
| g++ | sleep | vector | 1 | 2.936 | 1.176 | 0.660 | 0.543 | 5.40× |
| g++ | sleep | vector | 365 | 608.260 | 308.133 | 154.719 | 79.770 | 7.63× |
| g++ | sleep | hash | 1 | 2.565 | 1.643 | 0.957 | 0.807 | 3.18× |
| g++ | sleep | hash | 365 | 611.650 | 308.787 | 155.491 | 79.798 | 7.66× |
| g++ | sleep | holes | 1 | 2.695 | 1.960 | 1.226 | 0.961 | 2.81× |
| g++ | sleep | holes | 365 | 616.312 | 308.668 | 155.909 | 80.288 | 7.68× |
| g++ | active | vector | 1 | 2.153 | 1.157 | 0.733 | 0.396 | 5.44× |
| g++ | active | vector | 365 | 609.388 | 305.777 | 154.055 | 79.353 | 7.68× |
| g++ | active | hash | 1 | 2.463 | 1.599 | 0.896 | 0.708 | 3.48× |
| g++ | active | hash | 365 | 613.014 | 308.272 | 155.133 | 80.089 | 7.65× |
| g++ | active | holes | 1 | 2.723 | 1.776 | 1.038 | 0.901 | 3.02× |
| g++ | active | holes | 365 | 612.399 | 308.943 | 155.482 | 80.259 | 7.63× |

### Eight-worker variation

The machine was shared and workers could migrate within the CPU mask. Figures below are milliseconds per eight heavy passes. P90 uses the nearest-rank sample. The samples within one process share its pool and OS placement. These measurements establish timing variation, without identifying a cause.

| Compiler | Pool | Layout | Median | P90 | Maximum |
|---|---|---|---|---|---|
| clang++ | active | hash | 81.1 | 89.0 | 118.1 |
| clang++ | active | holes | 81.4 | 139.9 | 170.0 |
| clang++ | active | vector | 79.1 | 126.3 | 182.1 |
| clang++ | sleep | hash | 80.9 | 90.8 | 118.4 |
| clang++ | sleep | holes | 80.7 | 97.8 | 135.8 |
| clang++ | sleep | vector | 81.4 | 88.9 | 102.2 |
| g++ | active | hash | 80.1 | 114.1 | 161.0 |
| g++ | active | holes | 80.3 | 162.5 | 173.5 |
| g++ | active | vector | 79.4 | 82.5 | 129.3 |
| g++ | sleep | hash | 79.8 | 88.6 | 103.4 |
| g++ | sleep | holes | 80.3 | 91.0 | 120.7 |
| g++ | sleep | vector | 79.8 | 89.9 | 130.2 |

## Inventory records

2,700 samples. Eight passes over 65,536 128-byte stock records and their adjustment vector. The direct baseline calls the same compiled worker without the pool. The joined paths use the real runtime, consume per-worker subtotals and verify all rows after timing. Median milliseconds per eight passes.

| Compiler | Pool | Direct | 1 worker | 2 | 4 | 8 | 8 vs direct |
|---|---|---|---|---|---|---|---|
| clang++ | sleep | 6.796 | 6.804 | 4.247 | 2.195 | 1.790 | 3.80× |
| clang++ | active | 6.759 | 6.790 | 3.963 | 2.471 | 1.640 | 4.12× |
| g++ | sleep | 7.349 | 7.356 | 4.656 | 2.373 | 1.881 | 3.91× |
| g++ | active | 7.370 | 7.366 | 4.119 | 2.306 | 1.768 | 4.17× |

## Runtime-sized batches

2,880 samples. One increment per live entry, fixed total of 65,536 and a sleeping pool. Time covers one generated batch through return of the result vector. Entry counts and values were verified outside the timer. Median microseconds per batch.

| Compiler | Layout | Worker cap | 8 partitions | 64 | 256 | 1,024 |
|---|---|---|---|---|---|---|
| clang++ | vector | 1 | 201.2 | 205.2 | 229.5 | 524.4 |
| clang++ | vector | 8 | 54.2 | 55.2 | 80.5 | 378.9 |
| clang++ | hash | 1 | 249.2 | 277.3 | 307.6 | 646.3 |
| clang++ | hash | 8 | 81.7 | 88.1 | 118.2 | 450.4 |
| g++ | vector | 1 | 232.9 | 238.0 | 269.9 | 697.1 |
| g++ | vector | 8 | 61.3 | 63.7 | 96.3 | 524.4 |
| g++ | hash | 1 | 307.5 | 312.2 | 354.6 | 826.9 |
| g++ | hash | 8 | 86.1 | 95.5 | 132.6 | 595.8 |

The many-partition timings include grant construction, admission, scheduling, kernel work and result construction. The current admission implementation compares each nonempty interval with prior intervals. These end-to-end timings do not isolate the contribution of that comparison.

## Evidence

The accompanying archive contains raw timings, focused logs, benchmark inputs and drivers. WSL2 supplied correctness results only. No timing evidence from WSL2 is pooled with dev002.
