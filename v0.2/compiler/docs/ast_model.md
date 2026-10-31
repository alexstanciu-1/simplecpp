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

The root owns tokens/source context and one keyed declaration collection. Keys such
as `func:ping` identify actual declaration nodes. Indexes and resolved references
point to these nodes without creating new declaration identities. Duplicate keys
must diagnose rather than overwrite.

Declarations implement `ast_declaration_interface::declaration_key()`. The shared
`ast_declaration` trait defaults that local key to the name. Index owners add
category prefixes such as `func:`; these prefixes are not language namespaces.
Namespace qualification and caller-context lookup remain deferred, with no
namespace argument on the declaration interface. Implicit private `main` is first when present
and excluded from global publication. Explicit main and other declaration kinds
remain outside the current source-language slice.

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

Calls link directly to settled function declarations; variable references link to
local declarations; type links identify canonical definitions. Do not introduce
wrappers that merely repeat those identities. A compound fact record is justified
when it holds substantial additional data owned by a node, not simply because a
preparation pass produced it. It must not recreate a competing statement tree.

**Current boundary:** declarations and calls already own resolved facts. Other
prepared expression/statement records and completed program/body handles still
exist. Their migration is separate work, not claimed complete by removal of the
file wrappers. Keep useful conversion, literal-decoding and value facts while
moving their ownership deliberately.

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

Reset affected facts before reanalysis. Declaration links refer to the same objects,
so old handles are not immutable snapshots across reset. Incremental identity,
retokenization and concurrent reader retirement remain deferred until their slice.

## Review and evidence

Prefer fewer owners and indirections where the concepts permit it. Do not trade
clarity for speculative caching or extra abstractions. Performance improvements
require comparable measurements; fewer PHP objects alone do not establish a speedup.
Keep tests for direct identity, readiness boundaries, diagnostics, lowering purity
and end-to-end behavior. Explain each class/structure's role in its doc comment and
include a short code example when it makes that role clearer.
