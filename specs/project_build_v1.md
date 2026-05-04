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
  "dependencies": [],
  "libraries": [],
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
3. resolves `dependencies` declared in `prism.json` as Prism project dependencies
4. recursively loads dependency project configs before build planning continues
5. checks for dependency cycles and fails clearly if one is found
6. checks for Ninja
7. resolves a compiler from config override or sane defaults
8. recursively scans the root project tree and all dependency project trees for `*.phs` files and compatible `*.php` files (excluding each project's `.prism/`)
9. uses the S2S generator on all discovered PHP++ source files
10. fails if both `<name>.phs` and `<name>.php` exist in the same directory
11. stores S2S file state in each project's `.prism/cache/s2s_state.php` using PHP `return [...]` data for fast load
12. skips unchanged files when both file size and mtime match and generated outputs already exist
13. generates C++ into each project's `.prism/generated/`
14. emits `.prism/build/build.ninja`
15. links dependency project outputs in dependency order together with the root project output and configured `libraries`
16. runs Ninja
17. leaves the root project executable under `.prism/build/`

## Project dependencies

Project composition is controlled by `scpp build`, not by source-language `require` or `include`.

`prism.json` may declare:

- `dependencies`
  - other Prism projects that are built from source as part of the same build graph
- `libraries`
  - native prebuilt artifacts or linker-owned dependencies

Minimal example:

```json
{
  "dependencies": [
    "../shared/prism-utils",
    "../shared/prism-http"
  ],
  "libraries": [
    "sqlite3"
  ]
}
```

For v1, the dependency contract is:

- dependencies are explicit and project-level, not file-level
- source files in the root project do not need `require` or `include` statements to activate dependency projects
- dependency projects may declare their own `dependencies`, and `scpp build` must resolve that graph transitively
- duplicate dependency visits should be deduplicated by normalized project root
- symbol collisions across participating projects must fail clearly during build or link
- shared-library packaging is a later build mode and is not the semantic meaning of `dependencies` in v1

## Project exports

Cross-project declaration visibility in v1 is controlled by explicit source-level export markers, not by exporting every declaration by default.

The current export marker is:

- `/** @lib-export */`

The v1 export contract is:

- `@lib-export` marks a declaration as part of the dependency-visible project surface
- dependency projects compose exported declarations into a generated project header under `.prism/generated/__project.hpp`
- consuming projects receive dependency project headers through `scpp build`; they do not activate dependencies through source-language `require` or `include`
- unexported declarations remain project-internal by default for cross-project use

The current supported exported declaration kinds are:

- top-level functions
- top-level classes
- top-level interfaces
- top-level constants

Minimal examples:

```php
<?php
/** @lib-export */
function shared_value(): int { return 7; }

/** @lib-export */
interface NamedThing {
    public function getName(): string;
}

/** @lib-export */
class NamedBox implements NamedThing {
    public function getName(): string { return "box"; }
}

/** @lib-export */
const SHARED_OFFSET = 5;
```

Producer/consumer example:

`shared/prism-utils/lib.phs`

```php
<?php
/** @lib-export */
function shared_value(): int { return 7; }

/** @lib-export */
const SHARED_OFFSET = 5;
```

`app/prism.json`

```json
{
  "entrypoint": "main.phs",
  "dependencies": [
    "../shared/prism-utils"
  ]
}
```

`app/main.phs`

```php
<?php
echo shared_value() + SHARED_OFFSET, "\n";
```

Interface/class example:

`contracts/lib.phs`

```php
<?php
/** @lib-export */
interface NamedThing {
    public function getName(): string;
}
```

`models/lib.phs`

```php
<?php
/** @lib-export */
class NamedBox implements NamedThing {
    public function getName(): string { return "box"; }
}
```

`app/main.phs`

```php
<?php
$box = new NamedBox();
echo $box->getName(), "\n";
```

## What this document intentionally does not solve

This is not the final deliberate multi-file semantic model. It does not yet freeze:

- source-language include / require graph semantics
- static `__DIR__` expression evaluation rules
- cross-file declaration merge rules
- duplicate-definition semantics
- file-init execution order across multiple source units

Those belong to the dedicated multi-file model spec. The v1 project dependency model above is only a build-composition contract for whole Prism projects.

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
    "modules": ["json", "filesystem"]
  }
}
```

Legacy list-style `runtime.languages` remains accepted as a compatibility shape and defaults PHP to profile `legacy`.

Current default behavior enables the `json` and `filesystem` runtime modules. `mysqli` remains opt-in. Unsupported language or module names must fail clearly during build configuration.

The normative meaning of PHP profile selection itself is defined in:

- `specs/php/library_profiles.md`
