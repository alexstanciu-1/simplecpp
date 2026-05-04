# Changelog
Doc Status: supporting

This file is the authoritative checked-in source for release notes referenced by:

- `specs/git_workflow_release_procedure.md`

## Unreleased

- No release notes recorded yet.

## 0.1.8 - 2026-05-04

### Additions

- Added first-class PHP-surface `hash<T>` support that lowers to runtime `hash_t<T>`
- Added typed hash coverage for keyed literals, keyed access, properties, mixed keyed-plus-append literals, and `foreach` including by-reference forms

### Fixes

- Fixed typed hash lowering across `isset`, `empty`, dim access, append, and typed `foreach` value preservation
- Fixed runtime `foreach` bridge support so typed `hash_t<T>` containers iterate correctly without falling back to mixed-only handling

### Breaking Changes

- None

### Migration Notes

- Replace PHP-surface `hash_t<T>` examples with `hash<T>` in authored code and docs

## 0.1.7 - 2026-05-04

### Additions

- Added project-level Prism dependencies through `dependencies` in `prism.json`
- Added native library configuration through `libraries` in `prism.json`
- Added `/** @lib-export */` support for dependency-visible top-level functions, classes, interfaces, and constants
- Added generated per-project export manifests and composed project export headers for dependency builds
- Added documentation for project dependencies, project exports, and `@lib-export` usage in the build and AI onboarding docs

### Fixes

- Fixed multi-project build planning so dependency projects are scanned, generated, and linked as part of one build graph
- Fixed transitive dependency resolution with deduplication and cycle detection
- Fixed constant export detection for doc-comment based `@lib-export`
- Removed the generated PCH warning caused by emitting `#pragma once` in PCH main files

### Breaking Changes

- None

### Migration Notes

- Projects that want cross-project declaration visibility should mark public dependency surfaces explicitly with `/** @lib-export */`

## 0.1.4 - 2026-04-26

### Additions

- Clarified that the PHP S2S generator is type-blind and not a semantic compiler
- Strengthened AI onboarding guidance to prevent fallback to standard PHP semantics
- Added explicit documentation for generator responsibility boundaries
- Introduced `docs/future_thoughts/` for non-authoritative future ideas

### Fixes

- Removed implicit future promises from active documentation by relocating them to planning docs

### Breaking Changes

- None

### Migration Notes

- None

## 0.1.3 - 2026-04-26

### Additions

- Completed semantic-matrix coverage across the active operator families, including conditional selection, arithmetic, logical, comparison, bitwise, unary, probes, and compound-assignment surfaces
- Added runtime-supported iterable and `take(...)` extraction behavior for `result<T>`, `result_or_false<T>`, and `result_or_bool<T>` wrapper flows used by filesystem-style results

### Fixes

- Fixed wrapper-lifted runtime/operator behavior across arithmetic, logical, bitwise, ordering, equality, identity, and conditional-selection lowering paths
- Fixed matrix semantics and enablement boundaries for mixed-condition truthiness, `unset_*` handling, and remaining elvis / ternary consistency gaps

### Breaking Changes

- None

### Migration Notes

- None

## 0.1.2 - 2026-04-23

### Additions

- None

### Fixes

- Fixed `scpp --doctor` so compiler launcher detection no longer fatals when no launcher command has been resolved yet

### Breaking Changes

- None

### Migration Notes

- None

## 0.1.1 - 2026-04-23

### Additions

- Added AI onboarding documentation under `docs/ai_onboarding/`
- Added repository guidance for coding style, workflows, examples, repo structure, and language-model usage
- Added `AGENTS.md` and linked onboarding guidance from existing docs

### Fixes

- No user-facing fixes recorded in this release beyond documentation/process updates

### Breaking Changes

- None

### Migration Notes

- None
