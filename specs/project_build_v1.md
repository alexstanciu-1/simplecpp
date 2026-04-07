# Prism++ Project Build v1

Status: active staging contract.

## Purpose

This document fixes the first practical project build shape before the full deliberate multi-file semantic model is finished. The default project shape is not web-first.

It answers the operational questions that should not stay vague:

- what command users run
- where project config lives
- where generated files live
- what build backend is used
- how compiler detection works
- what the first output artifact is

## Locked decisions

### Public build command

The first public build command is:

- `scpp build`

### Entrypoint count

The first public build target count is:

- one entrypoint first

### Project config filename

The project config filename is:

- `prism.json`

### Project working tree

The project-local working tree is:

```text
.prism/
  build/
  generated/
  cache/
```

Generated C++ is kept on disk.

### Build backend

The default backend is:

- Ninja

`scpp build` emits the Ninja file and runs Ninja directly.

### Compiler policy

Compiler selection policy:

- detect a sane default compiler
- allow override in `prism.json`
- fail clearly if none is available

### Output artifact

The first output artifact is:

- one executable

### Runtime linkage

The runtime is compiled directly from the repository checkout.

### Path policy

Paths emitted into `build.ninja` are:

- relative to project root
- normalized to forward slashes

## Minimal config

```json
{
  "config_version": 1,
  "project_name": "my_project",
  "entrypoint": "main.php",
  "build_dir": ".prism/build",
  "generated_dir": ".prism/generated",
  "cache_dir": ".prism/cache",
  "build": {
    "backend": "ninja",
    "cxx": null
  }
}
```

## `scpp init` behavior

`scpp init` creates:

- `prism.json`
- `.prism/build/`
- `.prism/generated/`
- `.prism/cache/`

Entrypoint guessing checks these common candidates in order:

- `main.php`
- `src/main.php`
- `app/main.php`
- `index.php`
- `src/index.php`

If none exists, `prism.json` still gets written with the placeholder entrypoint `main.php` and the command tells the user to edit it.

## `scpp build` behavior

`scpp build`:

1. finds `prism.json` by walking upward from the current directory
2. validates the configured entrypoint
3. checks for Ninja
4. resolves a compiler from config override or sane defaults
5. recursively scans the project tree for `*.php` files (excluding `.prism/`)
6. uses the S2S generator on all discovered PHP files
7. stores S2S file state in `.prism/cache/s2s_state.php` using PHP `return [...]` data for fast load
8. skips unchanged files when both file size and mtime match and generated outputs already exist
9. generates C++ into `.prism/generated/`
10. emits `.prism/build/build.ninja`
11. runs Ninja
12. leaves the output executable under `.prism/build/`

## What this document intentionally does not solve

This is not the final deliberate multi-file semantic model. It does not yet freeze:

- include / require graph semantics
- static `__DIR__` expression evaluation rules
- cross-file declaration merge rules
- duplicate-definition semantics
- file-init execution order across multiple source units

Those belong to the dedicated multi-file model spec.
