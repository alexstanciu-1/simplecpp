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
| `lookup.php` | Analysis-owned observed lexical lookup across independent type/function/constant pools |
| `file.php` | Standalone file-entry adapter |
| `semantics/declarations.php` | Function signatures, parameters, records and fields |
| `semantics/defaults.php` | Bounded scalar parameter-default normalization and value equivalence |
| `semantics/bodies.php` | Statement order, locals, returns and parameter seeding |
| `semantics/expressions.php` | Expression dispatch, variable/member access and lexical call selection |
| `semantics/calls.php` | Common selected-call signature completion and argument admission |
| `semantics/initialization.php` | Shared initialization-interface selection/substitution and factory/input validation; model query only, not literal execution |
| `semantics/data_construction.php` | Contextual literal targets, input-shape selection and ordered entry conversion facts through common bindings |
| `semantics/selection_results.php` | Shared matching-scalar value-result admission and conversions |
| `semantics/matches.php` | Strict constant-label match preparation and per-arm facts |
| `semantics/capabilities.php` | Dependency-aware capability queries and owned-copy admission |
| `semantics/types.php`, `literals.php` | Canonical type resolution, storage identity checks and exact literal facts |
| `data/structures.php` | Facts, invocation contexts and lookup observations |
| `data/work_records.php` | Typed retained work and shared dependency/error state |
| `changes.php` | Signature/record equivalence and fact restoration |
| `status.php` | Read-only source-change, active-work and completion queries; no scheduling |
| `cleanup.php` | Typed maintenance traversal clearing affected facts |

## Work and dispatch

`Preparation_Worker` selects typed declaration work: `function_signature_work` and
`record_definition_work`. Bodies use `function_body_work` and `file_body_work`;
bodies are processing units, never symbols. Fields/parameters settle with their
definition. Parsing transfers existing body-work identity without setting work state.
Collected declaration change flags and body `syntax_changed` remain set across
repeated parsing until successful preparation consumes them. The worker selects
source changes and independently pending dependency/failure work through one path.

The worker owns typed dispatch for queueing, rebuilding, member settlement and
retirement. Work records expose state, identity and typed queries, not processing
hooks or worker/context parameters. Retirement removes each role from its own queue; only declaration
work unlinks incoming declaration dependencies. Common retirement clears outgoing
links and semantic attachments after dependent notification. Neither work records nor
syntax and collected identities select work or invoke preparation.
`Compiler_Lifecycle::retire_deleted_sources` guards the successful parse join,
orders this semantic retirement, then invokes frontend `Collection_Retirement`.
`Compiler` and the standalone `File_Preparation` composition adapter use that
protocol before running preparation; the analysis worker no longer disposes scope
membership or syntax. Saved preparation cannot be emitted while source changes
remain unconsumed.
Backend consumers use `Preparation_Status` for work validity/completion and shared
`Type_Queries` for established canonical identities/source associations. These
queries never prepare, allocate work or register dependencies. Semantic consumers
still require prerequisites through preparation before reading their facts.
Declaration work settles before separate function/file body lists. Identity sets
deduplicate scheduling. Unchanged ready owners retain their facts. The worker creates
one context per rebuild; function-body setup seeds its locals and return type.

`Expression_Preparation::prepare`, `Type_Preparation::type` and
`Body_Preparation::prepare_statement` own their family dispatch. Typed handlers
attach facts and process named fields; auxiliary syntax uses its owning semantic
worker directly. Loop headers/continuations and bounded constant evaluation are
analysis-owned too. Unsupported operations fail explicitly without a fallback walk.
There is no AST processing hook or universal visitor; contexts are never retained on syntax.
Expression `require_preparation()` overrides all return `prepared_expression`.
Concrete callers use named accessors such as `require_call_preparation()` for
specialized facts; both views return the same attached object.

Facts identify exact roles where known: calls reference `callable_target_i` (source
functions or installed runtime static factories); source record types reference structs.
`Call_Preparation::require_signature` completes source declarations and registers
consumer dependencies before reading the target's read-only `signature()` accessor.
Installed runtime contracts need no source work. Direct access to absent source
facts fails without scheduling preparation; consumers needing current semantic facts
must use the analysis query. Signatures/parameter contracts are declaration-independent. Source parameter facts
retain storage identity and share their contract with the signature; storage consumers
query `storage_type()`. Runtime contracts never fabricate source declarations.
Prepared storage deliberately shares the broader
occurrence capability across fields, parameters, explicit variables and first writes.
A first-write occurrence keeps its collected identity when it introduces storage.

## Dependencies and completion

Consumers register both selected declarations and visited scope/name pools, including
missing/ambiguous candidates.

`Observed_Lookup` owns dependency-aware traversal, observing each visible pool before
testing its candidates and stopping at the nearest nonempty pool. Plain
`Scope_Lookup` has no preparation context or observation side effects. Both consume
the same scope membership/publication queries; no callback or visitor joins their
processing. Shared observations survive while any consumer remains; only orphaned
observations are removed from the scope during dependency cleanup.

Effective signature/layout changes notify consumers; body-only edits do not
invalidate callers. Required record completion detects cycles;
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
The common C++ call path emits omitted defaults explicitly from those facts, so
registered runtime bindings need not repeat semantic defaults in C++ declarations.
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
branches and cross-payload nullable joins remain deferred. Same-family boxed pairs and
boxed/scalar joins in either order preserve the boxed family and box only the selected
scalar. `Selection_Result_Preparation::boxed_join` supplies this bounded domain for
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

