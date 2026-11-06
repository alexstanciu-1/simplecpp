# Named native numeric conversions

Doc Status: supporting
Date: 2026-11-06
Status: slices 1–3 implemented. Numeric/boolean names are native aliases; null is unchanged.

## Publication contract

Runtime metadata `checked_conversions` supplies a header and symbol prefix. The
default is `scpp/conversions.hpp`, with names `checked_<source>_to_<target>` using
canonical identities (including bool_t and uint8, not a duplicate byte identity).
The generator derives functions from existing published non-native numeric
conversion rows; it does not maintain a second permission or algorithm matrix.
Core publication has 12 numeric helpers plus three published string parsers.
All eight integer representations, float64 and bool_t yield 90 numeric helpers.
Published text parsing rows also produce helpers; formatting remains string construction.
No identity or null conversion helpers are added.
Removing a scalar cast policy removes its corresponding functions. Removing this
optional metadata block removes helper publication; consumers requiring helpers must supply it.

Arguments/results use explicit native aliases, for example:

```cpp
uint8 checked_int64_to_uint8(int64 value);
int64 checked_float64_to_int64(float64 value);
int64 checked_uint8_to_int64(uint8 value) noexcept;
bool_t checked_float64_to_bool_t(float64 value) noexcept;
int64 checked_string_to_int64(const string& value);
```

The header includes public scalar alias headers and forward-declares string. It
contains concrete declarations/deleted overloads, no templates or implementation.
Definitions in generated runtime.cpp reuse the private checked_integer_cast,
floating_integer_cast, numeric_cast, truth_cast and string parsing algorithms.
The prefix does not imply lossless numeric conversion: integer-to-float still
permits rounding, and numeric-to-bool still follows the established nonzero rule.
Range failures preserve std::out_of_range("integer_cast_out_of_range"). Destination
assignment occurs only after a successful return. Proven widening and other total
conversions retain their existing nonthrowing classification.

## Strict inputs and exact native identity

Generated overload fences reject other builtin scalar types, nullptr and ordinary
object pointers. The accepted parameter is the exact native source type, including
aliases of it. Distinct equal-width native types are not interchangeable: the type
probe now records `native_name` for each fixed integer. Private generated assertions
verify that identity. The generator never deduces overload identity solely from
width, signedness or size. Old target facts without this field must be re-probed.

This is the bounded native scalar boundary, not a generic prohibition on every
possible user-defined C++ conversion or an arbitrary callable/pointer type system.
An explicit conversion made by a handwritten C++ caller remains that caller's
responsibility. Compiler conversion contexts and source admissions are unchanged.

## Generator structures

- `php/checked_conversions.php` builds a temporary publication plan from the existing
  conversion rows. Representation records carry C++ spelling and native identity;
  function records carry canonical source/target, symbol, implementation, failure
  and lossless facts.
- `php/render_conversions.php` emits concrete declarations, strict scalar-input
  fences, native-identity assertions and calls to the existing algorithms.
- Target facts add `fixed_integers.<identity>.native_name`; no compiler AST or
  prepared-fact structures change. Slice 1 kept generated compiler artifacts
  byte-identical; slice 2 changes only checked conversion lowering and header selection. Metadata is consumed during generation, not source preparation.

## Historical slice 1 verification and timing

`tests/checked_conversions.cpp` checks native input admission, alias identity,
integer extrema, 16-bit bounds, unsigned/signed crossings, floating truncation,
NaN/infinity, signed zero, rounding above 2^53, boolean conversion and failed-write
atomicity. Results and exceptions are compared with still-published constructors.
`tools/api_generator/tests/checked_conversions.php` checks metadata binding changes,
permission removal, demand, malformed bindings and unchanged existing API headers.
The existing vector/carrier consumers continue to use the wrapped ABI; the earlier
vector-native prototype was saved separately, not incorporated into this slice.

Publication proof: 130 accepted/247 rejected runtime cases; 19 accepted/537 rejected
scalar cases, including the existing 2,692-operation matrix; 17 accepted/37 rejected
CLI cases plus publication lifecycle checks. Reviewed snapshots change only the new
header, generated definitions, prepared/manifest records and metadata fingerprints.
Existing API header bytes are unchanged. Public include closure and preprocessed
application checks remain template-free.

