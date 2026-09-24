# Issue #242 native compiler Storage slice
Doc Status: planning

Authority for this slice: `specs/compiler_storage.md`.

The PHP reference is in the sibling checkout at
`../simple_cpp_01/compiler/my-try/helpers/`. Do not edit/import that shared IDE
work area as part of this native slice. PHP tests remain reference behavior, not evidence that native lifetime or
allocation handling is correct.

## First proof (historical inline-value slice, ce1885b9)

The original standalone native fixture modeled a file's token records and AST child
memberships with inline records, stable owner-local positions and read-only views.
It exercises physical relocation after deletion, view redirection, field editing,
owner lifetime, index hooks, fail-closed behavior and allocation preparation.
It is a model-shaped native fixture, not execution of the PHP compiler in native code.

From repository root:

```sh
g++ -std=c++23 -Wall -Wextra -Werror -O2 -Iruntime/include tests/runtime/compiler/level_01/runtime_compiler_001_storage.cpp -o /tmp/scpp-242-numeric.bin
/tmp/scpp-242-numeric.bin
```

The fixture is also registered with the runtime metadata runner and CTest
(`scpp_test_compiler_storage`). It keeps assertions active in release builds.

Validation on 2026-09-24: the optimized warning-clean standalone build and runtime
metadata runner passed. AddressSanitizer, UndefinedBehaviorSanitizer and
LeakSanitizer passed; the leak-enabled executable ran outside the ptrace sandbox.
The fixture also checks destruction counts after normal and failed-owner/view paths.
The `scpp_compiler` interface target was consumed by the focused CMake executable,
and `scpp_test_compiler_storage` passed under CTest.

## Remaining delivery work

- Measure dense and deletion-heavy costs, separating shared-record allocation/control-block costs from slot/index costs.
- Extend the direct position-boundary proof to a bounded owner exhaustion fixture;
  expand allocation/index failure coverage.
- Unbacked computed view/interface parity and source declaration bindings.
- Strict numeric/mode diagnostics at source boundaries, including typed construction.
- PHP/native comparisons for supported operations, under the restored shared-record identity contract.
- Runtime module registry and conversion composition once bindings exist.
- Actual compiler model conversion, separately from this helper proof.

## String-keyed slice

String owners now use the same mutation implementation as numeric owners, with
a private compile-time key policy. A standard unordered_map owns key-to-position
lookup; positional key storage supplies iteration and hook keys. Both modes back
the same view shape through a variant of shared owner handles. No ownership policy
parameter or source-language generic support was introduced.

The second native fixture covers empty/numeric-looking/embedded-NUL keys, duplicate
rejection, keyed replacement, deleted-key reinsertion, physical relocation, churn,
hook arguments and guards, both key modes crossed with both view policies, and
actual slot/hash allocation failures. It distinguishes usable preparation failures
from failed-owner commit errors and confirms that failed view membership after
owner publication does not remove the published row.

Validation on 2026-09-24: both native fixtures pass the runtime metadata runner and
CTest. Both implementations also pass AddressSanitizer, UndefinedBehaviorSanitizer
and LeakSanitizer, including real key/slot allocation faults. The additional
deleted-key test confirms that reinsertion never reconnects dangling memberships.

Run the focused fixtures with:

```sh
php tests/tools/run_tests.php run --suite=runtime --test=runtime_compiler_00 --jobs=3
```

CTest names: `scpp_test_compiler_storage`, `scpp_test_compiler_string_storage`.
The native module intentionally does not repair or reuse hash_t: numeric positions
need no hash lookup, and its current index-width/reserve gaps would widen this work.
String mode currently duplicates key storage and position metadata; performance
measurement and representation optimization remain separate follow-up work.

## Shared-record direction update

The latest #242 decision restores automatic `shared_p<T>` wrapping. T is again the
record type; append/replace accept existing handles, read/iteration return handles,
and hooks receive old/new handles preserving identity. Empty handles are invalid.
`read` replaces the misleading public `snapshot` name. Field convenience helpers
access the shared record; fluent handle field edits no longer copy rows.

The numeric/string fixtures now use noncopyable, nonmovable records. A third
fixture proves shared identity, old handles surviving replacement/removal/owner
teardown, growth, null rejection before hooks, failure without handle revocation,
and final destruction when the last handle is released. Both key modes and both
view policies are exercised. No changes to the PHP helper work area are needed.

Build all focused fixtures:

```sh
cmake -S runtime -B build-storage-242 -DSCPP_WITH_MYSQLI=OFF
cmake --build build-storage-242 --target test_compiler_storage test_compiler_string_storage test_compiler_shared_records -j2
ctest --test-dir build-storage-242 -R '^scpp_test_compiler_(storage|string_storage|shared_records)$' --output-on-failure
```

Source bindings, computed-view extensibility, performance measurements and actual
compiler conversion remain outstanding. Existing cycle limitations still apply;
this change does not introduce weak row references or automatic cycle collection.

Validation of the shared-record revision (2026-09-24): all three fixtures pass
CTest and the runtime metadata runner, with warning-clean builds. All three pass
ASan, UBSan and LeakSanitizer (leak-enabled runs outside the ptrace sandbox).
A focused negative compile probe confirms that Storage<shared_p<T>> is rejected
with the record-type diagnostic rather than creating a double wrapper.
