# Complex edits and PCH experiment — 2026-10-16
Doc Status: supporting

An integration experiment against compiler revision `407fdabf`, not a change to compiler incremental/MT internals or a new grouping policy. It extends the [production Mold acceptance](native_linker_configuration_20261016.md) using the **same native compiler executable** and tool settings. The [evidence](native_edit_matrix_20261016/README.md) includes scripts, source patches, complete rebuilt-object lists and per-run stage/memory results.

## Setup and method

- Host: dev002, Ryzen 9 5950X, 32 logical CPUs, native Linux. Compiler: existing `-O2 -g` native binary, compiler jobs 12; accounting app: `-O0`, Clang 18.1.3, **Ninja 8 jobs**, Mold 3.0.0 with **8 threads**, runtime PCH enabled, profiling disabled. These are the preceding primary-comparison settings; this experiment does not switch to 12 Ninja jobs.
- Accounting baseline: 528,088 bytes / 93 authored PHS files; 93 generated plus 3 runtime-provider objects, one runtime PCH and one executable. Each compiler invocation is a fresh native process; Ninja owns retained native outputs between invocations.
- Both the accounting fixture and runtime tree are separate copies in a new dev002 home directory. Each is tracked in its own Git repository. Runtime edits affect only the copied headers/provider sources used to build the accounting executable; the running native compiler uses its existing compiled runtime library. Shared repository/runtime files are untouched.
- Three sequential trials, each beginning with fresh publication/native-build directories. OS caches are not dropped. Each of six scenarios runs edit → unchanged → Git revert → unchanged, yielding **78 successful builds/self-tests** including baseline builds.
- Before each non-cold invocation, read Ninja’s recorded dependencies. Compare their intersection with changed published artifacts/runtime files, propagate a dirty PCH to its generated-object consumers, and assert the exact observed compile-output set. This validates scheduling against the existing dependency graph; it does not prove that the emitter’s include graph is semantically minimal.

## Edits

| Scenario | Authored change |
| --- | --- |
| Two method bodies | Add equivalent `+ 0` expressions to `CustomerProfile::available_credit()` and its copy assignment. |
| Signature + callers | Add a required integer parameter to `available_credit`, use it in the body, and pass zero at all four calls across three caller files. |
| Local class layout | Add an integer field with zero default to `CustomerProfile`. |
| Shared declaration | Add an integer field with zero default to widely used `AccountingCheck`. |
| Runtime PCH header | Change the `int_t::native_value()` template body to an equivalent expression in copied `scpp/int_t.hpp`. |
| Runtime implementation | Change the equivalent increment expression in copied `scpp/support/var_dump.cpp`, included by `core/runtime.cpp`. |

Application behavior is preserved deliberately so the existing accounting self-test remains an independent executable check. Layout/signature edits change generated headers; runtime edits leave the generated publication unchanged.

## Edit timings and rebuild counts

Timings below use the actual median-total run for each case, with worst sampled process-tree RSS across its three runs. All durations are seconds. PCH is its standalone wall duration; “Compile elapsed” is the union of PCH/object intervals and already includes PCH time. Provider compilation can overlap PCH creation; do not add the PCH column again. “Other” contains distributed launch/identity/cache/status/exit work.

| Case | C++ ready | PCH | Compile elapsed | Link | Other | Total | Peak tree MiB | Objects rebuilt / reused | PCH rebuilt |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| Full baseline | 0.756 | 1.802 | 10.581 | 0.051 | 0.371 | **11.758** | 1541.9 | 96 / 0 | 1 |
| Two method bodies | 0.785 | 0.000 | 0.341 | 0.046 | 0.371 | **1.544** | 295.7 | 1 / 95 | 0 |
| Signature + callers | 0.735 | 0.000 | 2.890 | 0.051 | 0.357 | **4.034** | 867.0 | 5 / 91 | 0 |
| Local class layout | 0.741 | 0.000 | 2.799 | 0.052 | 0.346 | **3.938** | 874.0 | 5 / 91 | 0 |
| Shared declaration | 0.736 | 0.000 | 8.297 | 0.052 | 0.365 | **9.450** | 1507.3 | 89 / 7 | 0 |
| Runtime PCH header | 0.740 | 1.858 | 10.753 | 0.052 | 0.370 | **11.914** | 1534.2 | 96 / 0 | 1 |
| Runtime implementation | 0.743 | 0.000 | 4.076 | 0.048 | 0.384 | **5.251** | 435.5 | 1 / 95 | 0 |

Compiler emission/publication is included in C++ ready, not Clang compilation. Clang frontend and LLVM backend are combined here; no tracing flags were added.

## Compiler stages and executable availability

Actual median-total edit runs. Setup includes publication admission/runtime-catalog setup; request-to-C++-ready additionally includes process startup. Executable available is successful link-driver completion; total above continues through final readiness/status and process exit.

