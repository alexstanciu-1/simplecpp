# Shared Release Runtime Cache Phase 1
Doc Status: planning

Date: 2026-05-20

Purpose:
- record the agreed recovery plan for GitHub issue `#162`
- replace the current signature-heavy shared runtime cache identity with a release-owned shared runtime model
- keep this note as a resumable implementation anchor before and during Phase 1 code changes

## Problem Summary

Current reusable runtime behavior is too coupled to local checkout state and too eager to accumulate new shared cache families.

Observed problems:

- common project edits can still push authors toward runtime rebuild flows
- shared runtime cache paths grow continuously under repo-local `.prism/runtime/`
- multiple checkouts of the same release duplicate shared runtime storage
- local runtime source changes currently influence shared runtime identity through hashed runtime-tree inputs
- sandboxed or constrained environments suffer when ordinary builds still interact with shared runtime cache state

## Agreed Design Direction

Shared reusable runtime binaries should represent the installed release defaults, not arbitrary local build/source combinations.

Shared runtime binaries are:

- release assets
- prepared by `scpp update`
- limited to default supported runtime families
- reused only when the requested build shape matches shared-release eligibility rules

Anything outside the default release-supported build shape should build its runtime artifact inside the calling project.

## Shared Release Runtime Eligibility

Phase 1 shared runtime reuse is allowed only when all of these are true:

- compiler kind is the default supported compiler kind for the installed release
- linker flags are the default runtime linker flags
- runtime family is one of the release-prebuilt families
- build mode is `debug` or `release`
- runtime source state is not being treated as a custom local-build case

If any of those are false, the runtime artifact is project-local instead of shared.

## Runtime Family Model

For release/runtime packaging purposes, language profiles are treated as separate runtime families.

Examples:

- `php-legacy`
- `php-strict`

Each runtime family gets its own shared release binaries.

## Shared Release Matrix

Phase 1 shared binaries prepared by `scpp update`:

- each supported runtime family
- `debug`
- `release`

Initial expected families:

- `php-legacy`
- `php-strict`

This means `scpp update` should prepare four shared runtime artifacts for the default compiler/toolchain path:

- `php-legacy/debug`
- `php-legacy/release`
- `php-strict/debug`
- `php-strict/release`

## Optional Modules

Longer-term direction:

- optional runtime modules should move toward separate `.so` artifacts

Phase 1 scope:

- do not require the full per-module shared-library split yet
- first stabilize shared runtime identity and fallback rules
- keep module-split work as a follow-up phase unless a small targeted split becomes unavoidable

Open follow-up question after Phase 1:

- whether shared release runtime should expose only a fixed module baseline
- or a small explicit set of shared module families

## Shared Identity Rules

Phase 1 removes these from shared release-runtime identity:

- hash of the runtime include tree
- arbitrary local source-state drift as a shared cache key
- non-default compiler overrides
- non-default linker flags
- non-default/custom runtime variants

These inputs may still matter when deciding whether a requested build is shared-runtime-eligible, but they must not create new permanent shared runtime cache families.

## Local Source Drift Rule

The old model uses a runtime-tree content hash to create new shared cache directories.

Phase 1 changes that:

- local runtime source drift should no longer create a new shared-runtime identity
- instead it should force project-local runtime behavior when appropriate

Conceptually:

- release-matching build -> reuse shared release runtime
- custom/local runtime build state -> build local runtime in the calling project

## Cache Location Direction

Phase 1 keeps the current repo-local shared runtime root only if that is the smallest safe change.

Preferred direction after Phase 1:

- move shared runtime storage to a single machine-level cache root
- avoid per-checkout duplication of release runtime binaries

Phase 1 may still land the policy shift first even if cache-root relocation follows immediately after.

## Cleanup Direction

Phase 1 should leave room for cleanup of old signature-based runtime directories.

Desired follow-up capabilities:

- deterministic cleanup of obsolete shared runtime directories
- cleanup command or migration helper
- no unbounded accumulation of abandoned signature folders

## Implementable Phase 1

Phase 1 should do the smallest coherent architecture change that matches the agreed direction:

