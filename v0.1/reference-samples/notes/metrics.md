# Sample stage measurements
Doc Status: supporting

Measured 2026-10-26. Seven fresh-process trials after one warmup per command,
eight worker budget, warm filesystem cache, normal optimized build. All samples
use the same default five runtime catalogs (76,752 bytes). Timings below are medians.
Tokenization is excluded. No native build time is included.

| Stage / metric | Small accounting | Large accounting | my-try compiler |
| --- | ---: | ---: | ---: |
| Configuration (ms) | 0.111 | 0.106 | 0.123 |
| Catalog loading (ms) | 3.416 | 3.380 | 3.554 |
| Catalog validation (ms) | 1.612 | 1.607 | 1.635 |
| Discovery / sort (ms) | 1.059 | 2.930 | 7.480 |
| Checked read batch (ms) | 0.977 | 1.183 | 2.619 |
| Model publication (ms) | 0.001 | 0.001 | 0.001 |
| Total startup (ms) | 7.368 | 9.475 | 15.938 |
| Peak scan workers | 1 | 2 | 8 |
| Peak read workers | 2 | 6 | 8 |
| Whole-process peak RSS (KiB) | 5632 | 6016 | 7552 |

Discovery timing covers traversal/sorting; read timing covers worker scheduling,
checked reads and joining. Total startup additionally includes reconciliation,
read-request construction, buffer publication and other coordinator work.
Separate medians do not necessarily sum to the median total. Total excludes
process startup and teardown. RSS covers the whole process, not individual stages.

## Separate allocation-instrumented operations

These runs time the complete inventory scan/load calls. Counters cover ordinary
C++ new/new[] requests, not direct malloc, aligned allocation or allocator overhead.
Requested bytes are cumulative allocation traffic, not peak live memory.

| Operation / metric | Small accounting | Large accounting | my-try compiler |
| --- | ---: | ---: | ---: |
| Discovery initial time (µs) | 975 | 3018 | 7497 |
| Discovery initial allocation requests | 844 | 5350 | 18369 |
| Discovery initial requested bytes | 135468 | 774816 | 3847559 |
| Discovery unchanged time (µs) | 716 | 2624 | 7160 |
| Discovery unchanged allocation requests | 761 | 4864 | 17028 |
| Discovery unchanged requested bytes | 130950 | 741306 | 3726097 |
| Source loading initial time (µs) | 955 | 1277 | 2600 |
| Source loading initial allocation requests | 281 | 1535 | 4166 |
| Source loading initial requested bytes | 136511 | 635009 | 2570709 |
| Source loading unchanged time (µs) | 0 | 2 | 7 |
| Source loading unchanged allocation requests | 0 | 0 | 0 |
| Source loading unchanged requested bytes | 0 | 0 | 0 |

A zero-microsecond observation is below the timer resolution, not zero work.
Unchanged loading reuses buffers in the same process while changes remain pending.
An unchanged scan still traverses the filesystem and builds metadata observations.
This is not a measurement of incremental parsing or compilation.

## Reproduction

From the repository root:

```sh
python3 compiler/my-try-cpp/tools/build.py build
ninja -C compiler/my-try-cpp/build/compiler -j8 source-discovery-profile source-reading-profile
python3 compiler/my-try-cpp/tools/measure_samples.py
```

The script refreshes [raw trials and min/median/max summaries](metrics.json).
This Markdown file records the dated baseline. The benchmark checks source counts
and bytes against each startup report. Allocation profiling is separate from
uninstrumented startup timing. CPU scheduling and filesystem caches affect results.
