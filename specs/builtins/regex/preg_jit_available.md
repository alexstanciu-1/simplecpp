# Builtin Contract - `preg_jit_available`
Doc Status: normative

## Identity
- Name: `preg_jit_available`
- Module/family: regex
- Category/classification: Feature Runtime + Wrapper
- Status: experimental
- Source-language reference target: Prism++ regex runtime capability probe
- Compatibility level: Prism++

## Signature
- Shared runtime / strict typed core:
  - `regex_jit_available(): bool_t`
- Legacy PHP wrapper:
  - `preg_jit_available(): bool_t`

## Behavior
- Returns `true` when the loaded PCRE2 runtime reports JIT support and a trivial JIT compilation probe succeeds in the current process.
- Returns `false` when JIT support is not compiled into PCRE2 or cannot be used in the current runtime environment.
- Regex matching and replacement remain valid when this function returns `false`; the engine falls back to normal interpreted matching.

## Compatibility table
- PHP has no direct `preg_*` builtin for this capability probe -> Prism++ adds an explicit runtime helper -> added

## Error policy
- Does not throw for ordinary runtime probing.

## Runtime and wrapper split
- Runtime: detect whether opportunistic JIT can be used safely in the current process.
- Legacy wrapper: expose the PHP-visible helper name only.

## Configuration visibility
- Available only when the opt-in `regex` runtime module is enabled for the project.

## Compile plan summary
- Shared runtime support lives in `runtime/include/modules/regex/regex.hpp` and `runtime/include/modules/regex/regex.cpp`.
- PHP wrapper exposure lives in `runtime/include/lang/php/php_regex.hpp`.
- Registered in both strict and legacy runtime symbol maps.

## Test matrix
- returns a stable boolean result
- legacy wrapper mirrors the runtime result
