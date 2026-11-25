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
- Supported three-argument form: `str_replace(string $search, string $replace, string $subject): string`
- PHP also accepts a fourth argument: an existing writable int/int64 variable bound by reference.
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

Each form takes three string inputs including any receiver and returns an owning string.
PHP replace-all optionally overwrites a fourth argument with the number of non-overlapping
matches in the original subject, including identical replacements. Empty search or no match
writes zero. The count is committed after successful result construction. Locals, parameters
and explicit globals of exact int64 storage can bind; the call does not declare a missing variable.
Only the original subject bytes participate in the scan. Replacement bytes are literal,
including embedded NUL and sequences such as `$&`. An empty replacement deletes
matched bytes. An empty search or a missing match returns the original byte content.

## Compatibility table
- PHP array forms remain deferred.
- PHP replaces all non-overlapping matches â†’ Prism++ keeps the same practical direction â†’ kept

## Error policy
- No runtime error for valid typed inputs.

## Runtime and wrapper split
- The v0.2 runtime scans the subject with a first/all mode.
- Generated adapters select the mode and present arguments in flavor order.

## Configuration visibility
- Implicitly available by project policy.

## Compile plan summary

v0.2 declares the optional PHP reference in [runtime metadata](../../../v0.2/runtime/metadata/runtime.json).
The counted overload traverses the original subject once. The three-argument overload
uses the same scan with counting disabled.

Older binding notes below describe the v0.1 implementation.

- Implemented in `runtime/include/scpp/support/php.hpp`.
- Registered in `generators/php/specs/php_runtime_symbols_legacy.json`.

## Test matrix
- no matches
- single-byte search
- multi-byte search
- empty search no-op
- repeated non-overlapping matches
