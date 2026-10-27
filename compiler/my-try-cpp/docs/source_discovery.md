# Source discovery and retained inventory
Doc Status: supporting

These stages discover and load source files. [Tokenization](tokenization.md) is now
the next implemented stage. Parsing, semantic preparation and C++ generation remain
outside this implementation. Project manifests, watchers, configurable
globs, content hashing and tombstone compaction remain outside this slice.

## Invocation

From the repository root:

```sh
compiler/my-try-cpp/build/compiler/my-try-cpp \
  --catalogs runtime/metadata/modules \
  --source /path/to/project/src --source /path/to/library/src --jobs 8 --timings
```

`--source` is repeatable. Omitting it retains catalog-only startup. Roots must be
existing, canonicalizable, disjoint directories; duplicates and parent/child
ownership overlaps reject. Source-root order defines source module IDs for this
configuration. Changing roots requires an explicit lifecycle reset. These source
roots are distinct from the runtime catalog selection (`--module`). The existing
startup job default remains 12; `--jobs 8` sets discovery's worker budget to eight.
Ninja's default remains eight independently.

Only the case-sensitive `.phs` suffix is selected. Directory names `.git`, `.hg`,
`.svn` and `.prism` are skipped at every depth. File and directory symlink entries
are skipped; an explicitly supplied symlink root is rejected before canonicalization.
Canonicalization may resolve parent path aliases. This is a filesystem selection
policy, not a security sandbox or an atomic filesystem snapshot.

## Ownership and concurrency

- `src/sources/inventory.*` owns source selection policy and change transitions.
- `src/startup/configuration.*` owns the requested source roots.
- `Model` owns the static retained inventory; `Compiler_Lifecycle` initializes,
  rescans, accepts explicit notifications/acknowledgements, and resets it.
- Middleware exposes concrete `file_records` storage and `fs_discover`; templates,
  runtime filesystem calls and runtime task scheduling stay behind that boundary.
- The runtime's existing task implementation has an explicit `scpp_tasks` CMake
  target. The compiler's runtime package includes that library. Normal compiler
  builds still do not rebuild runtime or middleware.

Traversal uses directory frontiers. Runtime workers process sibling directories
with a bounded job count; each returns private metadata observations. Serialized
publication combines results into a private candidate. The frontier is joined
before its discovered subdirectories run. This simple design permits overlap
within one root but leaves a single flat directory serial. Peak workers measures
simultaneously active directory callbacks, not CPU utilization.

After the whole traversal succeeds, observations are sorted by root ID then
relative-path bytes. Existing records keep both their numeric IDs and addresses;
new records append in deterministic order. No folder objects are retained in the
model. This explicit initial ordering is lexicographic rather than the old
scanner's depth-first traversal order. Later additions never reorder old IDs.

New membership is staged by copying storage handles/indexes only when additions
exist. All allocating lookups and additions finish before live metadata/status
updates. An unchanged scan does not replace retained records or their index,
although it still allocates a temporary observation set. A failed traversal or
staging operation cannot publish a partial inventory or infer deletions. Successful
publication and lifecycle operations are coordinator-only; readers must not overlap
mutation/reset, and borrowed references must not outlive reset.

## Change detection

A record stores root ID, relative path, mtime, byte size, status and a scan-visit
flag. The runtime currently reports mtime in seconds. Metadata-only detection can
miss same-size edits with unchanged mtime; this limitation is intentional here.
No content fingerprint or semantic-incremental guarantee is claimed.

- New records are `added`.
- Changed metadata makes an acknowledged record `changed`.
- `added`/`changed` stay pending across rescans until explicitly acknowledged.
- Unseen records become `deleted` only after a successful complete scan.
- A deleted path that reappears reuses its ID/address and becomes `added`.
- `Compiler_Lifecycle::notify_source(id)` marks an acknowledged record changed
  even when metadata matches. Notifications use known stable IDs in this slice;
  external path notification adapters/watchers are deferred. A future content
  consumer calls `load_sources()` for pending revisions; discovery itself reads no contents.
- `acknowledge_source(id)` clears a live added/changed flag after consumption.
  Unread current revisions cannot be acknowledged. Deleted records remain tombstones;
  there is no compiler consumer yet.

`Model::sources()` exposes read-only lookup, records and measurements. `size()`
includes tombstones; `measurements().files` counts files in the latest successful
scan. `scan_us` measures traversal/sorting; the profiling executable separately
times the full scan plus reconciliation.

## Discovery baseline before source reading (2026-10-26)

Eight focused tests pass. The discovery fixture keeps one compiler process alive
across unchanged scans, metadata edits, forced notifications, pending retries,
additions, deletions and reappearances. It verifies stable addresses, suffix and
symlink filtering, excluded directories, disjoint-root checks and failed scans.
The unreadable-directory regression runs when the process is non-root.

