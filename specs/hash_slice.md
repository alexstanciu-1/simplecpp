# Concrete typed hashes

Doc Status: normative
Scope: bounded v0.2 native runtime/API generation. Compiler type resolution is now
connected through [published hash identities](../compiler/docs/hash_types.md); source
[empty construction and keyed operations](../compiler/docs/hash_operations.md) are supported; native traversal uses the [common protocol](collection_traversal.md); the [compiler foreach slice](../compiler/docs/foreach.md) consumes it.

## Owners and publication

`runtime/metadata/runtime.json` owns the optional hash binding/policy record.
`tools/api_generator/php/families.php` prepares concrete requested identities and methods;
`tools/api_generator/php/render_modules.php` emits declarations and wiring from that plan.
`tools/api_generator/templates/collections/hash.concrete.json` selects the concrete
storage recipe. Its key/value providers implement the common `ConcreteType` interface.
`runtime/src/hash/concrete_index.hpp` declares the type-independent index boundary;
`concrete_index_impl.hpp` defines it once in the generated runtime hash translation unit.
`hash_index.hpp` retains shared index primitives used by concrete and packed storage.

A publication requests `hashes: {specializations: [{value: "int64", key: "string"}]}`.
The metadata provider owns the header; an optional legacy header must agree with it. The name is `hash_VALUE_KEY`, matching source
`hash<V,K>`. This slice admits int64/string keys and values, all four combinations.
The default publication and tests/hash/publication.json request all four. The regex-enabled profile additionally requests vector_string values with string keys for named capture columns. Mixed, dynamic, object keys, other nested containers, native conveniences, extra
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
- Generated code uses `checked_slot(key)` on an lvalue hash to follow an existing
  intermediate key during a [nested write](vector_slice.md#nested-local-collection-writes).
  It checks for absence with the same error as `at`. The compiler uses the slot
  during the access and saves keys across user code. Source indexed reads return values.
- Native iterators, operator[], implicit conditions, std-container accessors and
  blanket native-argument overloads remain outside the API.

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
positions. Reinserted keys occupy new physical positions at the end. Native traversal visits live physical slots, skipping holes, without promising
stable visitation under mutation. No compaction or traversal subscriptions are added.

## Reuse and failure boundary

Donor: runtime/include/scpp/support/hash_t.hpp at 0c230e0bc. Reuse includes control
bytes/fingerprints, linear probing, deleted buckets, live-slot tracking,
load-based rehash/growth and uint8/uint16/uint32 physical entry indexes. The packed
heterogeneous algorithm, key pool, boxed wrappers and traversal subscriptions are
not imported. No replacement standard hash table is used.

Index width depends on physical entries including erased positions: up to 256
positions use uint8, up to 65,536 use uint16, then uint32. Growth/copy capacity and
physical-entry limits reject with std::length_error before integer truncation or
capacity wrap. These guards retain the donor's choices within its representable
storage domain; this is not unlimited-capacity storage.

The concrete recipe owns raw, aligned entry storage and a live-byte array. Each live
entry contains its key and value; erased positions contain no constructed objects.
Removal destroys the entry without invoking either type's default constructor. Growth
moves only live entries and preserves holes; copying constructs only live entries.

Allocation and replacement-index construction finish before a new entry is committed.
A replacement index is published only after successful preparation. set/append allocation or argument-copy failure
leaves logical membership, values and order unchanged, though private capacities or
an empty storage owner may remain allocated. Admitted key/value move construction,
move assignment and destruction are statically required to be nonthrowing; copying may
throw. Default construction is not required. Copy failures do not change the original or assignment destination.
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
