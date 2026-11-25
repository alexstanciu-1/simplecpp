# Access base constructor promotion — 2026-10-24
Doc Status: planning

Base 9e3081068 plus candidate hashes. User approved constructor promotion of both
index_node and field_access_node base fields. Reuses existing promotion conversion,
set initialization and shared receiver transport; no new semantics, runtime or
converter changes. No nullable backing or accessor needed: the receiver already
exists at allocation. Both parser sites pass that exact object at the old allocation
point; token consumption, index expression parsing, spans and collection stay in
order. Updated the one manual field_access fixture allocation as well.

## Proofs

Extracted production constructors, children/maintenance methods and child iterators
pass PHP, legacy, ordinary and concrete native routes. Native task durations are
22.48/25.40/21.74 s. Identity along a field/index/field chain, observed receiver
mutation, optional index omission, child order and maintenance edges are checked.
Dependency doubles bound native proof; this is not a full native compiler build.
Actual reference_model, grouping and indexing tests pass; indexing includes no-op
retention, arity edits and recovery. specialization_dispatch fails before reaching
its changed fixture, on an existing unsupported-expression diagnostic expectation.
An isolated HEAD boot/source/test overlay reproduces the same failure. Not fixed
or counted passing. Shared runner used CLI fallback because FPM sockets were denied.

## Bootstrap

74.66 s wall / 355224 KiB peak RSS (346.90 MiB) / 324 MiB PHP heap.
Total task 79.68 s includes conversion/setup. Required base initialization diagnostic
removed; none added. Next diagnostic: Property requires set: initialization before
the constructor body: tokens. Stopped without fixing it. No complete bootstrap claim.
