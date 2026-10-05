# Stage-owned processing refactor
Doc Status: planning

Recorded 2026-10-05 against `22755175` on `v0.2/develop`.
This records the agreed direction and bounded implementation batches.
Batches A–C and D1 are implemented in the 2026-10-05 follow-ups. D/E ownership
directions are agreed; remaining invalidation/retirement and consumption work is
split into bounded implementation slices.
The current implementation and
findings are described in the [stage-boundary audit](stage_boundary_audit.md).
Language semantics remain owned by the top-level specs.

## Agreed destination

**Structures describe the compiler model; each running stage consumes that model
and owns its traversal, dispatch and algorithms. The coordinator sequences stages.**

- Parsing calls collection directly. Collection is a sub-step of parsing, not an
  independent analysis stage. Its files already live in `03_parse/collect/`.
- Earlier stages do not call later-stage processing or modify its scheduling state.
- Shared structures may carry typed cross-stage identities and attached facts.
  Shared data does not imply shared processing ownership.
- No required `accept()` method, universal visitor interface or shared compilation
  walker replaces `prepare()` / `generate_cpp()` on AST nodes.
- Each stage follows the typed relationships it needs, in its required order.
  Traversal need not start at a file root or visit the entire syntax tree:
  incremental preparation and emission retain selected-work entry points.
- Structures retain constructors, local representation invariants, typed accessors,
  fact attachment/clearing and structural queries. Accessors must not secretly
  complete declarations, subscribe dependencies or invoke workers.
- Workers and invocation contexts remain transient; retained records never own them.

This supersedes the neutral-visitor replacement discussed after SB-02. A visitor
is not needed to establish the stage boundary. Neutral maintenance is the one
explicitly retained facility below, not an implicitly adopted compilation visitor.

## Dispatch and traversal shape

Reuse existing specialized workers rather than build a second implementation.
For example, analysis-owned expression dispatch selects concrete preparation
methods; body processing selects statement/completion methods. C++ owns its own
expression/statement/declaration dispatch and reuses `CPP_Syntax`'s typed handlers.

Conceptual entry points (names/signatures are not a new public API):

```text
analysis: prepare_expression(expression_node, context) -> prepared_expression
analysis: prepare_statement(statement_node, context) -> statement_completion
analysis: prepare_if(if_node, context) -> statement_completion
backend:  emit_expression(expression_node, context) -> string
backend:  emit_if(if_node, context) -> string
```

At heterogeneous boundaries, use explicit stage-local type selection and supported
checked narrowing. Once the concrete type is known, call its typed handler directly.
Organize selection by existing syntax families, not one compiler-wide node switch.
Do not use reflection, dynamic method-name construction, callback registries or a
parallel node/payload model. Do not add a redundant kind flag to avoid a typed call.

Each operation has one traversal owner. A dispatcher selects a handler; that handler
owns child processing or delegates it to one existing worker. Do not automatically
walk children and then have the handler walk them again. Different stages traversing
the same fields is intentional, not a reason to merge their algorithms.

Preserve the distinction between preparing every relevant branch for diagnostics
and dependencies, and emitting lazy runtime evaluation. Preserve evaluation order,
single-evaluation snapshots, local scope restoration and transfer targets.

Unsupported syntax must still fail explicitly at the proper phase. Removing an
inherited hook must not turn rejection into an empty output or a successful no-op.
Inspection-only trivia remains distinct from executable statements.

## Owner mapping

