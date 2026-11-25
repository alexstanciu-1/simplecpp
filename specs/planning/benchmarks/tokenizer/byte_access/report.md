# Presized tokenizer byte access and publication

Doc Status: supporting

The source scanners in cpp/php/js were emitted by the PHP compiler. The experiment changes copies of those emitted units on tf-dev002. All variants receive token counts from the same previous revision and retain the original recognition loop. The handwritten scanner is rebuilt and timed alongside them.

| Access mode | Per-byte operation |
|---|---|
| Runtime | Existing out-of-line string byte_at, which recovers a span and checks its index. |
| Inline checked | Cache the source pointer and byte length once per scan. An inline read checks the signed index before dereferencing. |
| Direct | Same cached pointer and length, with only the scanner’s existing loop and keyword-length guards. |

Each mode is measured with final vector copy assignment and with last-use move assignment. Source-string ownership stays the same in all six generated variants. The inline-checked/direct pair isolates the explicit range guard within that representation, including checks the native optimizer can eliminate. Runtime/inline-checked includes the call boundary and repeated span access together.

## Paired throughput ratios

Median of six within-corpus ratios, one for each flavor/compiler pair. A value above 1 means the change increased throughput. The precise baseline of each comparison is given in the column label.

| Corpus | Inline checked / runtime (copy) | Direct / inline checked (copy) | Move / copy (runtime) | Move / copy (inline checked) | Move / copy (direct) | Runtime + copy / handwritten | Inline checked + move / handwritten | Direct + move / handwritten |
|---|---:|---:|---:|---:|---:|---:|---:|---:|
| identifiers | 2.02× | 1.00× | 1.23× | 1.43× | 1.32× | 0.24× | 0.72× | 0.69× |
| operators | 1.49× | 1.02× | 1.23× | 1.19× | 1.17× | 0.23× | 0.47× | 0.49× |
| mixed | 1.91× | 1.00× | 1.08× | 1.12× | 1.12× | 0.29× | 0.58× | 0.58× |

## Fraction of elapsed-time gap closed

For each matched binary pair, divide time saved per scan by the original runtime-copy time minus handwritten time. The table gives medians across the six pairs. These interventions interact, so the separate percentages do not add.

| Corpus | Cached checked reads alone | Final move alone | Cached checked reads and final move |
|---|---:|---:|---:|
| identifiers | 69% | 26% | 88% |
| operators | 42% | 24% | 61% |
| mixed | 63% | 10% | 71% |

## Allocation sites

A separate executable marks scanner phases and records the phase and requested size at each C++ heap allocation in a fixed array. All six flavor/compiler pairs agreed. The following are the individual allocation sizes in bytes for the mixed corpus (20,480 input bytes, 5,120 tokens, previous count 5,121).

| Configuration | Input argument | Result source | Reserve tokens | Publish tokens | Total calls | Total requested bytes |
|---|---:|---:|---:|---:|---:|---:|
| runtime_copy | 32, 20,481 | 32, 20,481 | 24, 122,904 | 24, 122,880 | 8 | 286,858 |
| runtime_move | 32, 20,481 | 32, 20,481 | 24, 122,904 | — | 6 | 163,954 |
| handwritten | 20,481 | — | 122,904 | — | 2 | 143,385 |

A heap-backed scpp string copy allocates a storage object and its byte buffer. The scanner takes a string by value and assigns it to the result, making two copies. Its vector likewise allocates a storage object and element backing during reserve, then allocates both again on copy publication. The handwritten std::string copy uses one byte allocation and transfers it to the result. Its embedded std::vector needs one backing allocation. Byte access itself allocates zero bytes during recognition.

## Native throughput (MiB/s)

| Compiler | Flavor | Input | Runtime copy | Runtime move | Inline checked copy | Inline checked move | Direct copy | Direct move | Handwritten |
|---|---|---|---:|---:|---:|---:|---:|---:|---:|
| clang++ | cpp | identifiers | 253.2 | 310.9 | 488.2 | 642.8 | 489.5 | 631.4 | 816.0 |
| clang++ | cpp | operators | 223.8 | 286.0 | 257.5 | 334.4 | 265.5 | 297.0 | 1240.9 |
| clang++ | cpp | mixed | 232.3 | 251.4 | 356.2 | 408.8 | 371.4 | 414.7 | 752.8 |
| clang++ | php | identifiers | 279.0 | 311.9 | 481.1 | 703.1 | 474.0 | 716.4 | 1360.3 |
| clang++ | php | operators | 210.7 | 287.8 | 250.8 | 284.6 | 252.2 | 291.5 | 1015.2 |
| clang++ | php | mixed | 268.6 | 266.2 | 363.4 | 398.8 | 360.7 | 406.1 | 928.4 |
| clang++ | js | identifiers | 269.9 | 338.5 | 480.8 | 573.8 | 483.0 | 634.6 | 1007.7 |
| clang++ | js | operators | 262.6 | 280.6 | 311.9 | 354.6 | 311.0 | 366.9 | 1247.3 |
| clang++ | js | mixed | 259.3 | 278.5 | 362.9 | 405.9 | 366.6 | 411.3 | 840.5 |
| g++ | cpp | identifiers | 277.9 | 308.8 | 584.2 | 875.3 | 646.5 | 863.5 | 1193.8 |
| g++ | cpp | operators | 262.9 | 285.6 | 616.2 | 698.7 | 578.4 | 749.6 | 1071.4 |
| g++ | cpp | mixed | 260.0 | 256.7 | 612.7 | 727.8 | 560.6 | 608.0 | 940.9 |
| g++ | php | identifiers | 257.0 | 356.4 | 582.0 | 869.0 | 634.3 | 786.8 | 1213.9 |
| g++ | php | operators | 261.1 | 353.7 | 561.5 | 697.7 | 586.9 | 747.2 | 1051.3 |
| g++ | php | mixed | 249.5 | 296.1 | 570.1 | 602.9 | 666.1 | 599.8 | 973.9 |
| g++ | js | identifiers | 280.8 | 344.4 | 645.9 | 905.3 | 630.8 | 854.6 | 1182.5 |
| g++ | js | operators | 260.9 | 308.3 | 465.5 | 619.2 | 581.0 | 594.8 | 862.4 |
| g++ | js | mixed | 269.5 | 293.1 | 627.1 | 708.1 | 612.5 | 743.2 | 939.2 |

## Verification and reproduction

All 42 binaries compare complete token outputs or lexical diagnostics for 11 fixtures against PHP. Each checks valid edits, failure and recovery while retaining the original and sibling results. For timing, the previous revision contains one additional identifier. Each median uses 27 batches of 128 in-memory scans, with preloaded bytes and shuffled executable order across three repetitions. Timing includes result destruction. Allocation hooks and phase markers appear only in the separate trace executables. The JSON contains individual batch durations and allocation events for every corpus.

Compiler versions: Ubuntu clang version 18.1.3 (1ubuntu1), g++ (Ubuntu 13.3.0-6ubuntu2~24.04.1) 13.3.0. C++23, O2, g0, libstdc++, separate translation units, no LTO. These warm-cache repeated corpora measure a bounded scanner.

```sh
python3 -B compiler/tests/tokenizer_scanner_native.py /tmp/lexer-fixtures
python3 specs/planning/benchmarks/tokenizer/byte_access/measure.py /tmp/lexer-fixtures runtime /tmp/byte-proof
```

[Raw samples](results.dev002.json)
