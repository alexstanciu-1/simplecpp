# Runtime surface audit evidence
Doc Status: planning

Snapshot date: 2026-10-27; implementation baseline `6794e6f3c`.
See [the audit and proposed plan](../runtime_surface_contract_audit.md).

`modules.tsv` enumerates every current runtime metadata module. `catalog_*.tsv`
records every function signature and native binding, declared type, representation
recipe, property, constant and initialization binding. Large module inventories
are split below 180 KB. `configured_types.tsv` includes every family in the existing
runtime config, including stale entries flagged by the audit. Nothing is filtered
out because it is templated: unresolved recipes are evidence for lowering work,
not declarations allowed in the finished public include root.

These are review snapshots, not executable metadata, generated public APIs or a new
semantic registry. The existing catalogs/config and higher-level contracts remain
the authorities. `requires mapping/proof` does not mean the legacy function is
broken or that no new public equivalent exists; it means these legacy catalog rows
have not been mapped/proved against the restricted public boundary by this inventory.
Read the audit for the manually inspected current public API and confirmed issues.

Reproduce with `python3 specs/planning/runtime_surface_audit/inventory.py`.
A complete semantic audit of every backend implementation is separate from this
surface/dependency audit. No optional external services were exercised here.
