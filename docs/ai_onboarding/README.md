# AI Onboarding
Doc Status: supporting

Purpose: give Codex and other AI assistants a reliable entry point for working in this repository without guessing project semantics.

This folder is not a new semantic authority.

Use it as:
- an orientation layer
- a task-routing guide
- a compact summary of where the real authorities live

## Start Here

Read in this order:

1. `specs/spec_map.md`
2. `docs/ai_onboarding/language_model.md`
3. `docs/ai_onboarding/repo_map.md`
4. `docs/ai_onboarding/coding_style.md`
5. `docs/ai_onboarding/workflows.md`
6. `docs/ai_onboarding/examples.md`

## First 10 Minutes

For a new task, default to this sequence:

1. read `specs/spec_map.md`
2. identify the owning layer using `docs/ai_onboarding/repo_map.md`
3. confirm the semantic contract in the owning spec
4. inspect the nearest implementation anchor
5. validate at the smallest layer that proves the change

## Core Rules

- Treat top-level normative specs under `specs/` as the primary semantic authority.
- Treat architecture and runtime/generator subsystem specs as subordinate to top-level language semantics.
- Treat implementation behavior and tests as evidence, not as the semantic source of truth.
- Treat PHP as the current authoring language surface.
- Treat generated C++ as a lowering/debug artifact unless the task is specifically about runtime, native integration, or generator output.

## What To Edit

Edit the layer that owns the change:

- user-visible language meaning -> `specs/`
- PHP generator lowering -> `generators/php/`
- runtime semantics or helpers -> `runtime/`
- project CLI/build flow -> `bin/`, `docs/`, `specs/project_build_v1.md`
- onboarding and operator-facing guidance -> `docs/`

Do not casually patch generated output as if it were the primary source.

## Main Project Model

Prism++ currently works as:

- PHP source as the authoring surface
- structured source-to-source lowering into C++
- generated code targeting the `scpp` runtime
- project-mode builds through `scpp init`, `scpp build`, and `scpp run`

The generator is intentionally a deterministic structured lowerer, not a full semantic compiler.

## When Docs and Code Disagree

Use the authority order from `specs/spec_map.md`.

In practice:

- do not assume current code is correct just because it exists
- do not assume a planning document changed semantics
- if the conflict is real, document it as a spec gap, known fail, or regression instead of silently choosing one side

## Validation Defaults

Prefer validating at the smallest layer that proves the change:

- syntax/lowering behavior: generator samples and focused transpilation
- project workflow/build behavior: `scpp build` or `scpp run`
- broader user-facing regressions: usability harness
- runtime behavior: runtime test suite

See `docs/ai_onboarding/workflows.md` for concrete guidance.

## Style Guidance

For the short recommended coding posture, read:

- `docs/ai_onboarding/coding_style.md`
