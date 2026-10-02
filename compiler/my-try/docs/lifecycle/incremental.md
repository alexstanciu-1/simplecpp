# Incremental lifecycle
Doc Status: supporting

Fresh and incremental builds use the same stages. `sync(paths)` scans modules,
marks explicit notifications, tokenizes selected files, parses/collects, then cleans
retired symbols after the successful join. `exec_cpp`/`update_cpp` add preparation
and C++ generation; LLVM uses the same frontend followed by its parked preparation.
See the [call map](../../compiler/calls.md) and [work queue](work_queue.md).

The agreed post-collection concurrency boundaries and shared-write inventory are
recorded in the [multithreading plan](../planning/multithreading.md).
That discussion does not change the current execution model.

## Modules and files

`init_modules(Storage<module_input>)` validates complete module configuration before
mutation. Omitted names use the exact declared path. Modules retain declared/resolved
paths, input position, shared `change_state` and last-seen uint32 revision.

Initialization matches existing records by key, stamps presence, then marks unseen
records deleted. Any membership/path/order change resets compilation roots and
rebuilds the keyed module list in input order, with tombstones afterward. Identical
input retains the existing list. Overlapping canonical roots reject. Revision rollover
clears presence markers and restarts at one; there is no generic synchronization layer.

A full reset drops source/scope/preparation/output roots rather than traversing AST
facts. It first severs retained dependency registrations. Discovery failure blocks
frontend work and leaves a retry obligation. Invalid configuration preserves the
previous session. `Compiler_Lifecycle::reset()` also drops module identities.

Recursive scans work within module/folder context, skip directory symlinks and
update each module's existing source index directly. Keys and stored file paths are
module-relative. Only additions allocate source records. Changed mtime/size, pending
state or explicit notification selects reading; notifications force rereads even
when metadata matches. No global file index or folder-record hierarchy is retained.
Only a successful whole-module scan marks unseen files deleted. A failed scan cannot
delete unvisited entries. In-memory modules use explicit input/notifications.

## Token storage and retained parsing

Reading/tokenization is private until success. New tokens/text append to the retained
buffer; byte offsets gain the text-prefix offset. `first_token`, exclusive `end_token`
and `content_offset` identify the new input. Unchanged nodes keep their old indexes
and facts. Saved symbol names never depend on those positions.

Parser matches and updates existing declaration nodes/collected identities, allocating
only new declarations. Duplicate names consume identities in encounter order. Body
comparison uses exact source bytes between its first and last tokens: whitespace
changes matter. Unchanged bodies retain syntax/scopes/occurrences; changed bodies
are replaced. Signatures and file executable syntax use token comparison.

Parser invokes the collector immediately. Local scopes are worker-private; global
function/type registration uses the task publication mutex. After joining, revision
sweeps mark missing declarations. Failed files retain incomplete mutable state and
are not swept as completed files; preparation is blocked. No separate collection or
resolution pass runs during parsing. Resolution belongs to shared preparation.

## Preparation and deletion

[Preparation](../../04_analyze/prepare/README.md) schedules declaration work before
separate function/file body lists. Pending work is identity-deduplicated. Unchanged
ready facts remain attached; effective signature/layout and lookup changes notify
consumers, while body-only changes leave callers alone.

Deletion marks the retiring batch, notifies consumers through intact reverse links,
then unlinks dependencies/observations and removes collected rows and scope indexes.
Consumers need to be scheduled before removal, not already rebuilt. This boundary
also precedes LLVM lookup without preparing LLVM-only forms through S2S algorithms.
Deleted source/module records remain available for reconciliation/reappearance.

Preparation owners retain pending change/error state until success. Expected source
errors propagate pending/failed state; independent work continues. The next increment
retries even without source edits. Failed prerequisite facts are unavailable, and
recovery notifies consumers even if the recovered signature matches its old value.
Signature and body failures remain independent. Unexpected exceptions escaping the
worker request a full rebuild on the next attempt. No rollback is promised.

## Generation and cleanup

C++ selects dirty/outdated retained fragments and reuses unchanged text/includes.
Assembly still produces one `main.cpp`; see the [model](../architecture/MODEL.md#retained-c-generation).
A failed stage cannot expose completed output from the previous run. Generation work
settles and consumes preparation changes only after successful assembly.

`cleanup_tokens()` may run after output delivery. Specialized maintenance traversal
remaps live syntax, template/operator token sites and occurrences to the newest input,
then releases the obsolete buffer prefix. Facts and cached text survive. If the host
does not call it, the next tokenization performs synchronous cleanup first.

Incomplete parses, unparsed published input and outstanding deleted occurrences can
prevent cleanup. Repeated failed increments may retain several inputs; full reset
releases them. Unexpected cleanup failures request a full rebuild. Inspection handles
must not be traversed concurrently with mutation/cleanup. No background scheduler or
constant-time destruction guarantee is implied.

## Evidence and remaining work

Focused tests: `module_sync`, `file_scan`, `token_generations`, `token_cleanup`,
`parse_collection`, `combined_sync`, `incremental_preparation`, `preparation_recovery`,
`dependency_cleanup` and `incremental_cpp` under `tests/`. LLVM frontend compatibility
uses `incremental` and `incremental_smoke --restore`.

These tests are not exhaustive incremental/native proof. Accepted limitations and
future changes live in the [debt register](../planning/incremental_strategy.md).
