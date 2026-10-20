# Native linker configuration acceptance — 2026-10-16
Doc Status: supporting

Base revision `86aed366` plus the shared linker configuration changes in this checkpoint. The [native build contract](../architecture/native_cpp_build.md#linker-selection-and-identity) owns the interface. Exact authored native-source hashes, candidate implementation patch and rebuilt native executable identity are archived in [the evidence](native_linker_configuration_20261016/README.md).

## Implementation

- `native_cpp_toolchain` owns `linker` (absolute executable, default `/usr/bin/ld`) and `linker_threads` (nonnegative, default 0). Both host entrypoints expose `--linker=PATH` and `--linker-threads=N`. Zero leaves the linker default; positive counts emit `-Xlinker --threads=N`, tested with Mold 3. GNU BFD uses zero.
- Shared recipes add these only to linking. Ninja fingerprints selected-linker resolved path and contents in its link-only identity file; configuration changes also alter the link command. The content-addressed consumer separates linker identity from its compilation key and includes it in its link key.
- Compilation key namespace v2 gives the content-addressed consumer a one-time miss for old entries. Subsequent linker changes preserve objects/PCH. Ninja directories need no migration.
- Compiler incremental/MT internals, grouping, PCH contents and application compile flags remain unchanged. Production selection no longer depends on the timing wrapper substituting linkers.

## Proof

`tests/linker_configuration.py` passed **26 cases across both consumers** (PHP-hosted orchestration, real Clang/linker execution). Cases cover cold/no-op GNU builds, Mold selection, thread changes/revert, same-path replacement with preserved mtime, same-byte symlink retargeting, failed linking, recovery and GNU revert. Only cold builds create PCH/objects; linker-only changes do not. Historical executable reuse on a revert is asserted for the content-addressed consumer; Ninja follows its last successful command/identity.

The compiler and native Ninja worker were rebuilt at `-O2 -g` using the existing converter/framework and legacy S2S with `--no-stan`. The conversion and native build passed without further authoring repairs. The rebuilt binary and matching runtime were copied to dev002. There, **14 native configuration cases** passed, including one intentional failing linker: pending readiness was observed, then retry recovered. Each configuration change caused exactly one link and zero object/PCH builds; unchanged settings launched no tools. The timing wrapper records argv and adds no flags; checks assert recorded arguments equal executed arguments.

All successful accounting builds and restoration builds passed 998 baseline + 857 large-fixture checks. All six full native publications matched the independent PHP reference across 292 artifacts. Fixture changes were restored through Git. This is bounded native Ninja/linker acceptance, not a new exhaustive compiler incremental/MT checkpoint or a native execution proof of every content-addressed-builder case.

## dev002 measurements

Ryzen 9 5950X, 32 logical CPUs, native Linux. Same 528,088-byte / 93-file accounting fixture, application `-O0`, runtime PCH, Clang 18.1.3, Ninja 1.11.1, GNU BFD 2.42 and Mold 3.0.0 as the [previous comparison](native_ninja_mold_20261016.md). Full builds use fresh directories with OS cache allowed; every case starts a fresh native compiler process. Full/unchanged/body comparisons use Ninja 8 jobs; scaling uses 12. Mold uses 8 threads throughout measured cases. Each case has three trials.

| Linker | Case | Median total | Range | Peak sampled tree MiB |
| --- | --- | ---: | ---: | ---: |
| gnu | full | 12.057 s | 12.002–12.072 s | 1530.0 |
| gnu | unchanged | 0.859 s | 0.850–0.873 s | 75.3 |
| gnu | body-edit | 2.232 s | 2.175–2.247 s | 341.0 |
| mold | full | 11.762 s | 11.728–11.787 s | 1521.8 |
| mold | unchanged | 1.046 s | 1.031–1.052 s | 70.8 |
| mold | body-edit | 2.015 s | 1.998–2.053 s | 306.1 |

Mold stage rows below use the actual median-total run, rather than mixing component medians. Compile elapsed is the union of overlapping PCH/object intervals. C++ ready is cumulative; Other is distributed orchestration/validation plus process exit, not solely post-link work.

| Case | C++ ready | Compile elapsed | Link | Other | Link complete / exe available | Total |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| full | 0.772 | 10.549 | 0.062 | 0.380 | 11.673 | 11.762 |
| unchanged | 0.767 | 0.000 | 0.000 | 0.279 | — | 1.046 |
| body-edit | 0.793 | 0.799 | 0.054 | 0.370 | 1.939 | 2.015 |
| eight-body-j12 | 0.764 | 0.834 | 0.051 | 0.369 | 1.940 | 2.017 |

All stage values are seconds. Full detailed setup, sync/parse, preparation, emission/publication, readiness and memory observations remain in summary.json. Compilation here combines frontend/backend; primary timings have profiling disabled.

Proper validation now hashes the actual approximately 64 MiB Mold binary each invocation. The old benchmark wrapper hid Mold from the builder, which instead hashed GNU ld. Compared with that checkpoint, distributed native overhead grows by roughly 0.2 s, consistent with the new identity observation; that attribution is an inference from the stage delta, not an isolated hashing microbenchmark. Thus the old ~1.78 s body-edit timing is not the cost of fully validated production selection. Tool hashing remains content-based, including preserved-mtime replacement. No binary stripping, metadata-only shortcut or tool-setting change was introduced to suppress this cost.

## Scaling

| Batch, Ninja 12 jobs | One-edit median | Eight-edit median | Eight compile elapsed | Eight summed Clang time | Peak parallel compiles | Eight peak tree MiB |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| Original + seven checks | 1.991 | 2.017 | 0.834 | 3.194 | 8 | 1338.8 |
| Eight business classes | 1.526 | 1.549 | 0.391 | 2.935 | 8 | 1365.5 |

Every eight-edit case rebuilt 8 objects, reused 88, reused PCH and linked once. The batches and Git-restoration procedure are identical to the previous comparison. These concrete body-only batches overlap successfully; header changes and long individual compilations have different critical paths.

Peak memory uses nominal 10 ms process-tree RSS sampling, with shared pages counted more than once and possible missed short peaks. Mold default fork behavior is unchanged; wrapper wait4 fields alone are not total linker CPU/RSS. See the previous separately labelled no-fork resource replay for that distinction. Timestamp-marker quantization applies. Toolchain shared-library/search-state completeness remains the documented existing cache limitation.

CH15-MOLD-01 now records the implemented configuration/invalidation slice; larger-input tuning and executable/shared-library layout experiments remain deferred.
