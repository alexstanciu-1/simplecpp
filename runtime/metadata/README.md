# Runtime metadata
Doc Status: supporting

`modules/*.json` follows the [versioned module contract](../../specs/runtime_module_metadata.md).
The files are runtime-owned, reusable compiler input, not generated C++ or my-try-local
configuration. Current coverage includes core `strlen`/`count`, JSON free functions,
all eleven strict datetime helpers, twenty-two strict filesystem helpers,
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

JSON documents/nodes/diagnostics and file-lock handles are registered too. The
optional process catalog adds its five managed-process calls, two shared types and
six output fields. Shared storage copies handles, not provider payloads.

Concrete backend representations use the separate
[runtime interoperability contract](../specs/concrete_type_interoperability.md).
It consumes registry-selected representation facts without adding a parallel catalog.

## Add or select a module

1. Check the owning spec and provider ABI; do not infer semantics from a C++ name.
2. Add `modules/NAME.json`: version, module identity, dependencies and supported
   type/function/constant/property contracts. Use template parameter recipes rather
   than enumerating concrete applications. Declare storage and capabilities honestly.
3. Supply separate cpp/cpp_types/cpp_properties/initialization bindings. Symbols are
   names and paths, not executable C++ snippets. Factories are ordinary callables.
4. Declare `cpp_build` sources, libraries and definitions where needed. Paths are
   relative to runtime/include; libraries are logical linker names. Dependencies
   contribute their inputs automatically. No package installation or arbitrary flags.
5. Select the module at the composition root before compilation, then use normal
   artifact publication and native build. Module identity does not create a source
   namespace. Public names support ordinary rooted lookup and body-local imports.

For a custom my-try host, after loading boot.php and before initialization:

```php
$modules /** vector<string> */ = ['json', 'filesystem', 'process'];
Compiler_Lifecycle::configure_runtime(Runtime_Catalog_Loader::load($catalog_directory, $modules));
$publication = new Output_Publication($output_directory);
try {
    $compiler = new Compiler();
    $compiler->init([$source_directory]);
    $compiler->select_entry('main', 'main.phs');
    $compiler->exec_cpp($publication);
} finally {
    $publication->close();
}
```

These classes live in `scpp\compiler`. Selection replaces the host defaults and
loads the declared dependency closure once; core should be a declared dependency
when using core types. Failed configuration is not published. Catalog replacement
during compilation is unsupported; start a new compiler session for edits.
Default host selection remains core/json/strings/datetime/filesystem. Optional
modules are selected through this API, not a new CLI flag.

Build the published output with `php compiler/my-try/tools/build_cpp.php OUTPUT CACHE`.
It uses metadata requirements embedded in the ready manifest, not the catalogs.
Definitions affect PCH/object identities; sources receive normal dependency tracking;
libraries affect linking. Providers/external libraries must already be available.
Old publications without runtime_build require republishing.

Core supplies core/runtime.cpp; it already bundles filesystem, snapshot and lock
providers, so do not list standalone copies. JSON documents and datetime declare
separate provider sources. Process declares its provider; regex declares its provider
and pcre2-8. Tests/runtime_module_build.py and tests/runtime_assets.py under my-try
prove these paths without rebuilding the compiler itself.

The [closeout audit](../../compiler/my-try/docs/planning/runtime_module_closeout.md)
classifies every current module owner, remaining strict-map surface and residual
named compiler rules. Deferred APIs must not be added as speculative executable rows.
