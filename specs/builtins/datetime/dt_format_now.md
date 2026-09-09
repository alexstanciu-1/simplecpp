# Builtin Contract - `dt_format_now`
Doc Status: normative

## Identity

- Name: `dt_format_now`
- Module/family: datetime
- Category/classification: Feature Runtime + Wrapper
- Status: experimental
- Source-language reference target: Simple C++ strict family surface
- Compatibility level: narrow

## Signature

- Supported form: `dt_format_now(string $format): string`
- Accepted argument types: string format

## Behavior

- Strict PHP++ family-prefixed form of the narrow common local formatter using the current Unix seconds value.
- Supported tokens match `date.md`.
- The timestamp source is wall-clock time and may move if the host system clock changes.

## Error policy

- Throws a runtime error if the current timestamp cannot be represented by the host local-time conversion API.

## Runtime and wrapper split

- Runtime: `scpp::dt::format_local_now()`.
- Strict PHP++: direct symbol mapping to the runtime function.
- Legacy PHP: use `date(string $format)`.

## Configuration visibility

- Available when the `datetime` runtime module is enabled.
- New projects enable the module by default.

## Test matrix

- common local datetime format emits the supported token subset
