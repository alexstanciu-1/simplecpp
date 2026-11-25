# Regex builtins - first pass
Doc Status: normative

This page defines the first-pass Prism++ / Simple C++ regex builtin intake scope.

Regex is runtime-owned and lives under `namespace scpp::regex`. The PHP layer keeps thin wrappers in `namespace scpp::php`.

## Header split

Regex support is intentionally kept out of the generic `php.cpp` / `php.hpp` area.
Use the dedicated headers instead:

- runtime module header: `runtime/include/modules/regex/regex.hpp`
- PHP wrapper header: `runtime/include/lang/php/php_regex.hpp`

## First-pass module policy

- backend: PCRE2 8-bit API
- module name: `regex`
- strict reusable namespace root: `scpp::regex`
- current build policy: opt-in only
- current dependency policy: manual host install of PCRE2 development files is required
- missing-module diagnostic policy: strict source/build should report regex helper use without the `regex` runtime module before native link
- current JIT policy: opportunistic runtime use when PCRE2 reports usable JIT support
- shared runtime policy: typed-only, no `mixed_t`
- strict PHP policy: typed-only, no `mixed_t`
- legacy PHP policy: PHP-compatible array outputs may use `mixed_t`

## Current intake scope

- `preg_jit_available`
- `preg_grep`
- `preg_filter`
- `preg_quote`
- `preg_match`
- `preg_match_all`
- `preg_replace`
- `preg_replace_callback`
- `preg_replace_callback_array`
- `preg_split`

## Current implementation slice

- build/config/runtime module plumbing for opt-in regex support
- shared runtime JIT availability probe with opportunistic fallback use
- shared runtime quote helper
- strict registry exposure for `regex_*`
- PHP wrapper exposure for `preg_quote`
- shared PCRE2 compile/execute helpers
- PHP wrapper exposure for `preg_match`
- PHP wrapper exposure for `preg_match_all`
- PHP wrapper exposure for `preg_grep`
- PHP wrapper exposure for `preg_filter`
- PHP wrapper exposure for `preg_replace`
- PHP wrapper exposure for `preg_replace_callback`
- PHP wrapper exposure for `preg_replace_callback_array`
- PHP wrapper exposure for `preg_split`

## Typed strict surface

- `regex_jit_available(): bool_t`
- `regex_quote(string $text): string_t`
- `regex_quote(string $text, string $delimiter): string_t`
- `regex_match(string $pattern, string $subject): result_or_false<vector_t<string_t>>`
- `regex_match(string $pattern, string $subject, int_t $offset): result_or_false<vector_t<string_t>>`
- `regex_match_named(string $pattern, string $subject): result_or_false<hash_t<string_t, string_t>>`
- `regex_match_named(string $pattern, string $subject, int_t $offset): result_or_false<hash_t<string_t, string_t>>`
- `regex_match_all(string $pattern, string $subject): result_or_false<vector_t<vector_t<string_t>>>`
- `regex_match_all(string $pattern, string $subject, int_t $offset): result_or_false<vector_t<vector_t<string_t>>>`
- `regex_match_all_pattern_order(string $pattern, string $subject): result_or_false<vector_t<vector_t<string_t>>>`
- `regex_match_all_pattern_order(string $pattern, string $subject, int_t $offset): result_or_false<vector_t<vector_t<string_t>>>`
- `regex_match_all_named(string $pattern, string $subject): result_or_false<vector_t<hash_t<string_t, string_t>>>`
- `regex_match_all_named(string $pattern, string $subject, int_t $offset): result_or_false<vector_t<hash_t<string_t, string_t>>>`
- `regex_grep(string $pattern, vector_t<string_t> $input): result_or_false<vector_t<string_t>>`
- `regex_filter(string $pattern, string $replacement, vector_t<string_t> $input): result_or_false<vector_t<string_t>>`
- `regex_filter(string $pattern, string $replacement, vector_t<string_t> $input, int_t $limit): result_or_false<vector_t<string_t>>`
- `regex_replace(string $pattern, string $replacement, string $subject): result_or_false<string_t>`
- `regex_replace(string $pattern, string $replacement, string $subject, int_t $limit): result_or_false<string_t>`
- `regex_replace_callback(string $pattern, function<string_t(vector_t<string_t>)> $callback, string $subject): result_or_false<string_t>`
- `regex_replace_callback(string $pattern, function<string_t(vector_t<string_t>)> $callback, string $subject, int_t $limit): result_or_false<string_t>`
- `regex_replace_callback_array(hash_t<function<string_t(vector_t<string_t>)>, string_t> $callbacks, string $subject): result_or_false<string_t>`
- `regex_replace_callback_array(hash_t<function<string_t(vector_t<string_t>)>, string_t> $callbacks, string $subject, int_t $limit): result_or_false<string_t>`
- `regex_split(string $pattern, string $subject): result_or_false<vector_t<string_t>>`
- `regex_split(string $pattern, string $subject, int_t $limit): result_or_false<vector_t<string_t>>`

## First-pass compatibility limits

- pattern input is always a delimited PCRE2-style string such as `"/ab+/i"`
- supported modifiers are only `i`, `m`, `s`, `u`, `x`, `A`, `D`, and `U`
- `/u` enables PCRE2 UTF-8 mode and is tested with accented text and four-byte UTF-8 code points
- replacement strings support positional backreferences `$1`, `${1}`, and `\\1`
- no offset-capture output forms yet
- split flags currently support `PREG_SPLIT_NO_EMPTY` and `PREG_SPLIT_DELIM_CAPTURE`, but not offset capture
- match-all flags currently support `PREG_PATTERN_ORDER` and `PREG_SET_ORDER`, but not offset capture or unmatched-as-null
- match and match-all offset parameters are supported as byte offsets; negative offsets count back from the end
- unsupported output-shape flags raise `ValueError` with a "not supported by the regex module yet" message
- count-output parameters are supported for replacement/filter callback paths
- `preg_grep` and `preg_filter` currently preserve original keys in legacy mode
- legacy `preg_match` and `preg_match_all` include named capture entries when the pattern defines them
- `preg_replace_callback_array` currently narrows the callback table to typed `hash_t<function<...>, string_t>` entries rather than a dynamic PHP array of callables
- JIT is opportunistic in this pass and falls back silently to interpreted matching when unavailable
