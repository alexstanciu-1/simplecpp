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
functions using `func:name`. Keys belong to the declaration interface. Stable entries hold one head reference and their general change status. A successor
in the declaring scope duplicate chain means ambiguity. File scopes are reference
indexes, not owners of a second function duplicate chain.

`scope.entry()` creates an identity only in that scope. `scope.resolve()` searches
only that scope. The parent field records containment without introducing implicit
lookup, shadowing, capture or visibility behavior. Files parsed independently are
detached roots until compilation publication assigns their parent. Reused file/body
scopes retain identity; the stable compilation scope is updated in place.

The AST still owns declarations/statements. `ast_file_node.declarations` remains a
head-reference view, with private main first, for existing traversal/inspection.
The ordered `ast_file_node.declaration_nodes` inventory retains all file syntax,
including duplicates; file traversal never follows a compilation chain into another file.
Body `locals` retains statement-position references and IDs; `references` retains
name occurrences. These are indexes, not copies of declarations or another symbol
namespace. Preparation's `visible_locals` is its source-order visibility index:
collecting a declaration does not make a forward variable reference legal.

## One collection and publication worker

`Scope_Collection` temporarily inventories declarations by object identity. This
inventory contains many valid distinct declarations; there is no per-name candidate list.

- `declare(node)` / `import(node)` register references without mutating live links.
- `publish_references()` installs first references for a non-owning file index.
- `publish(previous?)` compares old chains, clears retired links, rebuilds declaring
  scope chains, and updates stable entries. Missing keys keep removed entries;
  reappearance reactivates them. No live link changes before publication.

Body collection publishes local-variable chains. The parser publishes only file
references and retains syntax order independently. File-only duplicate diagnostics
use that inventory without claiming ownership of public function chains.
`Symbol_Publication` checks discoveries against completed files and sorts references
by source path/position. Only after every source and IO check succeeds does it
publish public function chains. The private implicit main remains file-only.

Old sessions retain syntax and tokens, not historical duplicate-link snapshots.
On incremental publication, reused nodes may acquire different successors. File
traversal remains valid because it uses its own syntax inventory. A failed parse or
completeness check cannot change published links. Native readers must finish before
relinking; PHP remains sequential.

The function dependency role takes a temporary per-key signature/ambiguity snapshot
before relinking, then notifies affected observers afterward. Generic symbol status
compares the whole chain identity, so changing only a duplicate tail is still a change.

See [singular relationships and erroneous duplicates](singular_duplicates.md) for the
project-wide rule and follow-up inventory.

## Compilation state stays outside scopes

`compilation_session.scope` replaces `symbols`. The session also owns
`dependency_watches` and `is_current`. Success shares the stable scope and
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
