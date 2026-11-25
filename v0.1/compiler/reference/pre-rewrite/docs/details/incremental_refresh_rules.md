# Initial incremental refresh rules
Doc Status: supporting

Status: design rules for the resident [pipeline](../compiler_pipeline.md).
Manifest comparison, metadata-based source discovery and function-body updates
under unchanged contracts are implemented; broader incremental categories remain
future work. This document owns incremental eligibility and
replacement details. Keep file parsing coarse; introduce semantic change
rules one verified category at a time. No token splicing or incremental parser
is needed for this approach.

## Inputs and comparison baseline

Compare current inputs with `Compiler_Session::published` when available;
before the first publication, use its latest successful `observed` snapshot.
Both are complete [retained generations](core_data_structures.md#retained-generations).
Current discovery scans every configured root recursively
and compares each `.phs` file's whole-second `fs_mtime` and `fs_size` with the
previous source dataset. Either difference marks the file changed, even if a
timestamp-only edit left the content identical. No content hash or watcher is
needed at this stage. Retain unfinished stage work even when metadata is unchanged.

Current timing limitation: leave at least one second between the last edit and
compilation, and one second between the scan and the next edit. Same-size edits
with unchanged mtime can go undetected. The CLI prints this limitation in `--help`;
no automatic sleep or stronger change-detection guarantee is implemented.

Canonical paths identify live files; matching paths retain IDs across scans and
root reordering. Removed rows retain their ID/path as tombstones outside the live
path and folder indexes. Their pending removal IDs persist until downstream
retirement; recreating a removed path gets a new ID. Discovery builds a separate
candidate and leaves the previous source dataset unchanged, including on failure.

The initial update and any manifest path/content change trigger a full project
rebuild, even if the content change only affects formatting. Validate the new
manifest and rediscover the project first.
An invalid or missing manifest produces current diagnostics, not success from
the old configuration. Other configuration, runtime metadata, or external
dependency changes also require full rebuild until an explicit rule covers them.

For added/changed source files, read the full current content, tokenize it in
full, and parse it in full. With `full_rebuild`, the same loop selects every
current file. Otherwise unchanged files can retain syntax, but may require
new semantic analysis. A removed file contributes no new syntax; its old
declarations, bodies, diagnostics, and output contributions must be invalidated.
Until that propagation is supported, removal selects full rebuild.

The PHP prototype currently proves reuse through source reading, tokenization
and parsing, plus unchanged declaration extraction, record reuse and
[name-binding reuse](symbol_resolution.md) and
[declared signature reuse](return_type_resolution.md). Type cache validity is
established before frontend selection; catalog changes request full work.
Signature failures preserve the entire previous observation, including types.
A changed
file invalidates its source buffer; tokens are reusable
only for the same immutable buffer, and ASTs only for the same token snapshot.
These stages do not clear pending downstream compilation.
Read/lexical/syntax/collection/resolution failures preserve the previous
successful observation. Observed
metadata changes during a source read reject the update for retry, subject to
the timing limitation above. [Logical syntax comparison](symbol_comparison.md) is implemented; the semantic
[shared compilation eligibility gate](native_executable.md#incremental-boundary-and-proof)
admits unchanged callable definitions with body edits, and selects full work for
other catalog changes. Broader categories remain deferred.

## One selection flag, one algorithm

Use the [pipeline's single selection rule](../compiler_pipeline.md#resident-execution):
`update.full_rebuild || element.needs_recompile`. The coordinator owns this
per-update decision; tasks read it without rewriting every element's dirty flag.

`needs_recompile` means this stage's result needs work, including missing or
invalid results. Completing one stage must not erase work still needed by a
later stage. The loop applies to files, symbols/bodies, and backend units as
appropriate; it is not a second full-build implementation.

The flag starts true for an empty session, or becomes true for a manifest
change, unsupported change, or the eventual change-volume policy. Once true,
it stays true for that update. A subsequent update makes its own decision.
Recomputation overrides reuse of affected derived results; it does not require
discarding all storage, assigning new identities, or marking every row new.

Tasks report unsupported effects to the coordinator. At a join, the coordinator
can enable full rebuild and revisit earlier loops that skipped work. The flag
is fixed while a phase's tasks execute. The parse/resolve gate settles the
decision before selective lowering; any later-discovered unsupported impact
uses the same fallback before publication. Never recurse into a different build
entry point or retry selective mode within that update.

Selection only iterates current elements. Exclude removed files/declarations
from candidate indexes before resolving uses and from downstream candidate
results before consuming them. Publication replaces the old state and removes
obsolete diagnostics/backend contributions consistently. The flag alone cannot
do this. Last-successful outputs may be retained only as explicitly old results.

## Parse and resolution gate

### Two ownership levels

The frontend has two main stages in the mental model:

1. **File syntax:** parse a selected file into a complete, separate AST snapshot
   with one file root covering its definitions and implicit entry body. Nodes
   belong to that file snapshot. The parser does not maintain symbol-change
   statuses or patch the previous AST.
2. **Project semantics:** collect declarations and resolve references into a
   separate project-owned group of structures: symbols, indexes, and resolution
   results. Semantic identities and added/changed/removed classifications belong
   here, with definitions/contracts and executable bodies compared separately.
   Source-file and exact AST origins remain attached for replacement and
   diagnostics; project ownership does not erase those origins.

Project symbols reference their declaration and body/list roots in the owning
file AST; they do not copy syntax into project storage. Each reference must
identify the exact AST snapshot as well as its local node ID. Top-level
declarations also contribute named symbols: for example, `const Aaaa = 10`
contributes `Aaaa` to the project index while its declaration and initializer
remain in the file AST. Ordinary executable statements belong to the implicit
entry body without each becoming a named symbol. If runtime initialization is
required, the entry/init action references the existing initializer syntax;
constant evaluation and runtime initialization keep their distinct rules.

The prototype parser creates one explicit `file_root` node, identified by
`Syntax_Tree.root_node_id`. Its children are the implicit entry block followed
by top-level definitions in source order. `File_Frontend` retains definition
and entry-body IDs as indexes into that same tree. The entry block contains
statements only; the root's child order is structural, not an execution order.

The second stage first reconciles all candidate declaration contributions into
the project index, then resolves selected uses against that complete index.
Logical AST comparison supplies evidence for project-owned change summaries;
it does not require a second copied AST or retained per-node edit lists.
New definitions/removals and other unsupported effects select full rebuild;
only explicitly supported categories permit selective downstream reuse.

Resolution workers read the fixed candidate declarations and file
snapshots, produce separate results, and join them into project state. Global
ownership does not mean workers mutate a shared index concurrently. Keep the
previous accepted state intact while preparing the candidate update. Replacing
a file AST also invalidates bindings into that snapshot, even where comparison
finds an unchanged body; refresh those references before reusing results.

### Catalog changes separately from reactions

Design aim: track and catalog what changed before deciding how to react.
Tracking a category does not claim incremental rebuilding support for it.
The prototype collects additions/removals and initially marks matched
recollected definitions as `uncompared`. [Logical comparison](symbol_comparison.md)
then classifies their own definition and executable child content, omitting
wholly unchanged pairs. Class/member syntax and downstream reactions remain
unimplemented. See [collection](symbol_collection.md).

`Symbol_Refresh.changes` holds flat `symbol_change` records with previous/current
symbol references, `own_status`, and `children_changed`. A function/method body
is one of its children; it does not need a dedicated change property.
Not every semantic element has children, and tracking must not invent child
records or allocate child lists for elements without them.
The child summary is nullable: null is not established/not applicable, false is
proved unchanged, true is a known change. `symbol_record.owner_symbol_id`
describes semantic containment; zero denotes project scope. Each symbol retains
its exact `File_Frontend`, and node IDs refer
to that snapshot. Removed changes retain the previous symbol with no current
symbol; wholly unchanged entries may be omitted. Resolution owns selection
against previous binding results; the catalog contains no work-selection flags. The shared incremental eligibility gate belongs
to `Input_Selection::supports_increment`, applied by the session at the
post-resolution boundary. It does not belong to the change catalog or an
individual binding result.

For semantic elements with meaningful children, distinguish:

- **Own status:** unchanged, added, changed, or removed; `uncompared` while
  matched definitions await comparison. For a matched element,
  compare its own definition separately from its children, including an executable body.
- **Children changed:** a summary that at least one tracked child was added,
  removed, or changed, including changes within that child's tracked contents.
- **Child details:** individual statuses at useful semantic boundaries, such
  as properties, constants and methods. A method can retain its definition
  while its body changes; the containing class's summary still reports a change.

For example, a class can have an unchanged own definition (name, modifiers,
base classes), an added property, a removed method, and another method with an
unchanged signature but changed body. Preserve those distinctions instead of
collapsing them into a single class-changed status. Children-changed is a summary
for inspection, not a command to rebuild the parent or all of its users.

The owner defines child matching and ordering rules. Class members match by
owner, kind and name under language rules; parameter order contributes to a
callable's contract. Reuse the comparison pattern without imposing one matching
algorithm on every tree. Initially a complete parameter-list comparison can
report signature changed, and a body comparison can stop at its first meaningful
difference. Neither requires persistent statuses on every parameter, statement,
expression or token. Add finer catalog detail only where it has a concrete use.

Keep the catalog in project semantics, with identities and references to the
previous/current AST snapshots as appropriate, including removed elements that
have no current node. Do not copy syntax into change records. Compare logical
node kinds, meaningful contents and child order, ignoring source offsets and
snapshot-local node numbering. Source-reference refresh remains necessary even
when that comparison finds no logical change.

The catalog describes one update relative to the accepted baseline; it is not
an accumulating edit history or the stage's work-selection flags. Independent
comparison tasks produce separate results for a coordinator join. Later impact
rules consume this catalog to choose work. A full rebuild selects all required
work through the same loops without relabeling unchanged elements as changed.
Until a reaction is supported, the existing full-rebuild fallback still applies.

### Refresh ordering

File-owned tokens and AST retire together with their file snapshot; no
per-token removal pass is needed. Reconcile symbol presence project-wide:
retain unchanged files' definitions, replace changed files' definitions, and
withdraw deleted files' definitions. After the join, a previously known symbol
with no current defining file is removed; multiple current definitions of the
same key are an error. Removed symbols invalidate dependent bindings/results;
unsupported impact selects the common full rebuild. Physical cleanup is deferred.

1. Build candidate syntax for selected files (all current files if the flag is
   set); use independent work units and join before declaration reconciliation.
   Execute serially initially; actual parallel execution is deferred.
2. Compare logical syntax/declarations with the previous snapshot. Match by
   symbol kind, namespace, and name under the language's name rules, not file
   order or newly allocated node IDs. Renaming a function is deletion of the
   old key plus addition of the new key. Duplicate keys are diagnostics;
   unsupported correspondence selects full rebuild.
3. Classify the change against supported incremental rules. Initially only
   function body changes under unchanged callable contracts are candidates.
   Resolve and check those bodies against the current declarations. Declaration
   additions/removals or other unsupported effects select full rebuild, which
   also resolves affected uses in unchanged files. General incremental
   dependency propagation is deferred until its own rules are introduced.
4. Before selective lowering, require supported update rules for all affected
   results, including lifetime analysis, ABI/runtime demands, objects, and link
   inputs. Unknown impact or excessive work selects full rebuild.
5. Publish consistent results and retire obsolete state only after the chosen
   update completes. Updated source positions and diagnostics must be published
   even when semantic comparisons allow executable results to be reused.

Do not wait until the gate if an earlier stage already proves a full rebuild
necessary. On fallback, set the update flag and execute the normal loops for
all required work in the same process. Source errors are diagnostics;
fresh compilation is not a promise that invalid input will compile. A retained
last-successful artifact must not be presented as output for newer invalid input.

The first incremental category is specified below; implementation and proof
are still required. Set a change-volume policy explicitly when introduced;
do not invent a threshold now.

## First category: function body changes

Keep the callable's declaration/signature and externally visible contract
unchanged: identity, parameters, return type, and ABI/passing rules. The return
expression or value may change; for example, `return 42` becoming `return 43`
inside the same integer-returning function is the first simple proof candidate.
An inferred return type must also remain unchanged after checking the new body.
The first sample uses explicit return types; inference is not required yet.

Refresh flow:

```text
changed file -> full read/tokenize/parse -> compare declarations and bodies
-> resolve/check changed body -> rebuild typed body and lifetime results
-> lower changed body -> regenerate its LLVM
-> rebuild affected object/backend unit -> relink when required -> publish
```

Replacing a file's AST invalidates its old node IDs, including use bindings in
otherwise unchanged functions in that file. The current prototype conservatively
refreshes their bindings, checked bodies, lifetimes and lowering too. Its logical
change catalog still distinguishes the actually edited body. Unchanged files can
reuse their semantic results subject to dependency checks.

Finer reuse within a reparsed file is deferred. Such a rule must prove results
valid and retain/remap their source references; equality of syntax alone is
insufficient. Resolution work and body code-generation work remain distinct
selections.

Reuse unaffected function results when the gate proves this category applies.
Replace the changed body's dependency records and diagnostics as well as its
code. LLVM/object packaging may group functions; regenerating an affected
module/object does not require redoing semantic analysis of every function in it.
Per-function object files are not a prerequisite.

A body edit qualifies only if its effects fit the implemented refresh rule.
New declarations, changed exposed contracts, unsupported runtime/link demands,
or dependent compile-time uses of the old body select full rebuild until
supported. Ordinary runtime callers can keep their code when the call contract
is unchanged. No optimization or inlining support is required for this slice.

This does not require implementing every body feature first: the changed body
must use currently supported language behavior. Invalid bodies produce current
diagnostics through the normal pipeline.

## Later incremental categories

Grow toward added code, functions, properties, and classes one supported change
category at a time, once the underlying language behavior exists. Additions
can affect lookup, type layout, initialization, ABI, or existing users; being
an addition alone does not establish that old results remain valid. Until a
category has explicit impact/replacement rules and a fresh-build equivalence
proof, use the full-rebuild fallback. No general incremental engine is required
before the first function-body update works.

### Initializer-only edits: reuse the body-refresh rule

The common rule is an unchanged definition/contract with a changed executable
body. Function bodies are its first application; runtime declaration
initializers use the same selection, checking, lowering, and backend path when
initializer support is introduced. They do not need a separate rebuild path.

If symbol identities, types, storage/ABI rules, and initialization placement
remain unchanged, an initializer-only edit can retain those definitions and
rebuild the containing entry/init body. With the current per-file model, this
means the affected file entry body, not independent compilation of one statement.
Retain unaffected bodies and refresh bindings, dependencies, diagnostics, and
affected backend units under the same rules as a function-body edit.

Eligibility must still establish supported downstream impact. Compile-time
value consumers, a change between compile-time and runtime initialization, or
unsupported initialization-order/dependency changes require broader work or
full rebuild. Admit this application with its own fresh-build equivalence and
reuse proof once runtime initializers exist; the first implemented category
remains function-body edits.

## Rename policy

Function renames and namespace changes are deletion plus addition. Do not match
functions by body similarity or preserve identity across renamed keys. Under
the first incremental rule, these declaration changes select a full rebuild.
Calls still using the old name are resolved normally and may become errors.

All file rename detection, including Git-based detection, is deferred. A file
move is observed as removal plus addition and follows those rules. No Git
integration or rename-identity preservation is needed. Project symbol identity
does not include the file path, but file moves still use the initial full-build
fallback until a supported update rule says otherwise.

## Admission proof

The prototype now proves selective [declared body checking](body_checking.md):
unchanged owners and consumed contracts share typed results, body-only edits can
reuse callers, and signature edits invalidate dependent callers even with
unchanged name bindings. [Native builds](native_executable.md) additionally prove
a body edit through emitted LLVM and a replaced executable. Unchanged function
text is shared; changed modules are rebuilt and linked as a whole. Per-function
object replacement and broader categories remain deferred.

The early milestone of one real function-body increment from source to updated
executable in the same session is now proved. The broader checks below are later admission work;
storage cleanup is not a prerequisite for that first milestone.

For each incremental rule, compare final observable results with a fresh build
of the same inputs, including diagnostics and output membership. Exercise its
affected dependents and unsupported-change fallback. Include repeated updates
in the same process to expose stale rows. For the first rule, verify a changed
function's runtime result through an unchanged caller, reuse of unaffected
bodies, and full-rebuild fallback for a changed signature or function rename.
Verify cross-file lookup and duplicate same-key declarations across files.
Verify fallback after earlier stages skipped unchanged files, and that a later
supported update is selective again. Deleted contributions must disappear;
new contributors must participate in lookup. Reuse is an implementation result,
not something to claim merely because two outputs happen to match.

## TODO: cleanup after an update

After a successful update, reclaim elements marked deleted/removed and obsolete
payloads once all readers of the old state have finished. The coordinator's
[cleanup boundary](../../reference/original-phpp/src/compile/update.phs) will delegate reclamation to
each store owner, which maintains its indexes and references if storage moves.
This is deferred work; the skeleton does not call or implement cleanup yet.

Until then, removed records may remain allocated, but must be excluded from
current lookup, work selection, and output contributions. Retain removal
bookkeeping until those contributions have been retired. Cleanup uses the same
end-of-update boundary for selective and full rebuilds. First establish one
working increment; repeated-update storage reclamation comes later.
