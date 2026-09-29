# Incremental compiler strategy
Doc Status: planning

Discussion started: 2026-09-28. Modules, file scanning, token generations and the
standalone parsing/collection slice are implemented. Resolution is the next slice;
the combined sync pipeline now uses the same incremental phases. The agreed current
parsing boundary below supersedes earlier exploration in this document.

This is the shared planning document for incremental compilation in `my-try`.
[Current file synchronization](../lifecycle/incremental.md) describes implemented
behavior; [the retained model](../architecture/MODEL.md) describes current ownership.
This proposal does not redefine language semantics or resume parked LLVM work.

## Objective and boundaries

Retain useful compiler data between updates. Rebuild affected declarations and
whole executable bodies, including the file's top-level executable body. Preserve
source order and reliable declaration relationships without introducing incremental
tracking for every executable statement or expression.

For S2S, generating all outputs and avoiding writes when bytes match can reduce
native recompilation. It does not remove repeated frontend/semantic work, so it is
not a substitute for this strategy on larger projects. Output comparison remains
an independent useful step; output partitioning is a separate decision.

A **session** owns retained compiler data. A **run** is one initial build or update
within that session. `Compiler::init()` now reconciles module configuration and resets compilation data
only on configuration changes. `Compiler_Lifecycle::reset()` explicitly clears the
session, including retained module identities.

In this document, **body rebuild** means replacing a body's derived semantic facts,
resolutions and backend work together. It does not necessarily mean reparsing:
an unchanged source file whose dependencies changed can reuse its syntax.

## Direction supplied by the user

The following records the requested direction. Exact representation, matching,
publication and dependency mechanics remain discussion items below.

1. Start a new run while retaining the preceding run's useful data.
2. Reconcile modules and files as added, changed, removed or unchanged.
3. Tokenize and parse added or changed files. Avoid that work for unchanged files.
4. Replace the active token sequence with the newly tokenized sequence; no
   token-by-token incremental merge is requested.
5. Reconcile declarations across stages. Rebuild executable statements as their
   containing body, instead of matching individual acting statements across runs.
6. Match each new declaration to its predecessor. Construct the current AST in
   current source order, pulling matched declarations from the old run into it.
7. Update the retained global scope in the opposite direction: push current
   declaration information into its existing entries rather than reconstructing
   the scope as if every declaration were new.
8. Retain deletion information for declarations missing from the new run, in
   both AST/declaration tracking and scopes. Interpreting the original wording:
   old but absent from new means deleted; new but absent from old means added.
9. Resolutions refer back to the declarations/types they use and to their owning
   declaration or body. Discuss suitable storage and weak-reference relationships.
10. Global symbol changes update affected resolutions. The semantic rebuild unit
    is a whole function body, including the file's top-level body.
11. Incremental work operates at declaration/body granularity.
12. Physical removal of deleted data is deferred cleanup debt. Decide when
    added/changed flags return to unchanged, or how retained flags are qualified.

Functions, structs and their current frontend forms are the immediate examples.
Classes, constants and other declarations must fit the approach when implemented;
their mention here does not authorize adding their syntax now. Templates remain
outside the current implementation slice.

## Original baseline and gaps (historical discussion)

| Concern | Implemented today | Proposed direction / gap |
| --- | --- | --- |
| File identity | Module-owned `source_record` survives updates and deletion. | Keep that identity within unchanged module configuration; module reconciliation is implemented. |
| Discovery | Module changes trigger full discovery/reset; every sync scans module-local file indexes and accepts forced notifications. | Reconcile the known source set each run; discovery mechanism remains open. |
| Previous/current results | `source_work.previous` holds the published parse; `source_work.result` holds a private candidate. | Retain a clear candidate/publication boundary when reconciling declarations. |
| Changed input | Module scans compare mtime/size; only changed/pending files or explicit notifications enter the frontend. | Establish no-change before unnecessary frontend work where possible. |
| Tokens and syntax | Successful synchronization replaces a file's tokens, AST and scopes together. | Replace active tokens while retaining whatever old data comparison/reuse needs. |
| Declaration matching | Kind/name/enclosing context groups; exact matches first; ambiguous leftovers become additions/deletions. | Decide matching sufficient for stable declaration identity. |
| Declaration identity | Matched occurrences and source `type_definition` records are newly constructed. | Identify the stable object that resolutions and scopes retain. |
| AST reuse | A fresh tree is published; nodes have one-parent links, linked once. | Actual node transfer needs an explicit ownership/relinking contract. |
| Scope update | Old live collection references are removed; new ones are exported. | Update matched retained entries while preserving duplicate candidates. |
| Preparation | All attached preparation facts and generated output are reset before synchronization. | Preserve unaffected facts; retire/rebuild selected declaration/body facts. |
| Dependencies | Some facts point to declarations; no shared reverse dependency index. | Add a bounded way to find affected owners and revisit lookups. |
| Failure | A failed file keeps its previous parse; earlier successful files may already have published. | Decide failure/publication policy; do not imply whole-run rollback already exists. |

