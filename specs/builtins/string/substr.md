# Builtin Contract - `substr`
Doc Status: normative

## Identity

- Name: `substr`
- Module/family: string
- Category/classification: Slice Wrapper
- Status: experimental
- Source-language reference target: PHP `substr`
- Compatibility level: practical

## Signature

- Supported forms:
  - `substr(string $value, int $offset): string`
  - `substr(string $value, int $offset, int $length): string`
- Accepted argument types: `string_t`, `int_t`, optional `int_t`

## Behavior

- Returns a substring selected by offset and optional length.
- Negative offsets count from the end.
- Omitted length returns the remainder of the string.
- Negative lengths trim the returned window from the end.
- Current slicing follows the runtime string codepoint-slice policy used by `string_t`.

## Compatibility table

- PHP offset and length normalization direction is preserved for the supported typed forms.
- PHP can return `false` for some broader invalid inputs; Simple C++ strict keeps this typed string-only contract deterministic.

## Error policy

- Does not return an error sentinel for valid typed inputs.

## Runtime and wrapper split

- Runtime: normalize start/end positions and return a `string_t` slice.
- Wrapper: expose PHP-visible name only.

## Configuration visibility

- Implicitly available by project policy.

## Test matrix

- positive offset
- positive offset with length
- negative offset
- negative length
