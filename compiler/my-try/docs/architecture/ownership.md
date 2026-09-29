# Ownership and reference intent
Doc Status: supporting

PHP uses ordinary shared object references. Native reference classes use shared
handles; explicit supported `weak<T>` fields lower to weak handles. Documentary
`@reference.weak` alone does not implement expiration or reclaim cycles.

| Annotation | Intent |
| --- | --- |
| `@storage.owner` | Owns collection membership |
| `@ownership owner` | Owns a direct record or syntax child |
| `@storage.reference path` | Observes a record in the named collection |
| `@reference.source path` | Reference provenance in a direct/transient graph |
| `@reference.weak` | Non-retaining/backward/index intent |
| `@storage.index path` | Position in the named collection |
| `@storage.boundary path` | Exclusive boundary, possibly equal to count |

## Current graph

Model owns modules and global/language scopes. Modules own source records; sources
own file/token/parse state and observe their module. Tokens own captured text and
token records. Parsed files own typed AST roots, collected records and applicable
scopes. Concrete nodes own named children; there is no payload or parent/sibling graph.

Functions own signature scopes; structs own member scopes. File/body scopes observe
owners retained by the parsed file. Occurrences observe syntax, collection and scope;
name-bearing nodes observe their canonical occurrence. Scope indexes reference
those same records. Publication shares identities rather than copying definitions.

Prepared facts retain canonical types and reference collected declarations.
Declaration work belongs to collected definitions; body work belongs to body nodes.
Dependency and lookup memberships currently use strong identity containers with
explicit unlinking. C++ fragments refer to existing work identities. Retained model
records never own workers or operation contexts.

## Explicit nullability and publication

Required fields may be populated after construction, but must be assigned before
read or publication. `?T` means absence is usable state and needs explicit reset and
checked extraction. An empty collection is valid, not absent. Weak intent does not
make a required relationship nullable.

Parsing may register a declaration before completing its fields. Its file remains
incomplete and later phases wait for a successful join. Mutation is not transactional;
failed parses retain partial identities for retry. Retired nodes must not re-enter
the active graph. Stage entry checks reject incomplete/deleted owners; cleanup may
visit them. See [lifecycle](../lifecycle/incremental.md).

Acquire weak links through `weakref_get` before use. Same-type nullable required
returns use their checked return boundary; genuine class narrowing uses `object_cast`.
Keep an owning parsed-file/model handle when native scope observers must remain
usable. PHP behavior does not prove native expiration or cycle reclamation.

## Mutation responsibilities

Workers maintain indexes when indexed names/membership change. Scope methods expose
encapsulated membership operations; publication policy stays in `Scope_Publication`.
Removal leaves previously acquired object handles alive but does not authorize those
handles as current compiler data. Collection positions are not live counts.

[Storage](../storage/STORAGE.md) defines shared membership and snapshots.
[AST layout](ast_layout.md) defines typed ownership and inspection.
[Portability review](../portability/REVIEW.md) tracks lifetime/publication proof gaps.
