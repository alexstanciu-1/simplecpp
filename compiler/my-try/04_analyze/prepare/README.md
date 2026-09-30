# Shared semantic preparation
Doc Status: supporting

This is the active backend-neutral semantic path. C++ consumes its attached facts.
`File_Preparation` provides standalone entry; `Preparation_Worker` runs the same
incremental scheduler across collected files after parsing/collection has joined.
No separate resolution pass precedes it. LLVM preparation remains
[parked](../../05_backend/llvm/README.md).

## Ownership

| File | Responsibility |
| --- | --- |
| `worker.php` | Select/order work, require prerequisites, maintain dependencies, compare/settle results and handle failures |
| `file.php` | Standalone file-entry adapter |
| `semantics/declarations.php` | Function signatures, parameters, records and fields |
| `semantics/bodies.php` | Statement order, locals, returns and parameter seeding |
| `semantics/expressions.php` | Expression dispatch, variable/member access, calls and assignments |
| `semantics/types.php`, `literals.php` | Canonical type resolution/conversion requirements and exact literal facts |
| `data/structures.php` | Facts, invocation contexts and lookup observations |
| `data/work_records.php` | Typed retained work and shared dependency/error state |
| `changes.php` | `same_signature`/`same_record` equivalence and fact restoration |
| `cleanup.php` | Typed maintenance traversal clearing affected facts |

## Work and dispatch

Declaration nodes select typed work: `function_signature_work` and
`record_definition_work`. Bodies use `function_body_work` and `file_body_work`;
bodies are processing units, never symbols. Fields/parameters settle with their
definition. Replacing a body transfers its existing work identity.

Work hooks delegate queueing, rebuilding, member settlement and typed retirement
to the worker. Retirement removes each role from its own queue; only declaration
work unlinks incoming declaration dependencies. Common retirement clears outgoing
links and shared indexes after dependent notification.
Declaration work settles before separate function/file body lists. Identity sets
deduplicate scheduling. Unchanged ready owners retain their facts. The worker creates
one context per rebuild; function-body setup seeds its locals and return type.

`node->prepare(context)` calls the concrete semantic algorithm and attaches its
result. There is no intermediate syntax adapter. Typed fields drive traversal;
unsupported operations fail explicitly. Contexts are never retained on syntax.
Expression `require_preparation()` overrides all return `prepared_expression`.
Concrete callers use named accessors such as `require_call_preparation()` for
specialized facts; both views return the same attached object.

Facts identify exact collected roles where known: calls reference functions, source
record types reference structs. Prepared storage deliberately shares the broader
occurrence capability across fields, parameters, explicit variables and first writes.
A first-write occurrence keeps its collected identity when it introduces storage.

## Dependencies and completion

Consumers register both selected declarations and visited scope/name pools, including
missing/ambiguous candidates. Effective signature/layout changes notify consumers;
body-only edits do not invalidate callers. Required record completion detects cycles;
recursive function calls remain valid. Return-type checks are per-body context.

Failed work stays pending and keeps its diagnostic; consumers cannot use failed
prerequisites. Independent work can finish, and later increments retry pending work
without requiring source edits. Success settles state and publishes the generation
handoff. Deletion notifies consumers before unlinking/removing indexes. Unexpected
escaping exceptions request a full rebuild. The full policy is in
[lifecycle](../../docs/lifecycle/incremental.md); remaining improvements are in
[debt](../../docs/planning/incremental_strategy.md).

## Current language boundary

The S2S path currently supports one source file, scalar integer/bool/float literals,
single-quoted string literals and locals, ordinary functions and value structs.
Forward declarations/calls are prepared before bodies. Function signatures use
named scalar/record types, explicit
returns (including void), positional value parameters and explicit reference parameters.
Only a uniquely resolved function is supported; templates/overloads await review.
Entry variables do not become implicit captures. Integer value conversions use the
runtime contract; reference storage must have compatible representation. Program
entry returns remain restricted to numeric/bool wrapper values with defined exit-code
conversion; adding string values does not widen that ABI boundary.

Struct fields support bool, fixed-width integer aliases and nested structs under the
compact-layout contract. Ordinary int/float fields, keyed construction, field
initializers and richer object syntax are not added by this slice. Typed locals can
use normal default initialization; field access, copies and record parameters/returns
use prepared facts. Literal decimals remain strings until target lowering/rounding.

General validation/STAN is a later pass. Preparation rejects boundaries it cannot
serve safely to generation; these bounded rejections do not redefine the language.
