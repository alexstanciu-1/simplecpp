# Nullable enum ownership boundary — 2026-10-25
Doc Status: planning

A temporary host-only preparation-worker observer replayed the previous converted
snapshot and identified Type_Registry::validate_application as the remaining
same-enum comparison owner. The observer changes lived in /tmp, not compiler source.
The diagnostic replay was explicitly interrupted after collecting repeated owner
failures; audit_partial.json is deduplicated partial evidence, not a completed run
or exhaustive independent-root diagnosis. audit_recipe.py records its invocation.

User approved the same PHP-only fix: extract runtime_storage(payload_definition)
through take_nullable into a required enum before comparison. Only registered
shared runtime storage rejects ownership transformation. Absence and inline storage
retain their previous behavior. This reuses existing nullable extraction and needs
no compiler, converter, runtime, template-prelowering or language changes.

The extended tests/portability/nullable_enum_boundaries.py extracts the production
branch. PHP, v0.1 native and both v0.2 native output modes pass, including absent and
inline acceptance and shared rejection. Existing extraction/short-circuit proofs
remain covered. Host type_catalog, type_capabilities and incremental_preparation
pass. These native programs are focused proofs, not a rebuilt native compiler.

Final measured bootstrap: 1:55.96 wall, 614812 KiB peak RSS (600.40 MiB),
582.5 MiB PHP heap; preparation fails. Whole runner: 120.59 seconds including
conversion/orchestration. Same 16 diagnostic categories. First is now runtime-call
signature matching; enum comparison remains later in the aggregated diagnostics.
Thus the focused approved site is repaired, but the enum category is not eliminated.
The earlier partial owner audit is not evidence locating that later enum occurrence.

Stopped at the next blocker without runtime-call fixes. Native compiler and
second-generation acceptance remain outstanding. revision.txt records the parent
commit; source_hashes.json identifies the tested source snapshot.
