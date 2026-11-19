# Checked tokenizer byte access on dev002

Doc Status: supporting

Clang 18.1.3 and GCC 13.3.0, C++23, -O2, separate translation units without LTO. All three variants use the previous revision’s token count for reserve and copy the final vector. Current and inline compile the same generated scanner. The inline overlay exposes the existing byte_at body and its representation access to the optimizer. Cached captures the byte pointer and length once per scan and checks each index. Its invalid-index exception text differs from production.

## Throughput relative to current

Each cell gives **inline / cached** throughput multipliers against the current out-of-line accessor. Higher is faster. Each measurement uses the median of 45 batches of 128 scans, with variant order shuffled between groups of nine batches. Inputs and reserve hints match within each comparison.

| Compiler | Flavor | Identifiers | Operators | Mixed | Scanner compile ms (current / inline / cached) |
|---|---|---:|---:|---:|---:|
| clang++ | cpp | 1.64 / 1.89 | 0.98 / 1.04 | 1.46 / 1.63 | 308 / 587 / 303 |
| clang++ | php | 1.46 / 1.71 | 1.09 / 0.99 | 1.28 / 1.42 | 297 / 573 / 317 |
| clang++ | js | 1.50 / 1.73 | 1.05 / 1.19 | 1.31 / 1.46 | 297 / 588 / 305 |
| g++ | cpp | 1.80 / 2.16 | 1.63 / 2.12 | 1.78 / 2.29 | 246 / 491 / 255 |
| g++ | php | 2.18 / 2.30 | 1.77 / 2.29 | 2.04 / 2.38 | 241 / 482 / 245 |
| g++ | js | 1.80 / 2.00 | 1.70 / 2.17 | 1.78 / 2.19 | 243 / 490 / 254 |

Inline wins over current on 17 of 18 inputs. Caching wins over inline on 17 of 18. Clang operator results are sensitive to flavor. The Clang cpp operator result for inline is close to current at 0.98×. The overlay roughly doubles scanner translation-unit compile time (median of three compiler invocations, fixed mode order). It exposes private runtime definitions and their header dependencies, so that cost is specific to this prototype.

All 18 executables matched the PHP oracle byte-for-byte on 11 fixtures and passed the edit, failure, restoration and retained-result checks. Scanner objects had 15, 11 and 14 byte_at relocations for current cpp, php and js respectively under both compilers. Both alternatives had zero. This establishes removal of the external call on these builds. It does not establish how often the optimizer recomputed the view.

Reproduce with `python3 -B inline_accessor.py FIXTURE_ROOT RUNTIME WORK` using the emitted units and oracle inputs from the presized tokenizer benchmark. [Raw timings and compiler versions](inline_accessor.dev002.json).
