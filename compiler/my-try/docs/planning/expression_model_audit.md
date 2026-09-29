# Expression model coverage review
Doc Status: planning

Review date: 2026-09-29. Scope: PHP++/PHS strict-first expression representation in
[the AST proposal](../../03_parse/proposal/structures.php.example), compared with
Simple C++ contracts, the imported v0.2 catalog, and legacy lowering evidence.

**Architectural conclusion: yes, the specialized expression model can grow to
represent the reviewed language families without replacing its foundation.**
The original inventory below answers coverage; the user's actual question is
extensibility. Missing concrete node kinds are not, by themselves, design defects.

The foundation is sound: expression_node is a common category, concrete nodes own
typed operands, recursive expression fields allow composition, specialized workers
own semantics, and children() is inspection rather than an execution algorithm.
Unary, conditional, match, closure, construction and await nodes can be added as
ordinary specializations. Scope and preparation state remain on the owners that
need them. No universal payload, children list or scope field is required.

The extension seams requiring local model changes are:

1. **Assignment/binding ownership:** binding_node is statement-only, while nested
   assignment is an expression. Reuse one declaration/assignment semantic path at
   either depth; the current nested assignment class is not yet sufficient by itself.
2. **Call targets and arguments:** call_node.name models direct named calls only.
   Introduce typed call-target alternatives or specialized call nodes as callable,
   instance and static forms arrive. Type-valued/layout arguments cannot be forced
   into an expression-only argument list. This is a local call-model extension.
3. **Container entries and targets:** positional expressions alone cannot retain
   keyed entries; add entry records. Append is a target form distinct from indexed
   reads. Neither requires changing the general expression category.
4. **Syntactic category versus semantic capability:** assignable_expression_node
   does not establish native-reference eligibility. Reference sources and prepared
   value/access capabilities need their own rule, including reference-returning calls.
5. **Lazy operations:** workers must remain operation-aware. A generic eager walk
   over children would make the model unsuitable for short circuit, conditional
   access and branch selection; the agreed typed-worker design already avoids that.
6. **Adjacent models:** richer type syntax, signatures, block/closure scopes and
   retained token ownership must grow with the affected features. These are explicit
   neighboring dependencies, not a reason to redesign every expression now.

Adding a specialization requires updating its inspection tag, cursor and typed
worker contract where applicable. This is deliberate compiler maintenance, not
zero-edit plugin extensibility. It does not entail changing existing operand fields
from expression_node to a less precise universal AST type.

**Recommendation:** keep the hierarchy and typed-field approach. Settle the
assignment/binding seam first; record the other extension points and implement them
with their feature slices. Do not add all missing classes merely to make the
inventory appear complete. The inventory remains supporting evidence for this
assessment, not the primary conclusion or an implementation checklist approved now.

This review does not authorize implementation of all catalog features or change
language semantics. No parser, preparation, backend, or proposal code was changed.

## Subsequent agreed proposal change: declaration versus assignment

The user accepted separating explicit variable declaration syntax from assignment.
The proposal now uses `variable_declaration_node` with a required type and optional
initializer. Every assignment is an `assignment_expression_node`, including ordinary
assignment statements wrapped by `expression_statement_node`. Assignments retain a
saved operation enum, target and value, and specialized expression facts reusing
`prepared_binding` for the storage-binding outcome. A variable target retains its
occurrence; the assignment does not create a competing occurrence of its own.

Typed worker contracts and child iterators reflect that separation. Parser,
collector, preparation algorithms and backend migration remain future work; PHP
shape/dispatch checks do not prove chained-assignment language execution. Coverage
and current-field descriptions below are the pre-change review snapshot.

## Review boundary and authority

Follow [the spec map](../../../../specs/spec_map.md). The imported catalog is
planning/provenance, including supported, rejected, contradictory and historical
examples. A catalog card is not automatically an accepted feature. The legacy
implementation is evidence, not authority. PHP syntax acceptance alone is not a
Simple C++ contract.

The inventory below covers expression families, not every operand-type permutation,
every runtime helper, or every statement/declaration feature. Ordinary library
functions reuse call syntax. Their runtime value matrices do not require one AST
class per helper. Type-valued/layout operations are a significant exception.
No full-repository semantic-consistency proof or native-execution proof is claimed.

Reviewed sources:

