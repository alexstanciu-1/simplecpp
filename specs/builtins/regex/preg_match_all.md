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
  - `regex_match_all(string $pattern, string $subject, int_t $offset): result_or_false<vector_t<vector_t<string_t>>>`
  - `regex_match_all_pattern_order(string $pattern, string $subject): result_or_false<vector_t<vector_t<string_t>>>`
  - `regex_match_all_pattern_order(string $pattern, string $subject, int_t $offset): result_or_false<vector_t<vector_t<string_t>>>`
  - `regex_match_all_named(string $pattern, string $subject): result_or_false<vector_t<hash_t<string_t, string_t>>>`
  - `regex_match_all_named(string $pattern, string $subject, int_t $offset): result_or_false<vector_t<hash_t<string_t, string_t>>>`
- Legacy PHP wrapper:
  - `preg_match_all(string $pattern, string $subject): result_or_false<int_t>`
  - `preg_match_all(string $pattern, string $subject, mixed_t &$matches): result_or_false<int_t>`
  - `preg_match_all(string $pattern, string $subject, mixed_t &$matches, int_t $flags): result_or_false<int_t>`
  - `preg_match_all(string $pattern, string $subject, mixed_t &$matches, int_t $flags, int_t $offset): result_or_false<int_t>`

## Behavior
- Uses the same PCRE2-delimited pattern syntax and first-pass modifiers as `preg_match`.
- The shared typed core returns match-order rows.
- The shared typed `regex_match_all_pattern_order` variant returns pattern-order rows:
  - outer index `0` = all full matches
  - outer index `1..n` = all values for each capture group
- The named strict variant returns match-order rows of typed string-keyed tables:
  - numeric captures are keyed as `"0"`, `"1"`, `"2"`, ...
  - named captures are keyed by capture name
- Empty outer vector means no matches.
- Invalid pattern syntax returns `false`.
- The legacy wrapper returns the number of matches found.
- When the legacy output `matches` array is supplied without flags, or with `PREG_PATTERN_ORDER`, it is filled in PHP-compatible pattern order:
  - outer index `0` = packed array of all full matches
  - outer index `1..n` = packed array of all values for each capture group
  - named capture keys are added when the pattern defines names
- With `PREG_SET_ORDER`, legacy output is filled in match order:
  - outer array is packed by match index
  - each row is a packed PHP-compatible array of strings `[full, cap1, cap2, ...]`
  - named capture keys are added to each row when the pattern defines names
- The optional offset is a byte offset into the subject.
- Negative offsets count back from the end of the subject.
- Positive offsets beyond the end of the subject produce no matches.
- Zero-length matches are allowed and advance safely by one byte to avoid infinite loops in the first pass.

## Compatibility table
- PHP returns match count or `false` -> Prism++ keeps the same return contract -> kept
- PHP default output ordering is pattern-order -> legacy wrapper keeps that behavior -> kept
- PHP supports `PREG_PATTERN_ORDER` and `PREG_SET_ORDER` -> legacy wrapper keeps those ordering flags -> kept
- Runtime/strict expose separate typed functions for match-order and pattern-order results instead of changing shape based on flags -> modified
- PHP exposes named captures in match rows -> legacy keeps that behavior, and strict exposes a separate named-match-all function -> modified
- PHP supports offset-capture and unmatched-as-null flags -> Prism++ raises `ValueError` for those flags because those output forms are not supported yet -> modified

## Error policy
- Invalid pattern syntax returns `false`.
- Unsupported pattern modifiers return `false`.
- Unsupported flags throw `ValueError` with a "not supported by the regex module yet" message.

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
- pattern-order legacy output
- set-order legacy output
- positive and negative offset matching
- unsupported match-all flags throw
- no-match returns zero
- invalid pattern returns false
