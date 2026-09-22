# Load runtime call map
Doc Status: supporting

Compiler_Session loads/reuses the catalog after the manifest. utilities/catalog_syntax.php and
handlers/type_definitions.php own validation and definitions; type_model/data/catalog.php owns the catalog.
input_path() is a pure configuration query.

The following are ordered lifecycle calls, not calls between siblings.

```text
Language_Types                                  main_load_runtime.php
  init() -> [action] resolve catalog path
  run() -> reuse catalog or Catalog_Syntax::parse()
  finalize() -> [action] complete Type_Catalog
  result() => Type_Catalog
```

status() and supports_run() are always available. Result/store access requires
finished status; invalid timing throws without changing the state.

## Prepared package adapter

After the catalog, Compiler_Session optionally calls `Runtime_Import::open()`
(runtime_import.php) with one or more explicit prepared package directories.

```text
Runtime_Import::open()
  select() -> [action] canonicalize paths; fix tasks against one base catalog
  read() [each task] -> Package_Adapter::open() -> Runtime_Lease
  Input_Join::join() -> [action] validate complete results and compose Runtime_Input_Set
  => Runtime_Input_Lease [early snapshot; retained to link if no family service, otherwise released before preparation]
```

The adapter alone reads package JSON and verifies artifacts under `.prepare.lock`.
Each worker returns a private `runtime_package_result`; the join checks selected
provenance, live leases, common target/link context, source exposures and native link
identities. Provider namespaces must be distinct across packages; aliases of the
same directory are deduplicated before reading. Backend operation lookup uses the
exact provider/operation pair. Equal layouts never establish type identity.
`Callable_Bindings::validate()` (utilities/callable_bindings.php) checks language-role
and conversion uniqueness for both a package and the composed set.

`data/inputs.php` owns fixed tasks/results, normalized set queries and the aggregate
lease. The set exposes each required module once and protects every input path.
Unchanged verified package objects and base catalog reuse the previous set exactly;
changed membership/contracts conservatively invalidate downstream preparation.
This import service does not generate packages or schedule native specializations.

Package_Adapter composes verified opaque named definitions with the base catalog.
Runtime_Package retains both catalogs: unchanged inputs reuse that composition;
base/provider changes invalidate it before body workers run. Passing modes and
caller-storage results are normalized here, independently of serialized JSON.

Package_Adapter::lifetime() validates implicit destruction semantics and ABI against
the owning type. Imported bindings retain runtime_lifecycle_operation, including explicit
no-argument default construction; compiler-composed operations have separate provenance. These imported operations
are queried independently of the source callable catalog.

Copy construction: Package_Adapter::lifetime() imports the optional copy binding
through lifecycle_operation(), validating the source borrow and destination
ownership separately from destruction. Neither operation creates a source symbol.

Byte literals/output: callables() delegates to call_result(), call_parameter() and
call_language_binding(), then validates package-wide role uniqueness. A span
consumes two physical ABI positions; ordinary arguments consume one.

Conversions: call_conversion() imports the explicit purpose on one-input calls;
Callable_Bindings::validate() rejects duplicate conversion keys. call_result()
also accepts owned free-function results with validated caller-storage transitions.

Record_Import (handlers/records.php, composed by Package_Adapter) normalizes complete
plain native records into Type_Catalog record inputs. Raw field offsets, scalar bindings
and explicit lifetime/validation permissions are checked here; canonical IDs are allocated
later by the shared type-definition join. Free functions can borrow their const addresses
for one call. Imported signature parameters/results retain exact named_type_reference
records, not canonical definitions or foreign field ranges.

## Adapter responsibilities

`package_adapter.php` owns the lease, artifact verification and composition entry.
Its private traits run on that owner, without extra workers or mutable state:

- `handlers/package_types.php`: measured storage and language type bindings.
- `handlers/records.php`: normalized structural declarations.
- `handlers/lifecycle.php`: construction, assignment and destruction implementation contracts.
- `handlers/callables.php`: callable results, parameters and passing.
- `handlers/storage.php`: typed-family protocol, internal address results and native primitive ABIs.
- `handlers/resources.php`: resource permissions and complete allocation effects.
- `handlers/bindings.php`: language roles and conversion capabilities.
- `handlers/package_syntax.php`: shared field and physical ABI validation.

`handlers/type_definitions.php` remains the language-catalog parser's handler; it
is separate from prepared-package type import. Shared semantic records belong to
[type_model](../../04_analyze/type_model/calls.md), not this importer.

Typed families retain their descriptor as an implementation contract, excluding
its unspecialized name from concrete catalog types. Declaration collection exposes
the configured generic family and operation names; instance acceptance supplies
the static element contract. Native address rows have no source language binding.

## Standalone family boundary

`Family_Adapter::accept()` (`family_adapter.php`) accepts shared semantic family
records through `type_model\Family_Contracts::validate()` and indexes exact family
identities. It consumes no C++ bindings and schedules no preparation. The standalone family proof tests this ABI-free boundary.
Ordinary imported operations now pair `semantic_signature` with `runtime_callable_abi`;
checking reads semantic passing/production and backend preparation reads the ABI.