- All 14 [my-try catalog chapters](../catalog/README.md): progress inventories and
  expression-relevant imported rules/notes. Source counts are recorded below.
- [PHP language catalog](../../../../specs/php/catalog.md),
  [strict guide](../../../../specs/simple_cpp_php_strict_quick_learn.md),
  [strict-mode policy](../../../../specs/strict_mode.md).
- [Dynamic types and typed boundaries](../../../../specs/dynamic_types.md),
  [conditional selection](../../../../specs/conditional_expression_matrix.md),
  [array semantics](../../../../specs/array_semantics.md),
  [probes](../../../../specs/count_empty_isset_contract.md),
  [native reference safety](../../../../specs/native_reference_safety.md),
  [reference examples](../../../../specs/references.md).
- [Closures](../../../../specs/language/closures.md),
  [compact layout types and probes](../../../../specs/compact_layout_types.md),
  [async/await](../../../../specs/async_await.md),
  [future metaprogramming contract](../../../../specs/metaprogramming_contract.md).
- [Generator rules](../../../../generators/php/specs/rules.md),
  [source catalog](../../../../generators/php/specs/rules_catalog.md),
  [unsupported/reduced features](../../../../generators/php/specs/unsupported.md),
  [primary-type union parameters](../../../../generators/php/specs/primary_type_normalized_parameters.md).
- [Operator family inventory](../../../../specs/operator_matrix/data/families.json),
  [matrix limitations](../../../../specs/operator_matrix/missing_matrix_surface.md),
  [portability language inventory](../../../../specs/portability/catalog/language.md),
  [other compiler expression parser](../../../../specs/portability/expression_parser.md),
  [evaluation-order iterator](../../../../specs/portability/expression_order.md).
- [Legacy expression emission](../../../../generators/php/src/Generator/Generator.php):
  `renderExpr`, assignment-chain lowering, compound assignment, match,
  interpolation, and call/target handling. Compared with the
  [current my-try parser](../../03_parse/parser.php) and
  [active specializations](../../03_parse/structures_specialization.php).

## What the current proposal actually contains

There are 24 concrete node specializations, including declarations, types,
statements, trivia and executable bodies. The expression classes are:

- Integer, float and boolean literals.
- Variable references and direct named calls.
- Binary and assignment expressions.
- Positional array literals.
- Indexed access and named instance-field access.

`binding_node` is a statement combining local introduction and assignment.
`expression_statement_node` can host an arbitrary expression structurally.
Type syntax is restricted to named types and fixed-array type syntax.

These are proposal structures, not migrated executable compiler support. The
active parser accepts a smaller expression grammar: scalar numeric/boolean
literals, variables, named calls, positional arrays and access suffixes. It does
not currently parse general binary/unary/assignment expressions. Active
preparation explicitly supports only some of those parsed forms; array/index
specializations remain unsupported. Do not infer support from a class existing.

## Representation coverage

Legend: **shape** = existing fields can express the tree; **partial** = important
information/relationships are missing; **missing** = no honest current node shape.
None of these labels means end-to-end parsing/preparation/generation support.

