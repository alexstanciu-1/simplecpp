# Compiler preparation and emission concurrency evidence
Doc Status: planning

Candidate: `a1c113109` plus the source/runtime changes accompanying this report.
The independently verified iteration repair is commit `42e23e71f`. Candidate 07
passes focused retained-state checks, native execution in both output modes,
generated-compiler execution, and the full 32-worker ThreadSanitizer workload.
Accepted for this bounded source-batch slice, with the limitations below.

## Scope and reuse

The existing joined unordered task API schedules one batch per source collection.
Declaration contracts settle serially. Body workers reuse the semantic preparation
handlers, record forward observations privately, and suspend unmet declarations or
called-type materialization for the coordinator. The coordinator merges reverse
subscriptions, versions, failures and materialization demands after the join.
Registry snapshots reuse canonical identities; short publication callbacks serialize
new identity allocation. Capability checking stays outside the registry lock.

C++ rendering reuses existing fragment generation with private per-source programs
and specialization engines. The coordinator merges canonical names and publishes
artifacts. Contract settlement, artifact assembly, filesystem publication and the
production native C++ build runner remain serial. This does not claim that every
compiler phase now scales with worker count.

## Runtime failure found by native testing

The first native two-worker preparation run crashed. Repeat attempts either crashed
or returned an exception. The captured stack led through
`semantic_type_definition::declares_capability()` to vector value iteration.
Concurrent const traversals modified lazy iteration-source pointers and subscription
links without synchronization. This is hidden runtime bookkeeping, not an authored
container write by a compiler worker.

The runtime repair atomically publishes the lazy source and synchronizes subscription
attach/detach per source. Traversal positions remain private. Container mutation,
retirement and boxed-binding replacement still require caller synchronization.

The focused 16-reader vector/hash stress test passes at O2 and with ThreadSanitizer.
The same test against the three original headers reports a data race (exit 66),
retained in `baseline-iteration-tsan.stderr`. The existing mutation/preservation
runtime suite also passes with ASan/UBSan/LeakSanitizer. Its first sandbox execution
was blocked by LeakSanitizer's ptrace restriction; the outside-sandbox rerun passed.
This is focused runtime race evidence, not a whole-compiler race-freedom proof.

The full native compiler's ThreadSanitizer run then found a second race:
`Source_Types::definition()` created fresh nominal definitions even for read-like
`self`, `static` and field-owner queries during parallel bodies. Creation now has
an explicit collector-only entry; lookup reads the already published definition
from the declaration's scope and checks its existing provenance association. No
new retained index or semantic type is introduced. A regression checks repeated
source lookup identity and unchanged registry definition count. The trait-model check also passes. The broader shared_upcasts.php run reaches its
old uninitialized-required-local positive case and fails there, identically on
the untouched baseline; it is not counted as a passing suite.

A subsequent native fixture exposed duplicate typed-container initializer factories.
Initialization now validates a private factory/binding candidate before a short
synchronized existing-or-publish transaction. Factory identity and binding-cache
membership publish together; losers consume the winner. Signature/capability
preparation stays outside the publication lock. This reuses Initialization_Contracts
and Runtime_Callables rather than adding a second initialization mechanism.

The next sanitizer fixture reached emission and found lazy adapter registration
in `CPP_Data_Construction::reference()`. The C++ coordinator now publishes adapters
from the settled initialization-binding catalog before launching render tasks.
Workers consume those bindings without catalog mutation; standalone serial
consumers retain the same installation helper. This introduces no backend semantic
discovery or additional registry.

## PHP and retained checks

`tests/concurrent_stages.py` passes all six checks: concurrent_stages,
callable_instances, trait_instances, async_retained, result_model and
contextual_closures. The new fixture compares ordinary and concrete output with
jobs 1/2/32, warm fragment/version reuse, invalid edits, failed retry, and repair.
It combines source-private closures, shared template applications, inherited
called-type demand and shared constant/lookup dependencies. PHP runs callbacks
sequentially; these checks alone do not prove native overlap.

The empty-default suite also passed after preparation and emission integration.
The callable-contract suite passes after initializer publication changes. The old
initialization.php suite still expects bool-keyed hashes to reject, and fails at
that assertion on both candidate and untouched baseline; it is not a passing suite.
Two exploratory older tests, constructed_type.php and closures.php, failed their
existing fixed application-count / std::function-text assertions. Both fail
identically with the HEAD preparation/emission files restored in an isolated boot
overlay; they are not classified as new concurrency failures.

Local PHP bootstrap checkpoint 07: 42.125 seconds, no compiler failure. dev002's
host publication of candidate 07: 42.13 seconds, 1,022,304 KiB peak RSS.
The native compiler source build is O2. PHP speed improvement includes avoiding
repeated declaration scheduling; it is not attributed to PHP threading.

## Benchmark procedure

`native_rebuild.py`, `native_measurement.py`, `main.php` and `thread_monitor.py`
retain the candidate-07 server recipes/driver, including isolated absolute paths. They use the repository's
shared CommandRunner/Task runner. The native driver marks initialization, frontend
sync, preparation, C++ generation/publication and completion. `/usr/bin/time -v`
measures process wall/user/system time and peak RSS; a /proc sampler records live
threads and active thread CPU ticks by stage. Sampled CPU totals omit some short-lived
threads; they are activity evidence, not exact per-stage resource accounting.

