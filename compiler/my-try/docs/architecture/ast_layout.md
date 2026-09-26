# AST node layout and traversal
Doc Status: supporting

`ast_node` is the single final syntax-node class. Its private kind tag selects
its specialization; there are no AST node subclasses. `kind()` and `payload()`
expose the tag and owned record without allowing their replacement.

`Syntax_Nodes::make` is the construction boundary: it supplies leaf-expression
records, validates the kind/payload pair and existing local invariants, constructs
the common header, and links the complete child list. The node constructor only
initializes representation and checks span bounds; parser and test callers use
this factory.

The header retains uint32 token_index and end_token_index (exclusive end), kind,
and a private required node_structure. Named *_structure records hold specialized
syntax. Identifier, punctuation and comment nodes own an `empty_node_structure`. Integer literals,
floats, booleans and variable references have small expression records so their prepared
facts have a specialized owner. Integer, boolean and reference structures own their respective typed
preparation slots; `binding_structure` owns its distinct binding-fact slot.
`prepared_expression` holds only the common type; prepared_integer_literal adds
required decimal text, prepared_boolean_literal adds a bool value, and prepared_variable_reference adds a required declaration.
Unsupported expression structures have no speculative fact slots. `node_structure` supplies
no-op local cleanup for syntax-only records. Preparation algorithms stay in their
process owner; specialization hooks route calls and attach returned local facts.

## Fixed navigation fields

All navigation state is private:

| Field | Current native representation | Role |
| --- | --- | --- |
| parent_node | weak<ast_node> | Non-owning parent |
| previous_node | weak<ast_node> | Non-owning previous sibling |
| next_node | nullable shared node | Owns the following sibling |
| first_node | nullable shared node | Owns the first child |
| position | uint32 | Zero-based ordinal within the parent |

A root's position is zero but has no sibling meaning. parent(), prev(), next() and
first_child() return nullable node handles. child_position() exposes the ordinal
as an ordinary int for current Storage/map APIs. has_children() needs no allocation.
children_snapshot() returns a fresh Storage membership snapshot in linked order; editing
that snapshot does not edit the tree. Hot walks can use first_child()/next()
without allocating a snapshot. Each native parent/prev access acquires a shared
handle and returns null if the weak target is absent/expired. PHP uses ordinary
references and therefore has different retention behavior.

The header has a fixed field set, not a promised packed ABI or measured byte size.
Specialization records and their lists are separately allocated.

## Construction and ownership

ast_node::link_children(owner, children) receives explicit shared handles; it does
not create a shared owner from native `$this`. It validates duplicates, existing
parents, cycles and position capacity before changing links. It rejects replacing
a nonempty linked list. Empty linking has no effect. The input collection is not
retained as navigation state. Linking uses one pass to validate and another to
publish. Parser children are complete before their parent is built.

This is a build-once tree. No insertion, removal, moving, relinking or automatic
synchronization after publication is supported. The kind and payload handle are fixed at construction. Public span fields and
payload contents/named child aliases must not be rewritten to contradict the published tree.

The child/sibling chain provides uniform ownership and traversal. For this first
migration, the existing named child fields and Storage lists in structures remain
**retaining aliases** for semantic consumers. They share node identity, do not copy
nodes, and must remain consistent with the chain. They are not claimed to be native
weak indexes. Scope references remain explicitly native weak fields.

## Child order

- File/block: statements in parse order.
- Function: parameters, return type, body.
- Parameter/field: type syntax.
- Call: template arguments, then value arguments.
- Binary/assignment expression: left, right.
- Expression statement/return: expression, when present.
- Binding: type syntax, explicit target, value, omitting absent fields.
- Array type: element type, extent.
- Array literal: elements.
- Index expression: base, index.
- Struct: fields.
- Field access: base.
- Leaf: none.

Order is grammatical, not necessarily increasing token position: a function's
return type follows its parameters. Each specialization owns this mapping through
`append_children()`. `Syntax_Nodes::child_nodes()` only allocates a snapshot and
delegates; normal traversal follows the links established at construction.

## Future native representation

The intended optimization is preallocated owned blocks and positional links behind
these methods. Stable positions and an explicit absent sentinel will be required;
specialization records still need an allocation strategy alongside the fixed headers. This migration does not
implement an arena, serialization, weak collections, packed layout, or iterative
release. Long shared sibling chains can still produce deep destruction chains.

