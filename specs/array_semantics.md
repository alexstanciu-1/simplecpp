# Collection access and mutation

Doc Status: normative

## Value iteration

Value iteration visits the elements of the admitted receiver in encounter order. The [traversal contract](collection_traversal.md) defines ownership, mutation and lifetime for each supported receiver. Vectors have dense integer positions. Hashes retain key type, key identity and live insertion order. Removing a key and reinserting it puts the new entry at the end.

## PHP flat destructuring assignment

A standalone `[$x, $y] = expression;` reads integer keys 0 and 1. A keyed form such as
`["id" => $id, -1 => $last] = expression;` reads the literal integer or string keys in
written order under the source collection’s ordinary key contract. Use either positional
bindings or keyed bindings throughout. Each destination is a named local or parameter.
The flat form accepts a trailing comma.

Evaluate the indexed collection once. Each reached binding reads one entry, applies
ordinary assignment conversion and publishes its write before the next read. A fresh
local in the enclosing scope infers the entry type. Extra entries are ignored. A repeated
target receives each write. Missing keys, stored nulls and conversion failures follow
ordinary indexed reads and assignments. If a binding fails, earlier writes survive and
later bindings do not execute.

Replacing a destination that aliases the RHS slot still leaves the originally selected
collection available for subsequent reads. Any temporary receiver lives through the
last write or until unwinding. Bindings copy values under their usual ownership rules.
Nested patterns, skipped positions, reference bindings, expression keys and destructuring
inside a foreach header are outside this statement form.

## Indexed compound updates

A value read or presence check never creates an entry. Keyed writes evaluate the receiver once and apply the [evaluation rules](expression_evaluation.md) to the key and value. An indexed compound update snapshots the selected old value and key before evaluating its RHS. Failure before a successful write-back leaves that replacement unapplied. An append or removal requires an operation admitted by the receiver.

Fixed arrays have a declared extent. Indexing checks bounds. Passing whole collections by reference borrows eligible stable storage. An indexed value does not thereby become a native reference. See [compiler reference coverage](../compiler/docs/collection_references.md), [hash keys](../compiler/docs/hash_keys.md), and [collection presence](count_empty_isset_contract.md).

## Shared-owner hash keys

A shared key retains its allocation. Copies compare by owner identity, including across admitted base/interface views. Different allocations remain distinct even when their fields compare equal. An empty owner is a distinct admissible key. Removing an entry releases its stored key.
