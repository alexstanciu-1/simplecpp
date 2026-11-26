# Bounded JSON module

Doc Status: normative
Scope: v0.2 native runtime rewrite; subordinate to the top-level
[decode](json_module.md) and
[encode](json_module.md) contracts.

## Surface and composition

The PHP API generator publishes the following concrete operations on request:

| Request ID | Public declaration in `scpp::json` |
| --- | --- |
| `decode` | `result_dynamic decode(const string&)` |
| `decode_mixed` | `result_mixed decode_mixed(const string&)` |
| `encode_mixed` | `result_string encode(const mixed&)` |
| `encode_dynamic` | `result_string encode(const dynamic&)` |
| `encode_int64` | `result_string encode(const int64&)` |
| `encode_float64` | `result_string encode(const float64&)` |
| `encode_bool_t` | `result_string encode(const bool_t&)` |
| `encode_string` | `result_string encode(const string&)` |
| `encode_nullable_int64` | `result_string encode(const nullable_int64&)` |
| `encode_nullable_float64` | `result_string encode(const nullable_float64&)` |
| `encode_nullable_bool_t` | `result_string encode(const nullable_bool_t&)` |
| `encode_nullable_string` | `result_string encode(const nullable_string&)` |

Add `"json": {"header": "scpp/json.hpp", "operations": [...]}` to a publication.
Each operation requires its argument/payload type and result carrier explicitly published,
including their ordinary dependencies. Families/operations can be selected independently;
scalar family conversion need not be enabled. Typed scalar encoding does not require
a published boxed family and writes directly, using the same scalar formatter as
boxed encoding. Missing dependencies, invalid operations,
duplicates and header conflicts reject before emission. The default publication is unchanged.
`tests/json/publication.json` is the complete bounded request example.

`php/json.php` owns dependency preparation; `php/render_json.php` emits concrete
bindings over `runtime/src/json/`. The public include closure is template-free.
The module uses the existing result state owner; its private outcome is only a
native transport record. Link a publication's generated core/boxed definitions and
`scpp_boxed_storage`, then call `scpp_add_json(target core generated)` from
`cmake/Json.cmake`. The module depends on core, never the reverse.

## Production build profile

`SCPP_ENABLE_JSON=ON` publishes both families, their result carriers, the four nullable scalar types and all twelve
JSON operations, then builds `scpp_json` and `scpp_json_demo`. Scalar family crossing
is not requested. `cmake/RuntimePublication.cmake` composes explicit build demand
from the default core and module requests; PHP remains the admission authority.
The option composes with `SCPP_ENABLE_DATETIME`, including with tests disabled.

Option combinations share the shared `runtime/generated/` directory. Regeneration
fully cleans obsolete generated extensions before emitting the selected publication.
Disabling modules therefore removes their old source/header output from that root.
Independent test publications stay under the build directory, outside this cleanup root.
Generated boxed definitions belong to core when requested; parser/writer definitions
belong only to the separate JSON library. Both options default to OFF.

## Storage and failures

One donor-derived grammar constructs mixed or dynamic directly, without an intermediate
family or mode flag. Mixed retains COW semantics; dynamic retains shared container
identity. Successful null and false remain distinct from checked parse failure.
Malformed input and duplicate decoded names return errors with zero-based byte offsets;
no partial document escapes. Duplicate names are checked before key normalization.

Canonical unsigned decimal names from 0 through 2147483647 become integer keys.
Names from 2147483648 through 4294967295 return a **checked decode error**, positioned
at the key's opening quote. They do not throw the packed key-limit exception or
silently become strings. Wider numbers, signs and leading-zero names (except `"0"`)
remain string keys. This normalization is JSON-specific; ordinary boxed string-key
operations remain strictly classified. Numeric roots still support signed int64;
out-of-range integral tokens fall back to finite double. Fraction/exponent tokens
produce doubles; underflow to zero retains donor behavior. Parsing uses a private
C numeric locale; formatting uses the classic locale, without changing process locale.

The writer traverses same-family tables through a private synchronous const adapter.
Packed shape emits arrays; other tables emit objects in storage order. Empty object
identity is not retained. Cycles and nonfinite floats return checked encoding errors;
repeated aliases outside the active recursion path are valid. Every call owns its
output and active path, so failures expose no partial output and later calls recover.
Control bytes/quotes/backslashes are escaped, and escaped Unicode surrogate pairs are
validated. Raw bytes above ASCII retain the donor behavior (no new UTF-8 validation).
Allocation failures and unrelated runtime exceptions propagate; formatting does not
swallow allocation failure into partial numeric output.

## Direct typed scalar encoding

Only the four listed scalar types are admitted in this slice. Operations remain
independently demand-selected; unsupported/unrequested types and native C++ values
have no convenience overloads. No generic public encode template is introduced.
`encode(string)` encodes a JSON string, including quotes and embedded byte escapes;
it never parses the contents. Both booleans encode as JSON literals. Int64 uses
exact decimal text, including its minimum/maximum. Float64 uses the same finite
check, classic-locale max_digits10 formatting and checked failure as boxed floats.
Allocation failures retain their exception contract. The native writer owns one
set of scalar formatting overloads; boxed dispatch delegates to them, while typed
bindings pass the scalar directly without constructing a boxed value.

## Nullable scalar encoding

The four nullable encode requests require the corresponding published nullable
carrier and `result_string`. Each can be selected without a boxed API or a public
scalar encode overload. Empty encodes as successful `"null"`; present values follow
their scalar rule, so zero, false and empty string never collapse into null.
The generated binding queries presence and obtains one payload copy only when
present, then delegates to the shared native writer. This retains the existing
carrier value-copy contract (including string-copy allocation) and does not box
or mutate the payload. Nonfinite floats are checked errors; allocation failures
propagate. The source nullable remains unchanged after either failure.

