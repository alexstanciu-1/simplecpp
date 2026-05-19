# Contract-First Helper Unification
Doc Status: planning

Date: 2026-05-19

Purpose:
- define the v1 direction for shared helper naming between `legacy` and `strict`
- prevent plain helper names from carrying mode-specific surprise branch semantics
- classify current helpers into contract-first shared names versus explicitly separate subsystem surfaces

## Status

This document is planning only.

It does not change current language, generator, runtime, or docs authority by itself.

If adopted, the resulting authoritative updates should be made in:

- `specs/php/library_profiles.md`
- relevant builtin contracts under `specs/builtins/`
- strict onboarding docs
- profile symbol registries

## Problem Statement

Recent helper-name simplification reduced friction in one area while creating a more serious friction point in another:

- plain PHP-like names became available in strict mode
- some of those names now exposed strict wrapper contracts rather than the legacy-visible branch model suggested by the shared name

This creates a trust problem.

Examples:

- `strpos(...)` looks like a PHP `int|false` helper
- `hex2bin(...)` looks like a PHP `string|false` helper

If strict uses those same names, authors expect the same branching habit.
They should not need to remember a hidden mode-specific wrapper contract for a familiar helper name.

## Policy Direction

For v1, shared helper naming should follow a contract-first rule.

### Core Rule

If `legacy` and `strict` expose the same source-facing helper name, they must expose the same user-visible success/failure contract.

This means:

- the same name should imply the same branching model in both profiles
- internal lowering may differ by profile
- visible control-flow expectations must not differ just because the active profile changed

### Internal Representation Rule

Strict may still use shared runtime helpers, wrappers, adapters, or stricter internal implementation forms.

That does not permit a shared source-facing helper name to change:

- `false`-sentinel behavior into `nullable<T>`
- `false`-sentinel behavior into `result<T>`
- ordinary value-returning behavior into wrapper-only consumption

### Separate-Surface Rule

When strict intentionally exposes a different contract from legacy, it should use a distinct strict-facing surface instead of reusing the same plain helper name.

Subsystem/domain helper families remain the normal place for this separation.

## Classification Rule

Current helpers should be grouped as follows.

### 1. Safe to unify under contract-first

These helpers can share names because their visible contract is ordinary and unsurprising:

- `substr`
- `substr_compare`
- `substr_replace`
- `str_replace`
- `str_pad`
- `implode`
- `bin2hex`
- `number_format`
- `strlen`
- `strtolower`
- `strtoupper`
- `lcfirst`
- `ucfirst`
- `trim`
- `ltrim`
- `rtrim`
- `count`
- `empty`
- `isset`
- `str_starts_with`
- `str_ends_with`

### 2. Must be adapted in strict to preserve legacy visible contract

These helpers may still share names, but strict must preserve the legacy-visible branch model:

- `strpos`
- `strrpos`
- `hex2bin`
- `explode`
- `shell_exec`

Policy intent:

- `strpos` and `strrpos` should remain author-visible `int|false`
- `hex2bin` should remain author-visible `string|false`
- `explode` should remain author-visible dynamic/PHP-like array result when exposed under the shared PHP name

### 3. Should remain separate/prefixed because they are subsystem APIs

These families should remain explicit strict subsystem surfaces:

- `fs_*`
- `io_*`
- `regex_*`
- `curl_*`
- `dt_*`

Current tolerated outlier:

- `json_*`

For v1, `json_*` may remain unchanged rather than reopening another naming transition.

## Documentation Consequence

Strict docs should teach the following story:

- plain shared helper names preserve the legacy-visible contract when the same name exists in both profiles
- subsystem helpers remain explicitly prefixed
- wrapper-oriented strict programming still exists, but shared helper names must not become semantic traps

## Implementation Consequence

If this policy is adopted, implementation work should:

1. update `specs/php/library_profiles.md` to require shared-name contract preservation
2. update strict onboarding docs to teach the shared-branching rule
3. adapt the strict symbol registry for shared helpers that currently violate the policy
4. add or reuse adapter-layer shims where strict runtime helpers need visible contract preservation
5. update strict tests to exercise the preserved branch model directly

## Relation To Earlier Naming Simplification

This planning note supersedes the decision direction in:

- `specs/planning/v1_helper_naming_simplification_2026_05_18.md`

The older note correctly identified the naming-friction problem.
This note replaces its solution direction with a more stable rule:

- not plain-name unification by itself
- plain-name unification only when the visible contract also stays aligned
