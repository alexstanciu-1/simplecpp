# Source snapshot and syntax-tree storage slice
Doc Status: supporting

Two existing storage types now have their own files beside their indexed-set
owners: `read_sources/data/buffer.php` contains `Source_Buffer`, and
`parse/data/tree.php` contains `Syntax_Tree`. Each former `store.php` retains its
lookup set. Bootstrap loads the extracted declarations, and all public names and
caller APIs are unchanged. This separates already distinct storage owners without
introducing a new compiler process, representation or parallel implementation.

The source buffer's original declarations are unchanged. The tree adds only the
adjacent `vector<syntax_node>` annotation to its existing list property. Both use
the previously implemented conversion paths; no converter or runtime extension
was necessary. Nine production files are now in the ready source set.

## Source identity and bytes

`Source_Buffer` retains source ID, path, modification time and content. Ordinary
class references identify the exact snapshot; value-equal separately constructed
objects are still distinct. Tests cover shared aliases, changed content under the
same ID/time, and separate snapshots whose entire field values match. UTF-8 byte
slices and runtime-produced invalid-UTF-8 content remain byte-preserving strings.
Unsafe binary source literals remain subject to the existing converter diagnostic.
PHP readonly enforcement and native initialization-only usage are unchanged from
the role-view contract; this slice does not add native readonly checks.

## Lists of shared nodes

`Syntax_Tree` keeps scalar source/root IDs and a typed vector of ordinary class
node handles. Copying the list copies its membership while sharing existing node
objects. Mutating an existing node through the copied list is visible in the tree;
replacing the copy's element or appending to the copy does not replace/extend the
tree's list. This is the adopted PHP behavior and is explicitly proved natively.
No struct/value-node transformation or implicit deep clone is introduced.

## Evidence

[storage-02](../planning/compiler_migration/results/storage-02/summary.json)
records the cumulative PHP/native strict v0.1.76 proof and fifteen existing
compiler fixtures. It includes source identity, bytes, tree defaults, object-list
copy/mutation/replacement and all previous component witnesses. Provenance checks
recover the original extracted declarations after removing the vector annotation.
`storage-01` is the earlier passing run before the identical-field identity case.

Token buffers and their diagnostic serialization, indexed lookup sets, parser
execution and native source IO remain unported. Completing these storage records
is not a claim that the whole storage subsystem or frontend is ready.