Both explicit and implicit carrier extraction policies are supported. Select the
nullable encode request to obtain its presence-aware semantics: an ordinary
implicit carrier-to-scalar conversion at a scalar-only API remains ordinary checked
carrier extraction and throws when empty. This slice does not change that general
conversion policy or add encoders for result/boolean-sentinel carriers. Nested
carriers and nullable containers remain unadmitted.

## Reuse and boundaries

The lexical reader and grammar are adapted from `runtime/include/modules/json/detail/reader.hpp`
and `modules/json/json.cpp` at `2f39e536d`; scalar escaping/formatting and active-path cycle
semantics derive from `runtime/include/scpp/json/writer.hpp` at that revision. These
paths record provenance only: builds use v0.2 sources exclusively. The private
`boxed_inspection` adapter exposes no public cursor, mutable reference or subscription.
Public traversal/liveness remains RT-DEBT-01.

Standalone typed vector/hash encoding,
PHP compatibility wrappers and persistent `{}`/`[]` identity remain separate work.
The donor's recursive parser/writer has no explicit nesting-depth budget; this slice
does not establish a hostile-input resource-limit contract or a throughput claim.
See [validation](json_validation.md).

## Source bindings

With the JSON module selected, PHP exposes json_decode(string) → result<dynamic>
and json_encode(value) → result<string>. The general surface adds
json_decode_mixed(string) → result<mixed> and publishes the same decode and
encode contracts. Exact argument type selects each of the ten encode overloads,
including nullable scalars. General names follow project visibility.

For a field-derived data record, `json_encode($packet)` borrows the value and
calls its concrete `encode(Encoder&) const` method. Preparation checks the
reachable field schemas against the published scalar and vector-element
encoders. Each generated method sends object, field, array and typed value
operations to the same synchronous encoder. Native providers receive plain numeric
and boolean scalars by value and borrow strings, boxes and nullable carriers for
the duration of the write. Field names are declared
identifiers in schema order. Embedded records and shared owners can nest;
an absent owner writes JSON null. Repeated references on independent branches
encode independently. An active ownership cycle or nonfinite float yields a
failed `result<string>`, even after earlier fields have been written. Only a
successful operation publishes the output string. The runtime stores short results
inline and transfers the completed writer buffer into long results. Extracting a
string with the ordinary result value accessor retains its value-copy semantics.
See [data record
construction and codecs](data_initialization.md#native-codec-adapters-for-field-derived-signatures).

With the decode operation selected, PHP and the general API expose
`json_decode_as($text, Packet::class)` returning `result<Packet>`. The marker
must resolve to a concrete data class or struct with a generated data constructor.
Supported leaves are published signed int64, boolean and string values, including
vector elements. The root must be one complete JSON object. Members may arrive
in any order. Unknown members, duplicates, missing required inputs, malformed
elements and trailing non-whitespace produce a failed result. The error message
includes the byte offset and the reader path.

A nested field may be an embedded data struct or a required data class with
a supported schema. Each record consumes exactly one object on the same
decoder. The native `Decoder` interface exposes object and array entry,
field and element advancement, a borrowed field name, typed leaf reads and
schema rejection. Generated typed decoders classify decoded field names by schema
index. The JSON provider tracks those indices in a record-local bit mask, one
64-bit word per 64 fields. Duplicate errors retain the original key spelling,
path and opening-quote offset, and precede validation of the following colon.
Providers using ordinary field advancement can use the default schema lookup.
A typed string field owns its decoded bytes. A plain string is constructed
directly from the quoted input's contents. An escaped string uses the same
escape parser. Short results fit inline and long results take ownership of the
decoded buffer. A string
vector reads each element through the same scalar operation.
For a published int64 vector, generated decode requests the complete array in one
operation. The JSON provider appends in order and uses scalar diagnostics at the
first malformed element or delimiter. Other decoder providers can implement the
operation through ordinary array advancement and integer reads.
A false result from `next_field()` closes the object.
At the root it also guarantees document completion. A source call such as
`Packet::decode($reader)` takes a started JSON pull reader and returns the
record directly. An authored static method with that name takes precedence.
Use the text entry point for a checked result.

A successful result owns the struct or the class owner. Extracting a temporary
moves its payload; extracting a retained struct result into another value copies
the struct and preserves the saved result. Use `isset($result)` to test success.

## Document and node publication

The document selection publishes immutable document and node values. A copied
node retains the document arena, including after replacement of the originating
document. Parsing validates complete UTF-8, unique decoded object member names,
and a caller-supplied container depth from 1 to 256 (default 128). A nullable
diagnostic receives category, byte offset and message on failure and resets on
success. The returned result carries either a complete document or an error.
Node accessors preserve shape, source member order and exact number tokens.
Integer extraction accepts lexical signed int64 integers. All access failures
are checked results.

## Pull reader publication

The pull_reader selection publishes json_pull_reader_create and fourteen checked
methods. Copies share one cursor. start(text,file,limit) owns the input, uses
file for diagnostics and accepts depths 1–256. Use kind, begin_object,
begin_array, next_property, next_element and property_name to navigate. Read
the current scalar with read_number (original spelling), read_integer (int64),
read_string, read_boolean or read_null. A successful false from advancement
closes that container. Finish with finish_document after consuming the root,
or fail the cursor explicitly through reject(message). A failed read is
terminal; restart requires a fresh handle. Documents use the same private
structural reader, with independent public feature selection.
