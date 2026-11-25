# Provider-family preparation
Doc Status: supporting

```text
Catalog::parse()                              catalog.php
  -> [action] parse declared type positions into shared records; retain native bindings separately
  -> type_model\Family_Contracts::validate()   ../../src/04_analyze/type_model/family_contracts.php

Preparation::run()                            prepare.php
  -> select()
     -> Requests::arguments(); coverage()      requests.php
     -> Arguments::resolve()                  arguments.php [exact types and local import rows]
     -> Store::locate()                        store.php [short exact-key index lock]
     -> Package_Reservation::acquire()          ../reservation.php [nonblocking package lock]
     -> Store::retained(); Requests::export()
     -> [action] fix input files, receipt, coverage and input snapshots
  -> execute(task)
     -> Runtime_Preparation::stage()           ../prepare.php [same concrete generator]
     -> preparation_result                    structures.php [private candidate]
  -> Join::join(results)                       join.php
     -> Package_Candidate::validate()          ../candidate.php [sealed metadata/ABI validation]
     -> accepted_specialization               structures.php
  -> Package_Candidate::publish(); release()
```

The worker reads fixed selected inputs and writes a private package. The join checks
selection identity, input snapshots and exact authenticated request coverage before
returning accepted candidates. Publication rechecks inputs, artifacts and the previous
pointer. A failed batch publishes nothing through this coordinator; callers of the
explicit batch interface must release all candidates on rejection. No worker changes
compiler state. Execution is serial; these are the future scheduling units.

`Store` owns root-local monotonic directory slots. Exact scope/family/ordered arguments/
configuration form the key. Whole catalog/native-binding revisions conservatively
invalidate coverage; compatible requests union operations with lifecycle dependencies.
Artifacts and implementation/header changes are freshness checks, not entity identity.

One package reservation spans selection, generation, acceptance and replacement.
The root-index lock is released first. Busy readers fail preparation immediately;
consumers release their lease before requesting more coverage. Separate tasks have
separate files and results. No nested dependency preparation or lock upgrade exists.

The production path accepts catalog scalar arguments and accepted native types from
ordinary or specialization packages. Arguments::declarations() supplies prerequisite
native aliases. Preparation fixes generated `native_preparation` facts on its own
type row; Runtime_Preparation::build() -> Native_Types::export() (../native_types.php)
writes the common authenticated `native_types.json` artifact. The older plain
source-record experiment uses the same Catalog/Requests owners through
`Specialization_Request::export()` but remains isolated from compiler integration.
JSON/array projection occurs only on the existing concrete Definitions boundary.

Source exposure: `Catalog::parse()` retains optional family `language_type` and member
`expose_as` as typed semantic references. `Catalog::language_bindings()` exports exact
provider/type mappings without native implementation fields. Compiler adapters can
register these declarations before specialization; Requests still exports native
operation IDs independently of source member names.

## Optional compiler coordinator bridge

Load `compiler_bridge.php` explicitly after both bootstraps. `Compiler_Bridge`
implements `load_runtime\Family_Preparer`; its fixed `compiler_provider` contexts
retain native catalogs, configurable roots and explicit identity/configuration scopes.
It matches the exact registered semantic definition, canonical scalar mappings and
accepted argument definitions. type_bindings() indexes owned opaque types from the
fixed ordinary packages and accepted family results. native_argument() delegates to
Native_Types::from_package() to read authenticated descriptions under
short leases before reserving any output. It compares type-only header dependencies
with accepted manifest inputs and fixes those snapshots in selected tasks.
A prepare()-local memo shares each validated recipe/header read across repeated
arguments in that fixed frontier. It holds no leases and does not survive the call;
each task still revalidates its snapshots. It selects every task in a frontier,
executes private workers, then uses the
native `Join` before publication. Reservations release on every exit. Publication is
per package; this does not promise atomic rollback across several published packages.

After publication, the bridge briefly opens each package through `Package_Adapter`
with a compiler-owned internal type-name binding and releases that read lease.
It returns private `family_preparation_result` rows for compiler join acceptance. The
compiler acquires and revalidates final read leases separately before linking.
Coverage includes the family's lifecycle roles and explicitly demanded method IDs.
`copy_assign` has two semantic self-type borrows (mutable destination, const source)
and a void result. Catalog parsing fixes those positions; Requests exports the
ordinary assignment operation, so native generation and compiler import reuse the
same implementation as non-family runtime types.
Compiler requests state the complete coverage needed by current callers; missing
coverage controls selection, not the acceptance contract.
Previously imported method bindings remain available when a later request extends
coverage. Package import validates internal type/operation bindings without rewriting
shared metadata. Native workers still use the existing selection/execution/join protocol. `Requests::export()` exposes default construction only when the declared
constructor has no semantic parameters, independently of the element baseline.

Compiler imports bind package-local argument rows to accepted owner types. These
references introduce no second lifecycle or concrete definition. Preparation contexts
must match. Source arguments use accepted export contracts and explicit payload bindings.

## Explicit project modules

A formal parameter may declare `source_profiles: ["inline_source_payload_v1"]`.
`Catalog` retains this native compatibility separately from semantic generic
permissions. A borrowed formal argument additionally requires
`source_payload: "copy_in"`; an owned formal result requires
`source_payload_result: "copy_out"`. These permissions are checked before generation,
independently of concrete type names or equal storage layouts.

`Arguments` normalizes source-payload rows through the same local-ID allocation owner
as native imports, while preserving their distinct representation crossing.
`Module::from_arguments()` merges source obligations and checks project scope.
`Preparation::execute()` supplies that fixed contract to the common Runtime_Preparation
worker; `Join` checks exact project context as well as coverage and input snapshots.
The [project adapter map](../project/calls.md) documents generation and acceptance.
`Compiler_Bridge::exports()` merges direct and transitive source exports before native
selection; `project()` selects the declared project namespace/output root. Source
arguments use `Source_Adapter::argument()`; native arguments use ordinary or explicit
project-native descriptions under a reader lease. `project_binding()` associates the
accepted portable receipt with current compiler exports while the publication reservation
is still exclusive. Common package import rechecks it under a shared reservation;
family acceptance and final reader reservations retain those obligations through linking.
