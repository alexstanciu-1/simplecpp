# Bounded JSON module

Doc Status: normative
Scope: v0.2 native runtime rewrite; subordinate to the top-level
[decode](../../specs/builtins/json/json_decode.md) and
[encode](../../specs/builtins/json/json_encode.md) contracts.

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

Canonical unsigned decimal names through UINT32_MAX normalize to integer keys.
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

Typed `from_json`, typed vector/hash encoding, source compiler registration,
PHP compatibility wrappers and persistent `{}`/`[]` identity remain separate work.
The donor's recursive parser/writer has no explicit nesting-depth budget; this slice
does not establish a hostile-input resource-limit contract or a throughput claim.
See [validation](json_validation.md).
