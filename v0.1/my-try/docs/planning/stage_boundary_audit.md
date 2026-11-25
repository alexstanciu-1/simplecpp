# Stage dependency and ownership audit
Doc Status: planning

Recorded 2026-10-05 against `9cf1d28c`, with the callable correction and collection
move described below applied. Findings are for discussion, not authorization for a
pipeline rewrite. Language semantics remain defined by top-level specs.

Follow-up discussion selected stage-owned traversal and dispatch, with no required
visitor on shared structures and collection explicitly a parsing sub-step. See the
[refactor plan](stage_owned_processing.md) for the agreed destination, proposed
batches and remaining questions. Findings below describe the audited implementation;
the planning follow-up alone does not mark them repaired. Batch A subsequently
removed AST C++ hooks and the shared C++ worker interface. Batch B removed AST
semantic hooks and callable/work-selection processing from shared identities.
SB-02's shared-model hooks are resolved. Batch C also moved analysis-internal
work-record dispatch into its worker. D1 moved observed lookup to analysis and
resolved SB-03. D2 removed parser scheduling writes and split semantic retirement
from frontend disposal, resolving SB-01/SB-05. E moved acknowledgment into the
coordinator and extracted read-only queries, resolving SB-04. SB-06 remains parked;
SB-07 retains its separately scoped debt.

## Outcome and rule

The main pipeline is sequenced by the coordinator, not by stages calling the next
stage. However, incremental invalidation, semantic-aware lookup and AST operation
hooks cross ownership boundaries. The callable C++ hook was one instance of this
problem, not the only place worth reviewing.

**Shared structures may mix; stage processing must not.** An earlier worker should
publish its own facts and changes, not invoke a later worker or change that worker's
scheduling state. Consumers may read established upstream facts. Shared fields,
identity references and neutral traversal are not themselves violations. Moving a
processing operation into a structure does not make its dependency disappear.

The composition root/coordinator necessarily knows the stages and their reset order.
Parser-called collection is one frontend operation. Both are explicit exceptions,
not permission for arbitrary forward calls. Existing model operation hooks need the
separate decision in SB-02; this audit does not silently forbid all visitors.

## Changes completed in this slice

- Removed `cpp_reference()` from `callable_target_i`, `collected_function` and
  `registered_static_factory`. [CPP_Callables::reference](../../05_backend/cpp/callable_bindings.php)
  now owns the bounded source/factory dispatch and rejects unsupported target kinds.
  Semantic callable contracts contain no backend context, spelling or operation.
- Used `git mv` for both collection files from `04_analyze/collect/` to
  [03_parse/collect/](../../03_parse/collect/). Updated bootstrap and active navigation;
  repaired archival link destinations without rewriting their historical findings.
  Collection behavior, namespace, public identities and load order are unchanged.
- Added the forward-processing ownership rule to local operating/ownership guidance.
- Added callable regression assertions for the absence of backend methods and both
  backend-owned dispatch paths. No wider findings below were implemented.

The existing `require_signature(preparation_context)` semantic completion hook was
retained in that slice. The follow-up plan now places completion/dependency work in
analysis; batch B implements that query plus a read-only `signature()` accessor.
The original finding is in SB-02.

## Coverage and method

Inventoried all 115 PHP files in the eight production areas below using PHP-token
identifier/declaration cross-references, supplemented by direct inspection of worker
entry points, dispatch bodies, dynamic context calls, shared-state writes and bootstrap.
The identifier scan excludes comments/strings; it is an inventory, not a complete
call graph or a proof that runtime-dispatched dependencies do not exist. In particular,
the C++ visitor interface itself lives under parsing, so numeric-folder scans alone
would miss its semantic backend coupling.

Reviewed coordinator/lifecycle, host reporting/native execution and source discovery
in addition to numbered stages. Checked test/tool composition and source-copy discovery
for the moved paths. Tests, samples, generated/temp output and archived evidence are
not stage implementations; this is an architecture audit, not a correctness/security
review of every test or helper algorithm. Existing untracked user files were untouched.

| Area | PHP files | Assessment |
| --- | ---: | --- |
| `01_prepare_inputs` | 3 | No calls into later numbered workers; shared source records and session-service coupling (SB-07) |
| `02_tokenize` | 4 | No forward processing found; scanner consumes input loading, buffer owns token appending |
| `03_parse` including collection | 22 | Actual preparation-state writes and preparation-aware lookup; shared model hooks (SB-01–03) |
| `04_analyze` | 41 | No direct C++/LLVM/native worker invocation found; cross-owner retirement and entry ABI policy (SB-05, SB-07) |
| `05_backend` | 19 | C++ consumes prepared facts but acknowledges shared changes; LLVM has separate legacy semantics (SB-04, SB-06) |
| `06_native` | 2 | Host-only build/run of emitted LLVM artifacts; no feedback into analysis |
| `compiler` | 19 | Deliberate composition/lifecycle plus shared model/services; not all files here are coordinator algorithms |
| `helpers` | 5 | Storage/cursor infrastructure; no compiler-stage processing dependency found |

## SB-01 — parser controls preparation scheduling

