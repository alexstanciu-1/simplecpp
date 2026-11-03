# Common declaration scopes

Doc Status: supporting

This is a structural refactor. Supported syntax, lookup order, output and diagnostics
remain unchanged. Nested functions, classes, constants, captures, namespace lookup
and parent-scope fallback are not enabled.

## Model

```text
compilation_session.scope (parent = null)
    ↑ file.scope.parent
ast_file_node.scope
    ↑ body.scope.parent
ast_function_body.scope
```

All three use the same `scope`, `symbol_entry` and `Scope_Collection` types. File
scopes index functions, including the private implicit main. Body scopes index
local declarations using `var:name`; the compilation scope indexes exported
functions using `func:name`. Keys belong to the declaration interface. Stable entries
hold only references to candidate declarations and their general change status.

`scope.entry()` creates an identity only in that scope. `scope.resolve()` searches
only that scope. The parent field records containment without introducing implicit
lookup, shadowing, capture or visibility behavior. Files parsed independently are
detached roots until compilation publication assigns their parent. Reused file/body
scopes retain identity; the stable compilation scope is updated in place.

The AST still owns declarations/statements. `ast_file_node.declarations` remains a
head-reference view, with private main first, for existing traversal/inspection.
Body `locals` retains statement-position references and IDs; `references` retains
name occurrences. These are indexes, not copies of declarations or another symbol
namespace. Preparation's `visible_locals` is its source-order visibility index:
collecting a declaration does not make a forward variable reference legal.

## One collection and publication worker

`Scope_Collection` owns pending candidates and publishes them into a scope:

- `declare(node)` registers newly parsed syntax and links same-key duplicates
  belonging to that syntax scope.
- `import(node)` registers an existing declaration reference without relinking
  its source-owned duplicate chain.
- `publish(previous?)` installs candidates and updates stable entries. A missing
  key leaves a removed entry; reappearance reactivates it. Nothing becomes visible
  in the destination until publication.

The parser uses this worker for file functions. `Body_Collection` records body
statement metadata and delegates local declarations to this same worker; it no
longer implements name grouping or duplicate-chain publication itself.
`Symbol_Publication` remains the compilation coordinator: it checks that discoveries
belong to completed source files, selects public functions under current language
rules, and delegates aggregation/publication to `Scope_Collection`. It contains no
second implementation of entry updates. The normal path still supports early
header notifications; the incremental path imports completed/reused declarations.

Declaration completion and source IO checks precede live compilation publication.
Duplicate diagnostics retain all declarations, and imports never mutate reused
source chains. Signature-change comparison belongs to `function_dependencies`, not
to generic symbol entries or scope collection.

## Compilation state stays outside scopes

`compilation_session.scope` replaces `symbols`. The session also owns
`function_dependencies` and `is_current`. Success shares the stable scope and
dependency table with the next generation and retires the previous session; failure
leaves it current. The former scope-to-session backlink is removed.

Normal standalone/full preparation accepts a compilation scope directly. The
incremental coordinator passes the session's dependency table explicitly while
rebuilding dirty bodies. Scopes themselves hold no dependency-role tables or
compilation-lifecycle state. The frontend service remains frontend-only.

## Proof

`php v0.2/compiler/tests/scopes.php` covers all flavors: file/body/compilation
containment, local name isolation, original AST identity, pending visibility,
duplicate chains, shared publication/removal/reactivation for both function and
variable declarations, no parent fallback, private main exclusion, rejection of
nested functions, and unchanged declaration-before-use behavior.

Existing frontend, incremental cache, service, duplicate-diagnostic and native
function tests remain regression checks. No native runtime or lowering API changes
are part of this refactor.
