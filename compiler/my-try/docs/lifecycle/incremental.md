# File synchronization
Doc Status: supporting

This document describes current behavior. The proposed declaration/body incremental
strategy is being discussed in [Incremental compiler strategy](../planning/incremental_strategy.md);
its identity and dependency rules are not implemented yet.

Compiler.init(paths) reconciles the complete ordered module configuration and discovers
files when that configuration changes. Identical configuration retains compilation data. Compiler.exec_llvm()
submits every live file to Compiler.sync(paths), then invokes the existing full
preparation/generation path. Compiler.update_llvm(paths) submits only notified files and
then runs that same full preparation. Compiler.sync(paths) publishes source changes
without invoking the backend, so duplicate declarations can be retained for later
validation. No watcher or background process loop is introduced: the caller keeps
its Compiler instance alive and supplies notifications.

Changes to module membership/configuration require init(complete module paths).
Any change, including order, retires the compilation graph and rediscovers every
active module. The next sync/update includes all discovered sources as well as any
explicit notifications; it clears that full-sync obligation only on success. Unknown-module file notifications are rejected rather
than silently extending module membership. Module roots use canonical filesystem paths and reject overlap. Notifications
normalize lexical path components (including missing deletion paths) before the
unique source-path lookup.

## Module reconciliation and full reset

`init_modules(Storage<module_input>)` accepts explicit names and returns whether a
rebuild/discovery was required. `init(vector<string>)` is the unnamed adapter.
`module_input(path, name)` uses the exact declared path as its name when the name is
omitted. A module retains `name`, `declared_path`, `resolved_path`, `position`, a shared `change_state` (`unchanged`, `added`, `changed`, `deleted`),
and an inline `uint32` revision for run presence. A changed position triggers a full rebuild.

`Model::$modules` is one `Keyed_Storage<module>`, keyed by name. Live modules occur
in input order and deleted records follow them. Consumers skip `change_state::deleted`.
Removed modules retain identity; reappearance under the same key reuses that identity
as added. Renaming is delete/add. Changing a named module's path preserves identity.

`Module_Loader::configuration()` validates all incoming roots and keys before mutation.
`Compiler::init_modules()` directly loops that input, matches retained records by key,
compares paths and position, and stamps their last-seen revision. A second loop marks
missing records deleted. There is no module collection wrapper or synchronization
worker/helper class. `Model::$revision` is the general uint32 reconciliation counter;
modules retain their last-seen uint32 revision inline. Before rollover, initialization
clears retained module markers and restarts at one. Future participating record kinds
must join that reset when their incremental slices are implemented.

When configuration changes, initialization builds a replacement keyed collection in
input order using the retained objects, then appends tombstones. Only that collection
is retained; unchanged input keeps the existing collection object. PHP and the native
compiler collection both preserve insertion order, so no sorting API or separate
active-order store is necessary. Matching is expected linear in input plus retained
records; the small-module overlap validation remains pairwise.

A change calls `Compiler_Lifecycle::reset_compilation()`: replace source, scope,
preparation and output roots and empty module source stores. It does not walk discarded
ASTs merely to clear their facts. External handles to retired results are not current
compiler data; releasing roots does not promise constant-time memory reclamation.
Partial stage resets still clean retained syntax. `Compiler_Lifecycle::reset()` starts
an explicitly fresh session, dropping module identities and tombstones too.

Invalid configuration preserves the prior session. Failure during discovery clears
partial source data and blocks frontend work; identical initialization can retry.
Failed frontend synchronization leaves the full-sync obligation pending. Identical
successful initialization does not rescan for file additions: use file notifications
or an explicit fresh reset until the file-discovery slice is implemented.

Proof: `tests/module_sync.php` covers identity, both paths, named path changes,
canonical-root changes, ordering, deletion/reappearance, invalid configuration,
full-sync retry and discovery failure/recovery (permission case on non-root hosts).

## Stable sources and temporary comparisons

The current snapshots expose int changes on file and collected_name. Constants
are SYNC_ADDED=1, SYNC_CHANGED=2, SYNC_BODY_CHANGED=4 and SYNC_DELETED=8. Zero means
unchanged. Declaration and body changes can combine (6); added/deleted are exclusive
states. Each update clears transient flags on files and declaration inventory rows;
ordinary use/reference rows need no reset. Retained tombstones keep DELETED.
Temporary declaration_comparison records cache keys and spellings once per update,
grouped through Key_Storage_List. There are no persistent declaration IDs or
historical version maps. Only live previous declarations participate in matching.

