# AGENTS.md

Purpose: quick-start instructions for Codex and similar AI assistants working in this repository.

## Read First

Before making changes, read in this order:

1. `specs/spec_map.md`
2. `docs/ai_onboarding/README.md`
3. `docs/ai_onboarding/coding_style.md`
4. `specs/simple_cpp_php_strict_quick_learn.md`

Then read the owning spec for the task.

## Core Rules

- PHP is the current authoring surface.
- For PHP++ / PHS authoring, `specs/simple_cpp_php_strict_quick_learn.md` is a mandatory read.
- The source syntax is PHP-like, but source code must target the supported Prism++ / Simple C++ subset, not standard PHP.
- Do not use a PHP feature merely because normal PHP accepts it.
- Generated C++ is a lowering/debugging artifact, not the primary source of truth.
- Top-level normative specs under `specs/` define user-visible semantics.
- Architecture, runtime, and generator subsystem docs are subordinate to top-level semantic specs.
- Implementation and tests are evidence, not the highest semantic authority.

## S2S Generator Boundary

- The PHP S2S generator is type-blind unless a specific generation rule documents a narrow local structural check.
- The generator does not resolve symbols, infer full program types, validate inheritance, validate overrides, or behave like a semantic compiler.
- If emitted C++ is semantically invalid, that failure normally belongs to the C++ compiler or runtime contract unless a generator rule explicitly says otherwise.
- Do not write source code that depends on the generator having standard-PHP runtime knowledge.

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

## Standard-PHP Fallbacks To Avoid

Do not assume these are valid just because they are common PHP:

- untyped parameters or returns
- dynamic includes or computed `require_once`
- broad builtin availability without a spec
- loose truthiness as a substitute for explicit state checks
- magic array behavior outside the documented subset
- implicit PHP runtime behavior that is not documented in the Prism++ specs

## Validation

Validate at the smallest layer that proves the change:

- focused transpilation or samples for lowering changes
- `scpp build` / `scpp run` for project workflow
- `scpp usability-harness` for first-user flows
- runtime test suite for runtime behavior

See `docs/ai_onboarding/workflows.md` for the fuller task-routing and validation guide.

## Planning Notes

- Temporary planning notes for active tasks should default to `specs/planning/` unless a more specific owning folder is clearly better.
- Planning notes must carry `Doc Status: planning` and must not be treated as semantic authority.

## Dev Server Linking

- If the user asks the AI agent to link, re-link, activate, or start watch-sync to a dev server, the agent must first find, know, or request the shared `_dev_deploy` folder location.
- If the `_dev_deploy` location is not already known from the current workspace or prior chat context, the agent must ask for it before continuing.
- After `_dev_deploy` is known, the agent must read the shared runbooks there before acting:
  - `dev_deploy_rules.md`
  - `dev_deploy_channel_swap.md`
  - `dev_deploy_helper_contract.md`
  - `tools/fsync/README.md`
- The agent must then read the repo-local hook files under `specs/dev-deploy/`.
- The agent must resolve and validate:
  - current worktree
  - current branch and commit
  - target channel and remote path
  - current runtime binding if one exists
  - activation instance URL if one is required
- If any required input is missing, the agent must request that extra data, then continue with the documented flow.
- The agent must use the shared `_dev_deploy` helper flow rather than improvising ad hoc sync commands.
- Unless the user explicitly asks for one-time sync only, linking to a dev server should include file-sync activation by default.
