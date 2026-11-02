# Incremental frontend generations

Doc Status: supporting

This slice ends after tokenization, parsing/local collection and global symbol
indexing. It does not perform semantic preparation, lowering or native emission.

```php
$compiler = new Project_Compiler();
$current = $compiler->frontend($application_directory);
$current = $compiler->frontend($application_directory, $current);
$declaration = $current->symbols->resolve('func:helper');
```

An optional third argument identifies the output directory to exclude during
source discovery. Dependency modules retain the existing metadata-only boundary.
The [app-local frontend service](frontend_service.md) retains this session across
separate CLI invocations. The session must stay in memory to reuse PHP objects; persisted build metadata
cannot restore token or AST identities across separate processes.

## One frontend path

`Incremental_Frontend::collect($candidate, $previous)` owns the transition. Module
loading creates fresh candidate source records. With a null previous session,
every source is added and receives a complete frontend pass. Otherwise:

| Status | Action |
| --- | --- |
| added | Read full content, tokenize fully, parse and collect declarations. |
| changed | Same full frontend pass; the previous session retains the old root. |
| none | Assign the exact previous AST root reference; its tokens/content are already retained. No content read or parsing. |
| removed | Exclude from active membership and retain a removed source record referencing the old AST. |

Unchanged means the same physical source path, module name/type, flavor, mtime and
size. The existing second-resolution mtime/size limitation remains. Passing null
requests a clean/full frontend generation through the same method.

`compilation_session.previous` retains the prior generation. `removed_sources`
contains removed records without modifying previous source statuses. No tokens,
content buffers or AST nodes are copied. `symbols` remains null until every file
has completed and the final source metadata check passes. A thrown parse/IO error
leaves the prior session/index intact; the caller retries against that publication.
Duplicate declarations are retained for analysis and do not abort frontend publication.

Each published session has its own index; unchanged ASTs may be referenced by several
sessions. Session history is retained while those links remain; automatic retirement
and native lifetime/concurrency mechanisms are deferred. This is not proof of native
thread safety or a promise of bounded retained history.

## String handles and duplicates

`global_symbols.declarations` maps canonical keys such as `func:helper` to declaration
candidates. `resolve($key)` returns one declaration, null for absence, or throws for
ambiguity. Currently only functions are supported; class/namespace features are not
introduced. Function declarations and call statements provide `symbol_key()`.
There are no numeric IDs, slot objects, stored slot keys or stored symbol statuses.

Clients resolve a retained string key through the current session's index. Moving
or replacing a declaration changes that lookup's destination; deleting it makes the
lookup unresolved; renaming creates a different key. Older session indexes remain
historical publications. A direct declaration reference is not a stable cross-generation
handle and must not be cached as one.

The declaration trait's protected link and getter are named `duplicate_element`;
`link_duplicate()` is used during parsing/local collection. Those chains describe
one source scope only. Global candidate lists remain separate so publishing symbols
never mutates duplicate chains in reused ASTs. Index rebuilding visits declarations,
not executable statement trees.

## Preparation boundary

The executable `project`/`program` paths continue using full semantic compilation.
They do not prepare reused frontend ASTs. `Program_Frontend` rejects attempts to
prepare a published incremental frontend session in place. Prepared call/type facts
still use their existing representation; migrating them to key-based resolution,
semantic invalidation and incremental lowering are later slices. The shared AST
must remain read-only until an explicit analysis ownership policy is implemented.
Do not bypass this boundary by invoking low-level preparation on shared roots.

Checks: `php v0.2/compiler/tests/incremental_frontend.php` with `XDEBUG_MODE=off`.
Tests cover all flavors, actual source-read counts and object identity, changes,
moves, removals/reappearance, duplicates and failed-candidate retry.
