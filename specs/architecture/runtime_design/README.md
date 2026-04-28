# Runtime Design Rules
Doc Status: normative
Status: active normative architecture support folder.

## Purpose

This folder contains runtime code-organization rules whose job is to prevent semantic drift.

These documents do not replace user-visible language semantics.
They define how runtime code must be organized so that normative behavior is implemented through one authoritative path instead of duplicated logic.

## Scope

This folder is the right place for rules such as:

- operator-family file ownership
- cast-family file ownership
- shared-helper reuse requirements
- semantic authority boundaries
- anti-fallback rules
- runtime dispatch centralization rules
- language-adapter forwarding rules
- spec traceability requirements for runtime helpers

## Current architectural model

The current runtime design model has two semantic layers:

1. shared Prism++ semantic families
   - namespace root: `scpp::`
   - file roots such as:
     - `runtime/include/operators/`
     - `runtime/include/casts/`

2. language adapter families
   - namespace roots such as `scpp::php::`
   - file roots such as:
     - `runtime/include/lang/php/operators/`
     - `runtime/include/lang/php/casts/`

Current rule:
- shared Prism++ semantic families are the real semantic authorities
- language layers expose stable frontend-facing entrypoints
- language entrypoints forward by default
- language-specific semantic overrides are allowed only when explicitly needed

Current examples already following this model:
- `scpp::coalesce_eval(...)` under `runtime/include/operators/coalesce/`
- `scpp::condition_truthy(...)` under `runtime/include/operators/conditional/`
- `scpp::ternary_eval(...)` under `runtime/include/operators/conditional/`
- `scpp::php::*` adapters that forward to those shared families for legacy PHP
- strict-profile visible names that lower directly to shared runtime families through the active profile registry
- `mixed_t` convenience methods such as `mixed_t::empty()` and `mixed_t::isset(...)` delegating to shared semantic authorities instead of owning behavior

Current carve-out:
- PHP-visible wrapper shaping in headers such as `runtime/include/lang/php/support/php_string.hpp` remains PHP-owned
- shared reusable string helper mechanics have now been promoted into the non-language runtime-owned `runtime/include/core/string_support.hpp`
- PHP-specific shaping should remain in `lang/php/*`, while reusable mechanics stay in shared runtime-owned code

## Placement rule

Runtime design-rule documents belong here under `specs/architecture/runtime_design/`.

They must not be added ad hoc into the main `runtime/` folder or scattered across unrelated top-level spec files.
This keeps runtime implementation folders focused on code and keeps design-governance material discoverable without flooding the runtime tree.
