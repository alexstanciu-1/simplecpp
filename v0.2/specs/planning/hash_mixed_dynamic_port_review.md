# Hash, mixed and dynamic port review

Doc Status: planning
Date: 2026-10-28
Status: historical initial review; typed hashes are now delivered. The
[packed-storage audit](runtime_packed_storage_batch.md) records current findings and scope.

## User constraints and review result

Review follows the completed generated-vector slice. Preserve hash_t's custom
Swiss-style table core; adapt its API as needed. Both mixed_t and dynamic_t must
use hash_t. Do not replace its storage with std::unordered_map or another table.
Generated code remains untracked; commit validated port slices separately.

The existing reusable implementation is in
runtime/include/scpp/support/hash_t.hpp, not a new dependency on the original
mem_container donor. It contains two existing storage forms: the typed hash and
the packed table used for heterogeneous keys. Both belong to the reviewed hash
implementation and must retain their own algorithms. The initial review does not
justify rewriting or merging them into a newly designed table.

## Current owners and dependencies inspected

| Owner | Findings relevant to the port |
| --- | --- |
| support/hash_t.hpp, hash_detail | Control bytes, fingerprints, empty/deleted states, compact bucket-index variants, physical entry positions and index-width selection |
| support/hash_t.hpp, typed hash_t | Separate key/value/live arrays, insertion order, probing, rehash/growth and integer append key policy |
| support/hash_t.hpp, packed_table<Value> | Packed/associative shapes, packed keys, tombstones, ordered storage and existing hash index |
| support/hash_t.hpp, hash_t<mixed_t,mixed_t> | Public family wrapper over packed_table<mixed_t> |
| support/hash_t.cpp | Dynamic read adapters and explicit instantiations, not the core table algorithm owner |
| util/global_string_pool.hpp | Interned string-key identities used by packed keys; its internal unordered_map is an existing string pool, not a replacement for hash_t |
| support/mixed_t.hpp/.cpp | Tagged scalars and owned/shared/weak hash storage, copy/detach rules, borrowing and iteration dependencies |
| dynamic_t.hpp, support/dynamic_value.hpp/.cpp | Independent tagged dynamic values; scalar copies and shared container identity; packed_table<dynamic_value> storage |

The current dynamic representation is **not** a shared mixed hash. The v0.2
amendment near the start of specs/dynamic_types.md overrides the older object-only
description still present in parts of runtime/specs/hash_t.md and older sections
of dynamic_types.md. It requires mixed containers of mixed values and dynamic
containers of dynamic values, with distinct ownership behavior and no implicit
container crossing. Reusing hash_t must preserve this separation. A hash-owned
adapter for the existing packed_table<dynamic_value> can meet the user's direction
without substituting mixed payloads or changing the table core.

## Proposed bounded port order

1. Freeze the reviewed core boundary and its behavior proofs. Inventory the exact
   functions to move and retain the probing, control-byte, growth, tombstone,
   physical-index-width, packing and insertion-order behavior. Do not copy the whole
   1,700-line header with all old wrapper/iteration dependencies into the public API.
2. Bring over the private hash implementation in bounded pieces and add concrete
   generated API adapters. Start with a typed key/value case using v0.2 integer or
   string values. Supply key adaptation to the new explicit scalar API, preserving
   the existing key-domain and integer append rules. Prove collision, update,
   deletion/churn, order, growth, copy/move and index-width transitions.
3. Preserve the existing packed-table implementation and key-pool contract. Separate
   its dependency wiring from public wrappers without changing core operations.
   Keep an independent value fixture to prove the storage remains reusable before
   introducing either tagged family.
4. Port mixed's tagged scalar/hash ownership through the hash-owned boundary. Review
   COW/shared/weak states, missing-key behavior and safe mutation explicitly; do not
   bulk-import the old operator matrix, PHP coercion or wrapper lifting.
5. Port dynamic's independent tagged representation with hash-owned dynamic-valued
   containers. Preserve shared container identity, scalar value copies, binding
   replacement and nested family isolation. Do not restore the obsolete shared
   mixed-hash alias.

Private templates remain permitted, but no std/hash implementation templates enter
the public include closure. Metadata owns public concrete identities and requested
casts/operators; the symbol-aware compiler owns contextual source decisions.

## Existing proofs to mine, not wholesale import

- tests/runtime/native/test_hash_churn.cpp
- tests/runtime/native/test_packed_table_storage.cpp: independent element fixture,
  packed-to-associative transition, tombstones, copy/move and clear
- tests/runtime/hash_t/level_01/: strict key domains, fixed-width keys and access
- tests/runtime/native/test_mixed_t_dynamic.cpp
- tests/runtime/native/test_dynamic_value.cpp

This review inspected owners and relevant test cases; it did not run the legacy
suite or establish port equivalence. Each moved core slice needs differential or
focused equivalence evidence against the reviewed implementation, including index
width based on physical entries rather than merely live size.

## Questions to settle during the port review

Concrete public hash specialization spellings, missing-key API, boxed extraction
surface, public iteration and v0.2 key-pool placement still need a bounded design.
Existing wrapper and iteration machinery must not be dragged in merely to compile
an otherwise small slice. If separating those owners requires a wide refactor or
changing protected algorithms, report the specific blocker and obtain confirmation
before implementation. No such refactor or core change is performed by this review.