`Parser`/`Parser_Run` currently isolate parser invocation state; neither owns a
previous/current result pair. That pair lives in `source_work` and `source_record`.
Keep the class decision open until this lifecycle is settled.

Implementation anchors: `compiler/sync/sources.php`, `compiler/work_queue.php`,
`compiler/frontend.php`, `compiler/publication.php`, `compiler/sync/declarations.php`,
`compiler/scope_publication.php`, `compiler/lifecycle.php` and `03_parse/structures/structures.php`
(paths relative to `compiler/my-try/`).

## Original proposed run sequence — superseded for parsing

1. Establish the run's source changes, including additions and removals. Keep
   unchanged file data. Retain enough previous state to compare changed candidates.
2. Tokenize and parse changed/added files privately. As an initial simplification,
   parse each changed file completely, then reconcile declarations. Skipping the
   parsing of individual unchanged bodies inside a changed file is not required.
3. Walk each candidate's declarations in new source order. Match against the
   previous inventory, retain matched identity according to the agreed ownership
   rule, classify header/body/member changes, and record unmatched old declarations
   as deleted. Keep executable statements in their enclosing body's new order.
4. Publish reconciled syntax and push declaration changes into retained scope/type
   entries. Update old and new lookup buckets for removals, additions or renames.
5. After the relevant declaration changes are visible, schedule affected declaration
   and body owners. Include bodies changed in source and owners affected by lookup
   or declaration changes, even when their source files were untouched.
6. Rebuild those owners' preparation and resolutions. Replace their dependency
   memberships as a unit. Propagate changes to dependent declarations until no
   additional owner requires work. Process cycles as a bounded group/worklist;
   exact scheduling and comparison rules remain open.
7. Generate from current prepared data. Publish valid output and avoid rewriting
   identical generated files. Failed/dirty owners must not supply apparently current
   semantic facts or successful output.
8. Complete change consumption, then advance/reset transient state. Physical
   reclamation is deferred, but removing stale dependency memberships is necessary
   for correct selective rebuilding from its first implementation.

These are logical dependencies, not a requirement to serialize all file work.
Concurrent parsing can continue; semantic consumers must see a coherent scope view.
A declaration-publication barrier before semantic rebuilding is the simplest
proposal. Whole-run transactional publication is not assumed.

## Agreed first slice: module synchronization

Any module configuration change triggers a full rebuild, including a change only
in module order. File scanning and AST reconciliation optimizations are later slices.
This slice should be efficient and straightforward; it is not an optimization pass.

Each module stores its name, declared path and resolved path, even when the two
paths are identical. An explicit name/tag is the key; otherwise the exact declared
path is the key, preserving relative spelling. Match old/new modules by that key.
Reject duplicate incoming keys and overlapping resolved module roots.

Keep one retained Keyed_Storage indexed by name. Incoming entries need not arrive
indexed. Initialization directly performs reconciliation; no module-specific collection
wrapper or synchronization class is required:

1. Walk incoming entries in order. Find the old entry by key, compare its configuration
   and position, mark it present, and update or insert it in the retained collection.
2. Walk retained entries and mark those not present in this run deleted.

Keep run presence separate from change flags. A run marker avoids a preliminary
pass clearing presence. Detect order changes by comparing positions. With an indexed
old collection, matching takes expected O(old + new) time; path resolution and
configuration validation have their own costs.

