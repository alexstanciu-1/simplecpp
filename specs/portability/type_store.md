# Canonical type storage and scalar materialization
Doc Status: supporting

`type_model\Type_Store` owns separate one-based type and representation IDs and
zero-based member ranges. A reference can precede its declaration. Declaration can
precede definition and representation. These states remain distinct; a storage shape
does not grant operations, lifetime, layout or ABI eligibility.

Use `fresh(context)` for a new lineage and `fork()` for a private candidate in an
existing lineage. The explicit fork copies containers while sharing immutable rows,
shapes, members and definitions. Mutations replace rows rather than edit shared data.
The low-level context/lineage constructor is producer-internal; manually reusing a
lineage for an unrelated empty store is outside the contract. Candidate copying does
not retain an ancestor-store chain. Published stores are read-only by convention.

All prototype shapes are interned: void, integer, floating format, pointer/address
space, fixed array/count, ordered named structure fields/writability, signature
return/parameters/passing modes, opaque storage and byte spans. Scalar/pointer/array
hits avoid constructing another representation. Duplicate aggregate/signature hits
do not append member ranges. UTF-8 names use byte lengths in unambiguous keys.
Unknown IDs and invalid components fail before appending members.

`Representation::same` replaces PHP structural object comparison with explicit shape
comparison. Store-local IDs and ranges still require their owning lineage. Definition
binding preserves exact authoritative identity; replacement requires invalidation.
`Result_Contracts` supplies semantic result production. Signature keys include this
production mode so a surviving type ID with a changed representation cannot reuse a
stale scalar-versus-owned result contract. The retained oracle reproduces the old key
omission; this is an intentional correction within the store owner.

`resolve_types\Type_Cache` prepares full/retained candidates using all three context
keys and materializes the current scalar catalog's definitions. It never changes the
caller's full-rebuild decision. Scalar materialization is not a substitute for the
remaining array/resource/source-structure definition producers. The store supports
structural shapes, but binding richer definitions awaits that real model. Storage/
structure definition scans and production debug serialization will follow their
consumers; no empty placeholder implementations are supplied.

The proof covers 133 PHP/native outcomes, retained store facts and 20 host snapshot/
rejection checks. See [timings and provenance](../planning/compiler_migration/results/type-store-01/README.md).
