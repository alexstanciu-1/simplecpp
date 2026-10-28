# Boolean, null and integer operation forms

Doc Status: normative

Scope: standalone runtime/generator contracts inside v0.2, not compiler source
admission. This extends the integer contract without importing the old runtime.

## Owner and representation

`runtime/metadata/runtime.json` consolidates integer policies and simple-value
records. The former integers.json is retired. Publication selects integer and
simple-value types separately and explicitly; source type mappings are metadata,
not C++ macros. Current mappings are int -> int64, bool -> bool_t, null -> null_t. The signed
integer default is configurable through source_types.int. Metadata aliases map
`int_t` to that source default, so C++ and compiler source mappings share one fact.
The alias is emitted only when its target is published. No duplicate overload or
wrapper type is created. `scpp::float_t` follows the floating default (currently float64), and stays out
of global short_names imports to coexist with C math headers.
Prepared plans expose mappings only for published targets. No claim of implemented
source compiler integration follows from these mappings.

The existing generator now has a runtime-wide prepare/emit model in
`tools/api_generator/runtime.hpp`, `runtime.cpp` and `emission.cpp`. One executable,
output inventory, freshness check and publication pipeline serve both families.
`scpp_runtime` is the CMake consumer target. No parallel integer-only driver remains.

`scpp/values.hpp` contains concrete boolean/null types and their selected operations.
It includes no private or standard-library headers and exposes no templates.
`short_names.hpp` selectively imports their names, including the null constant.
Neither native bool nor another C++ keyword is redefined.

## Boolean and null

Boolean defaults to false. `bool_t{true}` and `static_cast<bool_t>(true)` are
explicit construction; `static_cast<bool>(value)` and contextual conversion in an
if/while condition are explicit observations. There is no implicit conversion in
either direction. Common unsupported native arithmetic/object-pointer constructor
inputs have deleted overloads. This is not a universal ban on arbitrary native
user-defined/function-pointer adaptation; code discipline remains necessary.

Boolean `!`, `==`, `!=` return bool_t. Do not overload &&/||: built-in C++ condition
conversion preserves short-circuiting. Their native C++ expression result is bool;
a compiler lowering a source boolean value must explicitly construct bool_t when
that result crosses back into the runtime value API. Source evaluation order and
condition normalization are compiler responsibilities, not runtime dispatch.

Null is an empty, default-constructible value with one inline constexpr sentinel
`scpp::null`. Null/null equality returns true, inequality false, both as bool_t.
Null is distinct from false and integer zero. No null/numeric or null/boolean
conversion is introduced. Explicit bool/numeric casts are now provided by the
[scalar conversion contract](floating_slice.md); implicit truthiness is not added.

## Integer comparisons

Each of == != < <= > >= has its own requested signature and returns bool_t.
`exact_integer_comparison` compares original integer representations using private
standard-library signedness-aware comparisons. It does not force values through
an arithmetic common type. For example, int64{-1} is less than uint64{max}; no
floating conversion or unsigned reinterpretation is involved. Signed/uint64
arithmetic rejection therefore does not prohibit an explicitly requested comparison.

Native comparison adapters are not admitted in this slice; use explicit runtime
construction. Default comparison publication is same-type int32/int64. The compiler
may request exact mixed comparisons as needed. Tests compile all 64 ordered integer
pairs for each comparison. Both == and != are explicitly emitted; do not rely on
C++ rewritten equality candidates accepting a user-defined boolean return type.

## Unary, shift and mutation forms

Prepared operation facts distinguish binary, unary, compound, prefix and postfix.
Each form has validated arity, result category, promotion and implementation binding.
No emitter inference of source types or another promotion table is introduced.

- Unary +, -, ~ preserve the integer type. Unsigned negation is modular. Signed
  negation of the minimum remains outside the defined signed-arithmetic domain.
- << and >> preserve the declared left type. The right count retains its original
  representation; reject negative or count >= left width before shifting. Failure
  throws std::domain_error("shift_count_out_of_range"). Left shift uses C++23 bit
  semantics at the declared width; signed right shift is arithmetic, rounding down.
- += -= *= /= %= &= |= ^= calculate under the existing integer-domain policy, then
  explicitly checked-cast the result back to the left type before assignment. The
  left value is unchanged when calculation/conversion throws. Calculation itself
  retains the existing signed-overflow domain; checked write-back does not make
  signed arithmetic universally checked.
- <<= >>= retain the left representation and the same count checks. They do not
  normalize the count into the left type before checking.
- Prefix ++/-- update and return the left reference. Postfix returns its old value.
  The C++ postfix int dummy parameter is syntax, not a numeric interoperability API.
- Mutation accepts a non-const left lvalue. No reversal of compound requests through
  both_orders is allowed. All native-left mutation requests reject.

Requested native-int compound adapters live in the optional bridge and delegate to
core mutation with the original left reference and an explicit right conversion.
Their dependency must be published. Core-only consumers do not acquire native
arithmetic from including these type headers.

## Reviewed reuse and evidence

Reviewed legacy responsibilities: `runtime/include/scpp/bool_t.hpp` for explicit
condition conversion and representation; `runtime/public/scpp/null_t.hpp` for the
empty sentinel; `runtime/include/scpp/generated/operator_detail.hpp` for scalar
comparisons; `runtime/include/operators/arithmetic/integer_shift.hpp` for checking
counts against the declared left width. These are reference paths, never build
inputs. The wider sentinel/carrier registries and error ABI were not imported.

The C++23 standard-library signedness-aware comparison primitive replaces the
legacy same-signed-only comparison implementation; this is a bounded adaptation to
exact integer value comparison, not adoption of native mixed-sign usual conversions.
Shift failure uses the standalone standard exception boundary. Tests cover contextual
short-circuiting, no implicit arithmetic, explicit comparison results, every integer
width, extreme signed/unsigned comparison, checked shift counts, mutation return
categories and failure preservation. Floating support is covered by its separate
contract; strings/vectors and full compiler integration remain later work.
