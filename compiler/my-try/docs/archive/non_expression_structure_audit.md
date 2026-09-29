# Non-expression structure extensibility audit
Doc Status: historical

Archived 2026-09-29. This records an earlier design/checkpoint, not current instructions.
See [the documentation index](../README.md) for active guidance.

Review date: 2026-09-29. This complements the
[expression audit](expression_model_audit.md); expression nodes, operators,
assignment/call composition and their semantics are excluded here.

**Verdict: the proposal is a sound architectural foundation, but not yet a complete
migration specification.** Its specialized nodes, typed fields, stable identity,
selective scopes/facts and worker-owned algorithms can accommodate the reviewed
Simple C++ declaration and statement families. Missing classes are generally
ordinary extensions. The important decisions are ownership, category boundaries,
publication, scope rules and retained processing identity.

This is an audit, not authorization to implement every feature below. The proposal
and production code were not modified. There was no native build or test-suite run.
This is not a full converter-capability certification or a layout/performance proof.

## Evidence and authority

- Proposal: [nodes](../../03_parse/proposal/structures.php.example),
  [abstractions](../../03_parse/proposal/abstractions.php.example),
  [iterators](../../03_parse/proposal/iterators.php.example).
- Current owners: [parser](../../03_parse/parser.php),
  [parse records](../../03_parse/structures/structures.php),
  [collection](../../04_analyze/collect/structures.php),
  [preparation](../../04_analyze/prepare/data/structures.php),
  [field preparation](../../04_analyze/prepare/semantics/declarations.php),
  [scope](../../03_parse/scopes/structures.php),
  [lookup](../../03_parse/scopes/lookup.php).
- [Spec authority](../../../../specs/spec_map.md),
  [strict guide](../../../../specs/simple_cpp_php_strict_quick_learn.md),
  [PHP language catalog](../../../../specs/php/catalog.md),
  [generator declarations/rules](../../../../generators/php/specs/rules.md),
  [reduced/unsupported features](../../../../generators/php/specs/unsupported.md).
- [Compact structs, unions and enums](../../../../specs/compact_layout_types.md),
  [normalized union parameters](../../../../generators/php/specs/primary_type_normalized_parameters.md),
  [closures](../../../../specs/language/closures.md),
  [async function contract](../../../../specs/async_await.md),
  [future generic surfaces](../../../../specs/metaprogramming_contract.md),
  [project composition](../../../../specs/project_build_v1.md).
- [Object storage](../../../../specs/compiler_storage.md),
  [compiler ownership](../architecture/ownership.md),
  [retained model](../architecture/MODEL.md),
  [incremental strategy](../planning/incremental_strategy.md),
  [earlier migration plan](specialized_ast_nodes.md).
- Catalog chapters [03](../catalog/03_control_flow.md),
  [04](../catalog/04_functions.md), [07](../catalog/07_references_lifetime.md),
  [08](../catalog/08_project_symbols.md), [09](../catalog/09_value_types.md),
  [10](../catalog/10_objects.md), [11](../catalog/11_inheritance.md),
  [12](../catalog/12_closures.md), [13](../catalog/13_errors.md),
  [14](../catalog/14_output_runtime.md), used as provenance, not blanket acceptance.

Normative language contracts outrank historical catalog variants and implementation.
For example, newer compact-layout enum widths override the older int-only summary;
unsupported PHP forms need not become accepted merely to complete an AST taxonomy.

## What is already solid

1. **Specialization is structural.** Functions own signatures and bodies, records
   own fields, bodies own ordered statements. New specializations need no universal
   payload, irrelevant nullable properties or shared mutable kind discriminator.
2. **Category bases can grow.** Type, statement, declaration and trivia categories
   are property-free. New grammar-specific categories can be added locally when
   they have a truthful owner. One inheritance parent is sufficient for this design.
3. **Processing does not live in data.** Typed preparation dispatch forwards to a
   worker; the node does not decide global scheduling, resolution or output layout.
