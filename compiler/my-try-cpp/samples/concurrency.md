# Achieved source-stage concurrency
Doc Status: supporting

Measured 2026-10-26: 21 trials per sample/job count after warmup, alternating
one and eight jobs, fresh processes and warm filesystem cache. The process has
12 logical CPUs in its affinity mask. No allocation-counter instrumentation.

CPU time comes from CLOCK_PROCESS_CPUTIME_ID and includes CPU consumed by all
process threads (user and kernel). Average cores = process CPU time / elapsed time.
100% means one logical CPU fully occupied; 800% would mean eight. These are
whole-stage averages, including coordinator work and worker startup/join.

| Sample | Stage | CPU usage | Average cores | Of 8-core budget | Peak callbacks | Wall ms (8 jobs) | Speedup over 1 job |
| --- | --- | ---: | ---: | ---: | ---: | ---: | ---: |
| accounting-small | discovery | 26.6% | 0.27 | 3.3% | 1 | 1.005 | 1.02× |
| accounting-small | reading | 157.4% | 1.57 | 19.7% | 3 | 0.932 | 0.60× |
| accounting-large | discovery | 29.7% | 0.30 | 3.7% | 2 | 3.141 | 1.00× |
| accounting-large | reading | 306.0% | 3.06 | 38.3% | 6 | 1.381 | 1.75× |
| my-try | discovery | 83.7% | 0.84 | 10.5% | 8 | 7.327 | 1.59× |
| my-try | reading | 411.7% | 4.12 | 51.5% | 8 | 2.766 | 2.50× |

Each value is a median across trials; speedup is the ratio of median elapsed
times. CPU percentages are medians of per-trial ratios. This is not whole-machine
CPU utilization, and logical cores are not necessarily separate physical cores.

Discovery achieves much less CPU use than its peak overlapping callbacks suggest.
Its directory frontier and serial reconciliation constrain parallel work; filesystem
waits and scheduling can also reduce CPU occupancy. These measurements do not
isolate their respective contributions. Warm caches do not eliminate filesystem calls.

Eight-job reading helps the larger inputs, but slows the 15-file input: scheduling
and worker startup costs can exceed the available parallel work. No automatic job
policy change has been made. Catalog setup is outside this measurement, and
tokenization is excluded. No speedup or CPU figures are inferred for those stages.

## Reproduce

```sh
ninja -C compiler/my-try-cpp/build/compiler -j8 source-concurrency-profile
python3 compiler/my-try-cpp/tools/measure_concurrency.py
```

The opt-in profiler uses a host POSIX CPU clock only for benchmarking; compiler
and middleware behavior are unchanged. Raw trials and min/median/max are saved in
[concurrency.json](concurrency.json). The script refreshes JSON; this document is
the dated baseline. Short millisecond intervals are sensitive to host scheduling.
