# Compiler extension-point assessment
Doc Status: supporting

Date: 2026-09-12.

Status: architecture investigation and recommendations for discussion, not an
approved implementation plan. The original assessment was read-only; subsequent
authorized implementation is recorded below. This assessment revises
the candidate priority in the [cooperation proposal](analysis_cooperation_proposal.md).
Discuss and agree each concrete change before implementing it.

## Conclusion

Keep the six process owners, existing folder/file organization, fixed-input
workers, separate results and coordinator joins. These are useful foundations
for the remaining compiler work. No stage merger is recommended.

Do not freeze contracts that encode the current scalar, straight-line subset.
The outer source/declaration/semantic/lifetime/backend flow can remain
recognizable, but future capabilities need new record variants, richer result
contracts and, only where dependencies require them, additional coordinator work
rounds. Adding fields alone cannot express all those changes truthfully.

Prioritize the extension contracts of checked bodies, resolution and type
requests before sharing traversal or interleaving stages. Otherwise a cleanup
may turn a temporary subset assumption into a shared constraint.

## Evidence and scope

The assessment reads the active PHP implementation and relevant tests and
compares it with the [foundations tracker](../planning/compiler_foundations.md),
[pipeline](../compiler_pipeline.md), [organization](../code_organization.md) and
[type model](../type_model.md). Future designs below are proposals/inferences,
not implemented APIs. Existing test sources were inspected, not rerun for this
documentation change. No new timing or memory claims are made.

The immediate roadmap includes operators, if/else and while, scalar external ABI
calls, structured values, consumed template families and ownership. Members and
overload sets also serve as design pressure cases; this assessment does not move
deferred overloads, closures, inheritance, exceptions or general template
authoring into the implementation scope. Full language support is not proved by
these examples.

## Stable foundations and subset contracts

| Area | Preserve | Extension required when the feature arrives |
|---|---|---|
| Parsing | Exact file snapshots, flat nodes, source spans, declaration index, syntax-role access | Explicit syntax kinds/roles for branches, operators, member/index access and type expressions; logical child order must remain distinct from execution order. |
| Collection | Per-file contributions, project identities, deterministic reconciliation and removals | New declaration kinds and parent/member reconciliation. Overloads eventually require candidate membership and an identity policy beyond unique name lookup. Keep current collection unchanged now. |
| Name resolution | Lexical scope/local identities and source-anchored uses | Early lexical binding must be distinguishable from selection that requires receiver/argument types. Not every future use can resolve to one project function ID immediately. |
| Type resolution | Canonical IDs distinct from representations; candidate ownership and interning | Family-definition/argument identity, recursive materialization and requests for compound type syntax. Named scalar definitions are not a general instantiated-type model. |
| Body checking | One typed body per callable, explicit conversions and ordered effects | Typed operation contracts, explicit control flow, writable locations and richer call targets. A global statement prefix is not a general execution model. |
| Lifetimes | Per-callable analysis over completed checked facts and provider lifetime rules | Flow-sensitive state, transfer/cleanup operations and exits associated with control flow rather than only statement numbers. |
| Backend boundary | Separate target facts, lowering plans, emission and publication | Multi-block lowering, layouts, richer ABI passing and executable provider bindings. Semantic representation storage alone does not provide these. |

## 1. Operators and typed operation construction

Current evidence:

- [typed_value](../../src/04_analyze/check_bodies/data/structures.php) has
  literal, call-result, local-read and conversion variants. Explicit unary
  conversion results already demonstrate adding a real operation to the shared
  value path.
- [Body_Worker](../../src/04_analyze/check_bodies/body.php) checks
  operands and conversions, but has no general operator selector.
- Provider [operation_contract and implementation_binding](../../src/04_analyze/type_model/data/definitions.php)
  are descriptors. The catalog does not yet import executable operation sets,
  and descriptor construction does not establish backend implementation.

Proposed extension: keep checking responsible for selecting an operation from
operand types and provider contracts. Put selection in a clearly named
processing file under check_bodies, with typed results in its existing
structures/result ownership. Type resolution provides identities and type
contracts; it does not become an expression checker. Lowering implements the
selected language operation using verified backend primitives.

The scalar operator slice can preserve the current outer phase calls. It still
needs a complete provider/checking/lowering/emission path; an operation name and
an enum entry alone are insufficient. First define arithmetic/conversion rules
from authoritative language/provider contracts rather than infer them from LLVM.

Proof pressure: an operator combining a local, literal and call result; conversion
at a return/assignment boundary; rejection of an unsupported operand pair; native
output and resident body-edit equivalence. This matches the existing roadmap.

## 2. Control flow is the most immediate representation boundary

Current evidence:

- [Body_Worker::check](../../src/04_analyze/check_bodies/body.php)
  flattens nested blocks, sets falls_through false for any encountered return,
  and still checks subsequent source.
