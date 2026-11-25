# Explicit instantiation and literal constants
Doc Status: supporting

## Implemented boundary

The prototype supports global integer constants initialized by integer literals,
for example `const COUNT = 3;`, and explicit source struct/function template
applications. Arguments are ordered types or language `int` values; multiple type
parameters and nested applications use the same path. Literal decoding and range
checking reuse the ordinary integer contract. Global constants can explicitly name
a fixed-width integer type; integer template parameters still use language `int`. A constant and literal of the same
typed value select the same instance.

General constant evaluation is outside this compiler version. Expression
initializers, local constants, deduction, defaults, packs and other value argument
kinds are unsupported. Required `constexpr`, `consteval`, `if constexpr` and
`if consteval` semantics report source errors. Their syntax remains intact for a
future explicit evaluation context; instantiation does not execute their code.
An unused template remains a bound definition and does not require concrete checking.

Existing record field, source passing and operation restrictions still apply.
Nested arguments do not imply nested record fields. [Fixed-array fields and source
methods](fixed_array_list_plan.md) now prove a small source list. Dynamic ownership,
provider-family materialization and optimization remain separate capabilities.

## Owners and shared path

| Owner | Responsibility |
|---|---|
| [Collection and bindings](template_bindings.md) | Original declaration identities, ordered parameter scopes, occurrence bindings and retained ASTs. |
| [Instantiation_Policy](../../src/04_analyze/instantiate/policy.php) | Load a fixed positive instance budget before cache selection; the session protects its input file from output replacement. |
| [Concrete_Preparation](../../src/04_analyze/resolve_types/main_prepare_concrete.php) | Own literal selection and concrete readiness; reuse applications/members and schedule fixed application/record/member batches. |
| [Constant_Worker / Constant_Join](../../src/04_analyze/instantiate/constants.php) | Produce and accept typed literal values. No expression execution. |
| [Application_Worker](../../src/04_analyze/instantiate/applications.php) | Interpret ordered arguments in a fixed context; return private arguments or missing type prerequisites. |
| [Instance_Join](../../src/04_analyze/instantiate/join.php) | Validate selected result provenance and completeness; allocate/reuse exact instance identities in a private candidate. |
| [Instance_Store](../../src/04_analyze/instantiate/data/store.php) | Own private current registry, incremental record index and newly accepted context frontier; publish one immutable result. |
| [Instance_Set](../../src/04_analyze/instantiate/data/result.php) | Retain current demands, occurrence targets, ready record definitions, constants, registry history and type lineage. |
| [Bindings](../../src/04_analyze/instantiate/bindings.php) | Shared read-only interpretation of bound type parameters, applications, constants and integer parameters. |
| [Existing concrete type preparation](../../src/04_analyze/resolve_types/calls.md) | Canonical records, signatures and local types for ordinary declarations and instance contexts. |
| Existing checking, lifetime and backend stages | Concrete operation validity, ownership, cleanup and output indexed by concrete callable identity. |

Type resolution selects ordinary source/provider records. Concrete preparation
first accepts literal constants; ordinary and specialized record tasks join through the same
`Record_Preparation` / `Record_Join` / `Record_Definitions` path. Once demands and
record prerequisites are complete, signature/local workers read one fixed view.
Signature acceptance precedes local acceptance; checking and output follow normally.

No specialized ASTs or synthetic source declarations are created. Each concrete
context references its original definition and ordered arguments. Checking follows
the original occurrence bindings plus that context. Recursive function calls share
an instance identity; an unsatisfied recursive record layout is a source error.

## Identity and lifetime

`Instance_Identities`, called only by the accepting `Instance_Join` and `Member_Join`,
allocates positive instance IDs. The exact registry key is the
complete definition symbol ID and ordered `(canonical type ID, decimal value or
null)` pairs, encoded as JSON. Null denotes a type argument. Hash uniqueness is
never involved.

Canonical type IDs are meaningful only inside their `Type_Store` lineage. Store
clones share an identity anchor; full rebuilds have a fresh anchor. Cross-lineage
instance joins are rejected. A full rebuild discards old keys but retains the
instance allocation watermark, so retained references cannot see recycled IDs.

`instance_context` owns the disjoint numeric encoding for concrete contexts:
ordinary callables use their source symbol ID in `1..MAX_SYMBOL_ID`; template
instances use `MAX_SYMBOL_ID + instance_id`. Definition IDs remain separate
provenance. Type instances map into the existing canonical type table. Template
instance record names live in an internal namespace unavailable to source syntax.

Signatures, local types, checked bodies, dependencies, lifetime results, backend
bindings and emitted functions use `callable_id`. Their source provenance retains
`symbol_id` where applicable. `for_symbol()` convenience queries refer only to
ordinary callables; templates require `for_callable()`. Debug exports expose this
distinction. Link names encode concrete callable identity through the existing
backend naming contract.

## Scheduling, replacement and cost

Workers read unchanged phase views, return private outputs and never allocate
shared identities. Joins validate tasks, results, argument provenance, prerequisites
and lineage before accepting a batch. Execution remains serial through these
same units; actual threading is deferred.

Concrete preparation admits each context once and registers its bound occurrences.
A process-local queue indexes pending requests by exact prerequisites. Accepted
nominal records and context-scoped type applications wake their dependents; completed
contexts are not rescanned. `Instance_Store` holds private current state between
batches and updates record indexes on acceptance. Workers read its query-only
`Instance_View` contract during fixed batches. Publication creates one retained
`Instance_Set`; no per-wave copies of the whole registry are needed.

Ordinary methods are concrete roots even when uncalled. Template methods remain
demand driven. The configured
[max_instances](../../language/instantiation_limits.json) cap bounds
runaway specialization with a source diagnostic. `Compiler_Session` accepts an
optional `instantiation_policy_path`; its value participates in cache validity
before the unchanged-input fast path and passes unchanged to preparation.

One ordinary function-body edit can change its demanded instances. Unchanged
bindings, constants and definitions allow application/member reuse before computation;
unchanged concrete contexts retain signature/body identity. Removed demands lose
current callable contributions, while allocation history and old snapshots remain
valid. Template-definition edits retain the existing full-rebuild fallback.
Repeated incremental recovery is not expanded by this slice.

## Proof

[explicit_instances.php](../../tests/04_analyze/instantiate/explicit_instances.php)
executes multiple specializations, a three-parameter record, nested type arguments,
constant/literal deduplication and recursive calls through native output. It checks
shared AST provenance, distinct signatures/linkage, fixed-worker purity, reversed
and rejected joins, lineage rejection, exports, unsupported-use diagnostics and
policy-limit enforcement/invalidation, and one body increment that adds/removes a demanded specialization while preserving
unchanged bodies and the previous snapshot.

[concrete_preparation.php](../../tests/04_analyze/resolve_types/concrete_preparation.php)
proves unused ordinary-method diagnostics, undemanded dependent methods, a 128-method
chain with bounded selected work, zero unchanged preparation workers and one demand
replacement preserving contexts/bodies. Existing joins also prove private candidate
acceptance without modifying prior snapshots.

The ordinary compiler suite also exercises the migrated callable identity path,
including runtime contracts, cleanup, conversions, structured values, native
modules and incremental publication.

Future versions may share generated machine code through optimization while
preserving distinct semantic specializations. No such optimization is implemented.
