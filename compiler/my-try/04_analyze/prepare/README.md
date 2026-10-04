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
| `../conversions/` | Backend-neutral conversion decisions, central routing and target-family policies |
| `worker.php` | Select/order work, require prerequisites, maintain dependencies, compare/settle results and handle failures |
| `file.php` | Standalone file-entry adapter |
| `semantics/declarations.php` | Function signatures, parameters, records and fields |
| `semantics/defaults.php` | Bounded scalar parameter-default normalization and value equivalence |
| `semantics/bodies.php` | Statement order, locals, returns and parameter seeding |
| `semantics/expressions.php` | Expression dispatch, variable/member access, calls and assignments |
| `semantics/selection_results.php` | Shared matching-scalar value-result admission and conversions |
| `semantics/matches.php` | Strict constant-label match preparation and per-arm facts |
| `semantics/capabilities.php` | Dependency-aware capability queries and owned-copy admission |
| `semantics/types.php`, `literals.php` | Canonical type resolution, storage identity checks and exact literal facts |
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
Parameters own optional default expressions. Signature preparation admits trailing
by-value scalar literal defaults, normalizes a single numeric sign without general
expression folding, and retains the literal value plus argument conversion. These
snapshots participate in signature equivalence, including absent/present changes.
The snapshot is a `prepared_literal`, not an arbitrary prepared expression. Its
typed family exposes a local `literal_key()`; equivalence compares canonical type
and key, preserving exact floating spelling. Default preparation obtains concrete
facts through the syntax's typed accessors. Native narrowing is confined to this
polymorphic literal family rather than imposed on every expression fact.
Calls retain only explicit argument facts; their count and the prepared signature
identify the validated defaulted suffix. Defaults are checked even without callers.
Typed variadic parameters retain an element type separately from their canonical
vector storage type. Signature preparation validates one trailing by-value pack
without an explicit default. Prepared calls record the fixed-prefix length and pack
parameter, convert each tail argument to the element type and permit an empty tail.
Fixed scalar defaults may precede the pack. Signature equivalence distinguishes a
variadic parameter from an ordinary vector parameter even with identical storage.
Vector construction uses the existing registry and copy/storage capability contracts;
source vector indexing and iteration remain separate Chapter 06 work.
Statements report transient `statement_completion` through typed dispatch. The
completion worker composes sequence fallthrough and conditional alternatives, with an
unmatched fallthrough path when a chain lacks else. Nested blocks share this path.
Non-void function bodies reject fallthrough before their work settles; void functions
and program entry may fall through. All statements are still prepared after a return.
Completion is a successful-body invariant, not a duplicate retained flag. Loop
composition consumes nearest break/continue exits and propagates return. Do-while
tests are reachable only from normal body completion or continue; omitted for tests
cannot exit normally without break. Explicit constant tests remain conservative.
Exceptions and non-returning calls need explicit completion rules when enabled.

Switch groups compose direct entry and preceding fallthrough in source order.
Switch completion consumes break, propagates return/continue, and admits unmatched
normal exit without default. `Control_Transfer_Preparation` resolves bare transfers
for both loops and switches. `Switch_Preparation` replaces only the break target;
each group uses ordinary block preparation with isolated local membership.
Selectors require canonical int. Labels retain normalized exact decimal facts from
bounded integer-constant resolution; duplicate values/defaults reject before output.
The constant worker supports literals, resolved integer definitions and unary signs;
general folding, other selector types and runtime labels remain deferred.
Conditions retain separate truthiness decisions from `Condition_Truthiness` in
`conversions/truthiness.php`: bool, integers, float and string use the established
runtime condition contract. Explicit boolean conversion remains a distinct operation.
The emitter consumes these decisions
and emits native lazy branches and loops. Loop header scope and transfer targets are
worker-local, restored on success and failure; facts retain a weak selected target.
For initialization uses statement preparation, with expressions for tests/updates.
Existing expression-effect restrictions still apply.

