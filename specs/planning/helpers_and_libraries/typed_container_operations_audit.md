# Typed container operations

Doc Status: planning

Audited the typed vector and hash source surface at `bf7e90367` against release
`d2f63e37e` and the my-try catalog at `624ab8a2d`. Fixed extents and boxed
values have separate receiver contracts and remain separate audits.

| Operation | Current v0.2 contract and Stage 1 disposition |
| --- | --- |
| Construction and population | A typed local accepts an empty or contextual populated literal. My-try also registers a zero-argument static hash factory. A typed empty literal already constructs the hash. Defer factory spelling to flavor review. |
| Indexed read, write and update | Typed reads check the index or key. Updates use the selected old value and preserve source evaluation and ownership rules. Nested writes have their own path rules. Keep the compiler-owned operations. |
| Append | `$v[] = $value` appends to a vector. Hash append requires an integer key type. My-try also registers a void vector instance method taking one value. The indexed append covers its mutation; defer the method spelling to flavor review. |
| Observation | Count, collection emptiness, keyed presence and key/value iteration already have source contracts. A vector position is dense; a hash retains key identity and live insertion order. |
| Presence and lookup | Both receivers support `has`. Hashes also support `hash_contains`. For published hash specializations, `hash_find` returns a nullable copied payload. For supported application hashes with shared class values, `hash_get_or_null` returns a handle and may require a separate presence check. Preserve these distinct result contracts. |
| Removal and clear | Both receivers implement `remove` and `clear`. Vector removal shifts later elements. Hash removal preserves the remaining keys. A removal miss returns false. PHP-flavor indexed `unset` is a separate source-language question below. |
| Capacity and transforms | The seven general vector capacity/construction helpers and six general typed map/filter names were integrated in earlier checkpoints. Their existing receiver and callback restrictions still apply. |

The donor native index also lists cursor operations, hash packing and storage
estimates. Those rows describe C++ implementations. The mined application
catalog supplies no corresponding general helper contract. The release shallow
stub for `to_hash(mixed)` takes boxed input and belongs with the boxed conversion
review.

## Source evidence

Ran small PHP-flavor projects using an empty module manifest and the current
compiler. Clang linked the accepted program against the built v0.2 runtime.
Appending 7 to [3,5] and removing position zero left [5,7]. A string-keyed hash
held both "05" and "5", then removed only "5". The executable returned zero.
The compiler independently rejected these other forms:

| Source | Observed boundary |
| --- | --- |
| `$v->append(9)` | Unknown vector method. |
| `hash::make()`, `hash<int,string>::make()` | Unknown static method and parse failure, respectively. |
| `unset($v[1])`, `unset($h["a"])` | Both rejected at parsing. The PHP-flavor unset operand currently must be a whole variable. |
| `$h[] = 5` on `hash<int,string>` | Rejected because a string-keyed hash does not admit append. |

Six focused compiler checks also passed locally: vector operations, hash
operations, hash contains, collection has, remove and clear.

## Proposed disposition

Mark the typed operations as covered at the capability level with the spelling
differences above. The release guide demonstrates indexed vector unset, while
the my-try indexing descriptor marks removal on hash only. For flat typed
containers, the current remove method already expresses the mutation. PHP-style
nested unset also needs a decision on missing intermediate keys and evaluation
of subsequent key expressions. Keep that decision for the PHP-flavor review.
Fixed arrays are deferred implementation debt. Locals lower directly to native C++ arrays with byte leaves and stack storage.
The current type also admits inline instance fields. Revisit element and extent admission, traversal, and
the release's sequence transform overloads together when this family resumes.

[Donor type protocols and catalog methods](donor_surfaces.tsv) ·
[Active indexing contract](../../array_semantics.md) ·
[Current method and lookup coverage](../../../compiler/docs/collection_methods.md) ·
[Capacity checkpoint](vector_capacity_audit.md) ·
[Transform checkpoint](collection_map_filter_audit.md)