4. **Identity survives edits.** Saved names, retained node objects and collected
   occurrences fit incremental reconciliation. Token position is provenance, not
   declaration identity. Qualified owner + kind + name remains necessary.
5. **Scope is encapsulated and selective.** Scope indexes remain behind methods;
   only owners of actual contexts carry scope accessors. Inspection parent links
   do not define lexical visibility.
6. **Signature and body are separate work.** One function identity can retain its
   signature while replacing its body. File executable work need not masquerade
   as a named function symbol.
7. **Order is in typed collections.** Parameter, field and statement ordering stays
   explicit, while scopes remain name indexes. Inspection iterators do not copy
   membership, and their state is independent of the node.

These choices are compatible with Simple C++ classes/interfaces, shared record
identity and typed object storage. They do not imply that the exact current PHP
sketch already converts or that virtual dispatch is necessarily optimized away.

## Important decisions before production migration

### A. Production records must migrate with the AST — high priority

The proposal imports the existing collected_name, preparation_owner and prepared
fact classes. However, collected_name.node and parsed_file.root are typed as the
production ast_node; that is a different class from proposal ast_node. Backend
records and cleanup/relocation consumers also retain those old identities.

The migration must retarget those links as one coherent change, preserve existing
collected/work identities, and remove the old outer-node/payload arrangement.
Do not introduce parallel collected-name registries or fact-transfer wrappers just
to keep both ASTs alive. The isolated proposal checks used mock worker dispatch;
they did not prove real collection/preparation/backend integration.

This is an expected integration seam, not a reason to abandon specialized nodes.
It needs an explicit migration step and tests for retained declaration identity.

### B. Scope ownership needs a complete lifecycle contract — high priority

The current proposal distinguishes:

| Context | Intended owner | Node access |
| --- | --- | --- |
| File declaration scope | parsed_file.scopes, or an external owner for a supplied scope | file_node.file_scope(): required weak observer |
| Executable-body scope | parsed_file.scopes | function_body_node.local_scope(): required weak observer |
| Function signature scope | function_node | signature_scope(): owning field accessor |
| Struct member scope | struct_node | member_scope(): owning field accessor |
| Global and language/runtime scopes | Model | through explicit context and scope parent links |

This can work, but the body node alone does not keep its weak scope alive. Retaining
an inspection node/iterator is not permission to run semantic processing after its
parsed-file owner has been retired. Workers must retain the owning file/context
while using required scope observers. Document scope retirement with body replacement
and deleted declaration cleanup; ensure node-owned member/signature scopes outlive
all allowed observers until dependency invalidation/cleanup completes.

Existing ownership documentation broadly says local scopes all live in the parsed
file store, while current function/struct specializations also own their introduced
scopes. Reconcile that documentation during migration instead of silently choosing
a second owning registry. The proposal's distinction is explicit and viable.

### C. One scope class does not mean one lookup operation — high priority

A struct's lexical parent supplies the context for resolving field types. Looking
up a member must not walk out into unrelated enclosing variables/functions.
Current field preparation uses the prepared record's field inventory; retain that
bounded behavior. Future class lookup may follow declared base types/interfaces,
which is different from lexical scope traversal.

Likewise signature lookup, body-local visibility and global publication differ.
Keep these operations in their appropriate lookup/worker owners; do not force all
of them through a universal parent-to-parent search. The existing scope storage can
remain shared. Ordinary nodes need no duplicate enclosing-scope property.

Nested blocks currently have no own scope in the proposal. That is insufficient
as a universal rule for the documented block-local visibility semantics. Decide on
retained block scopes or an explicit worker-owned lexical-context model when adding
branches/loops. Do not substitute the inspection-parent tree for this relationship.

### D. File grouping must not erase execution/namespace order — high priority before namespaces