`Family_Adapter::expose()` creates source payloads from exposed definitions and exact
provider/type-to-language-name mappings. `validate_exposures()` validates their
contracts, mappings and name uniqueness against the current catalog before collection.
`Compiler_Session` accepts a fixed `family_declarations` list; collection registers
families/methods in its ordinary symbol store. The compiler does not read native C++
bindings or invoke preparation here. No family-file CLI loading is introduced at this
gate; the session API supplies the native preparation service described below.

## Demanded family type preparation

```text
Concrete_Preparation::prepare_family_types()    ../../04_analyze/resolve_types/main_prepare_concrete.php
  -> Family_Preparation::prepare_types()         prepare_families.php
     -> Family_Preparer::prepare()         data/family_preparation.php [service contract]
        -> families\Compiler_Bridge             ../../../src-runtime-preparation/families/compiler_bridge.php
           -> [action] select native tasks; execute; native Join; publish accepted packages
           -> Package_Adapter::open() [short read lease; compiler-owned type bindings]
     -> Family_Preparation_Join::join()                 family_preparation_join.php
     => accepted instance/package/type associations
Compiler_Session::compile()
  -> Runtime_Import::reserve()                   runtime_import.php
     -> Package_Adapter::open() [each accepted snapshot; reject any replacement]
     -> Input_Join::join() => final Runtime_Input_Lease [held through linking]
```

`package_bindings` carries typed internal names, separate from serialized
source exposure. The adapter checks these against measured package types and
retains them for final revalidation/reuse; shared generated JSON is not rewritten.
`Family_Preparation_Join` accepts complete selected provenance and exact internal bindings
before the type coordinator materializes any result. Native receipt validation stays
with the preparation tool's join. Neither join allocates compiler canonical types.
After concrete discovery, `Family_Preparation::prepare_methods()` groups method contexts
by owning instance. Missing coverage selects a fixed batch; each selected request
states all methods needed by current callers, including already available methods.
The join rejects incomplete replacements before any association changes and returns exact
callable-ID associations for signature workers. `Family_Operations::validate()` checks
ordered type/passing/result contracts against the original semantic operation; package
membership and requested coverage are checked by `Family_Preparation_Join`.

`package_bindings` now also supplies compiler-internal callable names. Package import
retains unchanged normalized types at the same exact package/type/binding identity;
layout equality alone is insufficient. It reuses equal callable contracts while
returning a new package for changed artifacts/coverage. Incompatible type revisions
require a fresh type context and never rewrite retained snapshots. Full rebuilds omit
prior associations; normal body increments may extend compatible coverage.
`Callable_Import::call_parameter()` also accepts integer `const_address` parameters
with borrowed ownership and call scope. Pointer ABI validation is shared with object
borrows; mutable scalar-address metadata remains unsupported. Checking chooses the
semantic access, and lowering supplies storage for scalar expressions/conversions.

Lifecycle_Import accepts explicitly prepared `move_construct` operations with mutable
call-scoped source addresses, distinct caller destination and a still-live source.
Package lifecycle enumeration includes these operations for ordinary/family packages;
no capability is inferred from `move_constructible` without its bridge implementation.

`Lifecycle_Import::lifetime()` also imports explicit `copy_assign` through
`lifecycle_operation()` and `assignment_contract()`. Acceptance requires a live mutable
destination, a live const source, same-type call-scoped borrows, native self-assignment,
no semantic result and ordinary two-pointer/void ABI. The normalized operation fills
the existing `lifetime_contract::copy_assignment`; package enumeration includes it
in the same fixed lifecycle preparation batches as construction and cleanup.

Nested native arguments use `package_bindings::imports` and `runtime_type_import`.
Package_Adapter::types() validates exact identity, measured storage and capabilities;
read_package() checks the target before retaining the accepted definition.
Family_Preparation_Join::join() checks argument provenance. Input_Join::packages()
requires each import owner in the final package closure; compose() and
Runtime_Package::lifecycle_operations() omit imported declarations/implementations.
Family_Preparation::prepare_types() supplies accepted current results to later batches.

Family_Preparation captures accepted ordinary Runtime_Package inputs alongside the
base catalog and prior family results. Both prepare_types() and prepare_methods()
pass those fixed inputs to Family_Preparer::prepare(). The native bridge owns native
description reads; semantic workers and shared type records contain no such recipes.

## Project modules and source payloads

```text
Compiler_Bridge (native adapter)
  -> Package_Adapter::open(..., project_binding)          package_adapter.php
       -> Project_Import::validate()                     project_import.php
       -> Package_Adapter::types() -> Project_Import::source_type()
       -> Package_Adapter::callables()                   explicit copy-in/out checks
  -> Family_Preparation_Join::join()                     canonical source/native arguments
Runtime_Import::reserve()
  -> Package_Adapter::open(..., retained project_binding)
  -> Input_Join::join() -> Runtime_Input_Set             held through final native link
```

`data/project.php` owns the authenticated receipt/current-export association.
`Runtime_Package::source_imports` retains `Project_Import::validate()` output:
exact link symbols associated with accepted source operations. Backend preparation
consumes these normalized obligations without parsing the receipt again. Every
package reopen still validates the on-disk receipt before reusing a snapshot.
`package_bindings::sources` maps local payload IDs to existing compiler source exports.
Source rows use record storage and retain the same source definition; they introduce
neither native lifecycle ownership nor another catalog type. Dependent family results
resolve production from their accepted type argument. An ordinary package open without
explicit project context continues to reject unresolved project modules.
