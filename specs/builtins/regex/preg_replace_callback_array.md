# Builtin Contract - `preg_replace_callback_array`
Doc Status: normative

## Identity
- Name: `preg_replace_callback_array`
- Module/family: regex
- Category/classification: Feature Runtime + Wrapper
- Status: experimental
- Source-language reference target: PHP `preg_replace_callback_array`
- Compatibility level: practical

## Signature
- Shared runtime / strict typed core:
  - `regex_replace_callback_array(hash_t<function<string_t(vector_t<string_t>)>, string> $callbacks, string $subject): result_or_false<string_t>`
  - `regex_replace_callback_array(hash_t<function<string_t(vector_t<string_t>)>, string> $callbacks, string $subject, int $limit): result_or_false<string_t>`
- Legacy PHP wrapper:
  - `preg_replace_callback_array(hash_t<function<string_t(mixed_t)>, string> $callbacks, string $subject): result_or_false<string_t>`
  - `preg_replace_callback_array(hash_t<function<string_t(mixed_t)>, string> $callbacks, string $subject, int $limit): result_or_false<string_t>`

## Behavior
- Callbacks are applied in callback-table insertion order.
- Each callback entry is `pattern => callback`.
- Each pattern is applied against the current subject text after previous callback entries have already rewritten it.
- Shared runtime callback input is a packed vector of strings `[full, cap1, cap2, ...]`.
- Legacy wrapper adapts callback input to a PHP-compatible `mixed_t` array before invoking the callback.
- The legacy callback table itself remains typed in the first pass; it is not a dynamic `mixed_t` PHP array of callables.
- Invalid pattern syntax in any callback-table entry returns `false`.

## Compatibility table
- PHP callback table is an associative array of `pattern => callback` -> first pass keeps ordered pattern-to-callback mapping but narrows it to a typed `hash_t` callable table -> modified
- PHP callback receives an array of matches -> legacy wrapper keeps that behavior through `mixed_t` array adaptation while runtime/strict stay typed -> kept
- PHP supports optional count output -> first pass omits count output -> modified
- PHP accepts richer callable normalization forms -> first pass requires concrete `function<string_t(...)>` entries in the callback table -> modified

## Error policy
- Invalid pattern syntax returns `false`.
- Negative limit other than `-1` throws `ValueError`.

## Runtime and wrapper split
- Runtime: iterate typed callback-table entries in order and invoke typed callbacks with `vector_t<string_t>`.
- Legacy wrapper: adapt typed captures to a PHP-compatible `mixed_t` array before invoking each legacy callback.

## Configuration visibility
- Available only when the opt-in `regex` runtime module is enabled for the project.

## Compile plan summary
- Shared runtime support lives in `runtime/include/modules/regex/regex.hpp` and `runtime/include/modules/regex/regex.cpp`.
- PHP wrapper exposure lives in `runtime/include/lang/php/php_regex.hpp`.
- Registered in `generators/php/specs/php_runtime_symbols_legacy.json`.

## Test matrix
- callback entries apply in insertion order
- callback receives full match and captures
- invalid pattern returns false