| Family / source anchors | Proposal assessment | Required representation or decision |
| --- | --- | --- |
| Integer/float/bool: LIT-INT, LIT-FLOAT, LIT-BOOL | Shape | Preserve exact numeric lexeme plus provenance; do not round through host PHP floats. Current numeric nodes rely on retained tokens/facts, unlike bool's saved value. Token-generation ownership remains debt. |
| Strings, empty strings, escapes: LIT-STR | Missing | String literal payload; distinguish source spelling from decoded value. Heredoc/nowdoc can normalize here if accepted, without new semantic classes merely for spelling. |
| Null: LIT-NULL, NULL-CHECK | Missing | Explicit null literal. A nullable child field means absent syntax, not source `null`. Typed-null legality is a later boundary decision. |
| Constants and enum cases: LIT-CONST, CLASS-CONST, ENUM | Missing | Value-name reference and scoped constant/case access, with retained spelling and resolved identity. `named_type_node` cannot be used as a value expression. |
| Variables: VAR-ASSIGN, VAR-REASSIGN | Shape | Saved name and occurrence exist. Distinguish lookup/reference use from declaration-target use in nested assignments. |
| Arithmetic, comparisons, strict identity, bitwise/shifts, concatenation: EXPR-*, operator matrix | Partial | Binary tree is adequate. Replace token-only operator identity with an enum; keep token location as provenance. Concatenation and strict identity retain their own semantics. |
| Parentheses, precedence, recursive composition: EXPR-PAREN, EXPR-NESTED, EXPR-CHAIN | Shape | Preserve grouping in the tree and emit precedence-safe expressions. A grouping node is optional for source-fidelity needs, not required to preserve value semantics. Do not flatten/reassociate. |
| Logical short circuit and coalesce: EXPR-LOGIC, EXPR-OR, EXPR-COALESCE | Partial | Two children suffice structurally, but saved operation identity and lazy RHS evaluation must be explicit. Ordinary eager binary evaluation is not equivalent. |
| Unary `+ - ! ~`: EXPR-NEG/POS/NOT/BNOT | Missing | Unary operation + operand. Negative literals should not force a separate signed-literal policy. |
| Prefix/postfix increment/decrement: EXPR/STMT-PREINC/POSTINC/PREDEC/POSTDEC | Missing | Writable target + operation + prefix/postfix distinction; preserve returned old/new value and evaluate the target once. |
| Assignment as a value and chaining: VAR-CHAIN, NOTE-054 | Partial | Nested assignment shape exists; declaration occurrences, inferred binding facts and write context do not exist on nested assignment owners. Outer `binding_node` cannot carry all targets' semantic identities. |
| Compound assignment: STMT-ASSIGNOP, matrix compound family | Partial | Assignment tree can carry target/RHS, but needs saved operator identity and read-modify-write semantics. Do not rewrite `target += rhs` into two evaluations of target. |
| Reference binding: REF-BIND, TYPE-VAR-008 | Missing | Explicit reference-binding intent and source eligibility, distinct from copy assignment. Parameter passing mode alone does not represent local alias introduction. |
| Full ternary and Elvis: EXPR-TERNARY, EXPR-ELVIS | Missing | Condition, branches, and omitted-middle distinction. Elvis must reuse the evaluated condition value, not evaluate a copied subtree again. |
| Match: CTRL-MATCH | Missing | Subject + ordered arm records, each with conditions and result, and default marker. Evaluate subject once and only the selected result. |
| Scalar casts / typed normalization: CAST-* | Missing | Target type + operand for explicit casts. Implicit checked conversion at a typed destination belongs to preparation, not synthetic source casts everywhere. Object/array casts need contract-specific classification: the catalog restricts object conversion to `(object)[...]`, rather than arbitrary `(object)$value`. |
| Type tests: literal `instanceof` in legacy lowering | Missing | Operand + type target. A type operand is not a value expression. Dynamic class targets are rejected by the inspected implementation. |
| Positional and nested arrays: ARR-INIT | Shape for positional entries only | Recursive expression storage already handles positional nesting. Missing literal kinds still limit actual contents. Destination determines vector/fixed-array/table meaning. |
| Keyed/mixed array entries and struct initializer sugar: ARR-INIT-004/005/006, compact-layout contract | Missing | Ordered entry records with optional key and value. Do not turn entries into a map: order, duplicates and evaluation must survive until semantics decides them. |
| Read/write indexed chains: ARR-READ/WRITE, STR-INDEX, NOTE-053 | Partial | Recursive base/index fields cover syntax. Read, write, probe and reference-binding contexts differ. Read must not accidentally create missing entries. |
| Append targets and assignment values: ARR-APPEND, NOTE-054 | Missing | Append-target form distinct from indexed read; absent index is syntax, not `null` index expression. Include nested append and expression-result behavior. |
| Named instance properties and `$this`: OBJ-PROP, CLASS-THIS | Shape/partial | Named field/base recursion exists; `$this` needs receiver binding semantics. Syntactic assignability does not prove native-reference eligibility. |
| Named free calls and nested calls: FUNC-CALL | Shape | Ordered expression arguments and saved callee name work for fixed positional direct calls. Existing call specializations do not model all callable targets. |
| Callable-valued and instance/static calls: CLOSURE-CALL, CLASS-CALL, OBJ-METHOD-CALL | Missing | Typed callee alternatives: declared name, expression-valued callable, instance member, scoped/static member. A string cannot preserve receiver expression/identity. |
| Static properties/constants and class-name expressions: CLASS-PROP-006*, CLASS-CONST, layout probes | Missing | Scope/type qualifier, member spelling and access kind; retain `self`, `parent`, `static`, qualified names and instance-qualified forms distinctly where accepted. |
| Construction: CLASS-NEW, CLASS-CALL-008, compact-layout contract | Missing | Construction target/type + arguments. Preparation determines class/shared versus struct/value construction; do not encode every construction as an ordinary function call string. |
| Nullsafe access: ENUM-003, broader docs | Missing; target support partial | Conditional receiver access/call shape and lazy dependent arguments; preserve chain boundaries. Do not silently treat it as ordinary field access. |
| Closures and arrow functions: CLOSURE-*, ARROW-001 | Missing | Expression owning signature/body, captures and local context. Arrow bodies may normalize to returns. Capture kind/identity must be retained, including unsupported forms for diagnostics. |
| Probes/reset: VAR-ISSET/EMPTY/UNSET, ARR-EXIST/EMPTY/UNSET | Partial | Name+args is enough only after an explicit normalization retaining probe/reset meaning and access paths. Distinguish value operations from keyed operations; `unset` is statement-like. |
| Ordinary runtime helpers: count, take, casts-as-helpers, weakref helpers, etc. | Shape subject to argument forms | Reuse calls and member calls; semantics/facts describe outputs and conversions. No AST class for each runtime API. Out destinations must remain recognizable expressions. |
| Interpolation: STR-INTERP, generator interpolation rules | Missing | Ordered literal and expression fragments; embedded property/index/method forms reuse their expression nodes. Preserve evaluation and conversion rules. |
| Layout probes: compact-layout section 6 | Missing | Arguments can be type names / `T::class` and bare field names. `Storage<expression_node>` alone cannot honestly describe these syntactic categories. Normalize explicit intrinsic argument kinds or specialized probe nodes. |
| Async/await: top-level async contract | Missing | Await operation plus operand/context, and async function metadata. A documented frontend normalization to helper calls is possible; it must preserve timer suspension versus synchronous wait. |
| Generic applications: current templates and future metaprogramming | Partial | Call template args only accept named types. Constructed types, type/value argument kinds, constraints and resolved instances need distinct representation. Future policy is recorded, not activated by this review. |

