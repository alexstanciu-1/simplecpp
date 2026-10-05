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
scopes. Specialized node families own named children; the abstract statement body
owns its ordered statements. Conditional arms own their block and successor arm;
there is no payload or generic parent/sibling graph. Loops own their block body and
typed header children. Prepared control transfers observe the enclosing breakable
syntax through a supported weak field, not an owning back-edge. Invocation-local
preparation/emission contexts restore their nearest transfer targets after each loop
or switch. Switches own ordered case groups, groups own labels and an ordinary block
body, and value labels own their constant expression. No group adds a parser scope
or independent preparation-work owner; its lexical environment is transient.

Conditional expressions own two or three expression children. Shorthand owns its
condition once; the evaluated-value snapshot is an emission-local temporary, not
another syntax edge or retained variable. Result facts are non-addressable values.
Match expressions own the selector and source-ordered arms, each owning labels and
one result expression. Default is an arm with no labels, not a second owning fallback
edge. Its delayed emission and the selector snapshot are backend-local; no new scope,
statement body, transfer target or work identity is retained.

Functions own signature scopes; structs own member scopes. File/body scopes observe
owners retained by the parsed file. Occurrences observe syntax, collection and scope;
name-bearing nodes observe their canonical occurrence. Scope indexes reference
those same records. Publication shares identities rather than copying definitions.

Prepared facts retain canonical types and reference collected declarations.
Calls weakly observe a `callable_target_i` owned by source collection or the runtime
callable catalog. Their declaration-independent signature shares parameter contracts
with source parameter storage facts where applicable. Runtime free functions and factories have no
source AST or collected-parameter requirement. C++ callable bindings retain their
exact registered target and are reset with the runtime callable/type roots; they
never become the owner of source semantic facts. See [callable contracts](MODEL.md#callable-contracts).
Scope runtime-function pools are non-owning indexes of catalog identities, separate
from source collection membership. Both feed name-pool observations; retiring source
declarations must never remove installed runtime targets.
Derived source-record capabilities are owned by `prepared_record`, alongside its
prepared fields. Registry definitions retain identity and registered contracts;
they do not cache a second mutable copy of source-derived capabilities. The transient
`Capability_Preparation` query requires declaration completion and registers the
consumer dependency before reading them. Unknown/failed preparation is not a negative
capability answer. Nested changes propagate even when canonical type IDs are stable.

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
failed parses retain partial identities for retry. The parsed file keeps its previous
scope owners and newly selected scopes throughout parsing, including after failure.
Only successful parsing replaces that inventory with the selected scopes; weak
body observers must never outlive their scope owners during this handoff. Retired nodes must not re-enter
the active graph. Stage entry checks reject incomplete/deleted owners; cleanup may
visit them. See [lifecycle](../lifecycle/incremental.md).

Acquire weak links through `weakref_get` before use. Same-type nullable required
returns use their checked return boundary; genuine class narrowing uses `object_cast`.
Keep an owning parsed-file/model handle when native scope observers must remain
usable. PHP behavior does not prove native expiration or cycle reclamation.

## Mutation responsibilities

Stage workers own their processing and must not invoke later-stage processing or
change later-stage scheduling policy. They may share typed structures, stable
identities and attached results. The coordinator owns stage sequencing and reset
order; a shared data link does not transfer algorithm ownership. Parser-called
collection belongs to `03_parse/collect/`. Backend bindings and naming belong only
to their backend. Existing cross-stage hooks and retirement/invalidation exceptions
are inventoried in the [stage-boundary audit](../planning/stage_boundary_audit.md),
with repairs sequenced by the [stage-owned processing plan](../planning/stage_owned_processing.md).
AST preparation/C++ hooks and collected callable/work-selection processing hooks
have been removed. Stage workers own dispatch/traversal and attached-fact processing.
Analysis-internal work dispatch also belongs to the worker, not retained records.
Observed name resolution belongs to analysis; shared lexical lookup cannot invoke
preparation observation. Scope subscription containers retain shared identities,
with analysis removing a scope observation only after its last consumer detaches.
Parsing transfers work identity but never sets preparation scheduling state.
Analysis consumes persistent source-change flags and owns semantic retirement.
The lifecycle coordinator validates the parse join, orders semantic unlinking,
then calls frontend `Collection_Retirement` for membership/type-association/syntax
disposal. C++ generation never clears the semantic change handoff: `Compiler::cpp()`
acknowledges its source after publishing successful output. Standalone generation
leaves the handoff intact. Backend reads use `Preparation_Status`/`Type_Queries`,
not preparation workers;
neutral maintenance remains a structural facility with caller-controlled recursion.

Workers maintain indexes when indexed names/membership change. Scope methods expose
encapsulated membership operations; publication policy stays in `Scope_Publication`.
Removal leaves previously acquired object handles alive but does not authorize those
handles as current compiler data. Collection positions are not live counts.

[Storage](../storage/STORAGE.md) defines shared membership and snapshots.
[AST layout](ast_layout.md) defines typed ownership and inspection.
[Portability review](../portability/REVIEW.md) tracks lifetime/publication proof gaps.