`Conditional_Expression_Preparation` uses the same truthiness owner, prepares both
branches, and owns a symmetric conditional join: matching scalar payloads yield a
nullable result when either branch is nullable. Explicit branch conversions wrap
scalar alternatives or copy nullable values; no extraction happens inside the join.
Shorthand reuses its condition's prepared value as its true branch.
Results are non-addressable values; nested writes cannot introduce bindings. Eager
parents inspect every conditional child for effects. Emission uses typed lazy returns
and a single saved left value of the original condition type for shorthand. Bare-null
branches and cross-payload nullable joins remain deferred. Direct mixed/mixed and
mixed/scalar joins in either order preserve mixed identity and box only the selected
scalar. `Selection_Result_Preparation::mixed_join` supplies this bounded domain for
conditional and coalesce workers without widening scalar-only match. Result-led joins preserve
one exact Result identity: same-wrapper pairs copy the selected state, and exact
scalar alternatives wrap as value payloads. Nullable/Result and cross-family pairs
reject. Both branch conversions and copy capabilities are prepared before emission;
the existing lazy emitter and saved Elvis left value are reused unchanged.

`Match_Preparation` retains scalar-only `Selection_Result_Preparation` and strict integer label
selection rather than truthiness. `Integer_Constants::label` serves both switch and
match. Every arm is prepared, including unselected results, preserving dependency
tracking and diagnostics. Arm facts own normalized decimal labels and result
conversions. Repeated labels select the first result; duplicate defaults reject.
An omitted default is valid and unmatched runtime selection throws. Empty match is
deferred until non-returning expression typing exists. Match does not modify scope or
transfer targets; nested assignments cannot establish bindings. Eager parents check
selector/result effects; admitted labels are constants. Broader selectors and result
joins remain deferred.

