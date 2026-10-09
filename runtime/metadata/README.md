# Runtime metadata
Doc Status: supporting

`modules/*.json` follows the [versioned module contract](../../specs/runtime_module_metadata.md).
The files are runtime-owned, reusable compiler input, not generated C++ or my-try-local
configuration. Current coverage includes core `strlen`/`count`, JSON free functions,
and the first string-helper group (case conversion and byte predicates). Existing
v0.1 symbol maps have not yet been replaced wholesale. See the
[coverage roadmap](../../compiler/my-try/docs/planning/runtime_catalog_coverage.md)
for remaining groups and prerequisites.

Load selected modules once at startup, decode directly to typed records and retain
them for registry installation/reset. Do not rescan these files during compilation.
