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

## v0.2 strict callback contract

The source callable type is `function<string(vector<string>)>`. Ordinary exact
callable conversion applies: owned copyable captures are allowed; direct or
transitive borrowed captures cannot cross this erased boundary. The provider
invokes synchronously and does not retain the callback after returning.

The default limit is `-1` (unlimited). A positive limit bounds callback invocations;
zero returns the unchanged subject without invoking the callback. Validate the
limit before compiling the pattern, including empty subjects and zero limits.
An invalid pattern therefore returns false even at zero limit.

Zero-length matches use the same traversal as ordinary replacement: preserve
uncopied subject bytes, try a nonempty anchored alternative at the same position,
then advance the search by one character (respecting UTF mode). Callback output
is literal bytes, without backreference expansion. Successful empty output is a
present string, distinct from the false sentinel.

Evaluate and snapshot arguments in source order before entering the provider.
Callback exceptions propagate; completed callback effects are not rolled back.
An absent callable fails only if a match requires its invocation, using the
existing absent-callable invocation behavior.

The v0.2 runtime catalog registers both arities. Its explicit-limit binding uses
`replace_callback_limited` so a writable value snapshot cannot select a native
count-output overload. Public count-output forms remain outside this contract.

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
