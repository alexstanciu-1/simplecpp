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

Local forms validate their fixed syntax and calendar components before host
conversion. The host selects DST with `tm_isdst = -1`: nonexistent local times may
normalize to a different wall-clock time, and the choice between repeated times
is host-dependent. No portable earliest/latest or gap-rejection policy is imposed.
A successful normalized input need not round-trip to its original local fields.

Unix timestamp `-1` is a valid value when the host can represent it; it must not be
rejected solely because `mktime` also uses it as a failure sentinel. Invalid shape,
embedded NUL, trailing data and out-of-range calendar components are rejected.
The UTC `Z` form delegates to the fixed UTC contract and ignores local DST.

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

