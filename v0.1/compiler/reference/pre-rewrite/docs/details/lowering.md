# Instruction lowering
Doc Status: supporting

Status: implemented in the PHP prototype for scalar constants, positional
calls/parameters, returns, integer addition/comparison, branches/loops and initialized scalar
locals with reads/assignments. [LLVM emission and native builds](native_executable.md) now
consume these results; `--output` requests a published executable. Without it,
inspection stops before `build_native`. Optimization remains deferred.

Incoming parameters initialize ordinary local slots. Arguments are lowered left
to right through the checked value/call graph, using explicit cursors rather
than recursive PHP calls. The worker consumes each `argument_copy` fact at its
identified call, after all arguments are ready. Types remain shared; identity and same-family, same-signed integer widening
are supported for copyable, no-cleanup scalars.

The [handler organization and extension points](code_generation_handlers.md#lowering)
describe statement, expression, local and control-flow handlers, worker state
and the process-local data and utility folders.

## Input and output

[`Lowering_Worker`](../../src/05_generate_code/lower/body.php) reads one fixed
`lowering_input`: an analyzed body and the shared, prepared backend context.
It follows analyzed reachable block ranges and the shared checked-expression
traversal. [Operation/flow contracts](operations_and_control_flow.md) define the inputs.
It neither revisits syntax nor resolves language types or backend policy.
The manifest entry and named functions use this same worker.

[`Lowered_Body`](../../src/05_generate_code/lower/data/result.php) owns flat datasets of
values, local slots, argument value IDs, instructions and blocks. Value/slot/block IDs are separate
one-based indexes; zero means no value. Each block has a zero-based instruction
range and a separate return, jump or integer-condition branch terminator.
Source node IDs retain provenance through the input's exact AST snapshot.

- A constant retains its exact checked decimal value and shared type ID. This
  value-producing instruction does not require a future machine instruction:
  LLVM emission can use an immediate operand.
- A conversion consumes an earlier value and produces a destination-typed value.
  Lowering translates the resolved integer-widening operation into `sext` or
  `zext` using shared signedness facts. `conversion_operands` records the input
  lowered value ID and prepared primitive; identity creates no instruction.
- A parameter instruction supplies its one-based incoming position and a value
  with `source_value_id = 0` (there is no checked temporary for entry). An ordinary
  store initializes its binding slot; subsequent reads and writes use local storage.
- A call's `call_operands` retains the shared `callable_binding` and a zero-based
  start/count in `Lowered_Body.arguments`, one flat list of lowered value IDs.
  Completed calls append contiguous ranges. Each result is produced once; void
  calls remain instructions with result ID zero. A previously loaded argument
  stays available through nested calls, without reloading its source local.
- Each reached local has one `local_slot` containing its source local ID and
  shared type ID. A load references that slot and produces a fresh value.
  Initialization and assignment use the same store instruction with slot/value
  operands and no result. RHS evaluation precedes the write, including self-assignment.
- A return references a produced value, or zero for void. Permitted void
  fallthrough becomes an explicit void return anchored to the body node.

The worker consumes analyzed lifetime ends. Discarded scalar results keep their
production but need no cleanup. A destination-typed return with a verified value-copy
end uses the prepared direct scalar return contract; no storage or separate
memory-copy instruction is necessary. Missing/inconsistent facts fail explicitly.
Unreachable blocks produce neither instructions nor slots. The worker consumes
`local_copy` facts for writes and local initialization/exit facts. Its slot index
maps each reached binding to one physical slot; loop initialization executes
again at its original statement. Semantic liveness remains owned by analysis.
No source tree walk is needed.

LLVM definitions and cross-file imports use the binding's ordered
`callable_parameter` contracts. Incoming operands are `%p1`, `%p2`, etc.; calls
supply typed values from their lowered operand range using the same contracts.
LLVM emission allocates slots once in the entry block, then emits typed
loads/stores in instruction order. Scalar slots need no destruction; their
physical stack storage lasts until function exit even when a lexical lifetime
ends earlier. No slot reuse, promotion or lifetime intrinsics are introduced.
The fixed target DataLayout supplies the alloca address space; textual IR omits
alignment so LLVM supplies target alignment rather than host-PHP assumptions.
See the [LLVM memory instructions](https://llvm.org/docs/LangRef.html#memory-access-and-addressing-operations).
Integer addition lowers to wrapping `add`; ordered `<` lowers to signed/unsigned
`icmp` with an independent boolean result. [Binary operation contracts](binary_operations.md)
share the same operand/lifetime traversal. Conditions lower to comparison with
zero followed by a branch. Locals crossing joins use their existing slots.
Managed cleanup, foreign ABI adaptation and broader conversions remain later work.

## Selection and acceptance

[`Lowerer`](../../src/05_generate_code/lower/main_lower.php) selects new/stale bodies, or all
current bodies under `full_rebuild`, before work runs. Each worker has private
output. The join checks exact task/input identity, completeness and duplicates,
orders results by current callables, and excludes removed owners independently
of the full flag. The session retains lowered bodies inside its complete
`observed` snapshot, replaced only after every requested stage succeeds.

Unchanged analyzed-body and backend-context identities share the previous
`Lowered_Body`. Emission reads `backend_context()` to verify the exact shared
context without accessing lowering's retained input layout.
A body-only edit replaces that body's lowering; callers can
reuse their instructions when its callable contract is unchanged. Any backend
context replacement conservatively selects all lowered bodies, including when
one binding changes. Finer dependency selection is deferred. These are retained
stage results; [native publication](native_executable.md) now admits body-only
edits and rebuilds the complete module when needed.

## Inspection and proof

`--debug=json` exports `lowered`: callable identity, entry block, flat values/slots,
instructions and terminators. Shared definitions/configurations are not repeated
inside every instruction. [`lowering.php`](../../tests/05_generate_code/lower/lowering.php)
checks actual plans and evaluates composed calls with a test-only interpreter,
including order, void calls, unreachable exclusion and body edits. It also proves
fixed-input purity, out-of-order joins, stale/incomplete-result rejection, shared
reuse, failure/repair, backend invalidation, removal, unfamiliar type names/widths,
large bodies and fresh-build equivalence. CLI simulation checks both exported
runs. The interpreter is test evidence for lowered semantics, not a compiler
execution path or evidence of native code generation.

[Local lowering proof](../../tests/features/local_lowering.php) builds and runs
three-file programs with initialization, assignment, independent value copies,
shadowing, outer writes, early returns and void exits. It checks slot/value
exports, fixed workers, warm reuse, one-body executable updates, failure/repair,
fresh equivalence, unknown integer names/widths and floating storage compilation.
A format-only check covers non-default stack address spaces; it does not claim
native support for an additional target.

[Parameter native proof](../../tests/features/parameter_lowering.php) covers
three-file calls using both argument positions, nested evaluation order in the
plan/IR, parameter assignment/shadowing/copy isolation, empty and explicit void
exits, twelve-argument target passing, deep calls, provider-defined widths,
fixed workers/joins, debug exports and body-edit object reuse. Failure/repair and
fresh/incremental agreement use the resident compiler. Runtime side-effect order
and foreign ABI interoperability need their own later proofs.
