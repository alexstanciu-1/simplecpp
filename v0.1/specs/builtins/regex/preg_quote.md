# Builtin Contract - `preg_quote`
Doc Status: normative

## Identity
- Name: `preg_quote`
- Module/family: regex
- Category/classification: Feature Runtime + Wrapper
- Status: experimental
- Source-language reference target: PHP `preg_quote`
- Compatibility level: practical

## Signature
- Shared runtime / strict typed core:
  - `regex_quote(string $str): string_t`
  - `regex_quote(string $str, string $delimiter): string_t`
- Legacy PHP wrapper:
  - `preg_quote(string $str): string_t`
  - `preg_quote(string $str, string $delimiter): string_t`

## Behavior
- Escapes regex metacharacters in the input text for safe literal use inside a PCRE pattern.
- When a non-empty delimiter is supplied, that delimiter character is also escaped in the output.
- The first-pass contract treats the delimiter as a single-character string when present. Additional delimiter bytes are ignored after the first byte.

## Compatibility table
- PHP escapes PCRE metacharacters and optionally the delimiter -> Prism++ does the same through the shared `scpp::regex::quote(...)` helper -> kept

## Error policy
- Does not throw for ordinary text input.
- Empty delimiter is accepted and treated as "no extra delimiter escaping".

## Runtime and wrapper split
- Runtime: implement escaping in the shared regex module under `scpp::regex`.
- Legacy wrapper: expose the PHP-visible `preg_quote(...)` names only.

## Configuration visibility
- Available only when the opt-in `regex` runtime module is enabled for the project.

## Compile plan summary
- Shared runtime support lives in `runtime/include/modules/regex/regex.hpp` and `runtime/include/modules/regex/regex.cpp`.
- PHP wrapper exposure lives in `runtime/include/lang/php/php_regex.hpp`.
- Registered in `generators/php/specs/php_runtime_symbols_legacy.json`.

## Test matrix
- escapes common metacharacters
- escapes supplied delimiter
- leaves ordinary text unchanged
