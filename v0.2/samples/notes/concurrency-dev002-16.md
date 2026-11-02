# dev002 source-stage concurrency, 16 jobs
Doc Status: supporting

Measured 2026-10-26 on alex-ai@dev002.travelfuse.ro (tf-dev002).
AMD Ryzen 9 5950X: 16 physical cores, 32 logical CPUs, all 32 available to the process.
Ubuntu Clang 18.1.3, Linux 6.8.0-85-generic, release -O2 -DNDEBUG, Ninja and Mold.
Isolated workspace: /home/alex-ai/my-try-cpp-concurrency-20261026-bsHKhz.

Fresh runtime/middleware/compiler builds succeeded. All nine focused tests passed
(1.30 seconds CTest). No prebuilt WSL libraries were used.

21 trials per sample per job count, alternating one and 16 jobs after warmup.
Warm filesystem cache; fresh process each trial; no allocation counters.
CPU time is summed across all process threads. 100% CPU = one occupied logical CPU.
Whole-stage measurements include coordinator work and thread startup/join.

| Sample | Stage | Median CPU | Average cores | Of 16-core budget | Wall ms | Speedup vs 1 job | Peak callbacks (median / max) |
| --- | --- | ---: | ---: | ---: | ---: | ---: | ---: |
| accounting-small | discovery | 104.0% | 1.04 | 6.5% | 0.476 | 1.00× | 1 / 1 |
| accounting-small | reading | 237.1% | 2.37 | 14.8% | 0.538 | 0.52× | 4 / 4 |
| accounting-large | discovery | 107.6% | 1.08 | 6.7% | 1.713 | 0.95× | 2 / 2 |
| accounting-large | reading | 402.1% | 4.02 | 25.1% | 0.715 | 1.59× | 8 / 9 |
| my-try | discovery | 251.7% | 2.52 | 15.7% | 3.359 | 1.95× | 10 / 10 |
| my-try | reading | 677.1% | 6.77 | 42.3% | 1.083 | 4.79× | 15 / 16 |

Compiler reading CPU ranged from 554.8% to 728.0% across these short trials.
Sixteen jobs improve the larger read batches; the smallest read batch is slower
than one job. Callback overlap is not equivalent to sustained CPU occupancy.

This result does not establish a general WSL CPU cap: hardware, OS, storage and
job count differ. Also, remote transfer intentionally omitted the generated hidden
converter token cache, so compiler-sample discovery does not traverse precisely
the same non-source files as the WSL run. All PHS sources were transferred.
Automatic approval review rejected adding that cache; it was not needed to run
the benchmark. The one-versus-16 comparison uses the same remote input tree.

## Reproduction on the isolated host

```sh
cd /home/alex-ai/my-try-cpp-concurrency-20261026-bsHKhz
ninja -C compiler/my-try-cpp/build/compiler -j16 source-concurrency-profile
python3 compiler/my-try-cpp/tools/measure_concurrency.py --jobs 16 \
  --output compiler/my-try-cpp/samples/concurrency-dev002-16.json
```

Raw trials, environment identification and min/median/max summaries are in
[concurrency-dev002-16.json](concurrency-dev002-16.json). Tokenization is excluded.
