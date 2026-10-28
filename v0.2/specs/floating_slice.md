# Float64 and explicit scalar conversions

Doc Status: normative

Scope: runtime and physical API preparation inside v0.2. Source eligibility,
sequencing and contextual decisions remain compiler-owned.

## Representation, names and ownership

The floating record in runtime/metadata/runtime.json binds float64 to native IEEE
binary64 double. Preparation and generated assertions require radix 2, 53 precision
bits and the binary64 exponent range. No native long-double representation, float32
runtime class or cross-target ABI is implied. The concrete value lives with bool_t
and null_t in generated scpp/values.hpp. Default construction yields positive zero;
copy/move remain trivial. There is no implicit native conversion.

The source float default maps to float64. scpp::float_t is an alias to that configured
published target, without another wrapper or operator set. Currently float64 is the
only supported floating target. Per user decision float_t is omitted from default
global short_names imports: C math headers expose their own global float_t. A local
`using scpp::float_t;` in a function/application namespace is supported. Publication
short_aliases explicitly controls global alias imports; int_t remains imported.

The same scalar conversion and operation plan serves integers and floats. Scalar
casts carry implementation, losslessness and can-fail facts separately: rounding
can be nonthrowing without being lossless. No second generator or policy table in
the compiler is introduced. Private algorithms are grouped in scalar_semantics.hpp;
generated implementations are grouped in src/runtime.cpp.

## Construction and casts

All conversions below are explicit constructors, also usable by static_cast.
The scalar_casts metadata selects the admitted cross-family rules. Conversion
signatures are generated only for published types.

| Source | Target | Behavior |
| --- | --- | --- |
| Runtime integer | Runtime integer | Existing checked representability contract |
| Runtime integer | float64 | Native rounding allowed; no failure |
| float64 | Runtime integer | Truncate toward zero; reject nonfinite/out-of-range truncated value before casting |
| Runtime integer/float64 | bool_t | Zero is false, nonzero true; both signed zeros are false, NaN/infinity are true |
| bool_t | Runtime integer/float64 | Exactly zero or one |
| Native float/double | float64 | Explicit, nonthrowing native conversion |
| Admitted native integer constructor inputs | float64 | Explicit; rounding allowed |
| null_t | Numeric/bool_t | Not admitted |

Native bool, characters, short/unsigned short, long double and extended integer
inputs remain rejected by the floating constructor surface. Direct native floating
input to an integer constructor remains rejected: first construct a runtime float.
Native observations use native_value(); numeric-to-native bool is not made implicit.

Floating-to-integer failure uses std::out_of_range("integer_cast_out_of_range").
Checks use exclusive powers-of-two upper bounds, not a rounded representation of
integer max. The truncated value is checked: for example -0.5 converts to unsigned
zero, whereas -1.0 fails. Explicit float conversion may lose information; converting
an int64 to float64 and back is not guaranteed to recover the original integer.

## Operators and mixed requests

Float64 supports requested + - * /, six comparisons, unary +/-, compound += -= *=
/= and prefix/postfix increment/decrement. Arithmetic results are float64; comparisons
return bool_t. No remainder, bitwise or shift operators are admitted for floats.
Integer behaviors remain under their own operation contracts.

Same-type floating arithmetic follows native floating behavior, including signed
zero, infinities, NaN propagation and overflow/underflow. As in the reviewed legacy
runtime, division by either signed zero throws std::domain_error("division_by_zero").
Other nonfinite results are not promoted to checked-arithmetic failures. Comparisons
use native IEEE behavior: NaN is unequal to everything, and ordered comparisons with
NaN are false. This contract assumes ordinary IEEE compiler options, not fast-math.

Mixed integer/float requests require the integer's entire type range to be exactly
representable in binary64. Thus int8/16/32 and uint8/16/32 are eligible; int64/uint64
require explicit float conversion. This also applies to comparisons and mutations:
source facts do not silently change a runtime overload's admission. No literal-value
inspection or floating fallback for incompatible integer pairs occurs.

Normalization requires an admitted explicit integer-to-float cast in the metadata.
A mixed mutation into an integer also requires the admitted checked float-to-integer
cast. Calculation precedes checked write-back, preserving the left value on failure.
Prefix returns the updated reference; postfix returns the old value.

The optional bridge requests native int arithmetic and compound assignment with
float64, using the same type-range rule and explicit conversions to core operations.
Native double arithmetic adapters and native comparisons are not enabled by default.
Deleted exact signatures prevent unrequested native inputs from narrowing to int.

## Reuse, validation and limits

Reviewed legacy sources are runtime/include/scpp/float_t.hpp, the scalar floating
operations in runtime/include/scpp/generated/operator_detail.hpp, and the numeric
conversion portions of runtime/include/scpp/cast.hpp. Only these responsibilities
were adapted. Carrier/dynamic dispatch, runtime error ABI and dependency closures
were not imported. Native std::trunc/isfinite/ldexp and C++ arithmetic provide the
implementation; generated public headers remain free of std headers and templates.

Proofs cover configured aliases with C math headers, forbidden implicit conversions,
all integer-width cast bounds, fractional/nonfinite input, explicit wide rounding,
permitted and rejected mixed requests, division failure, signed zero, NaN ordering,
mutation results and unchanged targets on failure. A separate full publication
compiles admitted mixed combinations; the default does not publish their Cartesian
product. String parsing/formatting, vectors, carriers and compiler integration are
not implemented by this slice.
