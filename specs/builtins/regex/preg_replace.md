# Builtin Contract - `preg_replace`
Doc Status: normative

## Identity
- Name: `preg_replace`
- Module/family: regex
- Category/classification: Feature Runtime + Wrapper
- Status: experimental
- Source-language reference target: PHP `preg_replace`
- Compatibility level: practical

## Signature
- Shared runtime / strict typed core:
  - `regex_replace(string $pattern, string $replacement, string $subject): result_or_false<string_t>`
  - `regex_replace(string $pattern, string $replacement, string $subject, int $limit): result_or_false<string_t>`
- Legacy PHP wrapper:
  - `preg_replace(string $pattern, string $replacement, string $subject): result_or_false<string_t>`
  - `preg_replace(string $pattern, string $replacement, string $subject, int $limit): result_or_false<string_t>`

## Behavior
- Replaces non-overlapping matches of the PCRE2-delimited pattern inside the subject.
- Default limit is unlimited.
- Positive limit replaces at most that many matches.
- `limit == 0` performs zero replacements and returns the original subject unchanged after validating the pattern.
- The replacement string supports positional backreference expansion for `$1`, `${1}`, and `\\1`.
- Unmatched capture references expand to an empty string in the current pass.
- Invalid pattern syntax returns `false`.

## Compatibility table
- PHP supports array forms, richer replacement features, and optional count output -> Prism++ first pass supports the string-only form, positional backreferences, and optional count output -> modified
- PHP replaces non-overlapping matches left-to-right -> Prism++ keeps the same practical direction -> kept

## Error policy
- Invalid pattern syntax returns `false`.
- Negative limit other than `-1` throws `ValueError`.
- Validate the limit first and the pattern second, including zero limit and empty subject. Invalid limits take precedence over invalid pattern syntax.

## Runtime and wrapper split
- Runtime: compile the shared PCRE2 pattern, iterate matches, and assemble the output string.
- Legacy wrapper: expose the PHP-visible name while forwarding the typed string result unchanged.

## Configuration visibility
- Available only when the opt-in `regex` runtime module is enabled for the project.

## Compile plan summary
- Shared runtime support lives in `runtime/include/modules/regex/regex.hpp` and `runtime/include/modules/regex/regex.cpp`.
- PHP wrapper exposure lives in `runtime/include/lang/php/php_regex.hpp`.
- Registered in `generators/php/specs/php_runtime_symbols_legacy.json`.

## v0.2 binding boundary
- Four-argument `regex_replace` takes a value limit through `scpp::regex::replace_limited`; generated writable snapshots cannot bind to the older count-output overload. The three-argument form remains unlimited. Public count-output signatures remain separate.
- Zero-length matching keeps output-copy position separate from search progress, preserving all untouched bytes. After an empty match, try a nonempty match at that position before advancing the search by one character (one UTF-8 code point in UTF mode).

## Test matrix
- no matches returns original subject
- simple replacement
- positional backreference replacement
- limited replacement count
- zero limit no-op
- invalid pattern returns false
