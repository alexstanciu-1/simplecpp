# Model ownership and reference intent
Doc Status: supporting

Ownership describes the application graph; PHP currently uses strong object
references everywhere. The native starting point is shared_p<T>. Documentation
annotations do not implement weak references, lifetime checking or serialization.

| Tag | Meaning |
| --- | --- |
| @storage.owner | Owns a Storage or Keyed_Storage collection and its membership. |
| @ownership owner | Owns a direct record, AST child or payload. |
| @storage.reference path | References a record in the named Storage or Keyed_Storage. |
| @reference.source path | References data in a direct owner/graph or transient collection. |
| @reference.weak | Convenience/backward/index link; non-retaining intent for later native review. |
| @storage.index path | Integer positions into the named Storage. |
| @storage.boundary path | Token span end; an exclusive end can equal count. |

## Current graph

Model owns modules, tokenization/parse/collection/output results and global scope.
Modules own files. Token lists own token objects and captured source text. Parsed
files own their root AST and local scopes. AST nodes own concrete payloads; payloads
own single child nodes and Storage<ast_node> child lists. No shared backing node
store or Storage_View remains. Collection files own occurrence entries and work lists.
LLVM modules own functions; functions own operands and blocks; blocks own text.

file.tokens and parsed_file.collection are convenience links to Model results.
collected_file.root mirrors parsed_file.root. collected_name.file/scope and scope.parent
are backward/context links; scope name maps index collection entries. AST operands
and type syntax are direct child relationships. Collection/preparation node references
point into parsed_file.root's syntax graph, not a nonexistent parsed_file.nodes store.
Local scope ownership stays uniform in parsed_file.scopes; blocks reference scopes.

Preparation returns Storage<llvm_prepared_file>; each file owns its function list,
and functions own ordered parameter lists and sparse declaration-keyed locals.
The transient struct registry and per-file Keyed_Storage type maps share type
records; each type owns Keyed_Storage<llvm_field>. The instance registry and pending
queue reference functions already owned by files. Calls, keyed external-function collections and
resolved-name maps reference existing targets. A cleaner native preparation-type
owner remains future work. Emission copies operand values into independent output.

## Explicit nullability and publication

No ? means required, even without an initializer. Assign required fields before
reading/publishing. ?T means absence is valid; initialize/reset explicitly to null.
Weak intent does not imply nullable, and empty collections are not absent collections.
The wider nullability audit remains debt in REVIEW.md. Native STAN may require
constructor assignment for worker fields; a separate populate call is not always
proved automatically. Syntax_Nodes validates payload kinds but public records remain
mutable; callers must maintain or rebuild indexes when indexed data changes.

Stage invalidation and source-snapshot authority are described in ../MODEL.md.
No strong cycle reclamation, rollback, concurrent mutation or snapshot machinery is
introduced by this cleanup. Retaining a PHP object can retain other linked records;
future native optimizations must preserve needed behavior or state the change.
