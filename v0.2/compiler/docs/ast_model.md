# AST ownership and preparation decisions

Doc Status: supporting

These are ongoing implementation rules agreed during compiler review. They do not
extend the language semantics or authorize unrelated features. The owning language
specifications remain authoritative.

## One file root and one syntax model

`ast_file_node` is the sole frontend file result. Parsing and local collection happen
together; separate parsed/collected file wrappers do not represent distinct states.
`parse()` and `collect()` currently return the same kind of complete root; the latter
also accepts discovery notifications. Neither API returns a second copy of syntax.

The root owns tokens/source context and an ordered `declaration_nodes` syntax
inventory. Its `declarations` map holds first references keyed by `func:name` or `var:name`.
Indexes point to original nodes without creating declaration identities. A symbol
entry holds one nullable head; `duplicate_element()` retains erroneous extras.
Public function/global chains belong to the compilation scope, local-variable chains to their
body scope. File indexes never own a second chain. Publication uses temporary tails
for constant-time appends. File traversal always uses syntax inventory, so it stays
local and complete even when a live chain crosses files or is rebuilt incrementally.

The completed root carries a plain list of duplicate diagnostics. Compilation
gathers local and cross-file duplicates into a minimal `diagnostic_group` before
settling declarations or bodies. Every extra declaration refers to the first
location; global ordering uses source path and token position. An ambiguous key
must not resolve to its first candidate. General syntax recovery is deferred.

Declarations implement `ast_declaration_interface::declaration_key()`. The shared
`ast_declaration` trait owns `short_name` and nullable `namespace`; `name()` derives
the qualified identity and `declaration_key()` returns only `short_name`. Index owners add
category prefixes such as `func:`; these prefixes are not language namespaces.
Function `symbol_key()` uses the qualified identity. Calls resolve exact canonical
paths using their body scope context; see [the namespace slice](namespaces.md). Implicit private `main` is first when present
and excluded from global publication. Explicit main and other declaration kinds
remain outside the current source-language slice.

File-local imports are `ast_import` syntax children in `ast_file_node.imports`, not
declarations. A first-reference index selects unqualified function bindings;
preparation validates all occurrences. Calls retain the target's existing stable
entry, with no copied declaration or exported alias identity. See
[namespace/import ownership](namespaces.md).

All AST subclasses use `ast_`. A node owns token positions `[start, end)` and a
single-assignment syntax parent. Only the file root has a null parent. Source bytes
and line/column information are recovered through the root and token byte offsets.
Nodes keep names and operators directly when needed; ordinary semantic lookup does
not walk parents. A processing worker may retain source context for its traversal.
Native parent links are non-owning; PHP currently uses ordinary object references.

## Prepared facts belong to their AST owner

The direction is one AST that gains explicitly documented facts, not a parallel
prepared AST. Put a fact on the node whose meaning it describes. Mark it as a
prepared fact in a doc comment next to the property, identifying when it becomes
available and what null means. For example:

```php
/** Written type name; null when omitted. */
public readonly ?string $type_name;

/** Prepared fact: resolved type identity; null until this declaration is prepared. */
protected ?type_definition $type_def = null;
```

Authored syntax and resolved facts are different even when both are names/types.
A local owns `type_name` and `type_def`; a function owns `return_type_name` and
`return_type_def`. Omitted written syntax does not mean the resolved type may stay
unknown after successful preparation. Current missing-type inference remains
unsupported. Checked accessors reject premature fact reads.

Calls retain stable symbol entries resolving to current function declarations;
variable references retain either a local declaration or a stable global entry.
Type links identify canonical definitions. Do not introduce
wrappers that merely repeat those identities. A compound fact record is justified
when it holds substantial additional data owned by a node, not simply because a
preparation pass produced it. It must not recreate a competing statement tree.

**Current representation:** integer values, occurrence-specific local bindings/value
facts, decoded output bytes and return conversions live on their AST nodes.
There is no parallel prepared expression/statement hierarchy, second statement
list, or function/body wrapper. Output-only strings still do not introduce a
general string type. Unknown constants remain distinct from unprepared values
through checked type accessors; empty decoded strings remain valid facts.

Bodies retain completion state and work counters. Preparation resets affected
facts before analysis; it marks a body complete only after all statements succeed.
Emission and inspection traverse the original statement list and require completion.
`prepared_entry` retains only file, target and completion revision; `prepared_program`
retains the source inventory, selected entry and source completion revisions.
These revisions reject stale handles after reset/reanalysis; they are not incremental
snapshots or an incremental invalidation protocol. No AST child lists are duplicated.

## Processing, completion and future concurrency

Workers own parsing, analysis, traversal and lowering policy. AST records and traits
own fields, local invariants and checked accessors. A shared declaration trait is
not a preparation engine. Temporary lookup tables belong to the worker performing
the operation; they are not another semantic owner.

Frontend completion precedes global publication. Declaration preparation completes
before bodies consume foreign declaration facts. Foreign body inspection is not
needed for ordinary calls. Body preparation completes before lowering. Completion
barriers remain necessary even without file wrappers; a failed phase must not
publish successful output or make partial facts look ready.

Maintain the [native ownership plan](../../specs/planning/two_traversal_preparation.md):
source/body ownership, settled external reads, owner-private indexes and private
lowering. Review concrete shared lowering needs individually. PHP remains sequential;
do not restore worker transports or shared-object emulation. Thread safety is a
future native design obligation, not a property proven by this PHP implementation.

Reset affected facts before reanalysis. Stable function entries resolve current
declarations; old sessions retain syntax, not immutable prepared-state snapshots.
Incremental frontend reuse and function-body cache invalidation are implemented.
Incremental lowering and concurrent reader retirement remain deferred.

## Review and evidence

Prefer fewer owners and indirections where the concepts permit it. Do not trade
clarity for speculative caching or extra abstractions. Performance improvements
require comparable measurements; fewer PHP objects alone do not establish a speedup.
Keep tests for direct identity, readiness boundaries, diagnostics, lowering purity
and end-to-end behavior. Explain each class/structure's role in its doc comment and
include a short code example when it makes that role clearer.

Incremental frontend sessions may share an unchanged AST root and its tokens/source
snapshot by reference. Generations share the live compilation scope; file syntax inventories retain every declaration independently of its live duplicate links. See
[incremental frontend generations](incremental_frontend.md); preparation of shared
roots is not part of that slice. Function keys use `func:name`; `symbol_key()` derives
them without a slot/key/status record.

Function identity and incremental cache lifetimes follow [stable symbols](stable_symbols.md).

## Statement blocks and conditional arms

`ast_statement_block` owns the common ordered statements, lexical scope and local
reference indexes. `ast_function_body` extends it with completion, statistics and
dependency-watcher identity; `ast_block` is an ordinary branch body. `ast_if_statement`
owns an ordered list of `ast_if_arm`, each owning a block and an optional condition
(null only for else). Multiple arms are valid syntax, so a list is appropriate.
No prepared block copies or per-arm watchers are introduced. See [conditions](conditions.md).
