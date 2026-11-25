# Declared local types
Doc Status: supporting

Status: implemented in the PHP prototype, after name binding and before body
checking. This resolves annotations; it does **not** establish that an initializer,
assignment or returned local has a compatible type. [Body checking](body_checking.md)
now performs those checks; lifetime analysis and [lowering](lowering.md) carry
valid scalar locals through native execution. Formal parameters share the local
binding table; their type IDs come from the completed signature join.
Parameterized functions now check with initialized incoming bindings and receive
scalar lifetime facts, then use the scalar native lowering path.

## Meaning and ownership

[Local_Type_Resolver](../../src/04_analyze/resolve_types/locals.php) consumes the
body-local suffix of the declarations from name resolution. Each annotation uses
`Annotation_Types::definition`, shared with declared return types, to
follow its accepted declaration binding into the prepared definition view. The coordinator uses
`Type_Cache::materialize` for both roles: repeated annotations share canonical
type IDs and completed representations. No consumer list of built-in names or
per-annotation definition copies is introduced. Named types are the current
subset; compound type syntax remains future work.

Unknown types produce an error during name binding at the type-name span. A local requires a value
type, so a definition representing `void` is rejected at that same span.
Initializers do not determine a local's declared type or retype integer literals.
For example, `$x uint32 = 42;` resolves the `uint32` annotation here; the later
checker must still apply the common conversion resolver to the default-`int`
initializer. That checker currently rejects the non-identity conversion; this
annotation stage does not authorize it.

`Local_Types` holds one linear list of type IDs in local-ID order and a shared
reference to its exact `Symbol_Resolution`. That owner already provides the
source AST, declaration, scope and use bindings. There is no duplicate local
record dataset. `Type_Resolution::locals_for(symbol_id)` returns this container;
`Local_Types::type_for(local_id)` returns its shared type ID. Callables without
locals have no container. Parameters count as locals: their ordered signature
type IDs supply the prefix without resolving their annotations a second time.
The final type snapshot checks that both views agree.

## Work and refresh

`Type_Resolver` selects signature and local-annotation tasks before
running workers. Signature workers resolve return and parameter annotations;
local workers request only body-local definitions. Either worker batch can run
first against fixed symbol, name-binding and catalog inputs. They never allocate into the shared type store.
The coordinator validates complete batches and materializes definitions into
one private candidate, in deterministic callable/local order. The signature join
returns signature associations; the local join consumes those fixed associations
to assemble parameter IDs followed by body-local IDs. Only
after both finish does `refresh` assemble the `Type_Resolution` for body workers. There is
no threading or separate full-build algorithm.

Associations are reusable only when the exact name-binding owner and all their
type records remain current. An AST replacement replaces its local associations
as a unit; numeric IDs from another type-cache lineage do not establish reuse.
Removing locals or their callable removes the current associations independently
of full selection. Unchanged containers and immutable type records are shared.
Catalog changes use the existing context invalidation and full-selection rules.
These are stage reuse rules, not new incremental admission categories.

Errors leave the accepted compiler snapshot untouched. Incompatible expressions
fail body checking; valid scalar locals continue through the common downstream stages.

`--debug=json` exports `types.local_types` alongside signatures and the shared
type table. Direct stage exports support inspection; no export work runs without
a request.

[Local type proofs](../../tests/04_analyze/resolve_types/local_types.php) cover real file reads,
repeated/cross-file annotations, counted materialization, reversed workers,
input purity, invalid joins, unchanged sharing, fresh/full equivalence, edited
annotations, removed locals, type-cache lineage, a metadata-defined type,
unknown/non-value types, debug output and failure followed by repair.
