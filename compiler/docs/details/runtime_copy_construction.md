# Inline runtime copy construction
Doc Status: supporting

Status: implemented for same-type local declaration initialization, through the
common preparation, analysis and LLVM pipeline. This extends
[inline storage](inline_runtime_storage.md) and [runtime cleanup](runtime_cleanup.md).

## Behavior and boundaries

A declaration initialized from a live local may copy-construct a new inline runtime
object when its type explicitly exposes that capability. The source stays live
and owned. The destination gets distinct aligned storage and its own normal
scope/return cleanup obligations. Constructor-call results continue to construct
directly into their destination; scalar copying keeps its existing behavior.

Copy assignment, moves/ownership transfer, owned source-function parameters and
returns, smart pointers and exception unwinding remain separate capabilities.
A C++ copyable trait alone grants no language copy capability. The default string package now declares its copy binding for the
[literal/echo consumer](runtime_string_literals.md). The independent copy proof
also uses locally declared types without provider names in compiler code.

## Metadata and shared contracts

An inline type may add `"copy_construct": "item.copy"` to its `lifecycle` object.
The referenced operation declares `id`, `kind: "copy_construct"`, its owning `type`
and `error_policy: "terminate"`; it omits `expose_as`. See the
[definition vocabulary](../../src-runtime-preparation/definitions/README.md).

`runtime_preparation::Bridge::copy_construct()` generates placement construction
from a const C++ reference. The physical signature is
`void(destination_address, source_address)`. The source is borrowed only for the
call and remains live afterwards; aligned uninitialized destination storage
becomes an owned live object on success. Exceptions are caught by the bridge and
terminate, matching the current runtime boundary policy. Clang validates the copy
constructor and measured ABI for every exported module variant.

`Package_Adapter::lifecycle_operation()` validates role, exact type, ownership,
source borrow scope, storage transitions, error boundary and physical positions.
The shared `lifetime_contract` carries `copy_kind::construct` and one
`runtime_lifecycle_operation` reference for its copy constructor. Destruction and
copying use the same record shape with explicit `lifecycle_operation_kind` roles.
No implementation data is repeated on individual local/value rows.

## Processing owners

| Owner / file | Responsibility |
|---|---|
| [check_bodies/handlers/statements.php](../../src/04_analyze/check_bodies/handlers/statements.php), `check_local_write()` | Select `local_initialization::value_copy`, `direct_construct` or `copy_construct`; only live-local borrowing with an authorized same-type contract can enter the copy path. Assignment remains rejected. |
| [analyze_lifetimes/handlers/locals.php](../../src/04_analyze/analyze_lifetimes/handlers/locals.php), `validate_local_target()` | Validate the selected initialization capability. Value analysis proves the source is initialized/live; `copy_source` ends access without consuming ownership. Statement analysis starts the destination and ordinary cleanup analysis schedules its destruction. |
| [prepare_backend/callable.php](../../src/05_generate_code/prepare_backend/callable.php), `prepare_lifecycle()` | Prepare one selected implicit operation using fixed operation/configuration inputs and private output. Destruction has one address; copying has two. |
| [prepare_backend/lifecycle_join.php](../../src/05_generate_code/prepare_backend/lifecycle_join.php), `Lifecycle_Join::join()` | Accept complete selected results, reject stale provenance/ABI, reuse current unselected targets and remove obsolete contributions. The coordinator retains tool verification. |
| [lower/handlers/locals.php](../../src/05_generate_code/lower/handlers/locals.php), `copy_local()` | Emit an explicit `copy_construct` instruction with destination slot, source value and shared prepared target. Destination storage differs from the source. |
| [emit_llvm/handlers/calls.php](../../src/05_generate_code/emit_llvm/handlers/calls.php), `emit_copy_construct()` | Validate storage/type associations and pass the two addresses through the common ABI formatter. Opaque objects never use scalar byte loads/stores. |

The initialization boolean was replaced by a small enum because direct result
construction and copying an existing local have different ownership effects.
The destruction-only preparation contracts were generalized around the shared
implicit lifecycle-operation concept; there is no copy-only scheduler.

## Verification

The [copy integration fixture](../../tests/integration/runtime_copy.php)
compares native execution against C++ copy constructors for two metadata-defined
types: an over-aligned, self-address-checked heap owner and a cleanup-free type
with a custom copy constructor. It proves independent resources, source use after
copying, copies of copies, nested scopes, loops and both early/normal return paths.
Managed construction/copy counts equal destruction counts.

One full build and one managed-body increment add a copy under unchanged
contracts. The changed analysis, plan, emission and object are replaced; unchanged
callers and unrelated managed code retain their results and native objects.
Prior snapshots remain unchanged. Reversed analysis/lowering workers reproduce
the same exports. Shared lifecycle preparation tests cover mixed operation roles,
full/empty/partial selection, arbitrary completion order, purity, removal, and
rejection of missing, duplicated or stale results/configuration/provider contracts.

Negative cases reject object assignment, copying without an explicit binding,
and corrupted ownership/physical ABI metadata. Existing cleanup, scalar ABI,
ordinary/full/ThinLTO preparation and compiler regressions remain applicable.
