# TODO: end-to-end template prelowering solidification
Doc Status: planning

Date: 2026-10-20
Status: active planning checklist, combining the backend and runtime-contract audits.
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
- Source inspection confirmed three selectable implementations: historical recipes,
  runtime ABI transport, and concrete substitution. The CLI flag still selects the
  historical recipes; accounting uses concrete substitution through the API.
- Metadata owns type recipes and capabilities, but some physical conventions are
  fixed adapter protocols. Registered does not imply concrete-compatible or proven
  template-free. Callable representation can still expose `std::function`; several
  call shapes and generated header/layout cycles are explicitly unsupported.

## 1. Settle the supported boundary and runtime interaction contract

Owners: runtime metadata/specs, existing registry and prepared facts, C++ bindings.

- [ ] Produce one current coverage table for the surface already exercised by the
  general path. Distinguish **registered**, **concrete-compatible**, **template-free
  proven**, and **explicitly unsupported/unproved**. Reuse the existing
  [family/capability matrix](runtime_template_capability_matrix_2026_10_19.md);
  this is documentation/evidence, not another executable registry.
- [ ] Include scalars/strings, nested containers, source-class payloads, shared/unique/
  value storage, initialization, ordinary members, indexing/append/unset, iteration,
  conversions, output and runtime calls. Do not mark an entire family supported
  because one operation or accounting passed.
- [ ] Reconcile [metadata](../runtime_module_metadata.md) and the
  [interoperability contract](../../runtime/specs/concrete_type_interoperability.md)
  with current implementation. State the division explicitly: metadata declares
  semantics/bindings; preparation selects operations and boundaries; backend adapters
  implement concrete interfaces; runtime implementations retain dynamic algorithms.
- [ ] Audit argument/result transport, receiver adaptation, borrowing, lifecycle and
  physical interface assumptions. Keep fixed shared protocols documented in code/specs;
  add metadata only for real provider variation. Do not encode arbitrary C++ snippets
  or every implementation step in JSON.
- [ ] Preserve Clang probes as the source of native size/alignment/lifecycle traits.
  Do not duplicate ABI constants in metadata or infer semantics from native spelling.

Done when: each supported interaction has an existing semantic owner, physical
contract and proof reference; unknowns are explicitly bounded.

## 2. Validate contracts and make unsupported coverage explicit

Owners: runtime catalog installation, backend binding/admission checks, diagnostics.

- [ ] Verify required representation metadata, including native argument mapping,
  stored/payload roles, pointee contracts, scalar/byte interfaces and concrete exposure.
  Retain failed-configuration rejection without replacing installed configuration.
- [ ] Classify existing limitations: runtime member results/references/variadics;
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
- [ ] Complete strict validation for arbitrary metadata/provider header exposure,
  remaining representation/operation combinations and PCH configuration before
  migrating the public option. The listed rejection cases are not a universal proof.
- [ ] For the supported opt-in mode, report unsupported representations/operations
  explicitly. Do not silently fall back to template-bearing application interfaces.
  Choosing ordinary compilation remains an explicit caller decision.
- [ ] Establish a clear contract for transitive header exposure: `cpp_types` selection
  alone cannot prove arbitrary provider headers template-free. Combine binding checks
  with a complete generated-input closure regression, rather than promising universal
  freedom from templates based on flags alone.
- [ ] Cover representative invalid/missing contracts and failure/repair behavior.
  Implement only bounded missing adapters needed for the agreed surface; keep broader
  runtime expansion deferred instead of making it a hidden prerequisite.

Done when: supported cases work through general contracts; unsupported cases fail
clearly without publishing a usable partial result or silently changing mode.

## 3. Consolidate configuration and backend dispatch

Owners: `cpp_lowering_options`, CLI/API entry points, C++ generation/adapters.

- [ ] Make `--prelower-templates` select the same general concrete-substitution path
  used by accounting. Align the API setting and document effective options.
- [ ] Review PCH interaction: the template-free acceptance path must use no runtime
  PCH. Ensure configuration cannot misleadingly claim template-free app input while
  injecting the broad runtime PCH; leave ordinary-mode PCH behavior supported.
- [ ] Reuse ordinary expression sequencing and prepared operation/conversion facts.
  Keep the specialization engine free of family algorithms and semantic re-analysis.
- [ ] Prove mode switching invalidates affected C++ fragments without unnecessary
  semantic repreparation, and restoring settings restores deterministic output.
- [ ] Retire temporary selectors and superseded production paths only after their
  useful behavior/proofs are accounted for. Keep historical benchmark evidence;
  do not retain obsolete runtime branches solely to reproduce old experiments.

