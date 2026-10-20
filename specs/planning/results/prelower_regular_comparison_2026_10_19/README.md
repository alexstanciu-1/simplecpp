# Accounting unit compilation: regular templates versus concrete interfaces
Doc Status: supporting

Revision `ba211d2d`, 2026-10-19. Identical current accounting sources in both modes;
[source hashes](source_hashes.json). All 13 executables (ordinary capture plus twelve
measured rebuilds) pass 998 checks with zero failures. Prepared C++ was republished
and verified byte-identical to the preceding template-free acceptance artifacts.

O0, no native debug information, Clang 18.1.3, WSL2 Ryzen 5 PRO 4650G (6 cores,
12 logical CPUs). Regular templated C++ uses its normal reusable runtime PCH;
concrete/template-free application C++ uses no PCH. Runtime, middleware, layout and
PCH creation are excluded. Every sample invokes Clang on all 16 app units with fresh
object generation. Linking and execution validate those objects but are outside the
reported time. Frontend preparation/publication and dependency scanning are excluded.

Three rounds alternate configuration order; up to 12 concurrent Clang processes
for parallel samples. No other proof jobs ran during measurement. External system
load and OS file-cache state were not controlled. This is app recompilation with
reusable dependencies, not a cold full build or an incremental one-unit edit.

## Compile wall time

| Mode | Sequential | Parallel (12 jobs) |
|---|---:|---:|
| Regular templates + PCH | 22.769 s | 5.433 s |
| Template-free, no PCH | 2.801 s | 0.625 s |
| Speedup | 8.13x | 8.69x |

Medians of three measured wall times. Individual unit times under parallel load
include CPU/memory contention; their sum is not parallel wall time.

## Per-unit compilation

Milliseconds, median of three per-process wall times. `entry.cpp` is the generated
executable wrapper; the other units correspond to source files.

| Unit | Regular sequential | Concrete sequential | Regular parallel | Concrete parallel |
|---|---:|---:|---:|---:|
| entry.cpp | 840 | 69 | 1457 | 118 |
| main.phs | 1465 | 89 | 3953 | 198 |
| src/assets.phs | 1187 | 199 | 3392 | 426 |
| src/banking.phs | 1633 | 175 | 3674 | 368 |
| src/business_checks.phs | 2177 | 265 | 4701 | 542 |
| src/calendar.phs | 399 | 149 | 897 | 324 |
| src/checks.phs | 2159 | 316 | 4570 | 597 |
| src/closing.phs | 1534 | 186 | 3672 | 411 |
| src/closing_checks.phs | 3061 | 268 | 5423 | 556 |
| src/core.phs | 549 | 68 | 1146 | 159 |
| src/ledger.phs | 1555 | 209 | 3727 | 451 |
| src/money.phs | 702 | 145 | 1673 | 325 |
| src/purchasing.phs | 1759 | 223 | 3967 | 472 |
| src/reports.phs | 1162 | 119 | 2830 | 283 |
| src/sales.phs | 1024 | 117 | 2508 | 236 |
| src/stock.phs | 1533 | 196 | 3168 | 304 |

## Evidence and reproduction

[Raw samples](summary.json) include every unit duration and each compilation-batch
wall time. [Measurement harness](measure.py) preserves the exact recorded native
arguments, changes object output paths, then links the new objects against their
matching reusable artifacts. It uses the shared test runner and the native builder's
clean environment. The harness contains local paths to captured artifacts.

Raw capture commands, build results and per-command logs are retained under
`/tmp/accounting-regular-comparison/`. Ordinary output was freshly generated with both
prelowering options disabled. Prepared output has both enabled. The ordinary native
capture build used the existing `Native_CPP_Build`; recorded Clang commands were
then replayed directly to isolate compilation from build orchestration.

No O2, ThinLTO, compiler implementation rebuild or compiler code changes were made.
These numbers compare the current two backend modes, not a universal template cost.
