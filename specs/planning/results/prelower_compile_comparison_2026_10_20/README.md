# Accounting O0 application compilation comparison — 2026-10-20

Doc Status: planning

Measured revision: `08868a1d94847d7dd2e9f7198f9ec840a5160d42`. Generated accounting inputs and reusable support artifacts come from fresh acceptance at `da17e1bad6ab3751a0dbe3c1729273d55dcff5f7`; the intervening commit records documentation only.

Clang 18.1.3; WSL2; AMD Ryzen 5 PRO 4650G, 6 cores / 12 logical CPUs. Three alternating-order rounds, one or up to 12 concurrent Clang processes. All 18 measured executables produced byte-identical accepted output: **998 passed, 0 failed**.

## Application compilation wall time

| Configuration | Sequential median (range) | 12-process median (range) |
|---|---:|---:|
| Ordinary, no PCH | 79.348 s (74.220–87.834) | 18.130 s (16.711–20.912) |
| Ordinary, reusable PCH | 27.132 s (25.833–27.390) | 7.505 s (6.739–10.123) |
| Prelowered, no PCH | 3.188 s (2.967–6.780) | 0.703 s (0.671–0.724) |

Versus ordinary with PCH: **8.51× sequential**, **10.67× parallel**. Versus ordinary without PCH: **24.89× sequential**, **25.78× parallel**. Ratios are of median compilation-batch wall times.

## Slowest units

The slowest unit below is selected by its median process wall time across three rounds. Parallel per-process time includes CPU/memory contention; it is not isolated compile latency.

| Configuration | Jobs | Slowest median unit | Median | Highest single observed unit time |
|---|---:|---|---:|---|
| Ordinary, no PCH | 1 | src/business_checks.phs | 6.064 s | 14.092 s (src/checks.phs) |
| Ordinary, no PCH | 12 | src/closing_checks.phs | 16.063 s | 17.715 s (src/closing_checks.phs) |
| Ordinary, reusable PCH | 1 | src/closing_checks.phs | 3.315 s | 3.686 s (src/closing_checks.phs) |
| Ordinary, reusable PCH | 12 | src/closing_checks.phs | 7.496 s | 10.117 s (src/closing_checks.phs) |
| Prelowered, no PCH | 1 | src/checks.phs | 0.375 s | 1.123 s (src/stock.phs) |
| Prelowered, no PCH | 12 | src/checks.phs | 0.667 s | 0.691 s (src/checks.phs) |

## Complete per-unit medians

Milliseconds. These are independent per-unit medians; their sum need not equal the median batch wall time.

| Unit | Ordinary no PCH seq | Ordinary PCH seq | Prelowered seq | Ordinary no PCH parallel | Ordinary PCH parallel | Prelowered parallel |
|---|---:|---:|---:|---:|---:|---:|
| main.phs | 4972 | 1779 | 105 | 12336 | 4736 | 194 |
| src/assets.phs | 4673 | 1373 | 220 | 12708 | 4321 | 454 |
| src/banking.phs | 5154 | 1914 | 197 | 13244 | 5408 | 388 |
| src/business_checks.phs | 6064 | 2330 | 317 | 14364 | 6441 | 597 |
| src/calendar.phs | 3254 | 412 | 166 | 9610 | 1368 | 373 |
| src/checks.phs | 4825 | 2372 | 375 | 14298 | 6767 | 667 |
| src/closing.phs | 4635 | 1794 | 220 | 12748 | 4946 | 451 |
| src/closing_checks.phs | 6039 | 3315 | 368 | 16063 | 7496 | 605 |
| src/core.phs | 3641 | 552 | 86 | 9637 | 1654 | 151 |
| src/ledger.phs | 5365 | 1711 | 232 | 12765 | 5841 | 495 |
| src/money.phs | 3546 | 708 | 142 | 10496 | 2381 | 350 |
| src/purchasing.phs | 4429 | 1946 | 242 | 13141 | 5420 | 484 |
| src/reports.phs | 3868 | 1394 | 129 | 7543 | 4030 | 276 |
| src/sales.phs | 3730 | 1208 | 124 | 7414 | 3339 | 288 |
| src/stock.phs | 5046 | 1676 | 214 | 7562 | 4307 | 338 |
| entry.cpp | 4035 | 1112 | 78 | 5722 | 2179 | 139 |

## Scope and reproduction

This measures fresh compilation of all 16 application units with reusable dependencies, not a cold full build or a one-unit incremental edit. Clang runs at O0 with native debug disabled. Every measured batch writes new objects, links them against matching support objects and runs the executable. Linking/execution are outside the reported compilation wall time. Link durations are retained separately in the raw results.

Excluded: PHP-host lowering/publication, native build orchestration, dependency and template-policy validation, runtime/middleware/layout compilation and PCH creation. Prelowered input comes from the strict accepted publication; no fallback or replacement template interfaces are selected. Ordinary PCH creation uses the existing builder policy including `-fpch-instantiate-templates`.

No other proof jobs were launched during measurement. External host load, thermals and filesystem-cache state were not controlled. Variation was substantial in some samples, especially the third sequential prelowered run; all samples are retained. These results support a large workload-specific difference, not a universal template-cost ratio or precise small-regression claim.

[Raw measurements](summary.json) include every unit, round, batch wall time and link duration. [Harness](measure.py) reuses the existing shared runner and exact captured native arguments. Native_CPP_Build promotes staged object outputs before linking; the harness maps manifest-ordered translation units to the final linked object list, preserving provider objects. The initial setup attempt assumed temporary compile paths survived promotion and stopped before timing; it produced no benchmark samples.

Local accepted inputs: `/tmp/prelower-solidified-acceptance-20261020`. PCH capture: `/tmp/prelower-compile-comparison-20261020`. Measured logs/objects/executables: `/tmp/prelower-compile-comparison-20261020-final`.

```sh
python3 specs/planning/results/prelower_compile_comparison_2026_10_20/measure.py \
  --acceptance /tmp/prelower-solidified-acceptance-20261020 \
  --pch-record /tmp/prelower-compile-comparison-20261020/clang.jsonl \
  --results FRESH_DIRECTORY
```

Omit `--pch-record` to build a fresh ordinary PCH capture. The acceptance directory must retain its matching generated artifacts, captured commands and native support cache. No O2, ThinLTO, native compiler rebuild or production compiler changes were made.