Done when: one supported prelowering selection has consistent CLI/API behavior,
while ordinary compilation and its regression proofs still work.

## 4. Verify demand, publication and native reuse end to end

Owners: existing fragment requests, specialization assembly, layout cache,
publication coordinator and native build.

- [ ] Verify unused metadata emits no concrete types; nested demand emits only its
  required closure; removing/restoring use retires/restores support artifacts.
- [ ] Verify live emission and cached-fragment replay request identical interfaces,
  with stable names and output independent of discovery order.
- [ ] Exercise body-only edits, representation/layout changes, runtime header changes
  and configuration changes through existing invalidation owners. Reuse existing
  cache keys and dependency tracking; do not create another cache or redesign the
  unfinished incremental compiler here.
- [ ] Verify failure/repair, no partial ready publication, layout dependency ordering
  and warm reuse of layouts, middleware and app objects where inputs are unchanged.
- [ ] Preserve source field/signature consistency, nested reference identity,
  evaluation order, exception cleanup and zero extra boundary allocations/copies
  in the focused O0 proofs. Keep prior optimized-build differences explicitly scoped.

Done when: reuse is correct and demand-driven, with changed inputs invalidated and
unchanged artifacts reused; no whole-compiler incremental maturity claim is implied.

## 5. Make one reproducible acceptance workflow

Owners: existing shared test runner and prelowering fixtures; no duplicate test engine.

- [ ] Assemble the existing proofs into a documented O0/no-debug acceptance entry
  point, using normal publication/native build and the supported public configuration.
- [ ] Run ordinary and prelowered accounting with matching behavior: 998 checks pass.
- [ ] Capture the complete application compilation set from the build manifest and
  verify command coverage, including entry.cpp. Automatic capture belongs in the
  unified acceptance entry point.
- [x] Require exact manifest coverage in the closure audit, including entry.cpp;
  verify readiness through the existing publication owner and artifact digests, reject
  incomplete/conflicting recordings, and report middleware/layout exclusions.
  The saved accounting publication passes fresh tracing of all 16 units. See
  [evidence](results/prelower_closure_coverage_2026_10_20/README.md).
- [ ] Audit every app unit and its transitive headers without PCH: zero template
  declarations and zero Clang class/function instantiations. Exclude middleware and
  layout probes deliberately; report the unit set and exclusions.
- [ ] Include focused lifecycle/counter, evaluation-order, nested-reference,
  demand/replay, invalid-metadata, failure-recovery and ordinary-mode checks.
  Retain alternate metadata-selected provider proofs against accidental family coupling.
- [ ] Record revision, toolchain, options and result locations. Separate fresh results
  from historical evidence. A generated application proof is not a native compiler
  rebuild or an end-to-end compiler performance benchmark.

Done when: a fresh checkout can reproduce the supported behavior and complete
application boundary check without undocumented API toggles or local recordings.

## 6. Consolidate documentation and remove superseded code

- [ ] Rewrite the current architecture coverage summary around the single supported
  path; move historical recipe/ABI checkpoints out of current guidance.
- [ ] Update CLI/API examples, runtime interoperability docs and test instructions.
- [ ] Remove superseded branches, temporary helpers and stale tests only after the
  acceptance workflow covers the replacement. Preserve unrelated worktree changes.
- [ ] Naming debt: review readable, bounded, stable specialization and operation
  names, generated file paths and debug discoverability for deeply nested types.
  Concrete type labels already use a 48-byte readable prefix plus a SHA-256 identity;
  audit other generated names before choosing changes. Preserve identity separation,
  collision handling and deterministic replay; avoid recursively expanding full type
  spellings in public names. This is recorded debt, not a new optimization blocker.
- [ ] Review and commit bounded slices with their relevant proofs.

## Explicitly deferred / non-blocking

The [tentative v0.3 runtime review](runtime_prepared_facts_review_v0_3_2026_10_20.md)
retains wrapper removal, native-operation redesign and broader prepared-fact exposure.
Full runtime/template coverage, additional families or service APIs, optimization of
PHP-host execution, incremental-compiler redesign, new O2 benchmarks, ThinLTO,
linker integration, background specialization and persistent engine caches do not
block this checklist. Do not broaden current work to obtain a universal support claim.

Recommended order: settle the contract/coverage boundary (1), then bounded validation
and public-path consolidation (2–3), then verify reuse and close acceptance (4–5).
Consolidate documentation/code (6) within each slice and at final closeout.