| Original hook or coupling | Destination |
| --- | --- |
| AST `prepare`, `prepare_completion` | Analysis expression/body/declaration dispatch and existing semantic workers |
| Loop `prepare_header`, `prepare_continuation` | `Loop_Preparation` and typed loop handlers |
| AST `integer_constant()` processing | Analysis-owned bounded constant evaluation; retain plain fact accessors |
| AST `generate_cpp`, shared `cpp_generation_worker_i` | Backend-local dispatch and typed C++ handlers; remove the model protocol |
| Declaration `select_preparation`, collected `preparation_work(worker)` | Analysis-owned work selection using the same retained identities |
| Callable `require_signature(context)` | Analysis-owned completion/dependency query, followed by read-only signature access |
| Parser writes to preparation state | Parser-owned change facts consumed by analysis through coordinator sequencing |
| Scope lookup invokes preparation observation | Analysis-owned observed lookup over shared lexical membership |
| Analysis deletes collected membership and syntax | Coordinator orders semantic retirement, then frontend-owned disposal |
| Backend clears preparation change handoff | Explicit successful-consumption acknowledgment outside emission |

Review by behavior, not method spelling. For example, `body_runs_first()` or
`transfer_kind()` can be descriptive queries; executing loop preparation cannot.
Similarly, `require_preparation()` may remain a checked read of attached facts.
Moving a method between files without changing its owner is not sufficient.

Analysis-owned work records originally forwarded `enqueue`, `rebuild`,
`settle_members` and `retire_from` to `Preparation_Worker`. These do not cross a
stage boundary, but they mixed records with processing. Batch C moved their
processing dispatch into the analysis worker, preserving typed record families,
state and identity rather than flattening them into one tagged record.

## Bounded implementation sequence

Each batch is independently reviewed, validated and committed. This plan is not
permission to repair every audit finding in one patch. New retained protocols or
cross-owner redesign beyond the agreed batch require discussion first.

### A. C++ dispatch off the AST — implemented 2026-10-05

- Add backend-local family dispatch using the existing typed handlers.
- Migrate C++ callers, then remove AST `generate_cpp()` methods and the shared
  C++ visitor contract. Do not leave a compatibility forwarding path on nodes.
- Preserve emitted text, include collection, temporary naming, callable bindings,
  fragment selection and output reuse. Semantic preparation is unchanged.
- Update dispatch-specific tests to prove the new boundary, not obsolete method names.

This is a bounded first proof of the destination without simultaneously changing
invalidation, lookup or retirement. Preparation hooks remained explicitly temporary
at that checkpoint and are removed by B, not retained as an exception.

### B. Semantic traversal and queries off shared structures — implemented 2026-10-05

- Move expression, type, statement/completion, declaration and auxiliary syntax
  processing to their analysis owners, reusing existing algorithms.
- Move loop-header/continuation and constant-evaluation dispatch too; do not remove
  only the two conspicuously named hooks.
- Move source/runtime callable-signature completion into an analysis-owned query.
  Source targets still complete declarations and register dependencies; registered
  runtime targets expose their already-installed contracts without source work.
- Move syntax/collected work selection to analysis; preserve work identity, recursion,
  prerequisite ordering and failure propagation.
- Split into coherent family sub-batches if needed, with old hooks removed from each
  migrated family. No duplicate retained facts or new general visitor layer.

### C. Analysis-internal work dispatch — implemented 2026-10-05

Move work-record executable adapters into `Preparation_Worker` or its analysis-owned
helpers. Keep role-specific records and typed accessors. Preserve enqueue deduplication,
member settlement, work versions, retirement queues and dependency unlinking.
This is separate from changing the frontend-to-analysis handoff.

### D. Frontend/analysis lifecycle protocol — agreed; D1 implemented

Address SB-01, SB-03 and SB-05 together in design, then implement bounded parts:

- Separate frontend syntax/identity changes from analysis invalidation decisions.
  First inventory whether existing change fields can express the handoff truthfully;
  propose any additional retained data before adding it. No generic event bus or
  second symbol graph.
- Keep lexical membership neutral. Analysis owns observed lookup and records every
  visited pool, including missing, ambiguous and intermediate candidates.
- Coordinator orders semantic notification/retirement before frontend membership
  disposal. Preserve failure/retry lifetimes and source-type retirement.

