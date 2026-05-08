# Regex builtin contracts
Doc Status: normative

This folder contains one contract file per regex builtin in the first-pass Prism++ / Simple C++ regex surface.

Regex follows the shared runtime layering rule:

- shared runtime lives in `namespace scpp::regex`
- strict PHP may call typed `scpp::regex::*` entries directly through the strict runtime symbol registry
- legacy PHP calls `scpp::php::preg_*` wrappers
- only the legacy wrapper layer may adapt outputs into PHP-compatible `mixed_t` arrays
- runtime capability probes such as JIT availability live in the same family

## Current first-pass intake scope

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

See also: `specs/builtins/regex/first_pass.md`.
