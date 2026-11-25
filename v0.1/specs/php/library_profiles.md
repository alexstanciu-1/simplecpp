# PHP Library Profiles
Doc Status: normative
Status: active
Scope: PHP library/profile selection for Prism++ / Simple C++

## 1. Purpose

This document defines the normative split between the two supported PHP library profiles:

- `legacy`
- `strict`

It is authoritative for:

- what each PHP profile exposes
- which profile may be active in a project
- how legacy and strict surfaces relate to shared runtime authority

## 2. Core Rule

Exactly one PHP library profile is active per project.

The active PHP profile is selected through project configuration under:

```json
{
  "runtime": {
    "languages": {
      "php": {
        "profile": "legacy"
      }
    }
  }
}
```

Supported values are:

- `legacy`
- `strict`

If no explicit PHP profile is selected and compatibility behavior is required, the project defaults to `legacy` as defined by `specs/project_build_v1.md`.

## 3. No Mixing Rule

Legacy and strict library profiles must not be mixed inside one project.

This means:

- the active project profile exposes exactly one PHP library surface
- mirrored legacy and strict symbols must not both be active in the same project
- profile-specific symbol registries define the available surface for the active project

## 4. Legacy Profile

The `legacy` profile exposes the PHP-legacy builtin and library surface.

Contract:

- visible source names use the PHP-legacy surface such as `file_get_contents`, `scandir`, `strlen`, `json_decode`
- emitted/runtime-relative symbols are registered through `generators/php/specs/php_runtime_symbols_legacy.json`
- the legacy surface routes through the PHP adapter layer under `scpp::php::*`
- legacy contracts preserve PHP-shaped names and PHP-compat result shapes where specified by the owning builtin contracts

The `legacy` profile is the current default project profile.

## 5. Strict Profile

The `strict` profile exposes the native non-legacy Simple C++ library surface for PHP authoring.

Contract:

- visible source names use plain PHP-like names for general language-adjacent helpers and family-prefixed names for subsystem/domain helpers, such as `fs_get`, `fs_scan`, `strlen`, `io_open`, `json_encode`
- visible strict names are registered through `generators/php/specs/php_runtime_symbols_strict.json`
- strict reusable capability lowers directly to shared runtime families such as:
  - `scpp::fs::*`
  - `scpp::str::*`
  - `scpp::io::*`
  - `scpp::json::*`
- this direct lowering is allowed only through the active strict profile registry, not by ad hoc generator rewriting

Some PHP-owned helper semantics may still remain routed through `scpp::php::*` in strict profile when no shared strict runtime replacement exists.

Examples include helper-style operations such as:

- `count`
- `empty`
- `isset`
- `take`
- `cli_argc`
- `cli_argv`
- `cli_args`
- `shell_exec`

## 6. Shared-Name Contract Rule

When `legacy` and `strict` expose the same source-facing PHP helper name, they must expose the same visible success/failure contract.

This rule applies to shared plain helper names such as:

- `strpos`
- `strrpos`
- `hex2bin`
- `explode`
- `implode`
- `strlen`
- `trim`

The same visible helper name must not silently change its user-visible branch model just because the active profile changed.

This means:

- the same shared name should imply the same visible branching habit in both profiles
- internal lowering may still differ by profile
- shared runtime implementation reuse is still allowed
- stricter internal machinery in `strict` does not permit the shared visible helper name to change contract shape

Examples of contract drift that are not allowed for the same shared helper name:

- `false|T` in one profile vs `nullable<T>` in the other
- `false|T` in one profile vs `result<T>` in the other
- ordinary value return in one profile vs wrapper-only consumption in the other

If strict intentionally wants a different visible contract, it should expose a different visible helper name or family rather than reusing the same plain PHP-facing helper name.

## 7. Carrier Rule For Shared Helper Unions

`mixed_t` must not be used as the default carrier for routine shared helper unions when the real visible contract is already known.

For shared PHP-facing helper names:

- `false|T` should use `result_or_false<T>`
- `bool|T` should use `result_or_bool<T>`

`mixed_t` remains appropriate for genuinely dynamic payloads.
It should not remain the default representation merely because the helper historically resembled PHP.

This rule applies to shared helper union contracts in both profiles.

## 8. Distinct Strict-Native Families

Strict-native subsystem/domain helper families exposed under distinct names do not need to mirror legacy helper contracts solely because they are available from PHP authoring.

Examples include:

- `fs_*`
- `io_*`
- `regex_*`
- `dt_*`
- `curl_*`

These families may use `result<T>`, plain values, or other strict-native contracts as defined by their own owning specs and public helper contracts.

They are not treated as "shared plain helper names" for the purpose of the shared-name contract rule.

## 9. Authority Rule

The strict profile does not create a second semantic runtime authority.

Authority remains:

- shared runtime families for reusable non-language-owned capability
- PHP adapter/runtime ownership for PHP-specific compatibility behavior

The strict visible API is a project profile exposure layer, not a separate semantic implementation stack.

## 10. Relationship Between Legacy and Strict

Where a reusable capability exists in both profiles:

- legacy may expose a PHP-legacy name and contract
- strict may expose a native Simple C++ name and stricter contract
- both may share one underlying runtime authority when the capability is reusable across surfaces

When the visible helper names differ, the visible contracts do not need to be identical.

It does require semantic ownership to remain explicit and non-duplicated.

When the visible helper names are the same, the shared-name contract rule in section 6 applies.

## 11. Registry Rule

Profile registries are normative within their machine-owned domain.

Current PHP profile registries are:

- `generators/php/specs/php_runtime_symbols_legacy.json`
- `generators/php/specs/php_runtime_symbols_strict.json`

These registries:

- define the surfaced symbols for the active profile
- must not invent semantics beyond the owning top-level and architecture specs
- must stay aligned with this document and the owning builtin contracts
