# Byte strings, Unicode strings and builders

Doc Status: planning

Audit at `c82cde305`. Compared release `d2f63e37e`, my-try `624ab8a2d`,
the generated default v0.2 registry and the byte pilot `7936b9bec`. The unit of
coverage below is a source name with its overloads, result and receiver. An
existing PHP binding does not by itself establish a general name.

## Coverage and goal boundary

The my-try strings catalog has 73 signatures, 54 names, two value types and three
padding constants. The default v0.2 string request selects 35 function records.
Twenty-two names overlap. The remaining 32 donor names divide exactly into five
byte helpers, three code-point helpers, two grapheme helpers, twenty builder
helpers and two stable hashes. The current request also has 13 names outside this
donor catalog, including strlen (my-try core), string_byte_from_int, str_repeat,
substr_count and case-insensitive searches. Keep their current contracts.

| Donor surface already represented | v0.2 disposition |
| --- | --- |
| str_starts_with, str_ends_with, str_contains, strpos, strrpos | Keep current byte positions, flavor-specific miss results and offset policy. |
| substr, substr_compare, substr_replace, str_replace, str_pad | Keep current byte operations, supported arities, mutable replacement count and padding constants. Historical text-oriented slicing is not authority for changing units. |
| strtolower, strtoupper, ucfirst, lcfirst, trim, ltrim, rtrim | Keep ASCII case and current byte-mask behavior. Unicode case mapping is separate. |
| bin2hex, hex2bin, number_format | Keep binary transport, false-aware hex decode and current numeric overloads (1/2/4 arguments). |
| explode, implode | Keep vector operations. My-try additionally admits implode(separator, hash<string,K>); a scratch caller with sparse integer keys was rejected with `implode requires vector<string>`. Schedule this overload with the byte checkpoint, using values in insertion order for integer and string keys. Do not silently credit it as complete. |

Recommend one implementation goal with three checkpoint commits. Include the
five general byte names, the typed-hash join gap, three explicit code-point
names and both complete builder surfaces. Decide the two grapheme names before
adding them to that goal. Keep stable hashing as its own versioned family with
the source/span provider. General helper names use the established project guard.
Flavor-native redesign and optimization remain later work.
[Donor signatures](../../../v0.1/specs/planning/runtime_surface_audit/catalog_strings_1.tsv) · [String publication](../../../runtime/metadata/publication.json)

## Byte checkpoint

| General signature (string inputs borrowed) | Distinguishing contract and lowering |
| --- | --- |
| string_byte_len(s) → int | Read the size directly as for strlen. Count embedded NUL. |
| string_byte_at(s, index) → int | Return 0…255, or −1 for negative or past-end indices. The indexed expression has a throwing boundary. Reuse the checked storage access only after the range decision. |
| string_byte_find(s, needle[, offset]) → result_or_false<int> | Both public donor overloads preserve false on miss. Propose retaining that contract across flavors for this general name. Native search can return a signed index and adapt once. Zero is a hit. Require an explicit offset in [0,size], including an empty-needle match at the end. PHP strpos admits negative offsets and cannot be used verbatim. |
| string_byte_slice(s, offset, length) → string | Negative offset or length, or offset past the end, yields empty. A positive overrun truncates. Return owning bytes, even for a partial UTF-8 sequence. PHP substr has different negative bounds. |
| string_byte_slice_equals(s, offset, length, literal) → bool | Require a valid whole window and exact literal length. Empty at end matches; overrun is false. Compare borrowed bytes without materializing a substring. Check remaining length before adding. |

