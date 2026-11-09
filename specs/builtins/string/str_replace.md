# Builtin Contract â€” `str_replace`
Doc Status: normative
## Identity
- Name: `str_replace`
- Module/family: string
- Category/classification: Replace-All Wrapper
- Status: experimental
- Source-language reference target: PHP `str_replace`
- Compatibility level: practical

## Signature
- Supported form: `str_replace(string $search, string $replace, string $subject): string`
- Accepted argument types: `string_t`, `string_t`, `string_t`
- Current scope: string-only form

## Behavior
- Replaces all non-overlapping occurrences of `search` inside `subject`.
- Replacement is byte-oriented and binary-safe.
- Empty `search` returns the original subject unchanged.

## v0.2 flavor forms

| Flavor | First match | All matches |
| --- | --- | --- |
| PHP | `str_replace_first(search, replacement, subject)` | `str_replace(search, replacement, subject)` |
| C++ | `subject.replace_first(search, replacement)` | `subject.replace_all(search, replacement)` |
| JS | `subject.replace(search, replacement)` | `subject.replaceAll(search, replacement)` |

Each form takes three strings including the receiver and returns an owning string.
Only the original subject bytes participate in the scan. Replacement bytes are literal,
including embedded NUL and sequences such as `$&`. An empty replacement deletes
matched bytes. An empty search or a missing match returns the original byte content.

## Compatibility table
- PHP supports array forms and count output â†’ Prism++ currently supports only the string-only form â†’ modified
- PHP replaces all non-overlapping matches â†’ Prism++ keeps the same practical direction â†’ kept

## Error policy
- No runtime error for valid typed inputs.

## Runtime and wrapper split
- The v0.2 runtime scans the subject with a first/all mode.
- Generated adapters select the mode and present arguments in flavor order.

## Configuration visibility
- Implicitly available by project policy.

## Compile plan summary

v0.2 registers the supported forms in [runtime strings metadata](../../../runtime/metadata/modules/strings.json),
using `modules/strings/strings.hpp`. The trim family uses separate fixed-arity
contracts, not synthesized default arguments. Overload selection follows the shared
exact-match rule; ordinary conversions apply after a unique fixed target is selected.
Older binding notes below describe the v0.1 implementation.

- Implemented in `runtime/include/scpp/support/php.hpp`.
- Registered in `generators/php/specs/php_runtime_symbols_legacy.json`.

## Test matrix
- no matches
- single-byte search
- multi-byte search
- empty search no-op
- repeated non-overlapping matches
