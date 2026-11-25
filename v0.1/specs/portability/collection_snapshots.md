# Owned collections and explicit snapshot updates
Doc Status: supporting

This proof extends the [existing storage evidence](compiler_storage_slice.md)
through [typed method boundaries](method_signatures.md). It uses existing converter
and framework capabilities; no production compiler owner or source set changes.

## Proved representation

A `Store` owns an explicitly annotated `vector<Row>` property. Each ordinary class
`Row` has an integer, a string and a `vector<int>` property. In executable PHP the
vectors use arrays with native intent annotations, not ad-hoc array records.

- Copying the store's vector into a newly constructed store copies membership
  while sharing the row objects. Appending to one store does not append to another.
- An explicit row-copy method constructs a new row and assigns each field. Its
  string and vector of integers can subsequently change independently.
- An update method returns a new store, replaces only the selected row with an
  explicit copy, and shares unchanged rows. Three successive versions retain their
  own values for the changed row, including after a later edit to its nested vector.
- Mutating a shared unchanged row is visible in every version retaining it. A
  shallow copy is therefore not an immutable snapshot by itself.

The writer owns the rule: **do not mutate a published shared row; replace it with
an explicitly prepared row in the new version**. The converter does not discover
or enforce that rule. The fixture deliberately violates it in a separate final
witness to expose the sharing behavior, not to recommend that mutation pattern.

## Limits of this evidence

This is a useful authoring pattern, not a universal snapshot API or a mandated
compiler representation. It does not prove recursive deep copying, value structs,
maps, nested vectors of vectors, cycles, concurrency, readonly enforcement or
exception-safe publication of a complete compiler update. If a row later contains
an object field, field assignment shares that object unless its owner deliberately
copies it; the scalar-vector result must not be generalized to object graphs.

Indices in this fixture are valid storage positions. Mapping semantic IDs to
positions, checking invalid indices and preserving algorithm-specific identities
belong to the real owner when it is adapted. No implicit ID-to-offset mapping is
introduced. No performance claim follows from this behavioral proof; PHP and
native containers may have different allocation and copying costs.

## Run and evidence

```bash
python3 tests/portability/collection_snapshots.py \
  --target-checkout /tmp/scpp-v0.1.76-probe \
  --results FRESH_RESULTS_DIRECTORY
```

The runner checks imports and conversion eligibility, runs executable PHP, converts
file-to-file, builds/runs the pinned strict target, and compares both results to an
independent expected sequence. The sequence includes three versions, row identity,
nested scalar-vector edits, separate membership, and the shared-row counterexample.

[Recorded evidence](../planning/compiler_migration/results/collection-snapshots-01/summary.json)
retains the inputs, generated PHP++, commands, result and runner.
