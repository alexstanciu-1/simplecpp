# Owned string and sequence boundary slice

Doc Status: supporting

Status: string metadata, preparation, generated lifecycle/operators and explicit
text casts are implemented. Generated vector support is described in vector_slice.md.

For the PHP compiler source slice, see [string locals](../compiler/docs/strings.md).
Runtime text conversions described below are not enabled in that compiler slice.

## Reviewed reuse

Reviewed `runtime/include/scpp/string_t.hpp` and
`runtime/include/scpp/support/string_representation.hpp`: retain owned
`std::string` bytes, embedded NUL preservation, value copies and append behavior.
Keep standard storage private instead of exposing native containers publicly.
Do not import UTF-8, slicing, instrumentation or implicit null-pointer-to-empty
behavior. Checked pointer inputs reject null, including zero-length construction;
the default constructor will represent the empty value. Non-null pointer validity
and accessible buffer extent remain the native caller's responsibility.

Reviewed the bounds/append portions of `runtime/include/scpp/vector_t.hpp`:
retain checking signed indices before conversion to native size and use standard
owned vector storage. Do not import iterator snapshots, wrappers, reference returns
or the legacy error subsystem. The agreed public contract is at-by-value and set.
The private `sequence_index` helper checks both bounds without signedness loss.

These old-tree files are reviewed references, never build dependencies. The new
private algorithms are in `runtime/src/sequence/sequence_semantics.hpp`.

## Explicit text conversions

The user approved simpler text defaults in place of legacy PHP coercion. Reviewed
the strict parsers and scalar string conversions in `runtime/include/scpp/cast.hpp`.
The generated surface publishes explicit constructors/static_cast only:

- Integer/text: base-10 integer formatting and full-string parsing, with target
  range checks. Unsigned targets reject negative spellings, including `-0`.
- Float/text: locale-independent finite decimal formatting and full-string parsing.
  Formatting uses shortest round-trip `to_chars`; parsing accepts decimal fractions
  and exponents. Preserve negative zero and representable subnormal values.
  Reject overflow, underflow to zero, NaN and infinity.
- Bool/text: exactly `true` and `false` in both directions.
- Null/text: no conversion. No empty-string coercion or generic string truthiness.

Parsing does not trim whitespace, accept a leading plus, allow trailing bytes,
accept hexadecimal prefixes, or treat embedded NUL as end-of-input. This deliberately
replaces the legacy floating parser's leading-plus exception and bool parser's
empty/0/1 spellings. Exponent signs remain valid. Syntax failures throw
`std::invalid_argument`; numeric range failures throw `std::out_of_range`.

libc++ 18 on the configured toolchain lacks floating `from_chars`. A private
capability check uses it where available; otherwise a classic-locale standard
stream parser follows the same validated decimal grammar. libc++ marks representable
subnormals with failbit, so the fallback accepts that specific nonzero result and
still rejects zero underflow/overflow. No external parser library is introduced.

## Evidence and remaining work

`sequence_semantics` proves pointer/count failures, empty/binary byte strings,
signed/unsigned index extremes, integer boundaries, full-input parsing, locale
independence, floating range/round trips and strict boolean text. Standard storage
copy and self-append behavior are checked as inputs to the public ownership design.
The initial private primitive checkpoint passed fourteen release tests and its
focused UBSan proof; public consumer checks now cover generated behavior as well.

## Metadata and generated public API

The `string` record in runtime.json owns the byte-storage binding, signed count
identity (currently int64), explicit pointer construction, null rejection,
operator capabilities and text-cast policies. The optional publication `string`
record requests a header, operator names and scalar cast peers. Each requested
peer publishes both formatting and parsing; omitted peers publish neither.
Preparation resolves the native count representation from target facts and checks
published boolean dependencies, permitted cast families,
header conflicts and duplicate/unknown requests. Absence of the publication record
emits no string class or text conversions.

The prepared artifact records operator symbols, execution/result identities,
implementation bindings, result categories, text conversion algorithms/failure
facts, constructor arguments and member receiver/borrow facts. These are physical
API plans, not source type checking, evaluation sequencing or compiler integration.
The compiler still owns those decisions.

`scpp/string.hpp` exposes the concrete `scpp::string`, also optionally imported by
short_names.hpp. It provides explicit `const char*` and `(const char*, __INT64_TYPE__)`
constructors for the default int64 count identity, copy/move/assignment/destruction,
size, empty, data, append and clear. `size()` returns that native signed count.
The prepared `count_representation` is derived from the metadata count identity and
validated target registry; its physical signatures use that representation. Counts
do not require publishing the int64 wrapper or its native constructors. There is
no compatibility overload accepting wrapped counts; native callers must migrate.
Negative counts and null pointers (including null with zero count) still throw;
size conversion remains checked before returning.
Requested operators are +, +=, ==, !=, <, <=, > and >=. Comparisons return bool_t;
+= returns the left reference. There is no implicit string truthiness, numeric
coercion, native numeric construction or automatic native-pointer concatenation.
Use `a + string{"Ada"}`. C-string construction consumes up to the first NUL;
pointer/count construction preserves every supplied byte. Size measures bytes.

The public object occupies 24 bytes. Up to 22 payload bytes live inline, with a
separate byte length and trailing NUL. Longer strings own private std::string
storage. Default and moved-from values are inline and empty. Native consumers
can inspect the limit via `string::inline_capacity` and the current form via
`is_inline()`. Neither form adds standard-library includes to the public API.
Copies own independent bytes; moves leave an empty, reusable source. Copy assignment
constructs the replacement before changing the target. Self-copy, self-move and
self-append are supported. Clear releases the stored value. `data()` is a native
read-only byte view, valid only until mutation, move or destruction of its owner;
callers must not retain it across those operations. No mutable native storage or
standard-library references are exposed.