Separating declarations from the executable file body is appropriate for the current
small file grammar. It is not yet a complete namespace/file-execution model.
The catalog documents repeated namespace blocks and executable segments around
namespace declarations. Grouping all declarations by name and all statements into
one unqualified body could lose namespace context and execution segment order.

Add namespace/segment owners when that feature arrives, preserving ordered
executable segments and their lexical contexts. No duplicate universal child list
is required. A namespace is a source grouping, not a reason to make each source
file a separately visible language namespace. Global publication stays explicit.

### E. Work state needs a single owner per semantic unit — high priority

function_body_node.changed is a parser/body comparison flag; preparation_owner has
its own pending/ready, change_status, failed and dependency state. They answer
different questions. An unchanged body can still need preparation because a
signature or lookup changed. A successful preparation can still leave C++ generation
pending. Do not use the body boolean as the sole scheduler predicate or duplicate
preparation_owner state into every AST node.

Current collected_file also has a body_preparation slot. When moving ownership onto
the explicit file body, choose one canonical body work record and update references;
do not retain two independent schedules. Declaration revision/change_status should
continue to use the collected identity rather than be copied onto every syntax node.

Retain error-retry, deleted-owner notification and reverse cleanup behavior already
implemented in the active pipeline. Physical AST replacement must not silently
abandon dependency links or retained C++ records.

### F. Initialization and maintenance operations must be specified — migration prerequisite

Constructors currently initialize collections/scopes, while required syntax fields
remain uninitialized until parsing publishes them. That is a reasonable parser
construction protocol, provided failed/incomplete parses cannot reach preparation,
inspection of required fields, or code generation as completed trees. This is not
permission to add fake defaults or nullable fields for genuinely required children.

Relocation, cleanup, reparenting and deletion still need agreed typed dispatch.
Preparation dispatch alone does not cover those processes. The older migration
plan explicitly leaves their routing open. Each owned syntax edge must be visited
once, excluding scope parents, resolved-symbol links and dependency graphs.
Old/new token snapshot ownership must remain valid during retained-node reuse.

## Extension assessment by non-expression family

| Family | Assessment | Local extension boundary |
| --- | --- | --- |
| Functions and signatures | Solid | Add defaults, variadic metadata, return-reference and async intent where required; preserve ordered parameters and separate body work. |
| Methods, constructors and destructors | Solid with a callable/category decision | Share signature/body concepts, not an obligatory free-function name or return-type field on every special member. Retain explicit instance/static/visibility/override/final intent. |
| Abstract/interface methods | Required body cannot be universal | Add a signature-only declaration form or explicitly optional body in the appropriate callable category. Do not fabricate empty bodies: absence differs from an empty implementation. |
| Classes and interfaces | Ordinary specialized additions | Separate class/reference semantics from struct/value semantics. Explicit base/interface references and member declarations; do not broaden struct_node to hold arbitrary class members. |
| Struct fields | Good existing owner | Add permitted initializers and richer field types locally. Scope name lookup is separate from field storage/order. Existing field_node is not automatically a universal static property/constant node. |
| Restricted unions and exact-backed enums | Ordinary specialized additions | Distinct declaration kinds with their actual member/backing constraints; no untyped union payload on ast_node. Enum cases need stable name/identity and ordered membership. |
| Constants and static properties | Ordinary specialized additions | Dedicated member/file declaration ownership, initializers and explicit modifiers. Do not encode them as local variable statements or ordinary struct fields. |
| Namespaces/imports/exports | Local source-unit model extension | Saved qualified names, import/alias categories, publication context and execution segments. Module/project configuration remains outside AST node ownership. |
| Rich type syntax | Hierarchy can grow | Nullable, constructed, callable and normalized-union type nodes can derive from type_node. Preserve ordered union members because the first is semantically primary. Avoid flattening type structure into a name string. |
| Generic declarations and constraints | Current map is a limited placeholder | function_node.template_parameters is hash<int> provenance, insufficient for ordered parameter kinds/defaults/constraints and stable generic surfaces. Introduce typed parameter records when the generic slice is agreed. |
| Conditional/loop/switch statements | Ordinary specialized additions | Typed clauses, bodies, loop clauses and jump targets. Lexical scopes and break/continue control context belong to workers/appropriate retained owners. Expression contents are covered separately. |
| Try/catch/finally and throw statements | Ordinary specialized additions | Ordered handlers, exception declaration scopes, optional finally body and transfer context; preserve the documented restricted finally transfers. No need for a universal executable-block payload. |
| Closure bodies | Shared executable-body concept fits | A closure needs its own signature/capture context and body work identity; it is not a global function declaration. Capture expression details remain outside this audit. |
| Async function bodies | Shared body concept fits with metadata | Explicit async context and suspension work; do not use the ordinary body shape as proof that current preparation/lowering already implements async. |
| Declaration placement | Good current restriction, naming should be revisited when extended | declaration_node currently means file declarations, while local variable declarations are statement_node and members/parameters have separate bases. This is intentional grammar membership, not a universal taxonomy of everything declaring a name. Add truthful category interfaces/bases only when shared processing needs them. |

