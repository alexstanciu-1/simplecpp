# Builtin Contract - `dt_parse_iso_utc`
Doc Status: normative

## Identity

- Name: `dt_parse_iso_utc`
- Module/family: datetime
- Category/classification: Feature Runtime + Wrapper
- Status: experimental
- Source-language reference target: none; Simple C++ strict family surface
- Compatibility level: narrow

## Signature

- Supported form: `dt_parse_iso_utc(string $value): result<int>`
- Accepted argument types: string

## Behavior

- Accepts exactly `YYYY-MM-DDTHH:MM:SSZ`.
- Returns Unix seconds for valid UTC values.
- Rejects malformed values and out-of-range date/time components.
- Leap seconds are not supported in this pass.

## Error policy

- Invalid input returns an error result.

## Runtime and wrapper split

- Runtime: `scpp::dt::parse_iso_utc()`.
- Strict PHP++: direct symbol mapping to the runtime function.
- Legacy PHP: thin wrapper available as `php::dt_parse_iso_utc()`.

## Configuration visibility

- Available when the `datetime` runtime module is enabled.
- New projects enable the module by default.

## Test matrix

- epoch parses to `0`
- leap-day value parses and round-trips
- invalid day, hour, and shape return error

