# PHP Examples
Doc Status: supporting

Purpose: provide tested, reviewable Prism++ / Simple C++ examples written with the PHP authoring surface.

This folder is organized around the normative PHP library profile split defined in:

- `specs/php/library_profiles.md`

## Profile Order

When examples differ, read them in this order:

1. `strict/`
2. `legacy/`

`strict/` is the first-choice, authoritative agent-facing surface for new non-legacy library authoring.

`legacy/` remains useful for compatibility, migration, and PHP-shaped builtin guidance, but it is not the first recommendation when a strict reusable runtime family exists.

## What Lives Here

- `strict/`
  - authoritative examples for the strict PHP library profile
  - native Simple C++ library naming such as `fs_*`, `str_*`, `io_*`
- `legacy/`
  - compatibility examples for the legacy PHP library profile
  - PHP-legacy naming such as `scandir`, `file_get_contents`, `strlen`

## Rules For Agents

- prefer `strict/` first when the project profile is `strict`
- prefer `legacy/` only when the project profile is `legacy`
- do not mix strict and legacy library surfaces inside one project
- when strict and legacy differ, treat strict as the better-code guideline for new library authoring

PHP-owned helper semantics may still remain shared across profiles where the owning specs say so, for example:

- `count`
- `empty`
- `isset`
- `take`
- `cli_argc`
- `cli_argv`
- `cli_args`
- `shell_exec`

## Primary Semantic References

- `specs/spec_map.md`
- `specs/php/catalog.md`
- `specs/php/library_profiles.md`
- `specs/php/canonical_examples.md`
- `docs/ai_onboarding/coding_style.md`

## Validation Posture

This folder is intentionally conservative for the pre-release branch.

If a language or library feature does not currently work, do not add a workaround example here. Skip it, flag it, and fix the owning layer separately.
