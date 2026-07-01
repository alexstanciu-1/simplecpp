---
name: simple-cpp-php-strict
description: Write, review, debug, and fix strict-mode Simple C++ PHP++ applications. Use for PHP++ app authoring with PHS/.phs files, scpp strict-profile projects, plain PHP-like general helpers plus subsystem APIs such as fs_*, io_*, json_*, dt_*, typed containers, wrappers, modules, project dependencies, and diagnostics. Do not use for legacy-profile PHP++ projects except to say the strict skill does not apply.
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

Pay special attention to these quick-learn sections when the task is about everyday authoring style rather than one specific feature:

- `Preferred Strict Patterns`
- `Strict-Mode Idioms Cookbook`
- `Dynamic-Data Stabilization Cookbook`

Prefer local repository docs over web docs when both are available.

## Authoring Posture

Write PHP++ source, not generated C++.

Treat strict mode as a readability and durability posture, not as a quick-code posture.
Most strict code should already be typed after a small number of well-chosen boundaries.
The main job is to identify those boundaries clearly and handle wrapper/dynamic states intentionally.

- Create new source as `.phs`.
- Start source directly with declarations or executable code.
- Do not add `<?php`.
- Do not add `declare(strict_types=1);`.
- Use explicit types when the type is known at compile time.
- Use `vector<T>` for typed sequential data.
- Use `fixed_array<T, N>` for fixed-size sequential data when the size is part of the contract; do not append, unset, resize, or use keyed literals with this first slice.
- Use `hash<T>` for typed string-keyed data.
- Use `hash<T, T_KEY>` for intentionally typed key families.
- Use fixed-width integer aliases such as `int8`, `int16`, `int32`, `int64`, `uint8`/`byte`, `uint16`, `uint32`, and `uint64` when compact storage or interop boundaries need a narrower representation. Same-signed widening is allowed, while narrowing and signed/unsigned crossings should be explicit casts.
- Use `struct Name { ... }` for compact inline value-layout records when fields should be stored by value rather than through `shared_p<T>`. First-slice structs support public instance fields only and may contain fixed-width integers, fixed-backed enums, other first-slice structs, and typed struct containers such as `vector_t<StructName>`, `hash_t<StructName>`, and `fixed_array_t<StructName, N>`.
- Use fixed-backed enums such as `enum ExpressionKind : uint16 { case Error = 0; }` for compact discriminators and ids that need predictable generated storage width.
- Use restricted `union Name { ... }` only for mutually exclusive fixed-layout payloads. Current union payloads may contain fixed-width integers, fixed-backed enums, and recursively trivial structs; do not put strings, vectors, hashes, object/reference types, defaults, methods, constants, inheritance, or nested unions in union payloads.
- Use `layout_sizeof(TypeName)`, `layout_alignof(TypeName)`, `layout_offsetof(TypeName, field_name)`, and `layout_field_sizeof(TypeName, field_name)` for generated-layout probes in focused diagnostics and compact-row validation.
- Stabilize dynamic values early at explicit typed boundaries, but do not make strict mode sound like every line needs defensive handling.
- Treat a stable explicit left side as an ordinary typed boundary. Typed locals, properties, `hash<T>[...]` writes, `vector<T>[]` appends, typed args, and typed returns normally do not need an extra cast.
- If the destination is explicitly `mixed` or `dynamic`, no cast is needed and the value remains on the dynamic carrier path.
- Prefer `dynamic` when the source-level intent is shared mutable object/table state with reference-like aliasing; prefer `mixed` for broad boxed dynamic values that are not specifically object/table handles.
- Treat `json_decode(...)` as a fat-variable boundary. When the expected decoded shape is known, add or preserve a short local shape comment/doc comment and normalize into typed locals, properties, objects, or typed containers quickly instead of letting the dynamic carrier spread through the rest of the code. Decoded arrays may be stabilized directly into compatible `vector<T>` / nested `vector<vector<T>>` shapes, while absent, null, or incompatible dynamic values at required typed boundaries are expected to fail instead of silently coercing.
- Treat STAN warnings about unchecked wrapper results or dynamic-shape assignments as real strict-design feedback. Resolve wrappers with `take(...)` and guard dynamic/JSON fields with `isset(...)` or an explicit normalization step before assigning into required typed locals.
- Treat declared member visibility as enforced source semantics: private/protected properties, static properties, methods, and class constants are checked by STAN before build, including cross-file project code.
- Treat interface and abstract method contracts as build-blocking STAN checks in strict projects; implementation signatures should match declared return types, parameters, defaults, and visibility exactly.
- Resolve wrappers near meaningful boundaries with `take(...)` so success, failure, absence, and usable values stay explicit.
- Keep `null`, `false`, and error states distinct.
- Prefer `===` and explicit state checks over ambiguous truthiness.
- For nullable path guards, prefer `isset($node->child->name)` or `!isset($node->child->name)` over long manual chains like `$node === null || $node->child === null || $node->child->name === null`.
- Use strict profile APIs such as `fs_get`, `fs_put`, `strlen`, `io_open`, `json_encode`, `dt_format`, and strict `curl_*` helpers when the `curl` runtime module is enabled; do not substitute legacy PHP names unless a local strict doc explicitly says the helper remains shared.
- For datetime work in strict projects, prefer `dt_now`, `dt_format`, `dt_format_now`, `dt_parse`, `dt_format_iso_utc`, and `dt_parse_iso_utc`. Treat PHP-shaped `date()` and `strtotime()` as legacy wrapper names, not the strict authoring style.

