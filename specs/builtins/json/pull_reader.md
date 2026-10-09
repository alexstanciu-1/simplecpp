# Typed JSON pull reading
Doc Status: normative
Status: experimental tooling API

The JSON module provides a forward-only reader for schema consumers that construct
typed records directly, without a `mixed`, `dynamic` or immutable document tree.
This supplements, not replaces, `json_decode` and the [document API](document.md).
Module catalog schemas and compiler symbol registration are separate consumers;
they do not belong in JSON syntax processing.

## Protocol

The portable PHP/PHS facade is `scpp\Json_Reader(text, file, limit)`. It owns its
input, is mutable and single-consumer, and must not be concurrently advanced.
Copies of its ordinary class handle share cursor state, not an independent parse.
`file` is diagnostic attribution only; it performs no file IO. `limit` is 1..256.

The root starts selected. `kind()` returns `object`, `array`, `string`, `number`,
`boolean` or `null`, without consuming or fully validating the selected value.

- `begin_object()` / `begin_array()` enter the selected container.
- `next_property()` / `next_element()` select its next value; false consumes the
  closing delimiter and leaves that container. Call the matching operation only.
- `property_name()` returns the selected object's decoded key, before its value
  is consumed. Order and exact decoded key bytes are preserved.
- `read_string()`, `read_boolean()` and `read_null()` consume exactly that kind.
- `read_number()` returns the original JSON number spelling, without floating
  conversion or loss of precision.
- `read_integer()` requires a lexical signed 64-bit integer, as in `json_node_int`.
- `reject(message)` terminates reading with a consumer-owned schema error.
- `finish_document()` requires a completely consumed root and no trailing data
  except JSON whitespace. Repeating successful finish is harmless.

Each selected value must be consumed before advancing its container. Entering a
nested container requires fully leaving it before advancing its parent. Reading
twice, skipping an unconsumed value, accessing an unselected property or finishing
early is a protocol error. No implicit skip or coercion is provided in this slice.

Consumers own required/unknown-field rules, semantic validation and typed record
construction. They must keep partial records private, complete schema checks, and
successfully finish the document before publishing records or mutating a live
registry. The reader cannot roll back arbitrary consumer side effects.

## Syntax, diagnostics and resources

Grammar, Unicode, unique decoded object keys, depth and numeric rules match the
document contract. Duplicate names fail before consuming their second value.
UTF-8 validation covers the full source before reading starts. Empty objects and
arrays remain distinct; null/false are ordinary successful scalar values.

Syntax, kind, integer, schema and protocol failures terminate the cursor. It cannot
resume after a failure or be restarted with new input. Portable callers receive
`RuntimeException` (the existing native portability exception mapping), with file,
zero-based byte offset and bracket/index path where available. Host PHP uses its
scalar JSON string decoder and may report different malformed-string/UTF-8 offsets
and wording; accept/reject semantics and terminal failure behavior agree.

Native `scpp::json::pull_reader` shares the existing lexical scanner and exposes
`read_error` with category, position, path and detail. The language-boundary
`scpp::json_pull_reader` returns `result<T>`; non-value operations return successful
`result<bool>` containing true. Its one-shot `start(text,file,limit)` initializes a
default-created handle. Only JSON/protocol failures are translated, not allocation
failures. The portability facade maps Result errors to its exception family.

The reader owns source bytes, active container frames and duplicate-key sets for
open objects; it does not retain values already consumed. Memory is therefore
input storage plus active path/frame/key state, not constant space. It is not an
incremental IO/chunk parser. Size/node quotas, arbitrary recovery, reflection-based
object construction, float conversion and generated schema readers are non-goals.

The immutable document builder is a consumer of this same native reader; it keeps
its existing arena, access and publication contract. The existing boxed decoder
continues sharing lexical machinery without changing its value mapping.

## Focused proof

`python3 tests/portability/json_reader.py --results FRESH_DIRECTORY` uses the shared
test pool for native reader protocol/lifetime checks, unchanged document regression
tests, and one PHP-to-native typed-record consumer. It checks expected values,
schema failures, terminal state, private publication and a shared syntax corpus.
It uses the current checkout and bypasses legacy STAN for the portable compiler
fixture. It does not rebuild the my-try compiler or claim catalog registration.
