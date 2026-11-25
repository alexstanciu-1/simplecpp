# Emit llvm call map
Doc Status: supporting

Caller: Phases::run_emission(), functions before modules. body.php owns Emission_Worker with
instruction/call/terminator handlers. modules.php owns Module_Worker. join.php and
module_join.php accept their respective outputs; utilities/module_validity.php owns reuse
checks. data/ owns function/module results.

The following are ordered lifecycle calls, not calls between siblings.

```text
LLVM_Emitter                                  main_emit_llvm.php
  init() -> [action] select fixed lowered functions and changed source lifecycle plans
  run()
    -> Emission_Worker::emit() [each function]
    -> Lifecycle_Emission::emit() [each selected type operation]     lifecycle.php
  finalize()
    -> Lifecycle_Emission_Join::join()                              lifecycle_join.php
    -> Emission_Join::join()
  result() => Emitted_Function_Set

Module_Assembler                                  main_assemble_modules.php
  init() -> [action] select fixed module tasks
  run() -> Module_Worker::assemble() [each task]
  finalize() -> Module_Join::join()
  result() => Emitted_Program
```

status() and supports_run() are always available. Result/store access requires
finished status; invalid timing throws without changing the state.

Call instructions and external declarations preserve prepared integer ABI
extensions on results/parameters. Imported bodies are linked from the provider
module; they never enter source-function emission.

Opaque slots use measured size/alignment. Borrow instructions select prepared opaque,
record or integer slot addresses without loading the borrowed value. Scalar argument
temporaries use ordinary typed slots and stores selected by lowering; emission makes
no new lifetime or target decisions. Caller-storage results add a hidden
pointer and an ABI void return; declarations and calls use the same prepared
parameter/result plan through LLVM_Types.

Call_Emission::emit_call() and emit_destroy() share emit_abi_call(). Emitted references
and module imports use exact-link-name abi_target records for both source and implicit
runtime calls. Destruction consumes a prepared destination; lifetime policy remains in analysis.

Call_Emission::emit_construct() checks independent same-type source and
destination storage and uses emit_abi_call() with the prepared two-address target.
A null target is permitted only for trivial value copying, emitted as a load/store.
Call_Emission::emit_copy_assign() validates the selected operation and destination
address before the same ABI emission path. Assignment permits identical source
and destination; policy belongs to its complete operation.

Instruction_Emission::emit_byte_literal() emits function-owned private module
constants named by callable/value IDs. Call_Emission::emit_call() expands span
operands according to prepared parameter contracts, then uses emit_abi_call().
The existing function/module joins own publication and incremental replacement.

Location_Emission in handlers/locations.php supplies Emission_Worker::emit_address()
and storage_operand(). Prepared paths retain target offsets and LLVM element stride;
checked_index() expands the bounds guard and fail-stop trap before memory access.
Record storage spelling, alignment and offsets come from accepted backend layouts.

Generated lifecycle definitions have type-operation provenance, separate from
source-function AST provenance. Their selected workers consume accepted member
plans and layout/ABI facts. Arrays emit one loop; nested records call their complete
operations. The emission join retains unchanged definitions. Module_Worker places
this set once in the native-entry module and merges its prepared references with
ordinary function references. Other file modules import the same ABI targets.
Default construction and projected field borrowing share the normal instruction
emitter; field cleanup is owned by the containing operation, not local cleanup rows.

Custom constructor bodies are ordinary checked functions, called after field
initialization; custom destructor bodies are called before reverse field cleanup.
Custom copy bodies receive destination and const source after field defaults;
member roles control address/load/call emission independently of the enclosing ABI.
Custom assignment bodies receive two live objects with no automatic member plan;
automatic assignment emits field operations in declaration/array order.
The accepted lifecycle_order supplies body placement and array iteration direction;
member lists are already ordered. Emission does not independently classify custom
copy/assignment or destructor ordering. The role's has_source() supplies source
presence; LLVM_Types remains responsible for its physical address ABI.
An early source return therefore reaches the complete operation's remaining steps.
Lifecycle_Emission::references() derives the exact field/body imports; emission and
Lifecycle_Emission_Join share that fixed-plan validation without re-emitting IR.

Storage_Emission (handlers/storage.php) runs on Emission_Worker. Checked storage
allocation/count/release/transfer calls expand through emit_storage_call(). Paired
`emit_storage_begin()` / `emit_storage_end()` prepare and retire an internal typed
destination around ordinary lifecycle instructions; arguments use the shared
Call_Emission::call_arguments() exactly once. Location_Emission delegates dynamic
element projections to storage_element_address(). Both use storage_native() and
accepted native ABI targets. Element initialization precedes native count commit;
pop checks the last live address before destruction and decrements afterward.
Index guards compare against constructed count before obtaining the address.

`Instruction_Emission::emit_binary()` (handlers/instructions.php) consumes the
prepared binary primitive. Wrapping addition retains the operand type; signed or
unsigned less-than emits `icmp` with an independent `i1` result.

Location_Emission::slot_operand() maps `incoming_result` to the hidden first argument.
Semantic parameter positions account for that prefix. Call_Emission uses the same
slot mapping for ordinary calls and source construction, so forwarding can pass the
incoming destination directly. Terminator_Emission emits the prepared physical void
return only after lowered result construction and cleanup.

## Stable source exports

`LLVM_Emitter` selects lifecycle work from `Backend_Context::source_operations`, the
union of existing source operations and required native export implementations.
`Lifecycle_Emission_Join` accepts the same membership. The selected entry-module task
owns all generated lifecycle definitions and stable export wrappers:

```text
Module_Worker::module_text()
  -> Source_Export_Emission::entries()                   source_exports.php
       -> [action] forward stable entries to emitted complete source operations
Module_Join::join()
  -> Source_Export_Emission::closure()
       -> [action] require one compatible emitted definition per native source import
  => Emitted_Program -> existing native build/link
```

The module worker has fixed backend and lifecycle inputs and private text output.
Wrappers do not traverse fields. Source import ABI/effects/provenance acceptance precedes
emission; closure additionally checks the actual selected module definitions.
