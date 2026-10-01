# Specialized AST nodes
Doc Status: historical

Archived 2026-09-29. This records an earlier design/checkpoint, not current instructions.
See [the documentation index](../README.md) for active guidance.

## Agreed direction

Replace the final `ast_node` header plus `node_structure` payload with concrete
specialized nodes. Use an `ast_node_i` interface and a property-free abstract
`ast_node` base. Concrete nodes own their syntax fields, applicable collected
occurrence, and applicable preparation facts. Workers retain compilation algorithms.
This deliberately retires the single concrete node-layout constraint.

Generic `parent()` / `children()` access is for inspection. Compiler stages must
not depend on a universal children collection, sibling links, or snapshot traversal.
A function owns parameters, return type and body directly; a block owns statements.
No additional canonical child list duplicates these relationships.

This migration does not address staged initialization, expand supported syntax,
implement templates, redesign preparation dependencies, or resume LLVM semantic
work. Existing behavior, incremental identities, and parked LLVM regressions must
be preserved. No native compiler validation is included unless requested.

## Complete node inventory

| Existing representation | Proposed concrete node | Owned syntax / relevant data |
| --- | --- | --- |
| block_structure + file kind | file_node | Declaration list, required file-scope observer and explicit executable body. |
| block_structure + executable body | function_body_node | Ordered statements, required local-scope observer, syntax comparison flag and canonical body work owner. |
| block_structure + nested block | block_node | Ordered statements; no independent work owner. |
| identifier_structure in type position | named_type_node | Saved type spelling, span and collected occurrence. |
| empty_node_structure + punctuation kind | punctuation_node | Token span only. Preserve existing punctuation pending its separate performance debt. |
| empty_node_structure + comment kind | comment_node | Token span only. |
| integer_literal_structure | integer_literal_node | Token span and prepared integer facts. |
| float_literal_structure | float_literal_node | Token span and prepared float facts. |
| boolean_literal_structure | boolean_literal_node | Boolean value and prepared boolean facts. |
| variable_reference_structure | variable_reference_node | Collected occurrence and prepared reference facts. |
| call_structure | call_node | Typed arguments/template arguments, saved name, span, occurrence and call facts. |
| function_structure | function_node | Parameters, return type, explicit body node, saved name, owned signature scope, template metadata and signature facts. |
| parameter_structure | parameter_node | Type syntax, saved name, passing mode, occurrence and facts. |
| binary_structure + binary kind | binary_expression_node | Left/right operands and operator position. |
| binary_structure + assignment kind | assignment_expression_node | Target/value, saved assignment operator and expression facts; target owns its binding occurrence. |
| expression_statement_structure | expression_statement_node | Expression and source span. |
| return_structure | return_node | Optional expression and source span. |
| binding_structure, explicit declaration | variable_declaration_node | Required type, optional initializer, saved name, occurrence and binding facts. |
| binding_structure, untyped assignment | expression_statement_node + assignment_expression_node | One assignment-expression path, also usable at nested expression positions. |
| array_type_structure | array_type_node | Element type and count expression. |
| array_literal_structure | array_literal_node | Ordered elements. |
| index_structure | index_node | Base and index expression. |
| struct_structure | struct_node | Fields, owned member scope, saved name, occurrence and prepared record facts. |
| field_structure | field_node | Type syntax, saved name, occurrence and facts. |
| field_access_structure | field_access_node | Base, saved member name, occurrence and prepared access facts. |

The proposal uses saved names and common half-open spans, not dedicated punctuation
positions. Binary operator identity remains a separately recorded extension point.
The assignment/declaration split is agreed representation work; it does not expand
production grammar in this migration. The proposal-local kind enum distinguishes
function bodies from blocks and named types from other name uses.

## Common contract and concrete ownership

- The interface describes supported node operations. The abstract base supplies
  behavior only; it has no retained properties.
- Concrete classes declare their spans, using a small shared trait where appropriate.
  Name-bearing nodes alone use Collected_Occurrence; prepared nodes alone use the
  applicable facts accessors. Existing concrete require_preparation methods remain.
- Kind, where still required by diagnostics/parked LLVM, is derived from the concrete
  class rather than independently mutable. Shared old payload kinds become distinct
  concrete nodes, preventing class/kind disagreement.
- Debug parent links are weak observers, maintained at syntax attachment/replacement.
  They do not own nodes. Inspection children are derived from named fields on demand.
- Remove generic next/previous/position/first-child storage. Ordered statement and
  argument collections own order; named operand fields own fixed grammar order.
- Preserve required constructor arguments where they already exist. Do not introduce
  dummy defaults or nullable weakening to appease STAN. Parser-created incomplete
  retained declarations still follow the current incomplete-file publication gate.

## Issues identified in the current code

