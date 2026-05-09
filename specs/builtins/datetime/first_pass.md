# Datetime builtins - first pass
Doc Status: normative

## Scope

The first datetime pass exposes a small UTC and clock surface backed by the reusable runtime module `scpp::dt`.

This pass intentionally excludes:

- named timezone conversion
- locale-aware formatting
- calendar arithmetic beyond UTC ISO parsing and formatting
- PHP `DateTime` object compatibility

## Runtime Surface

The reusable runtime namespace is `scpp::dt`.

Supported functions:

- `now_unix_seconds(): int`
- `now_unix_millis(): int`
- `monotonic_millis(): int`
- `sleep_millis(int $millis): void`
- `format_iso_utc(int $unixSeconds): string`
- `parse_iso_utc(string $value): result<int>`
- `format_local(string $format, int $unixSeconds): string`
- `format_local_now(string $format): string`
- `parse_common_local(string $value): result<int>`

`format_iso_utc` emits `YYYY-MM-DDTHH:MM:SSZ`.
`parse_iso_utc` accepts exactly that UTC form and returns an error result for invalid shape or out-of-range date/time components.
`format_local` implements a narrow PHP-style token subset: `Y`, `y`, `m`, `n`, `d`, `j`, `H`, `G`, `i`, `s`, and `U`.
`parse_common_local` accepts `YYYY-MM-DD`, `YYYY-MM-DD HH:MM:SS`, `YYYY-MM-DDTHH:MM:SS`, and the strict ISO UTC form accepted by `parse_iso_utc`.

## PHP Surfaces

Strict PHP++ exposes direct family-prefixed names:

- `dt_now`
- `dt_now_ms`
- `dt_monotonic_ms`
- `dt_sleep_ms`
- `dt_format_iso_utc`
- `dt_parse_iso_utc`
- `dt_format`
- `dt_format_now`
- `dt_parse`

Legacy PHP exposes thin wrappers for the same names and additionally exposes:

- `time()`
- `date(string $format): string`
- `date(string $format, int $timestamp): string`
- `strtotime(string $value): result_or_false<int>`

## Platform Notes

The first pass depends only on standard C++ chrono/time facilities and avoids timezone databases. It is intended to be portable across Windows, macOS/iOS, Android, and Linux/Debian toolchains that support the project C++ standard.