Before implementation, specify changed-body identity transfer, failed-parse retry,
successful parse publication, deletion and repeated synchronization transitions.
Do not erase parser scheduling writes until equivalent invalidation is proved.

Confirmed: parsing may transfer retained work identity but must not decide its
preparation state. Reuse existing change fields if they preserve unconsumed changes
across repeated parsing/failure; discuss any new retained structure before adding it.
Analysis owns observed traversal without a visitor/callback interface. The coordinator
orders semantic retirement before frontend-owned disposal after successful parse joins.
Implementation order: D1 observed lookup; D2 invalidation/retirement; E consumption.

### E. Backend consumption boundary — agreed direction; implementation pending

Replace worker-class read-only dependencies with truthful result queries/guards where
needed, without copying canonical facts or rerunning preparation. Define who consumes
the shared change handoff and when.

Agreed for the current single active backend: coordinator acknowledgment
only after successful output assembly/publication. Failed emission retains changes
for retry. Review standalone C++ entry points before fixing the API. Per-backend
consumption versions remain an alternative if multiple independent consumers become
an actual requirement; this plan does not add them speculatively.

### F. Consolidate

Re-audit executable dependencies, remove dead protocols and update MODEL, ownership,
AST layout, preparation README, lifecycle guidance and local AGENTS together with
the implemented batches. Mark audit findings resolved only when their proofs exist.
Keep historical evidence unchanged. Resume Chapter 06 construction work afterward.

## Validation and acceptance

The original planning-only change needed document/link review, not compiler tests.
For implementation, select focused existing suites and add independent assertions:

- A: specialization dispatch, S2S, callables and incremental C++; generated-program
  cases covering calls/defaults, lazy selections and loop/switch transfer order.
- B: preparation dispatch/work, calls, control flow/fallthrough, wrapper selections,
  constants, capabilities and recovery; shared structures cannot invoke completion
  or emission, and reading facts does not schedule work.
- C/D: stable work and declaration identities; unchanged signatures/body-only edits;
  missing/duplicate names and recovery; deletion/re-addition; failure then retry;
  token cleanup/generations and clean-versus-incremental agreement.
- E: no-op output reuse, deleted fragment/header retirement, failed-emission retry
  and successful acknowledgment. Emission never completes semantic work.
- Audit structural dependencies as well as output behavior. Permit shared data
  types and explicit coordinator calls; do not use folder-name grep as sole proof.

Use the shared runner with 12 continuously refilled slots and its normal FPM/CLI
fallback. Keep generated-program evidence separate from native compiler evidence.
Portable-PHP narrowing, typed collection boundaries and transient context lifetimes
apply throughout. Removing virtual hooks can affect native polymorphism: preserve
a truthful polymorphic model boundary and request a focused native checkpoint before
claiming native compatibility. Do not add dummy methods or change converter/runtime
contracts to conceal a problem.

Native compiler builds, conversion, broad regressions and style/lint sweeps remain
on explicit request. No execution-speed or C++ compile-time improvement is claimed
without measurements; correctness and ownership are the objectives of this refactor.

### Batch A evidence — 2026-10-05

Candidate: `4d4a729c` plus the batch A source/test/documentation overlay.
Removed the AST C++ methods and shared interface. `CPP_Syntax` owns expression,
statement and interpolation-part selection; known concrete children use direct
typed handlers. Removed unused file/declaration visitor adapters; the active
fragment/declaration path is unchanged. Neutral maintenance and preparation are
unchanged. Portable-PHP checked narrowing is explicit at family dispatch boundaries.

- Baseline export: `/tmp/my-try-cpp-dispatch-before-20261005`.
- Function proofs: `/tmp/my-try-cpp-dispatch-functions-20261005` — 12 PHP suites
  and 77 generated C++ programs passed with PCH.
- Control-flow proofs: `/tmp/my-try-cpp-dispatch-control-20261005` — 12 PHP suites
  and 132 generated C++ programs passed with PCH.
