# Shared-handle completeness and native accounting comparison
Doc Status: planning

Date: 2026-10-16. This implements the bounded refinement proposed by the
[header fan-out investigation](accounting_header_fanout_20261016.md).

## Implementation and scope

The C++ backend's existing `cpp_type_requirement` now has a `stored_value` role.
For a nominal shared handle, `CPP_Types::representation_for()` satisfies it using
a payload forward declaration; other representations retain complete definitions.
Implementation parameters/results, sequenced call temporaries/default arguments,
and local storage use this role. Explicit payload operations continue to require
complete definitions. Template payloads, callable payloads and value modifiers
retain their conservative existing requirements.

There is no source grouping, expression, ABI, runtime, Ninja, Mold or compiler
incremental/MT change. Unit splitting and multi-file classes stay deferred. The
accounting publication still has 292 artifacts and 93 generated translation units;
90 artifacts differ from the previous policy only in includes/forward declarations.
Generated content is 2,387,633 bytes versus 2,389,172 previously. The application
remains 528,088 authored bytes / 93 PHS files.

## Validation protocol

The focused `tests/shared_handle_completeness.py` proves cross-file shared identity,
last-owner destruction, construction/member access/upcast dependency retention,
independent value copies and conservative template payloads. Both baseline and
layout-edited generated programs compile and execute. Only the Payload type header
changes between those publications; handle-transfer units have no direct or
transitive dependency on it. Five related PHP suites also pass: constructed_type,
reference_model, inheritance, destructors and callable_variance. These are focused
proofs, not a claim of an exhaustive language regression run.

Two initial harness-only repairs preceded the successful focused test: the
multi-source CLI needed an explicit main entry, and a forward declaration supplied
through a callable header need not be repeated directly in the unit. No compiler
implementation change was needed for either repair.

The native compiler is rebuilt through the existing portable-PHP conversion and
legacy S2S pipeline at O2 with debug information, using the existing clang-o2 wrapper
and --no-stan policy. Benchmark settings remain: dev002 Ryzen 9 5950X native Linux,
Clang 18.1.3, application O0, compiler jobs 12, Ninja jobs 8, Mold 3.0.0 / 8 threads,
runtime-only PCH, profiling disabled and normal OS filesystem caching. Each
application build starts a fresh native compiler process; Ninja owns cross-run
native reuse. Compiler retained-incremental performance is not measured here.

The application-only matrix has three trials, each with a full build, baseline
unchanged run, and four edits (two bodies, signature plus callers, local class
layout, common declaration), each followed by unchanged/revert/revert-unchanged.
Runtime-header and runtime-implementation edits are excluded as agreed: these are
toolchain-maintenance cases, not ordinary application edits. A separate runtime
copy is held unchanged. Each successful build executes the 1,855 existing checks.

## Native results

All **54 builds**, **51 exact dependency-set checks**, **3 complete PHP/native
publication comparisons**, and **1,855 self-tests per build** passed. All 27
unchanged runs launch zero Clang/PCH/link actions. Edits and their reverts rebuild
identical object sets in all three trials. Accounting/runtime Git trees are clean.
The native compiler source inventory matches all 214 archived file hashes; all 195
previously fingerprinted runtime files are unchanged. The new identity record also
fingerprints nine metadata files.

Times below are seconds from the actual median-total trial for each case. Memory
is the worst sampled process-tree RSS across its three trials. Compile elapsed is
the union of active Clang/PCH intervals, not their sum. PCH creation is included
in the full-build compile column; it is reused for every application edit.

| Case | Before total | After total | Rebuilt / reused | C++ ready | Compile elapsed | Link | Other | Peak tree MiB |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| Full build | 11.758 | **11.751** | 96 / 0 | 0.746 | 10.568 | 0.061 | 0.376 | 1532.6 |
| Unchanged | 1.052 | **1.049** | 0 / 96 | 0.772 | 0.000 | 0.000 | 0.278 | 74.8 |
| Two body edits | 1.544 | **1.554** | 1 / 95 | 0.777 | 0.352 | 0.054 | 0.372 | 297.4 |
| Signature + callers | 4.034 | **4.041** | 5 / 91 | 0.779 | 2.836 | 0.053 | 0.373 | 867.5 |
| Local class layout | 3.938 | **4.031** | 5 / 91 | 0.784 | 2.833 | 0.047 | 0.367 | 866.8 |
| Shared declaration | 9.450 | **3.771** | 20 / 76 | 0.773 | 2.596 | 0.045 | 0.357 | 1528.3 |

