# Demand, replay and publication coverage — 2026-10-20

Doc Status: planning

Tested base: `424f8a0f` plus the inventory/source-demand fixture and documentation
overlay. No compiler/runtime implementation changes or new retained structures.

## Added proof

`runtime_inventory.php` already checks that loading all production catalogs emits
nothing eagerly and that nested `hash<vector<int>,string>` demands exactly four
representations. It now compares the **complete middleware bundle**, including
implementation bodies and layout probes, with two additional discovery orders.
Names/bytes and semantic type counts must remain unchanged; compact engine entry
IDs are deliberately not compared across engines.

`runtime_template_demand.php` already verifies live emission versus cached-fragment
replay, retained fragment identity, unchanged semantic type count, demand retirement,
restoration and option switching. It now uses `Output_Publication` to publish the
uninstrumented compiler artifacts separately from its native counter harness:

1. Publish the live result and retain its ready manifest.
2. Replay cached fragments and require the identical manifest.
3. Remove the source demands, require obsolete artifact paths to disappear on disk,
   and preserve a user-owned file.
4. Restore the source and require the exact original manifest and artifact snapshot.

Each publication attempt acquires/closes a fresh existing publication worker.
Manifest generation, retirement and readiness remain production-owned. There is no
second publisher or specialization registry. The combined acceptance already runs
both strengthened fixtures through its metadata and header/counter phases.

## Evidence map

| TODO obligation | Proof boundary |
|---|---|
| Used-only nested closure, removal/restoration | Full catalog inventory plus source-driven demand, in memory and on disk |
| Replay and order independence | Retained fragment identity and exact artifacts/manifests; complete nested-type bundles across three request orders |
| Body/layout/runtime/config changes | Source lifecycle method/layout edits; layout-cache provider/header edits and corruption repair; source-demand option restoration |
| Failure/readiness, layout order and warm reuse | Existing publication crash/collision recovery and strict unsupported rejection/repair; native layout failure/repair; unchanged native builds reuse layouts/objects and do not link |
| Field/signature/reference/order/cleanup/counters | Ordinary/concrete output, indexing, compound and source lifecycle comparisons at O0, including five throwing-copy stages |

This closes the supported-path verification checklist. It is not an exhaustive
operation-request permutation test, arbitrary provider compatibility claim, or a
claim that the whole incremental compiler is finished.

## Fresh runs

All listed checks passed.

The final host rerun adds an explicit comparison of first live emission against
its first cached publication. Native runs used the same fixture before that final
assertion was added; generated artifacts and native harness code were unchanged.

- `/tmp/prelower-demand-publication-20261020-final`: inventory and direct/concrete
  source-demand fixtures, including alternate provider/output operations.
- `/tmp/prelower-demand-recovery-20261020`: existing publication fixture and
  21 strict unsupported-boundary rejection/recovery cases.
- `/tmp/prelower-demand-headers-20261020`: O0 output/indexing/compound counters,
  nested references, order, alternate bindings and warm reuse.
- `/tmp/prelower-demand-lifecycle-20261020`: O0 methods/constructors, failure cleanup,
  ownership/reference identity, body/layout edits and warm reuse.
- `/tmp/prelower-demand-layout-20261020`: O0-only layout cache, dependency changes,
  corruption/failure repair and reuse.

The shared runner used CLI fallback because FPM sockets were unavailable in the
sandbox. Accounting and full application closure were not rerun for this fixture-only
change; their previous acceptance remains the evidence. No O2/sanitizer run, native
compiler rebuild or new timing comparison was performed. Existing optimized-build
limitations remain deferred. Strict no-silent-fallback behavior is unchanged.
