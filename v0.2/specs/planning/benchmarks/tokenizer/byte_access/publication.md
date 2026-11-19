# Token publication with the split checked byte view

Doc Status: supporting

The three generated variants have identical recognition, split byte view, source retention and previous-token-count reserve. Copy and move accumulate tokens in a private local. Move transfers its buffer at the last use. Direct reserves and appends through a reference to the result field. On a lexical error direct resets the partially built field to an empty vector before returning. The handwritten control scans its retained std::string and appends to its embedded std::vector. All use the same input, hint and set of flavor keywords.

## Throughput

Each group is **copy / move / direct**, as percentages of matched handwritten C++ throughput (100%). The handwritten column gives the absolute baseline in MiB/s in identifiers / operators / mixed order. Samples include result destruction. Each median is from 27 batches of 128 scans, run in randomized groups of nine batches on dev002 with C++23, -O2, separate translation units and no LTO.

| Toolchain | Flavor | Identifiers | Operators | Mixed | Handwritten MiB/s |
|---|---|---:|---:|---:|---:|
| clang++ | cpp | 55.3 / 75.1 / 83.9 | 24.8 / 22.9 / 27.6 | 42.3 / 48.2 / 48.0 | 853 / 1229 / 823 |
| clang++ | php | 41.8 / 54.8 / 63.2 | 23.6 / 27.8 / 22.5 | 39.4 / 44.3 / 43.3 | 1127 / 1250 / 899 |
| clang++ | js | 59.9 / 77.1 / 80.5 | 25.1 / 23.0 / 27.7 | 37.7 / 42.3 / 42.4 | 790 / 1222 / 930 |
| g++ | cpp | 42.2 / 68.8 / 62.3 | 51.9 / 70.8 / 67.3 | 51.8 / 58.7 / 60.8 | 1424 / 1027 / 921 |
| g++ | php | 50.8 / 72.9 / 66.3 | 53.5 / 67.3 / 80.0 | 63.9 / 71.0 / 59.5 | 1178 / 1023 / 961 |
| g++ | js | 52.7 / 76.2 / 88.6 | 59.6 / 86.8 / 85.6 | 65.9 / 78.0 / 76.5 | 1154 / 1050 / 925 |

Median of the six within-build throughput ratios, across cpp/php/js and both compilers.

| Corpus | Move / copy | Direct / copy | Direct / move | Move / handwritten | Direct / handwritten |
|---|---:|---:|---:|---:|---:|
| identifiers | 1.396× | 1.494× | 1.081× | 0.740× | 0.734× |
| operators | 1.217× | 1.205× | 1.088× | 0.476× | 0.475× |
| mixed | 1.128× | 1.130× | 0.989× | 0.534× | 0.538× |

## Allocation and compilation

Separate instrumented binaries recorded allocations outside timing. On the mixed input (20,480 bytes, 5,120 tokens), all six pairs agreed.

| Path | Allocation calls | Bytes requested | Publication allocations |
|---|---:|---:|---:|
| copy | 8 | 286,858 | 2 |
| move | 6 | 163,954 | 0 |
| direct | 6 | 163,954 | 0 |
| handwritten | 2 | 143,385 | 0 |

Scanner-only compile wall time in milliseconds (median of three invocations, fixed variant order). These figures exclude the driver, runtime and linker.

| Compiler | Flavor | Copy | Move | Direct | Handwritten |
|---|---|---:|---:|---:|---:|
| clang++ | cpp | 308 | 311 | 309 | 378 |
| clang++ | php | 312 | 306 | 307 | 390 |
| clang++ | js | 317 | 314 | 316 | 377 |
| g++ | cpp | 266 | 258 | 272 | 294 |
| g++ | php | 260 | 251 | 254 | 294 |
| g++ | js | 256 | 254 | 256 | 302 |

## Failure and recovery

All 24 clean binaries checked the complete PHP oracle outputs on 11 fixtures and the edit/recovery sequence. A second edit sequence introduced an unsupported byte after several valid tokens. The driver rejects a failed result with any published tokens and compares the diagnostic offset and reason, checks recovery against the oracle and rechecks retained results. These are lexical-error tests. Allocation-failure injection was outside the experiment.

Reproduce with `python3 -B measure.py --publication FIXTURE_ROOT RUNTIME WORK`. [Raw dev002 samples, compiler versions and allocation events](publication.dev002.json).