- Final overlay checks: `/tmp/my-try-cpp-dispatch-final-20261005` — seven PHP suites
  passed, including the extended dispatch test, callables, cleanup and work/retry
  checks. Its 1,049 exported C++ files are byte-identical to the baseline.
- There are 19 distinct passing PHP suites across these overlapping selections.
  New assertions cover absence of AST/backend visitor hooks, unsupported-parent
  isolation, prepared-fact/work identity and state preservation.
- The runner used CLI fallback because sandbox Unix-socket binding prevented FPM
  startup. No native compiler rebuild, conversion, broad regression or lint sweep
  was performed. Generated-program execution is not native compiler evidence.

At the batch A checkpoint, SB-02 was partially resolved and B/C remained next.
D/E still require protocol review.

### Batch B evidence — 2026-10-05

Candidate: `060d31ca` plus the batch B source/test/documentation overlay.
Removed AST preparation/completion, loop header/continuation, constant evaluation
and declaration work-selection hooks. Existing semantic workers now own dispatch,
fact attachment and traversal. Collected definitions no longer select preparation
work. Callable `signature()` is read-only; `Call_Preparation::require_signature`
owns source completion and dependency registration. No new retained structure,
universal visitor, scheduler transition or parser invalidation protocol was added.
Analysis-internal work-record adapters remain for C.

- Baseline export: `/tmp/my-try-analysis-dispatch-before-20261005`.
- Initial focused checks: `/tmp/my-try-analysis-dispatch-php-20261005` — 12 passed.
- Function proofs: `/tmp/my-try-analysis-dispatch-functions-20261005-2` — 12 PHP
  suites and 77 generated programs passed. All 1,049 exported C++ files match the
  baseline byte-for-byte. The first function run exposed two accidentally renamed
  conditional/coalesce worker references during consolidation; these were repaired
  before the successful rerun. That failed run is retained without the `-2` suffix.
- Control-flow proofs: `/tmp/my-try-analysis-dispatch-control-20261005` — 12 PHP
  suites and 132 generated programs passed. Generated-program runs use PCH.
- Supplementary checks: `/tmp/my-try-analysis-dispatch-extra-20261005` — 13 passed,
  including callable query/accessor separation, work/retry, parsing/collection,
  capabilities, token cleanup and nullable/Result/mixed/probe/take preparation.
  Across the overlapping selections, 27 distinct PHP suites passed.
- **Existing debt, not a green check:** `tests/dependency_cleanup.php` fails at
  line 37, `Replaced body retained lookup registration`. The unchanged `060d31ca`
  compiler/test/framework archive reproduces the same failure in
  `/tmp/my-try-analysis-dispatch-baseline-cleanup-20261005`. The baseline tree is
  `/tmp/my-try-analysis-baseline-ni9Akt`. Leave the test unchanged and triage its
  lookup-registration expectation/behavior with D; the remaining checks in that
  suite are not claimed as passing.
- Structural checks find no processing worker/context references in AST structures
  or callable/collected semantic hooks. Neutral maintenance and fact access remain.
  The shared runner used its sandbox socket-related CLI fallback. No native compiler
  rebuild, conversion, broad regression or lint sweep was performed.

SB-02's shared-model hook migration is complete. Next: C, then review D/E protocols.

### Batch C evidence — 2026-10-05

Candidate: `6064dc5d` plus the batch C source/test/documentation overlay.
Removed work-record `enqueue`, `rebuild`, `settle_members` and `retire_from`
hooks. `Preparation_Worker` selects typed handlers directly; those implementation
handlers are now private. Records retain role-specific identity, state and typed
queries. No retained structure or scheduling/lifecycle policy was added.

- Focused checks: `/tmp/my-try-work-dispatch-20261005` — 12 PHP suites passed:
  preparation work, incremental preparation, recovery, incremental C++, callables,
  capabilities, constructed types, token cleanup/generations, parsing/collection,
  collected roles and specialization dispatch.
