# Runtime-owned scalar migration
Doc Status: supporting

## Ownership and publication

Middleware `types.hpp` now re-exports the exact runtime-owned `scpp::int32`,
`int64`, `uint32`, `uint64` and `boolean` classes. The duplicate middleware
classes and `src/types.cpp` arithmetic implementation are removed. The private
`scalar_access` adapter uses the public runtime value accessor to create legacy
template arguments where existing runtime APIs still require them.

`types.json` records public runtime names/headers separately from private native
bindings. Explicit publication copies the selected runtime's `scpp/integers.hpp`
into middleware's include snapshot. The existing runtime identity pin pairs that
header with its compiled core; no broad runtime include directory is exported.
This is one type family with a dependency snapshot, not two independently owned
implementations. Normal compiler builds still never rebuild runtime or middleware.

No string/container or task-scheduling redesign is part of this migration. The
public runtime native-value accessor is now available to compiler types; compiler
processing should continue using runtime operations, keeping native extraction at
private interoperability boundaries.

## Validation — WSL, 2026-10-27

- Normal suite: 13/13 passed.
- ASan/UBSan with leak detection: 13/13 passed; normal publications unchanged.
- ThreadSanitizer: 12/12 passed; allocation-hook scalar test excluded as before.
- Public boundary: exact C++ type identity for all five re-exports, byte equality
  with the pinned runtime header, no template leakage, and existing rejected
  conversions/helpers verified.
- Dependency isolation: edits to live runtime/middleware implementation or headers
  caused no compiler build; this includes the new public integer header. Compiler,
  support and model edits rebuilt their consumers; dependency archives stayed fixed.
- Publication fixture: copied public header, incremental object reuse, unchanged
  publication reuse, failed-build preservation, mismatched runtime rejection and
  interrupted-publication recovery passed. Fixture now supplies the private bridge
  files required by the existing publisher, as well as the new public header.
- Relocation: moved project built and passed 13/13 tests using the same archives;
  neither runtime nor middleware build trees were created.

## Build costs

Clang 18.1.3, C++23/O2/NDEBUG, Ninja 8 jobs, Mold, no LTO/PCH. Three trials per
case, before and after migration, with no competing build started by this task.
Only the compiler executable target is built: tests are excluded. Each trial
removes the selected object files and includes archive creation/linking. Published
runtime/middleware archive timestamps and SHA-256 hashes remain unchanged.

| Scope | Units | Before runs (s) | Before median | After runs (s) | After median |
| --- | ---: | --- | ---: | --- | ---: |
| src/ | 10 | 0.8437, 0.7958, 0.8646 | 0.8437 s | 0.7284, 0.9762, 0.9351 | 0.9351 s |
| src/ + support/ | 17 | 8.0586, 7.0172, 7.0508 | 7.0508 s | 7.272, 7.2171, 6.8683 | 7.2171 s |

No-op build after migration: 0.0106 s. The initial explicit middleware rebuild
compiled its five remaining implementation units and published in 3.738 s. This
is a single publication timing, not a before/after benchmark. Runtime publication
reused its already built objects.

These measurements show no demonstrated build speedup: the small median increases
are within the observed WSL run variation. The gain is one scalar owner and one
compiled arithmetic implementation, while preserving inexpensive compiler builds.
No dev002 measurement was made for this migration.

Reproduce from this directory:

```sh
python3 tools/measure_build.py --jobs 8 --trials 3 --output build/scalar-build-cost.json
```

Local before/after evidence is in `build/scalar-migration-before.json` and
`build/scalar-migration-after.json`. The script expects the current ten compiler
and seven support translation units and rejects a changed inventory rather than
silently measuring a different scope.
