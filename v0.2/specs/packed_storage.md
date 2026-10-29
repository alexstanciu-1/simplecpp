# Private compact packed storage

Doc Status: normative
Scope: v0.2 private runtime foundation; no public mixed/dynamic API or compiler wiring.

## Ownership and representation

`runtime/src/hash/packed_table.hpp` adapts the packed donor at `71925dd8b`.
`hash_index.hpp` owns the existing shared index primitives used by typed and packed
storage. Control bytes, fingerprints, linear probing, deleted buckets and narrow
physical-position indices are retained. Values are separate from keys/index.
Packed dense lists use implicit integer keys with no allocated key/index arrays.
Associative keys remain four-byte codes; no live-byte array is added.

`packed_key.hpp` owns checked physical encoding and the string pool. Integer keys
are 0…INT32_MAX, matching the approved amendment in `specs/array_semantics.md`.
The high bit identifies pooled strings; 0xffffffff is a tombstone and is never
allocated as a live key. Pool IDs range from zero through 0x7ffffffe, checked before
narrowing. String keys preserve all bytes including NUL and numeric spellings.
Source normalization belongs to a later language adapter, not this storage owner.
Typed hashes retain their existing signed-64-bit domain and strict string behavior.

The compiled `packed_key.cpp` owns one process-wide pool across payload types and
translation units. Map-owned strings and reverse pointers retain the donor's stable
identities and 0.4 map load factor. IDs are retained for pool lifetime, even after
keys are removed from tables. No concurrent mutation contract is added.

## Mutation, lookup and lifetime

Append selects max(live integer keys)+1, or zero if none exist. It throws
`std::overflow_error` beyond INT32_MAX before mutation. Explicit negative/wide
integer operations throw `std::out_of_range` before narrowing. A sparse table can
reach the append limit with a single live key. Deletion preserves remaining keys;
replacement retains position; reinsertion appends a new physical position.

String find/remove uses non-interning heterogeneous lookup, with no temporary string
allocation. A failed read never adds a shared identity. Insertion alone interns.
Pool exhaustion throws `std::overflow_error`; invalid reverse identities throw
`std::out_of_range`. Existing interned keys remain usable when the pool is full.

Transitions prepare key and index arrays before publishing. Insertion rolls back
uncommitted values/keys; rehash publishes only a complete replacement index. Pool
insertion rolls back its map node if reverse storage allocation fails. Argument-copy
or allocation failure preserves table membership, values and order. Capacity changes
or a completed packed-to-associative transition may remain after later failure.
A newly interned string may remain if subsequent table allocation fails; it is a
valid resolvable identity, never an orphan. No whole-table copy occurs per insertion.

Payload default/move construction and move assignment must be nonthrowing. Copies
copy payloads according to their own semantics; the storage imposes neither COW nor
shared identity on them. Copy assignment prepares a replacement first. Moves leave
empty reusable sources. Clear releases table arrays and restores packed mode.

Index widths are uint8 through 256 physical positions, uint16 through 65,536, then
uint32, including deleted positions. Physical-count and bucket-capacity arithmetic
checks throw `std::length_error` before truncation. Deleted slots remain until clear
or destruction; append and size retain the donor's associative scans.

## Boundaries and validation

The CMake target `scpp_packed_storage` compiles the private pool; the test target
includes private headers directly. No generated declaration, metadata registration,
public template, mixed/dynamic wrapper or compiler change is introduced. Private
visitation is for internal use with no structural mutation during a visit.
Source traversal preservation stays RT-DEBT-01.

`tests/packed` proves bounds, append/deletion, cross-translation-unit identities,
byte/NUL keys, pool exhaustion with small limits, allocation failures, ownership,
copy/move/clear, ordered reference traces and both index-width transitions under
churn. See [validation results](packed_storage_validation.md).
