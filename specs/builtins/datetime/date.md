# Builtin Contract - `date`
Doc Status: normative

## Identity

- Name: `date`
- Module/family: datetime
- Category/classification: Feature Runtime + Wrapper
- Status: experimental
- Source-language reference target: PHP `date`
- Compatibility level: narrow

## Signature

- Supported forms:
  - `date(string $format): string`
  - `date(string $format, int $timestamp): string`
- Accepted argument types: string format, optional int Unix timestamp

## Behavior

- Formats local wall-clock time using a narrow PHP-compatible token subset.
- Supported tokens: `Y`, `y`, `m`, `n`, `d`, `j`, `H`, `G`, `i`, `s`, and `U`.
- Unknown format characters are copied literally.
- Backslash escapes the next character.
- Without a timestamp, the current Unix seconds value is used.

## Error policy

- Throws a runtime error if the timestamp cannot be represented by the host local-time conversion API.

## Runtime and wrapper split

- Runtime: `scpp::dt::format_local()` and `scpp::dt::format_local_now()`.
- Legacy PHP: thin wrapper available as `php::date()`.
- Strict PHP++ uses `dt_format()` and `dt_format_now()` rather than the PHP name.

## Configuration visibility

- Available when the `datetime` runtime module is enabled.
- New projects enable the module by default.

## Test matrix

- `date("Y-m-d H:i:s", strtotime("2024-02-29 12:34:56"))` round-trips
- invalid `strtotime` input returns `false`

