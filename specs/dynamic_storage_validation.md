# Private dynamic ownership validation

Doc Status: supporting
Date: 2026-10-29
Contract: [dynamic_storage.md](dynamic_storage.md).

## Validation

- Full Release CTest: 41/41 passed, including the PHP generator contract suites,
  typed hashes, packed storage and the unchanged mixed ownership assertions.
- Focused libc++ UBSan: mixed/dynamic ownership 2/2 passed.
- Separate Clang ASan+UBSan executables for both mixed and dynamic passed using
  libstdc++. Leak detection was disabled; this is not a leak-sanitizer proof.
- A standalone temporary copy of the v0.2 boxed/hash sources and ownership fixtures
  compiled and ran both families with libc++; no v0.1 runtime inputs were used.
- `git diff --check` passed. No PHP generator, publication metadata or generated
  public API changes were needed for the private storage refactor.

`tests/dynamic/storage.cpp` keeps checks active in Release. It proves scalar copies,
exact kinds, byte/NUL strings, null versus empty, explicit construction before writes,
lazy null/missing reads and removal, key bounds, shared replacement/removal, nested
alias visibility, by-value parameter mutation versus reassignment, clean and moved-from
reuse, append limits and self/mutual cycles with explicit edge removal.

Compile-time assertions reject cross-family construction, assignment and set/append
while allowing same-family values. Explicit scalar conversion tests cover null,
bool, signed integer limits, negative zero, NaN, string bytes and container rejection
before target replacement. Injected conversion-allocation failure preserves the target.
Allocation-failure sweeps through shared-table transitions verify both aliases retain
old contents after failure and see the retry's new value. The existing packed suite
remains the deeper owner of index growth, pool failure and ordered churn proofs.

## Costs and limitations

Both private values measure 24 bytes on this x86-64 target. Numeric/null/bool values
and copies allocate nothing. Dynamic container copying and replacement of an existing
inline entry through a shared alias allocate nothing: dynamic does not clone its
table. String bytes are immutable and shared within one family; explicit cross-family
string conversion currently copies bytes and can allocate. No throughput improvement
or portable ABI size guarantee is claimed.

The common owner has one compile-time mutation-policy distinction: mixed stages
bootstrap/detachment, dynamic requires and mutates the existing shared table. This
preserves separate payload types without duplicating scalar storage/extraction code.
The previous mixed-only source/target was replaced by `boxed_value.cpp` and
`scpp_boxed_storage`; no second production implementation remains.

Strong cycles require explicit edge removal to reclaim their storage under reference
counting. Tests break those edges; automatic cycle collection is not proved or added
(RT-DEBT-02). Public generation, operators, source integration and traversal remain
separate work. PHP is the sole current API generator; the private template mechanics
must remain outside future public include closures.
