# Changelog
Doc Status: supporting

This file is the authoritative checked-in source for release notes referenced by:

- `specs/git_workflow_release_procedure.md`

## Unreleased

-- No release notes recorded yet.

## 0.1.17 - 2026-05-08

### Additions

- Added scanner regression coverage for allowed inline typed comment slots, including leading/trailing property comments, inline parameter comments, and inline function-like return comments

### Fixes

- Fixed type metadata ownership so params, properties, locals, and function/method/closure return comments are now sourced from pre-tokenizer scanner annotations instead of raw `php-ast` doc-comment attachment
- Fixed the accepted inline type-comment surface so only recognized typed slots are honored, while detached forms such as `/** @var T */` remain non-authoritative
- Fixed closure and arrow-function typed comment handling so it follows the same scanner-owned slot rules as ordinary function-like sites

### Breaking Changes

- None

### Migration Notes

- Inline type comments remain supported, but only in recognized typed slots such as `$x /** T */ = ...`, `function (/** T */ $x)`, `public /** T */ $x`, `public $x /** T */ = ...`, and immediate post-signature return slots
- `TypeCommentExtractor` has been removed from the active pipeline; scanner-owned annotation metadata is now the single type-comment source of truth

## 0.1.16 - 2026-05-08

### Additions

- Added explicit runtime maintenance commands and flags: `scpp runtime-build`, `--build-runtime`, `--build-dependencies`, and `--force`

### Fixes

- Fixed the public `scpp build` and `scpp run` defaults so they now reuse existing runtime and Prism project dependency artifacts unless explicit rebuild flags are requested
- Fixed `scpp update` so a real fast-forward rebuilds the default reusable runtime cache automatically, and `scpp update --force` rebuilds that cache even when the checkout is already current

### Breaking Changes

- `--reuse-runtime` and `--reuse-dependencies` are replaced by `--build-runtime` and `--build-dependencies`

### Migration Notes

- `scpp build` and `scpp run` now default to warm-build reuse for runtime and dependency artifacts
- Use `--build-runtime` and `--build-dependencies` when you explicitly want those heavier rebuild steps
- Use `scpp runtime-build [--debug|--release] [--force]` to rebuild the reusable runtime cache directly
- Use `scpp update --force` to rebuild the default reusable runtime cache even when no Git update is pulled
## 0.1.15 - 2026-05-08

### Additions

- Added a scanner-owned pre-tokenizer path for PHP++ shorthand type syntax across locals, properties, parameters, and function-like return sites
- Added shorthand-type regression fixtures and regression scripts for rewritten-source and annotation-ownership validation
- Added build option coverage and integration coverage for runtime/dependency warm-build reuse behavior
- Added `public_html/test` pre-tokenized AST/PHP execution support so browser-driven test runs can exercise shorthand typed syntax

### Fixes

- Fixed nested closure and arrow return-type ownership so callable annotations no longer depend on raw `php-ast` doc-comment attachment quirks
- Fixed the project build graph so lower-level build-service calls can reuse existing runtime and dependency artifacts without recompiling them unless explicitly requested
- Fixed the `public_html/test` harness so shorthand typed syntax is parsed and executed through the pre-tokenized source path instead of failing in raw PHP parsing

### Breaking Changes

- None

### Migration Notes

- Typed doc-comment forms such as `$count /** int */ = 0;` remain supported, but shorthand surface forms such as `$count int = 0;` are now first-class and normalize through the pre-tokenizer

## 0.1.14 - 2026-05-06

### Additions

- None

### Fixes

- None

### Breaking Changes

- None

### Migration Notes

- None

## 0.1.13 - 2026-05-06

### Additions

- Added targeted PHP regression coverage for typed-hash foreach over a fresh local initialized from a method returning a nullable typed object, preserving direct key usage at a `?string` helper boundary

### Fixes

- Fixed fresh-local generator type capture to reuse namespace-aware return-type recovery, preserving typed hash property shapes across method-return locals
- Fixed the remaining reproduced Open M3 typed-hash foreach-key to nullable-string helper boundary by preventing `assembled->properties` loops from degrading foreach keys to `mixed_t`

### Breaking Changes

- None

### Migration Notes

- Explicit `(string)` stabilization should no longer be required in the reproduced `assemble_path(...): ?model` then `foreach ($assembled->properties as $property_name => ...)` Open M3 flow

## 0.1.12 - 2026-05-05

### Additions

- Added targeted PHP regression coverage for typed `hash<shared_p<T>>` foreach keys passed into method helpers expecting `?string`

### Fixes

- Fixed generator argument wrapping so concrete `T` values passed to `nullable<T>` parameters no longer degrade into synthetic `cast<nullable<T>>(...)` bridges
- Fixed the reproduced typed-hash foreach key to nullable-string helper boundary seen in real Open M3 traversal flows

### Breaking Changes

- None

### Migration Notes

- Explicit `(string)` stabilization should no longer be required at typed `hash<T>` foreach-key to `?string` helper boundaries in the reproduced flow

## 0.1.10 - 2026-05-05

### Additions

- Added regression coverage for `scpp --doctor` best-effort subprocess timeout handling
- Added `foreach` planning/checklist notes for the long-term generator cleanup
- Added targeted PHP regression coverage for typed `hash<shared_p<T>>` `foreach` key/value flows, direct helper-key usage, and direct reindexing into typed hashes

### Fixes

- Fixed `scpp --doctor` so best-effort Git remote and compiler-launcher probes time out instead of hanging indefinitely
- Fixed the PHP test harness `--jobs=1` path so focused debugging runs execute sequentially without the flaky worker subprocess layer
- Fixed typed `hash<shared_p<T>>` generator lowering across direct `foreach` key/helper flows by qualifying declared hash/object types with the active PHP namespace before emission
- Fixed generator forward-declaration collection so imported object types referenced through doc-typed hash params/returns do not degrade into bogus local forward declarations

### Breaking Changes

- None

### Migration Notes

- None

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
