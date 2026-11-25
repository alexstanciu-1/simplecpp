# Collection access and mutation

Doc Status: normative

## Value iteration

Value iteration visits the elements of the admitted receiver in encounter order. The [traversal contract](collection_traversal.md) defines ownership, mutation and lifetime for each supported receiver. Vectors have dense integer positions. Hashes retain key type, key identity and live insertion order. Removing a key and reinserting it puts the new entry at the end.

## Indexed compound updates

A value read or presence check never creates an entry. Keyed writes evaluate the receiver once and apply the [evaluation rules](expression_evaluation.md) to the key and value. An indexed compound update snapshots the selected old value and key before evaluating its RHS. Failure before a successful write-back leaves that replacement unapplied. An append or removal requires an operation admitted by the receiver.

Fixed arrays have a declared extent. Indexing checks bounds. Passing whole collections by reference borrows eligible stable storage. An indexed value does not thereby become a native reference. See [compiler reference coverage](../compiler/docs/collection_references.md), [hash keys](../compiler/docs/hash_keys.md), and [collection presence](count_empty_isset_contract.md).

## Shared-owner hash keys

A shared key retains its allocation. Copies compare by owner identity, including across admitted base/interface views. Different allocations remain distinct even when their fields compare equal. An empty owner is a distinct admissible key. Removing an entry releases its stored key.