`local_environment` is transient, with parent-linked membership referencing attached
storage facts. Blocks allocate children and restore the enclosing environment even
on failure. Reads/plain writes use nearest-visible membership; typed declarations
belong to the current block, permit outer shadowing and reject local duplicates.
A typed name is reserved during its initializer, rejecting self-access rather than
falling back to an outer binding. Sibling/child locals never escape or merge. Root
parameters and locals share an environment. This does not add definite-assignment
analysis or constant-condition reachability.
Signature preparation rejects duplicate function names through observed visible
name pools, including unused declarations, and duplicate parameter names within
each signature. Candidate identities are preserved for diagnostics and recovery.
Duplicate struct fields remain
[PE-03 deferred debt](../../docs/planning/preparation_emission_audit.md#pe-03--duplicate-declarations-can-reach-c).
Prepared records retain field-derived storage/copy capabilities. Capability consumers
require declaration completion through `Capability_Preparation`; nested capability
changes invalidate consumers even when their canonical type IDs stay unchanged.
Template contracts use that same query before canonical application reuse. Registry
constraint failures become normal preparation diagnostics and retain retry state.
Copy checks belong to owned-value boundaries, independently of conversion identity;
reference arguments retain their existing storage-compatibility checks.

Failed work stays pending and keeps its diagnostic; consumers cannot use failed
prerequisites. Independent work can finish, and later increments retry pending work
without requiring source edits. Success settles state and publishes the generation
handoff. Deletion notifies consumers before unlinking/removing indexes. Unexpected
escaping exceptions request a full rebuild. The full policy is in
[lifecycle](../../docs/lifecycle/incremental.md); remaining improvements are in
[debt](../../docs/planning/incremental_strategy.md).

## Current language boundary

`String_Conversions::decide_text` owns the bounded concatenation context: mixed,
ordinary scalar and literal-null operands normalize to string using identity or
`text_conversion`. This dispatch precedes wrapper/null conversion routing, without
granting general assignment, cast or wrapper-unwrapping permissions. Binary `.`
retains ordered conversion decisions and an owned string result. `.=` admits string
or mixed local/parameter targets and reuses that operand domain. Its converted
assignment write-back keeps string storage or boxes the result into mixed, replacing
the previous payload kind. Numeric stored-kind validation is separate. Eager
operand-order checks and interpolation behavior remain unchanged.

`Mixed_Conversions` owns scalar/null boxing and checked required scalar extraction
at assignment, by-value argument and return boundaries. It recognizes `mixed_semantics`
and records `mixed_box` / `mixed_extract`; emission constructs the box or calls the
existing `required_cast<T>`. Same-type copies reuse identity conversion. Ordinary int
is the only boxed integer admitted in this slice; fixed widths, implicit cross-carrier
bridges, wrapper boxing and further operator families remain deferred. Selection contexts admit only
identity mixed copies and ordinary scalar boxing, never extraction or bare-null branches.
Explicit bool/int/float/string
casts record `explicit_runtime_cast` and call `cast<T>`, not `required_cast<T>`.
Explicit `mixed`/`dynamic` bridges use semantic roles: `mixed_dynamic_box` retains the
dynamic tag and shared identity; reverse extraction uses `explicit_runtime_cast`.
Null extracts to an empty handle, whose reboxing remains tagged dynamic rather than
null. Actual payload rejection belongs to the runtime; implicit bridges remain rejected.
`Condition_Truthiness` admits mixed through `mixed_condition`; all condition consumers
and mixed `!` reuse runtime kind dispatch, not explicit boolean conversion. Boxed
table/object conditions currently throw at runtime; broader kind truthiness is debt.
Mixed AND/OR admission belongs to `Mixed_Operators`, separately from general condition
admission: peers are mixed/bool/ordinary int/float, not strings or wrappers. Ordered
operand truthiness facts drive native-bool short circuit. Logical contexts admit
calls, mutations and compound assignments, while eager parents retain recursive
effect checks. Mixed xor and wrapper logical contracts remain deferred.
Unary preparation permits one effectful operand, without introducing new bindings;
eager parents still inspect its subtree for operand-order hazards. Runtime payload failure
is not preparation failure. Reference arguments retain exact stable-storage rules.

`Mixed_Operators` selects unary signs and binary numeric arithmetic on existing
operator decisions. Arithmetic admission allows matching mixed or ordinary int/float
peers, with no extraction. `%` rejects statically known float
peers; unknown mixed payloads are checked by runtime dispatch. Results stay mixed,
so typed consumers still apply their independent boundary checks. Mutation preparation
admits numeric mixed compound updates and prefix/postfix increment/decrement on
existing locals/parameters. `compound_write_back` separates converted native stores
from runtime stored-kind validation. Both direct runtime operators and generated
compound updates use `mixed_t::assign_compound_result` after computing the value;
ordinary mixed assignment remains free to replace the payload kind. Mixed bitwise
updates and native targets with mixed right operands remain deferred.
Direct dynamic handles are nonnumeric; future mixed-valued property reads reuse this
owner without adding a dynamic-object arithmetic family.

Mixed comparisons reuse that owner and return bool. Equality admits scalar/null
peers; ordering remains numeric. Operator admission owns those domains, while
`Mixed_Conversions` normalizes admitted scalar/null peers into mixed without
extraction. Strict identity instead retains each operand's original type and uses
the shared runtime helper; literal null must not be boxed because empty-handle
normalization differs from mixed/mixed kind comparison. Nullable null comparisons
keep their separate presence-check path. Runtime table/weak-carrier comparison
limits and the approved empty-carrier repair are recorded in chapter 05.

`Result_Conversions` in `conversions/results.php` selects exact scalar payload wrapping,
same-type copies and explicit legacy false/true state construction. The three Result
families retain distinct identities. Boolean payloads are not sentinels; canonical
singleton constant types distinguish `false_sentinel`/`true_sentinel`, while `nullopt`
uses the null identity. Source locals require an initializer in this slice. Exact
scalar required-value extraction at assignment/argument/return boundaries records
`result_extract`; the backend copies the guarded payload. Non-value states throw,
failed stores preserve targets and reference arguments never implicitly extract.
Result conditions and probes admit the same scalar payload domain through the existing
semantic role. `result_condition` facts use the shared runtime truthiness helper for
all condition consumers, while probes keep their independent runtime authorities.
Logical operators and explicit casts
remain deferred; structured error construction
depends on chapter 13's reusable runtime construction/member-call paths. The runtime-owned
`error` type is now registered with default/copy semantics and a readonly no-error constant.

`Take_Preparation` owns wrapper-directed output binding: source snapshot copy capability,
exact scalar payload/alternative types, existing locals/parameters and output distinctness.
Both take spellings retain one `take_expression_node` with named source/value/alternative
edges and attached `prepared_take` family facts. Emission consumes those facts and calls
the existing runtime helper inside a single-evaluation lambda. Eager operand sequencing
restrictions also cover take. `Condition_Truthiness` owns error presence tests; unary
logical negation records that decision instead of introducing a second error-state rule.

`Value_Probe_Preparation` owns single-value `isset`/`empty` admission and attached
boolean operation facts on `value_probe_node`. Scalars, nullable scalars, Result
scalars, mixed and literal null are supported; direct path probes and other carriers are deferred. Emission
uses separate runtime probe helpers, not condition truthiness: `empty("0")` is false.
Operand evaluation/errors remain ordinary; no undeclared-name or missing-path bypass
is introduced, and probes cannot establish new locals through nested assignment.

Nullable scalar boundaries use `conversions/nullable.php`: typed null construction,
exact-payload wrapping/copying and checked required-value extraction. Explicit casts
remain distinct (notably the empty nullable-string exception). `operators/nullable.php`
selects strict null presence comparisons. Null literals carry a non-storable sentinel;
`?T` resolves to the registered nullable semantic definition and shared application
interning. No source spelling test grants a nullable role. C++ consumes these decisions;
it does not infer payload conversions. See [Chapter 05](../../docs/catalog/05_dynamic_boundaries.md)
for the bounded truthiness/coalescing slice and deferred broader operations/defaults.
`Condition_Truthiness` admits nullable scalars through payload truthiness, with empty
false. `Coalesce_Expression_Preparation` separately selects a required scalar value
domain and exact conversions; its dedicated syntax/facts preserve lazy fallback and
single evaluation without reusing truthiness as presence. Both operands are validated
even when the scalar-left form never needs its fallback at runtime.
`prepared_coalesce_expression::presence` explicitly selects always-present scalar,
wrapper value-state or mixed non-null testing; emission must not infer this from
conversion identity. Direct mixed/scalar joins return owned mixed values in either
order. A selected mixed fallback containing null is valid. Approved wrapper-left/
mixed-fallback joins use `left_extraction` for the exact scalar payload followed by
`left_conversion` for result normalization (mixed boxing or identity). Direct left
values use identity extraction. This composition grants no general wrapper-to-mixed
conversion and does not involve mixed truthiness. Reverse mixed/wrapper joins and
wrapper/mixed ternary/Elvis remain deferred.
Result coalescing extends that same owner with exact scalar payload domains for
`result<T>` and `result_or_false<T>`. `coalesce_value` identifies the selected left
extraction boundary without granting general conditional-branch extraction. Existing
emission snapshots the source and tests `has_value()` before extracting; fallback
is a required matching scalar or mixed for bool/ordinary-int/float/string payloads.
Boolable operands on either side reject during
preparation under the [v0.2 diagnostic policy](../../../../specs/v0_2_preparation_diagnostics.md),
including statically unselected alternatives. Runtime value-dependent errors still propagate.

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

String value parameters preserve caller-independent values even when the body only
reads them and another parameter aliases the original. Explicit `&` parameters alias
caller storage. Ordinary returns remain values, including reads through reference
parameters; there is no body-inferred borrowing or reference-return mode. Explicit
reference returns await a separate lifetime contract. This does not imply deep copies
for containers, wrappers or shared-object handles.

Struct fields support bool, fixed-width integer aliases and nested structs under the
compact-layout contract. Ordinary int/float fields, keyed construction, field
initializers and richer object syntax are not added by this slice. Typed locals can
use normal default initialization; field access, copies and record parameters/returns
use prepared facts. Literal decimals remain strings until target lowering/rounding.

General validation/STAN is a later pass. Preparation rejects boundaries it cannot
serve safely to generation; these bounded rejections do not redefine the language.