A small application including only the core conversion header was built directly
with Clang, libc++, O2/g0, mold, no PCH/LTO and the prebuilt runtime. Three normal
runs measured median compile-driver time 51.443 ms and link-driver time 66.981 ms.
A separate instrumented compile recorded frontend 5.499 ms and LLVM backend
5.428 ms. Driver times include process/tool overhead; the instrumented phase totals
are not expected to sum to the separate normal samples. This is a smoke measurement,
not a speedup claim. Core header size is 14,652 bytes, mostly explicit input fences.

ASan with the installed libc++ reports an allocation/deallocation mismatch during
exception destruction; a runtime-independent std::out_of_range throw/catch reproduces
it. ASan/UBSan validation passed using separately probed/generated libstdc++ output;
leak detection was disabled, and no libc++ ASan or leak-check success is claimed.

## Compiler integration (slice 2)

The compiler generator resolves runtime-checked conversions by canonical source and
destination. `scalarValueConversions` shares the existing runtime conversion recipes;
`compilerCheckedConversionBindings` combines those with integer conversion recipes
and the named helper planner. No duplicated function-name or permission matrix is
introduced. These temporary plans exist only during generation.

Generated lowering now calls:

```cpp
scpp::checked_int64_to_uint8(static_cast<__INT64_TYPE__>(expression))
scpp::checked_float64_to_int64(static_cast<double>(expression))
scpp::checked_float64_to_uint8(static_cast<double>(expression))
```

The argument cast restores the prepared source representation: an int64 expression
containing `LL` literals may be a distinct C++ `long long` intermediate even when the
target's exact int64 is `long`. It does not narrow to the destination unchecked.
Each expression is evaluated once, and a failed conversion leaves its destination
unchanged. Required includes now name only `scpp/conversions.hpp` for these conversions.

Retired compiler metadata: per-type `conversion_binding`, plus `boxed_int64`,
`boxed_uint8` and `boxed_float64`. Transient conversion rows carry one resolved
`binding` (symbol, header, source C++ representation), replacing source/target wrapper
bindings. `boxed_bool` remains for string comparison result extraction. There are
no AST, preparation-fact, watcher, queue or session structure changes. Preparation,
constant conversion, permission and diagnostic code is byte-identical to slice 1;
only the checked lowering/header methods differ in regenerated PHP.

Verification: eight focused CTests and compiler style checks passed. Native scalar,
floating, family-conversion and integer-compound tools passed 60 executable fixtures
across cpp/php/js, plus separate scalar/floating boundary drivers. Coverage includes
nonfinite/range failures, exact-input normalization of an `LL` intermediate, single
evaluation, failed-write preservation, incremental edits, repeated failure, deletion
and recovery compared with fresh emission. Generator tests check renamed helper
prefix/header propagation and reject missing or malformed named bindings.

Instrumented representative builds, ranges across the three flavors (milliseconds):

| Fixture | Native wall | Clang frontend | LLVM backend | Link command |
| --- | ---: | ---: | ---: | ---: |
| Scalars | 124.7–143.2 | 11.5–13.6 | 5.5–8.7 | 62–67 |
| Floating | 135.8–142.1 | 39.6–45.4 | 11.6–12.3 | 56–61 |
| Family conversions | 135.2–137.1 | 15.5–18.1 | 14.0–16.9 | 56–63 |
| Integer compounds | 136.9–144.0 | 16.6–17.2 | 20.8–22.5 | 55–59 |

O2/g0, libc++, Ninja8, mold, no PCH/LTO, fresh app objects and prebuilt runtime.
Frontend/backend totals sum translation-unit traces; native wall is the build wall
clock and link includes driver overhead. One sample per fixture/flavor, not a speedup
claim. Raw reports are retained under `/tmp/scpp-named-bindings-{scalars,floating,family,compounds}/`.

The shared runtime recipe refactor also passed publication parity: 130 accepted /
247 rejected runtime cases, 19 accepted / 537 rejected scalar cases (including the
2,692-operation native matrix), and 17 accepted / 37 rejected CLI cases plus lifecycle
checks. Runtime artifacts and scalar artifact sets are byte-identical. Reviewed
snapshot updates contain only metadata-dependent CLI input hashes and scalar case
keys after removing the obsolete compiler wrapper bindings.

## Slice 3 verification

The wrapper comparison oracle is replaced with independent native domain checks.
See [alias migration verification](native_scalar_aliases.md) for current results.
