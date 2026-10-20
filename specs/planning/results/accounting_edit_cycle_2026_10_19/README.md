# Accounting: one-function edit to executable ready
Doc Status: supporting

2026-10-19, revision `1094a104`. The benchmark changes the description literal in
`check_bank_flow` in `src/closing_checks.phs`, with three distinct revisions so
previously cached app objects cannot satisfy later samples. All accounting behavior
checks still pass. Sources are temporary copies; the checkout's app is unchanged.

Scope update, 2026-10-20: these PHP-hosted timings are diagnostic only. The PHP
compiler is not the intended performance comparison, and incremental compilation
is unfinished. The follow-ups below are deferred, not current optimization work.
Current priority is solidifying preprocessed templates; see the
[non-blocking future review](../../runtime_prepared_facts_review_v0_3_2026_10_20.md).

## End-to-end results

Seconds, medians of three body edits after an initial build. The compiler retains
its AST/prepared state throughout a session. O0, no debug information. Ordinary
C++ uses runtime PCH; template-free C++ uses no PCH. Runtime/middleware/layout/PCH
artifacts are reused. Exactly one generated `.cpp` changes and one app object is
compiled on every edit. Header, middleware and layout-probe contents stay unchanged;
no layouts or middleware objects are rebuilt.

| Phase | Regular + GNU ld | Template-free + GNU ld | Template-free + mold |
|---|---:|---:|---:|
| Source synchronization/tokenization/parsing | 0.132 | 0.148 | 0.149 |
| Semantic preparation | 0.834 | 0.817 | 0.807 |
| C++ emission and publication | 0.375 | 1.546 | 1.498 |
| Clang dependency scans | 2.420 | 1.572 | 1.569 |
| PCH validation | 0.059 | 0.000 | 0.000 |
| Changed-unit compilation | 3.007 | 0.272 | 0.274 |
| Linking | 0.515 | 0.490 | 0.109 |
| Tool version query | 0.032 | 0.032 | 0.033 |
| Other native-build overhead | 4.242 | 3.576 | 3.575 |
| **Edit → executable ready** | 11.588 | 8.449 | 7.990 |

The total starts at writing the edit and ends when the native builder publishes its
ready executable. It excludes executable execution, editor/watch delivery latency,
compiler process/bootstrap/initialization and the initial build. Medians of individual
phases need not add exactly to the median total. A roughly millisecond-scale fixture
artifact-hash snapshot between emission and native building is excluded from the
total; raw `measured_total` and `observer_gap` retain both observations.

`build_other` is the residual native-build wall time after recorded Clang child
processes. It includes input/dependency hashing, cache/manifest validation, cache
publication, process orchestration and recorder startup. It is not a CPU profile or
proof that any one component is responsible for all of that residual.

## No-change invocation

One sample per mode, following the three edits. Each has zero compilation/linking.

| Mode | End-to-end no-op | Native build part | Dependency scans |
|---|---:|---:|---:|
| ordinary-gnu | 6.808 s | 6.571 s | 21 |
| prepared-gnu | 5.912 s | 4.956 s | 25 |
| prepared-mold | 5.991 s | 5.018 s | 25 |

The default build does fresh dependency discovery even when objects are reusable.
The existing opt-in `runtime_snapshot` mode was not enabled or benchmarked here.
It has a separate pinned-runtime contract and should not be assumed safe as a
transparent replacement for normal dependency invalidation.

## Isolated linker comparison

Same final template-free objects, three alternating rounds. Includes Clang driver
and linker process wall time; excludes compilation, dependency-file generation,
build validation, execution and recorder-proxy startup. Every output is executed
and passes all 998 checks. This measures full relinking, **not incremental linking**.

| Linker installed | Median |
|---|---:|
| GNU ld 2.42 | 490 ms |
| mold 2.40.4 | 109 ms |
| LLD 18.1.3 | 123 ms |

The end-to-end linker phase retains the builder's dependency-file generation, so
its timing is not expected to equal this isolated probe exactly. Mold is selected
by a test-only compiler recorder replacing the existing hard-coded GNU linker flag.
Each variant has a separate cache and fixed recorder executable. This is a measured
experiment, not a new production linker-selection setting.

## Potential follow-ups (deferred)

1. Profile native-build validation/dependency work, especially the no-op path. Keep
   correct invalidation for runtime/header changes; separate correctness from cache
   policy before reducing scans or hashing.
2. Profile prepared C++ emission/publication and preparation for a body-only edit.
   Exactly one changed output does not imply that producing/validating it was cheap.
3. Add explicit linker selection through the existing native-build configuration,
   including actual linker identity in cache keys and dependency tracking. Mold/LLD
   can reduce the link phase without waiting for incremental linking.

The earlier roughly 10x unit-compilation gain is confirmed, but the current complete
edit cycle contains substantial unoptimized work around that unit. These measurements
describe this experimental PHP-hosted path, not the intended compiler performance
or a requirement to optimize it now.

## Method and limits

- WSL2 Ryzen 5 PRO 4650G, 6 cores/12 logical CPUs; Clang 18.1.3.
- Existing `Compiler::sync`, `prepare(true)`, `cpp(publication)` phases, matching
  `update_cpp`, then `Native_CPP_Build`. No duplicate compilation pipeline.
- PHP-hosted retained compiler, CLI execution. No native compiler rebuild or native
  compiler-host performance claim. All scenarios execute serially without overlapping
  proof jobs. External machine load is not controlled; mode order is fixed for the
  retained-session runs, while isolated linker order alternates.
- Instrumentation uses a Python compiler recorder. Its startup is included in the
  residual/full edit time; therefore these are instrumented full-cycle times, not
  precise uninstrumented CLI latency. The recorder does not change compile flags.
- All five executables per mode (initial, three edits and no-op), plus nine isolated
  linker outputs, pass 998 checks with zero failures.
- The initial builds are setup, not controlled cold-cache benchmarks. No O2, LTO,
  source semantics changes, native cache bypass or production optimization was made.

[Summary](summary.json), [regular raw data](ordinary-gnu.json),
[template-free GNU raw data](prepared-gnu.json), [mold raw data](prepared-mold.json),
[isolated linker samples](linkers.json). The checked-in host fixture and runners
retain local paths. Full compiler-command logs and source/publication/cache artifacts
are under `/tmp/accounting-edit-cycle/`.