- Extended work assertions cover absence of processing hooks on all four roles,
  one-time initial completion, parameter/field settlement, no-op identity/version
  preservation and retirement unlinking. Existing incremental/recovery cases cover
  deduplication, nested layout propagation, failed retries and deletion/re-addition.
- The shared runner used CLI fallback after sandbox socket binding prevented FPM
  startup. No native build, conversion, broad regression or lint sweep was run.
  The previously baseline-reproduced dependency-cleanup failure remains D debt;
  that suite was not rerun or represented as passing in this batch.

At the C checkpoint, D/E lifecycle and consumption protocols still needed discussion;
the subsequent agreement is recorded above.

### D1 evidence — 2026-10-05

Candidate: `8713aad7` plus the observed-lookup source/test/documentation overlay.
`Observed_Lookup` owns analysis traversal of type/function/constant namespaces.
Plain `Scope_Lookup` exposes two-argument read-only queries. All five preparation
call sites use observed lookup; subscription identity/storage and scheduler policy
are unchanged. No new retained structure or callback protocol was introduced.

- Baseline diagnosis: `/tmp/my-try-observed-lookup-baseline-20261005`.
  The original dependency-cleanup assertion fails as before. The diagnostic probe
  shows that the replaced body is no longer subscribed; the remaining subscriber
  is the valid `function_signature_work` for `a`, observing its own uniqueness.
  This was a stale test expectation, not a production cleanup leak. Revised checks
  require old-body detachment, orphan removal and preservation of shared subscribers.
- Focused proof: `/tmp/my-try-observed-lookup-20261005-2` — 12 PHP suites passed,
  including the new observed-lookup suite and the complete dependency-cleanup suite.
  Other checks cover incremental preparation/C++, recovery, typed work, callables,
  capabilities, constructed types, publication, parsing/collection and token cleanup.
- The initial run (without the `-2` suffix) failed at bootstrap because the initial
  worker name collided case-insensitively with retained `preparation_lookup`.
  Renaming the worker to `Observed_Lookup` resolved that before the passing rerun.
- Shared runner: 12 slots, CLI fallback after sandbox FPM socket denial.
  Supplementary S2S preparation/export checks passed in
  `/tmp/my-try-observed-lookup-exports-20261005-2` (13 distinct PHP suites total).
  All 1,049 exported C++ files are byte-identical to the retained pre-B baseline
  at `/tmp/my-try-analysis-dispatch-before-20261005/programs`.
  The initial export invocation omitted output-directory creation: assertions passed
  but writes warned and produced no exports. It is not counted as export evidence;
  the corrected run created the directory first and has empty stderr.
  No native compiler build, conversion, broad regression or lint sweep.

SB-03 and the recorded cleanup-test debt are resolved. Next: D2, preserving parser
identity transfer while moving preparation-state decisions into analysis.

## Remaining questions and non-goals

1. **Neutral maintenance boundary — agreed:** retain `maintain(worker)` as structural
   edge/token enumeration, with the invoking operation owning recursion, along with
   lazy `children()` inspection. Neither becomes the traversal path for preparation or emission.
   Removing it touches cleanup, token remapping and lifecycle traversal and needs
   a separately agreed batch. It is not a back door for stage-specific node hooks.
2. **Implementation order — agreed:** A first, followed by B/C; review the D/E
   protocols before implementing them. Completed batches are marked above.

The acknowledgment owner in E and ownership direction in D are agreed above.
Exact transition handling must be validated within each slice; new retained
structures still require discussion. Dispatch helper names and file splits are
local implementation choices, not additional language-design questions.

Non-goals: new language semantics, Chapter 06 construction execution, CFG design,
LLVM semantic convergence, generic pass frameworks, universal visitors, AST ownership
or storage redesign, output partitioning, new concurrency, and broad folder reshuffling.
LLVM remains parked; report an unavoidable compatibility impact rather than silently
expanding its semantic pipeline. Smaller SB-07 concerns remain separate debt.
