# Packed storage foundation for mixed/dynamic

Doc Status: planning
Date: 2026-10-29
Status: donor audit complete; proposed key representation requires agreement.
Reviewed repository revision: 84e90960d.

## Proposed scope

Adapt the private packed_table<Value> owner before publishing either boxed family.
Prove reuse with independent payload types that cannot convert to mixed or dynamic.
Preserve packed-to-associative transitions, custom control bytes/fingerprints,
linear probing, deleted buckets, insertion order and physical-index width selection.
Do not replace the custom table with std::unordered_map or merge its algorithm with
the typed table. Only genuinely shared index primitives should have one private owner.

No public tagged values, casts/operators, compiler integration or public traversal
are included. Existing typed-hash exports and artifact parity must remain unchanged.
RT-DEBT-01 remains deferred. This is a private storage deliverable, not a claim that
mixed/dynamic are implemented or that all their source contracts are now satisfied.

## Authority and evidence

Repository-root references:

- specs/dynamic_types.md, separated-family amendment: mixed containers contain mixed
  values with value/COW behavior; dynamic containers contain dynamic values with
  shared identity. Storage must not force either payload family into the other.
- specs/array_semantics.md, v0.2 indexed operations: ordinary integer/string key
  domain, checked unknown key kinds and existing mixed/dynamic numeric-string policy.
- runtime/specs/hash_t.md: ordered table and packed 0..n-1 storage.
- runtime/include/scpp/support/hash_t.hpp: dyn_keys and packed_table<Value>.
- runtime/include/scpp/util/global_string_pool.hpp/.cpp: compiled singleton interner.
- tests/runtime/native/test_packed_table_storage.cpp and test_hash_churn.cpp:
  independent payload, shape transitions, ordering, lifecycle and width-transition ideas.
- v0.2/runtime/src/hash/typed_hash.hpp: already adapted index machinery and failure
  guarantees; use a local hash-owned extraction to share primitives without changing
  typed-hash behavior.

## Findings that prevent a blind copy

1. Integer packing casts int64 to uint32. For example, keys 1 and 4294967297 receive
   the same code. The high bit is also the string flag: integer 2147483648 is
   interpreted as string ID zero. Integer -1 becomes 0xffffffff, the tombstone marker.
   These are direct consequences of the inspected encoding, not valid int64 semantics.
2. Append has no guard against entering the string-tag domain. Pool ID 0x7fffffff
   produces the same tombstone code. A 32-bit storage code is not a language key limit.
3. Packed-to-associative conversion installs the new key vector before allocation
   succeeds. Multi-array insertion and interner map/vector insertion also lack complete
   rollback. Allocation failure can leave inconsistent state.
4. String lookup calls intern, including on a miss. A pure lookup can permanently grow
   the process-wide pool. Separate existing-ID lookup from insertion.
5. The donor combines storage with mixed key carriers, PHP-target normalization and
   mutation-preserving iteration. These adapters do not belong in a reusable raw key
   engine. Removal may allocate because it changes packed storage to associative form.

## Recommended key boundary

Use a private explicit key tag with a full-width payload: integer int64, interned string
ID, or deleted slot. Start with a straightforward representation (likely 16 bytes on
this target), measure it, and defer compression. Packed mode retains implicit keys
and therefore pays no per-entry key-vector cost until transition.

This changes key representation and fixes collisions; it does not authorize changing
probing/growth/order algorithms. Keep bucket indices uint8/uint16/uint32 based on
physical positions. Hash and equality dispatch by key tag, never reinterpret signed
integer bits as a string ID. Append scans live nonnegative integer keys, starts at zero
when none exist, and rejects a live INT64_MAX before mutation. Deleting the maximum
permits its reuse. Capacity and ID limits must reject before truncation/wrap.

The raw storage engine treats integer and byte-string identities distinctly. Later
mixed/dynamic adapters own the required numeric-string policy; typed hashes retain
their existing strict policy. Do not silently choose a new source normalization rule
or advertise the raw storage boundary as the completed boxed API. Unknown runtime
string values may still require runtime normalization; compiler selection alone cannot
resolve value-dependent text.

## String-key ownership

Reuse a single compiled process-lifetime interner, shared by the future boxed families;
no per-translation-unit singleton or pool per table. Adapt it to borrowed bytes and
owned resolution, with non-interning lookup. Preserve its stable-ID purpose and the
existing map-based interner approach; that map is not a replacement for the table.

Make map/vector insertion transactional and guard the ID domain. A successfully
interned key may remain retained if a subsequent table allocation fails; logical
membership/order/value guarantees do not promise rollback of global retained capacity.
Record process-lifetime retention explicitly. Do not add concurrent mutation support,
reset APIs or reference-counted reclamation speculatively; a future concurrency slice
must settle synchronization before concurrent use. Empty/missing reads must not intern.

## Failure and lifecycle contract for this slice

Require nonthrowing payload default/move/destruction, while copies may throw. Prepare
copies before replacement; use staged key/index transitions and insertion rollback.
On failure, preserve logical contents and order. Private capacity may change; shape
should remain coherent and any permitted shape change must be documented/tested.
Copies produce independent tables; moves leave reusable empty sources; clear restores
packed shape. No full-table copy on each insertion and no imported traversal machinery.

## Proposed backlog

| ID | Work | Acceptance |
| --- | --- | --- |
| P0 | Agree key representation and private-only deliverable | Full int64 identities, explicit tags, exclusions and provenance |
| P1 | Extract shared private index primitives | Existing typed-hash suite and generated artifacts unchanged |
| P2 | Adapt interner and key adapters | Stable byte/NUL identity, non-interning misses, insertion rollback and limits |
| P3 | Adapt packed engine and transactional transitions | Dense appends/replacements, sparse/string/negative transitions, deletion/reinsert/order |
| P4 | Prove lifecycle, failures and boundaries | Independent payloads, injected failures, int64 extremes, copy/move/clear, width transitions with small live sets |
| P5 | Measure and consolidate | Packed/associative memory and lookup/churn costs; Release/UBSan/focused sanitizer and standalone export proofs; commit |

Use an independent map/order oracle and reviewed donor traces only in its noncolliding
key domain. Test corrected cases against the language key contract rather than donor
bugs. Synthetic limits avoid allocating billions of entries. No Rust public API family
is added merely to expose a private test fixture.

## Agreement requested

Proceed with the simple explicit-tag/full-int64 key representation while retaining
the custom table algorithms, then build mixed and dynamic as later separate batches.
The additional associative key bytes are an explicit tradeoff for correct identities;
packing them further is a measured future optimization. No implementation goal has
been created by this audit.
