# Type, body and lifetime extension points
Doc Status: supporting

Implemented in the PHP prototype under the
[shared organization convention](../code_organization.md#controlled-feature-extension-in-the-php-prototype).
Each process keeps its namespace, public stage entries, independent tasks and
separate joins. Handler traits contain private methods composed explicitly by
one worker; all worker state stays in that worker.

The slice preserves supported language behavior, data fields, result ordering,
diagnostics, dependency identities and incremental rules. New language features,
threading, performance changes and PHP++ porting are separate work.

## Type resolution

```text
type_model/
    data/{definitions.php, representations.php, records.php, callables.php, catalog.php, store.php}
resolve_types/
    data/{structures.php, records.php, result.php}
    records.php; record_definitions.php; record_join.php; definition_view.php
    utilities/{annotation_types.php, signature_validity.php, local_type_validity.php}
    main_resolve_types.php
    utilities/type_cache.php
    signatures.php
    locals.php
    main_prepare_entry.php
    signature_join.php
    local_join.php
```

[Annotation_Types::definition](../../src/04_analyze/resolve_types/utilities/annotation_types.php)
is the shared consumer of accepted annotation bindings for record, signature,
local and construction processing. It follows the declaration reference into the
prepared definition view and preserves diagnostic role and annotation span.
Source spelling lookup belongs to [name resolution](template_bindings.md).
It allocates no canonical type IDs and writes no candidate state. The former
internal `Type_Resolver::annotation_definition()` method has moved here.

`Type_Resolver` owns phase coordination; `Type_Cache` owns cache preparation and
`materialize()`. Materialization dispatches on representation kind, using the
shared type-model store's interning contracts. Structural requests use
`Record_Definitions::materialize()` after `Record_Join` acceptance. These
small cases remain together in the existing named method. Larger implementations
can later become private named handlers or a cohesive trait. No empty handlers
folder is created.

| Obligation | Extension point |
|---|---|
| Interpret a named annotation | `Annotation_Types::definition()` |
| Select participating callable declarations | `Signature_Resolver::participates()` |
| Resolve return and ordered parameter annotations | `Signature_Resolver::resolve()` |
| Resolve body-local annotations | `Local_Type_Resolver::resolve()` |
| Materialize an admitted provider definition | `Type_Cache::materialize()` |
| Validate and accept requests | `Signature_Join`, followed by `Local_Type_Join` |

Both task batches are still selected before execution. Workers return requests;
only the coordinator materializes into its private candidate. See
[signature resolution](return_type_resolution.md) and
[local types](local_type_resolution.md) for semantic contracts.

## Body checking

```text
check_bodies/
    data/{structures.php, store.php, result.php}
    handlers/{statements.php, control_statements.php, expressions.php}
    utilities/{body_validity.php, conversions.php, operations.php,
               literals.php, evaluation.php, flow_graph.php}
    main_check_bodies.php
    body.php
    flow.php
    join.php
```

[Body_Worker](../../src/04_analyze/check_bodies/body.php) retains its
fixed inputs, output arrays, initialization, public `check()` and final assembly.
It also owns shared dependency recording, value insertion, conversion application,
scope entry and source diagnostics. In particular, a handler uses `append_value()`,
`local_type()` and `signature()` so result construction records the same reusable
type/signature dependencies.

| Trait | Dispatch and handler contract |
|---|---|
| [Statement_Checking](../../src/04_analyze/check_bodies/handlers/statements.php) | `check_statements()` runs the iterative cursor traversal. `check_statement()` dispatches structural and ordinary statements. Ordinary handlers return one `typed_statement`; the dispatcher appends it and terminates flow for returns. |
| [Control_Statement_Checking](../../src/04_analyze/check_bodies/handlers/control_statements.php) | `check_control()` checks the condition and reserves branch/loop blocks. `resume_control()` returns the next lexical-body cursor, or null after establishing the continuation. |
| [Expression_Checking](../../src/04_analyze/check_bodies/handlers/expressions.php) | `check_expression()` drives pending call/binary cursors. Named handlers perform call setup, argument conversion, call completion, binary resumption, literal checking and local reads. |

Statement traversal advances the sibling before dispatch and completes lexical
scope ranges on cursor exit. Blocks return a body cursor; control statements
return a control cursor. The driver alone pushes and pops pending cursors.
Ordinary local declaration and assignment handlers share `check_local_write()`
for destination typing, expression checking and conversion, preserving their
different binding and diagnostic roles.

Expression handlers preserve post-order call/value insertion and left-to-right
argument checking. `begin_call()` reserves the complete argument range before
nested calls can append rows. `check_argument()` fills a reserved slot and
returns the next sibling node. `finish_call()` validates the final count and
appends the call plus its optional value. `resume_binary()` returns the next
operand node or zero on completion; on completion it replaces the current value
ID through its reference parameter. No handler recursively calls the expression
driver for a nested expression. Zero remains the existing no-value/end-node
sentinel according to each method's contract.

`Flow_Builder` stays in `flow.php`: it owns mutable block construction.
`Flow_Graph` queries move to `utilities/flow_graph.php`. Existing conversion,
operation, literal and expression-order classes move unchanged into utilities;
they remain separate classes, and their cross-stage consumers keep using the
same namespace and contracts. See [body checking](body_checking.md).

## Lifetime analysis

```text
analyze_lifetimes/
    data/{structures.php, store.php, result.php}
    handlers/{statements.php, values.php, locals.php}
    main_analyze_lifetimes.php
    body.php
    flow.php
    join.php
```

[Lifetime_Worker](../../src/04_analyze/analyze_lifetimes/body.php)
retains the checked input, live-local and live-value state, block traversal,
result assembly and shared lifetime-contract/diagnostic checks. It visits
reachable blocks in the existing order and initializes each block from
`Local_Flow`'s converged entry facts.

| Trait | Dispatch and handler contract |
|---|---|
| [Statement_Lifetimes](../../src/04_analyze/analyze_lifetimes/handlers/statements.php) | `analyze_statement()` maps checked statement kind to its consumption role, validates scope/call ranges, consumes the expression, then completes declaration/return handling. `validate_return()` checks type and flow agreement. |
| [Value_Lifetimes](../../src/04_analyze/analyze_lifetimes/handlers/values.php) | `evaluate()` consumes `Expression_Order` steps. Call steps use `consume_call_arguments()`; value kinds dispatch to conversion/operation operand consumers. Shared production and final consumption enforce one producer and one consumer. |
| [Local_Lifetimes](../../src/04_analyze/analyze_lifetimes/handlers/locals.php) | `validate_local_target()`, `start_local()`, `exit_to_scope()` and `end_local()` own target validation, initialization and scope-exit bookkeeping against worker-owned state. |

Target validation precedes expression evaluation. A new local becomes live only
after its initializer has been consumed. Every statement must consume its exact
call segment and leave no pending temporary values. Calls consume arguments
before producing their optional result; conversion/operation inputs end before
the resulting value is produced. Local exits retain their existing order and
block IDs.

`Local_Flow` stays in the process root because it performs definite-initialization
analysis. Its static methods do not make that core processing a utility. No
utilities folder is needed here yet. See [lifetime analysis](lifetime_analysis.md).

A source feature such as `switch` would extend syntax-facing stages where
needed. If body checking expresses it through existing checked flow and value
contracts, lifetime analysis can consume those contracts without a source-keyword
handler. New checked operations or lifetime effects need their own handlers.

## Verification

The full `python3 tests/run.py` suite passed, including native execution,
exports, fixed-worker purity, reversed joins, full/selective reuse, invalid input
rejection and failure recovery. A temporary comparison loaded the original and
refactored workers against the same fixed inputs: all 520 body outcomes
(including 28 diagnostics) and 492 lifetime outcomes matched exactly, with
retained inputs unchanged. The corpus covered nested calls, deep lexical blocks,
addition, conversion, branches, loops, unreachable statements and invalid returns
and arguments. Comparison helpers are not part of the implementation.

The nine data files and four relocated checking utilities are unchanged moves.
No benchmarks were run.
