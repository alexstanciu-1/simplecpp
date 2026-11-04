# Stable symbols and preparation caches

Doc Status: supporting

Function and explicit-global identities use the same general watch coordinator. This does not implement
incremental lowering, executable emission or native concurrent publication.

## Structure and ownership

```text
compilation_session
├── scope: ?scope — current compilation declaration scope
├── dependency_watches: Dependency_Watches — shared across frontend generations
└── is_current: bool — prior session retires after successful publication

scope
├── parent: ?scope — lexical parent, null for compilation or detached file
├── declarations: array<string,ast_declaration_interface> — current head references
└── entries: array<string,symbol_entry> — stable identities, including tombstones

symbol_entry
├── status: change_status
└── head: ?ast_declaration_interface — null means removed; duplicate_element links errors

ast_call_statement → value: ast_call_expression
ast_call_expression
├── name: string — authored syntax
└── function_symbol: ?symbol_entry — prepared cache reference

Dependency_Watches
├── observers: symbol_entry → set<dependency_watcher>
├── targets: dependency_watcher → set<symbol_entry> — reverse index for cleanup
└── pending: set<dependency_watcher> — one notification per publication batch

ast_function_body implements dependency_watcher
└── invalidate_dependencies(): void — mark owned preparation cache dirty
```

No names, signatures, bodies or literal facts are copied into symbol entries.
The canonical map key supplies identity. An entry holds one head reference. The
compilation scope owns duplicate links for public functions and globals; body scopes own local
variable duplicate links. File indexes only reference declarations. A head without
a successor resolves to its AST; null means missing; a successor means ambiguity.
File syntax inventories retain every node independently of these live links.

A function call caches a stable entry. `function_def()` remains the consumer API,
but now reads the current declaration through that entry and rejects a dirty body.
Normal full compilation uses the same entries and the same preparation worker.
The previous separate bare-name callable map is gone. Collection and publication
now use the [common scope mechanism](scopes.md).

## Watch registration and dispatch

The session lineage owns `Dependency_Watches` in `compiler/dependencies.php`.
It replaces the function-specific dependency class; it does not wrap one.
Preparation calls `watch($symbol, $watcher)` when it consumes a symbol. The
coordinator deduplicates identity pairs and owns reverse subscriptions and pending
notifications. A symbol is currently a stable `symbol_entry`, including tombstones.

`Symbol_Publication` owns declaration-contract snapshots/comparison and supplies the
complete live watcher inventory. The dependency coordinator has no function-name,
signature, source traversal or body-preparation knowledge.

Global reads use the same body watchers, observing existence/ambiguity and storage
type. Their initializer values are not constant facts in readers, so initializer-only
edits can reuse readers while stable entries select replacement declarations. See
[global preparation and lowering](globals.md).

- `watch(symbol, watcher)` registers a relation; repeated registration is harmless.
- `changed(symbol)` queues its observers without invoking them.
- `dispatch()` ends the batch and calls `invalidate_dependencies()` once per queued
  watcher, even if several watched symbols changed. An empty dispatch does nothing.
- `forget(watcher)` removes both directions and cancels its pending notification.
- `retain(watchers)` removes subscriptions for absent watchers and queues their
  final invalidation; callers must keep retired targets alive through dispatch.

Publication retains current watchers, queues semantic changes, then dispatches
before returning. Watcher callbacks only invalidate owned caches: they must not
throw, prepare code or mutate subscriptions. This remains sequential PHP work;
native publication will require the existing reader/worker barrier.

The interface does not prescribe watcher granularity. Bodies implement it now;
groups of bodies or whole source units may implement it later. Subscription cleanup
must use that same ownership granularity: rebuilding one member must not forget
relations still needed by other members. No grouping heuristic or per-node observer
graph is introduced. Specialized methods such as `watch_function()` may later
optimize the same coordinator; do not restore a class per symbol kind.

The [namespace/import slice](namespaces.md) resolves each call to one exact qualified
key, with file-local imports and no global/parent fallback. Watching that stable entry,
including a missing entry, is sufficient for callable facts. Import validation also
runs on every preparation pass to catch unused target removal and new conflicts.
No namespace-wide observers are needed. Future lookup alternatives must account for
changes that can redirect a lookup without changing its former selected symbol.

