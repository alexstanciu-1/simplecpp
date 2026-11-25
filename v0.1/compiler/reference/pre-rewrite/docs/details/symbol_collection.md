# Project symbol collection
Doc Status: supporting

Status: implemented in the PHP prototype. Inspection requests stop before native
building with `completed: false`; output requests continue through publication.
Collection itself does not check calls, return types, contracts or bodies.
[Declared return-type resolution](return_type_resolution.md) now follows the frontend stages.

## Scope and ownership

The current grammar contributes named functions in the global
namespace, plus one unnamed implicit entry callable per file. Identifier spelling
is compared exactly, without case folding. Namespace/member/constant syntax is
not implemented; their future collection belongs to the same project model.
Typed parameters stay under their callable's AST declaration, without project
symbol rows. Collection does not claim their semantic binding is implemented.

`Declaration_Collector::select()` selects missing or replaced file snapshots,
or every current file on full rebuild. `collect_file()` reads one fixed frontend
and returns `File_Declarations`: temporary extraction rows with names, kinds and
node IDs. Workers do not allocate project identities or modify global indexes.
The coordinator's `join()` validates task/result membership, then consumes results
in source order regardless of completion order. Execution is serial today.

`Symbol_Store` owns a flat current dataset and indexes by logical ID, qualified
name, source file, semantic owner and implicit file entry. Lookup goes through
its methods. The current named key is owner/kind/namespace/name; a file entry
uses source-file identity and never acquires an invented source name. Collection
owns duplicate diagnostics, which identify both origins. Storage rejects invalid
or duplicate insertion as an internal invariant failure.

Named symbols match the previous project index by key, independently of file
origin and node numbering. A changed record retains its ID but references the
new exact `File_Frontend`; unchanged files share previous records. The join
rebuilds current membership, excluding removed contributions before consumers
run. Rename is deletion plus addition. Moving a definition between files can
retain its named identity without retaining file-rename identity or admitting
incremental code generation for the move.

IDs increase monotonically within the accepted session, up to `MAX_SYMBOL_ID`;
removed IDs are not recycled. Failed candidates do not consume accepted IDs.
Full collection uses the same workers and preserves surviving identities.

## Organization and extension points

```text
collect_symbols/
    data/
        structures.php
        store.php
        result.php
    handlers/
        declarations.php
    utilities/
        declaration_syntax.php
        frontend_validation.php
    collect.php
    compare.php
    join.php
    comparison_join.php
```

The folder groups follow the [shared organization convention](../code_organization.md#controlled-feature-extension-in-the-php-prototype).
Classes keep the `collect_symbols` namespace. `Declaration_Collector` explicitly
composes [Declaration_Collection](../../src/04_analyze/collect_symbols/handlers/declarations.php),
whose private static methods implement declaration dispatch and extraction.
`collect_file()` emits the implicit entry and then dispatches each defined entity
in source order. Each handler reads one fixed frontend and returns one existing
`declaration` record without allocating project IDs or modifying an index.
Unsupported kinds retain their source-anchored rejection. No new declaration
kinds are introduced by this organization.

[Declaration_Syntax](../../src/04_analyze/collect_symbols/utilities/declaration_syntax.php)
owns declaration-name spelling, named diagnostic anchors and the syntax roots
that contribute to a symbol's definition. Function extraction obtains its
structural parts once through `parse\Syntax_Access`, then reads the validated
name through `name_text()`. Duplicate checking uses `name_anchor()` rather than
assuming function child layout inside the join. The conflicting name remains
the primary diagnostic span; the first origin still points to its declaration.
These utility methods read existing syntax and introduce no retained metadata.

| Feature obligation | Owned extension point |
|---|---|
| Recognize and extract a declaration | `Declaration_Collection::collect_declaration()` and the selected handler |
| Read validated name spelling | `Declaration_Syntax::name_text()` |
| Locate a named declaration for diagnostics | `Declaration_Syntax::name_anchor()` |
| Choose definition-contributing syntax | `Declaration_Syntax::definition_nodes()` |
| Identity, duplicates and current membership | `Declaration_Join::join()` and its private append operation |
| Classify definition/body changes | `Symbol_Comparer::compare()` |

`collect.php` retains selection and file extraction. Its public `join()` delegates
to [Declaration_Join](../../src/04_analyze/collect_symbols/join.php),
which owns identity reconciliation and duplicate policy. `Frontend_Validation`
shares current-frontend checks between selection and joining.
`compare.php` retains `Symbol_Comparer` selection and comparison workers; its
public `join()` delegates to
[Comparison_Join](../../src/04_analyze/collect_symbols/comparison_join.php).
The parser's `Syntax_Comparer` is a supporting utility;
the symbol comparer has a different role. Data classes retain storage, lookup,
validation and export responsibilities. Other analysis processes and future
namespace/member/constant rules remain separate work.

## Catalog and acceptance boundary

Collection emits `Symbol_Refresh.changes` with additions, removals and
**uncompared** matched declarations from recollected files. The later
[comparison stage](symbol_comparison.md) completes their classifications. Matching identity proves neither
an unchanged contract nor a body-only edit. A body is child content, covered by
`children_changed`; no dedicated body-change property is needed. That summary
is nullable: null means not established/not applicable, false means compared
and unchanged, true means a known change. Elements without children need no
child records. Comparison produces known booleans for matched callables and
omits rows proved unchanged in both definition and child content.
Unchanged file snapshots require no extraction and contribute no change rows.
Resolution now owns its task selection independently of the change catalog.

The returned `Compile_Result.symbols` owns the current store and per-update
catalog. Its debug JSON exports current records and previous/current change
anchors, without copying ASTs or source text. A returned catalog can
retain old snapshots until its reader finishes. The session replaces its complete
`observed` snapshot only after all requested stages, including comparison, succeed;
`observed.symbols` stores current records only, never a chain of catalogs.
Inspection leaves the `published` snapshot and generation unchanged. Failures preserve
all observations. Physical tombstone cleanup is still deferred.

Cataloging additions/removals does not implement their incremental reactions.
The existing early full flag is provisional: matched comparison, name/type
resolution and the downstream eligibility gate must run before any selective
lowering. Successful collection alone does not mean calls are valid; the
following resolution stage diagnoses unknown functions.

## Verification

[Collection checks](../../tests/04_analyze/collect_symbols/symbol_collection.php) cover project
lookup, unnamed entries, exact AST origins, independent workers and deterministic
joins, unchanged reuse, uncompared edits, exact duplicate name spans and first
declaration origins, duplicate rollback, rename/removal and
recreation, cross-file movement, fresh-build equivalence of declaration facts,
old-origin lifetime, and ID exhaustion. Existing CLI/simulation, source/parse,
locking and crash-restoration checks continue through this new stopping point.