1. **Incremental parser cursors:** parse(), retain_file_body(), next_executable()
   currently compare linked siblings. Replace this with a cursor over the old file's
   statement collection; pull retained declarations into the new collection as now.
   No separate persistent order index is needed.
2. **Retained subtree relocation:** retain_tree() walks child links and shifts spans,
   specialization token fields, and collected occurrences together. Its replacement
   must visit each owned syntax edge once and must not traverse resolved declarations,
   scopes, parent links or prepared dependency graphs.
3. **Preparation cleanup:** Preparation_Cleanup::tree uses the same generic links.
   Keep worker ownership and local fact clearing while changing traversal dispatch.
4. **Preparation/C++ body iteration:** File_Preparation, Preparation_Worker and
   CPP_Generator use first_child/next for files and bodies. Use concrete file/block
   statement collections. Function/struct processing uses named fields directly.
5. **Identity:** collection entries, preparation owners and retained C++ fragments
   ultimately identify syntax nodes. Reusing a declaration must now reuse the one
   specialized object, not create a wrapper or transfer facts to another object.
6. **Dispatch:** existing hooks receive both payload `$this` and the outer node.
   Remove this redundant two-object arrangement. Check the portable PHP/native
   representation of passing `$this` where an owning handle is required before
   settling hook arguments; do not silently manufacture native shared ownership.
7. **Parked LLVM:** its kind-based processing and Syntax_Nodes casts still consume
   the active AST. Adapt access mechanically, preserving its current algorithms and
   tests. No new LLVM semantic preparation is part of this migration.
8. **Inspection/tests:** AST tests currently assert linked-sibling topology. Replace
   those assertions with concrete shape, unique ownership, grammar-order inspection,
   debug parent correctness and retained identity assertions. Do not merely delete
   invariant coverage when removing links.
9. **Portability:** traits and abstract/interface inheritance are already used, but
   trait flattening, inherited metadata and generated declaration ordering must be
   reviewed for the new hierarchy. Extend bounded converter support if needed;
   do not keep a fake wrapper/payload API as a permanent workaround.

## Consolidated lifecycle and maintenance decision

See the [active AST guide](../architecture/ast_layout.md) and
[non-expression audit](non_expression_structure_audit.md). The following replaces
the earlier open choice of separate maintenance hooks versus a typed visitor.

- Every concrete node implements `maintain(node_maintenance_worker_i)` and forwards
  to its typed `visit_*` method. Relocation and preparation cleanup implement the
  same dispatch contract in separate workers. No operation enum or generic child
  traversal chooses their algorithms, and unsupported/trivia nodes cannot silently
  skip maintenance. Workers retain context; nodes retain no worker.
- Workers traverse only owned typed syntax fields/collections. Inspection iterators,
  weak parents, scope ancestry, occurrences and dependency edges are not syntax
  traversal. Existing lifecycle/publication owners retain locks, parse-complete
  gates, scope index updates, reparenting and subtree selection.
- File/body scopes remain retained by the parsed file (or the model for an externally
  supplied file scope). Functions and structs own signature/member scopes. Semantic
  work retains the parsed-file context while reading required observers or tokens.
  An independently retained node/iterator does not confer a live semantic context.
- `function_body_node.work()` is the canonical body preparation owner, including
  file executable work. Its `syntax_changed` flag records parser comparison only;
  dependency retry and backend pending state remain separate. Replace duplicate
  file/function body-work slots with access to the same canonical owner during
  migration; lists/indexes may retain that identity, not create competing owners.
- When a body is unchanged, retain its node, scope, facts and work identity. When
  replacing syntax for the same semantic body, the lifecycle retains the old work
  owner, notifies/invalidates consumers as required, removes stale facts/dependency
  memberships, detaches it from the old body and attaches that same owner to the new
  body. It remains pending until successful preparation. Retire the old scope only
  after its dependent/index cleanup; deletion notifies dependents before unlinking
  and releasing owners. Unexpected failures preserve the full-rebuild retry policy.
- Migrate `collected_name.node`, `parsed_file.root`, preparation references and C++
  owner links together. The proposal's imported production records are placeholders,
  not a compatible two-AST bridge. On matched declarations retain the same specialized
  node, canonical occurrence, declaration work owner and backend record. Within a
  running migrated compiler, incremental updates do not allocate replacement symbol
  identities. No hot conversion of already-live old-layout compiler sessions is
  promised; start that version with a fresh compilation model.
- The parser attaches an occurrence once; relocation updates its provenance without
  reattachment. Retirement never makes the old syntax eligible for re-entry. Weak
  annotations and explicit cleanup keep observers distinct from ownership.

Native worker implementations and lifecycle integration remain migration work;
these proposal contracts are not a claim that cleanup/publication has been ported.
Future signature-only declarations, namespace execution segments and richer
block/type/generic models remain feature-specific extensions, not part of this pass.

