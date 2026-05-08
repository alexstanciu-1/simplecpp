# Builtin Contract - `preg_match_all`
Doc Status: normative

## Identity
- Name: `preg_match_all`
- Module/family: regex
- Category/classification: Feature Runtime + Wrapper
- Status: experimental
- Source-language reference target: PHP `preg_match_all`
- Compatibility level: practical

## Signature
- Shared runtime / strict typed core:
  - `regex_match_all(string $pattern, string $subject): result_or_false<vector_t<vector_t<string_t>>>`
  - `regex_match_all_named(string $pattern, string $subject): result_or_false<vector_t<hash_t<string_t, string_t>>>`
- Legacy PHP wrapper:
  - `preg_match_all(string $pattern, string $subject): result_or_false<int_t>`
  - `preg_match_all(string $pattern, string $subject, mixed_t &$matches): result_or_false<int_t>`

## Behavior
- Uses the same PCRE2-delimited pattern syntax and first-pass modifiers as `preg_match`.
- The shared typed core returns match-order rows.
- The named strict variant returns match-order rows of typed string-keyed tables:
  - numeric captures are keyed as `"0"`, `"1"`, `"2"`, ...
  - named captures are keyed by capture name
- Empty outer vector means no matches.
- Invalid pattern syntax returns `false`.
- The legacy wrapper returns the number of matches found.
- When the legacy output `matches` array is supplied, it is filled in match order:
  - outer array is packed by match index
  - each outer row is a packed PHP-compatible array of strings `[full, cap1, cap2, ...]`
- When the pattern defines named captures, each legacy row also receives named string keys pointing at the same captured values.
- Zero-length matches are allowed and advance safely by one byte to avoid infinite loops in the first pass.

## Compatibility table
- PHP returns match count or `false` -> Prism++ keeps the same return contract -> kept
- PHP default output ordering is pattern-order and also supports flags -> Prism++ first pass uses typed match-order rows in runtime/strict and adapts those rows into legacy PHP arrays -> modified
- PHP exposes named captures in match rows -> legacy keeps that behavior, and strict exposes a separate named-match-all function -> modified

## Error policy
- Invalid pattern syntax returns `false`.
- Unsupported pattern modifiers return `false`.

## Runtime and wrapper split
- Runtime: iterate repeated PCRE2 matches and materialize a packed outer table of packed capture rows.
- Legacy wrapper: expose the PHP-visible name and adapt typed match-order rows into packed PHP-compatible arrays.

## Configuration visibility
- Available only when the opt-in `regex` runtime module is enabled for the project.

## Compile plan summary
- Shared runtime support lives in `runtime/include/modules/regex/regex.hpp` and `runtime/include/modules/regex/regex.cpp`.
- PHP wrapper exposure lives in `runtime/include/lang/php/php_regex.hpp`.
- Registered in `generators/php/specs/php_runtime_symbols_legacy.json`.

## Test matrix
- multiple matches
- capture groups per row
- named captures per row in strict/runtime
- named captures per row in legacy output
- no-match returns zero
- invalid pattern returns false
