# Builtin Contract - `dt_format`
Doc Status: normative

## Identity

- Name: `dt_format`
- Module/family: datetime
- Category/classification: Feature Runtime + Wrapper
- Status: experimental
- Source-language reference target: Simple C++ strict family surface
- Compatibility level: narrow

## Signature

- Supported form: `dt_format(string $format, int $timestamp): string`

## Behavior

- Strict PHP++ family-prefixed form of the narrow common local formatter.
- Supported tokens match `date.md`.

## Error policy

- Throws a runtime error if the timestamp cannot be represented by the host local-time conversion API.

## Runtime and wrapper split

- Runtime: `scpp::dt::format_local()`.
- Strict PHP++: direct symbol mapping to the runtime function.
- Legacy PHP: use `date()`.

## Configuration visibility

- Available when the `datetime` runtime module is enabled.

## Test matrix

- common local datetime round-trips with `dt_parse`

