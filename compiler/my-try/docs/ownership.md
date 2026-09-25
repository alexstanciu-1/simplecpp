# Model ownership and reference intent
Doc Status: supporting

Ownership describes the application graph; PHP deliberately uses ordinary strong
object references. Native owning links use shared_p<T>. An adjacent `/** weak<T> */`
on a named object property now emits a native weak_p<T> field; the three direct
scope links use this binding. `@reference.weak` alone remains documentary intent,
including the collection/index annotations. No automatic lifetime analysis or
serialization follows from these tags.

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
files own their root AST and local scopes. AST nodes own additional structures and first-child/next-sibling chains. Native
parent/previous backlinks are weak. Named structure child fields and lists remain
retaining aliases during this migration; see ast_layout.md. No shared backing node
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

## Native scope observers

`scope.parent`, `block_structure.scope` and `collected_name.scope` are native
weak fields. `parsed_file.scopes` and `Model.global_scope` remain strong owners.
Assignments accept the existing shared records; reads use `weakref_get` to acquire
a shared handle. Required block/occurrence scopes then use `object_cast` to reject
an absent or expired scope. Parent traversal ends on an empty acquisition. A local
acquisition keeps its target alive for that use; it does not turn the stored link
back into an owner.

The PHP facade returns the ordinary reference unchanged. PHP checks prove functional
behavior, not native expiration. Retaining an AST or collection result alone does
not guarantee its native scope owner survives model reset. Keep the parsed-file or
model owner alive when those scope links must remain usable. Other graph cycles
(including occurrence/file links and indexed records) are not solved by this slice.

## Explicit nullability and publication

No ? means required, even without an initializer. Assign required fields before
reading/publishing. ?T means absence is valid; initialize/reset explicitly to null.
Weak intent does not imply nullable, and empty collections are not absent collections.
The wider nullability audit remains debt in REVIEW.md. Native STAN may require
constructor assignment for worker fields; a separate populate call is not always
proved automatically. Syntax_Nodes validates payload kinds and local binding/parameter field relationships
before parser publication. Binding equals/value presence must match, untyped writes
need a value, declarations agree with type syntax, and explicit targets require
assignment classification. Parameter reference mode agrees with ampersand presence.
These checks do not recursively revalidate children or replace required-field
initialization checks. Public records remain mutable; callers must maintain or rebuild indexes when indexed data changes.

Stage invalidation and source-snapshot authority are described in ../MODEL.md.
No strong cycle reclamation, rollback, concurrent mutation or snapshot machinery is
introduced by this cleanup. Retaining a PHP object can retain other linked records;
future native optimizations must preserve needed behavior or state the change.
