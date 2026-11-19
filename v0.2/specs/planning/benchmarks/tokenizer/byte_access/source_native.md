# Table-based scanner versus handwritten C++ on whole source files

Doc Status: supporting

The table scanner retains the proven cursor, checked split view, source transfer, direct token-slot construction, last-use move and original short-circuit equals lookahead. The handwritten comparison uses its existing range predicates, unsigned cursor, std::string_view, retained std::string and std::vector<Token>. Both use the same flavor keyword vocabulary and benchmark-only extra lexical rules. Handwritten helper reads use guarded std::string_view indexing. GCC 13 expresses the same proven cursor invariant with __builtin_unreachable. This is an end-to-end comparison of two implementations; the earlier table-versus-predicate experiment isolates classification.

All nine files matched their previous SHA-256 hashes. All 12 binaries passed the 11 oracle fixtures and edit/failure/recovery checks. For each file, the full stream of token kinds, byte offsets, lengths and spellings matched the production PHP tokenizer on both compilers and both implementations.

## Pinned Clang and GCC results

dev002 CPU 7, -O2, separate translation units without LTO. After warmup, each file/compiler had 32 adjacent pairs, alternating the first executable. Each run averages nine batches of 128 whole scans of preloaded bytes, including result destruction. Both builds reserve using the token count from the same file. Throughput gap is one minus the median within-pair table/handwritten throughput ratio. A negative gap means the table scanner was faster. MiB/s columns give independent variant medians; the final column gives the middle half of observed paired gaps.

| Compiler | Flavor | File | Table MiB/s | Handwritten MiB/s | Paired gap | Extra ns/token | Middle 50% of gaps |
|---|---|---|---:|---:|---:|---:|---:|
| clang++ | cpp | batch_001.scpp | 838.5 | 786.7 | -6.7% | -0.30 | -7.9% to -5.4% |
| clang++ | cpp | scanner.cpp | 796.1 | 790.2 | -1.1% | -0.05 | -2.5% to +1.0% |
| clang++ | cpp | core.scpp | 720.7 | 704.0 | -2.7% | -0.13 | -3.9% to -0.7% |
| clang++ | php | batch_000.phs | 811.0 | 877.5 | +7.3% | +0.36 | +5.7% to +8.1% |
| clang++ | php | scanner.php | 797.8 | 862.6 | +8.0% | +0.39 | +6.2% to +8.6% |
| clang++ | php | main.phs | 698.8 | 707.7 | +1.5% | +0.07 | +0.4% to +2.1% |
| clang++ | js | batch_002.jss | 817.5 | 821.4 | +0.4% | +0.02 | -0.0% to +1.5% |
| clang++ | js | scanner.js | 774.0 | 818.7 | +5.6% | +0.26 | +4.4% to +6.4% |
| clang++ | js | labels.jss | 650.4 | 760.7 | +14.0% | +0.70 | +13.2% to +16.2% |
| g++ | cpp | batch_001.scpp | 766.4 | 695.6 | -10.2% | -0.51 | -11.0% to -9.7% |
| g++ | cpp | scanner.cpp | 615.5 | 588.8 | -5.0% | -0.28 | -6.7% to -3.9% |
| g++ | cpp | core.scpp | 557.7 | 571.5 | +2.2% | +0.14 | +1.6% to +3.5% |
| g++ | php | batch_000.phs | 724.1 | 692.3 | -4.7% | -0.26 | -6.0% to -3.0% |
| g++ | php | scanner.php | 683.3 | 650.8 | -5.5% | -0.32 | -6.2% to -4.8% |
| g++ | php | main.phs | 411.5 | 462.4 | +11.4% | +0.89 | +11.0% to +11.8% |
| g++ | js | batch_002.jss | 608.5 | 630.2 | +3.9% | +0.24 | +2.6% to +4.5% |
| g++ | js | scanner.js | 546.6 | 572.8 | +4.8% | +0.30 | +3.9% to +5.4% |
| g++ | js | labels.jss | 467.4 | 530.7 | +11.9% | +0.82 | +10.8% to +13.8% |

Across one scan of each of the nine files, the estimated throughput gap was 1.5% on Clang and 0.8% on GCC. This uses the sum of per-file median elapsed times for each implementation.

The sample covers billing batches, the scanner sources and three application/support files. Results are warm-rescan measurements for this lexical coverage. Individual flavor groups contain different files.

Reproduce with `python3 -B source_native.py FIXTURES SLOT_WORK CORPUS CLASSIFICATION_WORK RUNTIME WORK --cpu 7`. [Paired samples, compiler versions, file and binary hashes](source_native.dev002.json).
