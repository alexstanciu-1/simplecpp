# Runtime API and compiler boundary

Doc Status: supporting

This is a consolidated map of the implemented v0.2 runtime/generator surface.
The family contracts linked below own exact semantics. Compiler integration is not
implemented or implied by generated physical plans.

## Published types and names

The default publication emits int32, int64, bool_t, null_t, float64, string,
vector_int64 and vector_string. The integer registry supports all eight signed/
unsigned widths (8/16/32/64); independent publications exercise them all. Vector
specialization supports published integer, floating, boolean, null and string
values. Names and configured defaults come from metadata. int_t aliases the signed
integer default (int64); scpp::float_t aliases float64. float_t is intentionally
not imported globally by short_names.hpp because of the C math typedef collision.
Neither C++ keywords nor std container identities are redefined.

## Operators

| Family | Implemented capabilities, published on request |
| --- | --- |
| Integers | + - * / %, &, \|, ^, == != < <= > >=, unary + - ~, << >>, corresponding compound assignments, prefix/postfix ++/-- |
| Float64 | + - * /, == != < <= > >=, unary + -, arithmetic compound assignments, prefix/postfix ++/-- |
| Bool_t | !, ==, !=; explicit native-bool conversion for conditions |
| Null_t | ==, !=; no numeric or string coercion |
| String | +, +=, == != < <= > >=; explicit text conversion |
| Concrete vectors | ==, != when element equality is published; explicit requested elementwise casts |

Operators are the public execution surface; private algorithms are shared. There
are no duplicate numeric_add or named numeric cast helpers. Same-type integer
arithmetic preserves type. Requested mixed integer arithmetic promotes to the domain
maximum: signed to int64, unsigned to uint64, signed/unsigned below 64 bits to int64.
Signed/uint64 arithmetic rejects instead of silently converting to float. Integer
comparisons retain exact original signedness and do not use arithmetic promotion.
Wide integer/float arithmetic requires an explicit cast; narrower integer ranges
are eligible for requested mixed operators. No complete mixed matrix is emitted
by default. The compiler can explicitly normalize known operands before calling a
same-type operator.

Shifts check the original count against the declared left width. Checked compound
write-back precedes mutation. Signed arithmetic overflow is outside the defined
native domain, including minimum/-1 division; checked construction does not turn
arithmetic into universally checked arithmetic. Unsigned arithmetic is modular.
Division by zero throws, including floating signed zero. IEEE float comparisons
preserve NaN behavior. No overloaded &&/|| defeats short-circuiting: plain C++ uses
built-in contextual boolean conversion and its native bool result; source lowering
must materialize a runtime bool_t where a source boolean value is required.

## Construction and cast matrix

All admitted nonidentity conversions below use explicit constructors/static_cast.
Publication controls concrete availability; an unsupported conversion is absent,
not an implicit fallback. Copy/move within an identity remain ordinary value operations.

| Source | Target | Rule |
| --- | --- | --- |
| Runtime integer | Runtime integer | Check representability before conversion |
| Admitted native integer | Runtime integer | Explicit checked constructor; native ranges asserted for the generation target |
| Native float/double | Float64 | Explicit construction, binary64 representation |
| Runtime integer | Float64 | Explicit rounding allowed |
| Float64 | Runtime integer | Truncate toward zero; reject nonfinite/out-of-range result |
| Numeric | Bool_t | Explicit nonzero test, including native IEEE NaN behavior |
| Bool_t | Numeric | Explicit 0/1 conversion |
| Native bool | Bool_t | Explicit construction |
| Bool_t | Native bool | Explicit observation/condition conversion |
| const char* | String | Explicit checked non-null C-string construction |
| const char*, signed runtime count | String | Explicit byte copy; reject null/negative count; preserve embedded NUL |
| Runtime integer | String | Requested locale-independent decimal formatting |
| String | Runtime integer | Requested full-string decimal parsing and range check |
| Float64 | String | Requested shortest round-trip finite decimal formatting |
| String | Float64 | Requested full-string finite decimal parsing; reject overflow/zero underflow |
| Bool_t | String | Requested true/false words |
| String | Bool_t | Requested exact true/false parsing |
| Null | Numeric, bool or string | Rejected |
| Native float/double | Runtime integer | Rejected directly; explicitly construct Float64 first |
| Native number | String | Rejected; explicitly construct a runtime number first |
| Vector<A> identity | Same identity | Ordinary deep value copy/nonthrowing ownership move |
| Concrete vector<A> | Concrete vector<B> | Only an explicit casts_from request with a published explicit A-to-B conversion; partial failure discards the temporary |
| Vector | Scalar/string or implicit other vector | Rejected |

Integer/float native_value and string data are deliberate C++ observation interfaces,
not implicit conversions. There is no general native-to-runtime adaptation framework.
String parsing rejects whitespace, leading plus, partial text, hexadecimal spelling
and embedded-NUL truncation. Negative unsigned spellings reject. Float exponent
signs are accepted. String pointers must designate accessible native storage: the
runtime cannot validate arbitrary non-null pointer provenance or buffer extent.

Optional numeric native-int arithmetic lives in native_bridge.hpp. Optional vector
native-int index/value and C-string value conveniences are selected as class members
by publication. Other fundamental native types are fenced with deleted overloads;
user-defined conversions/enums remain subject to ordinary C++ rules and code discipline.
C++ cannot distinguish a literal from a same-typed variable. Constructors stay explicit:
use int64 x{12}, not int64 x = 12.

## Ownership and physical pre-lowering

Metadata owns identities, conversion policies, operation bindings and publication
demands. The native C++ generator validates and prepares them once, then emits
concrete declarations and implementation wiring. Private runtime algorithms own
value-dependent checks. Public include closures expose no templates or std storage.
Strings/vectors hide their storage behind pointers; materialized storage adds an
allocation/indirection. Vector at returns a copy and set owns mutation. String data
is borrowed read-only storage invalidated by owner mutation/move/destruction.

prepared.json records conversion and execution/result facts, reference/value forms,
source/runtime mappings and concrete collection adaptations. It supports physical
pre-lowering planning; it does not perform source symbol resolution, admission,
evaluation ordering or a sealed compiler-import protocol. The future symbol-aware
my-try-cpp integration owns those tasks, source literal typing, contextual explicit
normalization, language-flavor choices and any proof allowing a runtime check to be
elided. Plain C++ callers retain all published value checks.

## Scope and remaining decisions

Implemented scope: runtime, metadata, native API generator and physical plans for
the families above, including a runnable plain C++ example. Source compiler import,
end-to-end PHS/JSS lowering, external package/ABI sealing, cross-target generation,
carriers, arbitrary user element types, nested vectors and general iteration are
not implemented by this goal. Carrier defaults discussed earlier remain future
metadata/compiler work; no nullable/result extraction is claimed here.

Other open decisions are a future runtime error ABI, any stronger signed-overflow
policy, public collection iteration and specialized storage/performance work. The
hash/mixed/dynamic port review is recorded separately and preserves the custom hash
core and separate tagged families. It has not imported those implementations.

## Contracts and evidence

- [Integer contract](integer_slice.md), [bool/null and forms](values_slice.md),
  [floating contract](floating_slice.md), [string contract](string_slice.md),
  [vector contract](vector_slice.md)
- [Plain C++ example](../examples/runtime_demo.cpp), built as scpp_runtime_demo
- [Completion audit](planning/runtime_completion_audit.md)
- [Hash/mixed/dynamic port review](planning/hash_mixed_dynamic_port_review.md)
