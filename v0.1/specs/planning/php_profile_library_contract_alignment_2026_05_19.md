# PHP Profile Library Contract Alignment
Doc Status: planning

Date: 2026-05-19

Purpose:
- align the visible PHP helper contracts between `legacy` and `strict`
- reduce accidental dependence on `mixed_t` for routine helper union contracts
- define a contract-first carrier policy for shared PHP-facing helpers
- separate shared helper contracts from strict-native subsystem helper contracts

## Status

This document is planning only.

It does not change current language, generator, runtime, or docs authority by itself.

If adopted, the resulting authoritative updates should be made in:

- `specs/php/library_profiles.md`
- relevant builtin contracts under `specs/builtins/`
- profile symbol registries
- strict and legacy onboarding/docs
- runtime adapter declarations where the visible contract is currently ambiguous

## Problem Statement

The current PHP library surface has two related clarity problems:

- shared helper names across `legacy` and `strict` do not always imply the same visible branching contract
- `mixed_t` is still used in places where the real visible contract is not "dynamic value", but rather a routine helper union such as `false|T`

This creates drift in both implementation and author expectations.

Examples:

- `strpos(...)` is a familiar PHP-like helper name, so authors expect a stable `int|false` branching model
- direct strict lowering to `nullable<T>`-shaped helpers changes that expectation even when the visible name stays the same
- `mixed_t` obscures whether the real contract is:
  - genuinely dynamic data
  - `false|T`
  - `bool|T`
  - a strict-native `result<T>` contract

The project does not want `legacy` to mean "normal PHP runtime behavior".
It wants both PHP profiles to expose deliberate, explicit contracts inside the Prism++ model.

## Design Direction

For v1 cleanup, the PHP library surfaces should follow these principles:

### 1. Shared helper names imply shared visible contracts

If `legacy` and `strict` expose the same source-facing helper name, they should expose the same visible success/failure contract.

This applies even if:

- internal lowering differs
- the runtime implementation is shared
- strict has better typed internal machinery available

### 2. `mixed_t` should not be the default carrier for routine helper unions

`mixed_t` should remain available for genuinely dynamic data surfaces.

It should no longer be the default way to model helper contracts whose real shape is one of:

- `false|T`
- `bool|T`
- another explicit wrapper-shaped result contract

### 3. Shared PHP-facing helper unions should use wrapper carriers

When a shared helper's visible contract is a routine union, the visible carrier should be explicit.

Preferred policy:

- `false|T` -> `result_or_false<T>`
- `bool|T` -> `result_or_bool<T>`

### 4. Strict-native subsystem helpers may use strict-native contracts

Helpers that are already exposed under distinct subsystem/domain names do not need to mirror legacy helper contracts just because they are reachable from PHP authoring.

Examples include:

- `fs_*`
- `io_*`
- `regex_*`
- `dt_*`
- `curl_*`

Those helpers may use `result<T>` or other strict-native contracts as appropriate for their own visible surface.

### 5. Different visible contracts require different visible names

If strict intentionally wants a different branching model from a legacy/shared PHP helper, it should expose a different visible helper name rather than silently repurposing the same plain name.

## Policy Matrix

| Helper class | Visible names | Legacy carrier policy | Strict carrier policy | Alignment rule | Notes |
| --- | --- | --- | --- | --- | --- |
| Shared plain PHP-style helpers with `false\|T` contract | same in both profiles | `result_or_false<T>` | `result_or_false<T>` | must match across profiles | not `mixed_t`; not `nullable<T>` |
| Shared plain PHP-style helpers with `bool\|T` contract | same in both profiles | `result_or_bool<T>` | `result_or_bool<T>` | must match across profiles | use only where the bool branch is part of the real contract |
| Shared plain PHP-style helpers with ordinary value return | same in both profiles | plain value | plain value | must match across profiles | no wrapper needed |
| Shared plain PHP-style helpers with intentionally dynamic/PHP-like data return | same in both profiles | keep documented dynamic contract | keep same documented dynamic contract | must match across profiles | use `mixed_t` only when the payload is truly dynamic |
| Language-owned PHP semantic helpers | same in both profiles | centralized PHP-owned contract | centralized PHP-owned contract | centralized semantics | do not bypass semantic ownership with ad hoc direct lowering |
| Strict-native subsystem helpers | distinct strict-native names or families | separate/legacy-specific as applicable | `result<T>`, plain values, or other strict-native contract | no forced mirror when names differ | these are not "shared plain helper names" |