## Boundaries that should change before simply adding nodes

### 1. Declaration and assignment must compose

`$a = $b = $c = 0` fits a binding statement whose value contains two assignment
expressions. But that is only the syntax tree. `$b` and `$c` may each introduce a
local with its own identity, inferred type and dirty/dependency state. Today the
outer binding owns those facilities and `assignment_expression_node` owns none.

Recommended discussion: keep an explicit declaration form for typed
predeclarations, and give assignment targets a reusable binding/occurrence path
that works at any expression depth. Do not duplicate a second symbol collector
inside the assignment-expression preparer. No final class split is selected here.

The legacy `tryRenderDeclarationAssignChain` is a useful behavior reference for
statement chains. Its declaration hoisting must not be copied indiscriminately
into conditional/short-circuit branches; expression locality and side effects
must survive. Loop initializer and condition positions also need this path.

### 2. Writable syntax is not native-reference eligibility

`assignable_expression_node` covers variables, fields and indexes. That is a
reasonable syntactic family. It must not promise a native reference: an indexed
slot can be writable while forbidden as a native reference source. A call may
return a permitted stable reference, yet `call_node` is not currently in that
assignable family. Settle whether reference sources use a separate prepared
capability, instead of forcing all such expressions into the assignment-target base.

Retain operation context at the owning expression/work step: value read, write,
read-modify-write, probe, append, reference binding. Do not add a permanent single
"read/write" flag to a retained node that may be inspected in different contexts.

### 3. Invocation has a target, not just a name

A direct function name, callable variable, instance method and static method have
different syntax and receiver evaluation. Use typed target forms, or distinct
concrete call forms sharing argument handling. Avoid adding a string plus several
mutually exclusive nullable receivers to every call. Exact structure is a next
review decision. Preserve argument order and scope-qualified names.

A future argument record is needed when named/unpacked arguments are actually
accepted; defaults and typed variadics already affect the declaration and prepared
call mapping. Fixed positional calls do not need fabricated argument metadata now.

### 4. Evaluation strategy belongs to the operation

`&&`, `||`, `??`, ternary, Elvis, match and nullsafe chains do not evaluate all
children unconditionally. Generic `children()` is inspection, not execution order.
Typed workers should establish the necessary lazy/evaluate-once behavior. The
legacy helper/lambda spelling is backend detail, not a reason to add lambda AST
nodes for every conditional expression.

