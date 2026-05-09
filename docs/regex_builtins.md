# Regex builtins - first pass
Doc Status: supporting

This page summarizes the first-pass Prism++ / Simple C++ regex builtin surface.

Regex is runtime-owned and lives under `namespace scpp::regex`. The PHP layer keeps only thin wrappers in `namespace scpp::php`.

## Header split

Regex support is intentionally kept out of the generic `php.cpp` / `php.hpp` area.
Use the dedicated headers instead:

- runtime module header: `runtime/include/modules/regex/regex.hpp`
- PHP wrapper header: `runtime/include/lang/php/php_regex.hpp`

## First-pass contract shape

- backend is PCRE2
- current module activation is opt-in
- current PCRE2 install expectation is manual host install
- JIT is used opportunistically when the installed PCRE2 reports usable runtime support
- strict and legacy PHP surfaces share one runtime-owned regex core
- shared runtime and strict-facing regex functions stay typed
- legacy `preg_*` wrappers adapt typed results into PHP-compatible `mixed_t` arrays where needed

## Surface split

- shared runtime and PHP strict use `scpp::regex::*`
- PHP legacy uses `scpp::php::preg_*`
- runtime and strict may use `vector_t<>` and typed `hash_t<>`
- only legacy may use PHP-compatible `mixed_t` array outputs

## Current strict/runtime entries

- `regex_jit_available`
- `regex_quote`
- `regex_match`
- `regex_match_named`
- `regex_match_all`
- `regex_match_all_pattern_order`
- `regex_match_all_named`
- `regex_grep`
- `regex_filter`
- `regex_replace`
- `regex_replace_callback`
- `regex_replace_callback_array`
- `regex_split`

## Current first-pass limits

- patterns must use delimited PCRE2 syntax like `"/foo/i"`
- supported modifiers are only `i`, `m`, `s`, `u`, `x`, `A`, `D`, and `U`
- `/u` enables PCRE2 UTF-8 mode and is tested with accented text and four-byte UTF-8 code points
- replacement strings support positional backreferences `$1`, `${1}`, and `\\1`
- `preg_match_all` supports `PREG_PATTERN_ORDER` and `PREG_SET_ORDER`
- `preg_match` and `preg_match_all` support byte offsets, including negative offsets counted from the end
- `preg_split` currently supports the `NO_EMPTY` and `DELIM_CAPTURE` flag bits, but not offset capture
- offset-capture and unmatched-as-null output forms raise `ValueError` because they are still deferred
- `preg_grep` and `preg_filter` preserve original keys in legacy mode
- legacy `preg_match` and `preg_match_all` include named capture entries when the pattern defines them
- `preg_replace_callback_array` currently uses a typed callback table, not a dynamic PHP array of callables
- JIT use is opportunistic and may be unavailable even when regex support itself is present

## Current implementation slice

- runtime module/build plumbing
- `preg_quote`
- `preg_match`
- `preg_match_all`
- `preg_grep`
- `preg_filter`
- `preg_replace`
- `preg_replace_callback`
- `preg_replace_callback_array`
- `preg_split`

## Planned regex builtin scope

- `preg_grep`
- `preg_filter`
- `preg_quote`
- `preg_match`
- `preg_match_all`
- `preg_replace`
- `preg_replace_callback`
- `preg_replace_callback_array`
- `preg_split`

For one-file-per-builtin contracts, see `specs/builtins/regex/`.
