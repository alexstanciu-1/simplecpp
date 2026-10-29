# Packed storage validation

Doc Status: supporting
Date: 2026-10-29

Contract: [packed_storage.md](packed_storage.md). The v0.1 donor remains unchanged;
its recorded defects are preserved in the earlier investigation evidence.

## Behavior and failure evidence

`tests/packed/storage.cpp` runs assertions in Release as well as sanitizer builds:

- Dense storage with no allocated keys/index; transition, deletion identity,
  replacement order, reinsertion, clear, reusable moves and independent copies.
- Explicit negative/wide integer rejection; append through INT32_MAX followed by
  failure without mutation; deleting the maximum; string-only append starting at zero.
- Exact string bytes including NUL, strict private numeric-string identity,
  non-interning find/remove with no allocations, canonical IDs across payload types
  and translation units (`identity.cpp`).
- Small-limit pool exhaustion, last usable/reserved IDs and pre-narrowing checks.
- Allocation-failure sweeps through transitions, deletion transitions, width growth,
  pool insertion, combined string/table insertion and copy assignment. Failed
  operations preserve membership/order; retry works on the same object. Completed
  interned identities remain resolvable even if table insertion subsequently fails.
- Value and shared-pointer payloads prove that the engine preserves payload-owned
  semantics without importing either boxed family.
- 12,000 ordered reference-model operations and churn through both 256 and 65,536
  physical positions, retaining small live membership.

Full Release and libc++ UBSan CTest suites passed 38/38. After adding further boundary
and combined-failure cases and repairing copy preparation, the focused Release
packed/hash suite and UBSan packed test were rerun successfully. A separate Clang
ASan+UBSan run with libstdc++ passed; leak detection was disabled, so this is not a
leak-sanitizer proof. An initial libstdc++ sanitizer failure during failed variant-copy
unwinding led to explicit key/index preparation outside the representation variant.
No suppression was added.

A temporary standalone copy of only the v0.2 private hash sources and packed test
fixtures compiled and ran successfully with libc++; no v0.1 runtime headers or
sources were available in that include path. Existing public hash boundary/freshness
checks continue to pass. No generator output or public family was added.

## Local donor comparison

`tests/packed/benchmark.cpp` can compile against either the actual donor or the new
private storage. Both use Clang 18.1.3, libc++, `-O3 -DNDEBUG`; the donor build defines
`SCPP_PACKED_DONOR` and links its `global_string_pool.cpp`, while the new build links
`packed_key.cpp`. Seven separate process runs per build gave these median times:

| Workload | Donor microseconds | New microseconds |
| --- | ---: | ---: |
| 10,000 dense appends | 182 | 158 |
| 10,000 sparse inserts | 1,005 | 1,024 |
| 200,000 integer hits | 4,084 | 3,993 |
| 10,000 insert/remove churn operations | 1,368 | 1,255 |
| 200,000 interned long-string hits | 6,415 | 6,847 |

Checksums matched (1,000,900,000). These short local samples are observations, not a
speedup guarantee. String-hit time was about 7% higher in this run; this remains a
baseline for broader workload measurements. Different public/private lookup wrappers
and the removed traversal owner also affect comparisons. The benchmark only uses
valid common keys and does not measure allocation-failure paths or pool exhaustion.

The table object is 128 bytes versus the donor's 144, with the deferred traversal
owner omitted. At 1,000 entries, storage estimates are 4,224 bytes dense / 14,464
sparse versus 4,240 / 14,544. At 10,000 entries they are 65,664 / 180,352 versus
65,680 / 180,432. Both use four-byte associative codes; dense keys/index remain
unallocated. Estimates include table-owned vector capacities, not allocator overhead
or shared pool storage. The reverse pool retains pointer-sized slots without a new
kind bitmap or exceptional-integer map. Erased slots and pooled strings remain
retained according to the contract; no compaction or concurrency claim is made.
