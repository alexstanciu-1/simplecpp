# Source snapshot acceptance
Doc Status: supporting

Snapshot_Join uses explicit vectors of selected read tasks and returned immutable
buffers, with typed keyed lookup tables for validation. Missing-key coalescing is
written as visible membership checks. Empty-container field assignments use typed
locals because the selected target cannot infer their type from an arbitrary receiver.
No converter capability was added.

The selected target evaluated a nullable dereference inside a compound `||` guard
even when the value was absent. A separate null-rejection statement preserves the
algorithm without relying on that expression behavior. The failed native diagnostic
is retained alongside the passing evidence; target lowering remains separate work.

Source_Set owns explicit shallow copying of its fields and indexes; acknowledgment
reuses that operation while clearing removed IDs. Snapshot acceptance preserves
membership/index positions and shares unchanged rows. Replaced or cleared buffers
receive a source_file copy. Deleted rows lose buffers only in the returned candidate.
No original row or set is mutated on either successful or rejected acceptance.

Content validation uses string_byte_len: task sizes count bytes, including UTF-8
source content. The proof uses a two-byte character to guard this boundary.

The cumulative PHP/native harness checks changed/retained/deleted row identities,
exact accepted buffer identity, preserved entry/path/removal/next-ID data, separate
membership/index storage, repeat acceptance, and incomplete, duplicate, stale and
wrong-size rejection. Existing source snapshot and cross-stage join fixtures remain
part of the retained compiler suite. Evidence is recorded after validation in
`specs/planning/compiler_migration/results/snapshot-01/summary.json`.

This migrates acceptance only. Filesystem reading, discovery orchestration and
whole-compiler execution remain outside this component proof.
