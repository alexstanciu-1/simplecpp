# Ready-worker read profiling on dev002
Doc Status: supporting

Measured 2026-10-26 on tf-dev002, Ryzen 9 5950X (16 cores / 32 logical CPUs).
Twenty-one fresh-process trials at each of one and 16 jobs, alternating after
warmup. Warm filesystem cache; the same source-only remote samples as the prior
dev002 run. The small sample has only 15 files, so its worker count is capped at 15.

The optional runtime profiling gate holds all workers until every thread is ready.
Thread creation is excluded from the active work interval. This changes scheduling
and adds clocks; these are diagnostic measurements, not replacement production
throughput numbers. Production calls leave profiling disabled. The experiment uses
fresh threads and bypasses the optional reusable pool.

## Median wall intervals

| Interval | Small accounting | Large accounting | Compiler |
| --- | ---: | ---: | ---: |
| Request construction (ms) | 0.009 | 0.051 | 0.119 |
| Thread startup/readiness (ms) | 0.480 | 0.485 | 0.456 |
| Ready-worker work interval (ms) | 0.401 | 0.459 | 0.542 |
| Completion tail (ms) | 0.002 | 0.002 | 0.003 |
| Join cleanup tail (ms) | 0.113 | 0.126 | 0.122 |
| Runtime result collection (ms) | 0.001 | 0.001 | 0.003 |
| Model buffer publication (ms) | 0.001 | 0.003 | 0.008 |
| Total profiled load (ms) | 1.032 | 1.135 | 1.278 |

The work interval runs from gate release to the last worker body completion.
It includes gate wakeup/scheduling, per-worker task-context initialization, atomic
queue access, checked reads and result-slot writes. It excludes thread creation
and final completion signalling/join. This is not a pure disk-transfer timer.
Completion tail ends when the last worker finishes signalling the coordinator.
Join cleanup tail runs from there through all thread joins; it includes thread
epilogues and coordinator wakeup. Time waiting for still-active readers is counted
only in work, never again as join overhead.

Collection is the runtime converting private result slots into its returned vector.
These read callbacks return booleans; source buffers remain in private work records.
Publication is the compiler swapping those buffers into retained records. It requires
no publication mutex because workers have already joined. Request/publication clocks
have microsecond resolution; other interval clocks use nanoseconds.

Totals include additional runtime setup, profile aggregation, cleanup and buffer
destruction. Independently computed medians need not add up to the total median.

## Worker CPU and synchronization sums

| Measurement | Small accounting | Large accounting | Compiler |
| --- | ---: | ---: | ---: |
| Worker-body CPU / work wall (%) | 917.103 | 1287.987 | 1188.296 |
| Checked-read callback CPU / work wall (%) | 75.594 | 372.471 | 1068.661 |
| Atomic index claims, summed (µs) | 2.094 | 4.936 | 14.051 |
| Completion mutex wait, summed (µs) | 20.599 | 39.322 | 50.715 |
| Completion mutex hold, summed (µs) | 21.933 | 22.903 | 29.095 |
| Checked-read callback CPU, summed (ms) | 0.321 | 1.723 | 5.784 |
| Checked-read callback wall, summed (ms) | 0.390 | 1.909 | 6.479 |

100% CPU is one logical CPU. CPU clocks are per-thread user+system time, summed
across workers. Callback CPU measures token_buffer::read, including checked filesystem
reads, allocations and buffer construction; it excludes task-context setup and queue
bookkeeping. Worker-body CPU includes those additional costs. In particular, high
worker CPU on a tiny sample should not be interpreted as useful read parallelism.

The scheduler uses an atomic fetch-add, not a scheduling mutex. Index-claim time
includes the atomic operation and clock overhead; it is not a pure contention or
lock-wait counter. Completion mutex timing covers worker finish_workers acquisition
and its critical section, including notification and unlock. It excludes allocator
internals, OS filesystem locks, the profiling gate and coordinator condition waits.
These sums overlap each other and the wall intervals; do not add or subtract them
to derive a corrected wall time. Late completion contention contributes to the tail;
earlier workers can signal completion while others continue reading.

## Verification and reproduction

All nine focused tests pass locally and on dev002. Source-reading tests exercise
the gated path for repeated notification, failed-batch rollback and retry. All nine
also pass in the isolated ASan/UBSan build with leak detection. Normal dependency
packages were unchanged by sanitizer builds. This is not ThreadSanitizer evidence.

Runtime ownership: modules/tasks/batch_profile.hpp and tasks.cpp own the gated
launch/completion measurement; the existing task scheduler body is reused. Middleware
source_reads owns callback measurement; source_inventory owns request/publication
timing. No tokenization or persistent-pool policy change is included.

In /home/alex-ai/my-try-cpp-concurrency-20261026-bsHKhz:

```sh
ninja -C compiler/my-try-cpp/build/compiler -j16 source-concurrency-profile
python3 compiler/my-try-cpp/tools/measure_read_breakdown.py --jobs 16 \
  --output compiler/my-try-cpp/samples/read-breakdown-dev002-16.json
```

[Raw trials and min/median/max](read-breakdown-dev002-16.json) retain both job counts.
The benchmark validates nonempty work intervals, worker counts, nested CPU totals
and that disjoint measured intervals fit within the complete load interval.
