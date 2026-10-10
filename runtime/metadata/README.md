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
inventory records 49 names/65 signatures, including two builder types and twenty
builder helpers. Search-coordinate conflicts, replacement semantics, boxed
joins/formatting and builder byte-span append remain explicit gaps.

Load selected modules once at startup, decode directly to typed records and retain
them for registry installation/reset. Do not rescan these files during compilation.

The opt-in `source` module depends on core and registers three owning value types
and thirteen buffer/index/location helpers. Startup defaults are unchanged.
Borrowed byte spans and their consumers remain held for lifetime/invalidation
support; source metadata does not make raw views into owning values.

Ordinary runtime type definitions use `types`, with separate `cpp_types` bindings.
The core vector/hash recipes now live here, including constraints, defaults and
capability dependencies; no duplicate PHP declarations are required. Definitions
install before function signatures. Language-specific type roles remain compiler-owned;
type-owned static/instance callables use optional membership on ordinary function
records. Property/constant members have separate contracts. Type records can opt
into initialization, indexing and value iteration through generic recipes; core
vector/hash use that same path. Initialization C++ adapters use separate bindings.
Borrowed lifetimes remain subsequent integration work.

Datetime bindings require the separately compiled `modules/datetime/datetime.cpp`
provider. Catalog dependencies control metadata loading, not linker configuration.
Filesystem providers (`modules/filesystem/filesystem.cpp` and `snapshot.cpp`) are
already bundled into the snippet `core/runtime.cpp` object: do not link them twice.
File-lock handle registration remains deferred; internal output-file
portability bindings are intentionally not exposed as public source functions.
`fs_scan` uses shared Result operations for its exact `vector<string>` payload.
