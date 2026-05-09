# Builtin Contract - `dt_parse`
Doc Status: normative

## Identity

- Name: `dt_parse`
- Module/family: datetime
- Category/classification: Feature Runtime + Wrapper
- Status: experimental
- Source-language reference target: Simple C++ strict family surface
- Compatibility level: narrow

## Signature

- Supported form: `dt_parse(string $value): result<int>`

## Behavior

- Strict PHP++ family-prefixed form of the common parser used by legacy `strtotime`.
- Supported input forms match `strtotime.md`.

## Error policy

- Invalid or unsupported input returns an error result.

## Runtime and wrapper split

- Runtime: `scpp::dt::parse_common_local()`.
- Strict PHP++: direct symbol mapping to the runtime function.
- Legacy PHP: use `strtotime()`.

## Configuration visibility

- Available when the `datetime` runtime module is enabled.

## Test matrix

- common local datetime parses and round-trips
- invalid date returns an error result

