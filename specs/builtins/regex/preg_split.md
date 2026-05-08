# Builtin Contract - `preg_split`
Doc Status: normative

## Identity
- Name: `preg_split`
- Module/family: regex
- Category/classification: Feature Runtime + Wrapper
- Status: experimental
- Source-language reference target: PHP `preg_split`
- Compatibility level: practical

## Signature
- Shared runtime / strict typed core:
  - `regex_split(string $pattern, string $subject): result_or_false<vector_t<string_t>>`
  - `regex_split(string $pattern, string $subject, int $limit): result_or_false<vector_t<string_t>>`
  - `regex_split(string $pattern, string $subject, int $limit, int $flags): result_or_false<vector_t<string_t>>`
- Legacy PHP wrapper:
  - `preg_split(string $pattern, string $subject): result_or_false<mixed_t>`
  - `preg_split(string $pattern, string $subject, int $limit): result_or_false<mixed_t>`
  - `preg_split(string $pattern, string $subject, int $limit, int $flags): result_or_false<mixed_t>`

## Behavior
- Splits the subject by matches of the PCRE2-delimited pattern string.
- Default limit is unlimited.
- Positive limit returns at most that many result parts, with the final part carrying the remainder.
- `limit == 0` is treated like unlimited.
- Invalid pattern syntax returns `false`.
- The typed core returns a packed `vector_t<string_t>` of string parts.
- The legacy wrapper adapts those parts into a packed PHP-compatible array.
- Supported first-pass split flags use PHP-compatible bit values:
  - `1` = `PREG_SPLIT_NO_EMPTY`
  - `2` = `PREG_SPLIT_DELIM_CAPTURE`
- `PREG_SPLIT_OFFSET_CAPTURE` is still unsupported in the current pass.

## Compatibility table
- PHP returns an array or `false` -> Prism++ runtime/strict returns `vector_t<string_t>` while the legacy wrapper adapts that into a packed `mixed_t` array -> modified
- PHP supports optional split flags -> Prism++ currently supports `PREG_SPLIT_NO_EMPTY` and `PREG_SPLIT_DELIM_CAPTURE`, but not offset capture -> modified
- PHP accepts regex delimiters and modifiers -> Prism++ keeps the same practical pattern form -> kept

## Error policy
- Invalid pattern syntax returns `false`.
- Negative limit other than `-1` throws `ValueError`.
- `PREG_SPLIT_OFFSET_CAPTURE` currently throws `ValueError`.

## Runtime and wrapper split
- Runtime: parse delimiters/modifiers, compile the shared PCRE2 pattern, iterate matches, and pack string segments into `vector_t<string_t>`.
- Legacy wrapper: expose the PHP-visible name and adapt the typed result into a packed PHP-compatible array.

## Configuration visibility
- Available only when the opt-in `regex` runtime module is enabled for the project.

## Compile plan summary
- Shared runtime support lives in `runtime/include/modules/regex/regex.hpp` and `runtime/include/modules/regex/regex.cpp`.
- PHP wrapper exposure lives in `runtime/include/lang/php/php_regex.hpp`.
- Registered in `generators/php/specs/php_runtime_symbols_legacy.json`.

## Test matrix
- simple split
- limit handling
- no-empty flag
- delimiter-capture flag
- trailing empty segment
- invalid pattern returns false
