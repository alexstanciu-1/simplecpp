# Generated C++ partitioning and remaining serial costs
Doc Status: planning

Base: `ffbd9ced7`; candidate is the backend partitioning change accompanying this
report. dev002: Ryzen 9 5950X, 32 logical CPUs, Clang 18, O2. No language, runtime,
converter, publication protocol or native scheduler changes are introduced.

## Implemented ownership and limits

`CPP_Implementation_Groups` indexes prepared owner membership once instead of
rescanning all owners for every file. It collects complete definitions with their
include/forward-declaration facts. Small units retain their existing paths.
Oversized units recursively partition whole definitions by stable identity digest
prefix, targeting 180,000 bytes including the preamble. Type-owned static storage
stays together in the original source unit, in its original order. Function bodies
and bridges stay together. No text slicing, source-AST rewriting, retained grouping
registry or semantic changes are involved. Adding/removing a member only affects
its bucket unless a size threshold splits/merges that branch. A single oversized
function or ordered-storage group is deliberately left intact for explicit review.

`CPP_Specializations` collects bounded layout batches within the already computed
dependency depth. Complete layout requests remain indivisible. Manifest order is
still depth first, then part order. The existing native consumer includes all prior
resolved headers and executes probes serially. Part identities can shift after
add/remove edits within a depth; no new cache or parallel layout protocol is claimed.

## Generated sizes

The candidate compiler's generated output and all three fixed-workload benchmark
outputs have **zero `.cpp` or `.hpp` files over 200,000 bytes**. No header exceeds
100,000 bytes. The prior candidate had 16 oversized `.cpp` files.

| Measure | Before | Partitioned candidate |
| --- | ---: | ---: |
| Largest generated C++ file | 3,594,653 bytes | 180,095 bytes |
| Largest header | 58,472 bytes | 58,472 bytes |
| Regular compile units including runtime | 505 | 734 |
| Layout probes | 3 | 27 |
| Maximum layout-probe compile | 4.83 s | 1.88 s |
| Sum of serial layout-probe compiles | 9.82 s | 27.24 s |
| External 32-job O2 compile + link pool | 45.66 s (prior full build) | 47.98 s |

**Tradeoff:** the external compile/link pool stays near its previous time, but
serial layout work increases by 17.4 seconds. Combined layout + pool cost is about
75.2 seconds versus 55.5 seconds previously. The smaller probes satisfy the size
and individual-job latency objective; they do not speed up a serial layout scheduler.
The slowest regular candidate compile is still unchanged runtime `core/runtime.cpp`
at 16.04 seconds / 385,684 KiB RSS. The slowest specialization bucket takes 7.22
seconds. Parallel-unit timings include CPU contention and are not isolated latency.

These are external manifest-based replay costs, excluding the production builder's
dependency-cache discovery/template-token validation. Do not equate them with a full
production cached build. PHP-hosted candidate publication succeeds in 42.208 seconds;
GNU time and peak memory are retained in `publish.time`.

## Native compiler timing

The same fixed converted-compiler input is used throughout; compiler snapshots
contain only additional portable filesystem timing markers. Markers are not checked
into production implementation. Main benchmarks do not overlap Clang builds.

| Native O2 compiler | Workers | Wall s | Peak RSS KiB |
| --- | ---: | ---: | ---: |
| Instrumented baseline | 32 | 43.97 | 995,240 |
| Partitioned candidate | 1 | 59.92 | 1,006,004 |
| Partitioned candidate | 32 | 43.36 | 1,011,656 |
| Partitioned candidate repeat | 32 | 43.03 | 1,011,196 |

The partitioned candidate remains roughly 1.38–1.39x faster at 32 workers than one.
Splitting alone does not materially improve whole-compiler wall time.

Representative 32-worker subphases:

| Phase | Baseline s | Partitioned s |
| --- | ---: | ---: |
| Frontend sync | 1.00 | 1.03 |
| Preparation | 5.16 | 5.23 |
| Parallel rendering and joined merge | 1.08 | 1.09 |
| Header assembly | 0.41 | 0.41 |
| Implementation-group assembly | 0.90 | 0.81 |
| Layout dependency resolution | 4.77 | 4.76 |
| Specialization ordering | 0.49 | 0.49 |
| Specialization/header/layout-text assembly | 8.08 | 0.067 |
| Generation end through final publication | 21.03 | 28.41 |

