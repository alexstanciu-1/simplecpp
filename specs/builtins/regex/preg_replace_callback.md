# Builtin Contract - `preg_replace_callback`
Doc Status: normative

## Identity
- Name: `preg_replace_callback`
- Module/family: regex
- Category/classification: Feature Runtime + Wrapper
- Status: experimental
- Source-language reference target: PHP `preg_replace_callback`
- Compatibility level: practical

## Signature
- Shared runtime / strict typed core:
  - `regex_replace_callback(string $pattern, function<string_t(vector_t<string_t>)> $callback, string $subject): result_or_false<string_t>`
  - `regex_replace_callback(string $pattern, function<string_t(vector_t<string_t>)> $callback, string $subject, int $limit): result_or_false<string_t>`
- Legacy PHP wrapper:
  - `preg_replace_callback(string $pattern, function<string_t(mixed_t)> $callback, string $subject): result_or_false<string_t>`
  - `preg_replace_callback(string $pattern, function<string_t(mixed_t)> $callback, string $subject, int $limit): result_or_false<string_t>`

## Behavior
- The shared typed core invokes the callback once for each non-overlapping match.
- Callback input is a packed vector of strings `[full, cap1, cap2, ...]`.
- The callback return value replaces the full matched span literally.
- Legacy wrapper adapts the packed vector into a PHP-compatible `mixed_t` array before invoking the callback.
- Invalid pattern syntax returns `false`.

## Compatibility table
- PHP callback receives an array of matches -> legacy wrapper keeps that behavior through `mixed_t` array adaptation while runtime/strict stay typed -> kept
- PHP supports richer callback-array callable forms and optional count output -> Prism++ first pass narrows to a single callable parameter and no count output -> modified

## Error policy
- Invalid pattern syntax returns `false`.
- Negative limit other than `-1` throws `ValueError`.

## Runtime and wrapper split
- Runtime: perform repeated matches and invoke a typed callback with `vector_t<string_t>`.
- Legacy wrapper: adapt typed captures to a PHP-compatible `mixed_t` array and invoke a legacy-shaped callback.

## Configuration visibility
- Available only when the opt-in `regex` runtime module is enabled for the project.

## Compile plan summary
- Shared runtime support lives in `runtime/include/modules/regex/regex.hpp` and `runtime/include/modules/regex/regex.cpp`.
- PHP wrapper exposure lives in `runtime/include/lang/php/php_regex.hpp`.
- Registered in `generators/php/specs/php_runtime_symbols_legacy.json`.

## Test matrix
- callback receives full match and captures
- limited replacement count
- invalid pattern returns false