Validate the complete incoming configuration before destructive reset. When it
changes, retire the prior compilation roots and rediscover/rebuild all active modules.
Avoid traversing discarded ASTs just to clear preparation facts; partial resets that
retain syntax still need their existing cleanup. Releasing a graph does not promise
constant-time destruction. Failed discovery must not expose old output as current.
An unchanged module configuration should retain the current compilation data.

Keep full reset behind its lifecycle method. Rebuild the small module collection in
incoming order only on change, reusing matched records and appending tombstones.
Unchanged input preserves the existing collection. A shared Model revision counter
and inline record revisions provide presence tracking without helper objects.
Do not add file/AST synchronization machinery in this slice. When file scanning is
addressed, prefer updating existing records directly from scan results over creating
replacement records solely for comparison. Physical tombstone cleanup remains debt.

## Agreed next slice: module-relative file reconciliation

Status: implemented. Keep scanning and reconciliation
inside the existing module/folder traversal, without new record types or generic
synchronization helpers.

- Store each file path relative to its module, including subfolders (for example
  `lib/math.phs`). Source membership and file snapshots must not retain an absolute
  file path as a second identity. Construct filesystem paths from the module's
  resolved root and the relative path at IO boundaries.
- One module-owned Keyed_Storage of source records owns and indexes all of that
  module's files by relative path. Folders are traversal context, not retained
  owners or indexes. The same relative path in different modules is valid.
- Replace the current global absolute-path source index with module-local lookup.
  External file notifications first identify the owning module, then select its
  relative-path entry. Normalize keys consistently with scan output.
- Scan each module recursively, updating existing records directly and allocating
  records only for additions. Stamp last-seen revisions. File order requires no
  reconciliation. Preserve the current source-extension and directory-symlink rules.
- After a complete successful module scan, mark unseen files deleted. A failed scan
  must not mark unvisited files deleted. Renames initially mean deletion/addition.
- Compare modification time and size to detect changes. Explicit file notifications
  force rereading. Observed filesystem metadata must not make old tokens/AST appear
  current: failed or pending reads/parses remain eligible for retry until publication.

Accepted debt: mtime plus size can miss equal-size edits whose modification time
is unchanged (including coarse timestamp resolution or deliberately preserved
metadata). This limitation is accepted for the initial file-scan slice. Consider
content hashing or another stronger detector later; do not add it in this slice.

## Declaration identity, AST order and scope order

The intended directions are compatible:

- AST reconciliation follows **new source order**, locating matching old identities.
- Scope reconciliation follows **retained symbol identity**, applying current facts.
- Scope insertion order must not become the authority for executable source order.

The outstanding question is what is physically retained:

| Option | Benefit | Consequence to resolve |
| --- | --- | --- |
| Transfer the actual old declaration AST node into the current tree. | Closest to the proposed AST pull; retains node identity. | Its links, spans, specialization children and collected occurrence may need updating. The old tree can no longer remain an immutable intact snapshot of that same node. |
| Retain a declaration identity/entry and replace its current syntax reference. | Existing consumers can retain one target while each parse has independent syntax. | This is identity reuse, not literal reuse of the old AST object; must be explicitly agreed if selected. |

No option is selected yet. Do not add both a stable-node mechanism and a separate
identity registry without demonstrating that both are necessary. Existing scope
entries/type definitions are candidates for reuse; an extra ID table is not assumed.

The present node API rejects a child already attached to a parent. Named child
lists also retain aliases, and occurrence attachment is currently permanent.
Consequently, pulling nodes cannot be implemented merely by appending old node
handles to a new list. We must decide when transfer is safe and how all associated
links and provenance change together.

Matching proposal for discussion: group by declaration kind, owner and name;
match equal candidates before changed candidates; do not arbitrarily pick among
ambiguous duplicates. A signature change should not automatically destroy identity
if there is one clear predecessor. Renames, moves between owners/files and ambiguous
matches can initially be delete/add, unless a stronger rule is agreed. Token indexes
and source offsets are not stable identities.