1. define stable shared runtime family identity from release defaults:
   - runtime family
   - build mode (`debug` / `release`)
2. prepare shared release runtime binaries during `scpp update`
3. route non-default compiler/linker/custom requests to project-local runtime artifacts
4. remove runtime-include-tree hashing from shared runtime identity
5. keep shared runtime reuse as an eligibility gate rather than a content-signature fan-out
6. preserve clear diagnostics so the user can tell whether a build reused shared release runtime or built a project-local runtime

## Expected User-Facing Outcome

After Phase 1:

- `scpp update` becomes the place where shared reusable runtime binaries are refreshed
- ordinary matching builds reuse stable release runtime artifacts
- non-default shapes build locally inside the project
- shared runtime storage no longer grows by creating a new family for every runtime source-tree variation

## Recovery Notes

If implementation work stops midstream, resume by checking:

- this planning note first
- `specs/project_build_v1.md`
- `docs/getting_started.md`
- `bin/project_services.php`
- `tests/tools/test_scpp_build_reuse_integration.php`
- `tests/tools/test_scpp_update.php`

Primary implementation target for Phase 1:

- separate “shared release runtime” from “project-local runtime” selection
- keep `scpp update` as the owner of shared runtime preparation

## Phase 2 - Shared Optional Module Runtime Artifacts

Purpose:

- reduce shared release runtime duplication further by splitting heavy optional modules into their own shared runtime artifacts
- keep module-specific compile and link flags attached to those module artifacts instead of the shared base runtime
- preserve the simpler Phase 1 fallback where custom/non-default project-local runtime builds may stay monolithic for now

### Phase 2 Scope

Phase 2 applies only to shared release runtime preparation and reuse.

Initial split targets:

- `mysqli`
- `regex`
- `curl`

Modules that remain in the shared base runtime for now:

- `json`
- `filesystem`
- `datetime`

Project-local runtime builds may continue to use the monolithic composition path during this phase.

### Shared Release Artifact Model

For each shared runtime family and build mode, `scpp update` should prepare:

- one shared base runtime artifact
- one shared optional module artifact per supported heavy optional module

Examples for `php-strict/debug`:

- base runtime artifact
- `mysqli` runtime module artifact
- `regex` runtime module artifact
- `curl` runtime module artifact

And likewise for:

- `php-strict/release`
- `php-legacy/debug`
- `php-legacy/release`

### Linkage Model

When a build reuses the shared release runtime path:

- base runtime artifact is always linked
- optional shared module artifacts are linked only when the current runtime module set requests them

When a build falls back to project-local runtime placement:

- Phase 2 does not require per-module splitting yet
- project-local runtime may stay monolithic for simplicity

### Compile-Flag Policy

Optional module compile and link flags should be isolated to their own module artifacts.

Examples:

- `mysqli` pkg-config flags apply only to the `mysqli` module artifact
- `regex` pkg-config flags apply only to the `regex` module artifact
- `curl` flags apply only to the `curl` module artifact

The shared base runtime should not inherit optional-module-specific flags.

### Shared Release Eligibility Update

Phase 2 broadens shared release reuse for module composition:

- default base modules remain part of the shared base runtime
- supported heavy optional modules may now be reused through shared module artifacts
- unsupported/custom shapes still fall back to project-local runtime behavior

### Phase 2 Implementation Target

1. keep the Phase 1 shared runtime family/build-mode layout
2. add shared optional module artifact specs for `mysqli`, `regex`, and `curl`
3. teach `scpp update` to build the shared base + shared optional module matrix
4. teach build rendering and preflight validation to link/check the required shared module artifacts
5. keep project-local runtime compilation monolithic for now

### Recovery Notes For Phase 2

If implementation stops midstream during Phase 2, resume by checking:

- this planning note first
- `bin/project_services.php`
- `tests/tools/test_scpp_build_options.php`
- `tests/tools/test_scpp_build_reuse_integration.php`
- `tests/tools/test_scpp_update.php`
- `tests/tools/test_scpp_mysqli_strict_runtime_link.php`
