# Runtime metadata
Doc Status: supporting

`modules/*.json` follows the [versioned module contract](../../specs/runtime_module_metadata.md).
The files are runtime-owned, reusable compiler input, not generated C++ or my-try-local
configuration. Current coverage includes core `strlen`/`count`, JSON free functions,
all eleven strict datetime helpers, nineteen strict filesystem helpers,
and the ready strings surface (case/predicates/trimming, padding/constants,
int/float formatting, split/typed join, slicing/comparison, hex conversion and
explicit byte/Unicode helpers). Existing
v0.1 symbol maps have not yet been replaced wholesale. See the
[coverage roadmap](../../compiler/my-try/docs/planning/runtime_catalog_coverage.md)
for remaining groups and prerequisites.

Work proceeds module-by-module: audit public contracts, register the settled rows,
validate the batch, and track blocked rows outside executable JSON. The strings
inventory records 29 names/45 signatures; search-coordinate conflicts, replacement
semantics, boxed joins/formatting and runtime builder types remain explicit gaps.

Load selected modules once at startup, decode directly to typed records and retain
them for registry installation/reset. Do not rescan these files during compilation.

Ordinary runtime type definitions use `types`, with separate `cpp_types` bindings.
The core vector/hash recipes now live here, including constraints, defaults and
capability dependencies; no duplicate PHP declarations are required. Definitions
install before function signatures. Language-specific type roles remain compiler-owned;
methods, construction/indexing/iteration protocols and handle lifetimes are not yet
part of these records.

Datetime bindings require the separately compiled `modules/datetime/datetime.cpp`
provider. Catalog dependencies control metadata loading, not linker configuration.
Filesystem providers (`modules/filesystem/filesystem.cpp` and `snapshot.cpp`) are
already bundled into the snippet `core/runtime.cpp` object: do not link them twice.
File-lock handle registration remains deferred; internal output-file
portability bindings are intentionally not exposed as public source functions.
`fs_scan` uses shared Result operations for its exact `vector<string>` payload.