Local variable declarations/initializers inside an executable body need a boundary
rule too. Proposed: rebuild them with that body, retaining cross-run identity for
externally addressable declarations. A typed local initializer both declares and
executes; treating every declaration-shaped statement as independently reusable
would defeat the intended body granularity.

## Token replacement and deleted data

Replacing the active token store is compatible with retaining the previous snapshot
until comparison and publication finish. It must not mean overwriting token memory
while previous AST spans/occurrences still refer to it.

For a reused node after an insertion or move, choose one explicit policy: update all
spans/provenance to the new snapshot, or retain the exact old snapshot its references
require. This applies to specialized token indexes as well as the common node span.
Location-only changes may require updated diagnostics even when semantic facts remain
valid. Physical token sharing/compaction is not needed for the first design.

Deleted declarations must remain observable to invalidation and inspection while
being excluded from live lookup and code generation. Decide whether AST deletion
records live in a separate retained inventory or in a traversal that explicitly
filters tombstones. They must not appear as executable nodes in current source order.
Deleting a file/module must retire its declarations, bodies and dependency memberships.

## Resolutions and dependency storage — options, not final layout

Recommended ownership direction for discussion:

- A declaration or body owns its current resolution/dependency records.
- A resolution has a non-owning reference to its selected declaration/type.
- A reverse index lets a changed declaration or lookup bucket find affected owners.
- Rebuilding an owner replaces its old dependency memberships; duplicate uses may
  be retained locally, but the owner should not be rebuilt once per use.

Whole-body rebuilding permits a compact reverse index of dependent owners rather
than a reverse pointer to every expression. Exact resolution records can remain
attached to the specialized syntax that already owns them. Avoid introducing a
second copy of all prepared facts just to drive invalidation.

A weak reference avoids retaining an obsolete target. It does **not** establish
semantic freshness: a still-live declaration can change its signature or fields,
or remain alive as a deleted tombstone. Change tracking must determine whether a
resolution is valid independently of whether its weak reference can be acquired.
Whether reverse memberships store weak owner handles or stable owned records remains
open; lifetimes and removal responsibility must be explicit before choosing storage.

Selected-target dependencies alone are insufficient:

- An unresolved name must be revisited when a declaration is added.
- A previously unique match can become ambiguous when a second declaration appears.
- A nearer declaration can shadow the previously selected outer declaration.
- Removing one ambiguous candidate can make lookup succeed.

Therefore also retain the relevant lookup dependency (scope/name/category, including
searched scopes where necessary), or agree a conservative owner invalidation rule
when those buckets change. The precise index representation is an open decision;
`Key_Storage_List` is available but is not chosen merely because it supports duplicates.

## Rebuild granularity and propagation examples

| Change | Expected minimum semantic work, subject to dependency policy |
| --- | --- |
| Ordinary function body only | Rebuild that body; callers need no semantic rebuild if they consume only its unchanged explicit signature. Backend/link work is separate. |
| Function signature | Rebuild the declaration, its body and affected call-site owners. |
| Struct field/type/order | Rebuild the declaration and affected declaration/body owners consuming its members or value layout; propagate through containing value types. |
| File-level executable statement | Rebuild the file's executable body, preserving statement order. |
| New/removed/renamed global name | Revisit affected lookup owners, including unresolved and ambiguous lookups. |
| Declaration move or location-only edit | Preserve current AST order and provenance; reuse semantic facts only when their dependencies and context remain valid. |
| Unchanged file, changed dependency | Reuse tokens/AST; rebuild affected declarations/bodies semantically. |

Do not confuse "body changed" with "only the body can affect dependents." A future
compile-time consumer of a body, or inferred exported facts, would require that
specific dependency. Templates/metaprogramming remain deferred; the current explicit
function-signature case should not acquire speculative machinery for them.

## Change flags and run completion

Current flags distinguish added, declaration-changed, body-changed and deleted.
The new design needs an equally clear distinction between current-run events,
persistent deletion state and work still awaiting rebuild. Their exact fields or
enums are not decided here.

Options: clear consumed added/changed flags at an agreed boundary, or associate them
with a run/revision so they cannot be mistaken for a new change later. Leaving bare
added/changed flags indefinitely is unsafe if the next run treats them as fresh work.
Failed or unprocessed work must not disappear merely because transient flags reset.
Deleted state remains until cleanup or an explicitly matched reappearance policy.

