# Nullable enum source boundaries — 2026-10-25
Doc Status: planning

Approved PHP-only adaptation of Type_Registry::use, is_value_type,
has_registered_capability and indexing_declaration construction. Reuses existing
required-local checked extraction and take_nullable. No compiler, converter,
runtime or language semantics changed.

Production snippets are extracted by tests/portability/nullable_enum_boundaries.py.
PHP, v0.1 native and v0.2 ordinary/concrete native proofs pass: absent/inline/shared
storage, skipped lookup, value-storage rejection, capability selection and optional
store-operation validation. These are small native programs, not a rebuilt compiler.
Actual host type_catalog, type_capabilities and incremental_preparation suites pass.

The first cross-route proof caught a v0.1 evaluation difference: combining
by_value && take_nullable(...) in one expression evaluated an unwanted lookup
through overloaded native logical operators. Explicit nested PHP if statements
preserve the skipped lookup in both routes; the final proof checks its call count.
No v0.1 operator redesign was attempted.

Final PHP-hosted bootstrap: 1:58.36 wall, 614012 KiB peak RSS (599.62 MiB),
582.5 MiB peak PHP heap; preparation fails. The whole runner reports 123.02 seconds,
including conversion/orchestration. Prior enum-presence baseline: 2:01.15,
614692 KiB RSS, 582.5 MiB heap. This is a single-run comparison, not a performance claim.
An intermediate bootstrap took 2:08.84 / 614324 KiB / 582.5 MiB.

All 16 diagnostic categories remain. First is still
`S2S enum comparison requires the same enum type`: the bounded audited sites pass
focused proofs, but this does not establish that all enum comparisons are repaired.
The aggregate bootstrap diagnostic does not locate the remaining occurrence.
Stopped before expanding the fix to other sites. Native compiler and second-generation
acceptance remain outstanding.

revision.txt is the parent revision; source_hashes.json identifies the tested dirty
source snapshot. portable.json and host.json record focused proof results.
