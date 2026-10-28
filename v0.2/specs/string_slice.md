# Owned string and sequence boundary slice

Doc Status: supporting

Status: string metadata, preparation, generated lifecycle/operators and explicit
text casts are implemented. Generated vector support is described in vector_slice.md.

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
Preparation checks published count/boolean dependencies, permitted cast families,
header conflicts and duplicate/unknown requests. Absence of the publication record
emits no string class or text conversions.

The prepared artifact records operator symbols, execution/result identities,
implementation bindings, result categories, text conversion algorithms/failure
facts, constructor arguments and member receiver/borrow facts. These are physical
API plans, not source type checking, evaluation sequencing or compiler integration.
The compiler still owns those decisions.

`scpp/string.hpp` exposes the concrete `scpp::string`, also optionally imported by
short_names.hpp. It provides explicit `const char*` and `(const char*, int64)`
constructors, copy/move/assignment/destruction, size, empty, data, append and clear.
Requested operators are +, +=, ==, !=, <, <=, > and >=. Comparisons return bool_t;
+= returns the left reference. There is no implicit string truthiness, numeric
coercion, native numeric construction or automatic native-pointer concatenation.
Use `a + string{"Ada"}`. C-string construction consumes up to the first NUL;
pointer/count construction preserves every supplied byte. Size measures bytes.

Storage is an incomplete private pointer to a generated owner containing
std::string. This avoids exposing templates and depending on standard-library
layout/ABI sizes. Its cost is an extra allocation/indirection for materialized
strings, including short strings; this slice makes no performance-equivalence
claim to an inline std::string. Default and moved-from values need no allocation.
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
