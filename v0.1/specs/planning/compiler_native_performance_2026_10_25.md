# Native bootstrap performance, Clang cost and concurrency audit
Doc Status: planning

Measured revision: `fd3dd1816` (compiler/runtime implementation from `b3ef5d2f0`).
No production compiler/runtime/semantic fixes were made during this investigation.
[Evidence, scripts and per-action data](results/compiler_native_performance_2026_10_25/README.md).

## Conclusions

1. The original 12-minute self-compilation used **O0**. Building the same generated
   compiler at O2 reduces local self-compilation to **252.58s**, a **2.85x** improvement.
   All 6276 generated C++ files are byte-identical to the O0 compiler's output.
2. The remaining dominant runtime cost is **serial C++ emission/publication with
   repeated growing-string copies**, not frontend thread availability.
3. dev002's 32-worker compiler run really executes multiple workers, but only the
   frontend phase currently uses that concurrency. Overall runtime barely changes.
4. Clang has a different bottleneck: one 15.3 MB specialization translation unit.
   It dominates even a 32-way compile replay. Template-free validation is another
   measurable cost; its checks were retained in all production build measurements.
5. Per-source **body** preparation is a plausible next parallel slice. Whole-file
   preparation is not currently independent; see the [concrete design and approval
   boundary](compiler_preparation_concurrency_audit_2026_10_25.md).

## Compiler execution

| Run | Wall | User CPU | Kernel CPU | Peak RSS |
| --- | ---: | ---: | ---: | ---: |
| Local native O0, original bootstrap | 718.99s | 633.74s | 114.87s | 823104 KiB |
| Local native O2, same input/driver | 252.58s | 145.91s | 113.72s | 808792 KiB |
| dev002 O2, 1 worker | 113.57s | 63.89s | 49.78s | 797512 KiB |
| dev002 O2, 32 workers | 111.77s | 67.60s | 50.50s | 809468 KiB |

Local historical PHP-hosted publication was 123.79s / 796076 KiB, with early
focused-test overlap and a project adapter; it is context, not a perfectly controlled
PHP-versus-native benchmark. dev002 uses a separate configurable benchmark entry
(runtime metadata path and jobs read from files), the same compiler implementation,
and the same converted compiler workload. Worker comparisons are within one machine;
local-versus-server differences must not be attributed to optimization alone.

dev002: AMD Ryzen 9 5950X, 32 logical CPUs available in affinity, native Linux
6.8.0-85-generic, Clang 18.1.3, PHP 8.5.12. The benchmark did not overlap its own Clang
build/profiling runs. The host was not reserved exclusively against unrelated work.

### Stage observation, local O2

GDB entry/return observations of the existing methods (no compiler source edits):

| Phase | Wall |
| --- | ---: |
| Initialization | 0.080s |
| Frontend sync: scan/tokenize/parse/join | 1.357s |
| Semantic preparation | 61.753s |
| C++ generation and output publication | 188.120s |
| Observed process total | 251.765s |

This agrees with the independent uninstrumented 252.58s run. Preparation accounts
for about a quarter of runtime. Its theoretical elimination alone would give only
about 1.32x overall speedup while emission stays unchanged. Actual parallel body
preparation has serial prerequisites and will improve less than that bound.

### Thread evidence

The 32-worker run reached 33 live threads (main plus workers), with CPU activity
observed from 32 threads in an interval. The busiest 250ms bin used approximately
14.4 CPU cores, versus approximately one in the one-worker control. Overall CPU
utilization was 105% versus 100%, reflecting long serial later phases. Short 10ms
percentage samples are quantized and can exceed physical CPU count; they are not
used as a CPU-capacity claim. Thread counts/activity are sampled lower bounds.

Both runs publish 6276 C++ files. Their differences reduce to three generated symbol
renames (canonical identity allocation order) and implementation-helper ordering.
After name normalization, 6275 files are byte-identical; the remaining implementation
file has the same line multiset. This is not raw reproducible output; names/order
remain a separate determinism consideration. No semantic output difference was found.

## Allocation evidence and source cause

A complete raw heaptrack recording reports **477,356,407 allocation events** and
**580,192,188,902 requested bytes (540.35 GiB cumulative)**. This is allocation traffic,
not live memory or a leak claim. The unprofiled compiler's peak RSS stays below 1 GiB.
Local O2 incurs 32,758,608 minor page faults, nearly unchanged from O0's 32,852,790;
there are no swaps or major faults explaining the slowdown.

The summary retains the 30 largest allocation-stack totals for requests >=64 KiB.
Consequently the following grouped stack totals are **lower bounds**, not complete
per-method totals:

| Source owner | Requested volume visible in those stacks |
| --- | ---: |
| `CPP_Specializations::append_artifacts()` | 216.56 GiB |
| `Output_Publication::publish()` | 100.41 GiB |
| `Tokenizer::token_end()` | 66.20 GiB |
| `Tokenizer::trivia_end()` | 13.23 GiB |

