# Transferring the private scanner input into the result

The cpp-flavor scanner accepts a string by value. The control copies that private input into the result and acquires a split checked byte view on a separate local owner. The prototype moves the private input into the result and acquires the descriptor from the result field. Both retain the previous-count reserve, direct token slot construction and last-use vector move. The caller still keeps its original input.

## Pinned operator benchmark

dev002 CPU 7, 24 adjacent pairs per compiler with alternating first scanner. The SMT sibling was available to other work. Each run averaged nine batches of 128 scans of the same 8,192-byte input with 2,560 tokens. MiB/s values are per-variant medians, and ratios and ranges are computed within pairs.

| Compiler | Copy MiB/s | Transfer MiB/s | Median transfer / copy | Middle 50% of paired ratios |
|---|---:|---:|---:|---:|
| clang++ | 905.4 | 1166.8 | 1.290× | 1.284–1.297× |
| g++ | 907.1 | 1108.1 | 1.225× | 1.214–1.236× |

On both compilers, separate allocation traces showed 6 calls requesting 77,938 bytes for copy and 4 requesting 69,713 bytes for transfer. Transfer removed exactly the 32-byte result-string storage object and its 8,193-byte byte buffer. The timings measure the full change in ownership and view acquisition. They do not assign the entire speedup to allocator time.

Both transfer builds matched the PHP oracle on 11 fixtures and passed edit, failure and recovery checks. A separate ownership check compared every retained source byte after replacing the caller input and scanning a sibling, including on error returns. The experiment is specific to a private by-value parameter and a result field that stays stable while the descriptor is used.

[Raw samples and allocation events](source_transfer.dev002.json) · [Benchmark runner](source_transfer.py)

## Direct comparison with handwritten C++

The split-view scanner with in-slot append, last-use vector move and source transfer ran directly against the handwritten scanner for each flavor on dev002 CPU 7. Both binaries used the same compiler, operator bytes and previous-count hint. Each of 24 adjacent pairs alternated which executable ran first. All builds finished before timing. Each run averages nine batches of 128 scans, including destruction. The SMT sibling was available to other work. Both implementations passed the flavor-specific PHP oracle and edit checks. The six transfer builds also passed retained-source checks.

| Compiler | Flavor | Transfer MiB/s | Handwritten MiB/s | Paired throughput | Throughput gap | Middle 50% of paired throughput | Extra ns/token |
|---|---|---:|---:|---:|---:|---:|---:|
| clang++ | cpp | 1173.8 | 1547.5 | 75.8% | 24.2% | 75.3%–76.3% | 0.63 |
| clang++ | php | 1171.2 | 1586.0 | 74.1% | 25.9% | 73.8%–74.5% | 0.67 |
| clang++ | js | 1182.0 | 1561.3 | 75.8% | 24.2% | 75.2%–76.7% | 0.62 |
| g++ | cpp | 1118.4 | 1329.6 | 83.8% | 16.2% | 83.0%–84.6% | 0.44 |
| g++ | php | 1225.2 | 1337.6 | 91.6% | 8.4% | 91.2%–92.8% | 0.21 |
| g++ | js | 1238.4 | 1337.3 | 92.8% | 7.2% | 91.6%–94.9% | 0.18 |

The gap is one minus the median within-pair throughput ratio. Extra ns/token is the median within-pair elapsed-time difference divided by 2,560 tokens. The middle 50% describes observed pairs, not a confidence interval. Results apply to the warm 8 KiB operator corpus.

Reproduce with `source_transfer.py FIXTURES RUNTIME SLOT_WORK --cpu 7 --handwritten`. [Raw paired runs and binary hashes](transfer_handwritten.dev002.json).
