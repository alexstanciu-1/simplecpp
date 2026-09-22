# Callable body checking
Doc Status: supporting

Status: implemented in the PHP prototype for the current scalar grammar:
nonnegative decimal integer literals, named calls with positional arguments, returns,
initialized typed locals, local reads/assignments, binary operations and scoped control flow.
[Integer comparisons and boolean results](binary_operations.md) use the same checked-operation path as addition.
Inspection stops at `build_native`; [native builds](native_executable.md) publish a generation
with an executable. The [project entry](program_entry.md) now shares this
checker. [Addition and control flow](operations_and_control_flow.md) now share these
contracts. Fractional syntax, general conversions and richer lifetimes remain later slices.
[Scalar lifetime analysis](lifetime_analysis.md) now follows this checker.
[Declared local types](local_type_resolution.md) are prepared by the type phase
and consumed here. Lifetime analysis and [local storage lowering](lowering.md)
now carry valid scalar locals and nested blocks through the common native path.

Declared parameters and positional arguments pass this checker, scalar lifetime
analysis, prepared backend contracts and [native lowering](lowering.md). The same
scalar copy/no-cleanup restrictions apply throughout. Identity and same-family,
same-signed integer widening are the implemented implicit conversions.

The [handler organization and contracts](analysis_handlers.md#body-checking)
describe statement/control/expression extension points, worker state and
the process-local data and utility folders.

## Parameters and argument evaluation

Project decision for this slice: evaluate arguments **left to right**, completing
each expression and its conversion before starting the next. Invoke the callee
after all its arguments. The existing conversion resolver handles the argument
boundary; identity and lossless integer widening are implemented. Missing/extra arguments,
void-valued arguments and unsupported conversions receive source diagnostics.

`Checked_Body::entry_parameter_count()` establishes that the existing local IDs
1..count are initialized incoming bindings in the root scope, in declaration
order. There are no fabricated assignment statements or copied parameter records.
Parameter reads, assignments and shadowing use the same local type/binding rules
as body locals. Copy/transfer/cleanup details remain lifetime responsibilities.

Each `typed_call` references a contiguous `argument_start/count` range in one
flat `typed_argument[]` dataset. Each argument holds its value ID, destination
parameter type ID; its value already includes any checked conversion. `argument_for(call_id, position)`
returns the shared record with a one-based position. Nested calls reserve their
own ranges; no per-call argument arrays or copied AST subtrees are retained.

An explicit call cursor stack checks nested expressions without recursive PHP
calls. Values are appended in evaluation order and calls after their arguments;
a call-result value refers to its producing call. The argument ranges express
how values are consumed, including reads before later nested calls. Void calls
remain effects without a value row and cannot be used as arguments. Statement
call segments include every nested call once, in effect order.

## Language decisions and the conversion boundary

Project decision: integer literals have the language's default `int` type;
fractional literals will have `float`. Their type does not come from a destination
annotation. The existing bundled definitions make `int` signed 64-bit and `float`
IEEE binary64. These are our chosen defaults, not a claim that all languages use
the same sizes or names. The user confirmed these literal-typing rules; the
external planning guide does not determine our conversion behavior.

The implemented integer default is a qualified reference in
[`named_types.json`](../../language/named_types.json), under
`literal_types.integer`. Catalog loading validates that it refers to an integer
definition. The type coordinator prepares this shared language-environment type
before returning the fixed type-stage output. Body workers neither look up an
`int` name nor create types. A default-policy edit changes the catalog context and
requests full work through the existing stages.

Current digit-only spellings are nonnegative decimal values, including leading
zeros. Integer checking normalizes the value and verifies its range from width
and signedness using decimal-string division. It retains an exact decimal value,
never converts through PHP integers/floats, and rejects out-of-range values with
their source span. Signs, alternate bases, suffixes and fractional syntax are not
implemented. The agreed `float` default is recorded here for that future slice.

Every argument, initialization, assignment and returned value uses
[`Conversion_Resolver::resolve`](../../src/04_analyze/check_bodies/utilities/conversions.php),
with source type ID, destination type ID and boundary use. It implements identity
and [same-family, same-signed integer widening](integer_conversions.md).
Other pairs report an **unsupported implicit conversion** naming the boundary.
No unchecked cast or contextually retyped literal substitutes for missing rules.
Equal storage shapes do not imply compatibility.

Identity returns the existing value ID. Widening appends a `typed_value` with the
destination type and a `conversion_value` payload: input value ID and resolved
operation. Statements and arguments reference that result; they have no separate
conversion flag. This preserves source type/provenance and one execution path.
Initializers and assignments require a produced value: a void call cannot supply
either. Local reads use the declaration's shared type ID, including shadowed
locals; assignment does not change that type or create another binding.

Named non-void functions require a returned value and cannot fall through.
Void functions support bare return or fallthrough; returning an expression from
a void function is outside the implemented subset. A call returning void may be
used as a statement, but cannot supply a returned value. All statements are
checked, including those after a return. Checked basic blocks describe branches, loops and returns. Return-path checking
follows their edges; lexical source ranges alone do not determine execution.
See the [flow contract](operations_and_control_flow.md#typed-control-flow-boundary).

## Work, storage and reuse

[`Body_Checker`](../../src/04_analyze/check_bodies/main_check_bodies.php) selects tasks from the
current resolved callable contracts, including the selected file entry. Each
selected `body_check_task` captures three shared references: its symbol owner,
that callable's exact `Symbol_Resolution`, and the completed `Type_Resolution`.
`Body_Checker::check(task)` takes only this fixed input; the worker derives the
literal default from the type snapshot. It validates callable identity, exact
syntax, root scope, signature and local-type associations before checking.
The project name-result set is needed for selection and joining, not by workers.
Tasks are temporary; checked results retain their existing precise dependencies
without retaining the task or complete type snapshot. No per-node data is added.

A focused PHP 8.5.7 probe on the existing `examples/scalability/1mb` workload
(4,266 callables, 2026-09-12) measured 96 additional bytes per selected callable:
409,536 bytes total. Selection including its list grew from 135,224 to 544,760
bytes; body-phase peak memory above retained inputs grew by the same amount.
Three fresh processes per version, alternating order, timed the complete body
phase after an untimed compile and explicit garbage collection. Median time was
304 ms before and 306 ms after (ranges 260–306 and 267–320 ms); this small probe
does not establish a speed improvement or a general performance guarantee.
Exports matched the ordinary pipeline. These costs concern temporary PHP task
objects, not native container layout or new retained semantic data.

The ordinary coordinator still completes body checking before starting lifetime
analysis, preserving stage order and diagnostic precedence. Individually callable
workers can also be composed: check a task, then analyze its completed private
body before either project join. Separate joins still establish current project
membership and reject stale inputs; composition does not imply publication or
merge their reuse rules.

Each [`Body_Worker`](../../src/04_analyze/check_bodies/body.php) owns its temporary
indexes and output arrays. It traverses linked statements with an explicit block
cursor stack and uses one `check_expression` path for literals, calls and local
reads, returning an optional value
ID while recording execution. Calls consume resolved signatures;
workers never recursively check another body, so recursion and forward calls
need no worker ordering beyond the signature phase.

`Checked_Body` retains its exact owner/AST and name bindings, flat typed value,
call, argument and statement arrays, scope ranges, and dependencies on type records and
signature shapes, including all parameter type definitions even when unused. It shares the prepared `Local_Types` owner without copying its
associations or performing name/type lookup. `local_type_for(local_id)` and
`scope_for(scope_id)` expose those contracts to later consumers.
Local value/call IDs are one-based. Source nodes are referenced, not copied.
Constants keep their normalized value; a `call_result` value references its
producing call. Each call holds the target symbol and optional `result_value_id`
(zero for no result). A void call has **no typed value row**, so it cannot become
a storage or cleanup candidate. Discarding a non-void result still retains its
value, for lifetime analysis. A `local_read` value references its source local
ID; it is neither the local's storage nor a cached last assigned value. Each
read produces a new value row.

Statements retain a zero-based `call_start` plus `call_count` identifying their
ordered execution segment, independently of their optional `value_id`. This
preserves void calls and calls in checked statements after a return; the lifetime stage
selects reachable blocks from the checked control-flow graph. Call order alone
is insufficient to schedule argument reads/conversions: their ordered ranges
must also be respected, and each nested call must execute exactly once. Argument
lifetimes and lowering follow this plan, including conversion values. Initializations,
assignments and returns consume their destination-typed value. Writes have a `target_local_id` independently
of the expression's `value_id`; initialization and assignment remain distinct
statement kinds for later lifetime/storage work.

Every typed statement has a `scope_id` into the existing name-resolution scopes.
One `typed_scope` range per scope records zero-based `statement_start` and
`statement_count` in the flattened executable-statement list, including all
descendants. Empty blocks retain a zero-length range. Parent relationships and
block source anchors remain in name resolution; there is no duplicate scope tree
or synthetic enter/exit instruction stream. These ranges describe lexical containment. The separate checked basic blocks
and edges describe execution, including branches and loops.
Void fallthrough is recorded without inventing an AST statement. Returning a
void call is diagnosed as an expression producing no value, even in a void
function; it never silently becomes a bare return.
These records support later lowering; they are not emitted LLVM instructions.
The [lowering input boundary](lowering_inputs.md) exposes resolved signatures and
definitions through access methods returning the same shared records.

The join validates selected-result membership, completeness and provenance,
including each task's exact callable bindings and type snapshot,
orders results by current symbols, and excludes removed owners. The session
accepts inputs, symbols, names, types and bodies together only after success.
Failure retains all six previous stage observations and the backend context.

An unchanged body is reused only when its owner/AST, name bindings, local-type
associations, referenced
type records and consumed signature shapes remain current. A callee's body-only
edit can preserve its callers' checked results; a callee return-type edit rechecks
them even when their AST/name bindings are unchanged. Dependencies retain no callee
AST or historical result set. The current shared compilation gate selects full
work for declaration edits in both request modes; these dependency checks do not
grant incremental admission by themselves. Catalog resets break type-ID lineage and select
all work. No-result calls still depend on their callee signature and void type
definition; gaining or losing a result rechecks unchanged callers. This proves
reuse through body checking, not backend incremental
admission or a published source-to-executable update.

`--debug=json` adds `bodies`: source anchors, values, calls, statements, scope
ranges, flow state and
type/signature dependencies. Normal CLI runs remain quiet.

## Proof

[`body_tasks.php`](../../tests/04_analyze/check_bodies/body_tasks.php) proves fixed callable
inputs, direct checking/lifetime composition before separate joins, equivalent
exports, reversed workers, independent reuse, wrong-callable/root rejection,
stale-task provenance, task/type-snapshot release and a native body edit.

[`body_checking.php`](../../tests/04_analyze/check_bodies/body_checking.php) covers the real
three-file chain, reversed workers and input purity, invalid joins, unchanged
sharing, body-only edits, unchanged caller rejection after a callee contract edit,
failure/repair, integer limits and large literals, bare/missing/void returns,
metadata-selected names and widths, equal-width signed/unsigned conversion
rejection, a long statement list, fresh-build semantic equivalence and exports.
Mixed result/no-result calls also prove evaluation order, absent void values,
contract edits in both directions, fixed-worker purity and warm reuse.
CLI simulation verifies typed-body exports through two actual refreshes.

[`local_bodies.php`](../../tests/04_analyze/check_bodies/local_bodies.php) adds source-derived
initialization/read/write proofs, shadowed types, conversion and void diagnostics,
nested/empty scopes, any-depth returns, checking unreachable statements, flat
large bodies, fixed workers and joins, fresh equivalence, sharing, callee-contract
invalidation and failure/repair. [Local lowering proofs](../../tests/features/local_lowering.php)
cover the following stages through native execution.

[Parameter/argument body proofs](../../tests/04_analyze/check_bodies/parameter_bodies.php)
cover initialized incoming bindings, reads/writes/shadowing, nested left-to-right
plans, void effects and argument rejection, arity/conversions, unused parameter
contracts, unchanged-caller invalidation, independent reversed workers, complete
joins, warm/full/fresh results, exports, deep/wide calls and failure/repair.
These prove checked plans; [parameter lifetimes](lifetime_analysis.md) now follow
them. Native parameter execution remains a subsequent slice.
