# Compact packed storage: donor investigation

Doc Status: planning
Date: 2026-10-29
Status: historical investigation; subsequent approval selected bounded integer keys.

Decision update: the user approved integer keys 0…INT32_MAX and rejection outside
that range, rather than the exceptional-integer pool candidate below. Append throws
at that boundary. See [the implemented contract](../packed_storage.md). The candidate
comparison is retained as investigation history, not the current recommendation.

This records evidence for [the packed-storage batch](runtime_packed_storage_batch.md).
It does not authorize implementation or change language semantics.

## What the optimized design owns

The donor intentionally keeps dense keys implicit, associative keys as four-byte
codes, values separate, and bucket indices narrow. Shared pooled string identities
permit raw-code equality across tables. These are useful optimizations to preserve.

`runtime/specs/hash_t.md` sections 1–2 establish the storage-preservation intent.
`specs/planning/hash_t_key_abstraction_task.md` sections 1.1, 2.5, 3.1–3.3 and 4.4
describe the specialized pooled representation and codec boundary. That planning
document is design evidence, subordinate to language specs.

History reinforces this reading:

- `0a6bdeb90`: the pool uses a string-owning map, stable pointers in a reverse
  vector, and a low map load factor to trade shared memory for lookup speed.
- `6e625d63b`: string interning was centralized across runtime boundaries. A repair
  must preserve global identity rather than assign unrelated per-table IDs.
- `8e5fed2aa`: typed-key work explicitly retains the specialized dynamic-key path.

The inspected history and documents do not supply a negative/wide-integer escape
encoding. Adding one would be a repair proposal, not recovery of a hidden design.

## Reproduced defects

The [audit runner](evidence/packed_codec_audit/README.md) compiles the actual donor
with a primitive payload, independently of the boxed runtime families. Recorded
results include PHP-target normalization both disabled and enabled.

| Case | Observed donor behavior |
| --- | --- |
| Dense append | No key array or hash index allocated |
| Integer keys `1` and `4294967297` | Same code; second write replaces first |
| Integer `2147483648` and first pooled string | Same code; second write replaces first |
| Integer `-1` | `size() == 0`, yet `has(-1)` is true: code equals tombstone |
| Append after `2147483647` | Returns `2147483648` repeatedly; later append replaces prior value |
| Missing string lookup | Interns the missing key, growing process-lifetime storage |
| Allocation failure during packed transition | Some failure points lose access to existing entries; another reports a new entry that lookup cannot find |
| Allocation failure during interning | One failure point leaves a map ID with no reverse entry; retry cannot resolve it |

Source inspection additionally shows that pool ID `0x7fffffff` can be allocated,
producing the reserved tombstone code. Exhaustion checks narrow the size before
checking the limit. This limit defect was not reproduced by allocating billions of
entries; a repair should prove it using a synthetic small limit.

Owning code: `runtime/include/scpp/support/hash_t.hpp` (`dyn_keys`, `packed_table`)
and `runtime/include/scpp/util/global_string_pool.hpp/.cpp`. The code publishes
storage before all transition allocations succeed, and inserts into the pool map
before its reverse vector is safely updated.

## Source normalization is a separate question

With the PHP-target define enabled, the current codec normalizes decimal strings
without signs or leading zeros, through `UINT32_MAX`. Thus `"12"` becomes integer
12; `"08"` and `"-1"` remain strings. `"2147483648"` and `"4294967295"` become
codes colliding with the string flag and tombstone. `"4294967296"` remains a string.
With the define disabled, all these strings are pooled strings.

This describes implementation, not approval of its normalization range. Top-level
`specs/array_semantics.md` and `specs/dynamic_types.md` govern source behavior.
Repair integer identity independently of any normalization-policy change; do not
silently import the donor's numeric-string limits as the v0.2 language contract.
Typed hashes retain strict declared keys.

## Compact repair options

Preferred candidate: keep four-byte table codes and inline nonnegative integers
through `INT32_MAX`. Interpret the high bit as **pooled identity**, encompassing
strings and exceptional signed-64-bit integers. Never allocate the tombstone ID.

The shared pool would own separate string/integer lookup maps and a common reverse
identity space. A reverse slot can hold a string pointer or int64 in an eight-byte
union on this host; a separate kind bitmap distinguishes them. This preserves the
existing eight-byte string reverse slot, adding one bit per pooled identity plus
bitmap capacity rounding. Exceptional integers add their own lookup-map storage
and process-lifetime retention. Common immediate integer keys do not enter a pool.

This is a candidate layout, not an implemented codec or speed measurement. A
1,024-slot kind bitmap costs 128 bytes. The actual reverse-slot size must be checked
on supported targets; union lifetime and transactional updates need explicit owners.
String lookup/hashing should retain its current path. Reverse decoding and append
scans need a kind check. Raw-code equality remains valid across tables because IDs
remain globally canonical. Strings and exceptional integers share the available
`2^31 - 1` pooled identities instead of reserving separate ranges.

Alternatives:

| Option | Tradeoff |
| --- | --- |
| Split high-bit codes into string and exceptional-integer prefixes | Four-byte keys and cheap tag decoding, but halves the string-ID space and partitions capacity |
| Table-local exceptional-key dictionary | Avoids global retention of exceptional integers, but adds per-table state and breaks cross-table raw-code equality without additional machinery |
| Larger explicit keys | Withdrawn: conflicts with the agreed compact-layout constraint |

Append must operate on decoded integer identities, ignore string keys, preserve
remaining keys after deletion, and detect signed-64-bit exhaustion without wrapping.
The implementation goal must specify the exact empty/negative-only and deletion
cases from the governing contract before coding them.

## Recommended discussion and next batch

Agree whether exceptional integers should use the shared identity pool. This retains
the compact table layout at the cost of shared storage for distinct exceptional
integers and a small reverse-kind bitmap. Do not claim that cost is free.

Then prepare one private-storage batch covering:

1. Transactional pool insertion and packed-to-associative/insertion updates.
2. Reserved-code/exhaustion checks and non-interning read probes.
3. The agreed exceptional-integer codec and decoded append arithmetic.
4. Donor adaptation with existing probing, order, packed mode and narrow indices.
5. Boundary, allocation-failure, cross-table identity and churn proofs, followed by
   memory and lookup measurements against the donor's valid common-key workloads.

Baseline on this host, primitive `int` payload: table object 144 bytes; donor
`estimated_storage_bytes()` reports 4,240 for 1,000 dense entries and 14,544 for
1,000 sparse entries (key capacity 1,024). These are container estimates, excluding
shared pool storage and allocator overhead, not total process memory measurements.
The candidate union's size alone proves neither equivalent speed nor total memory.

Acceptance should include no extra per-entry code bytes, no associative allocation
for dense lists, stable identities across tables, complete signed-integer identity,
coherent state after exceptions, and no pool growth on missing read probes. Tests
must cover string bytes/NULs and exhaustion without huge allocations. Performance
claims require release builds; this audit used `-O0` solely to reproduce behavior.

Non-goals remain public mixed/dynamic APIs, compiler adaptation, traversal preservation
(RT-DEBT-01), new concurrency guarantees, and changes to the typed-hash family.
