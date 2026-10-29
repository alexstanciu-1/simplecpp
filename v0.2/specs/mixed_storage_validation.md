# Private mixed ownership validation

Doc Status: supporting
Date: 2026-10-29
Contract: [mixed_storage.md](mixed_storage.md).

Follow-up: [dynamic ownership validation](dynamic_storage_validation.md) records
revalidation after moving mixed into the shared boxed-value owner. The evidence
below describes the original mixed-only slice.

- Full Release CTest: 39/39 passed, including prior packed, typed-hash, public
  boundary and generator freshness checks.
- Focused libc++ UBSan: packed and mixed storage 2/2 passed. Final mixed assertions
  for unique-table replacement were rerun in both Release and UBSan and passed.
- Clang ASan+UBSan with libstdc++ passed the ownership and allocation-failure suite.
  Leak detection was disabled in this environment; this is not a leak-sanitizer proof.
- A standalone temporary copy of the v0.2 boxed/hash sources and mixed fixtures
  compiled and ran with libc++; no old runtime dependency was used.
- `git diff --check` passed. No Rust generator/publication files or v0.1 runtime
  implementation files changed.

The private test keeps checks active in Release. It covers exact scalar kinds,
full int64 values independently of the key bound, byte/NUL strings, null versus
constructed empty container, lazy lookup/removal, integer-key rejection on null,
scalar receiver rejection, same-family payloads, membership versus null payload,
COW copies, nested copied reads and explicit write-back, reset, reusable moves,
self-assignment/self-insertion and append at/deletion after the maximum key.

Allocation failures are injected at successive allocation points in null bootstrap,
shared-table clone plus mutation, removal and string creation. Source/sibling values
remain unchanged; a failed bootstrap remains null; retries on the same value succeed.
The packed suite separately proves the unique-table mutation guarantees used here.

## Measured costs and remaining scope

`sizeof(mixed_value)` is 24 bytes with the current x86-64 Clang/libc++ build, matching
the design target. This is a measurement, not a portable ABI promise. Inline
null/bool/int64/float64 construction and copying allocate zero objects. Copying
strings and containers also allocates nothing: strings share immutable bytes and
containers use COW. Returning string bytes can allocate an independent copy.
Replacement in a uniquely owned table allocates nothing for inline values; first
shared mutation copies the table arrays. Absent removal and reads do not detach.
No throughput benchmark or comparison with the coupled donor boxed runtime is claimed.

This is a private ownership proof. Dynamic, generated/public mixed APIs, scalar
operators/casts, native shared/weak carriers, source nested-write lowering, JSON,
source numeric-string normalization and traversal remain outside this slice. Public
API selection must not expose the private C++ table or interior references.