### 5. Expression completeness also depends on type syntax

The proposal has named types and a fixed-array form with literal integer extent.
It has no nullable, constructed generic, callable-signature, normalized union or
qualified/reference type forms. Encoding `vector<int>` in a name string would
lose the requested full specialization. `parameter_node` also lacks default
initializers and a variadic marker; function nodes lack return-reference/async
intent. Field initializers are absent. These neighboring structures constrain
expressions even where the expression tree itself is sufficient.

### 6. Scope and source identity need two explicit qualifications

The proposal currently states that plain blocks introduce no scope. That reflects
the small current parser but is insufficient for the wider documented Safe v1
block-local visibility rules. Future executable blocks need an explicit lexical
scope or a worker-maintained scope relationship with retained identity. Do not
silently apply function-wide locals when adding branches/loops. Closure capture
and nested callable scopes also need dedicated ownership and incremental work.
This corrects the over-broad implication of the preceding proposal scope review.

Saved names are good. Operators still depend on token positions and numeric
literal syntax still depends on the owning snapshot. Save operator enums; define
snapshot/lexeme ownership before extending incremental retained expressions.
This does not require copying every literal into every AST node.

## Operator-family cross-check

The structured operator inventory supplies these families. Existing binary or
assignment shape is not evidence that preparation already implements them.

| Inventory family | Shape assessment |
| --- | --- |
| condition_truthiness | Expression operand reusable; controlling statement/conditional owner missing for most sites |
| casts_explicit | Missing cast node/type operand |
| operators_conditional_selection | Coalesce shape partial; ternary/Elvis missing |
| operators_unary | All eight unary/update variants need representation |
| operators_binary_arithmetic | Binary shape; saved operator needed |
| operators_binary_logical | Binary shape; saved operator and lazy evaluation needed |
| operators_comparison_equality | Binary shape; operator identity needed |
| operators_comparison_ordering | Binary shape; operator identity needed |
| operators_strict_identity | Binary shape; must preserve strict versus non-strict identity |
| operators_binary_bitwise | Binary shape; operator identity needed |
| language_probes_and_reset | Explicit probe/reset normalization and target context needed |
| operators_compound_assignment | Assignment shape; operation/target evaluate-once semantics needed |

The inventory does not itself settle power, spaceship, coalesce assignment or
word-form logical operators. Do not infer their acceptance just from PHP syntax
or an imported catalog example. No operand-type TSV matrix was exhaustively
executed/revalidated in this model review.

## Rejections, future features and conflicting documents

- `and/or/xor` appear as catalog examples but generator rules explicitly reject
  them. They are rejection coverage, not an immediate accepted-expression gap.
- Dynamic property names/calls, general PHP reference behavior, unsupported
  reference sources, dynamic closure storage and untyped variadics must not be
  promoted simply because generic AST fields could hold them.
- Closure capture-by-reference has conflicting imported versions. The reviewed
  generator unsupported document still rejects it while catalog material also
  advertises it. Record capture mode if/when modeled, but settle the accepted
  subset against current owners before implementing it.
- Power/spaceship are labelled supported in the source catalog, with notes that
  their runtime helpers must be created, but the inspected legacy binary emission
  does not supply corresponding normal cases and the structured inventory omits
  them. They need a contract/implementation decision, not assumed parity.
- Nullsafe property handling exists in legacy emission, while the JSS guide
  records incomplete PHS result typing. Treat it as partial, not fully proved.
- Old enum restrictions mention only int-backed enums; the newer top-level compact
  layout contract explicitly supports exact integer backing widths. The top-level
  contract wins. Enum methods/helpers remain separately restricted.
- Null-assignment permissiveness and several array/reference notes conflict
  between older catalog text and normalized rules. An explicit null node is
  still required for valid typed-null uses regardless of the untyped policy.
- The legacy direct-index-call-argument shortcut can autovivify, while top-level
  array/probe contracts require non-mutating reads/probes. Preserve distinct
  argument/access contexts and reconcile this discrepancy before reusing that
  shortcut in shared preparation.