**Resolved by D2, 2026-10-05:** parsing transfers identity and accumulates source
changes; analysis selects work from those flags. Repeated parsing/failure cannot
erase unconsumed changes. Original finding:

**High priority; actual forward control, not just a shared field.**

[Parser_Run::transfer_body_work and finish_declaration](../../03_parse/parser.php)
transfer `body_work` and set `preparation_state::pending` plus work change status.
Transferring an identity with retained syntax is understandable. Choosing a later
stage's scheduling state is a different responsibility. A preparation state-machine
change currently requires parser knowledge.

**Direction to discuss:** parser reports source/declaration/body changes and retains
identity through a neutral handoff; preparation or the coordinator owns invalidation.
Do not simply delete these writes: unchanged-signature body edits, failed-parse retry,
replaced bodies, signature changes and retained fragments all depend on this protocol.
Keep stable work identity; avoid introducing a second symbol graph or a generic event
bus as a shortcut. This repair touches parser reconciliation, scheduling and lifecycle.

## SB-02 — shared syntax includes phase-specific executable hooks

**Boundary decision first; broader than the callable correction.**

[ast_node_i and cpp_generation_worker_i](../../03_parse/structures/abstractions.php)
expose `prepare(preparation_context)` and `generate_cpp(...)`. The C++ protocol is
defined in the syntax model itself. Concrete nodes mostly dispatch to the supplied
visitor for C++; preparation hooks directly call `Expression_Preparation`,
`Declaration_Preparation`, `Loop_Preparation`, etc. Return/loop nodes also expose
completion/header work. These are executable phase adapters, not only fact storage.

[Collected definitions](../../03_parse/collect/structures.php) similarly dispatch
preparation-work selection, and `collected_function::require_signature()` requests
completion/dependency registration before returning facts.

**Important distinction:** the parser does not run C++ generation through these hooks.
C++ initiates its own visit; preparation initiates its own node dispatch. This is
less coupled than the removed hook's direct `CPP_Callables` call, but shared syntax
still advertises a specific backend and concrete preparation services.

**Follow-up decision:** move phase adapters and traversal to their consuming stages;
do not replace them with a shared visitor or `accept()` protocol. Reuse typed workers
behind stage-local family dispatch. Attached facts and owning fields remain; the
neutral maintenance boundary is an explicit question in the refactor plan. Do not
introduce one giant switch or duplicate recursion within an operation. This affects
many nodes and consumers; batches A/B now implement it. Neutral maintenance is
explicitly retained, with caller-owned recursion. Batch C subsequently removed
analysis-internal work adapters too. See the plan for validation and the remaining
lifecycle/consumption design gates.

## SB-03 — parse-layer lookup calls preparation observation

**Resolved by D1, 2026-10-05:** `Observed_Lookup` in analysis owns the observed
traversal. Plain `Scope_Lookup` no longer accepts a preparation context or invokes
observation. Tests cover all three namespaces, publication, missing/intermediate
pools, nearest-pool ambiguity and shared subscription cleanup. Original finding:

**Medium priority; a bounded worker dependency.**

[Scope_Lookup::constants/types/functions](../../03_parse/scopes/lookup.php) accepts an
optional `preparation_context` and calls `context->worker->observe(...)` for each
visited scope, including empty pools. Scope membership is shared data; subscribing
a preparation consumer is analysis processing. Current placement conflates the two.

**Direction to discuss:** separate a neutral lexical lookup/traversal from an
analysis-owned dependency-aware query, or supply a neutral observation interface.
Preserve nearest-pool selection and observation of missing/ambiguous/intermediate
pools; returning just the selected declaration is not enough for invalidation.

## SB-04 — C++ consumes and clears the shared semantic change handoff

**Resolved by E, 2026-10-05:** `Compiler::cpp()` acknowledges its source after
publishing successful output. Standalone generation leaves shared changes intact.
`Preparation_Status` owns read-only work guards and `Type_Queries` owns established
canonical/source-association reads; C++ no longer calls preparation workers.
Rendering and late assembly failure/retry are covered. Original finding:

**Medium priority; cross-owner mutation and worker API coupling.**

[CPP_Generator::generate](../../05_backend/cpp/generate.php) reads preparation work
state/versions and clears `collected_file::preparation_changes` after successful
assembly. It also calls `Preparation_Worker::require_active`. The latter is a
read-only guard, not a hidden preparation run. Likewise
[CPP_Types](../../05_backend/cpp/types.php) and declaration/fragment generation use
`Type_Preparation::canonical/source_record` as read-only queries over settled types.
Those calls are backward dependencies, not violations of the forward-stage rule,
but they tie the backend to worker classes rather than a clear result/query API.

Clearing a producer's change set makes acknowledgment implicitly single-consumer.
This is documented existing behavior, not a demonstrated stale-output bug today;
another independently consuming backend would need a defined acknowledgment policy.

**Direction to discuss:** keep versions/identities shared, expose read-only result
queries/guards, and give each backend its own consumed version or let the coordinator
acknowledge shared publication. Preserve deleted-fragment removal, failed-emission
retry, no-op output reuse and header retirement. Do not rerun semantic preparation
from emission or duplicate canonical type facts to avoid the calls.

