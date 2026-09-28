# File synchronization
Doc Status: supporting

The combined and standalone frontend entrypoints now share the incremental phases.
See [Incremental compiler strategy](../planning/incremental_strategy.md) for decisions
and remaining error-recovery and ownership debts.

Compiler.init(paths) reconciles the complete ordered module configuration and discovers
files when that configuration changes. Identical configuration retains compilation data.
Compiler.sync(paths) scans configured filesystem modules, then reads/parses only new,
changed, pending or explicitly notified files. Compiler.exec_cpp/exec_llvm use that
same scan. C++ selects affected preparation work; parked LLVM still prepares its
whole live program. Both regenerate backend output. update_cpp/update_llvm
also accept explicit notifications that force rereading even with equal metadata.
No watcher or background loop is introduced. In-memory test modules are not scanned;
their explicit execution/notifications supply input through the existing pipeline.

Changes to module membership/configuration require init(complete module paths).
Any change, including order, retires the compilation graph and rediscovers every
active module. The next sync/update includes all discovered sources as well as any
explicit notifications; it clears that full-sync obligation only on success. Unknown-module file notifications are rejected rather
than silently extending module membership. Module roots use canonical filesystem paths and reject overlap. Notifications
normalize lexical path components (including missing deletion paths) before the
owning-module lookup followed by its relative-path index.

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
clears retained module and source markers and restarts at one.

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
successful initialization retains data; the next sync/exec/update performs file scanning.

Proof: `tests/module_sync.php` covers identity, both paths, named path changes,
canonical-root changes, ordering, deletion/reappearance, invalid configuration,
full-sync retry and discovery failure/recovery (permission case on non-root hosts).

## Direct file scanning

Each module owns one `Keyed_Storage<source_record>` keyed by its normalized relative
path, including subfolders. Both source_record.path and file.path stay relative.
There is no global absolute-path file index and no retained folder collection.
Same-named files in different modules are distinct. External notifications select
the owning module, then its relative key. Filesystem reads receive a temporary full
path constructed from the module root; Tokenizer passes that path to File_Loader
without storing it on the file snapshot.

Module_Loader traverses module/folder context and updates existing entries directly.
Only additions allocate source records. It stamps last-seen revisions and compares
mtime/size with the last published file metadata. source_record.changes records
pending work using the shared change_state enum. Successful parse publication clears
live pending state; failures leave it set so matching metadata cannot suppress retry.
The scan itself does not change published bytes, metadata, tokens or ASTs.

Only a successful whole-module scan marks unseen entries deleted. A failed scan
leaves unvisited entries alone and aborts synchronization before frontend publication.
Deletion publication retires the old declarations; reappearance reuses the source
record. File collection order requires no reconciliation. Unchanged files retain
published snapshots. A changed file replaces tokens, reuses declaration identities
and unchanged bodies, and replaces changed bodies.

Accepted debt: mtime plus size misses equal-size edits with unchanged timestamps.
Explicit notifications force rereading. Stronger content-based detection is deferred.
Proof: `tests/file_scan.php` covers module-local identity, relative nested paths,
metadata changes, unchanged reuse, forced reads, deletion/reappearance, revision
rollover and failure/retry. Permission-based traversal failures run on non-root hosts.

## Retained parsing and preparation

`sync(paths)` scans filesystem modules, applies explicit notifications directly to
source records, calls `tokenize()`, then `parse()`. Each phase joins before the next
starts. Notifications force reads even with equal metadata; duplicate notifications
only mark the record again and do not create duplicate work. In-memory inputs use
explicit notifications. New files start pending, including after a full module reset.

The tokenizer publishes a new token generation only on successful reading/scanning.
Parsing updates the existing file, declarations and collected symbols in place.
Unchanged bodies retain syntax, scopes and occurrences with rebased token positions;
changed bodies are replaced. Signatures and bodies have separate change state.
Duplicate declaration names consume old identities in encounter order; there is no
second whole-file comparison or candidate-publication algorithm.

Parser calls collector methods immediately. Local indexes are worker-private; new
global function/type entries register under `task_synchronize`. After joining,
revision sweeps mark missing declarations. Failed files retain partial mutable state;
they are not swept as completed files. Preparation does not start after such errors.

## Deletion and consumers

After a successful combined join, the shared preparation worker's cleanup boundary
notifies dependents and removes deleted collected rows and scope/type memberships.
This cleanup also precedes parked LLVM resolution; it does not run shared S2S
semantic preparation on LLVM-only forms. Standalone `prepare()` uses the same cleanup.
Source records remain marked deleted for scanning/reappearance. Reappearing files
start new declarations in the retained source record, without stale scope entries.

C++ preparation uses separate declaration, function-body and file-body identity sets.
No-op runs retain facts, implementation-only edits leave callers alone, and effective
signature/layout changes notify consumers. Emission still generates complete output.
`sync` clears completion/output roots before work without clearing retained facts;
failed updates therefore cannot expose stale completed results. Preparation owners
retain change/error state until success. Failures propagate to consumers; independent
work may settle. Later increments retry pending owners even without source edits,
and signature recovery notifies consumers even when its facts match the previous value.
Body errors leave valid signatures independent. Required failed declaration facts are
unavailable; no rollback of mutable partial facts is promised. Unexpected exceptions escaping preparation force a full rebuild on the next attempt.
Distinguishing internal `RuntimeException` bugs from source diagnostics remains debt. `tests/preparation_recovery.php` covers this
policy and exact source-text body comparison, including whitespace and moved bodies.

Proofs: `tests/combined_sync.php` checks no-op identity, body reuse, signature
invalidation, fresh/incremental C++ equivalence and failure/retry.
`tests/incremental.php` and `tests/incremental_smoke.php --restore` retain LLVM
regression coverage using the same frontend path. LLVM's scope adapter accounts for
the separate executable/signature scopes; its semantic redesign remains deferred.
