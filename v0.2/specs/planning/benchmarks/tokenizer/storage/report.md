# Token storage on dev002

Doc Status: supporting

The four configurations use the same emitted recognition loop. Copy is the existing final vector assignment. Reserve supplies the preceding successful scan’s token count to the vector’s existing checked storage growth. Move invokes the existing nonthrowing move assignment at the last use of the private local. The source snapshot and all byte reads retain their original value and bounds checks.

The experiment builds private copies of the generated scanner and vector headers. These measurements evaluate candidate lowering and runtime behavior before adding a language API. A production reserve binding would need metadata and a defined error policy. A compiler move would require proof of last use and absence of observable aliases.

## Median scan throughput

MiB/s on repeated in-memory inputs. The last three columns give ratios over copy with geometric growth in the same compiler, flavor and corpus. Higher is faster. Each median contains 27 batches of 128 scans. Three process-level repetitions shuffled variant order within each group. Raw samples include batch spread.

| Compiler | Flavor | Input | Copy | Reserve + copy | Move | Reserve + move | Reserve only | Move only | Both |
|---|---|---|---:|---:|---:|---:|---:|---:|---:|
| clang++ | cpp | identifiers | 240.9 | 251.3 | 278.3 | 334.5 | 1.04× | 1.16× | 1.39× |
| clang++ | cpp | operators | 130.9 | 229.5 | 211.2 | 229.4 | 1.75× | 1.61× | 1.75× |
| clang++ | cpp | mixed | 121.7 | 228.9 | 125.0 | 269.2 | 1.88× | 1.03× | 2.21× |
| clang++ | php | identifiers | 251.1 | 276.9 | 282.7 | 309.2 | 1.10× | 1.13× | 1.23× |
| clang++ | php | operators | 123.8 | 255.3 | 175.1 | 239.3 | 2.06× | 1.41× | 1.93× |
| clang++ | php | mixed | 128.5 | 263.5 | 129.2 | 261.2 | 2.05× | 1.01× | 2.03× |
| clang++ | js | identifiers | 261.4 | 274.3 | 307.0 | 301.4 | 1.05× | 1.17× | 1.15× |
| clang++ | js | operators | 131.8 | 258.1 | 214.8 | 276.8 | 1.96× | 1.63× | 2.10× |
| clang++ | js | mixed | 128.5 | 257.5 | 133.3 | 249.4 | 2.00× | 1.04× | 1.94× |
| g++ | cpp | identifiers | 243.2 | 286.4 | 304.9 | 345.6 | 1.18× | 1.25× | 1.42× |
| g++ | cpp | operators | 144.8 | 341.8 | 230.5 | 308.2 | 2.36× | 1.59× | 2.13× |
| g++ | cpp | mixed | 126.4 | 268.5 | 136.1 | 290.5 | 2.12× | 1.08× | 2.30× |
| g++ | php | identifiers | 251.9 | 272.6 | 278.3 | 335.9 | 1.08× | 1.10× | 1.33× |
| g++ | php | operators | 145.8 | 306.8 | 230.1 | 293.9 | 2.10× | 1.58× | 2.02× |
| g++ | php | mixed | 131.9 | 256.6 | 134.0 | 285.4 | 1.94× | 1.02× | 2.16× |
| g++ | js | identifiers | 268.9 | 268.9 | 311.5 | 333.1 | 1.00× | 1.16× | 1.24× |
| g++ | js | operators | 152.9 | 310.8 | 264.9 | 347.1 | 2.03× | 1.73× | 2.27× |
| g++ | js | mixed | 133.6 | 247.9 | 137.3 | 270.7 | 1.86× | 1.03× | 2.03× |

## Heap traffic

Entries show allocation calls / total requested bytes per successful scan. Counts come from separate instrumented executables and exclude fixture loading and the scan supplying the hint.

| Input | Copy | Reserve + copy | Move | Reserve + move |
|---|---:|---:|---:|---:|
| identifiers | 18 / 315,410 | 8 / 192,650 | 16 / 241,658 | 6 / 118,898 |
| operators | 18 / 274,450 | 8 / 139,402 | 16 / 212,986 | 6 / 77,938 |
| mixed | 19 / 557,074 | 8 / 286,858 | 17 / 434,170 | 6 / 163,954 |

## Method and verification

Host tf-dev002. Ubuntu clang version 18.1.3 (1ubuntu1); g++ (Ubuntu 13.3.0-6ubuntu2~24.04.1) 13.3.0. C++23, O2, g0, default linker/libstdc++, one compilation at a time, no LTO/PCH. Each variant links against the same driver and prebuilt runtime for that compiler and flavor. Source compile times in the raw data are single build observations.

The timed previous revision has one additional trailing identifier. Correctness runs include zero, low and high hints. All variants compare complete token dumps (including slices) and lexical diagnostics to the PHP oracle, and require an empty published token list on failure. The edit sequence retains original and sibling results, updates the hint after success, keeps the last successful count through failure and checks restored tokens. The separate resident compiler proof checks fresh equivalence and sibling token-list identity across source edits.

The allocator and repeated short corpora are part of these results. The measurements isolate the two storage changes within each row. They do not measure random cold inputs, peak RSS or the performance of a full tokenizer.

## Reproduce

Run in a fresh directory on the benchmark host with matching compiler-generated runtime files.

```sh
python3 -B v0.2/compiler/tests/tokenizer_scanner_native.py /tmp/token-storage-fixtures
python3 v0.2/specs/planning/benchmarks/tokenizer/storage/measure.py /tmp/token-storage-fixtures v0.2/runtime /tmp/token-storage-run
```

[Raw measurements](results.dev002.json) · [Original handwritten comparison](../report.md)