That regression found and fixed an existing runtime issue: `fs::scan()` failed to
check errors immediately after constructing its directory iterator. Permission
failure could therefore appear as an empty directory. It now reports both open
and increment failures through its result wrapper.

A Linux fixture with 2,048 one-byte files in 32 sibling directories (33 directories
including the root) observed peak concurrency 1 with one job and 8 with eight jobs.
One noninstrumented trial measured traversal/sorting at 37.583 ms versus 8.652 ms.
This is a local observation, not a general speedup guarantee.

The opt-in `source-discovery-profile` target measures full initial and unchanged
scans with thread-safe ordinary C++ new/new[] counters. Three fresh processes:

| Measurement | Initial | Unchanged rescan |
| --- | ---: | ---: |
| Files | 2,048 | 2,048 |
| Peak active workers | 8 | 8 |
| Allocation requests | 67,564 | 63,397 |
| Cumulative requested bytes | 7,344,912 | 6,907,481 |
| Full scan time, instrumented | 8.742–9.066 ms | 7.636–8.176 ms |

Whole-process peak RSS was 4,852–4,956 KiB. Requested bytes are cumulative, not
peak live memory. Counters omit direct malloc/aligned allocation and allocator
metadata; RSS includes runtime/process costs. This baseline makes the remaining
observation, path and filesystem-wrapper allocation churn visible. The unchanged
path preserves identities but is not an allocation-free or filesystem-free scan.

Build the profiler explicitly (excluded from normal builds/tests):

```sh
ninja -C compiler/my-try-cpp/build/compiler -j8 source-discovery-profile
/usr/bin/time -f 'peak_rss_kib=%M' \
  compiler/my-try-cpp/build/compiler/source-discovery-profile /path/to/source-root
```

All eight tests also pass under the separate ASan/UBSan configuration with leak
detection, including threaded discovery and failure recovery. No sanitizer reports
were produced. This is not ThreadSanitizer evidence or proof of general race freedom.

## Checked source loading

Initialization scans and loads sources before publishing the model. Later,
`rescan_sources()` and `load_sources()` are separate coordinator operations.
Each file owns a `token_buffer`, source revision and loaded revision. Metadata
changes, reappearance and explicit notifications advance the source revision,
even when a prior change remains pending. Notifications for deleted records do
nothing; rescan discovers reappearance.

The buffer owns immutable runtime string storage. Copies share the bytes and
remain valid after replacement or reset. An absent buffer differs from a loaded
empty file. Byte access is bounds checked. Reading itself produces no token structures; the separate tokenization stage owns
compact tokens and retains their source snapshots.

Loading selects live revisions not already loaded and runs bounded runtime tasks
over files, including files in flat directories. Workers call the existing runtime
checked snapshot reader with discovered mtime and size. It checks file identity
and metadata around the read and rejects detected changes. This is not an atomic
filesystem snapshot: equal-metadata edits still require notification.

Workers write private candidates. Only a successful joined batch publishes buffers
and loaded revisions. Failure preserves every previous buffer, loaded revision and
pending status. Rescan before retry when metadata changed. Reading never clears
pending flags. Deleted entries retain their prior buffer but are not read.
Lifecycle mutation remains coordinator-only and cannot overlap readers.

`reads()` reports the last successful batch: files, bytes, reused live buffers,
peak simultaneous callbacks and read-batch microseconds. CLI source counters are
separate from catalog reads. Read timing includes scheduling and joining, but
excludes request construction and publication. The profiler measures the complete
load operation.

## Read verification and measurements (2026-10-26)

All nine focused tests pass normally and under separate ASan/UBSan with leak
detection. They cover repeated invalidation, loaded pending-buffer reuse, empty
files, embedded NULs, bounds, old-generation lifetimes, deletion/reappearance,
metadata races and all-or-none batch rollback. Normal dependency packages were
unchanged by sanitizer builds. This does not establish general race freedom.

The opt-in `source-reading-profile` uses ordinary-new counters. With 2,048 files
of 1 KiB across 32 directories, three fresh processes measured:

| Measurement | Initial load | Reuse loaded, still pending |
| --- | ---: | ---: |
| Peak active workers | 8 | 0 |
| Allocation requests | 30,775 | 0 |
| Cumulative requested bytes | 3,127,453 | 0 |
| Full load time | 6.946–10.846 ms | 34–41 microseconds |

Whole-process peak RSS was 7,040–7,296 KiB including discovery and retained text.
These are local warm-filesystem observations; initial timings overlapped other
validation work. Counters omit direct malloc/aligned allocations. Discovery
still incurs separate costs even when subsequent loading reuses every buffer.

Build explicitly with
`ninja -C compiler/my-try-cpp/build/compiler -j8 source-reading-profile`, then run
`compiler/my-try-cpp/build/compiler/source-reading-profile SOURCE_ROOT`.
