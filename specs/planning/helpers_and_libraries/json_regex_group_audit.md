# JSON and regex integration group

Doc Status: planning

One proposed Stage 1 goal, four public checkpoints in this order. Baseline
`01bec9f02`. Compared release `d2f63e37e`, my-try `624ab8a2d`, the archived
native fixtures and current v0.2. JSON and regex have independent module and
link dependencies. Fixed arrays remain separate debt.

## 1. JSON encode and decode

The JSON-on profile selects twelve native operations. Two decode into checked
dynamic or mixed results. Ten encode overloads return result<string> for mixed,
dynamic, int64, float64, bool, string and the four nullable scalars. All take
one const reference. The compiler needs three source names with exact overload
selection and two boxed result payloads. The existing carrier generator collects
scalar and vector payload definitions and skips unrecognized payloads.

In a scratch JSON-on production configuration, generated both runtime and
compiler and selected the json dependency plus general_api in a PHP project.
An empty caller produced the JSON, boxed-storage, packed-storage and runtime
archive entries. json_decode, json_decode_mixed and json_encode each failed
source lookup. Separate signature probes rejected result<mixed> and
result<dynamic> as unavailable. The three focused native JSON tests passed:
consumer, publication and freshness. Their consumer checks successful null
and false, malformed input, duplicate escaped keys with a byte offset, nullable
zero/false/empty text, cycles versus repeated aliases, and retry after error.

**Proposed source contract.** Follow the newer my-try decision and current
v0.2 native contract: json_decode(text) returns result<dynamic> and
json_decode_mixed(text) returns result<mixed>. The release's older PHP-facing
json_decode returned result<mixed>. Use the same return family for a given
spelling after enabling the general API. Publish PHP's natural json_decode and
json_encode with the module, and select explicit general names by project policy.
Bind directly to the selected native overload. Prove the result state separately
from the payload's presence. Use exact input type to select nullable encode before
any extraction, so absent, zero, false and empty text keep their meanings.
Inspect the current aggregate json.hpp include closure for each lowered call and
provide narrow declaration headers using the existing provider conventions.
Verify source module retirement, re-addition and a native link via the emitted bundle.

**Additional donor coverage.** Release native to_json/from_json and typed
vector/hash encoders go beyond the twelve selected operations. Record typed
conversion/container support separately unless explicitly added to this goal.
A future typed hash encoder must preserve object shape even for empty hashes
and dense integer keys. Encoding the current boxed decode of {} yields [].

[Active JSON contract](../../json_module.md) · [Native operation plan](../../../tools/api_generator/php/json.php) · [Source carrier generation](../../../tools/api_generator/php/render_compiler_carriers.php)

## 2. JSON document and nodes

The my-try publication has twelve free operations: document_parse and
document_root, plus node_kind, size, string, boolean, number, int, at, key, has
and member (each node operation has the json_node_ prefix). Parsing takes text,
a writable nullable diagnostic slot and optional max_depth (default 128, range
1–256). A diagnostic carries category, byte_offset and message. Success clears
the diagnostic; parse failure publishes an error and a diagnostic. Invalid node
access also returns a checked error.

An immutable node retains its backing storage after its document is replaced.
Preserve array versus object, exact decoded member names, source order, null
versus absent, and the original number token. For example 1e0 is a valid number
whose text can be read, but lexical integer extraction fails. Whole-input UTF-8
validation and bounded depth belong to this document contract. The boxed decoder
continues its existing raw-byte and number policies. Reuse the current private
lexical reader where those policies agree. Do not implement documents by
converting the output of boxed decode.

The archived native document fixture passed, including retained nodes, {} versus
[], integer boundaries, malformed UTF-8 and a 23-row duplicate-key corpus. A
current source probe rejected json_document_parse. The first integration proof
should carry an owned node through result<json_node>, reset its document and
access the node again. Pair that with a failed parse followed by successful reuse
of the same diagnostic slot. These proofs exercise the new handle/result boundary.

[Document contract and complete signatures](../../../v0.1/specs/builtins/json/document.md) · [Donor fixture](../../../v0.1/tests/runtime/native/test_json_document.cpp)

## 3. JSON pull reader

One factory and fourteen methods in the my-try catalog. The methods are start,
kind, begin_object, begin_array, next_property, next_element, property_name,
read_string, read_number, read_integer, read_boolean, read_null, finish_document
and reject. The mutable handle owns its input and shares cursor state across
aliases. start is one-shot; the file argument labels diagnostics. A checked
false from next_element or next_property means normal container completion.
A protocol error terminates the reader. Raw number spelling and object/array
shape must agree with the document API.

