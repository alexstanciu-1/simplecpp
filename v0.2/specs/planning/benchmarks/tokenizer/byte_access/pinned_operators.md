# Paired cpp-flavor operator scans on dev002

The split-view, last-use move, in-slot scanner and handwritten baseline reused the slot experiment executables and compile flags. Both returned the complete PHP oracle dump before timing. Each executable received the same previous-count hint. All four ran on logical CPU 7 (SMT sibling 23), one process at a time. Pinning controls migration but does not reserve either hardware thread. Each compiler ran 24 adjacent pairs with alternating first scanner. Compiler order reversed every two pairs. Each run contains nine timed batches of 128 scans of 8,192 bytes and 2,560 tokens. The timed region includes result destruction.

Throughput is bytes divided by mean elapsed time within each run, summarized by the median and interquartile range over the 24 runs. Relative performance is the median of the 24 within-pair throughput ratios.

| Compiler | Scanner | Median MiB/s | Middle 50% MiB/s | Median ns/token |
|---|---|---:|---:|---:|
| clang++ | slot | 895.7 | 882.8–897.9 | 3.41 |
| clang++ | handwritten | 1487.0 | 1465.8–1504.0 | 2.05 |
| g++ | slot | 881.4 | 869.5–883.4 | 3.46 |
| g++ | handwritten | 1286.7 | 1266.4–1300.7 | 2.37 |

The median paired in-slot throughput was 60.0% of handwritten on Clang and 67.9% on GCC.

Hardware cycles/token, retired instructions/token and branch misses are unavailable for all four binaries. The dev002 benchmark login received EACCES from perf_event_open for each user-only event. Kernel perf_event_paranoid was 4 and the perf command was unavailable. Wall time cannot establish cycle counts or attribute the gap to branch prediction.

[Per-batch timings and executable hashes](pinned_operators.dev002.json) · [Runner](pinned_operators.py)
