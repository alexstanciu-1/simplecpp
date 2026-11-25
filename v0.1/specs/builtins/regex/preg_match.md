# Builtin Contract - `preg_match`
Doc Status: normative

## Identity
- Name: `preg_match`
- Module/family: regex
- Category/classification: Feature Runtime + Wrapper
- Status: experimental
- Source-language reference target: PHP `preg_match`
- Compatibility level: practical

## Signature
- Shared runtime / strict typed core:
  - `regex_match(string $pattern, string $subject): result_or_false<vector_t<string_t>>`
  - `regex_match(string $pattern, string $subject, int_t $offset): result_or_false<vector_t<string_t>>`
  - `regex_match_named(string $pattern, string $subject): result_or_false<hash_t<string_t, string_t>>`
  - `regex_match_named(string $pattern, string $subject, int_t $offset): result_or_false<hash_t<string_t, string_t>>`
- Legacy PHP wrapper:
  - `preg_match(string $pattern, string $subject): result_or_false<int_t>`
  - `preg_match(string $pattern, string $subject, mixed_t &$matches): result_or_false<int_t>`
  - `preg_match(string $pattern, string $subject, mixed_t &$matches, int_t $flags): result_or_false<int_t>`
  - `preg_match(string $pattern, string $subject, mixed_t &$matches, int_t $flags, int_t $offset): result_or_false<int_t>`

## Behavior
- Uses a PCRE2-delimited pattern string such as `"/ab+/"` or `"/ab+/i"`.
- Supported first-pass modifiers:
  - `i`
  - `m`
  - `s`
  - `u`
  - `x`
  - `A`
  - `D`
  - `U`
- The shared typed core returns a packed vector of strings:
  - index `0` = full match
  - index `1..n` = capture groups in order
- The named strict variant returns a typed string-keyed table:
  - numeric captures are keyed as `"0"`, `"1"`, `"2"`, ...
  - named captures are keyed by capture name
- Empty vector means no match.
- Invalid pattern syntax returns `false`.
- The legacy `preg_match` wrapper returns `1` when a match is found and `0` when no match is found.
- When the legacy output `matches` array is supplied and a match is found, it is filled as a packed PHP-compatible array:
  - index `0` = full match
  - index `1..n` = capture groups in order
- When the pattern defines named captures, legacy `matches` also receives named string keys pointing at the same captured values.
- The legacy flags overload currently accepts only `0`.
- The optional offset is a byte offset into the subject.
- Negative offsets count back from the end of the subject.
- Positive offsets beyond the end of the subject produce no match.
- On no match, legacy `matches` is cleared to an empty packed array.

## Compatibility table
- PHP returns `1`, `0`, or `false` -> Prism++ keeps the same tri-state wrapper contract -> kept
- PHP can fill an output matches array -> legacy wrapper keeps that behavior through `mixed_t` PHP arrays while runtime/strict stay typed -> kept
- PHP supports named captures in output arrays -> legacy keeps that behavior, and strict exposes a separate named-match function -> modified
- PHP supports offset-capture and unmatched-as-null flags -> Prism++ raises `ValueError` for those flags because those output forms are not supported yet -> modified

## Error policy
- Invalid pattern syntax returns `false`.
- Unsupported pattern modifiers return `false`.
- Unsupported flags throw `ValueError` with a "not supported by the regex module yet" message.
- Valid typed calls do not throw for ordinary match/no-match outcomes.

## Runtime and wrapper split
- Runtime: parse delimiters/modifiers, compile the shared PCRE2 pattern, execute the match, and materialize typed packed captures.
- Legacy wrapper: expose the PHP-visible name and adapt typed captures into PHP-compatible `mixed_t` arrays.

## Configuration visibility
- Available only when the opt-in `regex` runtime module is enabled for the project.

## Compile plan summary
- Shared runtime support lives in `runtime/include/modules/regex/regex.hpp` and `runtime/include/modules/regex/regex.cpp`.
- PHP wrapper exposure lives in `runtime/include/lang/php/php_regex.hpp`.
- Registered in `generators/php/specs/php_runtime_symbols_legacy.json`.

## Test matrix
- simple positive match
- simple no-match result
- capture groups output
- named captures in strict/runtime
- named captures in legacy matches array
- UTF-8 `/u` matching, including four-byte UTF-8 code points
- positive and negative offset matching
- unsupported match flags throw
- case-insensitive modifier
- invalid pattern returns false

## v0.2 two-argument provider

Selecting the `regex` runtime module exposes `regex_test(pattern, subject): result<bool>`
in all three flavors. A successful search returns true, a valid miss returns false, and a
malformed pattern or engine failure returns an error. PHP flavor also exposes
`preg_match(pattern, subject): result_or_false<int>` with integer 1, integer 0 and the
false sentinel respectively. These calls consume byte strings with explicit lengths,
including embedded NUL, and expose no capture output. Empty delimited patterns match.
The supported modifiers are listed above. The module is built with `SCPP_ENABLE_REGEX=ON`
and selected per application through `dependencies.runtime.modules`.

All three flavors also expose `regex_find_span(pattern, subject, offset, end): result<int>`.
Pass an existing writable int/int64 local or parameter for `end`. The offset is an absolute
byte index from zero through the subject length, inclusive. Search begins there on the
original subject; the pattern controls anchoring (for example, `\G`). On match, the
payload is the first matched byte index and `end` receives the exclusive end. For an
empty match the two indices coincide. On miss the payload is −1. An out-of-range
offset, invalid pattern or engine failure (including a split UTF-8 code point in UTF
mode) returns an error. Miss and error both preserve the caller's `end`.
