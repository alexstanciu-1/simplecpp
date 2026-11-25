# Analysis cooperation proposal
Doc Status: supporting

Date: 2026-09-12.

Status: saved for discussion. No implementation changes are approved by this
document. Discuss one change, agree its concrete scope, implement and verify it,
then discuss the next. The candidate order below is not a commitment to implement
every item.

The subsequent [extension-point assessment](compiler_extension_assessment.md)
revises the candidate priority below. First discuss the checked-body and
name/type readiness contracts against upcoming features. Shared traversal is
not currently the recommended first implementation. Candidate numbers identify
the original proposal, not an approved execution order.

The first subsequently authorized implementation strengthens the checking task's
fixed input contract, preserving stage order and separate joins. See the
[assessment progress](compiler_extension_assessment.md#implemented-boundary-strengthening).
The broader candidates below remain proposals.

## Direction and agreed constraints

Clearer design is the primary objective. Keep memory and performance in view,
and preserve independent worker execution and incremental replacement/reuse.
Keep code aligned with the existing numbered process folders and file roles so
each change remains easy to review.

Preserve these six responsibilities:

- Parsing.
- Symbol collection.
- Name resolution.
- Type resolution.
- Body checking.
- Lifetime analysis.

No merger of these stages or their semantic ownership is proposed. Separate
roles stay in separate files and cooperate through explicit method calls.
Sharing an execution unit does not merge roles, results or invalidation rules.
Any later proposal to merge steps must be discussed before implementation.

Keep `collect_symbols` as it is: it visits the parser's prepared declaration
index rather than traversing function bodies, and separates file extraction
from project identity reconciliation.

The [organization rules](../code_organization.md#performance-and-memory) already
permit a process to call a following process's public preparation method when
the required fixed inputs are available. That method owns its logic and returns
private output. Coordination, selection, joins and publication remain explicit.
This does not authorize recursive phase dispatch or shared-state mutation.

The [pipeline](../compiler_pipeline.md), [organization](../code_organization.md)
and [type model](../type_model.md) remain authoritative for implemented behavior.
This proposal describes possible changes, not current APIs or readiness.

## Findings from the current implementation

| Finding | Evidence and implication |
|---|---|
| Names and checking independently navigate body structure | [Resolution_Worker](../../src/04_analyze/resolve_symbols/body.php) and [Body_Worker](../../src/04_analyze/check_bodies/body.php) both traverse blocks/statements and extract expression roles. Structural traversal is a candidate for sharing. |
| Local typing waits for a broader result than its semantic work needs | [Local_Type_Resolver](../../src/04_analyze/resolve_types/locals.php) consumes completed name results, but annotation interpretation needs ordered declarations, annotation syntax and namespace/catalog context. Current local IDs and reuse are still owned by that exact name result. |
| Signature requests do not require body name bindings | [Signature_Resolver](../../src/04_analyze/resolve_types/signatures.php) reads declaration annotations and catalog definitions. Shared materialization remains coordinator-owned. |
| Checking already prepares lifetime inputs | [Lifetime_Worker](../../src/04_analyze/analyze_lifetimes/body.php) consumes checked values, calls, arguments and scope ranges. Its traversal establishes different facts and does not repeat source name/type resolution. |
| Some dependency validation repeats | Name selection and joining revisit call targets; checking selection and joins revisit type/signature dependencies. Distinguish provenance checks from dependency re-evaluation before changing either. |

The existing data already shares useful facts: symbols reference syntax; local
types reference the name resolver's local table; checking references bindings
and canonical types; lifetime results reference the exact checked body.
Parameter types come from signatures rather than a second annotation-resolution
path. Preserve this sharing.

The earlier 5 MiB [recorded measurements](../../benchmarks/scalability/results/2026-09-11-reuse-mold-groups.json)
put full name resolution at about 1.421 seconds, including 0.674 seconds of PHP
GC. These historical timings do not isolate traversal, lookup, allocation or
validation. No new benchmark or per-structure memory measurement was performed
for this proposal; no speedup or memory reduction is established.

## Ownership of prepared facts

| Fact | Owner | Readiness |
|---|---|---|
| Declaration syntax, block nesting, statement/expression roles and argument order | Parsing | Available from the exact parsed file snapshot. |
| Project declaration identities and membership | Collection | Established by reconciling file contributions. |
| The declaration denoted by a name | Name resolution | Requires the relevant fixed declaration environment and lexical rules. |
| Canonical types and representations | Type resolution | Requires authoritative definitions and coordinated materialization. |
| Selected operations, conversions and typed values | Body checking | Requires current bindings and type contracts. |
| Reachability, value consumption and local exits | Lifetime analysis | Requires checked operations and lifetime contracts. |

Prepare information for consumers without predicting their answers. Parsing
can identify an assignment target, but cannot establish its binding validity.
A parsed return is not a completed lifetime/cleanup plan.

## Candidate 1: share structural body traversal

Strengthen the parser-owned structural interface used by names and checking.
[Syntax_Access](../../src/03_parse/utilities/syntax_access.php) already owns child roles.
A small shared traversal could expose block entry/exit, statements in their
containing blocks, and structural access to declaration/destination/expression
roles. Consumers retain their semantic decisions and private processing state.

Initially retain consumer-specific expression traversal: names visit a callee
before its arguments, while checking completes arguments and conversions before
constructing the call result. Avoid a generic visitor/event framework unless a
concrete simpler contract emerges.

Prefer sharing traversal without adding a persistent description of every name
occurrence. A prepared occurrence index could duplicate AST anchors/roles and
resolved bindings. Reconsider an index only when multiple consumers justify its
storage and replacement rules; remove or share equivalent existing data where
possible. The first candidate simplifies code, not necessarily traversal count.

Discussion must establish the exact traversal contract and show that it reduces
duplicated control logic rather than hiding it behind callbacks.

## Candidate 2: cooperate on names and declared-type preparation

When name processing encounters a local declaration, it already extracts its
annotation node. It could call type-owned preparation with that annotation and
the required fixed context, returning a private request. Signature preparation
can also run without body name bindings.

This could allow one callable work unit to prepare names and declared-type
requests while preserving separate files, APIs and results. It must not make
name resolution own type interpretation or permit worker writes to the shared
Type_Store. The type coordinator still completes the candidate before checking;
parameter IDs still come from completed signatures.

Current APIs tie local type requests and results to the completed
Symbol_Resolution and its local-ID order. The discussion must define the smaller
preparation contract and how final associations retain that exact identity.
Do not pass a partially built result through an API promising completed inputs.
Respect existing participation rules for named functions and file entries.

Early preparation can change diagnostic precedence and do work discarded by an
incremental fallback. Resolve those issues before changing scheduling.

## Candidate 3: compose checking and lifetime execution

Once the fixed type snapshot exists, a selected callable unit could obtain or
construct its completed Checked_Body, then obtain or construct its lifetime
result, and return both private outputs. The two algorithms remain separately
callable and separately owned. Project joins validate and assemble their result
sets before downstream publication.

The lifetime worker needs one completed checked body, not all project bodies.
The current whole-project barrier is therefore stronger than this dependency.
Composing work does require adapting selection/join contracts; it is not merely
moving the existing loops together.

Keep the lifetime traversal. Checking visits unreachable syntax; lifetimes cover
reachable execution and include inserted conversions, argument consumption and
scope/return exits. Future branches and loops may require further dataflow work.
Parsing should not precompute those answers.

## Possible execution arrangement

The following is conceptual and subject to the individual discussions:

```text
Parse selected files
    -> collect and reconcile project symbols
    -> prepare callable names and declared-type requests
    -> join names, compare changes and admit the update
    -> complete and join the shared type snapshot
    -> per callable: check body, then analyze lifetimes
    -> join checked bodies and lifetime results
```

Preparation before admission is disposable. On escalation, the coordinator uses
the same algorithms with full selection and rejects requests for superseded
inputs. No distinct sequential/full-build implementation is introduced.

## Candidate 4: consolidate validation and indexes

After the preceding design is assessed, distinguish task provenance,
completeness, structural validity and dependency validity in selection/joins.
Potentially retain coordinator-private validation evidence tied to the exact
fixed input snapshot instead of evaluating the same dependencies repeatedly.
Preserve rejection of missing, duplicate, removed and stale contributions.
Do not delete checks merely because existing tests happen to pass.

Review stored indexes and temporary arrays with the resulting consumer contracts.
Shared references do not copy upstream datasets; a new retained index does add
memory. PHP object/array costs do not predict packed native-record costs.

## MT, incremental and diagnostic constraints

Execution grouping must not become invalidation grouping. A composed task needs
explicit selected outputs and valid retained inputs for the other operations.
Do not rerun names merely because types changed, or unconditionally execute all
operations when only one result is stale.

Workers read fixed snapshots and write private outputs. Shared symbol/type IDs
remain coordinator-owned and deterministic. Joins validate exact inputs and
current membership. Remove deleted contributions independently of selection.
The coordinator fixes full_rebuild for tasks, and publication remains atomic at
the update level. Old snapshots remain valid after candidate failure.

Interleaving changes potential error order: currently project-wide name errors
precede type errors, and checking errors precede lifetime errors. Agree a
deterministic policy before changing scheduling. Preserving current precedence
may require deferred error reporting; do not introduce an elaborate diagnostic
framework solely to remove a batch boundary.

## Scope and proof

Non-goals: merging semantic stages; a single mutable record filled by all stages;
an exactly-one-pass requirement; a generic scheduler/visitor/dependency framework;
actual threading; new language features; PHP++ porting; GC policy changes; and
finer sibling-function reuse after file AST replacement. That last issue needs
its own source-anchor/identity design.

For each agreed change, prove real source-to-output behavior, fixed-input worker
purity, reversed completion, stale/incomplete-result rejection, unchanged and
body-edit reuse, full/selective equivalence, exports, and failure/repair rollback
as applicable. Include shadowing, self-initializer reads, nested arguments,
parameters, forward/recursive calls, conversions and unreachable code where the
changed contract affects them. Count actual work when claiming reduced traversal
or reuse. Use focused memory/timing checks when relevant, with record/index
growth made explicit.

The original order (shared traversal, name/type preparation, checking/lifetime
composition, validation/index consolidation) is superseded by the
[extension assessment's next-discussion recommendation](compiler_extension_assessment.md#recommended-next-discussion-and-implementation-gates).
Each item still requires its own scope decision and review before implementation.
Broader ownership changes must be discussed explicitly rather than absorbed into
a local change.
