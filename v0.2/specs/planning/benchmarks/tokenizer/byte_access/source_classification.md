# Byte classification on whole repository sources

Doc Status: supporting

Clang 18.1.3 on dev002 CPU 7, -O2, separate translation units, no LTO. Control and table share the proven signed cursor, split checked view, original short-circuit equals lookahead, in-slot token construction and publication by move. The table replaces only whitespace, equals dispatch and identifier byte predicates. PHP variable scanning after a dollar sign uses the same helper in both builds.

The bounded proof stops at punctuation in ordinary programs. For this comparison only, both variants used identical additional handling for keywords, punctuation, decimal literals, quoted strings and PHP variable names needed by these files. Entire original files were scanned without masking, truncation or replay. Sampling covered one arithmetic/control-flow billing batch per flavor, three implementations of the scanner itself, and three application/support files. The latter are different programs, so flavor-level differences there also reflect workload.

Each row uses 32 alternating pairs, after warmup. Nine batches of 128 scans per run include result destruction. Files and reserve hints are preloaded; the hint is the count from the same source (warm rescan). Rates are median per-variant throughput, gains are median within-pair ratios, and the middle 50% describes the observed pairs.

| Flavor | Source | Bytes | Tokens | Control MiB/s | Table MiB/s | Paired gain | Middle 50% gain |
|---|---|---:|---:|---:|---:|---:|---:|
| cpp | [batch_001.scpp](../../../../../samples/capability-benchmarks/medium/billing/batch_001.scpp) | 3,585 | 900 | 749.2 | 833.6 | +11.1% | +10.4% to +12.4% |
| cpp | [scanner.cpp](../../../../../specs/planning/benchmarks/tokenizer/scanner.cpp) | 1,822 | 486 | 779.1 | 798.5 | +2.9% | +1.4% to +4.4% |
| cpp | [core.scpp](../../../../../samples/capability-benchmarks/medium/support/core.scpp) | 2,355 | 651 | 669.7 | 713.8 | +6.9% | +5.8% to +7.8% |
| php | [batch_000.phs](../../../../../samples/capability-benchmarks/medium/billing/batch_000.phs) | 3,801 | 907 | 701.1 | 794.3 | +13.6% | +11.2% to +14.9% |
| php | [scanner.php](../../../../../specs/planning/benchmarks/tokenizer/scanner.php) | 1,853 | 444 | 692.4 | 778.3 | +12.9% | +11.4% to +15.3% |
| php | [main.phs](../../../../../samples/capability-benchmarks/medium/main.phs) | 2,701 | 804 | 617.6 | 680.0 | +9.8% | +8.6% to +11.8% |
| js | [batch_002.jss](../../../../../samples/capability-benchmarks/medium/billing/batch_002.jss) | 3,717 | 949 | 715.5 | 800.9 | +12.4% | +11.3% to +13.4% |
| js | [scanner.js](../../../../../specs/planning/benchmarks/tokenizer/scanner.js) | 1,807 | 492 | 749.1 | 753.1 | +0.6% | -0.3% to +1.6% |
| js | [labels.jss](../../../../../samples/capability-benchmarks/medium/support/labels.jss) | 296 | 87 | 640.2 | 642.7 | +0.5% | -0.9% to +1.9% |

Each binary matched token kind, byte offset, byte length and spelling against the production PHP tokenizer for every token in its three files. All six passed the 11 original fixtures and edit/failure/recovery checks. The benchmark-only lexer additions cover the selected corpus; broader lexical coverage and general production-lexer throughput remain unmeasured.

Run `python3 -B source_classification.py prepare V02 CORPUS` in the repository, then `python3 -B source_classification.py bench CORPUS FIXTURES SLOT_WORK SIGNED_WORK RUNTIME WORK --cpu 7` on dev002. [Raw timings and source hashes](source_classification.dev002.json).

[Direct comparison with handwritten C++ on the same files](source_native.md).
