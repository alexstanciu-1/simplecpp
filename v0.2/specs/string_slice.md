# Owned string and sequence boundary slice

Doc Status: supporting

Status: private primitives implemented and tested. Public string/vector metadata,
preparation and generated lifecycle/API are still pending. This checkpoint does
not claim a usable public string or vector implementation.

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
The intended generated surface publishes explicit constructors/static_cast only:

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
These are private primitive proofs, not generated lifecycle or overload proofs.
Validation: all fourteen release tests pass; the new sequence boundary proof also
passes with UBSan and recovery disabled under the configured Clang/libc++ preset.

Next: metadata selects text conversion publication; preparation records concrete
conversion/operation dependencies; generation emits the template-free string
declaration and hidden storage lifecycle. Then generate vectors and test composed
public overloads, exception invariants and actual ownership transitions.

Cleanup: no retired files or archive entries in this bounded primitive slice.
