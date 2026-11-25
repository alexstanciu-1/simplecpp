# Runtime preparation tool
Doc Status: supporting

This folder is the temporary development home for the runtime preparation tool
until the compiler becomes part of Simple C++. The first standalone string package
is implemented; its version-1 metadata is now consumed through the compiler's
[package adapter](../docs/details/runtime_package_consumption.md) for direct integer
calls, inline construction/borrowing, metadata-bound destruction and explicit
[copy construction](../docs/details/runtime_copy_construction.md). Metadata-bound
[literal construction and echo](../docs/details/runtime_string_literals.md)
use the same imported call contracts.
The [foundations sequence](../docs/planning/compiler_foundations.md#sequence-and-composition-gates)
owns the scope, dependencies and completion proofs. The
[design decisions](../docs/details/runtime_package_preparation.md) record the
agreed contracts, ownership and first proof.

The tool prepares selected Simple C++ headers and a target/toolchain configuration
for later consumption by the compiler. Its outputs are type/layout metadata,
operation/ABI metadata, matching LLVM implementations and a package manifest.
Clang supplies mechanical facts and compiled code from Simple C++ headers and
implementations. Local JSON definitions supply language meaning, exposed operations,
ownership and error policies. Existing Simple C++ metadata import is deferred.

Preparation belongs to the runtime version and configuration. Applications reuse
the resulting provider package rather than regenerate bridges for every project
or source file. Compiler-side package consumption remains within the existing
compiler processes in [src/](../src).

## Generated output location

The Simple C++ header root in [config.json](config.json) is
`../../../../runtime/include`, relative to this retained tool directory. It uses
this repository checkout rather than the former external vendor checkout.

[config.json](config.json) defines `output_directory`, initially
`../generated-runtime`. Relative paths resolve against the configuration file's
directory, independent of the working directory; absolute paths are also allowed.
The default output location is therefore [generated-runtime/](../generated-runtime).
Generated files there are ignored by Git.

The tool places generated metadata, LLVM artifacts and the package manifest
under this configured directory. Moving the output into Simple C++ later changes
the configuration rather than tool code. `definitions_directory` and each
`include_directories` entry follow the same configuration-relative path rule.
`clang`, `target` and `standard` fix the compilation context. A bare Clang name is
resolved through PATH; an explicit executable path is configuration-relative.

## Running preparation

From the repository root:

```sh
php src-runtime-preparation/main.php
php src-runtime-preparation/main.php --config /path/to/config.json
php src-runtime-preparation/tests/run.php
```

The CLI returns JSON with `status` (`built` or `reused`), the input key and the
absolute manifest path. It returns a nonzero exit code and a diagnostic on failure.
The current tool requires Linux, PHP POSIX support and Clang; the tested configuration
is Clang 18.1.3 with installed libstdc++ 13 headers. LTO tests also require `ld.lld-18`.
It reuses the compiler's existing bounded external-process executor without
starting or modifying any compiler phase.

## Definitions and package contract

[definitions/strings.json](definitions/strings.json) selects `scpp::string_t`,
construction from a byte span, copying, byte length, destruction and echo. The
[definition contract](definitions/README.md) describes the implemented vocabulary.
The default package exposes both integer/string directions through the
[conversion model](../docs/details/conversion_selection.md), plus line input,
concatenation and source-visible byte length. The [console contracts](../docs/details/runtime_console.md)
are implemented by local provider headers under `include/scpp_provider`, selected
through JSON and the configured include roots. Clang tracks them as dependencies.
[definitions/scalars.json](definitions/scalars.json) additionally exposes one
integer free function for the compiler's scalar ABI proof.

Exported symbols use [readable identity escaping](definitions/README.md#generated-symbol-identities):
`_X_` separates components, `__` represents an underscore, and `_xHH_` represents
another byte. Full provider/type/operation identities are encoded without hashes.
For example, the size type's move trait is
`rp_type_X_simple__cpp_X_size_X_move__constructible`. Type metadata publishes its
`fact_prefix`; callable metadata publishes its complete `symbol`.

The tool discovers dependencies with Clang, hashes definitions, transitive headers
(including system headers), generator code and the configured Clang binary, then
selects the package for reuse or rebuilding. A warm run performs version/dependency
inspection and hash checks only; it does not repeat AST extraction or code generation.
The package is currently one work unit: a relevant change regenerates it as a whole.

The [isolated lifecycle investigation](tests/lifecycle/README.md) tests complete
native structure operations calling separately compiled LLVM bodies. Its raw
project module is deliberately rejected by ordinary package publication because
application imports remain unresolved. It adds no production import capability.
The later [source-operation adapter probe](tests/source_operations/README.md) follows
the selected ownership boundary: complete source lifecycle stays in LLVM, and native
templates reach it through forwarding adapters and explicit payload bridges. It also
remains isolated from production request/package schemas and compiler consumption.
The planned [source/native contract](../docs/details/source_native_contract.md)
defines project requests, complete source-operation imports and acceptance without
changing production schema version 1 or its self-contained runtime-package guarantee.
The [parts 1–3 implementation plan](../docs/details/provider_family_implementation_plan.md)
describes the proposed typed family boundary and runtime-only specialization package
reuse/replacement. It is a plan, not implemented family caching or compiler demand
integration; existing isolated experiments retain their documented scope.

Published files live at the stable `package/` path inside the configured output
directory, initially `generated-runtime/package/`. Input hashes stay in
metadata and manifests; they are not directory names. `current.json` identifies
`package/manifest.json` and its `manifest_sha256`. Paths inside the manifest are
package-relative. Artifacts are:

| File | Role |
|---|---|
| `metadata.json` | Measured types, selected semantic operations, compiled ABI signatures and target |
| `abi.hpp` | Generated C++ declarations for the prepared bridge interface |
| `runtime.cpp` | Generated adaptations and layout probes for inspection/reproduction |
| `runtime.ll`, `runtime.bc` | Ordinary LLVM at `-O0`; bitcode assembled from the emitted IR |
| `runtime.lto.bc` | Full-LTO variant compiled from the same bridge at `-O2` |
| `runtime.thin.bc` | ThinLTO variant compiled from the same bridge at `-O2` |
| `type_<slot>.ast.jsonstream` | Raw Clang declarations; type metadata's `declaration_artifact` identifies the file. Slots are package-local and keep filenames short. |
| `commands.json` | Commands used to prepare and verify this package |
| `manifest.json` | Input provenance, artifact hashes, variants and matching C++ link driver |

All advertised symbols must be defined in each LLVM variant with compatible ABI
passing. A native shared-library link check rejects unresolved implementations;
its temporary output is discarded. Current bindings are header-defined and use
the selected driver's C++ runtime. Additional provider libraries/link inputs are
not implemented. Native execution happens in the focused test, not in preparation.

The selected target, standard-library headers/ABI and compatible link driver are
part of this package's context. A package is not portable across arbitrary C++
standard-library builds. Native toolchain/runtime dependencies must remain compatible;
the package does not bundle the operating system's C++ runtime.

Build and validate a complete replacement in `.preparing-<temporary-id>/`, then
move the old `package/` to `.previous-package`, move the replacement to `package/`,
and atomically replace `current.json`. These steps run under the exclusive output
lock. On publication failure, restore the previous directory; on success, remove
the backup. Files are replaced as a whole directory so removed artifacts cannot
linger. Failed generation preserves the previous package and removes private
outputs. Corrupted artifacts follow the same replacement path at the same location.

If directory replacement is interrupted, the next preparation uses the manifest
hash in `current.json` to distinguish the accepted package from an uncommitted
replacement and restore or remove the backup. If neither matches, it reports an
error without discarding the backup. This is process-interruption recovery, not a
power-loss durability guarantee. Successful builds and reuse also remove legacy
hashed package directories and their empty `packages/` parent. Unrelated entries
and symlink targets are preserved. Cleanup failures are reported explicitly and
retried on the next successful run.

Each output directory holds one selected runtime configuration. Use separate
configured output directories when multiple configurations must coexist. All
compiler consumers must hold a shared `.prepare.lock` while reading or linking
package files; preparation holds the exclusive lock through replacement and cleanup.
The directory and pointer replacements are separate filesystem operations; readers
must take the lock and verify the manifest hash rather than assume a stable path
means unchanged contents.
A failed new configuration must not be silently substituted by a consumer
with an older package merely because the old pointer is still available.

For LTO, supply prepared bitcode directly to the linker (the tests use Clang's
`-Xlinker <bitcode-path>`). Passing `.bc` as a normal Clang source input can cause
an extra compilation. LLVM owns optimized backend dependencies; these packages
cache the original provider modules, not application-specific machine code.

## Initial scope

The compiler's [inline proof](../tests/integration/runtime_inline.php) additionally
uses generic construction from declared integer arguments and read-only methods
with explicit call-scoped borrowing. Its two types declare no cleanup, verified
against actual C++ trivial destruction. See the [definition vocabulary](definitions/README.md).

The first proof covers inline storage, byte-span construction, a const method
with a direct integer result, and destruction. Generic same-type local copy construction is now proved by the
[compiler copy fixture](../tests/integration/runtime_copy.php), including managed
and cleanup-free types. The default string copy binding and echo are now consumed by the compiler
through [generic literal/output contracts](../docs/details/runtime_string_literals.md).
Named explicit integer-to-string conversion and general owned runtime-function
results are [proved](../docs/details/conversion_selection.md). The remaining
[console surface](../docs/details/runtime_console.md) is proved through existing
generic calls. The unexposed `size` type remains measured C++ `std::size_t`; the
source-visible byte-length wrapper explicitly checks conversion to language `int`.

[tests/run.php](tests/run.php) checks ordinary/full/ThinLTO native execution for
empty, embedded-zero and long strings; output reuse; a second C++ type using the
same adapters; transitive-header changes; missing methods/implementations;
unknown definition fields; failed-publication isolation; configurable paths;
damaged artifact rebuilding at the stable path; removal of obsolete files and legacy
package folders; publication rollback; and interrupted directory replacement.
Symbol checks cover reversible byte encoding, ambiguous-looking component
boundaries, provider separation, and unchanged symbols after adding definitions.
Its LTO proof retains caller bitcode, changes
the provider body, checks the changed result, and inspects saved optimized IR
for cross-module call elimination. A persistent ThinLTO cache is exercised without
claiming cache performance. Temporary test outputs are retained for inspection.

The [isolated artifact/LTO proof](../docs/planning/compiler_foundations.md#provider-artifacts-and-lto-feasibility)
is established for this standalone package and compiler-generated scalar consumers.
The larger runtime surface and production LTO integration remain open.
See [calls.md](calls.md) for the tool's process owners and execution order.

## Isolated source-type specialization proof

`requests.php` exports fixed resolved-type descriptions and demanded provider
families into ordinary preparation definitions and a generated `source_types.hpp`.
The separate `Prepared_Request` adapter verifies the package against those exact
definitions. Compiler stages and the compiler's runtime adapter are unchanged.

```sh
python3 src-runtime-preparation/tests/specializations/run.py
```

The default workspace is `generated-runtime/proofs/source-specializations`;
`--workspace` selects another location. The fixture owns this directory, preserves
stable package paths, and prevents concurrent fixture writers. Generated inputs,
LLVM callers, native baselines, build logs, optimized IR and `report.json` remain
available for inspection. Native compilation uses four concurrent jobs; timing runs
are serial after compilation and separate from allocation instrumentation.

The [proof contract and results](../docs/details/source_specialization_proof.md)
describe supported requests, measured overhead and remaining integration work.
Smart-pointer families, nontrivial source fields and compiler integration remain
outside this proof.

## Allocation owner preparation

[definitions/allocation.json](definitions/allocation.json) exposes an opaque local
owner and acquire, transfer, inspect and release calls. Native allocation policy
stays in the selected provider header; JSON declares resource effects. The compiler
consumes them through [allocation ownership](../docs/details/allocation_ownership.md),
separately from object lifetime. Typed element access uses the protocol below.
Explicit `struct_field` eligibility also supports static owning fields through
[source ownership summaries](../docs/details/owning_storage_fields.md).

## Typed prefix storage preparation

[definitions/element_storage.json](definitions/element_storage.json) declares a
one-type-argument family and its semantic operation roles. `storage.php` validates
the complete native protocol before export. The compiler adapter independently
checks measured ABI facts. Address results are internal primitive plumbing, never
ordinary source types. See [typed storage](../docs/details/typed_storage_plan.md)
for the source surface, owner separation, proof and remaining scope.

## Runtime-only family boundary

The [family process](families/calls.md) adds typed formation/operation contracts,
ordered type arguments, selected private preparation, request joins and stable
coverage replacement. The compiler-facing `Family_Adapter` accepts semantic records
only. The optional compiler bridge now services concrete type/lifecycle demands;
method demands and compatible coverage replacement now feed compiler signatures.

Use `families\Catalog::parse()` to load a catalog, then construct a
`families\specialization_request` with that catalog, family ID, ordered argument IDs,
requested operation IDs, explicit runtime identity scope and preparation context.
The context contains `clang`, `target`, `standard` and absolute `include_directories`.
Run it through `families\Preparation(new families\Store($output_root))`. The root is
configurable. Slots are `specialization-N/package/`; full exact keys live in the root
index. Requested coverage does not change specialization identity or old symbols.

The production argument subset includes declared runtime integers, accepted ordinary
managed runtime types, prepared native family specializations and explicitly authorized
source payload exports. Void is not eligible; source scalar-reference declarations
remain deferred. A formal's sole implemented contract
is `copyable_value`. Native adapters remain independent of semantic signatures.
Adding another supported family requires catalog/native bindings, not family-name
branches in the tool. The test holder demonstrates two independent ordered arguments.

Preparation holds an exclusive nonblocking reservation; live readers cause an explicit
busy diagnostic. Release readers before requesting coverage extension. A rejected
private candidate preserves the accepted package. Whole-catalog contract revisions
conservatively rebuild requested coverage; unchanged contracts union old and new
coverage. There is no cache eviction policy in this slice.

Run the standalone boundary proof with:

```sh
php src-runtime-preparation/tests/families/run.php
```

It executes generated ABI calls with ordinary/full/ThinLTO linking, validates reuse,
coverage growth, formal permissions, selected joins, locking and failed replacement.
The standalone bootstrap loads shared semantic records/validators and the Join
interface; it does not execute compiler stages or create a compiler session.

### Source family exposure

A family may declare `"language_type": {"name": "bag", "namespace": ""}`;
an ordinary operation may declare `"expose_as": {"name": "put", "namespace": ""}`.
Exposed members require a semantic `receiver` index and belong to the family scope.
Lifecycle operations remain separate roles and cannot also expose a member name.
Duplicate member names are rejected within an owner. Source names are never native
specialization identities or C++ dispatch choices.

`families\Catalog::language_bindings()` exports only explicit primitive language
mappings. Pass these with semantic definitions to `load_runtime\Family_Adapter::expose()`
when using the compiler's normalized family input API. Every provider type appearing
in a source-facing signature requires an explicit mapping, including size and void.
The compiler validates those mappings against its language catalog; physical ABI
compatibility remains the later preparation/import gate. Source registration and
symbolic member checking are implemented. Concrete type preparation and implicit
lifecycle execution work through the optional bridge. Methods using existing supported
call ABI contracts now execute, including const integer-address calls for copy-based
append. Scalar expression/conversion storage is supplied by compiler lowering.

### Optional compiler preparation bridge

After loading `bootstrap.php` and this directory's `bootstrap.php`, load
`families/compiler_bridge.php` explicitly. It is a coordinator integration, not a
compiler-semantic dependency or a standalone bootstrap side effect.

```php
$native = runtime_preparation\families\Catalog::parse($definitions, $provider_id);
$declarations = load_runtime\Family_Adapter::expose(
    array_map(static fn($family) => $family->semantic, array_values($native->families)),
    runtime_preparation\families\Catalog::language_bindings($native));
$bridge = new runtime_preparation\families\Compiler_Bridge([
    new runtime_preparation\families\compiler_provider($native, $output_root, $runtime_scope, $configuration),
]);
$session = new compile\Compiler_Session(
    runtime_package_path: $ordinary_package,
    family_declarations: $declarations,
    family_preparer: $bridge);
```

Use the same parsed catalog snapshot for exposure and preparation. `$configuration`
contains the explicit Clang/target/standard/include-directory context described above;
`$runtime_scope` names the native runtime identity scope. Preparation uses configurable
stable specialization directories, shared across sessions; compiler-local instance
names are adapter bindings and never modify those packages. The session currently
supports declared runtime scalar and nested native family arguments, implicit lifecycle operations
and methods with supported value/object-borrow and const integer-borrow ABI contracts. Source and
generic call occurrences coalesce into operation demands; a body increment may grow
coverage while preserving unchanged type/callable identity. No family-file CLI option,
source scalar-reference declarations or source-type native arguments are implied. See
the [integrated append checkpoint](../docs/details/provider_family_compiler_integration.md#gates-4-and-5-const-scalar-borrowing-and-integrated-append).

### Construction from an expiring source

An optional `lifecycle.move_construct` names an unexposed `move_construct` operation
with the same type. Preparation emits placement construction from `std::move(source)`
and validates its ABI/trait. Metadata retains a mutable call-scoped source and an owned
caller-storage result; the source remains live and destructible. This may select a
native copy constructor. It does not promise resource transfer or an empty source.
Ordinary definitions and native family catalogs use the same operation. The string
definition now exports it explicitly; other types opt in through their definitions.

### Copy assignment of live objects

An optional `lifecycle.copy_assign` names an implicit `copy_assign` operation of
the same type. For example:

```json
{"id": "string.assign", "kind": "copy_assign", "type": "string", "error_policy": "terminate"}
```

Preparation compiles native `destination = source`, checks `is_copy_assignable`
and exports a `void(ptr, ptr)` bridge. Metadata records a mutable destination
borrow at position zero and a const source borrow at position one, both call-scoped.
Both objects are live before and after successful assignment; there is no owned
result or destruction before the call. `self_assignment: native_call` explicitly
preserves native self-assignment behavior, including observable effects. The native
operation owns replacement of old contents; failure is fatal at the bridge.

The role is the same in ordinary definitions and native family catalogs. Assignment
requirements on formal parameters must be declared in family operation contracts.
Imported permission requires both the lifecycle binding and validated implementation;
the measured trait alone does not grant it. Allocation descriptors with separate
compiler-tracked resource effects remain noncopyable and nonassignable through this
implicit role. Assignment from temporary sources and move assignment remain deferred.

### Prepared native types as family arguments

Family requests accept catalog IDs or shared `native_type` records from accepted
ordinary runtime and specialization packages. Both normalize through
`families\Arguments`. `Native_Types` owns description export and decoding.
A recipe carries exact provider/type identity, native headers and prerequisite
aliases, declared baseline eligibility and preparation/dependency context. JSON is
only the persisted boundary; processing uses records.

`Runtime_Preparation::build()` exports owned opaque types into `native_types.json`,
covered by the package manifest and artifact fingerprints. Ordinary definitions
already supply native names, headers and lifecycle contracts; no extra per-type
configuration is needed. Generated family rows supply `native_preparation`
(headers, prerequisite aliases, coverage-independent contract and original context).
Imported argument rows and compiler-tracked resources/storage are not exported as
new owners. Existing packages without the native-description artifact must be
prepared again before use as family arguments. Baseline eligibility requires declared copy construction, copy assignment
and cleanup, plus the existing Clang/import validation.

The coordinator passes accepted ordinary packages explicitly to `Family_Preparer`.
The bridge indexes them together with accepted family results and revalidates native
descriptions under short leases. Compiler consumers retain canonical semantic types;
they do not decode native preparation details. Preparation contexts must match.

Generated `runtime_value` rows may contain `native_import: {provider, id}` with an
empty lifecycle map. Such a row references an existing owned type contract; it does
not claim trivial cleanup. Compiler import requires an explicit binding to the
accepted owner, matching target/storage and supported capabilities. Imported rows
are measured by Clang alongside the new specialization.

Dependencies are ready before work selection. Preparation does not recursively
invoke itself, and an outer specialization's native implementation does not have to
call inner wrapper functions. Metadata supplies all family/type bindings; names such
as `vector` never select a compiler implementation. See the
[nested boundary](../docs/details/provider_family_compiler_integration.md#nested-native-argument-boundary).

## Source-dependent project preparation

The [project boundary](project/calls.md) consumes accepted compiler source export
contracts through a typed adapter. Explicit family requests can prepare native
containers whose element is a distinct source-payload adapter. Family metadata must
authorize the profile per formal argument and each copy-in/copy-out crossing.

Project modules use the shared allocated specialization slots, reservations, private
workers, joins and publication. `project.json` records a versioned source contract and
required imports per ordinary/full/ThinLTO artifact. All variants validate source
import identities, ABI and target; generated native adapters also assert source
size/alignment/stride/offset agreement. Complete source lifecycle functions remain
unresolved compiler imports. The ordinary runtime package guarantee is unchanged:
its link must have no undefined symbols, and its importer rejects project modules.

Compiler routing, explicit project-module import, source export emission and final link
closure are now implemented for automatic source records. Configure `native_project`
on `Compiler_Session`; project keys and output roots are explicit. Nested native
families retain their transitive source obligations and project output scope.
`Native_Types::from_project_package()` requires current source contracts when reading
source-dependent native recipes; the ordinary reader still rejects those recipes.
See the [gate-4 proof](../tests/integration/source_family_execution.php).
