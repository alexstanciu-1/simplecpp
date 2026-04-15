# Runtime, Language, and Module Layering

Status: active normative architecture rule.

## Purpose

This document fixes the intended dependency and build composition model for the Prism++ / Simple C++ runtime.

The goal is to support:

- one non-language runtime core
- one or more language runtime layers
- one or more optional runtime modules
- future `scpp build` composition from selected languages and selected modules

## Required dependency direction

The allowed direction is:

```text
runtime core
  ↑
runtime modules
  ↑
language runtimes
```

Equivalent reading:

- `core/` must not depend on `lang/*`
- `modules/` must not depend on `lang/*`
- `lang/*` may depend on `core/` and `modules/`
- language-specific wrappers exist above the runtime, not below it

Hosts are runtime-owned entrypoint surfaces and may depend on:

- `core/`
- runtime modules
- selected language runtimes when the host is serving that language

## Public include model

### Non-language runtime umbrella

`runtime/include/scpp/runtime.hpp` is the non-language runtime umbrella.

It is for:

- core semantic types
- generic runtime helpers
- generic runtime-owned hosts and utilities
- generated operator surface

It must not expose language-specific headers.

### Language umbrellas

Each language must have its own explicit umbrella header.

Current example:

- `runtime/include/scpp/lang/php.hpp`

A language umbrella may include:

- `scpp/runtime.hpp`
- thin language wrappers
- language-specific support surfaces still required by generated code for that language

## Source tree intent

The intended runtime layout is:

```text
runtime/include/
  core/
  modules/
  hosts/
  lang/
```

Meaning:

- `core/` = language-agnostic runtime code
- `modules/` = optional reusable runtime subsystems
- `hosts/` = runtime-owned executable/serving entrypoint logic
- `lang/` = language-specific wrapper and support layers

## Module rules

A runtime module is reusable runtime functionality that is not owned by one language.

Examples already on this path:

- JSON → `modules/json/` → `namespace scpp::json`
- filesystem → `modules/filesystem/` → `namespace scpp::filesystem`

Module rules:

- modules should expose a runtime-owned namespace
- modules should not take their primary implementation from `lang/*`
- language layers should wrap modules lightly when source-language surface compatibility is needed
- module targets should be individually selectable by build/config

## Language-layer rules

Language layers should stay thin whenever the underlying behavior is reusable.

That means:

- reusable logic belongs in `core/` or `modules/`
- `lang/php/...` should prefer forwarding wrappers and PHP-shaped adapters
- broad catch-all files should be reduced over time into smaller PHP support buckets

This does not require every PHP-facing helper to become a runtime module.

Examples that may remain language-owned:

- PHP-only `echo` / stdio semantics
- PHP-only exception helpers
- PHP-only validation and lowering support that other languages will not reuse directly

## Build composition target

The intended long-term `scpp build` composition model is:

- base runtime core target
- selected language runtime target(s)
- selected runtime module target(s)

Illustrative shape:

```text
scpp_runtime
+ scpp_lang_php
+ scpp_json
+ scpp_filesystem
+ optional database module(s)
```

This document does not freeze the final CLI/config syntax for selecting languages/modules.
It freezes the architectural intent that build composition is explicit and layered.

## Current practical rule

When reorganizing runtime code:

1. prefer moving reusable logic down into `core/` or `modules/`
2. keep only thin wrappers or truly language-owned behavior in `lang/`
3. do not reintroduce `lang/*` dependencies into non-language runtime code
4. update specs/docs whenever the layering model changes


## Runtime build composition

`scpp build` now reads runtime composition from `prism.json` under:

```json
{
  "runtime": {
    "languages": ["php"],
    "modules": ["json", "filesystem", "mysqli"]
  }
}
```

Current default behavior keeps all known runtime modules active. Unsupported language or module names must fail clearly during build configuration.
