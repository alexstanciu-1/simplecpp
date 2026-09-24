# Issue #242 native compiler Storage slice
Doc Status: planning

Authority for this slice: `specs/compiler_storage.md`.

The PHP reference is in the sibling checkout at
`../simple_cpp_01/compiler/my-try/helpers/`. Do not edit/import that shared IDE
work area as part of this native slice. Its object-lifetime tests are not evidence
for native inline value behavior.

## First proof

The standalone native fixture models a file's token records and AST child
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

- Measure dense and deletion-heavy costs, including copies needed for hook snapshots.
- Extend the direct position-boundary proof to a bounded owner exhaustion fixture;
  expand allocation/index failure coverage.
- Unbacked computed view/interface parity and source declaration bindings.
- Strict numeric/mode diagnostics at source boundaries, including typed construction.
- PHP/native comparisons for supported operations, with explicit lifetime differences.
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

Run both fixtures with:

```sh
php tests/tools/run_tests.php run --suite=runtime --test=runtime_compiler_00 --jobs=2
```

CTest names: `scpp_test_compiler_storage`, `scpp_test_compiler_string_storage`.
The native module intentionally does not repair or reuse hash_t: numeric positions
need no hash lookup, and its current index-width/reserve gaps would widen this work.
String mode currently duplicates key storage and position metadata; performance
measurement and representation optimization remain separate follow-up work.
