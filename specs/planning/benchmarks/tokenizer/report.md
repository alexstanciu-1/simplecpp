# First tokenizer proof on dev002

Doc Status: supporting

Measured on tf-dev002 using Ubuntu clang version 18.1.3 (1ubuntu1) and g++ (Ubuntu 13.3.0-6ubuntu2~24.04.1) 13.3.0.

The PHP lexer supplied every expected token ID, byte offset, length, spelling and first invalid-byte diagnostic. Both native compilers passed the eleven inputs per flavor for generated and handwritten scanners. Each executable also scanned valid, edited, invalid and restored bytes in one process while retaining the original and a sibling result.

Each scanner receives preloaded bytes by value and returns owned source bytes and an ordered vector of three 64-bit integers (24 bytes per token). The handwritten scanner moves its input, compares keyword spans with a local string view and appends directly to the result. Generated C++ uses checked byte reads, copies the input to the result and assigns the completed local vector by value. Both cold policies start token backing at four slots on these nonempty inputs and grow geometrically. The handwritten prior-count run reserves the actual count from a previous revision with one extra identifier. The published application-vector API provides append without a reserve binding.

## Scan throughput and allocation cost

Rates are the median of nine batches of 128 in-memory scans after warmup. Brackets give the observed minimum and maximum MiB/s. Scanning includes result destruction. File I/O, validation dumps and printing are outside the interval. A separate executable counts requested C++ heap allocations and bytes for one scan. These are warm-cache, single-thread measurements. Identifiers, operators and mixed inputs repeat fixed patterns 512 times.

| Compiler | Flavor | Corpus (bytes / tokens) | Generated MiB/s [range] | C++ MiB/s [range] | Generated / C++ | Mtokens/s gen / C++ | Allocations gen / C++ | Bytes allocated gen / C++ | C++ prior MiB/s / allocations |
|---|---|---|---:|---:|---:|---:|---:|---:|---:|
| clang | cpp | identifiers (22528 / 3072) | 209.7 [172–212] | 503.4 [406–513] | 0.42 | 30.0 / 72.0 | 18 / 12 | 315410 / 219041 | 785.4 / 2 |
| clang | cpp | operators (8192 / 2560) | 132.8 [130–138] | 220.4 [217–263] | 0.60 | 43.5 / 72.2 | 18 / 12 | 274450 / 204705 | 1238.4 / 2 |
| clang | cpp | mixed (20480 / 5120) | 172.0 [102–173] | 132.9 [108–134] | 1.29 | 45.1 / 34.8 | 19 / 13 | 557074 / 413601 | 729.7 / 2 |
| clang | php | identifiers (22528 / 3072) | 208.5 [166–215] | 556.5 [454–582] | 0.37 | 29.8 / 79.6 | 18 / 12 | 315410 / 219041 | 1124.6 / 2 |
| clang | php | operators (8192 / 2560) | 129.3 [107–133] | 239.9 [236–279] | 0.54 | 42.4 / 78.6 | 18 / 12 | 274450 / 204705 | 1246.2 / 2 |
| clang | php | mixed (20480 / 5120) | 174.5 [123–176] | 139.1 [138–142] | 1.25 | 45.7 / 36.5 | 19 / 13 | 557074 / 413601 | 1138.2 / 2 |
| clang | js | identifiers (22528 / 3072) | 194.2 [161–197] | 501.4 [408–513] | 0.39 | 27.8 / 71.7 | 18 / 12 | 315410 / 219041 | 792.6 / 2 |
| clang | js | operators (8192 / 2560) | 129.7 [105–132] | 228.6 [222–282] | 0.57 | 42.5 / 74.9 | 18 / 12 | 274450 / 204705 | 1533.0 / 2 |
| clang | js | mixed (20480 / 5120) | 162.0 [98–163] | 135.4 [128–136] | 1.20 | 42.5 / 35.5 | 19 / 13 | 557074 / 413601 | 800.2 / 2 |
| GCC | cpp | identifiers (22528 / 3072) | 198.0 [197–202] | 566.6 [450–578] | 0.35 | 28.3 / 81.0 | 18 / 12 | 315410 / 219041 | 1178.8 / 2 |
| GCC | cpp | operators (8192 / 2560) | 144.1 [119–147] | 226.4 [222–271] | 0.64 | 47.2 / 74.2 | 18 / 12 | 274450 / 204705 | 1068.7 / 2 |
| GCC | cpp | mixed (20480 / 5120) | 165.7 [122–166] | 138.8 [117–140] | 1.19 | 43.4 / 36.4 | 19 / 13 | 557074 / 413601 | 1155.3 / 2 |
| GCC | php | identifiers (22528 / 3072) | 200.5 [173–202] | 542.8 [442–567] | 0.37 | 28.7 / 77.6 | 18 / 12 | 315410 / 219041 | 1235.3 / 2 |
| GCC | php | operators (8192 / 2560) | 145.6 [140–154] | 221.4 [216–259] | 0.66 | 47.7 / 72.6 | 18 / 12 | 274450 / 204705 | 1065.1 / 2 |
| GCC | php | mixed (20480 / 5120) | 171.5 [124–172] | 136.1 [112–136] | 1.26 | 45.0 / 35.7 | 19 / 13 | 557074 / 413601 | 958.1 / 2 |
| GCC | js | identifiers (22528 / 3072) | 210.9 [209–216] | 576.6 [556–600] | 0.37 | 30.2 / 82.4 | 18 / 12 | 315410 / 219041 | 1193.9 / 2 |
| GCC | js | operators (8192 / 2560) | 150.7 [124–155] | 215.0 [178–235] | 0.70 | 49.4 / 70.5 | 18 / 12 | 274450 / 204705 | 1315.0 / 2 |
| GCC | js | mixed (20480 / 5120) | 176.5 [104–177] | 138.7 [137–144] | 1.27 | 46.3 / 36.4 | 19 / 13 | 557074 / 413601 | 937.0 / 2 |