## SB-05 — semantic retirement also owns collected-index/AST deletion

**Resolved by D2, 2026-10-05:** the lifecycle coordinator validates the whole parse
join, runs semantic notification/unlinking, then frontend `Collection_Retirement`
disposes membership, source-type associations and deleted syntax. Both compiler
and standalone preparation use that ordering. Original finding:

**Medium priority; deliberate ordering, overbroad mutation owner.**

[Preparation_Worker::remove_deleted_sources/remove_deleted](../../04_analyze/prepare/worker.php)
notifies semantic dependents, detaches work, unregisters lexical/global symbols,
retires source-type associations, removes collected entries/indexes and clears deleted
file syntax. [Compiler::sync](../../compiler/compile.php) invokes this after the parse
join even on the parked LLVM route. Analysis therefore also serves as common frontend
retirement machinery, despite LLVM not consuming its normal preparation results.

**Direction to discuss:** separate semantic dependency retirement from frontend
membership disposal, with coordinator-owned ordering. Dependents must be notified
before identities are removed. Moving files alone cannot fix that lifecycle coupling;
tests must cover deletion, duplicate/missing names, type retirement and failed joins.

## SB-06 — LLVM remains an independent semantic pipeline

**Known parked exception; not repaired or expanded.**

[LLVM preparation](../../05_backend/llvm/prepare.php), `legacy_names.php` and
`legacy_templates.php` resolve/check their own input rather than consume shared
prepared facts. `Compiler::llvm()` explicitly selects that route; C++ does not call it.
This is semantic duplication inside a backend, not an early-stage forward invocation.
Its documented integer policy also differs from the language's default integer model.

Before resuming LLVM, converge semantic preparation as already required by its
[README](../../05_backend/llvm/README.md). A source-file move does not establish parity.
The host-only `06_native` path builds emitted LLVM; C++ fixture/native-compiler build
tools are separate host adapters. Their different execution surfaces are not evidence
that the numbered code-generation stages invoke native compilation.

## SB-07 — smaller placement and target-policy concerns

- [Module_Loader::discover](../../01_prepare_inputs/module.php) requests revisions
  from `Compiler_Lifecycle`; it also uses `Source_Registry`. This is dependency on a
  shared session service, not calling a later stage, but passing the revision or
  extracting a neutral revision service would reduce coordinator coupling.
- [Type_Preparation::entry_return_type](../../04_analyze/prepare/semantics/types.php)
  admits numeric/bool families specifically for native `main` exit adaptation.
  Validation still belongs before emission; consider an explicit entrypoint/target
  contract if more output targets are enabled. Do not move this check to Clang or
  classify every bounded `S2S` diagnostic as a backend invocation.
- Canonical semantic type models/registration live under `03_parse/types/`, while
  `compiler/types/structures.php` is a compatibility include location. This makes
  physical folders an imperfect layer map. Type identity, registry queries and
  injected `type_capability_query` are shared concepts, not parser execution.
  Folder cleanup is optional and distinct from fixing processing ownership.
- `boot.php`, `Compiler`, `Compiler_Lifecycle`, source publication and host reporting
  intentionally coordinate or inspect multiple stages. Retained `Model` roots are
  data ownership, not a pipeline runner. These are not findings to “fix” by hiding
  calls behind generic interfaces or duplicating state.

## Discussion grouping and recommended order

1. **Decide the shared-model hook boundary (SB-02).** Keep fields/facts/neutral
   traversal; explicitly choose which visitor/completion hooks remain exceptions.
   This settles what “structures may mix” permits before moving more code.
2. **Frontend-to-analysis ownership (SB-01 + SB-03 + SB-05).** Agree one invalidation,
   observed-lookup and retirement protocol, then implement it in bounded slices.
3. **Backend consumption (SB-04).** Separate read-only facts/query access and output
   acknowledgment; avoid premature multi-backend infrastructure.
4. **Leave SB-06 parked; revisit SB-07 when its owning area changes.**

No new retained event model, visitor redesign, ownership transfer or additional file
move is authorized by this audit. Follow-up changes need discussion and proportional
incremental/native validation; PHP behavior alone does not prove native portability.

## Validation and provenance

Focused callable/collection checks: `/tmp/my-try-stage-boundaries-20261005-2`.
Function regression/export/generated-program checks:
`/tmp/my-try-stage-boundaries-functions-20261005-1` (PCH enabled).
All 19 distinct PHP suites passed (12 function-driver suites plus eight supplementary
suites, with token generations in both), as did all 77 generated C++ function programs.
The first supplementary invocation named a nonexistent `scopes.php`; that was a
runner selection error, not a compiler failure. The corrected selection uses the
existing `publication.php` and also checks incremental preparation.

No native compiler rebuild, conversion, style/lint sweep, broad regression or LLVM
implementation work was performed. Historical conversion evidence retains original
file paths/hashes; it must not be rewritten to imply a native proof of the moved code.
Native source-copy tooling already discovers both numbered directories recursively.
