# Builtin Contract â€” `hex2bin`
Doc Status: normative
## Identity
- Name: `hex2bin`
- Module/family: string
- Category/classification: Binary/Hex Conversion
- Status: experimental
- Source-language reference target: PHP `hex2bin`
- Compatibility level: practical

## Signature
- Supported form: `hex2bin(string $string): mixed_t<string|false>`
- Accepted argument types: `string_t`

## Behavior
- Decodes an even-length hexadecimal byte string into raw bytes.
- Accepts both lowercase and uppercase hex digits.
- Returns a string payload on success.
- Returns `false` for odd-length input.
- Returns `false` for any non-hex digit.
- Empty input returns an empty string.

## Compatibility table
- PHP accepts mixed hex case â†’ Prism++ accepts both lower and upper hex digits â†’ kept
- PHP returns `false` for invalid input instead of throwing â†’ Prism++ keeps the same non-throwing failure mode â†’ kept

## Error policy
- Does not throw for ordinary invalid input.
- Returns `false` on invalid hex input or odd input length.

## Runtime and wrapper split
- Runtime: validate pairs of hex digits, decode each pair into one byte, and return string-or-false as `mixed_t`.
- Wrapper: expose PHP-visible name only.

## Configuration visibility
- Implicitly available by project policy.

## Compile plan summary
- Implemented through shared strict decoding support with PHP-visible adapter shaping in `runtime/include/lang/php/support/php_string.hpp`.
- Registered in `generators/php/specs/php_runtime_symbols_legacy.json`.
- Shared plain-name exposure in strict should preserve the same visible `string|false` contract rather than exposing the internal strict `result<string_t>` helper directly.

## Test matrix
- empty string
- lowercase hex
- uppercase hex
- odd-length input
- invalid digit input
- raw binary round-trip with null byte
