# Builtin Contract - `dt_monotonic_ms`
Doc Status: normative

## Identity

- Name: `dt_monotonic_ms`
- Module/family: datetime
- Category/classification: Feature Runtime + Wrapper
- Status: experimental
- Source-language reference target: none; Simple C++ strict family surface
- Compatibility level: narrow

## Signature

- Supported form: `dt_monotonic_ms(): int`

## Behavior

- Returns a monotonic millisecond counter suitable for elapsed-time comparisons.
- The epoch is implementation-defined and must not be interpreted as wall-clock time.

## Error policy

- Does not return an error sentinel for ordinary operation.

## Runtime and wrapper split

- Runtime: `scpp::dt::monotonic_millis()`.
- Strict PHP++: direct symbol mapping to the runtime function.
- Legacy PHP: thin wrapper available as `php::dt_monotonic_ms()`.

## Configuration visibility

- Available when the `datetime` runtime module is enabled.
- New projects enable the module by default.

## Test matrix

- value after a short sleep is greater than or equal to the earlier value