- [Analyzed_Body](../../src/04_analyze/analyze_lifetimes/data/result.php)
  represents reachability as reachable_statement_count, and
  [Lifetime_Worker](../../src/04_analyze/analyze_lifetimes/body.php)
  follows a prefix through the first return. Its initialized set and live-local
  stack describe one straight-line execution.
- [Lowering_Worker](../../src/05_generate_code/lower/body.php) consumes
  that prefix and produces one block. The
  [block contract](../../src/05_generate_code/lower/data/structures.php)
  has only a return terminator; [LLVM emission](../../src/05_generate_code/emit_llvm/main_emit_llvm.php)
  explicitly rejects multiple blocks.

Pressure cases, expressed conceptually rather than as supported source syntax:

```text
if condition:
    return first_value
return second_value

while condition:
    declare and initialize a local
    update an outer local
use the outer local
```

One return does not make all later source unreachable on every path. One static
local declaration in a loop may initialize a runtime local repeatedly. A lexical
scope's source range cannot by itself identify all runtime exits.

Proposed direction: checking owns typed control-flow structure, using explicit
blocks/edges or a structured representation with equally explicit branches,
loops and exits. The preferred design discussion should evaluate typed basic
blocks as the common analysis input. Lifetime analysis owns state propagation
over that structure; lowering owns its target execution form. Keep those roles
in separate files and preserve the check -> analyze -> lower call boundary.

Return-path checks and lifetime reachability should use the same checked flow
facts or a shared flow helper owned alongside that representation. They should
not independently infer execution from AST order. Do not introduce both a
separate flow tree and a graph without concrete consumers justifying both.

Existing mutable local slots offer a route to initial branch/loop lowering;
there is no need to require SSA conversion or phi nodes for locals as part of
the first control-flow slice. Temporary values still need valid definitions and
uses on each path. Condition typing, joins and loop analysis must be explicit.

This is a coordinated change spanning checking, lifetimes, lowering and emission,
not an isolated parser case. It requires a separately agreed scope and native
proofs for both branch outcomes, repeated loop execution, returns and updates.
Do not implement that refactor as part of traversal consolidation.

## 3. Names, members and overload selection

Current evidence: [Symbol_Resolver](../../src/04_analyze/resolve_symbols/main_resolve_symbols.php)
maps each call name to one exact project function. The
[Symbol_Store](../../src/04_analyze/collect_symbols/data/store.php) indexes
one symbol per name/namespace/kind/owner key. Local bindings depend on lexical
scope and declaration order. These are correct for the supported language.

For a future member use, first bind the receiver's lexical name; then its type
determines available fields/methods. For overloads, argument types can determine
which candidate is selected. A missing member name cannot always be diagnosed
before that type context exists.

Preserve early lexical binding. Add explicit unresolved/type-dependent reference
forms only with their feature; do not weaken today's completed direct bindings.
Checking can call the appropriate resolution service with typed operands and
fixed lookup inputs. Name lookup supplies declarations/candidates; checking owns
operation applicability and conversions under the type model. A completed
checked call must carry the selected target/contract for later consumers.

Overloads would require extending the collection identity/index contract as
well: name lookup returns candidates, and individual declarations still need
stable identities and duplicate rules. Adding a signature spelling to a key is
not an adequate substitute for designing those semantics. There is no need to
merge collection, name resolution and checking to support this interaction.

Proof pressure: direct calls still take the simple existing path; two receiver
types can select different members; ambiguity/missing members fail at the right
source anchor; adding a candidate invalidates affected selection even if the
previous target still exists. These are future feature gates, not current tests.

## 4. Stored locations and computed values

Current evidence: a checked local read produces a value and an assignment targets
a local ID. Lowering already separates value IDs from local slot IDs. That is a
good foundation, but the checked model has no general writable field/element
location or reference/transfer intent.

Before field/index assignment or reference passing, introduce a checking-owned
concept for a typed storage location: initially local, later field/element
projections as supported. Reads, writes and references consume that location
through explicit operations. A location is not automatically a copied value.
Keep source anchors and resolved member identity; target byte offsets belong to
layout/lowering, not parsing or name lookup.

Avoid growing target_local_id into unrelated optional local/field/index fields
on every statement. Use a concrete discriminated record/path when the feature
requires it. Avoid per-node location metadata for expressions that are only values.

Proof pressure: field read and field write share one resolved field contract;
receiver/index expressions execute once in the language-defined order; copying
an aggregate and referring to its storage remain distinct. This work belongs
with structured values and their agreed ownership rules, not a cleanup now.

## 5. Type requests and consumed generic families

Current evidence:

- [Type_Store](../../src/04_analyze/type_model/data/store.php) separates
  identities, declaration state and representations, and supports stored
  pointer/array/structure/signature graphs.