Deferred debt: physically remove tombstones, release obsolete source/token/AST
snapshots and compact stores after dependents can no longer access them. Tombstones
are deletion evidence, not an unlimited history service.

## Discussion order and acceptance examples

Resolve these decisions before selecting classes or implementing:

1. What identity is pulled forward: the actual declaration node or a stable entry
   referring to current syntax? Must previous syntax remain inspectable after publication?
2. Where do declaration identity and body ownership start/end, especially for locals
   and file-level executable code?
3. What matching ambiguity and rename/move policies are acceptable initially?
4. When do token/AST/scope changes become visible, and what remains usable after a
   failed parse or semantic rebuild? Are partial per-file publications acceptable?
5. How are selected-target and lookup dependencies stored and removed?
6. How are dirty owners scheduled, cycles bounded, and change flags consumed?
7. Module configuration changes, including order, now require a full rebuild; see
   the agreed first slice above. File and AST reconciliation remain separate work.

Future proofs should compare incremental results with a fresh compilation of the
same final inputs, while also asserting the intended retained identities and skipped
work. Cover unchanged notifications; insertion/reordering before a retained declaration;
body-only and signature edits; struct layout changes; additions/removals of lookup
candidates; file/module deletion; duplicate ambiguity; parse failure and retry;
semantic failure and retry; and multiple successive edits before cleanup.

Performance evidence should count rescanned/reparsed files, rebuilt declarations and
bodies, revisited dependencies, regenerated outputs and rewritten files. Matching
output bytes alone does not prove that incremental work was avoided.

## Decision log

- 2026-09-28: Recorded the user's declaration/body strategy and current implementation
  gaps. No representation, parser-class consolidation or implementation change approved.
- Physical removal of deleted data remains deferred debt. Storage choices and flag
  completion policy remain open. Continue discussion in this document.

- 2026-09-28: Approved module-first implementation: explicit name/tag or declared-path
  key, both path forms retained, indexed two-pass reconciliation, and full rebuild
  on any module configuration/order change. Keep this efficient without an optimization
  pass; revisit more detailed optimization with file scanning and AST nodes.

- 2026-09-28: Implemented the module slice, including stored ordinal position,
  named/path keys, separate declared/resolved paths, presence tracking, full-root
  retirement, and pending full frontend synchronization. File/AST reuse optimizations
  remain deferred. Current behavior and proof are in the lifecycle document.

- 2026-09-28: Simplified module reconciliation to direct initialization loops and one
  ordered Keyed_Storage. Removed module_collection, Key_Synchronization and
  Module_Synchronization. Ordering uses replacement membership only on change;
  matched records keep identity, tombstones follow live input order. The shared
  change_state enum and inline uint32 revisions replace integer module flags and
  per-module presence objects. Files, tokens, AST and symbols remain separate slices.

- 2026-09-28: Implemented module-relative file indexes and direct recursive scans.
  Metadata differences and pending state select frontend work; explicit notifications
  force reads. Failed scans do not delete unvisited entries, and failed frontend work
  stays pending. Absolute paths are computed at IO boundaries. The mtime/size detection
  limitation remains accepted debt; no new synchronization structures were introduced.

## Appended token storage and deferred cleanup — implemented

Changed files tokenize privately. Only successful tokenization appends records to
retained token storage and source bytes to retained text. Each new token's byte
offset receives the fixed text-prefix offset. `first_token` / exclusive `end_token`
select the new parse interval; `content_offset` identifies its source-text start.
Unchanged/deleted files are skipped. Lexical/read failure does not publish a partial
append. The separate `previous_tokens` link and parser's old-token selector are gone.

Unchanged bodies/statements keep their original nodes, facts and token indexes.
The parser records one old/current interval correspondence per reused region.
The existing collection pass uses these intervals to retain old occurrences and
retire temporary replacements, without walking those ASTs during compilation.
New declarations/signatures still use their newly parsed token positions. Saved
names remain independent of all token positions. Parser byte diagnostics subtract
the appended source offset; raw retained AST spans remain storage indexes.

