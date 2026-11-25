# Datetime builtins - first pass
Doc Status: supporting

Datetime is runtime-owned and lives under `namespace scpp::dt`.
The PHP layer keeps only thin wrappers in `namespace scpp::php`.

## Runtime files

- runtime module header: `runtime/include/modules/datetime/datetime.hpp`
- public runtime umbrella: `runtime/include/scpp/datetime.hpp`
- PHP wrapper header: `runtime/include/lang/php/php_datetime.hpp`

## Implemented functions

Strict PHP++:

- `dt_now`
- `dt_now_ms`
- `dt_monotonic_ms`
- `dt_sleep_ms`
- `dt_format_iso_utc`
- `dt_parse_iso_utc`
- `dt_format`
- `dt_format_now`
- `dt_parse`

Legacy PHP additionally exposes:

- `time`
- `date`
- `strtotime`

The first pass is UTC/epoch plus common local formatting/parsing. `date()` supports the common numeric tokens `Y`, `y`, `m`, `n`, `d`, `j`, `H`, `G`, `i`, `s`, and `U`. `strtotime()` supports `YYYY-MM-DD`, `YYYY-MM-DD HH:MM:SS`, `YYYY-MM-DDTHH:MM:SS`, and `YYYY-MM-DDTHH:MM:SSZ`.
