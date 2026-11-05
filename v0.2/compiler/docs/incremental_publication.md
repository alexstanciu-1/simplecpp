# Changed-source publication

Doc Status: supporting

`Incremental_Frontend` supplies only new/reparsed roots and replaced/removed roots
to `Symbol_Publication::changes()`, after all parsing and source checks succeed.
A clean run uses the same path with every source added. Parse/read failures leave
the current publication and preparation queue intact.

The coordinator derives affected keys from those roots. `Scope_Collection` retains
surviving declarations from each affected chain, sorts that temporary inventory by
source path and token position, clears the old affected links, and publishes the
replacement chains. Unrelated chains and stable entries are not rebuilt. Removed
entries remain tombstones; reintroduction reuses the same entry. Only the compilation
scope owns cross-file `duplicate_element` links; source inventories remain references
to their own syntax. Global index iteration order is not a contract; duplicate-chain
order and diagnostic ordering remain deterministic.

Only affected declaration contracts are compared. Retired roots supply retired body
watchers directly; surviving subscriptions remain. Notifications are deduplicated
and dispatched before the frontend retires old preparation-queue attachments.
Unchanged function contracts do not invalidate callers merely because the body changed.
The existing function/default, constant-value, global-type and ambiguity rules remain.

## Structure changes

- `scope.recent_entries`: private non-owning index of live entries marked added/changed
  in the last pass. Publication resets only those statuses to none, then records this
  pass's changed live entries. Tombstones retain removed status.
- `Incremental_Frontend` keeps a temporary changed-root index alongside its retired roots.
- `Scope_Collection::publish_changes()` updates affected chains through the existing owner.
- `Dependency_Watches::retire()` removes one retired watcher's subscriptions and schedules
  its final invalidation; full-inventory `retain()` reuses it.
- No new AST fields, candidate lists, persistent publication queues or symbol identities.

The full-inventory publication API remains available for full compilation and direct
analysis clients. The incremental path does not call it. A no-op reads no declaration
contracts and relinks no chains; it may reset the previous pass's live change statuses.
Preparation validation/import scans, filesystem metadata scans, retained-session cleanup
remain separate work. Source-unit incremental emission is now connected through the
[retained executable path](incremental_executable.md).

## Verification

`tests/incremental_publication.php` exercises all three flavors and counts contract
serialization: two initial function contracts, zero on no-op, two for one replacement
(old/new), with an unrelated function untouched. It verifies ordered duplicate insertion,
middle/head removal, recovery, stable tombstones and reactivation.

The existing frontend/preparation/queue tests cover changed files, edits, rename/removal,
parse failures, invalidation, preparation retry and recovery. The focused compiler CTest
selection passed 50/50 tests. Native unary fixtures additionally exercise fresh C++ builds
and execution across all flavors; native emission itself remains a full-build path.

Native proof (2026-11-05): six builds/executions passed with expected output and exit
status 8. O2/g0, libc++, Ninja8, mold, no PCH/LTO, prebuilt runtime. Normal all-flavor
fixtures: 135–138 ms wall, 10.6–13.4 ms frontend, 10.7–14.9 ms LLVM backend,
62–70 ms linker command. Recovery fixtures: 112–133 ms wall. These are focused
correctness-build timings, not a publication speedup benchmark. Raw artifacts are
under `v0.2/build/changed-publication-proof-2026-11-05/`.