- PHP `yield`, `yield from`, general clone, destructuring, named/unpacked call
  arguments and first-class-callable syntax are not established as generally
  accepted PHS expression contracts by this review. Keep them in an explicit
  deferred/decision bucket; the iterator proposal intentionally avoids generator
  lowering. `throw` statements are catalogued, but legacy `renderExpr` explicitly
  rejects throw-as-expression.
- Future metaprogramming policy includes typed compile-time value arguments and
  constraints. It does not license arbitrary runtime-dependent compile-time
  execution or broad C++ expression compatibility.
- Portability parser limitations describe another compiler/converter path;
  they are not automatically limits of PHS, and that parser's successes are not
  proof that my-try implements the same forms.

## Recommended review/implementation order

1. Settle assignment/declaration/reference-target relationships, including nested
   first assignment and expression-result semantics.
2. Settle callable targets, argument/type operands, scoped names and construction.
3. Add saved operator enums, unary/update forms, conditional selection and literal
   families. Keep lazy evaluation requirements explicit.
4. Add ordered array entries, append targets and probe/reset access modes.
5. Extend neighboring type/signature/block-scope structures, then closures,
   async and metaprogramming as separately agreed slices.

Keep the specialized node architecture. These are bounded families, not a reason
to return to an untyped property bag or put scopes/facts on every node. Validate
representative combinations, not just one isolated example per new class:

- `$a = $b = $c = 0` and assignment inside a conditional branch.
- A compound indexed write with calls in the base/index, proving single evaluation.
- Calls on receiver expressions; callable invocation; static/qualified targets.
- Nested keyed literals and append-assignment results in another expression.
- Short circuit, Elvis, coalesce and match with observable branch side effects.
- Closure capture/argument/body scopes and locals introduced in nested blocks.
- Explicit nullable/generic/callable type boundaries, type-valued layout probes.

These are future focused proofs, not tests run by this review. First agree the
representation changes; avoid implementing all language semantics during the
proposal consolidation.

## Catalog inventory screened

394 catalog cards/notes across 14 chapters were inventoried by ID and progress
example. This is an inventory count, not a count of supported or missing language
features; it includes duplicates, prose notes, rejected examples and non-expression
work. The family review above groups those forms instead of treating every row as
a new AST class. Detailed rule reading targeted expression-relevant sections.

| Chapter | Cards/notes | Review relevance |
| --- | ---: | --- |
| [01_literals_locals](../catalog/01_literals_locals.md) | 25 | Scalar/string/null-adjacent literals, saved names, chained assignment and local introduction. |
| [02_expressions](../catalog/02_expressions.md) | 60 | All arithmetic/logical/unary/update/compound/interpolation/index expression families; grouping. |
| [03_control_flow](../catalog/03_control_flow.md) | 13 | Ternary and match; surrounding condition/loop expression positions; statement nodes remain separate. |
| [04_functions](../catalog/04_functions.md) | 24 | Calls, defaults, typed/variadic/reference boundaries and function-local ownership. |
| [05_dynamic_boundaries](../catalog/05_dynamic_boundaries.md) | 23 | Null, probes, coalesce/Elvis, explicit casts, nullable and malformed type intent. |
| [06_containers](../catalog/06_containers.md) | 33 | Positional/keyed/nested arrays, read/write/append/probe paths and typed-container boundaries. |
| [07_references_lifetime](../catalog/07_references_lifetime.md) | 22 | Alias introduction, reference source restrictions, reset and return-reference intent. |
| [08_project_symbols](../catalog/08_project_symbols.md) | 36 | Qualified names, scoped/static targets, construction; namespace/import declarations are neighboring work. |
| [09_value_types](../catalog/09_value_types.md) | 7 | Ownership type intent and enum case/helper syntax; unsupported enum extensions remain restricted. |
| [10_objects](../catalog/10_objects.md) | 80 | Instance/static access, construction, method calls and typed member boundaries; declaration modifiers are neighboring work. |
| [11_inheritance](../catalog/11_inheritance.md) | 22 | Parent/static calls and construction; inheritance/trait/interface declarations are neighboring work. |
| [12_closures](../catalog/12_closures.md) | 13 | Closure/arrow expressions, captures, callable signatures/invocation and block-local visibility. |
| [13_errors](../catalog/13_errors.md) | 4 | Throw construction and expression-position rejection; try/catch/finally are statement structures. |
| [14_output_runtime](../catalog/14_output_runtime.md) | 32 | Expression emission/normalization rules and future reference intent; output layout is backend work. |
