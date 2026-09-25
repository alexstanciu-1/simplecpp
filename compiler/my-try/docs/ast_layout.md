# AST node layout and traversal
Doc Status: supporting

`ast_node` is an abstract PHP base. Each node_kind has one concrete final
`<kind>_node` subclass. The subclass constructor sets its kind. The parser's single
publication path allocates that subclass, validates the additional structure and
local invariants, checks/sets its span, and links its complete child list.

The base retains uint32 token_index and end_token_index (exclusive end), kind and
optional node_structure. Extra syntax data uses named *_structure records. Leaf
kinds have no structure. Syntax_Nodes retains typed *_data accessors so semantic
workers do not depend on the concrete structure representation.

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
children() returns a fresh Storage membership snapshot in linked order; editing
that snapshot does not edit the tree. Hot walks can use first_child()/next()
without allocating a snapshot. Each native parent/prev access acquires a shared
handle and returns null if the weak target is absent/expired. PHP uses ordinary
references and therefore has different retention behavior.

The header has a fixed field set, not a promised packed ABI or measured byte size.
Optional structure records and their lists are separately allocated.

## Construction and ownership

ast_node::link_children(owner, children) receives explicit shared handles; it does
not create a shared owner from native `$this`. It validates duplicates, existing
parents, cycles and position capacity before changing links. It rejects replacing
a nonempty linked list. Empty linking has no effect. The input collection is not
retained as navigation state. Linking uses one pass to validate and another to
publish. Parser children are complete before their parent is built.

This is a build-once tree. No insertion, removal, moving, relinking or automatic
synchronization after publication is supported. Public kind/structure/span fields
and named child aliases must not be rewritten to contradict the published tree.
initialize() belongs only to construction, before linking/publication.

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
return type follows its parameters. Syntax_Nodes::child_nodes owns this mapping.

## Future native representation

The intended optimization is preallocated owned blocks and positional links behind
these methods. Stable positions and an explicit absent sentinel will be required;
different concrete node sizes need an allocation strategy (for example fixed
headers/separate structures or per-specialization stores). This migration does not
implement an arena, serialization, weak collections, packed layout, or iterative
release. Long shared sibling chains can still produce deep destruction chains.

The converter now preserves abstract class declarations and one literal extends
clause. It does not resolve inheritance or validate overrides. The v0.1 native
emitter currently emits this abstract base as a polymorphic C++ class without a
pure virtual member; native abstractness enforcement is not claimed. Application
construction goes exclusively through concrete subclasses.

See the linked-AST native evidence under
specs/planning/compiler_migration/results/linked-ast-native-01.
