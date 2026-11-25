# Lowering call map
Doc Status: supporting

Compiler_Session runs lowering after backend preparation. Phases::run_lowering()
selects analyzed bodies with their fixed accepted Backend_Context. Lowering owns
execution plans, storage slots and instructions; it does not import metadata or
probe the target. [Backend preparation](../prepare_backend/calls.md) owns those facts.

The following are lifecycle calls in order, not calls between siblings.

```text
Lowerer                                      main_lower.php
  init() -> [action] select fixed analyzed bodies
  run() -> Lowering_Worker::lower()           body.php [each task]
  finalize() -> Lowering_Join::join()         join.php
  result() => Lowered_Set                     data/store.php

Native_Entry                                 main_native_entry.php
  init() -> [action] validate callable and native result width
  run() -> prepare_plan() [reuse or adapt integer result]
  finalize() -> [action] complete native_entry_plan
  result() => native_entry_plan               data/structures.php
```

Lowering_Worker owns private value/slot identities and lifetime cursors. Handler
traits group statements, expressions, local storage and control flow. Construction
writes its selected destination; copying consumes the prepared lifecycle target.
Local_Lowering::assign_local() emits copy_assign for the prepared two-address
operation, retaining root/projected destinations; value assignment stays a store.
Zero initialization stores its checked constant without requiring copy permission.
Cleanup instructions consume the exact analyzed obligations. No source lookup
occurs for implicit lifecycle calls.

`storage_slot::incoming_parameter` distinguishes borrowed caller storage from owned
allocations. Parameter entry copies scalar values but binds record addresses directly;
borrowed slots never acquire destruction obligations.

`storage_address` carries a root slot, ordered field/index projections and a body-local
address identity for projected locations. Internal native destinations have slot ID
zero and a positive address ID; they are never source-visible pointers. Index operands refer to lowered values.
Loads/stores use accepted layout facts; emission calculates addresses and bounds checks
through the common location path.
`data/structures.php` contains lowering inputs, instructions, storage locations and
entry plans. `data/result.php` owns immutable body results; `data/store.php` indexes them.

`Expression_Lowering::lower_operation()` asks `Binary_Operations::select()`
(binary_operations.php) to validate the selected semantic operation and independent
result type. Operand consumption stays in the common traversal; `binary_operands`
carries the native primitive to emission.

`Expression_Lowering::lower_call()` calls `argument_storage()` for each prepared
parameter. Borrowed operands with storage retain their exact address. A const integer
borrow without storage gets a typed slot, a store of its already evaluated scalar and
a borrow operand. Scalar and address have separate lowered IDs with the same checked
provenance; the original value mapping is not rebound. No source expression is evaluated
twice, and no family-specific lowering is involved.

Analysis ends the semantic argument access at the call. These cleanup-free slots use
the existing function-entry allocation path, so physical stack storage lasts until
function exit and repeated loop calls reuse the same slot. No lifetime intrinsic or
new destruction obligation is introduced. Selected lowering tasks own their slots and
values privately; only `Lowering_Join` publishes results.

Owned returns share construction destinations with initialized locals.
Local_Lowering::prepare_storage_slots() reserves `incoming_result` storage, then maps
fresh returned values to it. Statement_Lowering::write_result() establishes the result
using a store or Local_Lowering::construct_from() before emitting cleanup; a forwarded
call has already constructed directly. The physical return is void. Incoming result
storage is never allocated, default-initialized or destroyed by the callee.
Lowering_Worker also consumes analyzed scope exits at block entry, before its statements.

## Dynamic element lifecycle

```text
Expression_Lowering::lower_call()                handlers/expressions.php
  -> Storage_Lowering::lower_storage_transition() handlers/storage.php [push/pop]
       -> Local_Lowering::construct_at() / lifecycle_target() handlers/locals.php
       => begin; ordinary copy/destroy; end        data/structures.php
```

Construction/destruction operands share `storage_address` with assignment. The
prepared type selects exact source/runtime operations before emission. Trivial copying
uses the same construction instruction without an ABI target; cleanup-free pop emits
no destructor. The begin/end payload retains the one call-argument range and one
body-local internal address ID. Arguments are evaluated once, and native live count
changes only after the selected element operation completes.
