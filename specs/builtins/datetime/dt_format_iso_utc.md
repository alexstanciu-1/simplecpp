# Builtin Contract - `dt_format_iso_utc`
Doc Status: normative

## Identity

- Name: `dt_format_iso_utc`
- Module/family: datetime
- Category/classification: Feature Runtime + Wrapper
- Status: experimental
- Source-language reference target: none; Simple C++ strict family surface
- Compatibility level: narrow

## Signature

- Supported form: `dt_format_iso_utc(int $unixSeconds): string`
- Accepted argument types: int

## Behavior

- Formats Unix seconds as UTC text in exactly `YYYY-MM-DDTHH:MM:SSZ` form.
- Locale and host timezone do not affect the output.

## Error policy

- Throws a runtime error if the timestamp cannot be represented by the host UTC conversion API.

## Runtime and wrapper split

- Runtime: `scpp::dt::format_iso_utc()`.
- Strict PHP++: direct symbol mapping to the runtime function.
- Legacy PHP: thin wrapper available as `php::dt_format_iso_utc()`.

## Configuration visibility

- Available when the `datetime` runtime module is enabled.
- New projects enable the module by default.

## Test matrix

- Unix timestamp `0` formats as `1970-01-01T00:00:00Z`
- leap-day timestamp round-trips through parse and format

