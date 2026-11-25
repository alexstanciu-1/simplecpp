# Code generation organization and extension points
Doc Status: supporting

The PHP prototype's `05_generate_code` group applies the
[shared organization convention](../code_organization.md#controlled-feature-extension-in-the-php-prototype)
to lowering and LLVM emission. Namespaces, stage entry signatures, independent
tasks, result contracts and joins retain their existing roles. Each handler
trait contributes private methods to one callable worker.

This slice changes organization, not supported operations, evaluation order,
ABI decisions or emitted instruction spelling. Native object compilation and
publication stay in their existing processes. No PHP++ port or new scheduling
mechanism is introduced.

## Lowering

```text
prepare_backend/
    data/{configuration.php, bindings.php, abi.php, layout.php, lifecycle.php, context.php, tools.php}
    tools/toolchain.php
    utilities/{llvm_types.php, callable_contract.php}
    main_prepare_backend.php; callable.php; layout.php
    backend_join.php; lifecycle_join.php; layout_join.php
lower/
    data/{structures.php, store.php, result.php}
    handlers/{statements.php, expressions.php, locals.php, control_flow.php}
    main_lower.php; body.php; join.php; main_native_entry.php
```

[Lowering_Worker](../../src/05_generate_code/lower/body.php) owns the
fixed `lowering_input`, private output arrays, source-to-lowered value and slot
maps, consumed-value set and lifetime cursors. Its `lower()` visits reachable
blocks in analysis order, maps block identities, checks complete consumption of
lifetime facts and assembles the result. `get_value()`, `add_value()` and
`consume()` preserve shared value identity and lifetime enforcement.

| Trait | Extension point and contract |
|---|---|
| [Statement_Lowering](../../src/05_generate_code/lower/handlers/statements.php) | `lower_statement()` evaluates one checked statement, consumes its result according to statement kind, applies local writes and closes the corresponding local lifetimes. It returns the lowered value ID, or zero for no value, for block termination. |
| [Expression_Lowering](../../src/05_generate_code/lower/handlers/expressions.php) | `evaluate()` consumes the shared iterative expression-order steps and dispatches calls, conversions and operations. `lower_simple_value()` emits constants or delegates local loads for values newly allocated by the worker. |
| [Local_Lowering](../../src/05_generate_code/lower/handlers/locals.php) | `prepare_local_slots()`, `initialize_parameters()`, `write_local()`, `store_local()`, `load_local()` and `end_locals()` preserve slot allocation, parameter order, analyzed initialization and exit boundaries. |
| [Control_Flow_Lowering](../../src/05_generate_code/lower/handlers/control_flow.php) | `lower_terminator()` translates checked jump, branch, return and fallthrough endings using the worker's block-ID map and final value; return types remain validated against the prepared callable contract. |

The expression handlers obtain values through the worker's existing memoization
and consume operands against the exact analyzed lifetime sequence. Calls retain
contiguous argument ranges; their parameter order comes from the prepared
backend contract. Static slots remain ordered by first initialization and local
ID. Repeated loop execution uses those same slots; handlers do not recalculate
semantic liveness.

`Backend_Context` belongs to `prepare_backend/data/context.php`. Its validation and
canonicalization stay with that owner. `LLVM_Types` belongs to preparation utilities
and supplies common spelling to lowering and emission. `LLVM_Toolchain` is a
retained service in `prepare_backend/tools/`; the independent process runner is
in `tool_process/`. Native_Entry remains a separate lowering-plan owner.

See [lowering](lowering.md), [fixed inputs](lowering_inputs.md) and
[backend preparation](backend_preparation.md) for semantic and reuse contracts.

## LLVM emission

```text
emit_llvm/
    data/{structures.php, result.php}
    handlers/{instructions.php, calls.php, terminators.php}
    utilities/module_validity.php
    main_emit_llvm.php
    main_assemble_modules.php
    body.php
    join.php
    modules.php
    module_join.php
```

`LLVM_Emitter::run()` delegates each selected plan to a fresh
[Emission_Worker](../../src/05_generate_code/emit_llvm/body.php).
The worker retains one fixed plan, private LLVM text, the operand map, distinct
referenced callable contracts and parameter/argument coverage counters. It
validates the entry and instruction ranges, allocates local storage in the
first block, visits instructions in plan order and assembles `Emitted_Function`.
The worker is used once per task; completed output retains no worker object.

| Trait | Extension point and contract |
|---|---|
| [Instruction_Emission](../../src/05_generate_code/emit_llvm/handlers/instructions.php) | `emit_instruction()` checks result identity before dispatching each instruction kind. Named handlers cover constants, binary operations, conversion, incoming parameters, calls, loads and stores. Constants/parameters define operands without adding LLVM instructions. |
| [Call_Emission](../../src/05_generate_code/emit_llvm/handlers/calls.php) | `emit_call()` validates the exact backend target, consumes the contiguous argument range in parameter order, checks the result contract, records the reference and writes the call. |
| [Terminator_Emission](../../src/05_generate_code/emit_llvm/handlers/terminators.php) | `emit_terminator()` selects jump, branch or return emission. Branches retain the integer-to-condition conversion; returns retain their prepared type checks. |

All instruction handlers write only the worker's private text/maps/counters.
They consume completed lowering plans and prepared representation facts; no
source syntax or new language conversion decision is consulted. SSA names,
slot names, block labels, whitespace and declaration/reference order remain
unchanged.

`Module_Assembler` owns the module lifecycle; the stateless `Module_Worker` in
`modules.php` builds each module. Its `imports()` method
validates fixed file membership, backend/entry associations and callable
references, returning imports in the existing order. `module_text()` combines
target facts, declarations, completed function text and the optional native-entry
wrapper. `assemble()` returns the module. The existing private `entry()` method
owns wrapper spelling. Module assembly needs no new trait or worker state.

Function acceptance remains in `join.php`; file-module acceptance remains in
`module_join.php`. Independent function and module selection/reuse are preserved.
See [native emission and assembly](native_executable.md#emission-and-native-work).

## Source fingerprints

`LLVM_Toolchain::policy_fingerprint()` discovers PHP sources recursively under
`prepare_backend`, excluding its `tools/` services. It fingerprints sorted relative
paths and file contents, so layout workers, joins, binding/data contracts and future
nested policy files participate automatically. The native-entry adaptation in
`lower/main_native_entry.php` is the explicit policy dependency outside this owner.
Fingerprints detect implementation changes; they are not entity identities.

The toolchain implementation and independent process runner separately contribute
to the backend execution fingerprint. A runner change invalidates tool configuration
without redefining the ABI policy. Unchanged inputs retain probe reuse. This is cache
invalidation, not PHP code hot reload: restart a running compiler to load changed PHP.

The [backend preparation test](../../tests/05_generate_code/prepare_backend/backend_preparation.php)
uses private source copies to verify edits to layout/binding/join policy, automatic
nested-file addition/removal, execution-service separation and unchanged probe reuse.
It never edits the compiler checkout.

## Preservation proof

The full `python3 tests/run.py` suite passed with the settled
implementation, including real native execution, full/selective reuse, exports,
independent workers and joins, invalid-input rejection, failure recovery and
the added source-fingerprint checks.

A temporary before/after comparison ran the original and refactored code over
the same fixed inputs. All 370 lowered plans, 370 emitted functions and 111
complete modules matched exactly; 370 invalid-entry emission outcomes also
matched. Retained inputs remained unchanged. The corpus covered nested calls,
deep blocks, additions, local reads/writes, signed/unsigned widening, floating
parameters, void calls, branches, loops and unreachable statements.

The relocated data and LLVM utility implementations are unchanged; the lowered
result's stale single-block comment was corrected. Temporary comparison code is
not retained. No benchmarks were run.
