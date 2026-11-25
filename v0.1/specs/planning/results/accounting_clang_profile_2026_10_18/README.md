# Accounting Clang profiles and follow-up probes
Doc Status: planning

This experiment profiles the whole-accounting concrete conversion and selected original generated units with an eagerly instantiated runtime PCH. It does not modify the production compiler or runtime. The inputs and behavioral scope are documented in [the conversion experiment](../accounting_template_free_2026_10_18/README.md).

## Method

`profile.py` runs Clang with `-ftime-trace -ftime-trace-granularity=100`, C++23, `-fPIC -g -pipe`, at O0 and O2. It profiles all 15 concrete application units and three concrete support units; original/PCH profiling covers ledger, money and checks. PCH creation uses `-fpch-instantiate-templates`, as does consumption. Raw traces are compressed in `traces/`; `profile.json` retains commands, phase totals and expensive individual events. The original aggregate headers `broad.hpp` and `f74b50e9c799e8a2.hpp` were byte-identical when profiled.

Trace timings are diagnostic, not replacements for the uninstrumented compilation benchmark. Categories overlap: Source, ParseClass, instantiation and code generation can nest inside Frontend; optimizer passes nest inside Backend. Do not sum those categories. Event counts describe Clang work, not unique source-language functions.

`probes.py` measures three serial repetitions, varying order, without trace instrumentation. It compares the concrete build with its existing broad aggregate header, fewer project headers, and full headers with `-g0`. Every build includes all three support units, links and runs the complete 998-check workload. Header pruning greedily removes aggregate project includes only when the unchanged source still passes Clang syntax checking. This is an experimental dependency oracle, not a proposed production algorithm. The common `concrete.hpp`, containing all concrete handle/container declarations, remains exposed in every variant that needs it. Support source includes are unchanged. Lowering and header discovery are excluded from compilation timings.

Reproduce from repository root:

```sh
python3 specs/planning/results/accounting_clang_profile_2026_10_18/profile.py
python3 specs/planning/results/accounting_clang_profile_2026_10_18/probes.py
```

## Trace findings

Across all 18 concrete native units, O0 records 1.847 s frontend and 0.953 s backend; O2 records 1.838 s frontend and 4.573 s backend. Header processing accounts for 1.322 s at O0 and 1.296 s at O2, inside the frontend totals. The backend therefore becomes the larger component at O2 after the template-heavy representation is removed.

For ledger.cpp at O0:

| Clang work | Improved runtime PCH | Concrete |
|---|---:|---:|
| Frontend | 1,438 ms | 106 ms |
| Backend | 525 ms | 59 ms |
| Header processing | 479 ms | 75 ms |
| Function code generation | 404 ms | 18 ms |
| Function code-generation events | 2,127 | 144 |
| Debug type processing | 137 ms | 3 ms |

These are overlapping diagnostics. Original expensive instantiations include hash indexing and insertion for integers, booleans and LedgerAccount handles. Template removal is not merely avoiding parsing: the representation also reduces generated wrapper machinery that must pass through code generation, debug processing and LLVM.

The concrete containers.cpp at O2 records 166 ms frontend and 1,691 ms backend, including 811 ms optimizer and 876 ms code-generation passes. Its expensive work is spread across concrete hash/vector functions, rather than one pathological source expression. Cache these stable implementations and choose specialization boundaries carefully; these traces do not justify implementing an LLVM optimizer inside the incremental compiler.

## Ownership implications

1. **Specialization and reusable implementation placement:** retain specialization identity and capability dependencies in the existing semantic model; let the C++ backend choose concrete bindings and where their implementations live. Reuse compiled support implementations when their semantic/representation dependencies have not changed. Avoid emitting every wrapper implementation into every consumer.
2. **Declaration versus layout dependencies:** extend the existing backend representation requirement machinery, rather than creating another type registry. `compiler/my-try/05_backend/cpp/types.php` already distinguishes complete types from forward declarations. Applied runtime template arguments currently conservatively require complete payloads. Concrete handles can permit a narrower truthful contract, provided operations requiring payload layout/destruction still depend on it.
3. **Generated-header granularity:** expose only concrete types and operations actually needed by a unit. The experimental aggregate concrete header itself remains a repeated parsing cost. The project-header probe below measures only one part of this opportunity; it does not establish the gain from splitting that concrete header.
4. **Build policy:** debug information and optimization levels belong to native build configuration. Removing debug information is a diagnostic comparison with a debugging tradeoff, not a semantic lowering improvement or an automatic recommended default.

Ordinary RAII, exceptions, virtual dispatch and lambdas remain in the concrete build. No isolated evidence here justifies taking their complete lowering away from C++. Exception/lifetime machinery and repeated inline definitions may deserve later focused experiments, but the current data cannot assign them independent costs.

The measured application is the existing generated accounting sample plus an experimental conversion, not a measurement of my-try's current partitioned emitter. The my-try dependency model already addresses part of the broad-header problem; this report identifies how concrete representations could use that owner more precisely.

## Controlled probe results

Median seconds, three repetitions, compilation of all 18 units; linking is measured separately in `probes.json`. All 18 resulting executables passed 998 checks each. No PCH is used in these concrete variants.

| Concrete variant | O0 compile | O2 compile |
|---|---:|---:|
| Broad project header, full debug | 3.403 s | 6.910 s |
| Narrower project headers, full debug | 3.213 s | 6.724 s |
| Broad project header, no debug | 3.017 s | 5.816 s |

The header-only change saves 5.6% at O0 2.7% at O2 over this aggregate-header baseline. Disabling debug information saves 11.4% at O0 15.8% at O2 but sacrifices debug information. These are modest gains beside the earlier specialization experiment. They are measured separately, and their benefits must not be added without a combined experiment.

`environment.json` records Clang version and all 41 converted input hashes, verified identical to the previous whole-app benchmark. Trials were serial on this machine, with warm filesystem caches; three repetitions do not establish performance across machines or workloads. Runtime throughput is not measured.