## Build costs

C++23, O2, g0, libstdc++, default linker, sequential builds, no LTO/PCH/ccache. Scanner unit compilation and executable link are measured three times each. Each link uses a prebuilt driver and generated scanners also link a prebuilt runtime object. Native wall is the median paired sum. Scanner bytes count the translation unit alone. Clang frontend/backend milliseconds come from a separate instrumented build. Raw traces include nested optimizer/codegen subtotals in the JSON.

| Compiler | Flavor | Scanner | C++ bytes | Compile ms | Link ms | Native wall ms | Clang frontend / backend ms |
|---|---|---|---:|---:|---:|---:|---:|
| Clang | cpp | handwritten | 1404 | 372.8 | 60.7 | 433.1 | 323.2 / 49.7 |
| Clang | cpp | generated | 5846 | 301.6 | 57.9 | 361.7 | 253.9 / 35.7 |
| Clang | php | handwritten | 1404 | 372.1 | 55.7 | 428.5 | 319.3 / 49.3 |
| Clang | php | generated | 4915 | 301.4 | 55.4 | 355.1 | 249.3 / 33.4 |
| Clang | js | handwritten | 1404 | 389.6 | 57.2 | 446.2 | 330.1 / 50.9 |
| Clang | js | generated | 5623 | 299.6 | 56.7 | 358.4 | 271.7 / 35.3 |
| GCC | cpp | handwritten | 1404 | 284.7 | 39.8 | 324.4 | — |
| GCC | cpp | generated | 5846 | 241.4 | 43.8 | 287.4 | — |
| GCC | php | handwritten | 1404 | 289.0 | 40.9 | 330.1 | — |
| GCC | php | generated | 4915 | 245.3 | 46.2 | 290.8 | — |
| GCC | js | handwritten | 1404 | 285.4 | 41.2 | 326.6 | — |
| GCC | js | generated | 5623 | 244.8 | 48.2 | 291.5 | — |

PHP 8.5 medians from five fresh processes on dev002 (prepared two source units, then emitted C++ in memory, process startup excluded).

| Flavor | Prepare ms | Emit ms |
|---|---:|---:|
| cpp | 2.79 | 1.21 |
| php | 2.40 | 1.13 |
| js | 2.75 | 1.19 |

## What the generated code shows

Inside identifier recognition, every input byte goes through the runtime byte_at call. Length-matched keywords reread the candidate bytes through that same API. The scanner constructs no substring there. At publication, the assignment from the local application vector into the result invokes the generated vector copy assignment. Its storage copy and the owned string copies contribute to the allocation totals. Redundant scalar casts are visible in the emitted C++, but source inspection alone cannot attribute execution time to them.

The workload split makes identifier scanning the first candidate for an isolated comparison. Hold the vector/result path fixed and vary only spelling comparison or byte access. Then hold scanning fixed and measure token publication separately. The current measurements cover the combined scanner.

## Reproduce

From the checkout root, use a fresh output directory. The first command emits the three Simple C++ fixtures, exercises incremental compiler recovery, and writes PHP oracle data. The second runs the native proofs and benchmark. On a separate host transfer the fixture directory, benchmark directory, compiler (including generated bindings), the API generator PHP sources and templates, and matching runtime sources, metadata and generated headers.

```sh
python3 -B compiler/tests/tokenizer_scanner_native.py /tmp/tokenizer-fixtures
PHP=php8.5 XDEBUG_MODE=off python3 specs/planning/benchmarks/tokenizer/run.py /tmp/tokenizer-fixtures runtime
```

[Raw samples](results.dev002.json)
