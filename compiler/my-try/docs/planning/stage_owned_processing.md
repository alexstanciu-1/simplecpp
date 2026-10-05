# Stage-owned processing refactor
Doc Status: planning

Recorded 2026-10-05 against `22755175` on `v0.2/develop`.
This records the agreed direction and proposes bounded implementation batches.
No compiler behavior is changed by this document. The current implementation and
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
remaining scope question below, not an implicitly adopted compilation visitor.

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

| Current hook or coupling | Destination |
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

Analysis-owned work records also currently forward `enqueue`, `rebuild`,
`settle_members` and `retire_from` to `Preparation_Worker`. These do not cross a
stage boundary, but they mix records with processing. Proposed treatment: move
their processing dispatch into analysis in its own batch, preserving the typed
record families, state and identity. Do not flatten them into one tagged record.

## Bounded implementation sequence

Each batch is independently reviewed, validated and committed. This plan is not
permission to repair every audit finding in one patch. New retained protocols or
cross-owner redesign beyond the agreed batch require discussion first.

### A. C++ dispatch off the AST — recommended first implementation

- Add backend-local family dispatch using the existing typed handlers.
- Migrate C++ callers, then remove AST `generate_cpp()` methods and the shared
  C++ visitor contract. Do not leave a compatibility forwarding path on nodes.
- Preserve emitted text, include collection, temporary naming, callable bindings,
  fragment selection and output reuse. Semantic preparation is unchanged.
- Update dispatch-specific tests to prove the new boundary, not obsolete method names.

This is a bounded first proof of the destination without simultaneously changing
invalidation, lookup or retirement. Preparation hooks remain explicitly temporary
until B, not an exception to the target rule.

### B. Semantic traversal and queries off shared structures

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

### C. Analysis-internal work dispatch

Move work-record executable adapters into `Preparation_Worker` or its analysis-owned
helpers. Keep role-specific records and typed accessors. Preserve enqueue deduplication,
member settlement, work versions, retirement queues and dependency unlinking.
This is separate from changing the frontend-to-analysis handoff.

### D. Frontend/analysis lifecycle protocol — design gate

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

### E. Backend consumption boundary — design gate

Replace worker-class read-only dependencies with truthful result queries/guards where
needed, without copying canonical facts or rerunning preparation. Define who consumes
the shared change handoff and when.

Recommendation for the current single active backend: coordinator acknowledgment
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

This planning-only change needs document/link review, not compiler tests.
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

## Remaining questions and non-goals

1. **Neutral maintenance boundary:** keep `maintain(worker)` solely as structural
   edge/token enumeration, with the invoking operation owning recursion, or remove
   it too? Recommendation: retain it for this refactor, along with lazy `children()`
   inspection. Neither becomes the traversal path for preparation or emission.
   Removing it touches cleanup, token remapping and lifecycle traversal and needs
   a separately agreed batch. It is not a back door for stage-specific node hooks.
2. **First implementation boundary:** recommendation is A first, followed by B/C;
   review the D/E protocols before implementing them. The user has requested this
   plan, not execution of the entire sequence.

The acknowledgment choice in E and exact change/retirement protocol in D are later
design gates, not blockers for A. Dispatch helper names and file splits are local
implementation choices, not additional language-design questions.

Non-goals: new language semantics, Chapter 06 construction execution, CFG design,
LLVM semantic convergence, generic pass frameworks, universal visitors, AST ownership
or storage redesign, output partitioning, new concurrency, and broad folder reshuffling.
LLVM remains parked; report an unavoidable compatibility impact rather than silently
expanding its semantic pipeline. Smaller SB-07 concerns remain separate debt.