The generated implementation is grouped in src/runtime.cpp with scalar definitions;
integer/value headers forward-declare string for explicit text constructors.
No public header includes private runtime helpers or standard template declarations.
Private text algorithms remain handwritten; public signatures, wiring and lifecycle
are generated from the prepared family contract.

`string_consumer` exercises binary/long strings, copy/move/self operations,
concatenation/ordering, checked casts, null/count failures and composed native numeric
bridge lookup. `string_preparation` checks dependency rejection, selective casts and
deterministic ordering. `string_publication` compiles/runs a reduced API with only
string equality, no text casts, no float and no numeric operators. Public-header
closure checks include string.hpp.

Generated vectors and cross-family composition are covered by vector_slice.md and
runtime_api_summary.md; the evidence above describes the string-specific checkpoint.

Cleanup: the generated scalars.cpp name is retired in favor of runtime.cpp now that
it owns scalar and string definitions. Inventory/build/fixture references follow the
new artifact. No historical file needs archival.

Validation: all seventeen tests pass in the release preset and in a fresh standalone
Clang/libc++ UBSan build generated from no derived files. Ninja's dependency records
contain no original-repository includes. The full publication additionally formats
and parses extrema for all eight integer types. The generated string consumer passes
ASan+UBSan using Clang/libstdc++, which exercises the floating from_chars path as well
as the default libc++ fallback. Leak detection is disabled for that environment's
known LSan limitation; no clean LSan run is claimed.

## Byte positions

String length and indexed access count encoded bytes, including embedded NUL. For example, the UTF-8 encoding of `é中😀` has length 9. Indexing outside the stored bytes throws. Compiler offsets and source spans likewise count bytes. See the [source string bindings](../compiler/docs/strings.md#metadata-declared-byte-functions).

## Explicit byte helpers

The general API publishes the following operations independently. C++ flavor
can use selected general names by default. Other flavors require general_api.
All positions and lengths here count bytes, including embedded NUL.

| Operation | Result and bounds |
| --- | --- |
| string_byte_len(s) | int. Lowered as the size of the string. |
| string_byte_at(s, i) | int in 0…255, or −1 outside [0,size). |
| string_byte_slice(s, i, n) | Owned string. Negative bounds or a start past the end produce empty. An overrun truncates. |
| string_byte_slice_equals(s, i, n, literal) | bool. Match exactly n bytes in a fully contained window, including the empty window at the end. |
| string_byte_find(s, needle[, i]) | result_or_false<int>. Start defaults to zero and must be in [0,size], otherwise ValueError. Empty needle can match at the end. A hit at zero is present. |

PHP implode(separator, parts) also accepts a published hash with string values
and either integer or string keys. It joins the values in iteration order and
preserves empty values and embedded NUL bytes.

## Explicit UTF-8 helpers

The general helper `string_utf8_is_valid(string): bool` tests whether the entire
byte string encodes Unicode scalar values in UTF-8. Empty input and embedded NUL
are valid. Truncated and overlong sequences, stray continuation bytes, surrogate
encodings and code points above U+10FFFF return false.

string_utf8_codepoint_count(s) returns the number of Unicode scalar values.
string_utf8_codepoint_at(s,i) returns a scalar as int, or −1 outside the input.
string_utf8_slice_codepoints(s,start,length) returns an owning substring.
Negative bounds give an empty slice and lengths past the end truncate.
All three validate the complete input. A malformed sequence anywhere switches
the whole operation to unsigned byte units. U+0000 counts as one scalar and
decomposed combining marks count individually. No normalization is performed.

## Text builders

The general API publishes two owning value types. Prefix each operation below
with its type name and an underscore, for example `text_builder_take_string`.

| Type | Operations |
| --- | --- |
| `string_parts_builder` | create, reserve, capacity, count, byte_len, append_string, append_int, append_bool, to_string, clear |
| `text_builder` | create, reserve_bytes, capacity_bytes, byte_len, append_string, append_int, append_bool, to_string, take_string, clear |

Construction produces an empty builder. Assignment and by-value parameters copy
the contents independently. Mutating helpers take a writable reference to an
exact-type variable. Queries borrow their operand for the call. A PHP-flavor
synchronous free function may declare a readonly builder parameter with
`const text_builder &$value` or `const string_parts_builder &$value`.

String append preserves the exact bytes. Integer append writes the signed
decimal representation. Boolean append writes ASCII 1 for true and an empty
part for false. The parts builder counts every append, including empty parts.
Reserve requests a minimum capacity in parts or bytes according to the type.
A negative request raises ValueError. Clear empties the builder. A snapshot
owns its bytes independently, and take additionally empties the text builder
for reuse. Capacity after take is unspecified.

## PHP scalar byte helpers

`ord(s)` reads the first unsigned byte and yields zero for an empty string.
`chr(n)` writes the low eight bits of the integer as a single byte, including
NUL. These use the PHP 8.5 value results, without emitting PHP deprecation
notices. `strrev(s)` reverses the bytes. `str_rot13(s)` rotates ASCII letters
and preserves every other byte. `chop(s[,mask])` shares both published PHP
rtrim forms, including the explicit byte-mask behavior.

PHP `strstr` (alias `strchr`) and `stristr` return the suffix beginning at the
first substring match. `stristr` folds ASCII for comparison and preserves the
subject's original bytes in its result. `strrchr` searches backward for the
first byte of the needle, using a NUL byte for an empty needle. All three
operations accept the optional bool `before_needle`, which returns the prefix
up to the match. A successful empty prefix or suffix is a present string.
`strpbrk` returns the suffix at the first byte in a supplied set and raises
`ValueError` for an empty set. All five names return `result_or_false<string>`
and preserve embedded NULs.