- Canonical named identities are indexed by name and namespace.
  [type_record](../../src/04_analyze/type_model/data/representations.php)
  binds a named_type_definition; no family-instance key is implemented.
- [Type_Resolver](../../src/04_analyze/resolve_types/main_resolve_types.php)
  accepts leaf-name annotations and materializes scalar/void definitions.
  [local/signature requests](../../src/04_analyze/resolve_types/data/structures.php)
  retain those definitions; they cannot describe arbitrary compound type syntax.
- The [catalog loader](../../src/01_prepare_inputs/load_runtime/main_load_runtime.php)
  imports named scalar definitions. Representation records and pending IDs do
  not implement provider family expansion or instantiated operations.

The first explicitly annotated vector<int> and vector<vector<int>> cases can
potentially finish recursive materialization entirely in the type phase.
Use a family-definition identity plus ordered canonical argument identities
(and validated constant arguments when supported); display spelling is not the
canonical key. Share each materialized instance and its contracts across uses.
Nested instances must use the same generic algorithm, not enumerated cases.

Therefore generics do not automatically require a new scheduler. First extend
type-expression requests and the type-owned materializer for that finite,
explicit subset, with real provider construction/element behavior and ownership.

If later checking discovers a missing instance through inference or operation
selection, a fixed worker must not mutate its shared type input. A possible
extension is a private needs-type result, coordinator materialization/join, then
selection of the affected callable against the new fixed snapshot. Restarting a
callable is simpler initially than a general continuation engine. This protocol
needs progress/dependency/error rules before use; it is not implemented now.
Never publish a partial Checked_Body as if completed, or reinterpret IDs from a
different type lineage. Additional rounds would remain within semantic
coordination, not recursive calls to compile().

Proof pressure: repeated and nested instances share identities; reversed request
completion has no effect; failures preserve retained types; any future work
round resumes only genuinely affected work and terminates or reports a precise
unsupported/cyclic dependency. General inference and cyclic definitions remain
separate from the finite nested-vector proof.

## 6. Managed values and cleanup

Current [lifetime_contract](../../src/04_analyze/type_model/data/definitions.php)
supports only copy:value and cleanup:none. Values end at statement/call/conversion
consumers; reached locals have one initialization/end pair. These facts prove
scalar copying, not ownership transfer or cleanup support.

Extend provider contracts with implemented construction/copy/transfer/cleanup
operations as required by the chosen value model. Checking establishes typed
operation obligations and location/value distinctions. Lifetimes track state
and discharge those obligations on actual flow exits. Lowering emits explicit
cleanup/transfer actions using prepared implementations.

An owned return must keep returned contents valid and clean up other live locals;
overwriting an owned destination may require ending its previous contents. Loops
and branches require path-aware state. One extra cleanup flag or one extra end
enum does not express this behavior. Do not design full borrowing/exceptions
unless the selected language contract requires them.

The parser can preserve construction and exit syntax but cannot precompute final
lifetimes. Keep lifetime analysis separately callable over a completed checked
body. Its internal analysis may require several passes without altering the
outer call boundary.

## 7. Declarations, startup, layout and ABI

Collection has owner IDs and file contributions, but only function and implicit
entry declaration kinds are implemented. Classes/members need parent identity
before member reconciliation and their own definition/body comparison rules.
Extending collection at that time is compatible with leaving it unchanged in
this investigation.

[Entry_Resolver](../../src/04_analyze/resolve_types/main_prepare_entry.php) explicitly
rejects executable top-level code outside the selected entry. Runtime global
initializers need a defined startup order and dependency policy; compile-time
constants need evaluation. Neither should be hidden in parsing or represented
as a fabricated ordinary runtime success.

The backend owns verified target facts, but
[LLVM_Types](../../src/05_generate_code/prepare_backend/utilities/llvm_types.php) and prepared
callable parameters currently support scalar storage/passing. Structured values
need authoritative layout and possibly indirect/split ABI forms. Real external
calls also need imported callable implementations, not only the existing binding
descriptor. Keep these decisions behind backend preparation; do not infer layout
or foreign ABI support from a representation kind.

## 8. Incremental and MT extension constraints

Keep logical symbol IDs distinct from AST-local IDs and type-lineage IDs. Current
name/signature associations require the exact file AST; a file edit replaces
sibling callable associations. Separating reusable meaning from fresh source
anchors is a future broader change, not a prerequisite for language correctness.

For richer features, dependency facts must describe what was actually consulted:
member lookup, overload candidate sets (including relevant absence), provider
definitions, family arguments, layout and ABI context. Tracking only the chosen
callee ID cannot prove overload selection remains valid when another candidate
is added. Producer validity rules and coordinator incremental admission remain
separate; keep full fallback until each new impact category is proved.