One-argument container reads use `Indexing_Preparation` through ordinary operator
selection. Registry definitions supply key/result type references and failure policy;
`Contract_Type_Substitution` is shared with initialization without granting indexing
from initialization opt-in. Typed vector/hash reads yield copied element values and
throw on absence; mixed/dynamic reads yield mixed, with null on absence and runtime
errors for invalid receiver/key kinds. Key conversions and result copy capability
are prepared, including source-derived dependencies. Index facts are non-addressable.
The same registry and signature binding select direct indexed-store/append operations
on established local/parameter-rooted paths. `prepared_index_assignment` retains the
operation and `container_element` conversion; it never invents a variable binding or
an addressable slot. RHS literals inherit the registered nested-construction target.
Construction and writes share element admission, including bounded dynamic boxing.
Ordered path steps additionally retain key conversion and registered traversal type;
they observe syntax without assigning value-read facts to intermediate brackets.
Dynamic traversal remains dynamic; mixed traversal remains mixed. The current nested
slice gates cross-family aggregate operations without closing the containment debt.
Zero-argument reads, string indexing, intermediate append and indexed updates/references
remain gated. Eager parent effect checks inspect both indexed children.

`Keyed_Path_Preparation` binds lazy probe/removal recipes through the same signature
and key-domain workers. Registry probe/removal opt-ins are independent of read/write
permission. Each step observes bracket syntax; no value-read facts are manufactured.
Unknown mixed keys stay mixed until runtime lookup, allowing probes to report an
invalid shape without throwing a required-value conversion. Removal rejects such keys.
`Value_Probe_Preparation::require_type` owns both ordinary and leaf-value admission,
including registered containers, nullable scalars and Result scalars. Signature binding
does not require copy capability; copied reads and assignment results request it at
their own boundaries. Vector-element removal and bare-variable/property unset remain gated.

`String_Conversions::decide_text` owns the bounded concatenation context: boxed,
ordinary scalar and literal-null operands normalize to string using identity or
`text_conversion`. This dispatch precedes wrapper/null conversion routing, without
granting general assignment, cast or wrapper-unwrapping permissions. Binary `.`
retains ordered conversion decisions and an owned string result. `.=` admits string
or boxed local/parameter targets and reuses that operand domain. Its converted
assignment write-back keeps string storage or boxes the result into the target family, replacing
the previous payload kind. Numeric stored-kind validation is separate. Eager
operand-order checks and interpolation behavior remain unchanged.

`Boxed_Conversions` owns scalar/null boxing and checked required scalar extraction
at assignment, by-value argument and return boundaries for both `mixed_semantics`
and `dynamic_semantics`. It records `boxed_wrap` / `boxed_extract`; emission constructs the box or calls the
existing `required_cast<T>`. Same-type copies reuse identity conversion. Ordinary int
is the only boxed integer admitted in this slice; fixed widths, implicit cross-carrier
bridges, wrapper boxing and further operator families remain deferred. Selection contexts admit only
identity same-family copies and ordinary scalar boxing, never extraction or bare-null branches.
Explicit bool/int/float/string
casts record `explicit_runtime_cast` and call `cast<T>`, not `required_cast<T>`.
Explicit `mixed`/`dynamic` family crossings use semantic roles and
`explicit_runtime_cast` in both directions. The runtime preserves scalar/null kind
and value and rejects every container crossing before destination replacement.
The old `mixed_dynamic_box` operation is removed. Implicit crossings, including
container insertion, reject during preparation. Registered construction/indexing/
iteration signatures carry the receiver's own family for values and nested children;
mixed scalar key carriers remain a separate int/string key mechanism. Dynamic's
tagged binding is value-stored; redundant value modifiers normalize through registry
facts without changing shared container payload identity.
`Condition_Truthiness` admits both families through `boxed_condition`; all condition
consumers and boxed `!` reuse runtime kind dispatch, not explicit boolean conversion.
Mixed table conditions still throw; dynamic containers (including empty containers)
are true under the runtime's shared-object contract. Dynamic scalar/null conditions
use the same scalar rules as mixed. Boxed AND/OR admission belongs to `Boxed_Operators`,
separately from general condition admission: peers are the same boxed family or
bool/ordinary int/float, not strings or wrappers. Distinct boxed identities reject. Ordered
operand truthiness facts drive native-bool short circuit. Logical contexts admit
calls, mutations and compound assignments, while eager parents retain recursive
effect checks. Mixed xor and wrapper logical contracts remain deferred.
Unary preparation permits one effectful operand, without introducing new bindings;
eager parents still inspect its subtree for operand-order hazards. Runtime payload failure
is not preparation failure. Reference arguments retain exact stable-storage rules.

`Boxed_Operators` selects both families' unary signs and binary numeric arithmetic on
shared `boxed_*` operator decisions. Arithmetic admission allows matching boxed or ordinary int/float
peers, with no extraction. `%` rejects statically known float
peers; unknown boxed payloads are checked by runtime dispatch. Results retain their family,
so typed consumers still apply their independent boundary checks. Mutation preparation
admits numeric boxed compound updates and prefix/postfix increment/decrement on
existing locals/parameters. `compound_write_back` separates converted native stores
from runtime stored-kind validation. Both direct runtime operators and generated
compound updates share `detail::assign_numeric_result` after computing the value.
Its common policy validates numeric stored-kind preservation before replacing the
target; mixed retains its structured diagnostics. Ordinary boxed assignment may
replace the payload kind. Boxed bitwise updates, indexed mutation and native targets
with boxed right operands remain deferred. The backend records runtime operator
headers from prepared operations, with no per-family rendering path.

Boxed comparisons reuse that owner and return bool. Equality admits scalar/null
peers; ordering remains numeric. Operator admission owns those domains, while
`Boxed_Conversions` normalizes admitted scalar/null peers into the selected family without
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
