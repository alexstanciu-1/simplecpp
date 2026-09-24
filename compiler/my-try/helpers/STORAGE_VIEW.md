# Storage views
Doc Status: historical

Superseded by the [current model](../MODEL.md) and [collection API](STORAGE.md).
Views, parallel AST registries and inline-row lifetime machinery are not current
requirements. The text below records earlier exploration only; references to removed
helpers/tests and “current” behavior describe that historical checkpoint.

## Shape and scope

- Storage owns records, allocation intent and optional primary keys.
- Storage_View_Iface defines collection reads and membership operations.
- Storage_View_Abstract implements the default numeric membership list, policy,
  guards and optional backing reference. Specialized views extend it.
- Storage_View is final and has an empty body.

The helper split and file-owned AST node/payload stores are implemented in PHP.
AST child lists now use views of parsed_file.nodes; other compiler collections
still use owning Storage. Linked traversal remains deferred. See ../docs/native_layout_debt.md.

## Usage

```php
$nodes /** Storage<ast_node, false> */ = new Storage(false, 100);
$children = new Storage_View($nodes); // Read-only membership by default.
$node_position = $children->storage_append($node);
$view_position = $children->internal_append($node_position); // Duplicate allowed.
$children[$view_position]->kind = node_kind::identifier;
```

Constructor: Storage_View(?Storage $storage = null, bool $read_only = true).
The default view starts empty; it does not automatically include owner rows.
Null backing is allowed at construction, but default operations reject access,
including count/is_empty and iteration. No implicit owner is created. Backing is
fixed for the view lifetime. Specializations may implement access without backing;
they must override the appropriate access/representation methods consistently.

Native intent is Storage<T, UseStringKey=false> and a separate
Storage_View<T, ReadOnly=true>; capacity is a constructor argument, never a template
argument. A view's numeric membership is independent of its owner's key mode.
The abstract optional backing accepts either mode in PHP. Generic backing types,
virtual dispatch and custom template conversion remain unproved converter work;
no fake concrete element annotation is attached to the generic helper field.

## Position and mutation contract

| Operation | Effect / result |
| --- | --- |
| view[i] | Resolve view position i to an owned record. |
| storage_position(i) | Return the referenced owner position. |
| internal_append(owner_position) | Include an existing live record; return a new view position. |
| internal_replace(i, owner_position) | Redirect existing membership; do not replace the owned record. |
| internal_remove(i) | Remove membership only. |
| storage_append(record, optional_owner_key) | Append to owner and include in view; return the owner position. |
| append/replace/remove | Same membership operations, subject to read-only policy. |
| [] writes | Accept owner positions, not objects; subject to read-only policy. |
| foreach | Yield view positions and resolved record objects, in membership order. |
| count/is_empty | Count memberships, including duplicates. |

View positions are monotonic, may have holes and are never reused. They are not
owner positions. Only integer view offsets are accepted; string primary keys stay
on Storage. Duplicate owner positions are allowed. Append/replace validate the
owner position before modifying membership. Missing view reads/replacements/removal
throw OutOfBoundsException. Writable unset of absent membership is a no-op.

Read-only rejects ordinary membership mutations before modifying anything, even
absent-row unset. Internal methods and storage_append are explicit maintenance
operations and bypass only that policy. Record fields remain editable, subject to
the owner's indexed-field rules. A writable view uses read_only=false.

## Owner changes and lifetime

Views resolve current owner positions: replacing an owned row changes what all
its memberships return. An already retrieved PHP object still refers to the old
object. Removing an owner row leaves dangling memberships; dereference/iteration
throws rather than silently filtering them. count/isset describe membership, not
liveness of targets. storage_position remains available for a dangling membership,
and it can be removed or redirected. No reverse-view registry or cascade deletion.

Assignment aliases a view. Distinct views have independent membership lists and
may share an owner. PHP retains the owner reference; native reference/inline-record
semantics must be reconciled during layout work. Iterators have independent cursor
state. Mutation during iteration is unsupported; no view-global traversal cursor.

## Failure and extension boundaries

All default mutations reject reentry on the same view. Owner mutations retain
Storage's independent guard and index hooks. Validation failure before membership
writes leaves the view usable and consumes no view position. Failure inside a
membership write marks that view failed, rethrows and rejects subsequent access.
Owner failure propagates even through count/is_empty/isset of a backed view.

storage_append preflights view capacity, guards the view, appends to Storage, then
adds membership. If owner validation rejects, the view remains usable. If owner
write fails, Storage fails closed and view access also fails. If membership fails
after owner append succeeds, the view fails closed; the valid owned row remains.
There is no implicit removal/rollback of an already published owner row. Other
views of a healthy owner stay usable. The caller must rebuild the affected logical
segment rather than treating the combined operation as successful or atomic.

Default membership primitives append_member/replace_member/remove_member run
inside the guard. Their overrides must maintain their representation/indexes or
throw; they must not recursively mutate this view. check_append and read/access
methods are non-mutating. A specialization replacing the membership representation
must also supply matching reads, count, iteration and validation. Overriding public
operations assumes responsibility for the interface's policy/failure contract.
No production virtual view is needed yet; a test proves an unbacked computed read
implementation can override the default access behavior.

## Proof

Run tests/storage_view.php for owner versus view positions, duplicates, independent
views/iterators, read-only and writable behavior, both owner key modes, dangling
memberships, owner validation failure, partial membership failure, reentrancy,
failed-owner propagation and an unbacked virtual implementation. tests/run.py
includes that suite plus the existing Storage/model/compiler regressions.
