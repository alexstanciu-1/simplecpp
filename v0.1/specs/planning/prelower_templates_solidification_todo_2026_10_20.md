# TODO: end-to-end template prelowering solidification
Doc Status: planning

Date: 2026-10-20
Status: working for the supported path; solidification closed. Prelowering is the default,
with explicit ordinary opt-out. Broader coverage deferred.
This is the current follow-up to the [implementation history](cpp_template_prelowering_plan_2026_10_19.md)
and [architectural decision](cpp_template_prelowering_decision_2026_10_19.md).
Unchecked items are remaining work, not claims that the underlying mechanism is absent.
Creating this checklist does not implement its changes.

## Goal and scope

Make the proven general concrete-substitution path a coherent, supported opt-in path
from CLI/API configuration through runtime contracts, preparation, C++ publication,
layout/middleware compilation and application execution. Preserve the ordinary mode.
The application sees concrete interfaces; runtime templates remain in compiled
middleware. Prepare only demanded types and operations, recursively.

Solidification means reliable contracts, explicit coverage, integration and regression
protection. It does not require full runtime coverage, new optimization, a rewritten
AST, a new semantic stage, a parallel registry or family-specific engine branches.
Use existing owners; enrich prepared facts only when genuinely missing. Any newly
required wide cross-owner redesign must be discussed before implementation.

## Architecture closeout — 2026-10-20

Reviewed implementation at `cdc48357`, including `CPP_Specializations`,
`CPP_Opaque_Representation`, `CPP_Runtime_Operations`, fragment demand replay in
`CPP_Generator`, publication policy and `Native_CPP_Build`. This is a focused
architecture review supported by the retained acceptance results, not a new
whole-runtime correctness certification. No implementation or tests changed in
this closeout.

Assessment: the architecture is appropriate and the implementation is a sound
working foundation within its declared coverage. No blocking redesign was found
in the reviewed path:

- Semantic identity and operation selection remain with the existing registry and
  preparation. Physical substitution consumes them without rewriting the AST.
- The engine interns compact references and assembles demand/dependencies.
  Its dispatch is by operation contract, not runtime family names; runtime
  algorithms stay in the runtime and physical adapters have named backend owners.
- Concrete storage contains the actual native object. Layout probes, placement
  construction, destruction and prepared copy permission have distinct roles.
  This avoids a mandatory extra heap object or a second payload representation;
  retained counter proofs establish the tested allocation/copy behavior.
- Fragment-owned demand is replayed into a fresh engine. Stable sorted emission,
  existing publication and dependency-keyed native reuse avoid a parallel
  persistent cache or a second incremental lifecycle.
- CLI/API share one path. Published policy, transitive-input validation, PCH
  rejection and cache-policy separation enforce failure rather than silent fallback.

Accepted tradeoffs, not reasons to prolong this milestone:

- Middleware implementations are grouped in `specializations/containers.cpp`.
  A changed demand can rebuild that group; finer partitioning is future performance
  work, not evidence of fine-grained middleware incremental compilation today.
- Entry ordering uses insertion sorting and layout ordering uses repeated scans.
  Large specialization graphs may justify better algorithms after measurement.
- Fixed physical adapter protocols are intentional. Metadata is extensible provider
  configuration, not a promise that arbitrary C++ libraries work without adapters.
- Native layout probes require target execution; cross-target probe support is
  unclaimed. Unsupported transports/cycles and naming/debug-map debt stay explicit.

The combined O0 acceptance at `da17e1ba`, native O2 compiler execution at
`81913306`, and [matched benchmarks](results/native_compiler_benchmark_2026_10_20/README.md)
provide complementary behavior, portability and performance evidence. They justify
marking the supported feature working, without claiming full runtime coverage or
fully mature incremental compilation. Further work should be driven by concrete
test failures and prioritized coverage requests; runtime redesign remains deferred.

## Accepted architecture — 2026-10-20

