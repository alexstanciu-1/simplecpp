# Validation And Diagnostics

Use this reference for strict PHP++ project workflow, diagnostics, modules, and project composition.

## Project Setup

Initialize new strict projects explicitly:

```bash
scpp init --php-profile=strict
```

Use `.phs` for new source files and configure the entrypoint in `prism.json`.

Common entrypoints include:

- `main.phs`
- `src/main.phs`
- `app/main.phs`
- `index.phs`

## Build And Run

Use public `scpp` commands first:

```bash
scpp build
scpp run
```

Use a temporary entry override for focused checks:

```bash
scpp build --entry=tests/php/sample.phs
scpp run --entry=tests/php/sample.phs
```

Pass program args after `--`:

```bash
scpp run -- arg1 arg2
```

Use single-file transpile for narrow lowering checks:

```bash
scpp input.phs
```

## Local Documentation Lookup

Use `scpp docs` when local documentation is easier or safer than web access:

```bash
scpp docs
scpp docs strict
scpp docs diagnostics
scpp docs authoring
scpp docs gotchas
```

`scpp docs` lists known names. `scpp docs <name>` prints the resolved local source path and the Markdown content.

## Rebuild Controls

Default `scpp build` and `scpp run` reuse runtime and dependency artifacts.

Use explicit rebuild flags only when they answer the current debugging question:

```bash
scpp build --build-runtime
scpp build --build-dependencies
scpp build --build-runtime --build-dependencies
scpp build --force
scpp clean
```

Use `scpp clean` for a cold project rebuild. It removes generated project state such as `.prism/build`, `.prism/generated`, and `.prism/cache`.

## Diagnostics Workflow

After a failed build or run, inspect saved diagnostics before manually digging into backend output:

```bash
scpp error
scpp full-error
scpp last-run
scpp full-last-run
```

Use these as the normal first pass:

- `scpp error`: compact saved error summary.
- `scpp full-error`: full saved JSON error report.
- `scpp last-run`: compact most recent build/run context.
- `scpp full-last-run`: full saved JSON run report.

The toolchain stores diagnostic artifacts under `.prism/`, including:

- `.prism/last_error.json`
- `.prism/last_run.json`

Compiler diagnostics may be remapped from generated C++ back to original `.phs` / `.php` source lines when line maps are available. Generated `.cpp` and `.hpp` files may have compact sibling line maps:

```text
*.line.tsv
```

Prefer the remapped original source location. Inspect generated C++ only when the source looks valid and the failure suggests a lowering or runtime-boundary issue.

Strict runtime type failures currently preserve structured runtime details such as the failure code and actual runtime kind. Source attribution for runtime failures should come through generated-location capture plus `.line.tsv` remapping; until that path is complete, inspect generated C++ and line maps when the saved report does not identify the authoring expression.

## Generated Artifacts

Generated artifacts are inspection evidence:

- `.prism/generated/` contains generated C++.
- `.prism/build/` contains build artifacts and Ninja files.
- `.prism/cache/` contains generation state.

Do not patch generated C++ as the final app fix. Fix PHP++ source, project config, or the owning Simple C++ layer.

## Runtime Modules

Strict app code may require runtime modules in `prism.json`.

Example:

```json
{
  "runtime": {
    "languages": {
      "php": {
        "profile": "strict"
      }
    },
    "modules": [
      "json",
      "filesystem"
    ]
  }
}
```

Use the module set required by the APIs the app calls. When module ownership is unclear, check the strict examples and local builtin docs first.

## Project Dependencies

Use project-level composition, not PHP file inclusion.

Inside one project, `scpp build` discovers same-project `.phs` files and makes their generated declarations visible through an internal build header. Do not write source-level includes that name `.prism/generated/*.hpp` files.

In `prism.json`:

```json
{
  "dependencies": [
    "../shared"
  ],
  "libraries": [
    "sqlite3"
  ]
}
```

Use:

- `dependencies` for other Simple C++/Prism projects built from source.
- `libraries` for linker-owned native libraries or native artifacts.

Mark dependency-visible declarations with `/** @lib-export */`:

```php
/** @lib-export */
function shared_value(): int {
	return 7;
}
```

Do not activate project dependencies with PHP `require` or `include`.

## Failure Triage

Use the smallest useful loop:

1. Read the failing source location reported by `scpp error`.
2. Check strict profile and module config.
3. Check for normal-PHP assumptions in the source.
4. Check typed boundaries around `mixed`, wrappers, nullable values, and falseable results.
5. Inspect generated artifacts and line maps if remapped diagnostics point to lowering behavior.
6. Escalate to local specs only when behavior is unclear or appears unsupported.

Useful local docs:

- `scpp docs strict`
- `scpp docs diagnostics`
- `specs/simple_cpp_php_strict_quick_learn.md`
- `docs/examples/php/strict/`
- `docs/getting_started.md`
- `specs/project_build_v1.md`
- `specs/php/library_profiles.md`
- `specs/dynamic_types.md`
- `specs/array_semantics.md`