The earlier linked-AST native evidence under
specs/planning/compiler_migration/results/linked-ast-native-01 predates this final
common-node representation. The final-node follow-up passed conversion, a STAN-enabled native compiler build,
142 PHP/native comparisons and execution of all 48 valid emitted programs plus
the C++ S2S proof. See [native verification](../portability/native_adaptations.md#final-common-node-verification).
Compiling the compiler itself remains opt-in for subsequent changes.

## Access and preparation

Use first_child()/next() for an allocation-free walk, and children_snapshot() when
independent membership is needed. Preparation, C++ emission and cleanup traverse
the published links. Typed `Syntax_Nodes::*_data` accessors return the node's
specialization record for all consumers, including preparation and C++ emission.
Named child fields and Storage lists retain grammar roles and shared identity;
callers must not edit them after publication or reorder source-defined children.

On a specialization, preparation() returns nullable facts, require_preparation()
requires them, and set_preparation() attaches a processor's result. The common
node's clear_preparation() delegates local cleanup to its payload;
Preparation_Cleanup owns walking the tree. Fact access does not select a backend,
infer types or start processing. Facts have no reverse link to their syntax node.

## Deferred: narrowing after a type or kind check

User decision, 2026-09-26: retain checked `object_cast` calls that narrow a
specialization record, including calls after a matching `instanceof` or kind
branch. All AST handles now have the same final type, so node downcasts are gone.
The remaining kind contract relates the tag to the payload class, established by
`Syntax_Nodes::make` and its validator.

Neither a kind nor an instanceof check currently changes the generated variable's
static type. A typed alias from a base handle to a derived payload handle emits an
unsupported implicit C++ downcast. The new S2S generator should reuse established
type facts while preserving null behavior and identity; this refactor does not
extend the current converter or runtime to implement that optimization.

Same-type nullable access after a null guard can already use explicit typed
assignments. Required-state checks and unguarded narrowing remain checked operations.

## Deferred: punctuation representation and cost

User decision, 2026-09-26: review punctuation handling before the AST grows further.
Measure token storage, any standalone punctuation nodes, empty-specialization
allocations, link storage and traversal overhead on representative source. Many
punctuation tokens are currently consumed by the parser without becoming AST nodes;
do not assume every token is a node. Decide which punctuation needs retained node
identity versus a token index/span, preserving source mapping and required syntax.
Do not remove punctuation or change storage in this pass.

Fieldless syntax now owns an empty specialization so local delegation is unconditional.
This enables unconditional operation delegation; it does not make unsupported
constructs transparent to code generation. Traversal remains behind accessors so
its storage can be optimized independently of callers.

## Specialization dispatch

`node_structure` implements `node_operations_i` once. Concrete records override
`prepare_statement`, `prepare_expression`, `generate_cpp_statement` and
`generate_cpp_expression` only for currently supported operations. Base methods
throw; they never silently recurse. Expression preparation returns prepared facts,
expression generation returns C++ text, and statement generation returns statement
text. This preserves the existing emitter contract without a generic mixed result.

File_Preparation and CPP_Generator own phase entry and root statement iteration.
Binding and return routines own evaluation of their required expression children.
There is no second generic semantic traversal. Structural child discovery, cleanup
and reporting are separate operations; reporting now uses the linked tree, including
call template arguments previously missed by its duplicate specialization chain.

Specializations route to typed processing routines in their owning folders and may
attach returned facts to their local slots. They never retain workers or invocation
contexts. `preparation_context` holds source-order locals, occurrence references and
canonical type handles. `cpp_generation_context` holds per-generation header state.
No retained AST or fact record references either context.

The explicit contexts avoid passing native raw `$this` as a shared worker handle.
Binding/return hooks acquire their existing shared payload through typed accessors;
literal generation passes typed prepared facts directly. Those narrow access casts
remain under the existing converter-debt policy. No manual AST-kind dispatch is
left in C++ generation; preparation retains its explicit supported-type-syntax guard.
Construction validation/category queries and experimental LLVM dispatch are unchanged.

Conversion and PHP behavior establish this routing, not native inlining or speed.
Native compiler verification remains opt-in. See `tests/specialization_dispatch.php`
for unsupported-parent isolation, inherited interfaces, context isolation and failure
cleanup; existing AST and S2S suites prove child order and generated behavior.

Verification (2026-09-26): `/tmp/scpp-specialization-dispatch-02/summary.json`
records 73 PHP files linted, passing style/behavior suites, 37 generated C++
executions and the existing 19 LLVM / 28 call regression executions. All 37 C++
files match `/tmp/scpp-float-s2s-02/s2s/` byte-for-byte. All 51 portable compiler
sources converted in `/tmp/scpp-specialization-dispatch-conversion/`. The native
compiler itself was not built or run for this refactor.
