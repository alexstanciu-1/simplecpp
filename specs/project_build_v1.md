# Prism++ Project Build v1
Doc Status: normative
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
- allow persistent override in `prism.json` via `build.cxx`
- allow one-off environment override via `SCPP_CXX`
- allow launcher override via `SCPP_CXX_LAUNCHER`
- fail clearly if a requested compiler or launcher is not available

### Output artifact

The first output artifact is:

- one executable

### Runtime linkage

The runtime is compiled directly from the repository checkout.

The current architecture direction is layered composition rather than one monolithic language-coupled runtime target:

- `scpp_runtime` = non-language runtime core
- `scpp_lang_php` = PHP runtime layer
- runtime modules such as `scpp_json` and `scpp_filesystem` are linked explicitly

The active frontend language is `PHP++`.
Its canonical source extension is `.phs`.
Source files with the `.php` extension remain accepted as compatibility inputs in v1, but `.phs` is the preferred project-facing extension.
Inside docs and code, `PHP` refers to host tooling/runtime unless the surrounding text explicitly means `PHP++`.

Future `scpp build` composition must be able to select language layers and runtime modules deliberately rather than assuming one fixed all-in-one runtime surface.

### Path policy

Paths emitted into `build.ninja` are:

- relative to project root
- normalized to forward slashes

## Minimal config

```json
{
  "config_version": 1,
  "project_name": "my_project",
  "entrypoint": "main.phs",
  "build_dir": ".prism/build",
  "generated_dir": ".prism/generated",
  "cache_dir": ".prism/cache",
  "native_cpp_dir": "native_cpp",
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

`scpp init` accepts:

- `--php-profile=legacy`
- `--php-profile=strict`

The current default is `legacy`.

Entrypoint guessing checks these common candidates in order:

- `main.phs`
- `src/main.phs`
- `app/main.phs`
- `index.phs`
- `src/index.phs`
- `main.php`
- `src/main.php`
- `app/main.php`
- `index.php`
- `src/index.php`

If none exists, `prism.json` still gets written with the placeholder entrypoint `main.phs` and the command tells the user to edit it.

## `scpp build` behavior

`scpp build`:

1. finds `prism.json` by walking upward from the current directory
2. validates the configured entrypoint
3. checks for Ninja
4. resolves a compiler from config override or sane defaults
5. recursively scans the project tree for `*.phs` files and compatible `*.php` files (excluding `.prism/`)
6. uses the S2S generator on all discovered PHP++ source files
7. fails if both `<name>.phs` and `<name>.php` exist in the same directory
8. stores S2S file state in `.prism/cache/s2s_state.php` using PHP `return [...]` data for fast load
9. skips unchanged files when both file size and mtime match and generated outputs already exist
10. generates C++ into `.prism/generated/`
11. emits `.prism/build/build.ninja`
12. runs Ninja
13. leaves the output executable under `.prism/build/`

## What this document intentionally does not solve

This is not the final deliberate multi-file semantic model. It does not yet freeze:

- include / require graph semantics
- static `__DIR__` expression evaluation rules
- cross-file declaration merge rules
- duplicate-definition semantics
- file-init execution order across multiple source units

Those belong to the dedicated multi-file model spec.

## FastCGI companion build

When `prism.json` contains `fastcgi.enabled = true`, `scpp build` also emits a FastCGI companion executable.
The FastCGI host expects a handwritten `scpp::fcgi::http_handle(const scpp::fcgi::request_t&)` definition from `native_cpp/`.


## Runtime build composition

`scpp build` now reads runtime composition from `prism.json` under:

```json
{
  "runtime": {
    "languages": {
      "php": {
        "profile": "legacy"
      }
    },
    "modules": ["json", "filesystem", "mysqli"]
  }
}
```

Legacy list-style `runtime.languages` remains accepted as a compatibility shape and defaults PHP to profile `legacy`.

Current default behavior keeps all known runtime modules active. Unsupported language or module names must fail clearly during build configuration.

The normative meaning of PHP profile selection itself is defined in:

- `specs/php/library_profiles.md`
