# AGENTS.md

Purpose: quick-start instructions for Codex and similar AI assistants working in this repository.

## Read First

Before making changes, read in this order:

1. `specs/spec_map.md`
2. `docs/ai_onboarding/README.md`
3. `docs/ai_onboarding/coding_style.md`

Then read the owning spec for the task.

## Core Rules

- PHP is the current authoring surface.
- Generated C++ is a lowering/debugging artifact, not the primary source of truth.
- Top-level normative specs under `specs/` define user-visible semantics.
- Architecture, runtime, and generator subsystem docs are subordinate to top-level semantic specs.
- Implementation and tests are evidence, not the highest semantic authority.

## Work By Owning Layer

- language meaning -> `specs/`
- PHP lowering -> `generators/php/`
- runtime behavior -> `runtime/`
- CLI/build/project workflow -> `bin/`, `docs/`, `specs/project_build_v1.md`
- onboarding/operator guidance -> `docs/`

Do not patch generated output as the final fix when the real issue belongs in a higher-level source.

## Default Coding Posture

- prefer explicit types at meaningful boundaries
- stabilize `mixed` early
- keep `null` and `false` distinct
- prefer `===`
- avoid ambiguous truthiness
- write for the supported Prism++ subset, not full PHP

## Validation

Validate at the smallest layer that proves the change:

- focused transpilation or samples for lowering changes
- `scpp build` / `scpp run` for project workflow
- `scpp usability-harness` for first-user flows
- runtime test suite for runtime behavior

See `docs/ai_onboarding/workflows.md` for the fuller task-routing and validation guide.
