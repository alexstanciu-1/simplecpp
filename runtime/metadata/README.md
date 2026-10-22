# Runtime metadata
Doc Status: supporting

`modules/*.json` follows the [versioned module contract](../../specs/runtime_module_metadata.md).
The files are runtime-owned, reusable compiler input, not generated C++ or my-try-local
configuration. Core also exposes `error::make` with scalar defaults and the three
error detail getters, using the existing registered error identity and ordinary
static/instance callable bindings. Core also registers the opaque shared `Exception`,
its `make(string)` factory and `get_message()` method; see the
[bounded exception contract](../../specs/exceptions_v0_2.md). See the [error contract](../../specs/error_values_and_take.md).
Current coverage includes core `strlen`/`count`, JSON free functions,
all eleven strict datetime helpers, twenty-two strict filesystem helpers,
and the ready strings surface (case/predicates/trimming, padding/constants,
int/float formatting, split/typed join, slicing/comparison, hex conversion and
explicit byte/Unicode helpers). Existing
v0.1 symbol maps have not yet been replaced wholesale. See the
[coverage roadmap](../../compiler/my-try/docs/planning/runtime_catalog_coverage.md)
for remaining groups and prerequisites.

Work proceeds module-by-module: audit public contracts, register the settled rows,
validate the batch, and track blocked rows outside executable JSON. The strings
inventory records 54 names/73 signatures, including two builder types and twenty
builder helpers. Search-coordinate conflicts, replacement semantics, boxed
joins/formatting remain explicit gaps. Builder span append is enabled by the source module.

Load selected modules once at startup, decode directly to typed records and retain
them for registry installation/reset. Do not rescan these files during compilation.

The opt-in `source` module depends on strings and registers four runtime value
types and twenty helpers, including checked spans, span hashes and builder span
append. Startup defaults are unchanged. Spans weakly observe independent buffer
states and reject reads after release, move, replacement or destruction. String
hashes are registered in strings; the stable algorithm is FNV-1a64 with v2 keys.
Source and builder types use existing concrete-boundary metadata for prelowering.

Ordinary runtime type definitions use `types`, with separate `cpp_types` bindings.
The core vector/hash recipes now live here, including constraints, defaults and
capability dependencies; no duplicate PHP declarations are required. Definitions
install before function signatures. Language-specific type roles remain compiler-owned;
type-owned static/instance callables use optional membership on ordinary function
records. Property/constant members have separate contracts. Type records can opt
into initialization, indexing and value iteration through generic recipes; core
vector/hash use that same path. Initialization C++ adapters use separate bindings.
The core vector/hash adapters select `scpp/data_initializer_typed.hpp`. Compiler
bindings for the call-scoped input types select `scpp/data_initializer_input.hpp`;
the language-owned mixed/dynamic adapters select their respective
`data_initializer_mixed.hpp` and `data_initializer_dynamic.hpp` headers. These
headers preserve the same inline adapter bodies. `scpp/data_initializer.hpp` remains
a compatibility umbrella including every adapter. Do not select the umbrella for a
typed-only initialization binding: its concrete mixed/dynamic bodies cause unrelated
template instantiation in each consuming unit.

Borrowed lifetimes remain subsequent integration work.

JSON documents/nodes/diagnostics and file-lock handles are registered too. The
optional process catalog adds its five managed-process calls, two shared types and
six output fields. Shared storage copies handles, not provider payloads.

Core now supplies C++ representation recipes for existing compiler-known scalar,
boxed, nullable/Result, ownership and value-modifier identities. These are bindings
in `cpp_types`, not additional semantic declarations. Structured native arguments
distinguish plain types, closed templates and semantic arguments in stored/payload
representation. Missing core selection supplies no implicit runtime binding fallback.
Catalog registration does not generate concrete types; application demand does.

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

The optional ui/webview catalogs expose the 24 existing strict preview helpers.
Webview depends on ui; `ui_event_webview` is selected with webview. Project-mode
compilation resolves their named SDK requirements using the existing host provider
resolver before retaining metadata. Custom PHP hosts must call
`Host_Runtime_Build::resolve($input)` from `compiler/my-try/host/runtime_build.php`
before configuration. Unresolved SDK requests reject publication. Resolved options
and diagnostics are published once and validated by native consumers; they are
not executable module JSON fields. Linux/Clang integration is currently verified
only with disabled providers: enabled GTK/WebKitGTK and GUI proofs remain explicit
in the [V-R08 report](../../compiler/my-try/docs/planning/ui_webview_integration.md).

The [closeout audit](../../compiler/my-try/docs/planning/runtime_module_closeout.md)
classifies every current module owner, remaining strict-map surface and residual
named compiler rules. Deferred APIs must not be added as speculative executable rows.
