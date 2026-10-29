# Concrete typed hashes

Doc Status: normative
Scope: bounded v0.2 native runtime/API generation; source compiler integration and
public traversal are not implemented by this slice.

## Owners and publication

`runtime/metadata/runtime.json` owns the optional hash binding/policy record.
`tools/api_generator/src/hash.rs` prepares concrete requested identities and methods;
`src/emission/hash.rs` emits declarations and wiring from that plan.
`runtime/src/hash/typed_hash.hpp` owns the reviewed private table algorithms.
`hash_index.hpp` owns shared index primitives used by typed and packed storage.

A publication requests `hashes: {header: "scpp/hashes.hpp", specializations:
[{value: "int64", key: "string"}]}`. The name is `hash_VALUE_KEY`, matching source
`hash<V,K>`. This slice admits int64/string keys and values, all four combinations.
The default publication remains unchanged; tests/hash/publication.json requests all
four. Mixed, dynamic, object keys, nested containers, native conveniences, extra
operators and hash payloads in carriers are not admitted.

Publication requires int64 counts, bool_t, the requested key/value types and an
existing nullable specialization for each value type. No hidden dependency or
alternate lookup carrier is synthesized. String keys use complete bytes including
embedded NUL. Numeric strings remain strings; no normalization/coercion is performed.
Hashing borrows key bytes rather than creating temporary strings. Public headers
remain template-free. Generated src/hash.cpp is linked alongside src/runtime.cpp;
private implementation templates never enter the public include closure.

## API and states

- Default construction is empty and allocation-free.
- size() returns int64; empty()/has(key) return bool_t.
- at(const Key&) returns a value copy, throwing std::out_of_range for absence.
- find(const Key&) returns nullable<Value>, empty for absence and an owned copied
  value for presence. Allocation/copy failure still throws; absence itself does not.
- set(Key, Value) inserts/replaces and returns void. remove(const Key&) returns
  bool_t indicating whether removal occurred. clear() releases storage and is noexcept.
- append(Value) exists only for int64 keys and returns the assigned int64 key.
- Reads/probes and unsuccessful removal never materialize empty storage.
- No public element reference, native iterator, operator[], implicit condition,
  std-container accessor or blanket native-argument overload is provided.

Copies own independent keys/values; copy assignment prepares a replacement first.
Moves transfer storage and leave empty reusable sources; self-copy/self-move preserve
the value. Strings copied out cannot mutate stored values. Materializing a hash adds
one private storage-owner allocation beyond the table's arrays/index allocations.

## Append and ordering

Top-level array semantics and runtime/specs/hash_t.md require the next existing
numeric key, not entry count or a historical insertion counter. The reviewed typed
hash donor makes the rule precise: use one greater than the largest live nonnegative
integer key; use zero if none exists. Negative keys do not affect append.

Deleting the largest key can therefore make that key available to the next append.
Deleting a lower key does not fill that hole while a higher key remains. A present
INT64_MAX rejects append with std::overflow_error before changing membership.
Removing it permits append based on the new maximum. clear() restarts at zero.

Replacement retains entry position. Removal preserves other key identities and
positions. Reinserted keys occupy new physical positions at the end. Order remains
an internal invariant, proved with private fixtures; no public traversal contract
is claimed. Source-visible mutation-preserving traversal stays RT-DEBT-01.

## Reuse and failure boundary

Donor: runtime/include/scpp/support/hash_t.hpp at 0c230e0bc. Reuse includes control
bytes/fingerprints, linear probing, deleted buckets, separate key/value/live arrays,
load-based rehash/growth and uint8/uint16/uint32 physical entry indexes. The packed
heterogeneous algorithm, key pool, boxed wrappers and traversal subscriptions are
not imported. No replacement standard hash table is used.

Index width depends on physical entries including erased positions: up to 256
positions use uint8, up to 65,536 use uint16, then uint32. Growth/copy capacity and
physical-entry limits reject with std::length_error before integer truncation or
capacity wrap. These guards retain the donor's choices within its representable
storage domain; this is not unlimited-capacity storage.

The donor's sequential multi-array append lacked allocation rollback. The local
adaptation rolls back uncommitted appended slots; a replacement index is published
only after successful preparation. set/append allocation or argument-copy failure
leaves logical membership, values and order unchanged, though private capacities or
an empty storage owner may remain allocated. Admitted key/value default and move
operations are statically required to be nonthrowing. Replacement and removal use
those operations. Copy failures do not change the original or assignment destination.
No collection-wide transaction copy is performed on each insertion.

## Proof obligations

Prove collision/update/delete behavior, insertion order, empty and moved-from reuse,
append after deletion/negative/high/maximum keys, strict byte keys, no implicit native
adapters, nullable absence/presence, and failure recovery. Exercise both physical
index-width transitions under churn with small live membership. Preserve independent
contract traces and reviewed donor algorithms rather than using new implementation
output as its own expected result. Check generated dependency rejection, deterministic
publication, public include boundaries, Release/UBSan, focused lifetime sanitizers,
standalone export and allocation/lookup/churn costs.
