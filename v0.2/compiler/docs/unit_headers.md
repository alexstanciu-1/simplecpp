# Generated source-unit headers

Doc Status: supporting

After successful preparation, externally watched native symbols are declared in the
owning source unit's generated `.hpp`. Symbols with only local watchers (or no
watchers) retain their declarations in the `.cpp`. Definitions remain in `.cpp` files;
this slice does not change linkage, language visibility or module export semantics.

## Selection and emission

`Cpp_Headers` plans declarations using `Dependency_Watches::has_external_observers()`.
It does not read the grouped observer storage or traverse executable bodies. Function
and global declarations use the existing emitter's signature/type spelling. Headers
include their own runtime type requirements and use `#pragma once`.

A source with no externally used native symbols has no header. Generated header paths
are `units/<source-key hash>.hpp`, beside the matching `.cpp`. Consumers include the
owner's header instead of repeating its function/global declaration. Providers include
their own header to check exported declarations against definitions. Local function
prototypes remain in their `.cpp`. Named constants still lower to inlined values and
therefore do not acquire C++ declarations or headers in this slice.

Both full and incremental program preparation collect watches. `prepared_program`
retains that coordinator; full preparation passes the existing watch registration
mechanism into body preparation. Standalone single-file `Cpp_Emitter::emit()` retains
self-contained declaration emission without needing a header layout.

## Incremental boundary

Header planning currently inspects all published function/global declarations after
successful preparation. This is a bounded metadata pass, not another body traversal
or usage-analysis implementation. It may later consume changed usage relations if
measurements justify that optimization; this slice does not add another dependency index.

Header text is compared with the last emitted bundle. A changed, new or removed header
also makes its provider's `.cpp` eligible for emission: the provider may need to replace
local prototypes with an include, or restore them. Actual body lowering otherwise
retains its existing changed-source selection. Identical generated text preserves its
file timestamp.

This handles the first/last external watcher even if the provider source never changed.
Ninja's existing depfiles rebuild every consumer of a changed header, including a
consumer whose generated `.cpp` text remained identical. Per-source headers are a coarse
native dependency boundary: changing one exported declaration can rebuild consumers of
other declarations in that header. No symbol-specific header splitting is introduced.

## Output model and manifest

- `ast_function_body.source_key` generalizes the existing preparation source key: both
  full and incremental preparation bind logical unit identity before registering watches.
- `prepared_program.dependencies` retains complete usage relations for header planning.
- `Cpp_Headers` owns temporary symbol-to-header lookup and generated header text.
- `cpp_program_output.files` contains all generated artifacts. `translation_units()`
  returns only `.cpp` paths; `headers()` returns `.hpp` paths.
- `cpp_program_output.emitted_sources` reports actual source-body emission, including
  providers affected only by header-placement changes.
- The bundle manifest is `scpp-cpp-program-v2`, with separate `translation_units` and
  `headers` inventories. Cache validation and obsolete-file cleanup cover both. Version
  1 remains readable for migration/cleanup but cannot satisfy a new reusable-build check.

The output manifest remains the cleanup owner. Header deletion/addition uses the existing
publication/recovery protocol. Native tools compile only translation units, never headers
as separate objects. Multifile native test fixtures now preserve artifact names so quoted
includes resolve correctly; benchmark adapters likewise distinguish translation units
from all generated files.

## Proof

`tests/unit_headers_native.py` retains a PHP compiler and Ninja build directory per flavor.
It checks local-only declarations, the first foreign watcher, last foreign watcher removal,
header recreation, unchanged-provider emission, header-only consumer recompilation, body-only
header reuse, and self-contained string-signature headers. Every executable exits with 8.
The existing incremental executable proof also verifies globals, constants, failures,
publication retry, addition/removal and native recovery with the new headers present.

Validation on 2026-11-05: 51 compiler regression checks and all 23 native suites passed.
The dedicated header-policy proof has 24 successful executable steps across the three
flavors; the existing incremental proof adds 48 executable steps and expected failure
recovery. Raw header-policy timings and Clang traces are retained under
`v0.2/build/unit-headers-policy-proof/`; broader incremental results are under
`v0.2/build/unit-headers-incremental-proof/`.
