# Builtin Contract - `preg_filter`
Doc Status: normative

## Identity
- Name: `preg_filter`
- Module/family: regex
- Category/classification: Feature Runtime + Wrapper
- Status: experimental
- Source-language reference target: PHP `preg_filter`
- Compatibility level: practical

## Signature
- Shared runtime / strict typed core:
  - `regex_filter(string $pattern, string $replacement, vector_t<string_t> $input): result_or_false<vector_t<string_t>>`
  - `regex_filter(string $pattern, string $replacement, vector_t<string_t> $input, int $limit): result_or_false<vector_t<string_t>>`
- Legacy PHP wrapper:
  - `preg_filter(string $pattern, string $replacement, mixed_t $input): result_or_false<mixed_t>`
  - `preg_filter(string $pattern, string $replacement, mixed_t $input, int $limit): result_or_false<mixed_t>`

## Behavior
- Applies `preg_replace` to each element's string value.
- Only elements where at least one replacement occurred are emitted into the output, including replacements that leave identical bytes.
- The typed core returns a packed vector of replaced strings in input order.
- The legacy wrapper returns a packed PHP-compatible array of replaced strings in input order.
- Replacement expands positional backreferences `$1`, `${1}` and `\\1`, matching `regex_replace`; unmatched capture references expand to an empty string.
- The default limit is `-1` (unlimited); positive limits apply separately to each input string. A zero limit produces a successful empty vector. No matches also produce a successful empty vector, distinct from false.
- Invalid pattern syntax returns `false`.

## Compatibility table
- PHP preserves original keys -> Prism++ first pass returns a packed table in input order -> modified
- PHP accepts broader array forms and replacement features -> Prism++ currently narrows to typed packed vectors in runtime/strict, packed PHP-compatible arrays in legacy, and positional backreferences -> modified

## Error policy
- Invalid pattern syntax returns `false`.
- Negative limit other than `-1` throws `ValueError`.
- The strict/shared core validates the limit first, then the pattern, even for empty input or a zero limit. Thus an invalid limit takes precedence over invalid pattern syntax; otherwise invalid syntax returns false.

## Runtime and wrapper split
- Runtime: reuse shared compile/replace helpers against each input element and emit only elements with a nonzero replacement count. Compile the pattern once per filter operation.
- Legacy wrapper: convert a packed PHP-compatible input array into typed strings, then adapt the typed output back into a packed PHP-compatible array.

## Configuration visibility
- Available only when the opt-in `regex` runtime module is enabled for the project.

## Compile plan summary
- Shared runtime support lives in `runtime/include/modules/regex/regex.hpp` and `runtime/include/modules/regex/regex.cpp`.
- PHP wrapper exposure lives in `runtime/include/lang/php/php_regex.hpp`.
- Registered in `generators/php/specs/php_runtime_symbols_legacy.json`.

## v0.2 binding boundary
- `regex_filter` exposes three arguments or a fourth value limit through opt-in runtime metadata. The four-argument provider target is `scpp::regex::filter_limited`, so a writable argument snapshot cannot select a count-output overload.
- Public count-output signatures, callback APIs and legacy wrapper migration remain separate. Existing legacy adapter differences are not proof of strict-core behavior.

## Test matrix
- mixed matching/non-matching rows
- no matches returns empty packed table
- limit handling
- invalid pattern returns false
