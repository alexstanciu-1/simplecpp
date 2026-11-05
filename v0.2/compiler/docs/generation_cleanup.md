# Successful-generation cleanup (slice 2)

Doc Status: supporting

`Generation_Cleanup::close()` is the dedicated retirement owner. The executable
coordinator calls it after C++ bundle publication succeeds and emission work is
acknowledged. It releases only the current session's `previous` and `removed_sources`
references. It never traverses or clears ASTs, symbols, facts, watches or caches.
No new fields or retained cleanup records are introduced.

The owner requires a current published session with no pending preparation or
emission work. These guards reject obvious misuse; the caller still owns the successful
publication gate. Native execution must join readers before calling close. Explicitly
retained external sessions/handles keep their objects alive; close does not mutate their
history. Tombstones and current symbol identities remain. Repeated close is harmless.

Failures do not reach the successful `close()` gate. [Slice 4](failure_cleanup.md)
adds a separate `failed()` gate after frontend publication, releasing obsolete history
without acknowledging pending work or removing recovery watches. A native build failure occurs
after successful C++ publication, so it does not require keeping prior AST generations.
The frontend-only service/API retains its existing history policy: this slice integrates
only the successful executable coordinator gate.

PHP parent/child and scope references form cycles. Releasing history makes retired
objects eligible for collection; it does not promise immediate reclamation of every byte.
Production does not call `gc_collect_cycles()` on each run. PHP's existing cycle collector
remains responsible; choosing a forced-collection policy is separate from reference
ownership. The native port must honor the documented non-owning backlinks.

## Verification and timings

The separate `tests/retention/` directory contains:

- `run.php`: real retained executable coordinator, unchanged AST/token/entry identity,
  watch consistency, empty queues, bounded history, weak lifetime checks and total PHP
  run/explicit test-GC timings. Does not detach history itself.
- `inspect.php`: read-only test inspection, never loaded by the production bootstrap.
- `gates.php`: external old-session ownership, stale/pending-work rejection, preparation
  failure retaining history, successful recovery and repeated close.
- `close_timing.php`: invokes production frontend/preparation/emission/publication stages,
  then separately times the actual close method and explicit PHP collection. No clocks
  are introduced into compiler implementation. This two-file fixture is for close costs,
  not incremental emitter throughput (it renders the whole small program before timing).

Run with `XDEBUG_MODE=off`. The 12- and 100-cycle retention runs passed all three flavors
(678 successful runs). After 100 edits and 100 no-ops, each project retains one session,
three AST/token roots, zero retired roots, two watch relationships and no pending work.
All tracked old objects become collectible; whole-project release leaves zero survivors.
Compare [historical slice 1](retained_state.md): 201 sessions and 103 roots at that point.

On this WSL host, 25 edits and 25 no-ops per flavor (75 samples per row):

| Operation | Close avg (min–max), ms | Explicit PHP GC avg (min–max), ms |
| --- | ---: | ---: |
| Edit | 0.00387 (0.00200–0.00797) | 0.02967 (0.02253–0.04984) |
| No-op | 0.00241 (0.00139–0.00877) | 0.02020 (0.01354–0.06980) |

These are tiny-fixture measurements including timer overhead; GC is process-wide,
not exclusive compiler cleanup time. They do not establish costs for large ASTs or
long failure histories. Graph inspection is outside both timed regions. Memory numbers
in `run.php` include the harness and scalar report history, so object reachability is
the boundedness proof, not a claim of flat process RSS.

The existing native incremental suite passed 48 executable builds/runs across all flavors,
including edits, addition/removal, no-op/no-link, failed parse/preparation/publication and
native failure recovery. Its output, exact object counts and stale-handle checks remain
unchanged. Detailed native phase timings and raw results are in
`v0.2/build/retention-slice2/native/results.json`; cleanup timings are in
`v0.2/build/retention-slice2/close-timing.json`.

[Slice 3: symbol and watcher retirement](symbol_retirement.md) verifies deletion,
duplicates, replacement and re-addition without adding a second cleanup mechanism.
