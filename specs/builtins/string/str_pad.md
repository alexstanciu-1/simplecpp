# Builtin Contract — `str_pad`

## Identity
- Name: `str_pad`
- Module/family: string
- Category/classification: Padding Wrapper
- Status: experimental
- Source-language reference target: PHP `str_pad`
- Compatibility level: practical

## Signature
- Supported forms:
  - `str_pad(string $input, int $pad_length): string`
  - `str_pad(string $input, int $pad_length, string $pad_string): string`
  - `str_pad(string $input, int $pad_length, string $pad_string, int $pad_type): string`
- Accepted argument types: `string_t`, `int_t`, optional `string_t`, optional `int_t`

## Behavior
- Pads `input` to the target byte length.
- Default `pad_string` is one space.
- Default `pad_type` is `STR_PAD_RIGHT`.
- Supported pad types are `STR_PAD_LEFT`, `STR_PAD_RIGHT`, and `STR_PAD_BOTH`.
- Multi-byte pad strings are repeated and truncated at byte boundaries as needed.
- If `pad_length` is not greater than the input length, the original string is returned unchanged.
- For `STR_PAD_BOTH`, odd extra padding keeps the extra byte on the right.

## Compatibility table
- PHP pads by repeated/truncated pad string bytes → Prism++ keeps the same practical direction → kept
- PHP validates empty pad string and pad type → Prism++ throws `ValueError` for those invalid cases → kept

## Error policy
- Throws `ValueError` if `pad_string` is empty.
- Throws `ValueError` if `pad_type` is not one of the supported constants.

## Runtime and wrapper split
- Runtime: compute left/right pad sizes, build repeated pad bytes, then assemble the output.
- Wrapper: expose PHP-visible name and constants only.

## Configuration visibility
- Implicitly available by project policy.

## Compile plan summary
- Implemented in `runtime/include/scpp/support/php.hpp`.
- Registered in `generators/php/specs/php_runtime_symbols.json`.

## Test matrix
- default right padding
- explicit left padding
- explicit both padding
- multi-byte pad string truncation
- no-op when target length is too small
- empty pad string error
- invalid pad type error
