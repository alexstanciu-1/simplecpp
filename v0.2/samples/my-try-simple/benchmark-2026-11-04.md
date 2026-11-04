# Compiler-layout sample benchmark — 2026-11-04

Doc Status: supporting

Compiler revision: `40a046e72`. The optional preparation mode was added to the harness after that commit; compiler sources were unchanged during measurement.

Local WSL, AMD Ryzen 5 PRO 4650G (6 cores / 12 logical CPUs), PHP 8.5.7, Xdebug absent, CLI OPcache disabled. Seven warm-filesystem-cache samples per mode in one PHP process; no CPU isolation. Sources and edits use disposable copies.

Workload: 257 files, 256 public no-argument functions plus the implicit entry, 42,195 source bytes and 4,870 tokens. This mirrors the donor directory layout, not its compiler complexity.

## Full stages

These are all-file totals from `--prepare`. Parsing includes local collection. Preparation includes existing reset/admission/diagnostic work; C++ emission produces text in memory, without writing files or invoking Clang. Module loading and PHP startup are outside these stage timers.

| Stage | Median ms | Min–max ms |
| --- | ---: | ---: |
| read_snapshot | 5.546 | 4.519–7.047 |
| tokenize | 6.749 | 6.474–7.490 |
| parse_collect | 8.030 | 5.786–11.447 |
| symbol_index | 0.851 | 0.743–1.100 |
| prepare | 2.066 | 1.699–2.458 |
| cpp_text | 0.719 | 0.633–0.867 |
| staged_total | 23.960 | 20.132–29.540 |

Preparation occupies a median 8.3% of each staged total in these samples. This does not predict the cost of future types, namespaces or inheritance, or explain the historical 90%+ figure from my-try. Total medians are computed from per-sample totals, not sums of medians.

## Incremental frontend plus preparation

These use the real in-memory incremental APIs. Frontend includes module loading, metadata checks, symbol publication, watch retirement/comparison/dispatch, and inventory writes when membership changes. Preparation reuses or rebuilds body caches; it does not emit incremental C++ or an executable. Edits, assertions and test manifest writes occur outside timers.

| Scenario | Frontend median ms | Preparation median ms | Combined median ms | Combined min–max ms | Bodies prepared / reused |
| --- | ---: | ---: | ---: | ---: | ---: |
| full | 24.378 | 2.202 | 27.015 | 22.068–28.243 | 257 / 0 |
| unchanged | 4.514 | 0.492 | 4.982 | 4.854–5.978 | 0 / 257 |
| changed | 4.823 | 0.552 | 5.417 | 4.926–5.799 | 1 / 256 |
| added | 5.167 | 0.507 | 5.754 | 5.224–6.371 | 1 / 257 |
| removed | 5.637 | 0.491 | 6.140 | 5.773–6.463 | 0 / 257 |
| moved | 5.829 | 0.524 | 6.396 | 5.848–6.664 | 1 / 256 |

The unchanged preparation pass still scans/validates the inventory. Editing or moving a function without changing its signature rebuilds its body but leaves callers prepared. The added/removed scenario uses an unreferenced function. Removal/reintroduction of called functions and signature errors remain covered by the regression tests, not these timing scenarios.

## Frontend-only comparison

The original harness mode was also rerun, without registering preparation watches. Its current full frontend median is 22.670 ms and unchanged median 4.293 ms. The earlier UTF-8 run recorded 19.362 / 3.778 ms. These are separate runs across code changes, not a controlled A/B attribution. Parsing and publication are review candidates; this measurement alone does not identify the cause of the difference.

## Correctness and memory

- All seven samples passed source read/status counts, unchanged AST/token identity, stable symbol redirects, addition/removal/move checks, and fresh-index equivalence.
- Preparation counts matched expectations in every scenario.
- Full preparation and emission succeeded with 257 source units plus one native entry adapter, 81968 bytes of generated text.
- Peak PHP allocator usage was 8 MiB in each harness process. This is not process RSS or a long-running retention benchmark.
- The repository sample sources were not edited; no native executable was compiled or run.

## Reproduce

```sh
XDEBUG_MODE=off php v0.2/samples/my-try-simple/benchmark.php
XDEBUG_MODE=off php v0.2/samples/my-try-simple/benchmark.php --prepare
```

Raw samples: [frontend](benchmark-2026-11-04-frontend.json), [preparation and incremental](benchmark-2026-11-04-preparation.json).
