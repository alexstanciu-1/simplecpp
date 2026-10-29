# Packed storage foundation for mixed/dynamic

Doc Status: planning
Date: 2026-10-29
Status: bounded compact-key implementation approved; private foundation implemented.
Reviewed runtime revision: 84e90960d.

Follow-up: [donor investigation and compact repair options](runtime_packed_storage_investigation.md)
records executable evidence, history, allocation failures and measured layout sizes.
P0 is complete. The user approved the bounded alternative in P1: integer keys
0…INT32_MAX, rejecting negative/wide keys, append overflow before mutation, reserved
pool codes, non-interning probes and transactional updates. No exceptional-integer
pool or larger key representation is needed. See [the implemented contract](../packed_storage.md).

## User correction and governing direction

The user rejected replacing the optimized compact key representation with a larger
explicit-tag/full-int64 entry representation. Preserve the existing optimized design;
identify and fix mistakes within its owning mechanisms. The later approval authorizes the bounded private implementation described above.

The previous 16-byte-key recommendation was premature. It treated observed encoding
problems as justification to abandon a storage optimization before checking the v0.1
design documents. The findings below remain useful regression candidates, but do not
justify that recommendation.

## Recovered v0.1 design intent

Repository-root references:

1. `runtime/specs/hash_t.md`, sections 1–2: reuse the donor mem_container storage
   strategy and retain its memory/performance-oriented structure while enforcing
   language contracts. This intent is also present in the early version at commit
   910de5765; it is not a recent rewrite assumption.
2. `specs/planning/hash_t_key_abstraction_task.md`, sections 1.1, 2.3, 3.1–3.3 and 5:
   the pooled-lowered mixed/dynamic mode remains specialized, retains its compact
   tokenized layout and packed integer fast paths, and must not acquire the heavier
   storage/dispatch model of explicit typed keys. This planning note was introduced
   with the typed-key work at 8e5fed2aa. It is design evidence, not higher semantic
   authority; the user's correction now explicitly reaffirms this direction.
3. The same note, section 2.1: stored hash width and canonical key identity are
   different concepts. The discussed 1/2/3/4/8-byte stored hash widths were future
   design options, not proof that all are implemented or that truncated hashes can
   replace equality/identity. The width-selection rule was explicitly deferred.
4. The same note, section 4.4: pack_key/unpack_key codec boundaries permit compact
   pooled keys and explicit typed keys to retain different representations. They
   must not hide semantic differences or force identical layouts.
5. The same note, section 2.5: evaluate lookup, update/append, iteration, key-storage
   footprint and pooled memory growth; avoid material memory/performance regressions.
6. `runtime/specs/hash_t.md`, packed removal/key stability: packed keys are implicit
   0..n-1. Removing an element must not renumber later entries; transition to
   associative storage or retain tombstones. Clear restores packed-empty state.
7. `specs/planning/compiler_support_runtime_implementation_audit_2026_06_27.md`,
   section 1: explicit typed keys use separate key/value/live arrays and a compact
   uint8/uint16/uint32 bucket-index variant. Bucket-position width is distinct from
   pooled key-code width and from semantic key identity.

The design note also discusses fixed shared key sets and richer typed-key families.
Those are not prerequisites for this packed-storage port and are not added to scope.

## Intended optimized structure to retain

- Packed list mode has implicit integer keys and no associative key/index arrays.
- Associative mode uses compact lowered key codes and an interned string identity.
- Values remain separate from key/index storage and independent of either boxed family.
- The custom control bytes, fingerprints, probing, tombstones, ordered physical
  positions and growth policy remain owned by the hash implementation.
- Bucket-index widths follow physical positions, including churn, not only live size.
- Typed hashes continue storing their declared key type; do not force that layout
  onto pooled mixed/dynamic tables.

## Historical donor issues reproduced before approval

Inspected owners: `runtime/include/scpp/support/hash_t.hpp` (dyn_keys and packed_table)
and `runtime/include/scpp/util/global_string_pool.hpp/.cpp`.

- Integer pack currently casts int64 to uint32. Codes for 1 and 4294967297 coincide;
  2147483648 has the string flag; -1 has the tombstone code 0xffffffff. These are
  historical encoding consequences; the later approved key-domain limit now rejects these inputs.
- String ID 0x7fffffff also produces the tombstone code. Reserved codes must not be
  allocated as live identities; exhaustion must be checked before narrowing.
- Append can cross the immediate-integer/tag boundary without a guard or repair path.
- Packed-to-associative conversion publishes key storage before all allocations
  succeed. Multi-array insertion and pool map/vector updates need failure recovery.
- String probes call intern, so a miss can grow retained pool storage. Review a
  non-interning lookup path without changing stable-ID ownership.

The recovered docs do not fully specify an exceptional-integer encoding for negative
or wide integers in the pooled path. Do not invent that history or infer a 31-bit
language restriction. Trace the codec, pool and unpack consumers, reproduce these
cases in the actual donor, and design the smallest correct repair while retaining
compact stored codes and normal packed fast paths. Any fallback proposal must show
its identity rules, limits and measured cost before being treated as agreed.

## Semantic boundary

Top-level `specs/dynamic_types.md` and `specs/array_semantics.md` still govern:
mixed containers hold mixed values with value/COW behavior; dynamic containers hold
dynamic values with shared identity. Both use the hash-owned storage, without crossing
payload families. Source key admission/normalization is separate from physical codes;
typed hashes retain strict keys and mixed/dynamic retain their approved normalization.
No type-blind compiler adaptation or public traversal machinery is imported merely
to compile the storage. RT-DEBT-01 remains deferred.

## Revised next steps

| ID | Work | Acceptance |
| --- | --- | --- |
| P0 | Reconstruct compact codec/pool invariants and run donor reproductions | Distinguish documented design, implemented behavior and defects |
| P1 | Review local compact repairs | Reserved-code handling, exceptional integers, append limits and rollback; memory/cost evidence before approval |
| P2 (done) | Adapt shared index primitives and private packed engine | Preserve typed-hash behavior, packed fast path, order and width transitions |
| P3 (done) | Adapt pool boundaries and failure handling | Stable IDs, byte/NUL keys, non-interning probes, coherent failed transitions |
| P4 (done) | Prove independent payload/lifecycle behavior | Reference membership/order traces, copy/move/clear, failure injection and churn |
| P5 (done) | Validate standalone reuse and consolidate | Release/UBSan, focused sanitizers, memory/lookup/churn measurements and commit |

P0–P1 are settled by the later bounded-design approval. Do not publish mixed/dynamic,
add a test-only public runtime family, or choose a larger per-entry representation as
part of this audit. The next implementation must preserve optimization and prove the
necessary fixes rather than replace the optimized model wholesale.

Completed evidence: [packed-storage validation](../packed_storage_validation.md).
