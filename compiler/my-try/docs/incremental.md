# File synchronization
Doc Status: supporting

Compiler.init(paths) starts an empty session and discovers module files. Compiler.exec()
submits every live file to Compiler.sync(paths), then invokes the existing full
preparation/generation path. Compiler.update(paths) submits only notified files and
then runs that same full preparation. Compiler.sync(paths) publishes source changes
without invoking the backend, so duplicate declarations can be retained for later
validation. No watcher or background process loop is introduced: the caller keeps
its Compiler instance alive and supplies notifications.

Changes to module membership/configuration require init(complete module paths) and
exec(), a full compilation. Unknown-module file notifications are rejected rather
than silently extending module membership. Paths use the same module/path spelling
as discovery; normalization and overlapping module aliases are not introduced here.

## Existing records only

The only added record property is int changes on file and collected_name. Constants
are SYNC_ADDED=1, SYNC_CHANGED=2, SYNC_BODY_CHANGED=4 and SYNC_DELETED=8. Zero means
unchanged. Declaration and body changes can combine (6); added/deleted are exclusive
states. Each update clears transient flags; retained tombstones keep DELETED.
There are no declaration-change records, persistent declaration IDs or historical
version mapping. Only live previous declarations participate in matching.

Private source records are allocated for workers. Reading, tokenization and parsing
never mutate published source bytes. A completed candidate is compared and installed
under the existing publication lock. Tokens, ASTs and local scopes are replaced as
a unit. Unchanged files retain their syntax; matched declarations use new occurrence
records pointing into the new syntax. Global indexes replace their obsolete live
references. Declaration object identity is not promised across updates.

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
- Replace repeated file/root scans and declaration candidate scans with deliberate
  indexes if measurements justify them. This implementation prioritizes the agreed
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
