# V1 Helper Naming Simplification
Doc Status: planning

Date: 2026-05-18

Purpose:
- record the current design direction for simplifying helper naming in v1
- remove the current split between plain PHP-like helper names and strict-prefixed aliases for general language-adjacent helpers
- define which helper families stay prefixed in v1
- provide a planning basis for resolving GitHub issue `#122`

## Status

This document is planning only.

It does not change current language, generator, runtime, or docs authority by itself.

If adopted, the resulting authoritative updates should be made in the owning normative docs and generator/runtime symbol registries.

## Background

Current repository behavior and docs are inconsistent.

Observed current split:

- the strict quick-learn teaches prefixed names such as `str_trim`, `str_substr`, and `str_explode`
- the broader runtime/builtin docs continue to describe plain PHP-like names such as `trim`, `substr`, and `explode`
- strict code currently rejects many unqualified plain helper names because the strict symbol registry maps only the prefixed forms

This creates an unnecessary v1 complexity cost:

- author expectations drift from actual strict behavior
- docs and examples disagree with each other
- issue `#122` becomes an easy trap rather than a meaningful language design guardrail

## Decision Direction

For v1, general language-adjacent string and array-like helpers should:

- be unprefixed
- retain their familiar PHP-like names
- use plain names only rather than supporting duplicate prefixed aliases

This is an intentional simplification for the current project stage.

Rationale:

- there is currently only one downstream project using Simple C++
- the codebase is still small enough for direct cleanup
- reducing naming duplication is more valuable than preserving both surfaces
- ordinary authoring should feel natural where the helper is language-adjacent rather than subsystem-oriented

## Core Rule

### 1. General language-adjacent helpers

General language-adjacent helpers should use unprefixed PHP-like names in v1.

Representative examples:

- `strlen`
- `strpos`
- `strrpos`
- `strtolower`
- `strtoupper`
- `lcfirst`
- `ucfirst`
- `trim`
- `ltrim`
- `rtrim`
- `substr`
- `substr_compare`
- `substr_replace`
- `str_replace`
- `str_pad`
- `explode`
- `implode`
- `count`
- `empty`
- `isset`

Planning intent:

- these names should be the normal source-facing helper names
- strict docs should teach these plain names
- strict-prefixed duplicates for this family should be removed rather than retained in parallel

### 2. Subsystem and domain helpers

Subsystem/domain helpers should remain prefixed.

Current intended prefixed families include:

- `regex_*`
- `fs_*`
- `io_*`
- `dt_*`
- `system_*`

These are not being treated as plain language-adjacent helpers.

They represent subsystem surfaces where explicit family grouping remains useful.

### 3. Accidental legacy outliers

Some already-prefixed outliers may remain as-is in v1 when changing them would add churn without enough payoff.

Current example:

- `json_*`

Planning rule:

- keep accidental legacy outliers such as `json_*` as-is for v1
- do not reopen them now unless they become painful enough to justify normalization

## Explicit Non-Decision

This note does not currently propose introducing both plain and prefixed aliases for the same general helper.

Current direction is:

- plain names only for general language-adjacent helpers
- no duplicate `str_*` compatibility surface for those helpers in strict authoring

This is deliberate.

The goal is simplification, not a broader alias matrix.

## Strict Docs Consequence

If this direction is adopted, the strict quick-learn must stop teaching `str_*` names as the primary string helper surface for general helpers.

Examples that should no longer be taught as the normal strict surface:

- `str_trim`
- `str_ltrim`
- `str_rtrim`
- `str_substr`
- `str_substr_compare`
- `str_substr_replace`
- `str_explode`
- `str_implode`
- `str_strlen`
- `str_strtolower`
- `str_strtoupper`

Those docs should instead teach the plain helper names where the helper is classified as general language-adjacent.

## Likely Cleanup Scope

If this planning direction is accepted, likely follow-up work includes:

1. update the strict symbol registry so general helpers resolve by plain PHP-like names
2. remove the `str_*` duplicate registrations for the general helper family from the strict symbol registry
3. update the strict quick-learn to teach plain helper names
4. audit strict examples and samples that currently use `str_*` names for general helpers
5. reconcile runtime/spec/catalog language so the helper naming story is consistent across strict and broader docs
6. confirm whether any implementation-only helper names or tests assume the prefixed strict surface and adjust them intentionally

## Questions To Resolve During Implementation

These are implementation questions, not open design questions for this note's main policy direction:

1. Which current `str_*` helpers are truly general language-adjacent and should move to plain names now?
2. Whether `str_implode` should become plain `implode` under the same rule as `explode`
3. Whether any current docs/examples outside strict onboarding should remain historically descriptive for a short transition period
4. Whether diagnostics should explicitly suggest the new plain helper names when old `str_*` names are encountered after cleanup

## Recommended Next Step

Use this note as the planning basis for:

- resolving issue `#122`
- updating strict helper naming guidance
- preparing a focused implementation change in the strict symbol registry and docs
