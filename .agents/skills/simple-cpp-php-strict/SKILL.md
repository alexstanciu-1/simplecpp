---
name: simple-cpp-php-strict
description: Write, review, debug, and fix strict-mode Simple C++ PHP++ applications. Use for PHP++ app authoring with PHS/.phs files, scpp strict-profile projects, family-prefixed strict APIs such as fs_*, str_*, io_*, json_*, dt_*, typed containers, wrappers, modules, project dependencies, and diagnostics. Do not use for legacy-profile PHP++ projects except to say the strict skill does not apply.
---

# Simple C++ PHP++ Strict

Use this skill for strict-mode PHP++ app work. Simple C++ is the project/toolchain/runtime; PHP++ is the authoring language surface; PHS is the preferred `.phs` source extension.

## Scope Guard

Confirm the project is strict before applying strict authoring advice.

- For existing projects, inspect `prism.json` and require `runtime.languages.php.profile = "strict"`.
- For new projects, initialize explicitly with `scpp init --php-profile=strict`.
- If the project is `legacy`, stop using this skill and follow legacy guidance instead.
- Do not mix strict and legacy library surfaces in one project.

## First Moves

For nontrivial PHP++ decisions, read the local quick-learn first:

- `specs/simple_cpp_php_strict_quick_learn.md`

If the local `scpp` command is available, prefer:

```bash
scpp docs strict
```

Then read only the reference files needed for the task:

- `references/authoring-rules.md` for writing or reviewing PHP++ source.
- `references/validation-and-diagnostics.md` for build/run/debug workflows, modules, dependencies, and diagnostics.
- `references/php-habit-gotchas.md` when translating from normal PHP habits or fixing code that "looks like PHP" but fails in Simple C++.

Prefer local repository docs over web docs when both are available.

## Authoring Posture

Write PHP++ source, not generated C++.

- Create new source as `.phs`.
- Start source directly with declarations or executable code.
- Do not add `<?php`.
- Do not add `declare(strict_types=1);`.
- Use explicit types when the type is known at compile time.
- Use `vector<T>` for typed sequential data.
- Use `hash<T>` for typed string-keyed data.
- Use `hash<T, T_KEY>` for intentionally typed key families.
- Stabilize dynamic values early at explicit typed boundaries.
- Treat a stable explicit left side as an ordinary typed boundary. Typed locals, properties, `hash<T>[...]` writes, `vector<T>[]` appends, typed args, and typed returns normally do not need an extra cast.
- If the destination is explicitly `mixed`, no cast is needed and the value remains `mixed`.
- Resolve wrappers near meaningful boundaries with `take(...)`.
- Keep `null`, `false`, and error states distinct.
- Prefer `===` and explicit state checks over ambiguous truthiness.
- Use strict profile APIs such as `fs_get`, `fs_put`, `str_strlen`, `io_open`, `json_encode`, and `dt_format`; do not substitute legacy PHP names unless a local strict doc explicitly says the helper remains shared.
- For datetime work in strict projects, prefer `dt_now`, `dt_format`, `dt_format_now`, `dt_parse`, `dt_format_iso_utc`, and `dt_parse_iso_utc`. Treat PHP-shaped `date()` and `strtotime()` as legacy wrapper names, not the strict authoring style.

## Project Workflow

Use the public `scpp` workflow before reaching for lower-level tools.

```bash
scpp init --php-profile=strict
scpp build
scpp run
scpp docs
```

After failures, prefer saved diagnostics:

```bash
scpp error
scpp full-error
scpp last-run
scpp full-last-run
```

Use generated C++ and `.prism/generated/*.line.tsv` artifacts as inspection evidence, not as the primary source to patch.
For strict runtime type failures, inspect `scpp error` / `.prism/last_error.json` first. Recent generated-location remapping can populate `original_file` / `original_line` there; use generated C++ and line maps only when the saved report still lacks the needed attribution.
For real strict-project runtime failures, follow this default sequence: `scpp run` -> `scpp error` -> inspect `original_file`, `original_line`, `expected_type`, `actual_runtime_kind`, and `operation` -> add `dbg(...)` near the failing typed boundary -> inspect `.line.tsv` or generated C++ only if the saved report is still not enough.
For runtime shape confusion, use strict-safe debug helpers before ad hoc probes:

```php
dbg("value", $value);
dbg("shape", $value, DBG_SHAPE | DBG_DEPTH_3 | DBG_PTR);
dbg_if("gate", "deep value", $value, DBG_SHAPE);
```

Use `dbg_set("gate", $condition)` and `dbg_unset("gate", $condition)` to activate focused lower-call debugging. Duplicate enabled gates and missing unsets are intentionally loud.

## Composition

For multi-file and multi-project work:

- Add runtime modules in `prism.json` when strict builtins require them.
- Let `scpp build` compose files inside the same project; same-project `.phs` files should not include generated `.hpp` files.
- Use `dependencies` for other Simple C++/Prism projects built from source.
- Dependency project export headers are generated build artifacts too; do not add generated dependency `.hpp` names to PHP++ source to force ordering.
- Use `libraries` for linker-owned native artifacts.
- Mark dependency-visible top-level declarations with `/** @lib-export */`.
- Do not use PHP `require`, `require_once`, `include`, or `include_once` for project composition in the current strict-project model.

## Before Finishing

Validate at the smallest layer that proves the app change:

- `scpp <file.phs>` for focused transpilation checks.
- `scpp build` for project compile checks.
- `scpp run` for behavior checks.
- `scpp docs <name>` for local documentation lookup when unsure.
- `scpp error` / `scpp full-error` when a failure needs explanation.

If behavior is unclear, follow local authority order: quick-learn, strict examples, then relevant specs. Do not invent normal PHP semantics to fill gaps.
