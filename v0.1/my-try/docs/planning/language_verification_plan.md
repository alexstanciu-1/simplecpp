# Language-wide verification and repair plan
Doc Status: planning

Verification audit completed — frozen 2026-10-18 snapshot.
See the [final verification report](language_verification_report.md).
The scalar-admission slice remains closed. No repair goal has been started.
See the [verification progress ledger](language_verification_progress.md).

The checklist is expanded into [119 concrete checks](language_verification_checks.md)
and [36 probe/reconciliation tasks](language_verification_probes.md). The
[machine-readable inventory](language_verification_inventory.json) retains runner
recipes/options, catalog routes, runtime declarations and source/fixture discovery.
These are the frozen planning mappings. Current execution evidence and unresolved
coverage are recorded separately in the coverage, runtime and issue ledgers; the
planning inventory itself is not a claim of verification.

## Objective and scope

Establish an auditable account of what v0.2 can parse, prepare, compile and execute
across the PHS language and its public runtime surface. Preserve supported strict
v0.1 source behavior except approved incompatibilities; separately verify promised
v0.2 behavior. Identical v0.1/v0.2 C++ lowering is not an acceptance criterion.

The [coverage checklist](language_verification_checklist.md) is the starting map.
Coverage means observable behavior and correct rejection, not merely a file or
catalog chapter marked done. Existing tests and catalog closeouts are evidence
inputs; the authoritative specs define the obligations.

Retain the existing boundaries:

- Strict PHS first. Inventory legacy syntax/profile cases but leave their execution
  deferred. Keep original-source compatibility and explicitly adapted probes apart.
- Cover PHP-hosted my-try plus generated C++ execution. Include production
  multi-file/build/publication integration because compilation success depends on it.
- Native compiler parity, LLVM, legacy STAN, JSS and additional platform/toolchain
  sweeps stay separately recorded and deferred unless explicitly selected. A test
  requiring one of these is classified, not silently launched by a broad runner.
- Inventory public runtime APIs and optional modules. Missing dependencies or
  unavailable services remain visible blockers, not passes or assumed absence of
  language support. Use local fixtures/fakes where they prove the actual contract.
- No compiler/runtime fixes or new semantics during verification. Test tooling,
  minimal probes and evidence recording are allowed; changed expectations need a
  cited contract and preserved before/after evidence.
- Execute tests on `alex-ai@dev002.travelfuse.ro`, using isolated versioned snapshots
  and the shared runner with up to 32 continuously refilled workers. Budget memory
  for compile jobs; record actual concurrency and environment. No test runs on WSL.

## Sequence and gates

1. **Prepare the coverage inventory.** Reconcile every catalog card, documented
   language rule, existing strict fixture and public runtime module against the
   checklist. Split its broad rows into finite, independently verifiable checks.
   Assign IDs, owners, source contracts, runner/probe and expected observations.
   Route existing CH15/Sxx debt rather than duplicating it. The concrete planning
   inventory now supplies this starting worklist; assertion-level/card-level
   reconciliation is explicit work in verification wave 1 and continues as results
   arrive. Review the scope before launching the verification goal.
2. **Run one verification goal.** Freeze the tested source hashes and a v0.1
   baseline; use the previously pinned baseline as the starting candidate and
   recheck local/remote references before selecting it. Run existing relevant tests,
   fill uncovered obligations with focused probes, and record first blockers.
   This goal gathers evidence; it does not repair the compiler.
3. **Produce the issue catalog.** Group reproductions by responsible concept/owner,
   distinguish masked failures from independently reached failures, and assign both
   a semantic disposition and one of the four handling classes below. Triage as
   findings arrive, then reconcile it at goal closeout.
4. **Run a separate simple-fix goal.** Select an explicit issue-ID set from the
   verified catalog. Repair, prove and update each issue through its existing owner.
   Promote newly discovered complexity instead of silently broadening the goal.
5. **Discuss light changes in batches, then run a goal for the agreed batch.** Each
   agreement states behavior, touched owners, exclusions and acceptance tests.
6. **Discuss normal changes and red flags individually in this chat.** Each can
   produce its own bounded implementation goal after design agreement.

No repair goal starts automatically when verification finishes. No speculative
repair is justified by a preliminary difficulty label.

## Two independent classifications

Semantic disposition answers what the finding means:

| Disposition | Required evidence |
| --- | --- |
| Missing/broken support | Valid required behavior, reproduction and failing stage; v0.1 baseline proof when claiming regression |
| Intentional v0.2 change | Exact approved contract/change and expected new behavior |
| Deferred legacy syntax/profile | Original spelling/profile and strict-scope reason; any adaptation gets separate evidence |
| Contract conflict/decision | Conflicting authorities/expectations and concrete observable alternatives |
| Test/harness defect | Independent evidence that the oracle or adapter is wrong, without weakening the real assertion |
| Environment/provider blocker | Missing dependency/service/toolchain and what remains untested |
| Coverage gap | A required obligation has no adequate behavioral or rejection proof yet |