`Compiler::cleanup_tokens()` is an explicit host operation after output delivery.
It remaps live syntax and collected indexes to the latest input, then replaces
storage/text with that input alone. It preserves prepared facts and cached C++ text.
`Syntax_Relocation` is removed; `Token_Cleanup` owns deferred normalization.
Each specialized node owns its maintenance traversal. Cleanup workers supply
operations and choose recursion through the three-method maintenance interface;
the abstract `Syntax_Maintenance` visitor has been removed. Inspection-parent links
and their attachment worker are deferred until an actual consumer needs them.

No background scheduler is introduced. If the host does not request idle cleanup,
the next tokenization performs it synchronously before scanning. It may block:
optimizing cleanup latency is not a requirement for this slice. Incomplete parses,
unparsed published input and outstanding deleted occurrences retain their buffers
until recovery/deletion makes compaction safe. Repeated failures may therefore
retain several appended inputs; full reset releases them. Unexpected cleanup
failures set the existing full-rebuild flag. External debug cursors/references must
not be traversed concurrently with cleanup.

The one-shot CLI may simply exit after output; a retained host uses the explicit
cleanup operation. Focused evidence: `tests/token_cleanup.php` and
`tests/token_generations.php`, plus retained parse/preparation/generation regressions.
No native compiler validation or rigorous incremental proof is claimed.

## Agreed parsing/collection slice — implemented, resolution deferred

- `Compiler::tokenize()` hands current and previous token generations to
  `Compiler::parse()`. Only added/changed sources enter parsing; unchanged files
  retain their graph. Successful parsing consumes the pending source change.
- Parser calls `Symbol_Collector` immediately when a declaration is recognized.
  Collector has no separate pass or worker. It registers/reuses a symbol within
  the supplied owning scope; it does not resolve references.
- Existing function/struct/field/parameter nodes, specializations and collected
  declaration identities are updated directly. Their scope indexes remain intact.
  Matching is by kind and name within the owning file/member/signature scope;
  duplicate spellings consume old entries in encounter order. Renames are
  additions/deletions, not inferred moves. No overload resolution is attempted.
- Existing `collected_name` owns enum `change_status` and uint32 `revision`.
  No state is added to every AST node. Function specializations own a separate
  `body_changed` flag; parsed_file owns the file executable-body flag. A trait
  is unnecessary because symbol revision/status has one existing owner.
- Each file collection advances its own revision in its worker, clearing retained
  symbol markers on uint32 rollover. Local missing-symbol checks run only after
  that file parses successfully. Global checks run coordinator-side after joining,
  using each completed file's revision. Failed files are excluded from sweeping.
- New exported functions/types register immediately under `task_synchronize`,
  using the unordered batch's publication mutex. No resolver reads these partially
  filled declarations during parsing. Existing global index membership is reused.
- Changed body syntax is replaced as a unit, never treated as a persistent symbol.
  Unchanged bodies and their collected occurrences are retained together, with
  token positions rebased to the new generation.
  Token cursors compare spelling/order without host numeric conversion or saved
  order fields. File executable comparison skips declarations. Signature and body
  changes are separate. Syntax spans and child/sibling links use current tokens.
- A file has a private executable scope. Its variables are not exported globally.
  Function signatures have their own scope, with retained unchanged or replacement changed body-local scopes.
  Functions cannot implicitly obtain file-body variables through the scope chain.
  `global` syntax and captures for named functions/methods are not implemented.
- `collected_file.entries` retains symbol indexes. Obsolete body/reference rows
  are removed without index reuse; current unresolved work lists are rebuilt.
  References retain their node, token and lexical scope for the next resolution
  implementation. Type definitions retain identity alongside their declaration.
- Parse errors stop only that file. The incomplete mutable file and newly
  registered symbols remain for retry; successful other files retain their work.
  Errors are reported after joining. No deletion sweep runs for the failed file,
  and no resolution/preparation/backend work is started by this phase.
- `parsed_file.complete` is false during parsing and after failure. Previous
  handles are mutable identities, not historical snapshots. The old token snapshot
  alone supplies comparisons. Retrying a failed file conservatively marks matched
  signatures/bodies changed because its partial spans are not an old snapshot.

