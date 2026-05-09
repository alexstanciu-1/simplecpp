# Builtin Contract - `strtotime`
Doc Status: normative

## Identity

- Name: `strtotime`
- Module/family: datetime
- Category/classification: Feature Runtime + Wrapper
- Status: experimental
- Source-language reference target: PHP `strtotime`
- Compatibility level: narrow

## Signature

- Supported form: `strtotime(string $value): result_or_false<int>`
- Accepted argument types: string

## Behavior

- Parses these common forms:
  - `YYYY-MM-DD`
  - `YYYY-MM-DD HH:MM:SS`
  - `YYYY-MM-DDTHH:MM:SS`
  - `YYYY-MM-DDTHH:MM:SSZ`
- Non-UTC forms are interpreted as local wall-clock time through the host C/C++ runtime.
- The `Z` form is interpreted as UTC through `dt_parse_iso_utc`.
- Relative expressions such as `next Tuesday`, `+1 day`, and natural language text are not supported in this pass.

## Error policy

- Invalid or unsupported input returns `false`.

## Runtime and wrapper split

- Runtime: `scpp::dt::parse_common_local()`.
- Legacy PHP: thin wrapper available as `php::strtotime()`.
- Strict PHP++ uses `dt_parse()` which returns `result<int>`.

## Configuration visibility

- Available when the `datetime` runtime module is enabled.
- New projects enable the module by default.

## Test matrix

- common local datetime parses and round-trips through `date`
- invalid day returns `false`
- unsupported natural-language input returns `false`

