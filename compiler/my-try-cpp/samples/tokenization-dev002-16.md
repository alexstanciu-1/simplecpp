# Compact tokenizer benchmark — dev002, 16 jobs
Doc Status: supporting

Measured 2026-10-26 on tf-dev002, Ryzen 9 5950X (16 physical / 32 logical cores),
Ubuntu Clang 18.1.3, release -O2 -DNDEBUG, Ninja and Mold. Isolated workspace:
/home/alex-ai/my-try-cpp-concurrency-20261026-bsHKhz.

Twenty-one trials per sample, mode and job count, after warmup; alternate one and
16 jobs within each mode. Modes are normal timing, gated ready-worker timing, and
allocation interception. Sources are discovered and loaded before timed tokenization.
No catalogs, parsing, semantic analysis or C++ generation are included. Each first
tokenization uses a fresh process; unchanged reuse is measured in that same process.

## Normal tokenization (uninstrumented allocation path)

| Metric | Small accounting | Large accounting | Compiler |
| --- | ---: | ---: | ---: |
| Source bytes | 118,906 | 528,088 | 1,943,499 |
| Files | 15 | 93 | 257 |
| Tokens | 19,469 | 86,942 | 298,589 |
| Tokenization total (ms) | 1.338 | 4.017 | 7.185 |
| Whole-stage CPU (%) | 699.5 | 985.1 | 1283.1 |
| Peak callbacks | 13 | 16 | 16 |
| Source throughput (MB/s) | 88.9 | 131.5 | 270.5 |
| Packed token payload (bytes) | 155,752 | 695,536 | 2,388,712 |
| Token array capacity (bytes) | 221,184 | 1,178,080 | 3,605,256 |
| Current RSS before tokenization (KiB) | 4,096 | 4,352 | 5,120 |
| Current RSS after tokenization (KiB) | 4,352 | 4,352 | 8,448 |
| Process high-water RSS (KiB) | 4,352 | 4,352 | 8,448 |
| Unchanged reuse (µs) | 1 | 3 | 6 |

CPU is process CPU / elapsed time: 100% is one logical CPU. RSS comes from this
exec's /proc/self/status VmRSS/VmHWM, avoiding inherited launcher peaks reported by
getrusage. Kernel RSS counters have sampling/accounting granularity. RSS includes
discovery, loaded bytes, libraries, worker/runtime state and allocator-retained pages;
it is not exact live token heap. Payload and capacity byte counts are exact.

## Separate gated profile

All threads reach the launch gate before work starts. Work includes task-context
setup, queue scheduling, scanning, packing and result slots; startup and join cleanup
are excluded. Scanner callback CPU excludes scheduler/context setup. The small
sample has 15 files, so the runtime starts 15 workers, not 16.

| Metric | Small accounting | Large accounting | Compiler |
| --- | ---: | ---: | ---: |
| Thread startup/readiness (ms) | 0.327 | 0.414 | 0.399 |
| Ready-worker work interval (ms) | 0.925 | 2.699 | 7.571 |
| Scanner CPU / work wall (%) | 717.1 | 1078.6 | 1316.8 |
| Worker-body CPU / work wall (%) | 916.6 | 1104.7 | 1320.1 |
| Peak callbacks | 14 | 16 | 16 |
| Completion tail (µs) | 1.382 | 1.463 | 2.104 |
| Join cleanup tail (µs) | 81.723 | 107.241 | 120.606 |
| Result gathering (µs) | 0.491 | 1.893 | 3.486 |
| Model publication (µs) | 1.000 | 3.000 | 8.000 |
| Atomic queue claims, summed (µs) | 2.086 | 4.544 | 9.880 |
| Completion mutex wait, summed (µs) | 1.721 | 1.854 | 3.116 |
| Completion mutex hold, summed (µs) | 20.268 | 21.490 | 25.808 |

Gating and per-file clocks change timing. These measurements are separate runs,
not numbers to subtract from normal wall time; the compiler gated work interval
can exceed the normal total due to instrumentation and frequency/scheduling variation.
Worker sums overlap wall intervals. Atomic claims include clock overhead and are
not a pure contention measure. Completion locks exclude allocator/internal OS locks.
Publication runs after joining and requires no publication mutex. See
[read profiling methodology](read-breakdown-dev002-16.md) for the timing boundaries.

## Separate allocation measurements

| Metric | Small accounting | Large accounting | Compiler |
| --- | ---: | ---: | ---: |
| Allocation requests | 245 | 1,301 | 3,386 |
| Cumulative requested bytes | 419,331 | 2,207,659 | 6,808,447 |
| Unchanged reuse allocations | 0 | 0 | 0 |

These count ordinary new/new[] during the complete tokenization operation, including
worker/task setup, work records and geometric token-array growth. They exclude source
reading, reporting, direct malloc/aligned allocation and allocator overhead. There is
no allocation per token or copied token spelling. Reserved capacity is not live payload.
The excess capacity is visible above; a changed generation can reserve using its prior
token count. No persistent pool or shrink-to-fit policy is introduced in this slice.

## Correctness and reproduction

All 12 focused tests passed on dev002 (1.85 seconds) and locally, including exact
24-bit length limits, rejected input, old generation lifetime, unread-revision guard
and transactional lexical-failure recovery. ASan/UBSan with leak detection passed
all 12 tests (7.10 seconds); normal dependencies were unchanged by sanitizer builds.

All 365 files and 405,000 tokens match the old tokenizer offset-for-offset and
length-for-length. Tags are independently checked against source spelling. Remote
source hashes and complete token dumps match that reference at one and 16 jobs.
See [validation evidence](token-validation.json). This does not establish parser or
semantic correctness, race freedom, or performance of a full compiler.

```sh
python3 compiler/my-try-cpp/tools/build.py middleware --jobs 16
python3 compiler/my-try-cpp/tools/build.py test --jobs 16
ninja -C compiler/my-try-cpp/build/compiler -j16 tokenization-profile tokenization-allocations
python3 compiler/my-try-cpp/tools/verify_token_samples.py
python3 compiler/my-try-cpp/tools/measure_tokenization.py --jobs 16 \
  --output compiler/my-try-cpp/samples/tokenization-dev002-16.json
```

[Raw trials and min/median/max summaries](tokenization-dev002-16.json) include both
job counts. The local oracle comparison is tools/validate_token_samples.py; it uses
the old compiler shared test runner and is deliberately outside the fast default suite.
