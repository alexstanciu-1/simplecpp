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

- visible source names use the strict flat family-prefixed surface such as `fs_get`, `fs_scan`, `str_strlen`, `io_open`, `json_encode`
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

## 6. Authority Rule

The strict profile does not create a second semantic runtime authority.

Authority remains:

- shared runtime families for reusable non-language-owned capability
- PHP adapter/runtime ownership for PHP-specific compatibility behavior

The strict visible API is a project profile exposure layer, not a separate semantic implementation stack.

## 7. Relationship Between Legacy and Strict

Where a reusable capability exists in both profiles:

- legacy may expose a PHP-legacy name and contract
- strict may expose a native Simple C++ name and stricter contract
- both may share one underlying runtime authority when the capability is reusable across surfaces

This does not require the visible contracts to be identical.

It does require semantic ownership to remain explicit and non-duplicated.

## 8. Registry Rule

Profile registries are normative within their machine-owned domain.

Current PHP profile registries are:

- `generators/php/specs/php_runtime_symbols_legacy.json`
- `generators/php/specs/php_runtime_symbols_strict.json`

These registries:

- define the surfaced symbols for the active profile
- must not invent semantics beyond the owning top-level and architecture specs
- must stay aligned with this document and the owning builtin contracts