## Classification Guidance

The following categories express the current intended cleanup direction.

### 1. Shared helper names that should move to explicit wrapper contracts

These helpers should not use `mixed_t` merely to express a routine union shape:

- `strpos`
- `strrpos`
- `hex2bin`
- `shell_exec`

Any additional shared helper with the same routine `false|T` shape should be treated by the same rule.

### 2. Shared helper names that should remain ordinary value-returning helpers

These helpers can continue to use plain non-wrapper values because their visible contract is already ordinary:

- `strlen`
- `substr`
- `substr_compare`
- `substr_replace`
- `str_replace`
- `str_pad`
- `bin2hex`
- `number_format`
- `strtolower`
- `strtoupper`
- `lcfirst`
- `ucfirst`
- `trim`
- `ltrim`
- `rtrim`
- `str_starts_with`
- `str_ends_with`

### 3. Shared helper names that need case-by-case confirmation for dynamic data shape

These helpers should keep one shared visible contract across profiles, but may still intentionally carry dynamic/PHP-shaped data:

- `explode`
- `implode`

If they remain shared plain PHP helper names, that visible contract should match across `legacy` and `strict`.

### 4. Language-owned PHP semantic helpers

These helpers/operators remain centrally owned by the PHP semantic layer:

- `count`
- `empty`
- `isset`
- `take`
- truthiness helpers
- strict/loose comparison helpers where applicable
- coalesce/ternary wrapper-aware behavior

These should not be redefined implicitly by lower shared runtime families.

### 5. Strict-native subsystem helpers

These are already separate visible families and may use strict-native contracts:

- `fs_*`
- `io_*`
- `regex_*`
- `dt_*`
- `curl_*`

Current tolerated outlier:

- `json_*`

This note does not propose reopening `json_*` naming or contract policy unless a separate need appears.

## Meaning Of `legacy`

This cleanup direction explicitly rejects the idea that `legacy` should mean "normal PHP".

Instead:

- `legacy` remains a Prism++ PHP profile
- `legacy` may preserve PHP-like naming and familiar branch shapes where documented
- `legacy` should still use deliberate Prism++ wrapper contracts rather than defaulting to `mixed_t` for routine helper unions

This means the cleanup is not about making `legacy` more PHP-like.
It is about making both PHP profiles more explicit and more aligned.

## Immediate Consequences If Adopted

If this direction is adopted, follow-up work should:

1. update `specs/php/library_profiles.md` to require shared-name visible contract alignment
2. update builtin contracts that still describe `mixed_t` where the intended visible contract is actually `false|T` or `bool|T`
3. adapt profile symbol registries so shared plain helper names map to matching visible contracts in both profiles
4. update adapter/runtime declarations for shared helpers that currently expose ambiguous or mismatched carriers
5. audit docs/examples that currently teach patterns depending on `mixed_t` for routine helper unions
6. add or update tests for explicit branching behavior on shared helpers

## Non-Goals For This Note

This note does not yet:

- redefine the entire operator/control-flow family around wrappers
- fully reclassify every current PHP helper
- decide every individual dynamic-data helper contract
- perform the implementation audit

Those follow-up tasks should happen after the contract policy is accepted.

## Relation To Existing Planning Notes

This note extends and sharpens the direction from:

- `specs/planning/contract_first_helper_unification_2026_05_19.md`

Relationship:

- `contract_first_helper_unification_2026_05_19.md` focused on shared-name contract preservation and naming clarity
- this note adds the carrier policy needed to align `legacy` and `strict` on wrapper shape rather than relying on `mixed_t`

This note also narrows the earlier naming-simplification direction:

- plain shared names are still desirable where appropriate
- but plain-name unification is only safe when the visible contract is also aligned

## Recommended Next Step

Use this note as the planning basis for a focused normative update to:

- `specs/php/library_profiles.md`

Then perform a targeted audit of:

- `generators/php/specs/php_runtime_symbols_legacy.json`
- `generators/php/specs/php_runtime_symbols_strict.json`
- shared PHP adapter declarations under `runtime/include/lang/php/`
- builtin contracts for helpers currently using `mixed_t` as a routine helper-union carrier