Machine: dev002, Ryzen 9 5950X, 32 logical CPUs, 128 GiB RAM, Clang 18, PHP 8.5.
The fixed converted compiler workload is the same isolated `base/input` directory
used by the earlier performance audit. The compiler being measured is rebuilt from
the current concurrency candidate. Native runs do not overlap their Clang build.
The timing benchmark completed before additional native-build and sanitizer diagnostics overlapped.

The original candidate's one-worker run passed in 58.63 seconds / 986,428 KiB RSS;
its two-worker crash invalidates it as a concurrency result. Corrected candidate-07
results follow; the earlier failure is retained only as diagnostic history.

The preceding partition change's 38.50-second number measured only a parallel
specialization compile replay. It is not the full production build time. The first
full native build of this larger partitioned candidate took 824.99 seconds with the
serial production builder, including validation/layout work. These scopes must not
be conflated; native C++ build scheduling is a separate remaining bottleneck.

## Candidate 07 measured scaling

Same O2 native compiler and fixed converted-compiler input on dev002; wall time
includes initialization, frontend synchronization, preparation, rendering/assembly/
publication and shutdown. Clang compilation is separate.

| Workers | Total wall s | Frontend s | Preparation s | Emission + publication s | Peak RSS KiB |
| --- | ---: | ---: | ---: | ---: | ---: |
| 1 | 61.05 | 4.94 | 8.12 | 47.68 | 988,496 |
| 2 | 52.49 | 2.53 | 6.59 | 43.04 | 989,604 |
| 32 | 43.20 | 1.02 | 5.12 | 36.77 | 994,136 |
| 32 repeat | 42.30 | 1.05 | 5.08 | 35.89 | 994,544 |

The 32-worker run is 1.41x faster than one worker (29.2% less wall time);
the repeat gives 1.44x. Total CPU use rises from 100% to 190–195%, so this is not
near-linear scaling across 32 CPUs. Serial contract settlement and artifact assembly
remain substantial. The 100ms sampler observes 32 active rendering workers but
misses short preparation bursts. A separate 5ms activity-only run observes 30 active
preparation workers and 32 active emission workers, with 33 live threads including
the coordinator. That diagnostic overlaps other verification and is not a timing
benchmark. System time remains about 22 seconds with roughly 16 million minor page
faults; allocation/system overhead merits profiling before claiming CPU saturation.

All 6,531 generated C++/header files match byte-for-byte between one and two workers.
The 32-worker output differs in a small set of callable adapters: source-type numeric
allocation IDs appear in callable specialization names, affecting two function
facades, a runtime call adapter, layout text and their implementation buckets.
Five paths are renamed; this is not evidence of omitted definitions. Stable output
names independent of registry allocation order remain a reproducibility/cache issue;
full byte identity across worker counts is not claimed.

The 32-worker sanitizer fixture passes both stages. Ordinary and concrete native
sample outputs both execute as `7:10`. The compiler generated at 32 workers also
builds and runs, compiles the sample, and its output executes as `7:10`
(`generated_compiler_proof.py`, `generated-compiler-result.json`). The complete
compiler workload passes ThreadSanitizer at 32 workers (671.833 seconds, exit 0;
`acceptance-commands.json`). This is dynamic evidence for the exercised workload,
not a whole-program proof for every possible input. The first generated-compiler
execution attempt compiled successfully but used the original fixture's hard-coded
local runtime-metadata path on dev002. The corrected proof changes that fixture
entry at source level to read `runtime.txt`, then regenerates it; benchmark inputs
and timings are unchanged.

## Generated-file size watch

Thresholds are advisory: flag `.cpp` or `.hpp` over 200,000 bytes, and headers over
100,000 bytes. `file_sizes.py` records actual bytes and per-unit Clang measurements.
Candidate 07 has 16 `.cpp` files above 200 KB: two middleware layout probes and
14 generated application units (the compiler itself). Largest:

| Generated artifact | Size bytes | Clang wall s |
| --- | ---: | ---: |
| specializations/layout.cpp (probe) | 3,594,653 | 4.84 |
| 03_parse/parser.phs implementation | 1,091,499 | 7.01 |
| 03_parse/structures/structures_specialization.phs implementation | 984,266 | 4.32 |
| specializations/layout_1.cpp (probe) | 811,865 | 3.12 |
| 05_backend/cpp/generate.phs implementation | 659,750 | 4.27 |

The largest header is Parser_Run at 58,472 bytes; none exceeds 100 KB.
The slowest regular O2 compile is runtime/include/core/runtime.cpp: 18.57 seconds,
383,644 KiB peak RSS. Its authored body is only 482 bytes, illustrating the importance
of includes. The slowest specialization bucket is containers_b7f.cpp: 8.03 seconds,
306,124 KiB RSS and 82,260 bytes. Parallel compile wall times include CPU contention;
layout probes run serially before the 32-worker compile pool. These figures are
separate from the earlier serial production-build timing.

The final external O2 build compiles 505 units and links in 45.66 seconds with
32 jobs; three layout-probe builds add 9.84 seconds before that pool. The separate
instrumented compile/link takes 47.48 seconds. This replay validates manifest hashes
and rebuilds layout probes, but omits the production builder's dependency-cache
discovery and template-free token validation; it is not a replacement measurement
for that still-serial production workflow.

Remaining performance work: profile serial artifact assembly and allocation costs;
split oversized source implementation groups and layout probes at their existing
artifact owners; stabilize callable specialization names. No new language semantics
or automatic file-size rejection is introduced by this slice.
