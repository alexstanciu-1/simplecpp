# Struct self-value checkpoint
Doc Status: planning

Starting revision e012200fd plus bounded SELF-02 and receiver identity reuse.
Full snapshot, source hashes and diff: `/tmp/compiler-bootstrap-struct-self-01`.
Reproduce with `python3 compiler/my-try/tools/bootstrap_probe.py --results FRESH`.

Conversion: 4.68 s / 195,112 KiB RSS; 269 PHS files / 1,960,733 bytes.
Bootstrap: 206.26 s / 469,752 KiB RSS (458.7 MiB), 451.0 MiB PHP heap.
Initialization: 0.304 s; sync/parse: 4.096 s. The first blocker remains the existing
nullable-value boundary restriction. The old generic self diagnostic becomes the
more precise class shared-owner-retention diagnostic. No fatal/OOM, emission or
native compiler rebuild. No competing tests/builds ran during measurement.

`/tmp/struct-self-core-02` passes the initial native program in both modes.
`/tmp/struct-self-core-03` passes the expanded native program in both modes:
assignment/argument/return copies, receiver mutation, shared field identity, vector
copy isolation, inherited snapshots, snapshots of shared-allocated structs and
copies from borrowed receiver closures. Its precursor negative harness omitted
invocation of a demand-prepared static method. The corrected
`/tmp/struct-self-preparation-05` passes eight negative cases plus repeated and
cross-file noncopyable-layout rejection/recovery in both modes.

All existing method (26), inheritance (59) and reference-capture (44) generated
programs pass; locations and summaries are in fixture_summary.json. These are
v0.2 core proofs. PHP/legacy portable struct-copy parity remains COPY-01, not a
claim from native struct behavior. Shared struct retention remains deferred.

The initial self fixture exposed duplicate canonical identities from recreating a
source definition. Method/constructor receiver setup now reuses published identity
through the existing observed lookup. Other source-definition consumers remain
listed in the blocking CH15 registry audit. No registry interning or name-equality
shortcut was introduced.