A missing statement/declaration class is not an architectural defect. The cases that
would force awkward workarounds are pretending every callable has a body, every
member is a field, every scope search is lexical, or every generic parameter is a
name-to-token map. None is necessary with the current specialization approach.

## PHP++/converter suitability and ownership cautions

- Use ordinary shared object identity for polymorphic AST nodes. Source-language
  value structs/unions are language constructs the compiler models; they do not
  imply that its own AST implementation should use compact value storage.
- Storage<T> retains shared handles and supports holes. It does not imply covariance
  between Storage<Derived> and Storage<Base>; retain concrete typed collections and
  adapt inspection iteration explicitly.
- Traits containing properties, interface inheritance/dispatch, typed iterator
  adapters and passing the existing owning `$this` handle require focused native
  proof. Ordinary PHP execution does not establish those converter/native paths.
- Preparation_Facts uses the existing `@field-type` convention for converter
  specialization; its executable PHP signatures are still `object` boundaries.
  Concrete require_preparation() supplies the precise return type. This is code
  reuse with a portability contract, not native template support inferred from PHP.
- Actual weak-field lowering needs the adjacent weak<T> annotation; documentary
  `@reference.weak` alone does not make a PHP/native edge weak. Keep downward syntax
  ownership, upward inspection observers and collected/dependency links distinct.
- Named child fields and mutable collections allow cycles or multiple inspection
  parents if misused. Parser discipline plus focused ownership tests is appropriate;
  no universal runtime guard or readonly collection layer is recommended.
- Scope/preparation references can outlive syntax through retained records. Explicit
  retirement cleanup remains necessary; shared pointers do not collect cycles.
- Derived kind tags provide a useful inspection/compatibility contract but require
  updating enum/dispatch/iterators together. They do not eliminate normal maintenance.

## Recommended next decisions

1. Confirm the canonical scope/work-record ownership and the lifetime of retained
   nodes relative to parsed files. Specify the real collection/preparation link migration.
2. Settle typed relocation/cleanup/publication handling before replacing production
   nodes. Preserve stable collected identities and independent body/signature work.
3. When extending callable declarations, distinguish body-bearing implementations
   from signature-only declarations and special members.
4. Add namespace execution segments and nested lexical scopes with those features.
5. Grow type/generic declaration records with their agreed semantics, keeping the
   future metaprogramming surface/version identities separate from ordinary body state.

Suggested later proofs: retained declaration across a body-only edit; body scope
retirement after dependency notification; missing member never resolving to an
unrelated enclosing name; independent signature/body invalidation; signature-only
method versus empty method; namespace executable segment order; node release with
weak parent/occurrence links; genuine PHP++ conversion of the specialized hierarchy.

No fundamental redesign is recommended. The next work is to close lifecycle and
category seams, not add missing node names or force all nodes to share more fields.