The byte-at and slice-equals pilot proved two source calls on a separate branch.
The current general visibility guard supersedes its C++-only registration.
A fresh PHP-flavor executable for this audit used existing v0.2 helpers on
`"é\0x"`. strlen returned 4, bin2hex(substr(s,1,2)) returned a900 and strpos
located x at 3. The compiler_strings, compiler_strings_native and
compiler_string_scanner_native checks also passed. These results establish the
current byte boundary; the five general names still require publication.
[Active byte positions](../../string_slice.md#byte-positions) · [My-try helper bodies](../../../v0.1/runtime/include/modules/strings/strings.hpp)

## Unicode checkpoint

Propose explicit string_utf8_codepoint_count(s) → int,
string_utf8_codepoint_at(s,index) → int and
string_utf8_slice_codepoints(s,start,length) → string. The donor's public at
returns −1 outside the range and zero for U+0000. Slice returns empty on negative
bounds and truncates at the end. Use bounded offset arithmetic. Keep the decoder
private, with no Unicode or third-party includes in the caller.

**Malformed input needs an explicit choice.** Both pinned native helpers use the
same decoder file. If *any* byte is invalid, they interpret the entire input as
unsigned bytes for all three operations. The separate portability framework
requires valid UTF-8 and rejects malformed input. The active runtime string
preserves arbitrary bytes. For donor-compatible Stage 1, recommend the whole-input
fallback for these explicit donor names, documented and tested even when the
malformed byte follows the requested window. Validating source file text is a
different boundary. Existing donor UTF8-CPP is vendored in v0.1; choosing a
private decoder does not require linking ICU for code points.

A native scratch consumer compared the actual donor header with the installed
ICU 74.2 character break iterator on the same UTF-8 bytes:

| Input | Bytes | Donor code points | Donor graphemes | ICU clusters |
| --- | ---: | ---: | ---: | ---: |
| e followed by U+0301 | 3 | 2 | 1 | 1 |
| Romanian flag (two regional indicators) | 8 | 2 | 2 | 1 |
| CR LF | 2 | 2 | 1 | 1 |
| a, U+200D, b | 5 | 3 | 1 | 2 |
| é followed by byte FF | 3 | 3 | 3 | outside valid-input comparison |

Count and slice must agree about CR LF. The donor joins selected combining
ranges, selectors, emoji modifiers and ZWJ sequences; it has no complete
segmentation tables. Recommend reserving string_grapheme_count and
string_grapheme_slice for extended grapheme clusters. Defer these two until an
optional versioned provider, matching conformance tests and malformed-input
policy are agreed. The local ICU probe demonstrates feasibility on this host.
It does not establish availability on all build targets. No normalization is
needed for byte or code-point operations.
[Donor decoder](../../../v0.1/runtime/include/scpp/support/utf8.hpp) · [Portability contract](../../../v0.1/specs/portability/utf8_text_contract.md) · [Unicode segmentation rules GB3, GB11–13](https://www.unicode.org/reports/tr29/#Grapheme_Cluster_Boundary_Rules)

## Builder checkpoint

| Value type | General operations (prefix each with the type name and an underscore) |
| --- | --- |
| string_parts_builder | create, reserve, capacity, count, byte_len, append_string, append_int, append_bool, to_string, clear |
| text_builder | create, reserve_bytes, capacity_bytes, byte_len, append_string, append_int, append_bool, to_string, take_string, clear |

Adopt two ordinary owning runtime values. Copies evolve independently. Use
a writable reference for mutation and take, and a nonretaining readonly borrow
for queries. A snapshot owns its bytes; take returns owned text and leaves an
empty reusable builder. Reserve is a lower bound measured in parts or bytes,
respectively. Negative reserve fails. A false append contributes an empty part
to the parts builder, so count increases and byte_len stays unchanged. True
writes ASCII 1. Integer formatting must handle INT64_MIN. An append must commit
byte_len after successful storage insertion; the donor updates its counter first.
Capacity after take and zero-copy conversion to the 24-byte v0.2 string need
no Stage 1 promise. The source module's text_builder_append_byte_span depends on
a byte-span owner and belongs with source-family intake.

**What actually ran.** Compiled the donor's two native classes directly. A text
builder containing a NUL survived independent copy, snapshot, take and reuse.
Appending false to an empty parts builder produced one part and zero bytes.
Separately, a v0.2 source stand-in using an ordinary named string local and
writable-reference functions preserved the old bytes across copy and snapshot,
returned the modified bytes on take, then accepted a new append. That stand-in
has no capacity API. Trying an application inline struct as the writable builder
parameter failed during signature preparation. A source-declared readonly string
reference and a field passed as a mutable free-call operand were also rejected.

Implement dedicated runtime value definitions and generated bindings with narrow
type/operation headers. The existing runtime-call reference masks already admit
an exact named writable slot and reject mutation of a temporary. Keep the STL
representation private. A public shared class would change ordinary assignment.
Prove builder-by-value calls, writable aliases and parameters, temporary readonly
queries, and rejection of a readonly mutation. Source-declared const-reference
parameters currently admit only vectors/hashes; if supporting the donor's
user-defined readonly builder functions, extend admission narrowly to the two
new types. No general relaxation for application structs is needed. Link only
the selected providers and run incremental declaration and manifest recovery.
[Builder contract](../../../v0.1/specs/compiler_support_runtime_contract.md#v02-metadata-backed-builder-calls) · [Donor caller matrix](../../../v0.1/my-try/tests/runtime_builders.php) · [Current parameter admission](../../../compiler/04_analyze/prepare/functions.php)

## Adjacent hashing family

Release d2f63e37e derives hash_string and stable_hash_string_u64 from the runtime
string-key hash, which mixes std::hash<string>. My-try explicitly switched to
64-bit FNV-1a over the exact bytes, with fixed 16-digit lowercase hexadecimal
and uint64 forms. The hash of empty input is cbf29ce484222325. The matching
hash_bytes and stable_hash_bytes_u64 forms take a source byte span. A future
hash goal must choose the persisted algorithm/version and prove cross-form
agreement. Existing uint64 transport solves the return ABI only.
[Versioned hash contract](../../../v0.1/specs/compiler_support_runtime_contract.md#stable-hash)
