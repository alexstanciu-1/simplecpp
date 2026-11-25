# Builtin Contract - `dt_sleep_ms`
Doc Status: normative

## Identity

- Name: `dt_sleep_ms`
- Module/family: datetime
- Category/classification: Feature Runtime + Wrapper
- Status: experimental
- Source-language reference target: none; Simple C++ strict family surface
- Compatibility level: narrow

## Signature

- Supported form: `dt_sleep_ms(int $millis): void`
- Accepted argument types: int

## Behavior

- Suspends the current thread for approximately the requested number of milliseconds.
- Values less than or equal to zero return immediately.
- Scheduling precision is host-dependent.

## Error policy

- Does not return an error sentinel for ordinary operation.

## Runtime and wrapper split

- Runtime: `scpp::dt::sleep_millis()`.
- Strict PHP++: direct symbol mapping to the runtime function.
- Legacy PHP: thin wrapper available as `php::dt_sleep_ms()`.

## Configuration visibility

- Available when the `datetime` runtime module is enabled.
- New projects enable the module by default.

## Test matrix

- zero or negative sleep returns without error
- monotonic timestamp after a short positive sleep is greater than or equal to the earlier timestamp