The user approved one concrete-substitution path for CLI/API; a strict template-free
application guarantee with explicit failure and no silent fallback; existing metadata
for provider variation and shared adapters for fixed physical protocols; and support
checks by representation plus operation contract at the earliest informed owner.
Shared semantic preparation stays backend-independent. See the
[decision](cpp_template_prelowering_decision_2026_10_19.md#agreed-solidification-contract--2026-10-20).
First bounded slice: close the confirmed structural-callable template escape in the
existing representation owner, with ordinary-mode, explicit-rejection and recovery
proofs. Public option migration follows contract enforcement; this slice does not
claim complete closure validation or broaden runtime coverage.

## Evidence and audit baseline

- [Native prelowering/accounting execution](results/native_prelower_accounting_2026_10_20/README.md):
  the O2 native compiler publishes and builds ordinary/concrete accounting at O0;
  artifact/manifests match PHP, 998/0 behavior matches, all 16 application units are
  template-free, warm builds do no compile/link work, and strict rejection/recovery,
  replay and PCH rejection pass. This is bounded native execution evidence, not
  expanded runtime coverage or a new optimization benchmark.

- [Final fresh combined O0 acceptance](results/prelower_solidified_acceptance_2026_10_20/README.md):
  all eight phases pass at `da17e1ba`, including the strengthened metadata, demand,
  publication and deep-naming proofs. Accounting is identical (998/0 each), all 16
  app units are template-free, and both warm builds compile/link nothing. This closes
  the supported-path scope; it does not certify full runtime or incremental maturity.

- [Fresh public-CLI accounting acceptance](results/prelower_public_accounting_2026_10_20/README.md):
  ordinary and prelowered O0/no-debug/no-PCH builds both pass 998/0 with byte-identical
  output. Complete fresh 16-unit application closure is template-free; both warm
  builds compile/link nothing. This supersedes the saved-publication-only acceptance
  checkpoint without making a new performance or full-runtime-coverage claim.
- [Accounting acceptance](results/prelower_template_free_accounting_2026_10_19/README.md):
  998 checks pass; 16 app units have zero template declarations and zero Clang
  class/function instantiations without PCH. This is workload coverage, not universal
  runtime coverage. Runtime and middleware still compile templates.
- [Native compilation comparison](results/prelower_regular_comparison_2026_10_19/README.md):
  substantial O0 application-unit improvement; initial middleware and compiler
  lowering costs are excluded.
- The 2026-10-20 audit freshly reran `prelower_header_boundaries.py`: output,
  indexing and compound groups passed, including direct/concrete behavior,
  allocation/copy counters, nested references and warm reuse. Local results:
  `/tmp/prelower-solidification-audit-20261020`. Accounting was not rerun in that audit.
- Initial source inspection found three selectable implementations: historical recipes,
  runtime ABI transport, and concrete substitution. Public selection is now consolidated
  as recorded in section 3; superseded implementations and temporary selectors have
  been retired, and useful source-lifecycle proofs migrated.
- Metadata owns type recipes and capabilities, but some physical conventions are
  fixed adapter protocols. Registered does not imply concrete-compatible or proven
  template-free. The original audit found a callable `std::function` escape, now
  explicitly rejected in concrete mode; several
  call shapes and generated header/layout cycles are explicitly unsupported.

## 1. Settle the supported boundary and runtime interaction contract

Owners: runtime metadata/specs, existing registry and prepared facts, C++ bindings.

- [x] Produce one current coverage table for the surface already exercised by the
  general path. Distinguish **registered**, **concrete-compatible**, **template-free
  proven**, and **explicitly unsupported/unproved**. Reuse the existing
  [family/capability matrix](runtime_template_capability_matrix_2026_10_19.md);
  this is documentation/evidence, not another executable registry. See its
  [current coverage contract](runtime_template_capability_matrix_2026_10_19.md#current-coverage-contract--2026-10-20),
  reviewed against `b52a02a8`; historical checkpoints remain explicitly dated.
- [x] Include scalars/strings, nested containers, source-class payloads, shared/unique/
  value storage, initialization, ordinary members, indexing/append/unset, iteration,
  conversions, output and runtime calls. Do not mark an entire family supported
  because one operation or accounting passed.
- [x] Reconcile [metadata](../runtime_module_metadata.md) and the
  [interoperability contract](../../runtime/specs/concrete_type_interoperability.md)
  with current implementation. State the division explicitly: metadata declares
  semantics/bindings; preparation selects operations and boundaries; backend adapters
  implement concrete interfaces; runtime implementations retain dynamic algorithms.
  The interoperability guide now separates these owners, physical transport and
  layout-probe authority from source admission and complete closure evidence.
- [x] Audit argument/result transport, receiver adaptation, borrowing, lifecycle and
  physical interface assumptions. Keep fixed shared protocols documented in code/specs;
  add metadata only for real provider variation. Do not encode arbitrary C++ snippets
  or every implementation step in JSON. See the [transport audit](results/prelower_transport_audit_2026_10_20/README.md)
  and the interoperability guide for owners, constraints and existing proof coverage.
- [x] Preserve Clang probes as the source of native size/alignment/lifecycle traits.
  Do not duplicate ABI constants in metadata or infer semantics from native spelling.

Done when: each supported interaction has an existing semantic owner, physical
contract and proof reference; unknowns are explicitly bounded.

## 2. Validate contracts and make unsupported coverage explicit

Owners: runtime catalog installation, backend binding/admission checks, diagnostics.

- [x] Verify required representation metadata, including native argument mapping,
  stored/payload roles, pointee contracts, scalar/byte interfaces and concrete exposure.
  Retain failed-configuration rejection without replacing installed configuration.
  The [metadata recovery proof](results/prelower_metadata_recovery_2026_10_20/README.md)
  covers 39 invalid schemas, valid reconfiguration after each rejection and identical
  demanded artifacts after a registry reset. Provider C++ validity remains a native check.
- [x] Classify existing limitations: runtime member results/references/variadics;
  runtime-call reference results, variadics and observation/default-input restrictions;
  callable transport; boxed write-back/provider composition; unsupported generated
  header/layout cycles. Separate real remaining gaps from historical stale claims.
- [x] Close the structural-callable escape in concrete representation emission.
  Local/signature/field callables now reuse the existing unsupported-transport check;
  nested callable storage uses the same diagnostic. Five ordinary/rejection/recovery/
  replay cases pass. This closes one known escape, not the complete contract gate.
  See [slice evidence](results/prelower_callable_boundary_2026_10_20/README.md).
- [x] Close native-only object-test/cast and weak-observation escapes in the existing
  expression owner. Reject before operand emission/runtime-header exposure, keeping
  ordinary source semantics unchanged. Four new rejection/recovery cases pass;
  [evidence](results/prelower_operation_boundaries_2026_10_20/README.md) records the
  bounded scope. Concrete adapters for these operations remain unimplemented.
- [x] Reject source throw/catch/finally through the existing native-only handler
  guard. Their template-bearing exception transport is not yet concrete. Twelve
  boundary cases now also prove on-disk non-readiness on failure and exact manifest
  recovery using a fresh publication worker for each attempt; see
  [evidence](results/prelower_exception_boundaries_2026_10_20/README.md).
- [x] Reject native `take` transport and the runtime failure fallback for match
  expressions without a default. Explicit-default match remains admitted and is
  exercised in every repair/replay case. Fourteen boundary cases pass with disk
  readiness/recovery; see [evidence](results/prelower_value_boundaries_2026_10_20/README.md).
- [x] Audit runtime-backed condition truthiness/value probes, boxed extraction
  and enum compiler helpers. Reject the confirmed native transport paths at their
  existing emitters; preserve integer/boolean conditions, prepared presence and
  keyed probes. Twenty-one boundary cases exercise rejection/recovery; see
  [evidence](results/prelower_runtime_boundaries_2026_10_20/README.md).
- [x] Supply concrete null-output transport through the existing generic output
  bridge and C++ binding registry. The internal sentinel remains non-storable;
  its runtime header no longer includes unused template machinery. O0 output/counter
  parity and a fresh complete no-PCH closure proof pass, including ordered echo side
  effects. See [evidence](results/prelower_null_output_2026_10_20/README.md).
Broader representation/operation validation is deferred below; it is not an open
solidification prerequisite. Public selection and strict transitive-header/PCH
enforcement are complete. The rejection cases are not a universal support proof.

- [x] Carry the concrete application's template policy through existing publication
  into the native builder. Reject broad runtime PCH; validate active Clang tokens
  across application inputs and transitive provider headers before accepting new
  objects. Reuse dependency-keyed validation through the existing object cache.
  Provider mutation/recovery and ordinary-cache separation are covered by
  `prelower_native_boundary.py`; remaining operation combinations stay open. Public
  configuration consolidation is complete. See
  [native enforcement evidence](results/prelower_native_boundary_2026_10_20/README.md).
- [x] For the supported opt-in mode, report unsupported representations/operations
  explicitly. Do not silently fall back to template-bearing application interfaces.
  Choosing ordinary compilation remains an explicit caller decision. See the
  [metadata/native recovery evidence](results/prelower_metadata_recovery_2026_10_20/README.md).
- [x] Establish a clear contract for transitive header exposure: `cpp_types` selection
  alone cannot prove arbitrary provider headers template-free. Combine binding checks
  with a complete generated-input closure regression, rather than promising universal
  freedom from templates based on flags alone. The public/native contracts and
  complete accounting acceptance establish this bounded guarantee.
- [x] Cover representative invalid/missing contracts and failure/repair behavior.
  Implement only bounded missing adapters needed for the agreed surface; keep broader
  runtime expansion deferred instead of making it a hidden prerequisite.

Done when: supported cases work through general contracts; unsupported cases fail
clearly without publishing a usable partial result or silently changing mode.

## 3. Consolidate configuration and backend dispatch

Owners: `cpp_lowering_options`, CLI/API entry points, C++ generation/adapters.

- [x] Make `--prelower-templates` select the same general concrete-substitution path
  used by accounting. Align the API setting and document effective options.
  CLI and API now require only `prelower_templates`; former comparison fields have
  been removed. Artifact/manifest parity, strict CLI rejection and repair,
  ordinary opt-out, native PCH rejection and complete fixture closure pass through
  `prelower_null_output.py`. See [public-path evidence](results/prelower_public_options_2026_10_20/README.md).
- [x] Review PCH interaction: the template-free acceptance path must use no runtime
  PCH. Ensure configuration cannot misleadingly claim template-free app input while
  injecting the broad runtime PCH; leave ordinary-mode PCH behavior supported.
- [x] Reuse ordinary expression sequencing and prepared operation/conversion facts.
  Keep the specialization engine free of family algorithms and semantic re-analysis.
  See the [transport audit](results/prelower_transport_audit_2026_10_20/README.md) and
  [demand/counter verification](results/prelower_demand_publication_2026_10_20/README.md).
- [x] Prove mode switching invalidates affected C++ fragments without unnecessary
  semantic repreparation, and restoring settings restores deterministic output.
  The migrated lifecycle fixture verifies ordinary/concrete restoration, unchanged
  preparation version, no-op fragment identity, absence of comparison fields, demand
  retirement and failure/repair through the public option.
- [x] Retire temporary selectors and superseded production paths only after their
  useful behavior/proofs are accounted for. Keep historical benchmark evidence;
  do not retain obsolete runtime branches solely to reproduce old experiments.
  The engine has one configuration. Handwritten integer/vector recipes, their
  fallback algorithms, native-source partitioning and the old runtime-ABI emission
  branches are removed. Shared entry support, storage/layout workers, constructor
  transport and scalar unary emission are preserved at their current owners.
  Comparison-only harnesses are archived with an explicit coverage/migration map.

Done when: one supported prelowering selection has consistent CLI/API behavior,
while ordinary compilation and its regression proofs still work.

## 4. Verify demand, publication and native reuse end to end

Owners: existing fragment requests, specialization assembly, layout cache,
publication coordinator and native build.

- [x] Verify unused metadata emits no concrete types; nested demand emits only its
  required closure; removing/restoring use retires/restores support artifacts.
- [x] Verify live emission and cached-fragment replay request identical interfaces,
  with stable names and output independent of discovery order.
- [x] Exercise body-only edits, representation/layout changes, runtime header changes
  and configuration changes through existing invalidation owners. Reuse existing
  cache keys and dependency tracking; do not create another cache or redesign the
  unfinished incremental compiler here.
- [x] Verify failure/repair, no partial ready publication, layout dependency ordering
  and warm reuse of layouts, middleware and app objects where inputs are unchanged.
- [x] Preserve source field/signature consistency, nested reference identity,
  evaluation order, exception cleanup and zero extra boundary allocations/copies
  in the focused O0 proofs. Keep prior optimized-build differences explicitly scoped.

Evidence: [demand/replay/publication acceptance](results/prelower_demand_publication_2026_10_20/README.md)
records the exact nested-type discovery-order comparison, disk retirement/restoration,
retained-fragment checks and focused O0 cache/lifecycle proofs. These checkboxes close
the bounded supported-path verification, not arbitrary providers or all incremental
edit combinations. Earlier optimized-build limitations remain deferred.

Done when: reuse is correct and demand-driven, with changed inputs invalidated and
unchanged artifacts reused; no whole-compiler incremental maturity claim is implied.

## 5. Make one reproducible acceptance workflow

Owners: existing shared test runner and prelowering fixtures; no duplicate test engine.

- [x] Assemble the existing proofs into a documented O0/no-debug acceptance entry
  point, using normal publication/native build and the supported public configuration.
  `tests/prelower_acceptance.py --results FRESH_DIRECTORY` composes eight existing
  proof groups without another scheduler. All phases pass in the
  [combined acceptance recording](results/prelower_acceptance_2026_10_20/README.md).
- [x] Run ordinary and prelowered accounting with matching behavior: 998 checks pass.
- [x] Capture the complete application compilation set from the build manifest and
  verify command coverage, including entry.cpp. Automatic capture belongs in the
  accounting acceptance entry point, `prelower_accounting_acceptance.py`.
- [x] Require exact manifest coverage in the closure audit, including entry.cpp;
  verify readiness through the existing publication owner and artifact digests, reject
  incomplete/conflicting recordings, and report middleware/layout exclusions.
  The saved accounting publication passes fresh tracing of all 16 units. See
  [evidence](results/prelower_closure_coverage_2026_10_20/README.md).
- [x] Audit every app unit and its transitive headers without PCH: zero template
  declarations and zero Clang class/function instantiations. Exclude middleware and
  layout probes deliberately; report the unit set and exclusions.
- [x] Include focused lifecycle/counter, evaluation-order, nested-reference,
  demand/replay, invalid-metadata, failure-recovery and ordinary-mode checks.
  Retain alternate metadata-selected provider proofs against accidental family coupling.
  The combined command includes metadata/inventory, contracts, public flags,
  provider/PCH enforcement, header/counter proofs, source lifecycle, O0 layout
  recovery and fresh accounting closure. Failed or incomplete phases cannot yield
  a passing aggregate; four reporting-contract cases exercise that requirement.
- [x] Record revision, toolchain, options and result locations. Separate fresh results
  from historical evidence. A generated application proof is not a native compiler
  rebuild or an end-to-end compiler performance benchmark.

Done when: a fresh checkout can reproduce the supported behavior and complete
application boundary check without undocumented API toggles or local recordings.

## 6. Consolidate documentation and remove superseded code

Bounded selector/legacy-path retirement and fresh validation are recorded in
[the cleanup evidence](results/prelower_cleanup_2026_10_20/README.md).

- [x] Rewrite the current architecture coverage summary around the single supported
  path; move historical recipe/ABI checkpoints out of current guidance.
- [x] Update CLI/API examples, runtime interoperability docs and test instructions.
- [x] Remove superseded branches, temporary helpers and stale tests only after the
  acceptance workflow covers the replacement. Preserve unrelated worktree changes.
- [x] Migrate the archived detailed source-method/constructor failure cases not
  covered by current general fixtures. Their inputs remain in
  [the historical proof archive](../../compiler/my-try/tests/historical/prelowering/README.md);
  do not count them as current acceptance or retain obsolete production paths for them.
  `tests/prelower_source_lifecycle.py` now executes both modes at O0 with matching
  counters across seven phases, five injected copy failures, strict rejection/recovery,
  unchanged replay and method/layout edits. The approved modifier-payload pointer
  contract lives in existing metadata/binding owners; signature-only forward
  declarations and prepared empty-owner transport close the other discovered gaps.
  See [migration evidence](results/prelower_source_lifecycle_2026_10_20/README.md).
- [x] Naming debt: review readable, bounded, stable specialization and operation
  names, generated file paths and debug discoverability for deeply nested types.
  Concrete type labels already use a 48-byte readable prefix plus a SHA-256 identity;
  audit other generated names before choosing changes. Preserve identity separation,
  collision handling and deterministic replay; avoid recursively expanding full type
  spellings in public names. The [consolidation/naming review](results/prelower_consolidation_2026_10_20/README.md)
  records bounded concrete/operation names and a deep-nesting proof. Source path and
  proof-only opaque-name growth, richer debug mapping and hash-collision handling
  remain explicitly non-blocking debt; no naming scheme was changed.
- [x] Review and commit bounded slices with their relevant proofs. Final combined
  O0 acceptance passed all eight phases at `da17e1ba`; see the
  [sign-off and remaining debt](results/prelower_solidified_acceptance_2026_10_20/README.md).

## Explicitly deferred / non-blocking

- [ ] Validate additional representation/operation combinations before claiming
  broader provider coverage. Keep current unsupported transports explicit and
  retain strict native validation for all admitted application inputs.


The [tentative v0.3 runtime review](runtime_prepared_facts_review_v0_3_2026_10_20.md)
retains wrapper removal, native-operation redesign and broader prepared-fact exposure.
Full runtime/template coverage, additional families or service APIs, optimization of
PHP-host execution, incremental-compiler redesign, new O2 benchmarks, ThinLTO,
linker integration, background specialization and persistent engine caches do not
block this checklist. Do not broaden current work to obtain a universal support claim.

Recommended order: settle the contract/coverage boundary (1), then bounded validation
and public-path consolidation (2–3), then verify reuse and close acceptance (4–5).
Consolidate documentation/code (6) within each slice and at final closeout.
