# Builtin Contract â€” `substr_compare`
Doc Status: normative
## Identity
- Name: `substr_compare`
- Module/family: string
- Category/classification: Offset Compare Wrapper
- Status: experimental
- Source-language reference target: PHP `substr_compare`
- Compatibility level: practical

## Signature
- Supported forms:
  - `substr_compare(string $main_str, string $str, int $offset): int`
  - `substr_compare(string $main_str, string $str, int $offset, int $length): int`
  - `substr_compare(string $main_str, string $str, int $offset, int $length, bool $case_insensitive): int`
- Accepted argument types: `string_t`, `string_t`, `int_t`, optional `int_t`, optional `bool_t`

## Behavior
- Compares a byte slice of `main_str` against `str` and returns a native compare-style integer.
- Negative offsets count from the end.
- Omitted length compares the full remainder of `main_str`.
- Negative lengths trim the comparison window from the end of `main_str`.
- Case-insensitive mode is ASCII-only and byte-oriented.
- If normalization yields no usable subject window, Prism++ compares an empty left slice.

## Compatibility table
- PHP supports negative offset and length normalization â†’ Prism++ keeps the same practical normalization direction â†’ kept
- PHP case-insensitive mode depends on byte comparison behavior â†’ Prism++ uses ASCII-only byte folding â†’ modified
- PHP emits warnings/errors in some edge conditions â†’ Prism++ uses deterministic empty-slice behavior â†’ modified

## Error policy
- No runtime error for valid typed inputs.
- Out-of-range or unusable windows degrade to the empty-slice compare policy.

## Runtime and wrapper split
- Runtime: normalize the compare window and perform byte/ASCII comparison.
- Wrapper: expose PHP-visible name only.

## Configuration visibility
- Implicitly available by project policy.

## Compile plan summary
- Implemented in `runtime/include/scpp/support/php.hpp`.
- Registered in `generators/php/specs/php_runtime_symbols_legacy.json`.

## Test matrix
- positive offset compare
- negative offset compare
- positive and negative length normalization
- case-insensitive ASCII compare
- empty-slice fallback
