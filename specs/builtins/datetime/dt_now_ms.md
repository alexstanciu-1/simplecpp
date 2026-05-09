# Builtin Contract - `dt_now_ms`
Doc Status: normative

## Identity

- Name: `dt_now_ms`
- Module/family: datetime
- Category/classification: Feature Runtime + Wrapper
- Status: experimental
- Source-language reference target: none; Simple C++ strict family surface
- Compatibility level: narrow

## Signature

- Supported form: `dt_now_ms(): int`

## Behavior

- Returns the current Unix timestamp in UTC milliseconds.
- The value is wall-clock time and may move if the host system clock changes.

## Error policy

- Does not return an error sentinel for ordinary operation.

## Runtime and wrapper split

- Runtime: `scpp::dt::now_unix_millis()`.
- Strict PHP++: direct symbol mapping to the runtime function.
- Legacy PHP: thin wrapper available as `php::dt_now_ms()`.

## Configuration visibility

- Available when the `datetime` runtime module is enabled.
- New projects enable the module by default.

## Test matrix

- returned millisecond timestamp is positive on current hosts
- millisecond timestamp is at least current seconds multiplied by 1000

