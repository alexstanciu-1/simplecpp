# Retained incremental state: cleanup slice 1

Doc Status: supporting
Date: 2026-11-05

This records the pre-cleanup slice 1 inventory and evidence. Successful-generation
retirement is now implemented by [slice 2](generation_cleanup.md); its current policy
supersedes the history-retention descriptions below.
The ownership/barrier baseline remains the [native plan](../../specs/planning/two_traversal_preparation.md).

## Pre-cleanup owners and reasons for retention

| Retained state | Strong PHP path / owner | Reason and eventual boundary |
| --- | --- | --- |
| Current session | `Incremental_Project.session` | Current analysis and retry state; retained between invocations. |
| Prior sessions | `compilation_session.previous`, recursively | Retains old module/source inventories and replaced syntax. There is no generation-close operation yet; even no-op runs add sessions. These are not immutable historical semantic snapshots. |
| Current source/tokens/AST | session modules → source units → AST root → tokens → source | Needed for reuse, diagnostics and lowering. Unchanged roots are shared, not copied. |
| Replaced ASTs and their facts | prior session source units | Retained by history even after successful replacement. Parent/child and scope references also form PHP cycles, but reachable history cannot be reclaimed by cycle collection. |
| Removed source records | `session.removed_sources` → old AST and module | Records withdrawals for that generation; history retains these too. Their eventual release is separate from keeping symbol tombstones. |
| Stable symbol slots | shared `scope.entries` | Identity handles and missing-name recovery; tombstones intentionally survive. They retain no removed declaration head. Symbol identity reclamation is outside the initial cleanup work. |
| Live declaration and duplicate references | scope indexes and `duplicate_element` | Current resolution and ambiguity evidence. The declaring scope alone maintains chains at publication; file syntax inventories independently retain their nodes. Native intent is non-owning, but PHP references are strong. |
| Watch relations | shared `Dependency_Watches.observers` and `targets` | Valid caches need their subscriptions. Both directions strongly retain their keys in PHP. Publication retires replaced/removed bodies; reprepare forgets old relations before rebuilding them. Failed lookups may intentionally retain missing-symbol watches. |
| Pending invalidations | `Dependency_Watches.pending` | Holds watchers until dispatch. Empty after successful publication/dispatch; not a historical event log. |
| Preparation membership | shared `preparation_work.bodies` | Dirty/failed bodies awaiting success. Bodies point back to this queue; retirement detaches old bodies. Empty after a successful preparation pass. |
| Downstream dirty sources | `preparation_work.changed_sources` | Retained through preparation until output publication succeeds. Cleared by successful `Incremental_Project.run()`. |
| Prepared facts and completion revisions | AST expressions, declarations, bodies and file root | Reusable derived state, not disposable scratch. Old retained roots may contain invalidated facts; old sessions are not independently readable prepared snapshots. |
| Last emitted bundle | `Incremental_Project.output` | Strings, paths and link inputs for emission reuse; no second AST. Retain the last successfully published bundle across retries. |
| Temporary stage collections | local parser, publication, preparation and emitter variables | Normally released on return/unwind. A caller retaining an exception, diagnostic, emission handle or session can extend source lifetimes. |
| Canonical types/tokenizer tables | process statics | Intentional process-wide caches; not per-generation cleanup candidates. |
| Generated disk artifacts | `Cpp_Bundle` manifest ownership | Existing publication retires obsolete owned artifacts and preserves unchanged outputs. Disk artifacts are distinct from in-memory session history. |

Relevant owners: [session](../01_prepare_inputs/modules.php),
[incremental frontend](../compiler/incremental.php),
[symbol publication](../compiler/symbols.php), [watches](../compiler/dependencies.php),
[preparation queue](../04_analyze/prepare/structures.php),
[executable coordinator](../compiler/incremental_project.php).

## Separate verification tool

```sh
XDEBUG_MODE=off php v0.2/compiler/tests/retention/run.php 12 > retention.json
```

The optional argument is edit-cycle count (1–1000). Each flavor gets an isolated
three-file application and one resident production `Incremental_Project`. One initial
build is followed by an edit and a no-op per cycle. The provider body changes; its
signature and the other two files stay unchanged. Explicit mtimes make same-size edits
observable under the current mtime+size contract. Temporary applications are removed
on exit; JSON output is retained wherever the caller redirects it.

The test-only `Retention_Inspection` reads instance fields through reflection and
traverses strong references, including `SplObjectStorage` keys/data and `ArrayObject`
buckets. It returns scalar counts/class histograms, not compiler objects. WeakMap keys
observe lifetime families without extending them. No production introspection APIs,
cleanup hooks, fake owner, history detachment or boot registration are introduced.
Keep future cleanup implementation in its real lifecycle owner; do not put it in this
inspection helper or disperse it through the test's graph traversal.

Assertions:

- Unchanged AST/token identities and the provider's stable symbol slot survive.
- Initial preparation/emission touches three units; edits touch one; no-ops touch zero.
- Two live watcher relationships remain deduplicated; forward/reverse indexes agree.
- No retired body remains registered as a watcher in this successful-edit scenario.
- Successful runs leave no pending invalidations, bodies or emission-source work.
- Same-key edits create no extra symbol slots or tombstones.
- After releasing the entire project, all observed session/root/body/token/source
  objects become collectible (PHP cycle collection is invoked only by the test).

The current tool additionally asserts one retained session, zero retired roots and
only the current observed lifetime families after collection. The historical numbers
below were recorded before those slice 2 assertions were added. It does not mutate or null
`session.previous`, unlike the earlier timing harness which deliberately bounded history.

The memory figure is whole-process PHP used memory sampled before each graph walk;
it includes the harness, weak observations and accumulated scalar reports. It is not
exclusive compiler memory, RSS, object byte size or evidence of a leak by itself.
Object reachability and weak lifetime checks are the principal evidence. Inspection
is deliberately outside production and is not a compiler performance benchmark.

## Historical slice 1 evidence

With 12 cycles in each of cpp/php/js (75 successful compiler runs):

| Metric per flavor | Initial | After 12 edits + 12 no-ops |
| --- | ---: | ---: |
| Current source files | 3 | 3 |
| Reachable sessions | 1 | 25 |
| Reachable AST roots / token lists | 3 / 3 | 15 / 15 |
| Retired AST roots | 0 | 12 |
| Global symbol slots | 2 | 2 |
| Watchers / relations | 2 / 2 | 2 / 2 |
| Stale watchers / pending work | 0 | 0 |

All tracked lifetime families have zero survivors after whole-project release.
This demonstrates reachable-history retention, not an independently rooted leak in
this fixture. No-op generations retain additional session/module/source records,
while edits additionally retain replaced source/token/AST generations.

This slice emits C++ but does not invoke Clang or claim native build timings. The
purpose is retained compiler-state inspection. Deletion/re-addition, duplicates,
failure cleanup, bounded native lifetimes and clean-build semantic equivalence remain
later slices; the successful-edit result is not proof for those cases.

A second run with 100 cycles per flavor also passed (603 successful compiler runs):
201 retained sessions, 103 AST roots/token lists, 100 retired roots, two watch relations
and no pending work per flavor. Whole-project release again left zero tracked survivors.
Raw local evidence: `v0.2/build/retention-slice1/{12,100}-cycles.json`.
