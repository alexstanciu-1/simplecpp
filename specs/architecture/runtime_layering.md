# Runtime, Language, and Module Layering
Doc Status: normative
Status: active normative architecture rule.

## Purpose

This document fixes the intended dependency and build composition model for the Prism++ / Simple C++ runtime.

The goal is to support:

- one non-language runtime core
- one shared semantic layer for current Prism++ base families
- one or more language runtime adapter layers
- one or more optional runtime modules
- future `scpp build` composition from selected languages and selected modules

## Required dependency direction

The allowed direction is:

```text
runtime core
  ^
shared semantic families
  ^
runtime modules
  ^
language adapters
```

Equivalent reading:

- `core/` must not depend on `lang/*`
- shared semantic family roots such as `operators/` and `casts/` must not depend on `lang/*`
- `modules/` must not depend on `lang/*`
- `lang/*` may depend on shared runtime roots and modules
- language-specific wrappers/adapters exist above the runtime, not below it

Hosts are runtime-owned entrypoint surfaces and may depend on:

- `core/`
- shared semantic families
- runtime modules
- selected language runtimes when the host is serving that language

## Public include model

### Non-language runtime umbrella

`runtime/include/scpp/runtime.hpp` is the non-language runtime umbrella.

It is for:

- core semantic types
- generic runtime helpers
- generic runtime-owned hosts and utilities
- shared semantic operator/cast surface

It must not expose language-specific headers.

It is also the intended public umbrella for shared Prism++ semantic families such as:

- shared operator-family authorities
- shared cast-family authorities

### Language umbrellas

Each language must have its own explicit umbrella header.

Current example:

- `runtime/include/scpp/lang/php.hpp`

A language umbrella may include:

- `scpp/runtime.hpp`
- thin language wrappers
- thin language operator/cast adapters
- language-specific support surfaces still required by generated code for that language

## Source tree intent

The intended runtime layout is:

```text
runtime/include/
  core/
  operators/
  casts/
  modules/
  hosts/
  lang/
```

Meaning:

- `core/` = language-agnostic runtime code
- `operators/` = shared Prism++ semantic operator families
- `casts/` = shared Prism++ semantic cast families
- `modules/` = optional reusable runtime subsystems
- `hosts/` = runtime-owned executable/serving entrypoint logic
- `lang/` = language-specific adapter, wrapper, and support layers

## Module rules

A runtime module is reusable runtime functionality that is not owned by one language.

Examples already on this path:

- JSON -> `modules/json/` -> `namespace scpp::json`
- filesystem -> `modules/filesystem/` -> `namespace scpp::fs`
- regex -> `modules/regex/` -> `namespace scpp::regex`
- shared string family -> runtime-owned `namespace scpp::str`
- shared stdio/resource I/O family -> runtime-owned `namespace scpp::io`

Module rules:

- modules should expose a runtime-owned namespace
- modules should not take their primary implementation from `lang/*`
- language layers should wrap modules lightly when source-language surface compatibility is needed
- module targets should be individually selectable by build/config

## Language-layer rules

Language layers should stay thin whenever the underlying behavior is reusable.

That means:

- reusable semantic logic belongs in shared runtime roots such as `operators/`, `casts/`, `core/`, or `modules/`
- `lang/php/...` should prefer forwarding adapters and thin PHP-shaped entry surfaces
- language adapters remain the stable generator-facing surface even when the real semantic authority lives in shared `scpp::*`
- broad catch-all files should be reduced over time into smaller family-owned buckets

This does not require every language-facing helper to become a shared runtime family.

Examples that may remain language-owned:

- PHP-only `echo` / stdio semantics
- PHP-only exception helpers
- PHP-only validation and lowering support that other languages will not reuse directly

## Generator-facing rule

Generated/frontend-facing code must follow the active language-profile surface.

Current PHP rule:

- legacy PHP generated/frontend-facing code targets language entrypoints such as `scpp::php::*`
- strict PHP generated/frontend-facing code may target shared runtime families such as `scpp::fs::*`, `scpp::str::*`, `scpp::io::*`, and `scpp::json::*` directly when those symbols are declared by the active strict profile registry
- regex follows the same rule through `scpp::regex::*` once strict profile symbols are registered

Direct shared-family calls from generated code are allowed only through the active profile registry and must not be introduced ad hoc.

Language entrypoints remain required for:

- legacy PHP surfaces
- PHP-owned semantics
- helper/support behavior that is not owned by a shared runtime family

## Build composition target

The intended long-term `scpp build` composition model is:

- base runtime core target
- shared semantic family target(s)
- selected language runtime target(s)
- selected runtime module target(s)

Illustrative shape:

```text
scpp_runtime
+ scpp_operators
+ scpp_casts
+ scpp_lang_php
+ scpp_json
+ scpp_filesystem
+ scpp_regex
+ optional database module(s)
```

This document does not freeze the final CLI/config syntax for selecting languages/modules.
It freezes the architectural intent that build composition is explicit and layered.

## Current practical rule

When reorganizing runtime code:

1. prefer moving reusable semantic logic down into shared roots such as `operators/`, `casts/`, `core/`, or `modules/`
2. keep only thin adapters or truly language-owned behavior in `lang/`
3. do not reintroduce `lang/*` dependencies into non-language runtime code
4. update specs/docs whenever the layering model changes

## Runtime build composition

`scpp build` now reads runtime composition from `prism.json` under:

```json
{
  "runtime": {
    "languages": {
      "php": {
        "profile": "legacy"
      }
    },
    "modules": ["json", "filesystem", "mysqli"]
  }
}
```

Legacy list-style `runtime.languages` remains accepted as a compatibility shape and defaults PHP to profile `legacy`.

Current default behavior enables the `json` and `filesystem` runtime modules. `mysqli` and `regex` remain opt-in. Unsupported language or module names must fail clearly during build configuration.
