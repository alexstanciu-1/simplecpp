# Compiler performance goal — first measured slices
Doc Status: planning

Baseline: `fceb29c2e`. This records the first slices; subsequent production
native-build concurrency and completed goal evidence are in the
[native pool report](../compiler_native_pool_2026_10_25/README.md).
No language semantics, compound-assignment lowering, runtime string
semantics or filesystem publication protocol changed in these slices.

## Reuse and changes

1. `Output_Publication` keeps ordered manifest rows in an existing typed
   `vector<string>` and joins once through portable `q_implode`. This removes
   repeated growing-prefix copies from native `.=` lowering. Artifact checks,
   digests, preflight, recovery journal, writes and ready publication retain order.
2. `CPP_Layout_Depths` is an invocation-local backend worker extracted from
   `CPP_Specializations`. It owns calculation maps instead of repeatedly passing
   the whole known/depth maps by value. Existing graph traversal, depth rules,
   reached-header filtering and cycle diagnostics are preserved. It retains no
   semantic facts, references to preparation workers or shared mutable state.

## Measured results

O2 native compiler on dev002 (Ryzen 9 5950X, 32 logical CPUs), the same fixed
converted compiler workload as the preceding partition report. Benchmarks do not
overlap Clang builds. Wall time covers compiler execution and publication; it
excludes compiling emitted C++ with Clang.

| Candidate | Workers | Wall seconds | Peak RSS KiB |
| --- | ---: | ---: | ---: |
| Partition baseline | 32 | 43.36 | 1,011,656 |
| Manifest join | 1 | 33.97 | 1,004,632 |
| Manifest join | 32 | 17.18 | 1,008,784 |
| Manifest join repeat | 32 | 17.26 | 1,010,112 |
| Manifest + layout maps | 1 | 29.45 | 1,006,320 |
| Manifest + layout maps | 8 | 13.22 | 1,005,604 |
| Manifest + layout maps | 32 | 12.56 | 1,012,204 |
| Manifest + layout maps repeat | 32 | 12.54 | 1,008,004 |

At 32 workers, manifest inventory/assembly falls from 27.09 to approximately
1.2 seconds. Layout dependency calculation falls from 4.76 to 0.015 seconds.
Frontend is approximately 1.0 second; preparation 5.1 seconds; parallel rendering
1.06 seconds. Rendering takes 11.15 seconds at one worker. Remaining serial phases
limit overall scaling; CPU count alone does not establish effective concurrency.

The one-worker full-workload manifest is byte-identical before and after the
layout extraction, including every generated artifact digest and include list.
The candidate compiler and generated fixed workload have no CPP/HPP file above
200,000 bytes: maxima 180,095 bytes CPP and 58,472 bytes HPP.

## Validation

- Publication adapter/recovery/locking/CLI suite and converted native facade pass.
- Exact manifest serialization fixture passes, including artifact order/newline.
- Six retained concurrency/source/closure/trait/recovery checks pass.
- Layout dependency test covers reached headers, irrelevant cycles, real cycle
  rejection and successful retry after failure.
- Both production slices convert and the resulting compiler builds at O2.
- First layout conversion attempt rejected unannotated empty-map reset; fixed with
  supported explicitly typed locals. No converter or language change.

The rebuilt native compiler compiles and runs the partition fixture in ordinary
and concrete modes (expected output `71`). A 10 ms `/proc` sampler observes CPU
activity in up to 32 preparation and rendering workers. An initial 100 ms sampler
underreported preparation by discarding CPU accumulated before a new thread's
first observation; both sampling results remain in the summary. Sampling establishes
overlap, not continuous utilization or race freedom. Prior concurrency ThreadSanitizer evidence remains tied to
its prior revision; no new sanitizer claim is made here.

## Next costly owner: native build concurrency

The production `Native_CPP_Build` synchronously invokes dependency scans, token
validation and object compilation. Its per-call `invoke_program()` polls one
process to completion, so compiler preparation/rendering jobs do not parallelize
Clang. The preceding full production build took about 825 seconds; external replay
of 32 Clang jobs is a different recipe and excludes some production checks.

Bounded next slice: within the native owner, reuse spawn/poll/close and output
staging for a bounded, continuously refilled set of independent object actions.
Keep dependency-cache mutation, pending/ready publication, diagnostics order and
link membership coordinator-owned. Preserve required dependency scans and strict
application token validation. On failure, stop/join child processes before closing
output stages or releasing locks; do not publish an executable as ready. Test
jobs=1 versus multiple jobs, actual overlap, cache reuse, failure/retry and generated
execution, then measure the complete production recipe.

Layout probes remain ordered prerequisites. Parallel same-depth probes would need
an explicit backend-to-publication-to-native dependency contract; it is a separate
cross-owner proposal, not permission to infer dependencies from filenames or skip
layout validation. Frontend language contracts and task admission are non-goals.
