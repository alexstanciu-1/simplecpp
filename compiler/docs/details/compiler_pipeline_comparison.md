# Pipeline comparison with the previous compiler
Doc Status: supporting

Date: 2026-09-07. Reference repository:
`../../../simple_cpp_compiler`, HEAD
`9776900f6c305729ae6ef8474052363508913315`.

The inspected working tree has 9 modified tracked files, 147 deleted tracked
paths, and one untracked directory. This comparison describes the available
working tree, not a clean checkout of HEAD. The previous repository was only
read. No old builds, tests, or historical benchmarks were rerun.

Status: historical evidence and responsibility mapping. Observations and source
hashes refer to that inspection; this consolidation does not re-audit the old
compiler. The [current specs](../README.md) and [first-slice plan](first_slice.md)
govern implementation scope and supersede earlier planning assumptions below.

## Verdict and meaning of coverage

The original 17-step list covered the conventional compilation spine, but was
not complete as an architecture description. Runtime authority import, generic
materialization, semantic dependency propagation, bridge production, state
lifecycle, and shared tooling consumers needed explicit owners.

The revised pipeline at the time of this comparison had 22 responsibilities
and 8 continuing services. Its [numbered inventory is preserved below](#historical-responsibility-ids);
the [current pipeline](../compiler_pipeline.md) now maps actual prototype processes. The historical inventory maps all 44
responsibilities in the comparison below, all 14 explicitly declared v1 stage boundaries, all 8 v2
compiler interfaces, and all 15 rows of the old campaign group inventory
(including the historical planning row). No item in those enumerated sets is
left without an owner or an explicit external/future boundary.

The [historical boundary review](compiler_pipeline_review.md) preserves the
expanded analysis and future acceptance inventory from this comparison.

That is **100% mapping coverage of those enumerated responsibility sets**.
It is not proof that every requirement in every historical document has been
found, that every old feature is supported, or that the new compiler is already
equivalent, faster, or correct. Source inspection and historical test artifacts
cannot establish behavioral parity for an unimplemented compiler. The revised
document includes executable acceptance requirements for those later claims.

At this level, the revised design preserves the old architecture's substantive
contracts and improves on the inspected active code's ownership/wiring gaps.
It does not claim a new architecture superior to every old design proposal:
several of the best requirements were already written down in the old docs.

## Method and evidence status

Reviewed the active source tree by subsystem, read the driver/backend routes,
examined representative model, incremental, metadata, lifetime, cache, and
tooling implementations, and compared them with core/substrate/future design
docs. Mined v1's explicit stage table, materialization, parser, code generator,
native build/cache code, and selected historical contracts. Read existing
structural audits and checked their principal claims against source.

Evidence references below mean:

- **active code**: implementation inspected under the current `compiler/src`;
  this does not automatically mean every function is entry-reachable;
- **retained code/proof**: code exists, but the current driver does not establish
  production execution of it;
- **v1 code**: retired implementation, used as reference only;
- **design**: a stated contract or future direction, not executable acceptance;
- **fixture/tool**: historical expected observations or test machinery, not a
  fresh successful test run.

The old [organization audit][A1] reports entry reachability; its static analysis
has stated limits. The [alignment audit][A2] distinguishes current execution
from retained incremental, scheduler, and daemon machinery. We preserve that
distinction rather than treating every source file or “complete boundary” as
a functioning stage.

## Responsibility coverage

Numbers refer to the [historical responsibility IDs](#historical-responsibility-ids);
C1–C8 refer to the continuing services in the [current pipeline](../compiler_pipeline.md).
“Covered” below means the responsibility has a named design owner.

| ID | Old responsibility | Evidence and status | Revised owner / preservation or improvement |
|---|---|---|---|
| R01 | Manifest, entry, options, project setup | [manifest reader][S1], active code; [architecture][D1], design | 1, 3: validated normalized request and session compatibility. |
| R02 | Source discovery, project reading, added/deleted files | [source units][S2], active code; [watcher][D2], design | 4–6: stable file identities, actual source-set reconciliation and content confirmation. Tree walking is broader than the current explicit-source manifest. |
| R03 | Source buffers, line indexes, source-range lookup | [source buffers][S3], active code; [analysis foundation][D3], design | 5, C3, C5: preserve source versions/ranges through semantic and backend output. |
| R04 | Tokenization and compact token buffers | [tokenizer][S4], active code; [v1 stage table][V1] | 7, C3: reusable compact token lists. |
| R05 | Parsing, recovery, PHS/JSS common model | [parser][S5], active PHS code; [v1 parser][V2], JSS probe; [architecture][D1], design | 8, C4: distinct frontends converge before semantics; no claim of full JSS support. |
| R06 | Symbols, scopes, declarations, members | [symbol index][S6], active code; [interfaces][D4], design | 9: declaration/index ownership independent of backend forms. |
| R07 | Name/reference binding and dependency edges | [reference resolution][S7], [dependency graph][S8], active/retained code | 10, 15: references plus lookup dependencies, including failed/candidate-set lookups. |
| R08 | Canonical type identity and structured arguments | [TypeRefs][S9], active code; [type substrate][D5], design | 11, C3: canonical interned identities, no downstream type-spelling parsing. |
| R09 | Family/type demand materialization and constraints | [v1 materialization][V3], v1 code; [generic boundary][D6], design/limited implementation description | 11, 15, 17: demand-driven type/value instances, recursion/constraint checks, and backend demands. |
| R10 | Constants, globals, top-level executable entry | [v1 parser][V2], v1 code; [top-level policy][D7], historical contract | 9, 11, 13, 16–17: explicit initialization/body owners and order; constant-value dependencies. |
| R11 | Expressions, operators, casts, coercions | [operation readiness][S10], active code; [metadata generator][T1] | 2, 12–13: metadata-selected operation contracts and explicit evaluation order. |
| R12 | Calls, arguments, signatures, methods and helper calls | [callable contracts][S11], active code; [interfaces][D4], design | 9–14, 16: one callable model; readiness for defaults/named/byref/variadic and other supported forms. |
| R13 | Traits, providers, consumers, readiness, provenance | [type readiness][S12], active code; [provider protocol][D8], design | 2, 11–16, C4, C7: contracts govern behavior; blocked operations cannot bypass the gate. |
| R14 | Nullable/result extraction, presence and cleanup | [interfaces][D4], [type substrate][D5], design; capability row families in old tree | 11–14, 16: generic wrapper contract owner; source support remains separately gated. |
| R15 | Indexing, ranges, foreach, container access | [interfaces][D4], design; [campaign][D9], feature inventory | 11–14, 16: shared range/place/storage obligations rather than container-specific statement paths. |
| R16 | Objects, fields, layout, constructors, destruction, dispatch | [interfaces][D4], design; [v1 materialization][V3], v1 code | 9–14, 16: object/layout/callable/lifetime ownership; layout changes affect dependent ABI/body work. |
| R17 | Storage contexts, copy/move, ownership and lifetime | [storage/lifetime][S13], active/retained code; [interfaces][D4], design | 12–14, 16: local/parameter/return/field/capture/container obligations share the same model. |
| R18 | Branches, loops, definite assignment and transfers | [dataflow][S14], [transfers][S15], active code | 13–14, 16: common CFG/body analysis, including cleanup across exits. |
| R19 | Closures, exceptions/finally, async/task contracts | [preservation map][D10], [interfaces][D4], design/mining inventory | 9–14, 16, C7: source semantics and transformations have owners; unsupported cases stay blocked. |
| R20 | Lowering plans, requests and backend decisions | [lowering][S16], active/retained code; [lowering boundary][D11], design | 13–18: body semantics first; no frontend interpretation in the sink. Avoid redundant IR layers with no independent purpose. |
| R21 | Runtime/operator/module metadata import/generation | [metadata generator][T1], tool; [generated bridge consumer][S17], active code | 2, C7–C8: validated versioned authority; generation can remain external to a compile request. |
| R22 | Runtime ABI, demanded bridge source, native symbol provision | [v1 codegen][V4], [v1 build][V5], v1 code; [runtime link boundary][D12], design | 16–18, 20–21: explicit bridge/ABI/link products and ownership/effect contracts. |
| R23 | LLVM text and future LLVM API sink | [LLVM text][S18], active code; [API preflight][S19], retained row-only preflight | 18: shared lowered input, verified backend output; API support requires an actual sink proof. |
| R24 | Optimization, effect visibility, backend options | [performance plan][D13], [runtime link boundary][D12], design | 19, C7: passes at the right IR boundary; preserve effects and record optimization dependencies. No unsupported optimizer claims. |
| R25 | Backend ownership, partitions and dependency clusters | [partition readiness][S20], retained code; [clusters][D14], future design | 17, C2–C3: logical ownership/cache partitions distinct from physical storage segments. |
| R26 | Objects, command fingerprints, missing-output recovery and linking | [v1 build][V5], v1 code; [object/link cache][S21], retained logical proof; [v1 object/link contract][D15] | 20–22: actual objects/link results; current object set, options/libraries, and output existence participate in reuse. |
| R27 | Executable launch and exit/output reporting | [native adapter][S22], [execution exports][S23], active code; [v1 build][V5] | C8 after 21–22: explicit execution consumer; export requests do not trigger a run. |
| R28 | Resident session and one cold/warm transaction | [driver][S24], active code; [incremental model][D16], design; [v1 session][V6] | 3, 6, 15, 22, C1: the session's retained state must actually drive selected work. |
| R29 | Source/definition/body/public/value/layout diffs | [dirty propagation][S25], retained code; [incremental model][D16], design | 5–6, 9–15, C1: semantic diffs determine selective invalidation; body edits are local unless consumers depend on their contents. |
| R30 | Reverse dependencies, dirty queue, upgrades and fixed point | [reverse index][S26], [dirty propagation][S25], retained code; [incremental model][D16], design | 6, 15, C1: process stronger reasons, remove obsolete edges, terminate when outputs stabilize. |
| R31 | Publish/repoint, consistent generations, retirement | [incremental model][D16], [segmented storage][D17], design; [payload tables][S27], retained code | 22, C1–C3: stage replacements, publish consistent state, reclaim when readers finish; explicit failed-build validity. |
| R32 | One-worker/MT scheduling, real payloads and ordered merge | [scheduler][S28], [frontend scheduler][S29], retained code; [performance plan][D13], design | C2 throughout: same execution contract for one/many workers; proof counts/hashes do not replace output payloads. |
| R33 | Watch events, daemon requests and repeated service | [daemon IPC][S30], [daemon proof][S31], retained/proof code; [watcher][D2], design | 3–6, 22, C1: transport owns requests; compiler owns semantic invalidation and retained state. |
| R34 | Persisted incremental state/cache codecs | [v1 persisted probe][V7], v1 proof; [cache contract][D18], design | 3, 22, C3: optional compatible persistence, invalidation/version checks; not required before in-memory reuse works. |
| R35 | Stable keys, exact identity, cache dependencies | [cache contract][D18], [incremental model][D16], design; [source identity][S32], active code | C3, steps 2–6, 15–21: identity is not a path or hash alone; include semantic, provider, target, compiler and schema inputs. |
| R36 | Compact storage, indexes, sidecars, memory modes/budgets | [model tables][S33], active code; [memory model][D19], [analysis foundation][D3], design | C3, C5–C6: bounded retained state, derive readable views, measure retirement and growth. Keep old budget as a calibration reference. |
| R37 | Structured source diagnostics and unsupported boundaries | [dataflow][S14], active code; [interfaces][D4], [analysis foundation][D3], design | C4 throughout: stage-specific errors and readiness, with partial analysis where valid. |
| R38 | Artifact writing, deterministic exports and write reuse | [artifact writes][S34], [exports][S35], active code | C5, 22: exports observe state; output deduplication is distinct from incremental computation reuse. |
| R39 | STAN, editor queries, source maps and debug plans | [analysis foundation][D3], design; [v1 source-map query][V8], v1 code | C5: shared semantic facts for hover/definition/references/completion/locals/debug maps; optional analysis-only product. |
| R40 | Timing, cache/work counters, profile instrumentation | [profile events][S36], active/retained code; [analysis foundation][D3], [performance plan][D13], design | C6, 16, 19: measured actual execution and optional plan-driven instrumentation; separate compiler/native/runtime costs. |
| R41 | Build compiler implementation and regenerate support inputs | [PHS project][S37], active build config; [TDD runner][T2], build orchestration | C8: bootstrap/self-build and runtime provisioning remain external toolchain responsibilities, not stages of compiling a user source file. |
| R42 | Pure source tests, exports, negative cases and structural guards | [TDD runner][T2], [campaign][D9], [example source][F1] and [expected execution][F2] | C8, acceptance requirements: fixtures observe normal behavior; no behavior selected by test names or expected files. |
| R43 | Feature catalog coverage, architecture rules and growth discipline | [campaign][D9], [contract-first model][D20], [support boundary][D21], design/tools | C7–C8: capability coverage and planning stay external; representative proofs precede breadth, with named owners and no feature/type multiplication. |
| R44 | Future LTO/PGO/BOLT/JIT, runtime link profiles and service extensions | [runtime link boundary][D12], [clusters][D14], future/deferred design | 17–21, C1–C2, C6–C8: explicit extension points. JIT remains a future execution consumer of shared lowered/backend output; not an implemented feature. |

## Explicit old pipeline/interface cross-checks

The v1 [stage table][V1] declares the following 14 entries. Some entries mix
production work and probes; preserving a responsibility does not preserve its
old placement or status.

| Declared v1 stage | Revised owner |
|---|---|
| source_reader | 4–5 |
| tokenizer | 7 |
| parser | 8 |
| symbol_index | 9–10 |
| typed_fact_builder | 11–14 |
| materialization | 11, 17 |
| lowering | 16 |
| runtime_metadata | 2, C7 |
| bridge_codegen | 17–18, C7 |
| llvm_writer | 18 |
| artifact_writer | C5, 22 |
| incremental_diff | 5–6, 15, C1 |
| incremental_transaction | 3, 6, 15, 22, C1 |
| process_runner | 20–21, C7; explicit program execution in C8 |

The v2 [interface definition][D4] declares 8 core interfaces:

| Interface | Revised owner |
|---|---|
| Identity | 4, 9–11, C3 |
| Type Trait | 2, 11–12, C7 |
| Storage/Lifetime | 12–14, 16 |
| Wrapper | 11–14, 16 |
| Range | 11–14, 16 |
| Object/Layout | 9–14, 16 |
| Callable | 9–14, 16 |
| Backend Adapter | 16–21, C7 |

The [campaign inventory][D9] contains these 15 group rows. The grouping is a
feature-planning taxonomy, not an additional pipeline. Mapping a group does
not imply acceptance of its individual cases.

| Group | Revised owner |
|---|---|
| BASE — foundation | 1–22; small vertical acceptance proof |
| SF — scalars | 11–14, 16–18 |
| EX — expressions/operators | 11–14, 16–19 |
| FN — functions/calls/ABI | 9–14, 16–18 |
| SL — storage/lifetime | 12–14, 16 |
| CF — control flow | 13–14, 16 |
| RV — runtime values/strings | 2, 11–14, 16–21 |
| RI — range/index/foreach | 11–14, 16 |
| WR — wrappers/results | 11–14, 16 |
| MH — modules/helpers | 1–2, 10–14, 16–21 |
| OBJ — classes/structs/objects | 9–14, 16–18 |
| DX — diagnostics/source maps | C4–C5; denominator accounting in C8 |
| PG — performance/incremental | C1–C3, C6, C8 |
| MX — validation matrices | C8 |
| 90-D — legacy scaffold | historical planning/reference only, C8 |

## Concrete gaps in the old active implementation

1. **State reuse is not on the default path.**
   [The driver][S24] constructs source/frontend state in `row_from_config()`.
   Its `run_batch_against_previous()` ignores `$previous`. The revised session
   owns retained state, and selected work must reuse real payloads. This must
   be proven by work counts and changed program behavior, not report labels.
2. **The backend still recognizes source shapes.**
   [Module composition][S38] passes frontend models/source into
   `parameter_function_text_from_symbol_rows()` in [the LLVM writer][S18].
   That method inspects a return/binary expression and emits LLVM directly.
   Revised steps 13–18 require shared bodies and already-decided lowering.
3. **Worker proof carriers are not necessarily usable outputs.**
   `BackendLoweringWorkerResult` in [lowering][S16] contains counts/hash without
   a lowering-plan payload; `EmissionLLVMWorkerResult` in [the LLVM writer][S18]
   contains counts/hash/text length without LLVM text. C2 requires publishable
   results actually consumed by the next stage. Other worker paths must be
   assessed individually; this is not a claim that all retained workers are fake.
4. **Execution is reached through an export request.**
   [Execution artifact materialization][S23] calls [the native adapter][S22].
   Revised object/link operations are explicit requested products, and program
   execution is a separate explicit action. Merely exporting facts has no run
   side effect.
5. **Design closure and executable readiness differ.**
   The [runtime-link doc][D12] calls its boundary complete while deferring
   production object/link side effects. [The API sink][S19] explicitly identifies
   a row-only preflight. [Daemon proof code][S31] sends `incremental_ok` and a
   constant timing value through a wrapper that ignores previous state.
   Revised C4/C6 and the acceptance checks require mode-specific truth and
   measured execution. A useful contract or proof is retained as such.
6. **Documents disagree about current scope and paths.**
   The [README][D22] names absent root runner/fixture paths. The [campaign][D9]
   contains both updated partial FN/CF results and older `no_tests_yet` rows.
   Historical status/count claims were not adopted as verified current facts.

## Improvements and decisions retained for implementation

The new list makes runtime import and bridge generation visible, replaces a
single early “identify changes” step with real semantic propagation, and names
the session, scheduler, body model, and query consumers. It preserves the old
requirements for compact identities, metadata-backed runtime semantics,
readiness, one cold/warm path, one/many-worker contracts, source mapping, and
backend-neutral decisions.

It also makes several correctness consequences explicit: failed name lookups
must be invalidated when declarations appear, body-based optimization creates
dependencies, cache artifacts must match their input generation, and failed
builds must not advertise an old executable as current. These are proposed
requirements derived from the dependency/session model, not claims that the
old implementation already handled them.

Keep the old resident-memory target in [the memory model][D19] as a reference:
`max(0, resident_bytes - 20 MB) <= 40 * source_bytes`, under that document's
measurement exclusions. Do not promise this bound or old timing figures until
the new implementation is measured. Storage layouts, hash algorithm, cache
codec, source identity rules, precise recovery publication policy, initial
frontend scope, and runtime link products still need concrete slice designs.

This review changes documentation only. It does not authorize a broad refactor
of the old compiler or implement the new one. The existing user rule remains:
if a later slice needs a refactor across multiple ownership areas, report its
scope, risks, validation cost, and options before proceeding.

## Evidence links

Validation completed for this documentation change:

- Confirmed the revised list has 22 sequential steps and 8 service owners.
- Confirmed R01–R44 are present exactly once and have mappings.
- Extracted all 14 stage names and all 8 interface names from the old sources
  and checked their presence in the cross-check tables.
- Extracted all 15 campaign groups and checked their mappings.
- Checked all 74 evidence references and document links resolve locally;
  checked for undefined references and trailing whitespace.
- Saved file sizes and SHA-256 hashes in the
  [evidence snapshot](compiler_pipeline_evidence.tsv)
  so later edits in the mining repository can be detected. This is a snapshot
  of cited evidence, not a whole-repository semantic audit.
  Evidence paths are relative to the TSV file's directory.
- No compiler tests or performance runs were needed for this documentation-only
  change. The new workspace has no usable Git repository, so file checks were
  used instead of `git diff --check`; no commit was created.

[A1]: ../../../simple_cpp_compiler/compiler/docs/audits/2026_09_07_organization_calls/README.md
[A2]: ../../../simple_cpp_compiler/compiler/docs/audits/2026_09_07_organization_calls/incremental_mt_daemon.md
[D1]: ../../../simple_cpp_compiler/compiler/docs/core/architecture.md
[D2]: ../../../simple_cpp_compiler/compiler/archive/v1_retired_2026_08_03/specs/watcher_strategy_v1.md
[D3]: ../../../simple_cpp_compiler/compiler/docs/substrate/analysis_debug_profile_foundation.md
[D4]: ../../../simple_cpp_compiler/compiler/docs/core/compiler_interface_definition.md
[D5]: ../../../simple_cpp_compiler/compiler/docs/substrate/type_substrate_plan.md
[D6]: ../../../simple_cpp_compiler/compiler/docs/future/generic_families_boundary_plan_2026_08_03.md
[D7]: ../../../simple_cpp_compiler/compiler/archive/v1_retired_2026_08_03/specs/top_level_executable_lowering_backlog_2026_07_06.md
[D8]: ../../../simple_cpp_compiler/compiler/docs/core/provider_consumer_interface_protocol.md
[D9]: ../../../simple_cpp_compiler/compiler/docs/future/90_percent_functionality/00_inventory/compiler_90_percent_functionality_action_inventory_2026_08_04.md
[D10]: ../../../simple_cpp_compiler/compiler/docs/reference/v1_preservation_map.md
[D11]: ../../../simple_cpp_compiler/compiler/docs/future/lowering_plans_boundary_plan_2026_08_03.md
[D12]: ../../../simple_cpp_compiler/compiler/docs/future/runtime_link_discipline_boundary_plan_2026_08_03.md
[D13]: ../../../simple_cpp_compiler/compiler/docs/substrate/performance_and_parallelism_plan.md
[D14]: ../../../simple_cpp_compiler/compiler/docs/future/dependency_cluster_partitioning_future_plan_2026_07_18.md
[D15]: ../../../simple_cpp_compiler/compiler/archive/v1_retired_2026_08_03/specs/incremental_object_link_completion_backlog_2026_07_06.md
[D16]: ../../../simple_cpp_compiler/compiler/docs/incremental/incremental_implementation_model_2026_07_19.md
[D17]: ../../../simple_cpp_compiler/compiler/docs/substrate/segmented_row_storage_implementation_plan_2026_07_18.md
[D18]: ../../../simple_cpp_compiler/compiler/archive/v1_retired_2026_08_03/specs/hash_cache_key_contract_v1.md
[D19]: ../../../simple_cpp_compiler/compiler/docs/substrate/resident_memory_scaling_model_2026_07_16.md
[D20]: ../../../simple_cpp_compiler/compiler/docs/core/contract_first_growth_model.md
[D21]: ../../../simple_cpp_compiler/compiler/docs/core/compile_path_vs_support_boundary.md
[D22]: ../../../simple_cpp_compiler/compiler/README.md
[S1]: ../../../simple_cpp_compiler/compiler/src/compile/pipeline/project_manifest.phs
[S2]: ../../../simple_cpp_compiler/compiler/src/compile/model/source_units.phs
[S3]: ../../../simple_cpp_compiler/compiler/src/compile/model/source_buffers.phs
[S4]: ../../../simple_cpp_compiler/compiler/src/compile/frontend_adapter/phs/phs_tokenizer.phs
[S5]: ../../../simple_cpp_compiler/compiler/src/compile/frontend_adapter/phs/frontend_model_builder.phs
[S6]: ../../../simple_cpp_compiler/compiler/src/compile/model/project_symbol_index.phs
[S7]: ../../../simple_cpp_compiler/compiler/src/compile/model/project_reference_resolution.phs
[S8]: ../../../simple_cpp_compiler/compiler/src/compile/model/project_dependency_graph.phs
[S9]: ../../../simple_cpp_compiler/compiler/src/compile/model/type_refs.phs
[S10]: ../../../simple_cpp_compiler/compiler/src/compile/operations/operation_readiness.phs
[S11]: ../../../simple_cpp_compiler/compiler/src/compile/model/project_callable_contracts.phs
[S12]: ../../../simple_cpp_compiler/compiler/src/compile/capabilities/type_capability_readiness.phs
[S13]: ../../../simple_cpp_compiler/compiler/src/compile/capabilities/storage_lifetime_readiness.phs
[S14]: ../../../simple_cpp_compiler/compiler/src/compile/control_flow/control_flow_dataflows.phs
[S15]: ../../../simple_cpp_compiler/compiler/src/compile/control_flow/control_flow_transfers.phs
[S16]: ../../../simple_cpp_compiler/compiler/src/compile/backend/lowering_plan.phs
[S17]: ../../../simple_cpp_compiler/compiler/src/compile/backend/abi_bridge/runtime_abi_bridge.phs
[S18]: ../../../simple_cpp_compiler/compiler/src/compile/backend/llvm_text_from_plan.phs
[S19]: ../../../simple_cpp_compiler/compiler/src/compile/backend/llvm_api_sink_preflight.phs
[S20]: ../../../simple_cpp_compiler/compiler/src/compile/backend/backend_partition_readiness.phs
[S21]: ../../../simple_cpp_compiler/compiler/src/compile/backend/backend_object_link_cache.phs
[S22]: ../../../simple_cpp_compiler/compiler/src/compile/backend/backend_native_execution_adapter.phs
[S23]: ../../../simple_cpp_compiler/compiler/src/compile/support/compiler_execution_artifacts.phs
[S24]: ../../../simple_cpp_compiler/compiler/src/compile/pipeline/compiler_project_runner.phs
[S25]: ../../../simple_cpp_compiler/compiler/src/compile/incremental/resident_dirty_propagation.phs
[S26]: ../../../simple_cpp_compiler/compiler/src/compile/incremental/resident_reverse_dependency_indexes.phs
[S27]: ../../../simple_cpp_compiler/compiler/src/compile/incremental/resident_source_unit_frontend_payload_tables.phs
[S28]: ../../../simple_cpp_compiler/compiler/src/compile/incremental/scheduler_api.phs
[S29]: ../../../simple_cpp_compiler/compiler/src/compile/incremental/source_unit_frontend_scheduler.phs
[S30]: ../../../simple_cpp_compiler/compiler/src/compile/incremental/resident_daemon_file_ipc.phs
[S31]: ../../../simple_cpp_compiler/compiler/src/compile/incremental/resident_daemon_incremental_proof.phs
[S32]: ../../../simple_cpp_compiler/compiler/src/compile/model/source_identity.phs
[S33]: ../../../simple_cpp_compiler/compiler/src/structure_kernel/frontend_model_tables.phs
[S34]: ../../../simple_cpp_compiler/compiler/src/compile/support/artifact_writes.phs
[S35]: ../../../simple_cpp_compiler/compiler/src/compile/support/compiler_export_artifacts.phs
[S36]: ../../../simple_cpp_compiler/compiler/src/compile/support/compiler_profile_events.phs
[S37]: ../../../simple_cpp_compiler/compiler/src/prism.json
[S38]: ../../../simple_cpp_compiler/compiler/src/compile/backend/backend_module_composition.phs
[V1]: ../../../simple_cpp_compiler/compiler/archive/v1_retired_2026_08_03/src/stage_boundaries.phs
[V2]: ../../../simple_cpp_compiler/compiler/archive/v1_retired_2026_08_03/src/frontend_parser.phs
[V3]: ../../../simple_cpp_compiler/compiler/archive/v1_retired_2026_08_03/src/backend/materialization.phs
[V4]: ../../../simple_cpp_compiler/compiler/archive/v1_retired_2026_08_03/src/backend/codegen.phs
[V5]: ../../../simple_cpp_compiler/compiler/archive/v1_retired_2026_08_03/src/support/pipeline_build.phs
[V6]: ../../../simple_cpp_compiler/compiler/archive/v1_retired_2026_08_03/src/incremental_resident_session.phs
[V7]: ../../../simple_cpp_compiler/compiler/archive/v1_retired_2026_08_03/src/incremental_persisted_probe.phs
[V8]: ../../../simple_cpp_compiler/compiler/archive/v1_retired_2026_08_03/src/source_map_query.phs
[T1]: ../../../simple_cpp_compiler/compiler/tools/generate_semantic_lookup_manifest.php
[T2]: ../../../simple_cpp_compiler/compiler/tools/run_90_pure_tdd.php
[F1]: ../../../simple_cpp_compiler/compiler/tests/90_percent/valid/FN/FN-02/tdd_90_fn02_one_int_arg_return/src/main.phs
[F2]: ../../../simple_cpp_compiler/compiler/tests/90_percent/valid/FN/FN-02/tdd_90_fn02_one_int_arg_return/expected/execution.json

## Historical responsibility IDs

Preserved from the earlier design inventory so the comparison mappings above
remain interpretable. These are broad responsibility boundaries, not the current
prototype call schedule or implementation status.

Numbers identify responsibilities and preserve the old comparison's mapping.
They are not a literal call schedule: select a session around the update and
select work before each computation, including source reads. Responsibility 6
describes selection throughout the update, not a pass that waits for all reads.

1. **Read and validate the manifest/request** — sources, dependencies, entry,
   language/modules, target, options, and requested output.
2. **Load compiler/runtime metadata** — versioned types, operations,
   capabilities, callable signatures, and ABI descriptors.
3. **Select the resident project session** — retained model state and compatible
   caches; create empty project state when first opened.
4. **Discover the file tree** — reconcile sources, additions, and removals.
5. **Refresh source snapshots** — read possibly changed content; retain source
   versions, buffers, hashes, and line indexes.
6. **Select transaction work** — select affected products from source/configuration/
   metadata changes and missing outputs; set `full_rebuild` when update rules
   are insufficient or the rebuild policy applies.
7. **Tokenize** — tokenize each selected file in full; `full_rebuild` selects
   every current file through the same loop.
8. **Parse** — parse each selected file in full into the common syntax model
   and recoverable diagnostics: top-level defined entities plus one implicit
   entry body for the file, using shared flat node storage. Declaration comparison belongs to collection
   and affected resolution, using these previous/new syntax snapshots.
9. **Collect declarations and scopes** — establish identities, signatures,
   imports, and explicit owners for initialization/top-level bodies; join
   file contributions into the project symbol index and diagnose duplicate keys.
10. **Resolve names** — use the symbol lookup API to bind references across
    project files and record lookup dependencies.
11. **Resolve types, constants, and generic instances** — canonical identities,
    constraints, materialization, and value/layout dependencies.
12. **Check semantic contracts** — operations, calls, conversions, and required
    type/storage/runtime capabilities.
13. **Build typed executable bodies** — shared values, places, calls,
    evaluation order, blocks, and control-flow edges.
14. **Analyze dataflow and lifetime** — initialization, returns, ownership,
    references, copy/move, and cleanup across control-flow exits.
15. **Stabilize dependencies and readiness** — propagate changed semantic facts,
    repeat affected work to a fixed point, and confirm supported incremental
    propagation or select a full rebuild before the requested next stage.
16. **Lower accepted bodies and data** — explicit execution, layouts, cleanup,
    runtime calls, and target ABI operations.
17. **Plan backend units and runtime demands** — partitions, globals, entry
    support, bridge demands, cache inputs, and link requirements.
18. **Generate and verify backend output** — LLVM representations and demanded
    bridges from completed lowering/ABI plans.
19. **Optimize at the appropriate representation boundaries (later)** — supported
    IR/backend passes, preserving effects and source mappings; no optimization
    work in the initial slice.
20. **Compile or reuse objects** — user-code and bridge objects, with actual
    tool results and compatible cache outputs.
21. **Link or reuse the final artifact** — current objects, runtime/dependency
    libraries, startup support, and link options.
22. **Publish update results** — consistent state, valid caches, diagnostics,
    and output references; safely retire superseded state and remain available
    for the next update.