## Pre-migration contracts consolidated

### Typed preparation results

`expression_node::require_preparation()` exposes prepared_expression; supported
specializations narrow the return to their concrete fact type. Unsupported
expressions explicitly reject access. `type_node::require_preparation()` exposes
canonical type_definition; named/fixed-array type nodes attach a nullable alias
through Preparation_Facts. Clearing that alias does not mutate the shared type.
Workers can process an arbitrary operand/type through its category without casts
or kind dispatch. No result slot is added to unrelated nodes or category bases.

`prepare()` remains void: it attaches facts in the worker's current context. A
caller reads the typed result only after successful preparation. Missing facts
must fail at the required return boundary. Native covariance, weak/nullable returns
and trait field-type specialization still need the focused portability checkpoint.

### Generation dispatch

`generate_cpp(cpp_generation_worker_i): string` forwards supported node forms to
typed worker methods. Unsupported nodes reject, rather than returning empty text.
Type syntax is consumed through resolved type facts; it is not emitted by ordinary
expression dispatch. Function-signature and executable-body fragments stay separate.
Workers retain recursive rendering, includes and fragment publication; generating
text alone must not settle a retained backend record before all publication succeeds.
No runtime include partitioning or output-layout change is introduced.

### Deleted, retained and retired owners

The proposal Work_Entry demonstrates checks to integrate into existing preparation
and generation entry methods, not a new scheduler. Check the canonical owner,
source deletion and (if present) declaration deletion before dispatch. Incomplete
parses also reject. Generation additionally requires ready, nonfailed preparation.
Pending/failed preparation remains retryable; those states are not deletion.
Recursive child calls use the validated owner's context without per-node flags.

A deleted retained declaration remains a tombstone eligible for parser reconciliation
until retirement. Reconciliation alone may reactivate it, retaining node/occurrence
identity, updating revision/state, and scheduling affected owners before normal
processing. Generation and preparation never reactivate it implicitly.
Retirement means removal from reconciliation indexes and active work enrollment,
after notification and cleanup. A later same-name declaration gets a new identity.
Cleanup calls maintain() without the active-work guard, including on deleted owners.
A retained pointer is not evidence of active enrollment; scheduling owns that check.

### Collection mapping for declaration/assignment separation

| Source shape | Occurrence owner | Preparation/identity rule |
| --- | --- | --- |
| Explicit typed local declaration | variable_declaration_node | Register the saved name/type in the current lexical context and reuse prepared_binding storage facts. |
| Assignment to a plain variable | assignment target variable_reference_node | Collect target in binding/write role, not ordinary value-read role; resolve first introduction versus existing storage through the existing binding algorithm. |
| Assignment to a field/index | Existing target reference/path nodes | Resolve the target path as a write; do not create a local declaration for its spelling or duplicate the field identity on the assignment. |
| Ordinary variable read | variable_reference_node | Existing read-resolution behavior; syntax class alone does not determine occurrence role. |

The assignment owns prepared_assignment, which describes the result and retains the
prepared_binding outcome. It does not acquire its own competing collected occurrence.
`prepare_assignment` must route the target through write/binding resolution, not call
ordinary reference preparation before a first declaration exists. Explicit declarations
and assignments use the same binding machinery in the existing semantic owner.

Update collection reference inventories, body reconciliation, cleanup, diagnostics and
C++ emission together: code which previously recovered binding data from an occurrence's
statement node must now use its target and enclosing assignment context. Derive this
context from the typed worker/parser operation; do not introduce a semantic backlink
through inspection parent(). Preserve one binding occurrence per plain-variable target
and stable symbol identity for retained syntax. Changed whole bodies follow the existing
replacement policy; no hot transfer between live old-layout/new-layout sessions is promised.
The new source compiler preserves existing accepted programs; the expressive AST shape
alone does not authorize adding chain parsing or compound assignment in this migration.

### Focused checks and outstanding native checkpoint

A PHP proposal harness checked all 24 concrete-node maintenance dispatches, 19
preparation forwards and 17 C++ forwards; unsupported generation rejects. It also
checked category-level fact/type access, missing facts, scope access, canonical body
work transfer, deletion/incomplete-parse rejection before worker invocation, pending
preparation retry and failed/not-ready generation rejection. Workers in this harness
are dispatch doubles: no production semantic evaluation, real retirement cleanup or
C++ output correctness is claimed.

Before full production migration is considered proved, run a small PHP++/converter
slice for inherited/covariant methods, property traits, required nullable/weak returns,
typed collection iteration and existing owning-handle transfer into iterators. Fix
bounded toolchain gaps at their owners; do not weaken these contracts to fit PHP-only
success. This is still pending and is distinct from compiling the complete my-try
compiler, which remains explicitly opt-in.

## Implementation sequence