| Case | Setup | Sync/parse | Preparation | C++ emission/publication | Request → executable available | Compiler peak MiB |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| Two method bodies | 0.019 | 0.070 | 0.450 | 0.245 | 1.471 | 70.0 |
| Signature + callers | 0.016 | 0.058 | 0.421 | 0.239 | 3.955 | 69.8 |
| Local class layout | 0.020 | 0.066 | 0.424 | 0.230 | 3.864 | 70.3 |
| Shared declaration | 0.021 | 0.064 | 0.419 | 0.231 | 9.370 | 69.8 |
| Runtime PCH header | 0.019 | 0.054 | 0.425 | 0.241 | 11.836 | 69.8 |
| Runtime implementation | 0.020 | 0.058 | 0.424 | 0.240 | 5.158 | 70.0 |

## Exact object sets

- Two body edits rebuild only `src/customer_profile.phs`’s implementation object.
- Signature/callers and local layout both rebuild **five** application objects: `src/customer_profile.phs`, `src/check_customer_profile.phs`, `src/credit_desk.phs`, `src/workflow_checks.phs`, and `src/large_cli.phs`. The last is an unchanged source file whose existing C++ dependency includes the changed type header.
- The `AccountingCheck` layout edit changes only its generated type header and rebuilds **89 application objects**. It spares `entry.cpp`, `src/document_catalog_checks.phs`, `src/large_cli.phs`, `src/money.phs`, and all three runtime providers.
- The PCH-header edit rebuilds the **single runtime.pch**, all **93 generated objects**, and providers `core/runtime.cpp`, `modules/datetime/datetime.cpp`, `modules/json/document.cpp`.
- The runtime implementation edit rebuilds only **`core/runtime.cpp`**. All 93 application objects, the other two providers and runtime PCH are reused.
- Each edit and each revert invokes exactly one link. The complete lists and native output paths are retained in summary.json.

## Unchanged and Git-revert behavior

Medians across three trials, seconds. Reverts rebuild the same object/PCH sets as the corresponding edits; Ninja does not restore historical object binaries by content. Every unchanged run after either edit or revert launches zero Clang/link actions.

| Case | Edit range | Unchanged after edit | Git revert | Unchanged after revert |
| --- | ---: | ---: | ---: | ---: |
| Two method bodies | 1.527–1.570 | 1.045 | 1.534 | 1.041 |
| Signature + callers | 4.007–4.072 | 1.031 | 4.037 | 1.030 |
| Local class layout | 3.912–3.997 | 1.046 | 3.982 | 1.028 |
| Shared declaration | 9.444–9.527 | 1.048 | 9.475 | 1.069 |
| Runtime PCH header | 11.810–12.550 | 1.048 | 11.884 | 1.052 |
| Runtime implementation | 5.213–5.310 | 1.029 | 5.295 | 1.033 |

## What Ninja contributes to PCHs

Our current graph already gives Ninja a useful role: one PCH-producing edge, header dependencies collected by Clang, and an explicit PCH prerequisite on each generated object. Ninja decides whether that edge is dirty, creates it once, waits before starting consumers, and schedules independent provider work while it runs. In every PCH-rebuilding case, generated-object Clang processes began after the PCH process completed. Provider compilation overlapped PCH creation. There are no separate dependency-scan or syntax-only PCH-validation commands.

Ninja does not choose which headers belong in a PCH, determine Clang compatibility, or split implementation units. Those choices belong to our builder/lowering and Clang. If later experiments introduce multiple independent PCH variants for different unit groups, Ninja can schedule their producers concurrently with explicit dependencies; this experiment creates only one runtime PCH.

The current runtime-only PCH boundary is useful: even the broad application declaration edit preserved it. A PCH containing volatile application declarations would couple those edits to PCH invalidation and potentially increase rebuild scope. That is a tradeoff for a future experiment, not a layout change made here.

## Findings and limits

Localized class edits remain below eight seconds on this host. A widely used class layout and runtime PCH change cross that threshold because they fan out to most or all objects. The large runtime provider also demonstrates that “one rebuilt object” need not mean a short compilation. These are measured workload boundaries, not failures of parallel scheduling or evidence that every current header inclusion is necessary.

All exact dependency-set checks, executable self-tests (998 baseline + 857 large-fixture checks per build), full-publication PHP parity checks, no-op checks and Git restoration checks passed. Peak tree RSS is sampled at nominal 10 ms, double-counts shared pages and can miss short peaks; it is not PSS. Marker timestamps have filesystem quantization. Mold retains its normal fork behavior, so its parent-only wait4 memory/CPU is not used as total linker resource usage. Tool identity is still fully hashed on each invocation, including Mold; that existing cost remains in Other.

No compiler, Ninja scheduler, grouping, PCH-composition or linker settings were changed. Results are integration evidence for the next discussion, not an instruction to broaden compiler incremental/MT work.

## Agreed follow-up scope — 2026-10-16

Runtime-header/PCH and runtime-implementation changes are toolchain-maintenance
cases, excluded from ordinary application-edit performance targets. The runtime
is expected to remain unchanged during normal application builds; retain these
rows only as maintenance/invalidation evidence.

The next focus is application-header fan-out and expensive individual Clang units,
particularly the 89-object shared-declaration case. See the
[updated lowering plan](clang_units_and_mold.md#next-focus-after-the-complex-edit-experiment--2026-10-16)
for the bounded investigation and lessons from the pre-my-try September study.
