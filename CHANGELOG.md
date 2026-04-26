# Changelog
Doc Status: supporting

This file is the authoritative checked-in source for release notes referenced by:

- `specs/git_workflow_release_procedure.md`

## Unreleased

- No release notes recorded yet.

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
