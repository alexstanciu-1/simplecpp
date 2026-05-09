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
- Only elements that actually matched at least once are emitted into the output.
- The typed core returns a packed vector of replaced strings in input order.
- The legacy wrapper returns a packed PHP-compatible array of replaced strings in input order.
- Replacement is literal in the first pass, matching the current `preg_replace` scope.
- Invalid pattern syntax returns `false`.

## Compatibility table
- PHP preserves original keys -> Prism++ first pass returns a packed table in input order -> modified
- PHP accepts broader array forms and replacement features -> Prism++ currently narrows to typed packed vectors in runtime/strict, packed PHP-compatible arrays in legacy, and literal replacement -> modified

## Error policy
- Invalid pattern syntax returns `false`.
- Negative limit other than `-1` throws `ValueError`.

## Runtime and wrapper split
- Runtime: reuse shared compile/replace helpers against each input element and emit only changed elements.
- Legacy wrapper: convert a packed PHP-compatible input array into typed strings, then adapt the typed output back into a packed PHP-compatible array.

## Configuration visibility
- Available only when the opt-in `regex` runtime module is enabled for the project.

## Compile plan summary
- Shared runtime support lives in `runtime/include/modules/regex/regex.hpp` and `runtime/include/modules/regex/regex.cpp`.
- PHP wrapper exposure lives in `runtime/include/lang/php/php_regex.hpp`.
- Registered in `generators/php/specs/php_runtime_symbols_legacy.json`.

## Test matrix
- mixed matching/non-matching rows
- no matches returns empty packed table
- limit handling
- invalid pattern returns false