“Other” is the residual between invocation and process exit after removing
request-to-C++-ready, active compilation and linking. It includes distributed
identity/coordination/status work and final exit; it is not all post-link work.
No standalone dependency-scan or PCH-validation commands are added.

| Case | Setup | Sync/parse | Prepare | Emit/publish | Exe ready | Compiler peak MiB |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| Full build | 0.008 | 0.060 | 0.439 | 0.238 | 11.672 | 69.5 |
| Unchanged | 0.018 | 0.072 | 0.440 | 0.241 | Already present | 69.8 |
| Two body edits | 0.018 | 0.072 | 0.442 | 0.243 | 1.477 | 70.0 |
| Signature + callers | 0.020 | 0.070 | 0.444 | 0.243 | 3.959 | 70.3 |
| Local class layout | 0.021 | 0.073 | 0.446 | 0.243 | 3.958 | 69.8 |
| Shared declaration | 0.021 | 0.070 | 0.439 | 0.242 | 3.705 | 69.5 |

Setup/sync/preparation/emission markers have filesystem timestamp quantization;
request-to-C++-ready also includes the small process-start-to-first-marker interval.
Executable readiness is the successful link-driver exit. Unchanged runs already
have the executable; validation still takes the reported total invocation time.

## Rebuild behavior and interpretation

The shared AccountingCheck layout edit now rebuilds exactly the **20 required
payload consumers**, reusing **76 objects** and the PCH. The old count was 89
rebuilt / 7 reused. Both workflow_checks and closing_checks stay reused in this
case. The affected set matches the independent forward-declaration investigation.
Other application cases retain their previous sets: one object for the two body
edits and five for signature/caller or local CustomerProfile layout edits.
Full builds still compile 93 generated plus 3 runtime-provider objects and one PCH.

The shared-declaration total falls **9.450 → 3.771 seconds (60.1% less)**; its
active compilation falls **8.297 → 2.596 seconds**. The executable is ready at
**3.705 seconds**, with approximately 0.067 seconds left before process exit.
The three totals are 3.732–3.921 seconds. Full builds and body edits are essentially
unchanged in this comparison; local class-layout timing is slightly higher, so
this is not a claimed universal compile speedup. No claim is made about execution
speed beyond preserving generated expressions/ABI and passing behavioral checks.

Shared-edit peak RSS is **1,528.3 MiB**, versus **1,507.3 MiB** previously: there
is no measured peak-memory reduction. Eight substantial compilations can still
run together even though total work is much smaller. Tree RSS is sampled around
every 10 ms, double-counts shared pages and can miss short peaks; it is not PSS.
Compiler-only peak is reported separately. Normal Mold forking remains enabled.

| Case | Unchanged after edit | Revert | Unchanged after revert |
| --- | ---: | ---: | ---: |
| Two body edits | 1.039 | 1.542 | 1.062 |
| Signature + callers | 1.054 | 3.926 | 1.059 |
| Local class layout | 1.048 | 4.093 | 1.019 |
| Shared declaration | 1.045 | 3.760 | 1.045 |

These are three-trial observations compared with the preceding same-day baseline,
not interleaved old/new trials or guarantees for arbitrary workloads. Profiling is
disabled, so the compile column combines Clang frontend and LLVM backend work.
No runtime edit, unit splitting, shared-library packaging or compiler incremental/MT
internal change is included. The local compiler rebuild itself took 672.23 seconds
through the legacy build pipeline; that is setup cost, not application edit latency.

[Evidence and reproduction notes](shared_handle_completeness_20261016/README.md).