The donor document builder consumes the private structural reader. Implement
that private machinery as part of checkpoint 2, then publish its checked source
facade in checkpoint 3. This preserves the requested public order and gives
the two APIs one implementation of depth, duplicate names and token consumption.
The current v0.2 lexical reader alone supplies neither protocol nor owned cursor.

The archived native pull-reader fixture passed, including replacing the original
input, int64 extrema, exact 1e9999 spelling, a nested error path, early finish
and failed restart. Current source rejected json_pull_reader_create. The v0.2
source proof should advance through an alias, finish an empty container with
successful false and distinguish that outcome from a terminal checked error.
Memory includes owned input and duplicate-name sets for open objects.

[Reader contract](../../../v0.1/specs/builtins/json/pull_reader.md) · [Donor fixture](../../../v0.1/tests/runtime/native/test_json_pull_reader.cpp)

## 4. Regex

v0.2 selects regex_test, regex_find_span and the two-argument PHP preg_match.
The current PCRE2 implementation requests one output pair for the whole match.
A native v0.2 probe found x at bytes [2,3) in éx, an empty hit at [3,3),
a miss returning −1 and invalid syntax returning an error. Both miss and
error preserved the caller's end slot. Keep that behavior and its nonnegative
offset bounds. Donor capture helpers have a separate offset policy.

My-try has 25 overload records across thirteen names:

| General operations | Integration boundary |
| --- | --- |
| regex_match, regex_match_named | Numbered vector or string-keyed hash with numbered and named entries. Invalid pattern returns false; a miss gives an empty collection. |
| regex_match_all, regex_match_all_pattern_order, regex_match_all_named | Nested rows, transposed columns, or rows of named hashes. Prove the exact result carrier and zero-length match progression. |
| regex_quote, regex_jit_available | Quoting with an optional delimiter; JIT availability reports the linked provider's capability. |
| regex_grep, regex_filter, regex_split | Typed vector inputs or outputs; preserve limit and split-flag contracts. My-try names PREG_SPLIT_NO_EMPTY and PREG_SPLIT_DELIM_CAPTURE. |
| regex_replace, regex_replace_callback, regex_replace_callback_array | Replacement syntax, literal callback output, ordered pattern/callback hash, limits, throwing callbacks and owned capture values. |

The release PHP surfaces are preg_match, preg_match_all, preg_quote,
preg_jit_available, preg_grep, preg_filter, preg_split, preg_replace,
preg_replace_callback and preg_replace_callback_array. Match and match-all list
2–5 arguments: a mutable matches output, flags, then byte offset. Match-all
defaults to pattern order; set order produces one row per match. Quote lists
1–2 arguments, JIT zero, grep two, split 2–4, and filter 3–4. Replace and
replace-callback list 3–4, callback-array 2–3. A value-limit overload and a
native count-output overload can differ only in passing mode. Bind the value
limit explicitly; the my-try source catalog does not publish count outputs.
Review the release's separate count-output evidence before publishing any such
source signature. Preserve the existing PHP two-argument preg_match status result. A current PHP probe with the regex
module and general_api disabled accepted plain regex_test; regex_match failed
with the opt-in enabled. Move neutral names under the normal general-API guard
and migrate existing callers requiring that opt-in.

An archived native capture probe distinguished an unmatched intermediate group
(empty string), trailing unmatched group (omitted from the numbered vector),
empty full match (one empty string) and miss (empty vector). Archived native
callback and replacement fixtures passed 34 and 54 checks respectively.
The current span-only runtime cannot supply the capture rows. Extend the private
PCRE2 path with capture-sized match data for operations that need it. Preserve
the existing length-aware pattern and subject boundaries. Prove selected nested
result types and source-callable arguments at the native boundary before adding
all adapters. Keep PCRE2 headers private and link the provider only for regex.

[Donor regex signatures and compatibility limits](../../../v0.1/specs/builtins/regex/first_pass.md) · [Current provider](../../../runtime/src/regex/regex.cpp) · [Existing source fixture](../../../compiler/tests/regex_native.py)

## Acceptance for the group

Each checkpoint needs selected/absent module and applicable flavor visibility,
a source caller compiled and linked from its emitted bundle, typed failures and
incremental recovery. Check minimal header closure and absence of cross-module
dependencies. Keep the three JSON surfaces separable in metadata. Verify native
provenance independently from v0.2 source acceptance: the donor fixture results
above establish behavior to reproduce, not completed v0.2 publication.
