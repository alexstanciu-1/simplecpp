# Named native numeric conversions

Doc Status: supporting
Date: 2026-11-06
Status: alias-migration slice 1 implemented; scalar wrappers and null remain unchanged.

## Publication contract

Runtime metadata `checked_conversions` supplies a header and symbol prefix. The
default is `scpp/conversions.hpp`, with names `checked_<source>_to_<target>` using
canonical identities (including bool_t and uint8, not a duplicate byte identity).
The generator derives functions from existing published non-native numeric
conversion rows; it does not maintain a second permission or algorithm matrix.
Core publication has 12 functions. Publishing all eight integer representations,
float64 and bool_t yields 90. No identity, null or text conversion helpers are added.
Removing a scalar cast policy removes its corresponding functions. Removing this
optional metadata block removes the helper publication; wrapper APIs remain usable.

Arguments/results use native representations while wrapper names remain occupied.
For example:

```cpp
__UINT8_TYPE__ checked_int64_to_uint8(__INT64_TYPE__ value);
__INT64_TYPE__ checked_float64_to_int64(double value);
__INT64_TYPE__ checked_uint8_to_int64(__UINT8_TYPE__ value) noexcept;
bool checked_float64_to_bool_t(double value) noexcept;
```

The later alias cutover can retain explicit scpp type spellings without changing
these underlying native signatures. No parallel scpp::native hierarchy is added.

The header contains concrete declarations and deleted overloads only: no includes,
templates or implementation. Definitions are compiled into generated `runtime.cpp`
and invoke the same private `checked_integer_cast`, `floating_integer_cast`,
`numeric_cast` and `truth_cast` algorithms as existing wrapper constructors.
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
  prepared-fact structures change. Existing generated compiler artifacts remain
  byte-identical. Metadata is consumed during generation, not source preparation.

## Verification and timing

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
