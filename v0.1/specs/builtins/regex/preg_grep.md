# Builtin Contract - `preg_grep`
Doc Status: normative

## Identity
- Name: `preg_grep`
- Module/family: regex
- Category/classification: Feature Runtime + Wrapper
- Status: experimental
- Source-language reference target: PHP `preg_grep`
- Compatibility level: practical

## Signature
- Shared runtime / strict typed core:
  - `regex_grep(string $pattern, vector_t<string_t> $input): result_or_false<vector_t<string_t>>`
- Legacy PHP wrapper:
  - `preg_grep(string $pattern, mixed_t $input): result_or_false<mixed_t>`

## Behavior
- Shared runtime / strict filters typed packed strings.
- Legacy wrapper reads a PHP-compatible packed array, converts each element to string, and returns a PHP-compatible packed array.
- Invalid pattern syntax returns `false`.

## Compatibility table
- PHP preserves original keys -> Prism++ first pass returns packed values in input order -> modified
- PHP accepts array inputs broadly -> Prism++ first pass narrows the typed core to `vector_t<string_t>` and the legacy wrapper to packed PHP arrays -> modified

## Error policy
- Invalid pattern syntax returns `false`.
- Valid typed calls do not throw for ordinary no-match filtering.

## Runtime and wrapper split
- Runtime: reuse the shared PCRE2 compile/match helpers against each input element.
- Legacy wrapper: convert a packed PHP-compatible input array into typed strings, then adapt the typed output back into a packed PHP-compatible array.

## Configuration visibility
- Available only when the opt-in `regex` runtime module is enabled for the project.

## Compile plan summary
- Shared runtime support lives in `runtime/include/modules/regex/regex.hpp` and `runtime/include/modules/regex/regex.cpp`.
- PHP wrapper exposure lives in `runtime/include/lang/php/php_regex.hpp`.
- Registered in `generators/php/specs/php_runtime_symbols_legacy.json`.

## Test matrix
- mixed matches
- no matches
- invalid pattern returns false
