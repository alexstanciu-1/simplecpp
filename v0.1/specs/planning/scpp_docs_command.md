# `scpp docs` Command
Doc Status: planning

Purpose: capture a future CLI affordance for making local Simple C++ documentation easy for users and AI agents to discover without relying on web access.

## Problem

Agents and users often need a fast local answer to questions such as:

- strict PHP++ authoring rules
- project build workflow
- diagnostics workflow
- runtime modules
- project dependencies
- supported library profiles

Today, the right document may be known only by path. Some agents cannot browse the web reliably, and manual repository search can be slower than a stable command.

## Proposed Shape

Add a command:

```bash
scpp docs <name>
```

The command should print a local documentation artifact or a curated excerpt for `<name>`.

Implementation note:

- an initial command exists in `bin/project_services.php`
- the implemented command prints whole local Markdown artifacts
- the implemented registry is documented in `specs/project_build_v1.md`

Possible names:

- `strict`
- `php-strict`
- `quick-learn`
- `build`
- `diagnostics`
- `profiles`
- `modules`
- `dependencies`
- `examples`

## Desirable Behavior

- Prefer repo-local docs over network access.
- Print the resolved source path before content.
- Fail with a list of known doc names when `<name>` is unknown.
- Keep output plain text / Markdown so agents can read it directly.
- Avoid changing language semantics; this is a discoverability command.

## Candidate Sources

- `specs/simple_cpp_php_strict_quick_learn.md`
- `docs/getting_started.md`
- `specs/project_build_v1.md`
- `specs/php/library_profiles.md`
- `docs/examples/php/strict/README.md`
- `docs/ai_onboarding/README.md`
- `.agents/skills/simple-cpp-php-strict/`