Every derived fact needs a dependency explanation, not necessarily a separately
allocated edge. Body-owned facts are invalidated together; only dependencies crossing
that cache boundary currently need explicit watches. The historical 90%+ preparation
measurement belongs to `my-try`, not this compiler. Future grouping/performance
choices need current measurements.

## Publication and changes

The frontend collects candidate ASTs and completes source checks before touching
live entries. A parse/read failure leaves current entries, statuses and body caches
unchanged. Successful publication updates the existing global index and entries,
removes obsolete observer-body subscriptions, then dispatches batched invalidation notifications.
No observer callback performs preparation during publication.

- Same key and identical ordered chain of declaration objects: `none`.
- Same key and replacement declaration or changed duplicate chain: `changed` (even if only trivia changed).
- Missing key: retain the entry with a null head and status `removed`.
- Reintroduced key: reactivate that exact entry and mark `added`.
- Rename: old key removed, new key added.
- Move between files: same key and entry, with a new declaration destination.
- Initially unresolved calls can subscribe to a removed entry before it ever exists.

A symbol change does not necessarily invalidate its observers. Compare candidate
availability/ambiguity and the signature contract: ordered written parameter types
(including count) and written return type. Parameter names do not affect positional
callers. A new AST object with the same unambiguous signature does not invalidate
callers just because its body changed. This transient signature snapshot copies
no AST nodes and does not introduce a retained prepared-signature object.

Older sessions retain old syntax but share the live symbol index. Their duplicate links are live publication data, not historical snapshots. They are not
historical symbol snapshots, and their shared prepared caches can become dirty.
`compilation_session.is_current` prevents preparing or branching from an old
session against a newer publication. Retirement happens only after successful
publication; scope objects have no session backlink. To start an independent clean lineage, pass null as before.
Tombstones remain for the lifetime of the lineage; automatic symbol reclamation
is intentionally deferred. Native readers must join before publication; the PHP
implementation is sequential and is not proof of lock-free native safety.

## Cache invalidation and rebuilding

A caller observes only a callee's existence/signature. It does not observe its
implementation. Removal, reintroduction, ambiguity changes and signature changes
mark the observing whole body unprepared and invalidate its root's preparation
revision. Facts remain on their AST nodes until reconstruction; completion guards
prevent consuming dirty bodies. Notifications neither traverse bodies nor recursively
invalidate callers of callers whose own signature has not changed.

Before preparing a body, remove its previous subscriptions. Each call lookup then
records a dependency, including a missing lookup before reporting its diagnostic.
A failed body stays unprepared and is retried; removed symbols keep their live
observers so reintroduction can notify them. Replaced/removed observing bodies are marked unprepared and unsubscribed
at frontend publication; they no longer receive notifications.

`Incremental_Preparation::prepare()` coordinates reconstruction after publication:

1. Check admission and settle all supported declaration signatures.
2. Reuse prepared bodies whose preparation context still matches.
3. Reprepare dirty/new bodies with the existing `Preparation` worker.
4. Return prepared/reused body counts, or collected body diagnostics.

The implicit entry also caches its native exit-target width; a target change
reprepares the entry. There is no second prepared statement list. The body retains
its existing completion flag/statistics plus that small context fact. Successful
unaffected body caches may survive a semantic error elsewhere; semantic failure
does not roll back the already successful frontend publication.

```php
$compiler = new Project_Compiler();
$session = $compiler->frontend($app);
$report = Incremental_Preparation::prepare($session, new entry_target(31));
// Edit files externally.
$session = $compiler->frontend($app, $session);
$report = Incremental_Preparation::prepare($session, new entry_target(31));
```

This API intentionally returns no emission handle and does not mark a whole
incremental file/program ready for lowering. The app-local `frontend` service stays
scoped to tokenization, parsing and symbol publication. Incremental preparation is
an in-process API in this slice. The normal executable build still prepares and
emits its whole program.

## Verification

`php v0.2/compiler/tests/incremental_preparation.php` checks stable identity,
unchanged caches, body-only edits, removal/reintroduction, rename, signature changes,
unresolved-name repair, ambiguity/recovery, replaced/removed subscriptions,
entry-target changes, stale-session rejection and failed-parse preservation.
All three flavors are exercised within their currently supported grammar.
Existing frontend/service/native-function tests cover the surrounding paths.

`php v0.2/compiler/tests/dependency_watches.php` checks generic non-AST watchers,
registration and dispatch deduplication, retirement, pending cancellation, repeated
forget, re-registration and independent-coordinator isolation.