1. Establish the agreed interface, property-free base, concrete-node names and
   operation dispatch. Keep expression/statement/unsupported rejection behavior clear.
2. Migrate every node in the inventory and node construction; remove outer wrappers,
   payload access and kind/payload validation. Keep binding/parameter invariants.
3. Update parser/collector creation, retained declaration matching, file-body cursors,
   relocation and diagnostic parent maintenance. Preserve early collection and locks.
4. Update preparation cleanup and typed declaration/body/expression access. Preserve
   dependency notification, pending/failed state and deleted-element cleanup.
5. Update C++ generation to typed nodes and statement collections. Preserve stable
   names, retained fragments, cached includes and single-main.cpp output.
6. Mechanically adapt parked LLVM, host reporting and tests. Remove obsolete factories,
   access shims, sibling APIs and misleading ownership comments rather than retaining
   both AST models.
7. Update architecture/portability guidance with the new ownership rules, why the
   former layout was retired, and any demonstrated converter limits. Keep historical
   successful build evidence distinct from current guarantees.

## Verification

Use focused PHP suites covering AST/structure access/dispatch, parse collection,
retained-body relocation, incremental preparation/recovery/deletion, retained C++
generation and existing LLVM behavior. Compare output and retained identities, not
only successful loading. Review for remaining payload/node pairs and compiler-stage
uses of generic inspection traversal. Conversion checks are appropriate only for a
converter change made in this slice. Native build/STAN initialization work is separate.

The working tree contains earlier uncommitted toolchain and formatting fixes. Preserve
those changes and keep the migration's evidence distinguishable. Do not claim the
existing native-build blocker is solved by this representation change.

## Initial implementation checkpoint — 2026-09-29 (historical)

Pre-migration checkpoint committed/pushed as `deee122a`. Implementation began with
its bounded portability checkpoint; the production AST has not been replaced yet.
The candidate toolchain is this working checkout based on `deee122a`, with the
trait-property converter extension below, not the older pinned portability target.

Completed prerequisite: direct trait instance fields now expand through the existing
class-field converter. Field collisions with a consumer or another direct trait are
rejected; no PHP property-merging or inherited-field resolution was introduced.
Focused proofs passed: `trait_properties.py`, `traits.py`, `trait_field_types.py`
under `tests/portability/`. These are PHP/conversion checks, not native execution.

The bounded source fixture is
`tests/portability/fixtures/specialized_ast/main.php`. It executes as `17:17` in PHP
and converts successfully. A normal strict candidate build stops at STAN:
`integer_node::require_preparation()` differs from the abstract return contract.
For diagnosis only, generation with `--no-stan` followed by Clang 18 syntax checking
confirmed two independent native representation gaps:

1. `shared_p<integer_facts>` cannot override a virtual method returning
   `shared_p<facts>` in C++. The existing emitter uses those signatures directly.
   Supporting the proposal's covariance requires an agreed lowering plus coordinated
   STAN contract checking. Disabling STAN does not solve it.
2. `$this` passed to a worker emits raw `this`, while class arguments require
   `shared_p<T>`. It cannot safely construct a new shared owner from that pointer.
   Retaining node iterators have the same ownership requirement. The implementation
   must preserve the existing control block, not introduce duplicate ownership.

No native executable was produced. The normal build stopped at STAN; the diagnostic
bypass generated source but lacked a runtime artifact. Direct `-fsyntax-only` on
that source then reported the covariance and raw-pointer argument errors, without
requiring or rebuilding the runtime. No full native my-try compilation was run.

A decision was requested before widening into STAN/emitter/shared-ownership work,
per the repository's cross-ownership refactor rule. Preserve the proposal pending
that decision; do not silently replace typed accessors, manufacture unsafe handles,
or present a PHP-only AST rewrite as portable.

### Required final implementation audit

After migration, compare the production structures against the frozen proposal at
`deee122a`, recording each actual departure and its reason. The audit must cover:

- all 24 concrete node families, category bases, exact kind tags and source spans;
- typed owning fields/collections, stable saved names, and inspection-only traversal;
- scope ownership/accessors and occurrence placement;
- preparation facts/result types and declaration/assignment separation;
- canonical body work, retained identities, replacement, deletion and retirement;
- preparation/generation/maintenance dispatch and worker ownership;
- iterator ownership and the proven PHP/converter/native capability boundary.

The initial checkpoint above preceded authorization to extend the toolchain.

## Current implementation and audit

User authorized the covariance/shared-self extensions while preserving the proposal.
The production PHP node migration is now implemented and its focused regressions
pass. The [structure audit](specialized_ast_migration_audit.md) compares all 24 nodes
against `deee122a`, records the small implementation differences and distinguishes
the bounded native covariance/ownership and production iterator proofs from a full
native compiler build. The separately authorized iterator tooling preserves lazy
typed traversal; its small adapter change is recorded in the audit.
