# Shape-preserving JSON documents
Doc Status: normative
Status: experimental
Compatibility: narrow schema reader, separate from `json_decode`.

## Contract and intake

Kept: complete-document checked parsing, decoded Unicode strings without
normalization, ordered arrays, and duplicate object keys with last-value-wins
lookup while retaining first-insertion order. Modified: immutable typed document
and node handles replace PHP objects/arrays; explicit checked access and integer
conversion replace PHP coercion. Dropped: PHP flags, named-argument compatibility,
stdClass, source whitespace/escape roundtripping, mutation and serialization.
Existing `json_decode`/`json_encode` contracts are unchanged.

The JSON module owns the storage, grammar and accessors. Shared private lexical
reading lives in `runtime/include/modules/json/detail/reader.hpp`; the existing
decoder retains its value mapping. Document storage/access lives in `document.cpp`
and compiles once with `scpp_json` and the CLI's JSON runtime composition. Strict
registration, normalized contracts and runtime class stubs expose this family to
PHS/STAN; no generator type inference is added. No third-party dependency or OS
API is required. Tests and reported host evidence determine validated platforms.

## Strict surface

All access errors (null handle, wrong kind, missing key, invalid index/conversion)
return the error branch of `result`; no coercion or partial value is published.
Use `take(...)`. Public aliases are `json_document`, `json_node`, `json_parse_error`.

| Function | Return / meaning |
| --- | --- |
| `json_document_parse(string text, json_parse_error &diagnostic, int max_depth = 128)` | `result<json_document>` |
| `json_document_root(json_document document)` | `result<json_node>` |
| `json_node_kind(json_node node)` | `result<string>`: `null`, `boolean`, `number`, `string`, `array`, `object` |
| `json_node_size(json_node node)` | `result<int>`: array length or distinct object member count |
| `json_node_at(json_node node, int index)` | `result<json_node>`: array element in source order |
| `json_node_key(json_node node, int index)` | `result<string>`: object key in first-insertion order |
| `json_node_has(json_node node, string key)` | `result<bool>`: exact object member presence |
| `json_node_member(json_node node, string key)` | `result<json_node>`: exact object member value |
| `json_node_string(json_node node)` | `result<string>`: decoded string bytes |
| `json_node_boolean(json_node node)` | `result<bool>`: boolean value |
| `json_node_number(json_node node)` | `result<string>`: original JSON number token |
| `json_node_int(json_node node)` | `result<int>`: checked signed 64-bit integer |

Integer conversion accepts only lexical integers: optional minus and JSON integer
digits, in [-9223372036854775808, 9223372036854775807]. It rejects `1.0`, `1e0`,
fractional values and out-of-range integers without rounding. `-0` converts to 0.
Parsing retains every grammar-valid number, even outside native numeric range;
callers can inspect the spelling. Floating conversion is not part of this slice.

Object keys retain exact decoded UTF-8 bytes, including numeric-looking keys,
embedded NUL and escaped spellings. Equality is byte equality after JSON escape
decoding, without Unicode normalization. Duplicate decoded keys replace the value
at the original key position. Missing keys differ from present null; `has` reports
presence, `member` fails only for absence/wrong kind/invalid handle. Empty arrays
and objects retain different kinds. All scalar roots, including null/false, parse
successfully and have present root handles.

## Failures and limits

Parse returns `result<json_document>` and sets the diagnostic output to null on
success. On parse failure it supplies a separate `json_parse_error` record with
public `category` (string), `byte_offset` (int) and `message` (string), and returns
an ordinary error with a readable message. The output record is diagnostic data;
changing it cannot affect the parser or any document. There is no partial document.
`take` retains its normal output-on-failure semantics: clear your prior document
slot explicitly if you do not want to retain a previous successful document.

Categories: `syntax`, `utf8`, `unicode_escape`, `depth`, `invalid_limit`.
Offsets are zero-based byte positions in the supplied text; end-of-input errors
may point one past the last byte. `invalid_limit` uses offset 0. UTF-8 validation
runs on the complete input first, before grammar parsing. Invalid UTF-8 reports
the leading byte of the first invalid sequence, including overlong encodings,
encoded surrogates and codepoints above U+10FFFF. Escape/syntax errors point to
the detected offending byte (or the relevant token start for an unterminated
string). These offsets/categories are native contracts, not promises to match
PHP diagnostic precedence or positions. Malformed `\\u` escapes and invalid
surrogate pairs have category `unicode_escape`.

`max_depth` must be 1..256 (default 128). Depth counts simultaneously open array
and object containers; scalar roots have depth 0. An empty container counts as 1.
The first opening bracket/brace beyond the limit fails with `depth`. Whitespace
is JSON's ASCII space, tab, CR and LF; a BOM is not accepted. Failed parsing does
not poison later calls. Allocation failures retain ordinary exception behavior;
RAII releases partial storage and the diagnostic output is reset before parsing.

## Lifetime and resource model

Documents and nodes are created only by parse/navigation operations, not public
constructors. They expose no mutators or raw owning pointers. A node owns a
shared immutable backing arena and remains valid after the document handle is
released or replaced. Handles from different documents never share node IDs.
Input text can be released after parsing. Copies share immutable storage;
concurrent reads are safe with ordinary caller-side handle lifetime discipline.
The arena stores nodes flat, so destruction does not recurse through the tree.
Duplicate values may leave unreachable nodes in the private arena until release;
space is bounded by input-derived storage, not just distinct keys. There is no
input-size/node-count quota in this slice; host allocation limits apply.

Manifest schema decisions remain caller-owned: require an object root, an array
`source_folders`, nonempty length and string elements before constructing typed
records. Object values such as `{"0":"src"}` and `{}` are not lists.