Module-owned source_record identity survives updates and deletion. Private file
snapshots are allocated for workers. Reading, tokenization and parsing
never mutate published source bytes. A completed candidate is compared and installed
under the existing publication lock. Tokens, ASTs and local scopes are replaced as
a unit. Unchanged files retain their syntax; matched declarations use new occurrence
records pointing into the new syntax. Global indexes replace obsolete live references by previous collection identity. Declaration object identity is not promised across updates.

The existing collected_file inventory includes struct fields as declaration rows,
but those fields are not exported as global variables. Logical matching uses node
kind, name and enclosing function/struct names in the current namespace-free subset.
Exact header/body matches are paired first inside duplicate groups. Remaining unique
pairs are compared; ambiguous leftovers become additions and deletions. Namespaces,
classes/methods/constants gain matching rules only when their syntax is implemented.

Comparison uses AST-selected token spans with length-framed token spellings. The
scanner already removes whitespace; token offsets do not participate. Comments are
not currently accepted source syntax. Function headers and bodies are compared
separately. Other declarations, including fields and variable initializers, compare
as a whole. File CHANGED includes source/location changes; its ordered top-level
statement AST is fully replaced, preserving initialization order. No location flag
or per-statement flags were added.

## Tombstones and consumers

Unmatched old declarations remain marked DELETED in collected_file.entries and its
defined_elements inventory. Their old provenance/local_index describes the retained
old collection, not their appended position in the current inventory. Consumers
must inspect changes before reading node/scope/provenance. Existing global buckets
retain removed root declarations. Scope_Lookup.live filters deleted candidates before
lookup or ambiguity counting; direct index access is an inventory, not a live lookup.
Deleted source files keep their last complete model rows marked on the source record.
The compiler passes only live collections to the backend. Declaration consumers and
host presentation skip deleted rows before dereferencing them. Storage iteration has
not changed.

A missing path that previously had published tokens is a deletion notification.
An unreadable/newly missing discovered input still fails loading; a disappearing file
after dispatch can also fail the read. Syntax failure retains the previous complete
file, clears generated output and raises the error. Earlier files in the batch may
already have published; update batches are not transactions. Do not generate stale
results after catching an update failure. Existing parser/backend diagnostics remain
fail-fast: preserving duplicate candidates does not itself implement multi-error STAN.

Resolution/preparation reruns for the complete live program. No cached resolved
bindings survive through this path, and no bidirectional use dependency graph or
selective semantic invalidation is added. Clang/LLVM behavior is unchanged.

## Deferred debt

- Physically remove deleted declarations/files and reclaim retained old syntax and
  source references. Memory can grow over a long session; tombstones are not history.
- Consolidate cleanup after all consumers have observed flags. Transient flags are
  already reset before each update because stale flags would be incorrect.
- Replace remaining file lookups and declaration candidate scans with deliberate
  indexes if measurements justify them. Root ordering already uses a temporary
  source-identity index instead of scanning all parses for every source. This implementation prioritizes the agreed
  minimal record shape; there are no new persistent lookup properties.
- Track resolved type/use/call/return dependencies in both directions later. Until
  then full preparation is the correctness path.
- Add multi-error validation/recovery in the validation milestone, not by accepting
  partial malformed ASTs here.

PHP proof: tests/incremental.php. Native-driver preflight exercises additions, body
updates, unchanged caller reuse, duplicate retention/removal and deleted-file lookup.

## On-demand development check

Run the small smoke when editing code that could affect synchronization, collection,
publication, resolution or generation:

```sh
php compiler/my-try/tests/incremental_smoke.php
```

It performs one full PHP-hosted compilation and one incremental update in the same
session, checks changed output, flags, unchanged caller reuse and refreshed binding,
then restores the temporary source. It does not invoke Clang or rebuild the native
compiler. Working samples are never edited; cleanup runs in finally on failure too.
This smoke is deliberately not registered as another automatic full-suite step.

For the explicit restoration regression:

```sh
php compiler/my-try/tests/incremental_smoke.php --restore
```

This additionally compiles the restored file incrementally and compares all generated
filenames/text with both the initial full build and a fresh full compilation of the
restored source. It requires one extra incremental and one extra full compilation;
those are optional, not the normal development loop. The existing incremental.php
suite remains the broader multi-update/deletion/failure regression test.