The four principal growing-implementation concatenation/copy stacks alone total
approximately 168.39 GiB; four manifest-building stacks total approximately 84.86 GiB.

`05_backend/cpp/specializations.php:244` repeatedly appends to the implementation
string that eventually becomes the ~15.3 MB `specializations/containers.cpp`.
`compiler/output_publication.php:85` repeatedly grows the JSON manifest. Layout
strings accumulated by depth add further traffic. These are concrete hot sources,
not a hypothetical concern about all strings.

The existing general compound-assignment lowering (`05_backend/cpp/generate.php:760`)
snapshots the old target, snapshots operands, computes a new value, assigns it back
and returns the target value even when the expression result is discarded. The
concrete string copy constructor owns a native `std::string` copy. Thus repeated `.=`
can copy the entire growing prefix several times per append, creating quadratic
traffic. Optimization removes much scalar/call overhead but cannot simply erase
all these opaque ownership boundaries and preserve arbitrary RHS effects.

Tokenizer stacks separately show full source-string snapshots around read-only byte
operations. Their allocation volume is large, but their measured parallel stage is
short. Prioritize the dominant emission/publication sites before broad tokenizer work.

### Recommended performance repair order (not implemented)

1. Use existing typed piece containers plus one final `q_implode`, or an already
   supported portable builder, for large implementation/manifest assembly. Preserve
   exact bytes, order and ownership. Audit the PHP helper surface before selecting a
   builder; runtime metadata alone is not proof of a PHP portability helper.
2. Review unused compound-expression results and existing prepared snapshot/effect
   facts for a bounded general lowering optimization. Do not change `.=` semantics,
   arbitrary RHS evaluation order or failure/alias behavior just for this compiler.
3. Partition the single specialization implementation artifact into stable bounded
   units, using the existing artifact owner and layout dependencies. This can reduce
   both large-string assembly and Clang's serial critical path.
4. Implement approved preparation body concurrency through the existing owners;
   do not launch unsynchronized whole-file analyzers.

## Clang build measurements

| Build | Wall | Peak individual-process RSS | Cache |
| --- | ---: | ---: | --- |
| Local O2 native compiler | 702.30s | 2501528 KiB | 250 objects + 3 layouts, all fresh |
| dev002 O2 baseline | 369.36s | 2503004 KiB | 250 objects + 3 layouts, all fresh |
| dev002 O2 with action timing / Clang traces | 384.34s | see per-action records | isolated fresh cache |

All use the production concrete-layout/validation path, no PCH and O2. A temporary
host-only action allowance avoids the known 120-second default timeout; production
policy remains unchanged. Profiling instrumentation includes per-action GNU time
and `-ftime-trace`; do not equate its wall time with an uninstrumented build.

Profiled dev002 action totals (serial execution):

| Action category | Count | Wall sum | Kernel CPU sum |
| --- | ---: | ---: | ---: |
| Object compilation, excluding layout probes | 250 | 233.35s | 7.40s |
| Template-free token validation | 242 | 91.07s | 76.86s |
| Dependency discovery | 253 | 7.35s | 3.00s |
| Layout-probe compilation | 3 | 9.87s | 0.61s |
| Layout links / executions | 6 | 0.20s | 0.10s |
| Final compiler link | 1 | 0.40s | 0.16s |

The remaining approximately 42 seconds include host orchestration, hashing,
publication/cache operations and measurement overhead. These sums are not CPU-core
utilization estimates. Raw per-action resource records and all summarized Clang
phase totals are retained in evidence.

`specializations/containers.cpp` alone: **155.74s**, **2518040 KiB RSS**. Clang's trace
reports frontend 76.005s, backend 79.153s; optimizer 54.737s and code-generation passes
24.348s are nested backend components, not additive to the total. Template
instantiation and header-source figures are likewise nested, not extra phases.
All other ordinary object compilations combined take approximately 77.61s.

### 32-way Clang diagnostic replay

After the production build had completed layout resolution and template-free checks,
its 250 independent object commands were replayed with the shared 32-slot runner,
separate output files and the same flags/inputs. **Object compilation took about
159.73s**, versus 233.35s summed serial action time (~1.46x). All 250 object hashes and
the final linked executable match the serial build. The large unit still takes
155.48s and almost entirely determines the parallel wall time.

This is a diagnostic replay, **not** an implemented parallel production builder or a
complete 160-second build including layout/validation. Aggregate parallel resident
memory was not sampled; individual Clang peaks are recorded. The first diagnostic
link command incorrectly forwarded the dependency-file option; only the harness was
corrected and linking retried, without recompiling objects.

## Preparation concurrency decision

The existing scheduler settles contracts before selected bodies, which is a strong
reuse opportunity. However bodies still mutate shared dependencies, lookup records,
canonical identities and demanded implementations. The [bounded proposal](compiler_preparation_concurrency_audit_2026_10_25.md)
keeps contracts/materialization serial and batches bodies per source with coordinator
publication and defined interning synchronization. This is a cross-owner protocol
change and awaits discussion; no production concurrency changes were made.
