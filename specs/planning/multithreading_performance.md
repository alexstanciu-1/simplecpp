# A simple first thread pool for v0.2

Doc Status: planning

**Use one reusable pool for substantial parallel phases.** For a phase with N participants, let the caller compute one contiguous slice and N−1 pool workers compute the others. Keep the same assignments through the phase. Admit the whole batch before publishing work, and wait for every participant before releasing its segments or exposing results. This fits the existing zero-copy segment model. [MT backlog](multithreading_backlog.md)

## What dev002 measured

| Synthetic workload | Clang 18 | GCC 13 |
|---|---:|---:|
| Eight synchronized passes, one thread | 603 ms | 601 ms |
| Same work, four threads, active-wait pool | 153 ms (3.95×) | 156 ms (3.86×) |
| Same work, eight threads, active-wait pool | 81 ms (7.46×) | 81 ms (7.42×) |

The eight passes each traversed 65,536 records with equal arithmetic work per record. Pool setup and shutdown were excluded; dispatch, work and completion waits were timed. Real compiler passes can have unequal costs and dependencies. [Eight-pass measurements](benchmarks/native_loop/dev002-eight-passes.csv)

Short tasks exposed the cost of dispatch. Over 5,801 short passes, fresh threads took **215–224 µs per pass** at eight threads. A prestarted blocking pool took **27–29 µs**. In a separate comparison, active waiting reduced **28–29 µs to 16–18 µs** while occupying nearly eight cores. [Fresh and pooled trials](benchmarks/native_loop/dev002-pool.csv) · [Wait-policy trials](benchmarks/native_loop/dev002-pool-active.csv)

## What to implement first

1. **Make the pool wait policy configurable.** Offer sleeping workers for workloads that share CPU capacity and active waiting for sustained work on dedicated cores. Dispatch once per substantial pass and use the direct serial path for small inputs. Measure both modes on real compiler passes to guide the default.
2. **Keep worker placement stable.** Where topology is available, prefer one worker per available physical core. Equal contiguous slices are sufficient for the first implementation. Adaptive migration needed about 20 ms to react on dev002. Clang first showed a sustained gain with about 180 ms of work left after contention began; GCC showed no sustained crossover through 1.11 s. A background load monitor or an automatic migration threshold would add complexity with uncertain benefit. [Migration results](benchmarks/native_loop/README.md#adaptive-placement-break-even-on-dev002)
3. **Measure serial and pooled elapsed time per real pass.** Record the input size and worker count. Those measurements can guide the parallel cutoff and show whether any pass needs better load balancing. Report thread-only and whole-pool efficiency side by side to show how much scaling is lost inside the slices and during coordination. Report one-time pool startup separately from steady-state pass timing. [Definitions and benchmark results](benchmarks/native_loop/README.md#kernel-only-versus-whole-pool-efficiency)

## Lexeme scanning on accounting-large

On dev002 the original native harness scanned 93 preloaded files (528,088 bytes, 125,334 lexemes). Each of eight rounds measured all four participant counts with Clang 18 and GCC 13. The caller worked, the pool was ready, and the assignments used whole files on distinct physical cores. Each run verified all lexeme boundaries against the PHP reference.

| Participants | Clang ms/pass | Clang speedup | GCC ms/pass | GCC speedup |
|---:|---:|---:|---:|---:|
| 1 | 5.69 | 1.00× | 5.31 | 1.00× |
| 2 | 3.03 | 1.88× | 3.39 | 1.57× |
| 4 | 1.74 | 3.49× | 1.80 | 3.23× |
| 8 | 1.26 | 4.69× | 1.32 | 4.07× |

Times are medians of eight round means, each covering 224 passes. Speedups are medians of within-round serial/parallel ratios. Four participants delivered 81–87% whole-pass efficiency. Eight delivered 51–59% and reduced median latency by about another half millisecond. The eight slices covered 14,716–16,763 lexemes each. Slice counts alone do not establish the cause of the remaining scaling loss. [Matched dev002 timings](benchmarks/native_loop/dev002-lexeme-scaling.csv.gz)