Focused proof: `tests/parse_collection.php`; token handoff:
`tests/token_generations.php`. This is PHP evidence, not whole-compiler native proof.
The runtime callback has separate PHP and native lock/context tests in
`tests/portability/task_synchronize*` at the repository root.

### Incremental shared preparation — implemented

After the successful parsing/collection join, one preparation worker handles initial
and update runs. Separate identity sets select declarations, function bodies and file
executable bodies. Initial owners are pending; updates seed only affected owners.
Declaration work settles first, including newly notified declarations; each selected
body runs once afterward. Resolution remains within these preparation algorithms,
against the complete declaration inventory; there is no duplicate resolution pass.

`collected_name` owns optional declaration preparation state only where needed.
`function_structure` separately owns body state; `collected_file` owns file-body state.
Parameters and fields participate through their enclosing signature/record. Prepared
facts stay attached to specializations. Effective declaration facts are compared before
notifying consumers; unchanged facts retain identity. Source changes mark owners pending
without discarding the old declaration facts needed for that comparison.

Forward/reverse declaration dependencies and scope/name candidate observations use
identity-keyed maps. Missing/ambiguous lookups are observed too. Required completed
by-value record facts use pending/processing/ready cycle detection; ordinary recursive
calls only need signatures. Nested record changes propagate conservatively.

Deleted entries notify dependents, then leave collected storage, scope/type indexes and
occurrence work lists. `Key_Storage_List::remove(key, object)` removes matching identity
insertions, preserving other same-key candidates. Deletion filtering stays at this
cleanup boundary. No cleanup or preparation starts after an earlier phase error.

Parser retention of unchanged bodies was explicitly approved: preserve the body AST,
local scope and occurrences as a unit, updating token offsets. Replace changed bodies.
This avoids copying facts or references between equivalent body trees.

Focused evidence: `tests/incremental_preparation.php`, `tests/parse_collection.php`,
`tests/token_generations.php`, `tests/storage.php`, and repository
`tests/portability/object_hashes.php`. The latter proves the explicit `@object-key`
foreach annotation used for PHP/native identity-map iteration. No native compiler
validation was requested for this slice.

Combined `Compiler::sync()` is now migrated: scan and notifications select source
records, then existing tokenize/parse phases run with their join boundaries. It removes
deleted collected/index rows after the successful join, notifying pending preparation
owners without preparing LLVM-only syntax. Old candidate replacement and declaration
comparison code is removed. The parked LLVM adapter only accounts for the new scope
layout. `tests/combined_sync.php` proves selective reuse and fresh C++ equivalence.
No new language forms or independent field queues are introduced.

### Preparation recovery implemented (2026-09-28)

Preparation uses persistent change/error state on existing preparation owners, with
independent signature and body owners. Parsing does not settle collected declaration
changes; successful preparation settles the declaration and its fields/parameters.
Starting an increment or an attempt does not clear unfinished work or its error.

The existing declaration, function-body and file-body lists select added/changed work.
Expected preparation errors mark the failing owner and its transitive consumers
failed/changed. Independent work continues. Each failing unit is attempted at most once
per invocation and retried on the next invocation, including a no-edit increment.
Required declaration facts are completed before consumption; failed facts remain
unavailable. New edges also check previously ready dependency chains for cycles.
Removing an erroneous dependency from a consumer allows that consumer to recover.

Success clears failure/change state. Declaration recovery notifies consumers even if
its signature equals the last successful signature. A body error does not poison its
valid signature or callers. Completion/output is withheld while any selected work is
unfinished; partial mutable facts are not rollback snapshots.

Function-body comparison now uses raw source text bounded by the existing inclusive
`token_index` and exclusive `end_token_index`. The byte range starts at the first token
and ends after the last token. Internal whitespace changes count as changes; moving
identical body text retains its AST, occurrences and facts with rebased token positions.
Signature and file executable comparisons retain their existing token comparison.

`tests/preparation_recovery.php` proves retry without edits, independent progress,
recovery to an identical signature, new consumers of failed declarations, removed
prerequisites, initial/new declaration cycles, cycle deletion and body-text retention.

### Retained C++ generation implemented

