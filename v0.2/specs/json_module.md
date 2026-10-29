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

Add `"json": {"header": "scpp/json.hpp", "operations": [...]}` to a publication.
Each operation requires its boxed family and result carrier explicitly published,
including their ordinary dependencies. Families/operations can be selected independently;
scalar family conversion need not be enabled. Missing dependencies, invalid operations,
duplicates and header conflicts reject before emission. The default publication is unchanged.
`tests/json/publication.json` is the complete bounded request example.

`php/json.php` owns dependency preparation; `php/render_json.php` emits concrete
bindings over `runtime/src/json/`. The public include closure is template-free.
The module uses the existing result state owner; its private outcome is only a
native transport record. Link a publication's generated core/boxed definitions and
`scpp_boxed_storage`, then call `scpp_add_json(target core generated)` from
`cmake/Json.cmake`. The module depends on core, never the reverse.

## Production build profile

`SCPP_ENABLE_JSON=ON` publishes both families, their result carriers and all four
JSON operations, then builds `scpp_json` and `scpp_json_demo`. Scalar family crossing
is not requested. `cmake/RuntimePublication.cmake` composes explicit build demand
from the default core and module requests; PHP remains the admission authority.
The option composes with `SCPP_ENABLE_DATETIME`, including with tests disabled.

Each option combination selects a distinct generated directory: `generated`,
`generated-json`, `generated-datetime` or `generated-datetime-json`. Reconfiguration
selects the matching publication and target graph; old output directories are inert.
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

## Reuse and boundaries

The lexical reader and grammar are adapted from `runtime/include/modules/json/detail/reader.hpp`
and `modules/json/json.cpp` at `2f39e536d`; scalar escaping/formatting and active-path cycle
semantics derive from `runtime/include/scpp/json/writer.hpp` at that revision. These
paths record provenance only: builds use v0.2 sources exclusively. The private
`boxed_inspection` adapter exposes no public cursor, mutable reference or subscription.
Public traversal/liveness remains RT-DEBT-01.

Typed `from_json`, typed scalar/vector/hash encoding, source compiler registration,
PHP compatibility wrappers and persistent `{}`/`[]` identity remain separate work.
The donor's recursive parser/writer has no explicit nesting-depth budget; this slice
does not establish a hostile-input resource-limit contract or a throughput claim.
See [validation](json_validation.md).
