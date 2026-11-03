# Incremental frontend generations

Doc Status: supporting

This slice ends after tokenization, parsing/local collection and global symbol
indexing. It does not perform semantic preparation, lowering or native emission.

```php
$compiler = new Project_Compiler();
$current = $compiler->frontend($application_directory);
$current = $compiler->frontend($application_directory, $current);
$declaration = $current->scope->resolve('func:helper');
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
content buffers or AST nodes are copied. `scope` remains null until every file
has completed and the final source metadata check passes. A thrown parse/IO error
leaves the current session/index intact; the caller retries against that publication.
Duplicate declarations are retained for analysis and do not abort frontend publication.

Each published session has its own index; unchanged ASTs may be referenced by several
sessions. Session history is retained while those links remain; automatic retirement
and native lifetime/concurrency mechanisms are deferred. This is not proof of native
thread safety or a promise of bounded retained history.

## Stable symbols and duplicates

Canonical keys such as `func:helper` now identify stable `symbol_entry` objects.
`resolve($key)` still returns the current declaration, null for absence, or throws
for ambiguity. Removed entries remain and are reactivated if the key reappears.
Previous sessions share the current symbol publication; old AST syntax remains
retained, but symbol lookup is no longer historical. Only the current session may
be used as an incremental baseline or prepared against this live index.

File-local `duplicate_element` chains remain unchanged. Global candidate indexes
reference AST declarations without mutating those chains. See
[stable symbols and preparation caches](stable_symbols.md) for structure, status,
transactional publication, observer ownership and signature comparison rules.

## Preparation boundary

The executable `project`/`program` paths continue using full semantic compilation.
`Program_Frontend` rejects preparing a published incremental frontend session through
that full-build path. `Incremental_Preparation::prepare()` is the explicit in-process
coordinator for incremental body-cache reconstruction. It returns counts/diagnostics,
not an emission handle; incremental lowering remains deferred. The frontend service
still performs only frontend work, including invalidation if observers exist.

Unchanged syntax is shared. Declaration facts and body caches are current analysis
state, not historical snapshots; publication can mark dependent bodies dirty.
There must be no concurrent preparation/readers across that transition in this PHP
implementation. See the stable-symbol guide before accessing retained facts.

Checks: `php v0.2/compiler/tests/incremental_frontend.php` and
`php v0.2/compiler/tests/incremental_preparation.php`.
