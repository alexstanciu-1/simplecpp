# Singular relationships and erroneous duplicates

Doc Status: supporting

This pattern and review apply to active `v0.2` code.

If a property or per-key slot has zero or one element in valid code, and multiple
elements are necessarily erroneous, store one nullable reference. Preserve extra
invalid elements through a typed `duplicate_element` chain. Do not allocate a
candidate list for the normal zero/one case.

One declaring scope owns each chain. Other indexes reference the same nodes and
never maintain competing chains on that link. Resolve null as absent, one node as
unique, and a node with a successor as ambiguous. Do not silently select the head
of an ambiguous chain. Preserve every invalid declaration and its diagnostic.

This is a representation rule, not a new semantic rule. It does not turn overloads,
imports, or multiple observations into errors. Retain collections where multiple
elements are valid. Existing serialized report formats may materialize lists at
the inspection boundary; they are not the compiler's retained representation.

## Current implementation

- `symbol_entry.head` is one nullable declaration reference.
- `scope.declarations` maps each key to one head reference.
- `Scope_Collection.pending` is a flat object-identity inventory of all declarations,
  including many valid distinct names. There is no list per name. Temporary head,
  tail and comparison indexes hold one reference per key.
- Body scopes publish local-variable chains. The compilation scope publishes public
  function chains; file scopes only publish reference indexes.
- `ast_file_node.declaration_nodes` retains all syntax in order, including duplicate
  declarations. It is a valid multi-element syntax inventory, not a candidate list.
- Only successful publication rebuilds live chains, after old readers finish.
  Failure leaves old links intact. Previous sessions retain syntax, not historical
  duplicate chains. Stable entries survive removal and reactivation.
- Generic change status compares the complete old/new chain identity, including a
  changed tail with the same head. Dependency comparison captures transient scalar
  signature/ambiguity facts before links change; no declaration lists are copied.

## Similar-case review

This review covers active `v0.2` code only. The identified error-only candidate
lists have been converted; no additional clear conversion was identified among
the reviewed collections. This is a targeted review, not an exhaustive audit.

| Location in v0.2 | Assessment |
| --- | --- |
| `compiler/03_parse/collect/structures.php`: `symbol_entry`, `scope.declarations` | Converted to one nullable head per entry and one head reference per key. |
| `compiler/03_parse/collect/scope.php`: pending inventory and publication indexes | No per-name candidate lists remain. The flat inventory legitimately contains many distinct declarations. |
| `compiler/03_parse/structures/source.php`: file declaration inventory | Retain the collection: a file can validly contain many declarations. Traversal must remain independent of live duplicate chains. |
| `compiler/03_parse/structures/functions.php`: statements, locals, references | Retain collections: multiple statements, local declarations and reference occurrences are valid. |
| `compiler/compiler/dependencies.php`: observers and targets | Retain sets: a symbol can have many observers and a watcher can depend on many symbols. |
| Source/module inventories, diagnostics and backend output units | Retain collections: multiple entries are valid. |
| `tools/compiler_panel/report.php` | Traverses the live chain; lists are materialized only to preserve inspection JSON. |

Existing singular fields such as a declaration's type or a call's symbol already
use one nullable reference.

## Mined structures

Structures mined from `my-try`, `my-try-cpp` or other implementations are reference
material. Discuss their purpose, ownership and representation with the user before
adopting them into `v0.2`. Mining does not authorize copying their structures or
adding functionality. Donor subsystems are outside this review's scope.

Apply the rule in reviews and future development. Do not alter copied historical
samples/generated output to enforce it. Do not replace a general collection utility
with a duplicate chain when that utility also serves valid multi-element relations.
