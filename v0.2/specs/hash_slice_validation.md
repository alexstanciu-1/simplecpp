# Typed hash slice validation

Doc Status: supporting
Date: 2026-10-29

The contract is [hash_slice.md](hash_slice.md). The donor is
`runtime/include/scpp/support/hash_t.hpp` at `0c230e0bc`.

## Evidence

- `tests/hash/storage.cpp`: 6,000 deterministic collision-heavy operations against
  an independent membership/value and insertion-order oracle; replacement, removal,
  reinsertion, copies/moves, append/deletion/overflow, capacity guards and injected
  allocation failures. Churn crosses both 256 and 65,536 physical positions while
  retaining only 16 live entries. Assertions remain active in Release.
- `tests/hash/consumer.cpp`: all four concrete key/value combinations, byte-exact
  string keys including NUL, copied and nullable lookup, independent ownership,
  reusable moved-from values, self-assignment and copy/insertion/read failure.
- `tests/hash/boundary.cmake`: standalone template-free include closure and compiler
  rejection of native arguments, string-key append, traversal, subscript and condition.
- Rust preparation tests cover requested subsets, unsupported key/value families,
  missing nullable/string dependencies, duplicate specializations, header collisions
  and invalid metadata policy. Existing rejection and byte-parity corpora remain active.
- Release CTest: 29/29 pass. Cargo workspace tests pass in debug and release; Clippy
  with warnings denied passes. UBSan passed 28/29 initially; the integer-preparation
  metadata compatibility fix then passed its failed native-probe test and all four
  hash tests (5/5). Final Release hash rerun passed 4/4.
- `tests/build_cutover.py` passes: isolated source-only build and 29-test suite,
  unchanged-build stability, metadata/publication/Rust/target invalidation and
  production configuration/build/demo with tests disabled.
- Focused private-storage and generated-consumer AddressSanitizer+UBSan runs pass
  using Clang with libstdc++; leak detection disabled in this environment. Primary
  Release and UBSan builds use libc++. This is not a leak-sanitizer proof.

The optional metadata registration changes input fingerprints in the frozen G1
corpus and one full-metadata scalar fixture. Only these input identities were updated;
all prior expected artifact hashes and rejection expectations were retained. Scalar
fixtures exclude optional hash metadata just as they exclude other runtime families.

## Cost observations and limits

10,000 borrowed probes with an already constructed long string key allocate zero
objects. Default empty hashes allocate nothing. Materialized hashes use one pimpl
allocation plus their separate arrays and index. Copied string reads allocate as
needed for independent ownership; allocation failure does not mutate the table.

One standalone Release run of the 66,000-entry churn plus copy proof took 27,634 us
and 2,604 allocations. These are local observations, not a benchmark comparison or
speedup claim; sanitizer/concurrent-build timings are not comparable.

Erased physical slots remain until clear/destruction, and copies preserve that
physical history. Integer append scans physical entries to find the largest live
nonnegative key. These inherited costs are explicit; compaction/layout optimization
and a cached append maximum are not part of this batch. Capacity limits are checked.
Public traversal and mutation preservation remain RT-DEBT-01; nested/boxed hashes,
object keys, mixed/dynamic, native convenience overloads and compiler integration
remain outside this slice.
