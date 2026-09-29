# Shared semantic preparation
Doc Status: supporting

- `worker.php`: `Preparation_Worker`, incremental selection, dependency maintenance,
  declaration completion and separate function/file body work lists.
- `file.php`: `File_Preparation`, standalone file entry into the shared incremental scheduler.
- `semantics/bodies.php`: `Body_Preparation`, body contexts, source-order locals and statement boundaries.
- `semantics/expressions.php`: `Expression_Preparation`, literals, references, assignments, calls and member access.
- `semantics/types.php`: `Type_Preparation`, named type resolution and supported value/storage boundaries.
- `changes.php`: comparison of declaration facts and preservation of unchanged fact identities.
- `semantics/declarations.php`: `Declaration_Preparation`, function signatures, parameters and record fields.
- `semantics/literals.php`: exact integer literal preparation under the Simple C++ contract.
- `cleanup.php`: tree traversal invoking each specialization's cleanup.
- `data/structures.php`: backend-neutral facts, completed-file records, lookup observations and invocation data.
- `data/work_records.php`: typed retained preparation-work identities and dependency/error state.

`semantics/` groups language-processing algorithms; `data/` groups prepared facts
and retained work records. The preparation root retains entry/scheduling, comparison
and cleanup ownership.

Shared types live in `../../compiler/types/`; scope representation and lookup live in
`../../03_parse/scopes/`. This grouping changes locations, not processing behavior.

Prepared facts are accessed through the specialization records: `preparation()`,
`require_preparation()` and `set_preparation()`. The `Preparation_Facts` trait shares
nullable access, assignment and cleanup; concrete fields and required typed accessors stay in
the final node. Same-type nullable extraction is not a class-narrowing cast. Literal and reference facts share only
the prepared_expression type and stable-storage flag; integer decimal text, floating decimal spelling,
boolean value and reference declaration live in separate typed fact records. Floating
spellings are retained verbatim from validated numeric tokens, without host rounding.

Compiler::prepare() publishes shared preparation without backend emission.
Completed semantic changes and deletions accumulate in each collected file's
`preparation_changes` handoff until successful C++ assembly consumes them.
Compiler::cpp() consumes completed preparation; exec_cpp()/update_cpp() still run the complete pipeline.
Compiler_Lifecycle::reset_preparation() clears facts and dependent C++ output;
reset_cpp() clears output alone.

`Preparation_Worker` processes all supplied collected files through one incremental
path. New owners start added/pending; unchanged ready owners retain their facts. Declaration
work settles before the separate function-body and file-body lists run. Identity-keyed
sets deduplicate notifications. Function signatures and bodies have independent owners;
parameters/fields are prepared with their enclosing signature/record.

Declaration nodes select work through typed scheduling hooks. Retained work consists
of `function_signature_work`, `record_definition_work`, `function_body_work` and
`file_body_work`; only the file-body role has no named declaration. Required function/
record links are typed and constructors derive their source collection from that
declaration. Body nodes accept only `body_work`, preserving transfer of the existing
work identity when parsing replaces their syntax. Bodies never become symbols.

Work records dispatch queue selection, rebuilding and member settlement to typed
worker methods. The worker retains dependency ordering, comparison, publication,
error propagation and retry algorithms. Its dependency targets are declaration work;
dependents can be any work role. `kind()` is a computed compatibility category for
the existing C++ assembly path, not a mutable scheduling tag.

Call facts identify `collected_function`; current source record types identify
`collected_struct`. Storage facts intentionally share the broader occurrence identity:
parameters, fields, explicit locals and inferred first writes can all provide storage.
`same_signature()` and `same_record()` return true for equivalent observable facts.

Dependencies link consumers to declarations and back. Scope/name observations also
cover missing and ambiguous candidates. Effective signature/layout changes notify
consumers; implementation-only function changes do not notify callers. Required
by-value record completion detects cycles; recursive function calls remain valid.
Deletion marks all retiring preparation owners first, notifies transitive consumers
through intact reverse links, then detaches relationships and removes collected rows
and scope indexes. Consumers need only be scheduled, not rebuilt, before cleanup.
Full compilation/syntax resets sever dependency/lookup registrations before discarding
roots; there are no surviving consumers to notify. Strong identity storage is retained;
weak references are deferred. Body replacement detaches outgoing registrations before
rebuilding facts. Incomplete parsing blocks this boundary entirely.

