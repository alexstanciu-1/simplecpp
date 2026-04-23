# Builtin Contract â€” `bin2hex`
Doc Status: normative
## Identity
- Name: `bin2hex`
- Module/family: string
- Category/classification: Binary/Hex Conversion
- Status: experimental
- Source-language reference target: PHP `bin2hex`
- Compatibility level: practical

## Signature
- Supported form: `bin2hex(string $string): string`
- Accepted argument types: `string_t`

## Behavior
- Encodes raw bytes into hexadecimal text.
- Output is lowercase hex.
- Input is treated as raw bytes, including null bytes.
- Empty input returns an empty string.

## Compatibility table
- PHP emits lowercase hexadecimal text â†’ Prism++ emits lowercase hexadecimal text â†’ kept
- PHP is binary-safe for raw input bytes â†’ Prism++ keeps byte-oriented conversion â†’ kept

## Error policy
- Does not throw for ordinary input.

## Runtime and wrapper split
- Runtime: map each input byte to two lowercase hex digits.
- Wrapper: expose PHP-visible name only.

## Configuration visibility
- Implicitly available by project policy.

## Compile plan summary
- Implemented in `runtime/include/scpp/support/php.hpp`.
- Registered in `generators/php/specs/php_runtime_symbols.json`.

## Test matrix
- empty string
- plain ASCII input
- null-byte input
- 0xff byte input
- round-trip with `hex2bin`