## Project Workflow

Use the public `scpp` workflow before reaching for lower-level tools.

```bash
scpp init --php-profile=strict
scpp build
scpp run
scpp docs
```

For runtime rebuilds, keep this model in mind:

- plain `scpp build` / `scpp run` reuse runtime artifacts by default
- `scpp build --build-runtime` and `scpp run --build-runtime` rebuild the runtime for the current build/invocation through the project-local/custom path
- `scpp update` and `scpp runtime-build` refresh the shared reusable runtime cache
- plain `scpp build` / `scpp run` also consult the current STAN project state before generation/compilation unless `--no-stan` is passed for that invocation
- STAN `compile-errors` stop the build before native generation/compilation continues, while advisory STAN findings print a short summary and allow the build to continue
- if a strict project needs a one-off bypass while STAN is still being refined, `scpp build --no-stan` and `scpp run --no-stan` remain the explicit escape hatch
- use `scpp build --timings` or `scpp run --timings` when you need the internal build-step timing breakdown during warm-build or no-op performance investigation

After failures, prefer saved diagnostics:

```bash
scpp error
scpp full-error
scpp last-run
scpp full-last-run
```

On Windows, prefer `scpp --doctor` early when native builds fail in Git Bash / MSYS environments. The doctor output now warns about:

- `C:\msys64\mingw64\bin` appearing without `C:\msys64\usr\bin` on `PATH`
- `TMP` / `TEMP` resolving to unwritable or risky locations such as `C:\Windows`

Use generated C++ and `.prism/generated/*.line.tsv` artifacts as inspection evidence, not as the primary source to patch.
For strict runtime type failures, inspect `scpp error` / `.prism/last_error.json` first. Recent generated-location remapping can populate `original_file` / `original_line` there; use generated C++ and line maps only when the saved report still lacks the needed attribution.
For real strict-project runtime failures, follow this default sequence: `scpp run` -> `scpp error` -> inspect `original_file`, `original_line`, `expected_type`, `actual_runtime_kind`, and `operation` -> add `dbg(...)` near the failing typed boundary -> inspect `.line.tsv` or generated C++ only if the saved report is still not enough.
Treat the default runtime-failure view as source-first: `scpp run` should be the compact app-facing view, `scpp error` the richer saved summary, and `scpp full-error` the place for raw runtime message details plus deeper generated/runtime trace inspection. Bounds and typed-boundary failures should normally show source location, a short source snippet, and operation details before requiring generated-code inspection.
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
- For native-window or embedded-browser preview projects, enable the `webview` runtime module explicitly and use the `ui_*` / `webview_*` helper families. `webview` auto-enables `ui`; use `scpp docs ui-webview` for the current preview API, platform support promise, dependency checklist, and golden `strict_webview_bridge` sample.
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