Handling class answers how to proceed:

| Class | Admission rule | Handling |
| --- | --- | --- |
| Simple fix | Contract settled; existing owner/model/path is sufficient; no new retained fields, semantic choice or lifecycle/control-flow protocol; focused independent proof exists; bounded impact understood | Eligible for the explicitly scoped autonomous simple-fix goal |
| Light discussion | Behavior/detail needs agreement or a small local algorithm change; existing structures and ownership remain sufficient | Brief proposal, agree batch, then implement via goal |
| Normal discussion | Add/change retained structures, processing flow, ownership/lifetime rules or public contracts | Discuss design and validation per issue before implementation |
| Red flag | Current model cannot faithfully represent the requirement; repair crosses foundational owners or exposes systematic unsoundness | Stop that workstream; describe restructuring options, risks, dependencies and validation cost |

Difficulty is not line count. `++` working and `--` missing is only a simple fix
if the same admitted storage, snapshot, overflow and lifetime contracts apply.
A one-line permission change that affects ownership or error semantics is not simple.
Uncertain findings are provisional and cannot enter the autonomous simple queue.
Intentional changes and deferred legacy cases are not compiler-fix candidates.
A red flag in one family need not stop independent verification elsewhere.

## Evidence and coverage model

Maintain two linked ledgers during verification:

- **Checks:** check ID, family, contract link, original/adapted/new source lane,
  v0.1/v0.2 applicability, value/type/state combinations, expected values/types/
  effects/errors, execution layers, runner/seed IDs, source/toolchain hashes,
  status, evidence path and blocking issue IDs.
- **Issues:** issue ID, existing Sxx/CH15 links, minimal reproduction, expected and
  actual behavior, first failing stage, responsible owner, affected check IDs,
  masked/dependent checks, semantic disposition, handling class and rationale,
  dependencies, proposed bounded repair and acceptance proof.

Check statuses: `unmapped`, `ready`, `passed`, `failed`, `blocked`,
`intentional-difference`, `deferred`. A compile-only pass must say compile-only.
Old evidence is `historical` until its applicability to the frozen snapshot is
established. Reuse unchanged evidence with explicit provenance; rerun affected
checks after changes. No renumbering or silent rewriting of dated audit totals.

For each relevant feature, check positive values, negative admission, runtime
errors, boundaries, evaluation order, ownership/lifetime, composition and
incremental recovery. Mark an axis not applicable with a reason. Do not require
the entire Cartesian product: exhaust finite truth/state tables, exercise each
admitted type-pair rule and use targeted interactions at semantic boundaries.
Record combinations not exercised so “representative” cannot become “exhaustive.”

Separate these proof layers: parse/collection; preparation decisions/types;
generated-C++ compilation; runtime value/effects/diagnostics; multi-file/export
integration; fresh versus incremental/recovery. Native compiler execution is a
separate dimension and remains deferred in this plan.

## Verification goal completion

The verification goal is complete when:

1. Every in-scope catalog card, semantic obligation and discovered test group maps
   to a check, or to an explicit exclusion/deferred item with a reason. Public
   runtime modules have per-symbol inventory, not only module-level green checks.
2. Every planned check has evidence or an identified blocker/explicit deferral.
   No unexplained `unmapped`, `ready` or unrun entries remain. Unexecutable checks
   are reported as coverage gaps; a complete audit does not mean complete support.
3. Every failure/blocker has a grouped issue with disposition, owner, handling
   class, dependencies and next action. Ambiguity remains a discussion item.
4. The final report shows coverage by family and proof layer, measured results,
   intentional differences, blind spots and the four actionable handling queues.
5. The proposed simple-fix batch is an explicit list with acceptance checks and
   no unresolved semantic/structural decisions.

Do not mark verification complete just because existing suites pass, tests are
numerous, or all failures have been hidden behind one broad blocker. Probe behind
first blockers only in a separately labelled lane when the adaptation is valid.

## Proposed verification goal text

> Verify the approved language coverage checklist for the frozen v0.2 my-try
> snapshot on dev002, covering strict PHS, supported v0.1 compatibility, promised
> v0.2 semantics and public runtime/project integration. Map every in-scope
> obligation, run the applicable existing suites, add focused missing probes, and
> retain per-check evidence. Make no compiler/runtime fixes. Preserve intentional
> changes and separate legacy/adapted sources. Produce a coverage report and
> deduplicated issue catalog with semantic dispositions, the four agreed handling
> classes, dependencies and an explicit proposed simple-fix batch. Record all
> blocked/deferred coverage and satisfy the completion criteria in this plan.

The goal should be created only after this plan's inventory/scope review. No token
budget is proposed; execution should end at the agreed evidence criteria.