Bounded layout-text assembly avoids repeatedly copying a multi-megabyte growing
probe and removes about eight seconds from that phase. More implementation units
increase manifest size, and manifest construction absorbs that gain.

Candidate publication breakdown (includes hashing and path validation in its first
phase; the tiny runtime-build requirement query also precedes the first marker):

| Phase | Seconds |
| --- | ---: |
| Artifact inventory / hashing / manifest construction | 27.093 |
| Existing-output and collision preflight | 0.087 |
| Journal construction | 0.620 |
| Mark pending | 0.010 |
| Write generated files and retire obsolete owned files | 0.554 |
| Final manifest/status and coordinator completion | 0.044 |

This explains the limited scaling: workers overlap, but the parallel render interval
is only about one second while manifest construction alone is roughly 63% of total
wall time. Adding workers cannot remove that serial work. Filesystem writing is not
the dominant cost in this fresh-output workload.

The relevant source path is `Output_Publication::publish()`, which appends every
row to a growing `$manifest`. `CPP_Generator::generate_compound_assignment()` emits
an owned old-value snapshot, computes a new value, writes back and returns a value.
Consequently `.=` is not lowered to the runtime's in-place `string_t::append` path.
Repeated appends make repeated whole-prefix copies; that is consistent with the
large allocation/system-time cost (about 22.7 s system time, 16.1 million minor
faults). The profile does not separately attribute every millisecond inside the
manifest loop to copying versus hashing/quoting; no unmeasured speedup is claimed.

**Recommended next bounded fix:** collect already ordered manifest rows in an
explicit `vector<string>` and join once with existing portable `q_implode`. Preserve
exact manifest bytes, error checks, preflight order, journal-before-write and final
ready publication. This is source-level assembly work; it requires neither parallel
filesystem publication nor a changed `.=` language contract. Prove byte parity and
failure/recovery before timing it. This publisher change is not implemented here.

Next after that: profile layout-depth resolution (4.76 s), including read-only maps
passed by value and repeated whole-graph scans. Parallel same-depth native layout
builds would address the new Clang bottleneck, but require an explicit dependency
batch contract in the native consumer; they are not silently added here.

## Proofs and limitations

- Six retained-stage tests pass after partitioning: cold/warm output, edit/failure
  recovery, callable/trait instances, contextual closures and async retention.
- New 96-method class fixture verifies every implementation unit stays below 200 KB,
  warm bytes stay identical, one same-size method edit changes exactly one artifact,
  and static storage links once. PHP-hosted generation compiles/runs as `71` in both
  ordinary and concrete output modes.
- The rebuilt native compiler, at 32 workers, generates and executes that same split
  fixture in both modes, also producing `71`.
- Full compiler bootstrap compiles all 27 layout probes and 734 regular units,
  then the native compiler successfully republishes the fixed compiler workload.
- Project placement, traits and property-default checks pass. The older
  `abstract_dispatch.php` fails its uninitialized required-local positive case
  (`b`) identically with untouched HEAD backend files. Its expected-exit baseline
  probe is diagnostic evidence, not a passing abstract-dispatch suite.
- Initial implementation debugging found an index lookup for owners outside the
  emitted source set; collection now preserves the old membership filter. Native
  conversion then required explicit generic construction annotations and explicit
  object_cast views; these were source-only corrections.
- No new thread-shared mutation is introduced: partitioning and final assembly
  remain coordinator-owned. The prior concurrency commit's full ThreadSanitizer
  evidence is not relabeled as a sanitizer run of this candidate.
- Callable specialization naming still contains allocation-order IDs in a few
  cases, as documented by the preceding concurrency report. This change does not
  claim byte reproducibility across all native worker schedules.

The Python files preserve isolated server recipes and profiling markers. Results
and resource figures come from the shared CommandRunner/Task runner. Local FPM
startup was unavailable in the sandbox, so PHP checks used the normal CLI fallback.