There is no need to build a general dependency engine now. Current
[Input_Selection](../../src/compile/inputs.php) admits unchanged callable
definitions with body changes and falls back otherwise. Update this policy only
with feature-specific proofs. Do not confuse a reusable type representation with
unchanged language semantics or executable behavior.

Per-file parsing and per-callable semantic tasks remain useful units. Control
flow iterations can remain inside a callable worker. Shared symbol/type joins
stay coordinated; a worker cannot write a shared cache because it happens to
run serially today. Composition may use completed private outputs in the next
operation, but must keep independent output selection and fixed dependencies.
Real threading, scheduling thresholds and splitting a very large callable are
not prerequisites for this assessment.

New records should scale with real concepts: blocks/edges for flow, location
projections for addressed storage, one canonical instance per type application,
and cleanup facts at relevant operations/exits. Keep scratch state private and
bounded by the selected analysis. Do not create dense all-values-by-all-blocks
tables or universal optional metadata on every AST node without measured need.
Old/candidate snapshots overlap; preserve sharing and account for retention.

## Consequences for the cooperation proposal

| Original candidate | Revised recommendation | Reason |
|---|---|---|
| Shared structural body traversal | Defer implementation until the syntax/control-flow contract is discussed. Retain current Syntax_Access. | A generic flat statement walk could cement the straight-line assumption. Shared syntax traversal must preserve conditional/loop roles without deciding execution. |
| Earlier name/type preparation | Retain as a narrowly scoped option after defining type-expression requests and completed-result identities. Do not merge full resolution stages. | Gathering annotations early is useful; coupling all type work to the lexical name walk would obstruct type-dependent operations and inference. |
| Checking/lifetime work-unit composition | Architecturally compatible, but not the first priority. Keep separate algorithms and joins. | A completed callable body is a durable boundary, including CFG/dataflow; streaming lifetime decisions from parser events is not. Diagnostic order and independent selection still need design. |
| Validation/index consolidation | Consider local simplifications only with exact dependency/provenance proofs; defer broad consolidation. | Richer lookup and type contexts increase dependency requirements. Reducing checks around today's narrow keys could be misleading. |

Do not implement a generic walker, extra prepared body representation, universal
semantic result or dependency framework before the corresponding contract is
agreed. The existing stage count is not the central problem.

## Recommended next discussion and implementation gates

First discuss what a completed Checked_Body guarantees as operations and control
flow grow: source versus execution order, typed values/effects, writable
locations, branch/loop structure, exits, and what lifetime analysis can assume.
Set the ownership and extension direction without designing every future record
or implementing all these capabilities at once.

Then discuss name/type readiness: what lexical binding completes, what requires
typed operands, and how type-owned requests can be completed without worker
mutation. This is a contract discussion, not authorization for a new scheduler.

Use the existing feature sequence (operator, control flow, ABI, structured and
consumed template values with ownership) for concrete vertical proofs. It is
not necessary to postpone the operator until all future designs are complete.
Review each affected contract immediately before its feature slice; introduce
only the fields/variants/process helpers that have a real producer and consumer.

Every slice should preserve the common source-to-output path, deterministic
worker/join results, full/selective equivalence where admitted, failure/repair,
exports and source anchors. Test both paths or repeated execution for flow,
canonical sharing for types, and executable cleanup behavior for owned values.
Unsupported update categories retain full fallback. Explain scope, risks and
validation cost and obtain agreement before any cross-owner implementation.

This assessment does not establish exact memory layouts, algorithm timings,
overload rules, ownership policy or the full target language. Those choices need
their own concrete feature contracts. The durable commitment is clear owners
and explicit readiness, not an immutable sequence of one-time phase calls.

## Implemented boundary strengthening

Following authorization to strengthen specific boundaries while preserving the
structure, the first bounded change makes body-checking inputs explicit.
Selection now captures a callable owner, its exact name bindings and the completed
type snapshot in a temporary `body_check_task`. Checking takes that task and
derives the prepared literal default from its type owner. Worker and join checks
protect callable associations and snapshot provenance.

The ordinary stage order, separate owners/results/joins and incremental admission
are preserved. A focused proof composes checking and lifetime workers before
their separate joins; this does not introduce combined scheduling. See
[body checking](body_checking.md#work-storage-and-reuse) for the contract and
measured temporary-memory cost. Control-flow, name/type readiness and other
feature-specific contracts above remain future work.

The subsequently authorized addition/control-flow work now establishes selected
exact-type operations, shared checked-expression traversal, typed basic blocks,
flow-sensitive scalar initialization/exit facts and multi-block lowering/emission.
See [the implemented contracts and proofs](operations_and_control_flow.md).
The original straight-line limitations above record the assessment baseline;
member lookup, generic-family requests and managed ownership remain future work.