Polymorphic syntax receives `preparation_context` directly through `node->prepare($context)`.
Each concrete node calls its typed preparation algorithm and attaches the returned facts;
there is no intermediate dispatch adapter or adapter allocation. The context remains
invocation-local and is never stored on syntax or as mutable current state on the scheduler.
Typed routines retain resolution/inference algorithms. Typed work hooks enter scheduler
methods that compare old/new specialized facts and settle work. Function-body entry
establishes its local/return context before traversing statements.
The file node's hook delegates to its executable body: it prepares only statements,
not the independently scheduled declarations. Bodies and ordinary blocks share one
source-order statement loop and pass the same active context to their statements.

Parameter and field algorithms belong to `Declaration_Preparation`. Local declarations
and variable assignments share the local-storage routine; member writes use a separate
typed field-write routine and never introduce locals. Literal/reference helpers accept
their concrete node types rather than arbitrary AST nodes.

Optimization debt: function-body setup still creates its own local context after
the scheduler creates the outer invocation context. Selection still scans file
declarations and registered lookup observations to discover
work. Incremental execution skips unchanged semantic work, but selection is not yet
limited to a changed-only input list. Context setup and direct change queues require
separate measurement and must preserve context isolation and missing-name observations.

Resolution runs here after the parsing join,
not in a second pre-resolution pass. Unchanged bodies retain syntax, occurrences and
facts at their retained positions in appended token storage; changed bodies replace their facts and
outgoing dependencies. Prepared completion records publish only after success.
Preparation owners retain `change_status`, `failed` and their diagnostic across
increments. Selection uses change status; only successful preparation settles an
owner to unchanged and clears its error. Expected preparation errors (`RuntimeException`)
mark the owner and its transitive consumers failed/changed. Independent work continues;
failed attempts are not repeated in the same invocation. A later invocation retries
pending work even without source edits. Signatures and bodies fail independently.

Declaration lookup registers dependencies before requiring completed facts. Retained
facts on failed declarations are unavailable to consumers. Recovery notifies consumers
even when a signature matches its last successful value. Previously ready dependency
chains are checked for cycles when completing newly introduced edges. Edited consumers
rebuild their dependencies, allowing removal of a failed prerequisite. This is mutable
recovery, not rollback: partial facts may remain, but completion/output is withheld.
An unexpected exception escaping the preparation worker sets `Model::$rebuild_required`.
The next sync, frontend or preparation entry discards compilation roots and retokenizes/
reparses all live inputs before preparing again. Module configuration and supplied
in-memory source bytes survive; reset visits dependency registrations but does not traverse prepared facts. Expected
`RuntimeException` diagnostics keep incremental retry. Distinguishing internal bugs
that also use `RuntimeException` from source diagnostics remains classification debt.

Function bodies compare exact source bytes using the existing `token_index` and
exclusive `end_token_index`: first-token offset through last-token offset plus length.
Internal whitespace changes replace a body; moving identical text retains it with its old
token positions until deferred token cleanup remaps retained spans. Signatures and file
executable statements still compare tokens.

Combined `sync` now uses the same tokenization/parsing phases. It clears completion
and output roots without clearing retained facts. Successful joins remove deleted
symbols through the same cleanup boundary before either backend resolves names.

The destination is one shared semantic preparation path. Extend this model for new
language semantics. The separate token-indexed name maps and template checks are
[parked LLVM regression code](../../05_backend/llvm/README.md), not a second active
semantic model. LLVM must be reviewed and adapted to consume shared facts before
its development resumes, leaving backend-specific lowering there. Consolidating
those implementations is deferred; this isolation does not change either behavior.

## Ordinary functions and value structs

The current S2S boundary remains one source file. Function/struct declarations may
appear after their users. Signatures are attached before any body is prepared;
each function gets a fresh local `Key_Storage_List<prepared_storage>` seeded with
parameters. Entry locals are not implicit function captures. Calls retain their
resolved declaration and signature; members retain their exact prepared field.
No backend performs symbol lookup. Struct fields use an ordered duplicate-key
collection owned by their prepared record, not the surrounding variable scope.

Supported function signatures use named scalar/record types, explicit returns
(including `void`), positional value parameters and explicit `&` parameters.
Only a uniquely resolved function is supported; overload selection and templates
remain deferred. Integer value boundaries use the runtime integer conversion;
reference boundaries require stable storage with a compatible representation.
Integer aliases with equal width/sign have compatible reference storage.

Structs use inline value storage. This frontend slice supports `bool`, fixed-width
integer aliases and nested structs as fields. Ordinary `int` and `float` fields
remain excluded by the existing compact-layout contract. Typed locals without an
initializer use their normal generated default; copies, field reads/writes and
record function boundaries are supported. Positional array literals are not struct
constructors. Keyed initialization, `new`, field initializers and other syntax not
accepted by this frontend are not added here. General validation/STAN is deferred;
preparation rejects only boundaries it cannot serve correctly to generation.