Preparation accumulates successful declaration/body changes and deletions in a
per-file identity handoff. C++ generation retains separate declaration and body
fragments with completion versions, dirty state, rendered text, include requirements
and record-order dependencies. Only selected fragments are rerendered; unchanged
bodies are not traversed. Successful assembly consumes the pending handoff. Rendering
failure withholds output and retains dirty work for retry; unexpected exceptions
request the existing full-rebuild fallback.

The output remains one `main.cpp`, assembled in the existing record/prototype/function/
entry-body order. Source identifiers with role prefixes replace token-index names in
current supported scopes; temporary numbering is independent per body. No output
partitioning, disk writer, Ninja integration or token-ownership change is introduced.
Future namespaces/overloads/richer local scopes must revisit name qualification.

Focused evidence: `tests/incremental_cpp.php` covers cached identity, body/signature
independence, moved declarations, fresh equivalence, failed generation retry, deletion,
signature-dependent consumers and removal of unused includes. Rigorous/native testing
remains deferred as agreed.

### Preparation debts

- Unexpected exceptions escaping preparation now set `Model::$rebuild_required`.
  The next attempt resets all compilation roots and rebuilds from retained inputs,
  including in-memory source bytes. It does not traverse potentially corrupt facts.
  Expected `RuntimeException` diagnostics retain incremental retry. Remaining debt:
  distinguish internal bugs reported as `RuntimeException` from source diagnostics;
  no transactional rollback is promised.
- Dependency/lookup cleanup uses explicit unlinking with existing strong identity
  storage. Deletion marks the whole batch, notifies transitive consumers while links
  remain intact, then detaches both directions and removes indexes. Full compilation
  and syntax resets sever these registrations before dropping roots; body preparation
  replaces outgoing registrations. Weak references are deferred unless a concrete
  need appears. Broader AST/scope ownership cycles remain a separate lifetime review.
  Focused evidence: `tests/dependency_cleanup.php`; rigorous validation remains deferred.
- Compact sparse occurrence and duplicate-key storage when useful. Removal preserves
  stable positions; native duplicate-key storage retains empty slots until release.
- Review more precise record invalidation after the current conservative path is proven.

### Agreed debts

- C++ output partitioning: retain the current single `main.cpp` layout for now.
  Later, group retained generation records into `.hpp`/`.cpp` units to reduce
  native compilation cost without coupling those groups to source-file boundaries.
- Token storage: the appended-buffer ownership policy is implemented above. Future
  cleanup scheduling/performance and memory limits under repeated failed increments
  remain review items; generated names stay independent of token positions.
- Rigorous validation of the current incremental implementation is deferred to a
  later testing pass. Existing focused tests provide limited evidence, not exhaustive
  coverage. Include preparation recovery, repeated increments and native behavior
  in that pass; this note does not require additional testing during each change.
- Add `use` to normal functions and methods, following lambda capture semantics
  (value/reference intent). Tracked in the [v0.2 function catalog](../catalog/04_functions.md#deferred-v02-planning-explicit-captures-for-functions-and-methods).
  Implementation is for later; capture syntax and lifetime rules need their own slice.
- Review reconciliation of nested sub-structures and declaration relationships
  such as extends/implements as their frontend syntax is introduced. This slice
  adds no classes, methods, constants or inheritance syntax that the frontend
  does not already support.
- Evaluate error boundaries across files and subsequent processes: continue as
  much independent work as is safe, without rollback machinery. The current
  parser stops a failing file, preserves partial identities and blocks progression
  by reporting failure after the join. Lexical/read error continuation and broader
  failure classification remain separate decisions.
- Release obsolete generations/tombstones and compact sparse occurrence storage
  when its observers permit it; positions currently grow without reuse.

### Combined migration validation (2026-09-28)

`python3 compiler/my-try/tests/run.py --php-only --results <fresh-directory>` runs
all 24 current PHP test files, including incremental smoke with restoration and fresh
output comparison. All passed after the combined migration. The affected repository
portability tests `object_hashes.php` and `task_synchronize.php` also passed. PHP tests
that exercise Native_Runner still compile generated sample programs; the compiler
itself was not compiled natively. Preparation recovery was implemented afterward, as
described above; that later slice has focused PHP coverage only.
