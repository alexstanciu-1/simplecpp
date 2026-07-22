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
Prism++ source files start directly with Prism++ declarations or executable code. They must not rely on a leading `<?php` header or `declare(strict_types=1);`.
Inside docs and code, `PHP` refers to host tooling/runtime unless the surrounding text explicitly means `PHP++`.

Future `scpp build` composition must be able to select language layers and runtime modules deliberately rather than assuming one fixed all-in-one runtime surface.

### Path policy

Paths emitted into `build.ninja` are:

- relative to the configured `build_dir`
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

Optional build profiles may provide stable per-mode project state roots without
requiring scripts to rewrite `prism.json` between measurements:

```json
{
  "profiles": {
    "debug": {
      "build_dir": ".prism/build/debug",
      "generated_dir": ".prism/generated/debug",
      "cache_dir": ".prism/cache/debug",
      "build": {
        "mode": "debug"
      }
    },
    "release": {
      "build_dir": ".prism/build/release",
      "generated_dir": ".prism/generated/release",
      "cache_dir": ".prism/cache/release",
      "build": {
        "mode": "release"
      }
    }
  }
}
```

When `--mode=<debug|release>` is supplied to `scpp build` or `scpp run`, the
selected profile overrides the top-level state roots and `build` settings for
that invocation. If no matching profile exists, the command uses built-in
mode-separated roots under `.prism/build/<mode>`, `.prism/generated/<mode>`,
and `.prism/cache/<mode>`. Existing projects without `--mode` keep the current
top-level default behavior.

Projects may also set `build.grouping_policy` to one of `incremental`,
`isolated`, `package`, `folder`, `manual`, `release`, `auto`, or `none`.
Current v1 builds record the selected deterministic grouping policy and group
membership in build reports, while the actual Ninja graph still compiles one
generated/native object per source. This makes debug/incremental isolation and
future release grouping decisions visible without changing compile edges before
the dependency model can enforce them.

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
2. validates the configured entrypoint, or an explicit `--entry=<path>` override when one is supplied
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

By default, the public CLI commands `scpp build` and `scpp run` both reuse:

- the runtime artifact
- resolved Prism project dependencies

The current public opt-in rebuild flags are:

- `--build-runtime`
- `--build-dependencies`
- `--force`
- `--timings`

When `--build-runtime` is present, `scpp build` or `scpp run` recompiles the runtime artifact for the current build instead of reusing the existing runtime artifact path in the emitted Ninja graph.
This rebuild is for the current build/invocation. Shared reusable runtime refresh remains owned by `scpp update` and `scpp runtime-build`, while explicit custom/non-default runtime rebuilds stay on the project-local side.

When `--build-dependencies` is present, `scpp build` or `scpp run` still resolves the Prism project dependency graph for source discovery, export composition, and header visibility, and also recompiles dependency project units instead of reusing their existing object/artifact paths in the emitted Ninja graph.

When `--force` is present, `scpp build` or `scpp run` forces a runtime rebuild for the current build, even if the reusable runtime artifact already exists. `--force` implies runtime compilation.

The lower-level build service path used by helpers/tests also defaults to reuse mode unless it explicitly opts into runtime/dependency compilation. The public user-facing CLI contract is:

- `scpp build` reuses runtime and dependencies by default
- `scpp run` reuses runtime and dependencies by default, then executes the primary output
- both commands accept `--entry=<path>` to build or run a specific project-local source file instead of the configured `prism.json` entrypoint for that invocation only
- both commands accept `--mode=debug` and `--mode=release` to select a stable build profile/root set for that invocation only
- both commands accept `--timings` to print the internal `execute_build()` timing breakdown for that invocation only

### STAN pre-build check

`scpp build` and `scpp run` also consult the current STAN project state before generation/compilation unless the caller explicitly passes `--no-stan`.

Current v1 behavior:

- if a fresh matching STAN report already exists for the current project source fingerprint, the build reuses it and may start a background STAN worker when none is alive
- if a live STAN worker exists but its report is stale, the build requests a refresh and waits briefly for a matching ready result; if the worker does not publish in time, the build falls back to an inline compile-gating STAN check
- if no live STAN worker exists and the current report is stale, the build performs an inline compile-gating STAN check, starts a worker for the full advisory/status/report refresh, and continues only if the compile-gating diagnostics are clean
- while only the compile-gating check is fresh, or when `--no-stan` bypasses the STAN pre-build check, the build writes a fresh build-owned dependency summary and may activate scoped project-unit packs from that summary; sources without complete dependency evidence keep broad fallback
- background worker refreshes debounce source edits before proactive analysis; explicit build refresh requests bypass that debounce
- if STAN reports `compile-errors`, the build stops before C++ generation/compilation continues
- if STAN reports only advisory findings, the build continues and prints a short static-analysis summary
- `--no-stan` bypasses this STAN pre-build check for that invocation only

Current limitation for early testing:

- the STAN source fingerprint currently covers the root project's `prism.json` plus the root project's participating `*.phs` / compatible `*.php` files
- dependency project source changes are not yet part of that fingerprint, so warm STAN reuse can be stale across dependency-only edits until the root project is reanalyzed again

## `scpp clean` behavior

`scpp clean` removes generated project state so the next `scpp build` is a cold rebuild.

The command:

1. finds `prism.json` by walking upward from the current directory
2. resolves the root project and its Prism project dependency graph
3. removes each participating project's `.prism/` working tree when the configured `build_dir`, `generated_dir`, and `cache_dir` all live inside it
4. otherwise removes each participating project's configured `build_dir`, `generated_dir`, and `cache_dir`
5. includes configured profile roots from `profiles.*.build_dir`, `profiles.*.generated_dir`, and `profiles.*.cache_dir`
6. treats missing clean targets as already clean
7. refuses to remove the project root, filesystem root, non-directory targets, or paths outside the owning project root

## `scpp update` behavior

`scpp update` updates the installed `scpp` repository checkout from GitHub `main`.

The command:

1. resolves the active `scpp` repository root
2. requires Git to be available
3. requires the checkout to be on branch `main`
4. requires a configured `origin` remote
5. requires a clean working tree
6. fetches `origin main`
7. fast-forwards the checkout to `origin/main`
8. rebuilds the default reusable runtime cache when the checkout actually changes
9. when `--force` is present, rebuilds that default reusable runtime cache even if the checkout was already current

If an existing project shows unexpected build, reuse, or generation behavior immediately after `scpp update`, and the same problem does not reproduce in a fresh project, users should treat a one-time project-state reset as a normal troubleshooting step:

```bash
scpp clean
scpp build
```

This note is specifically about stale per-project `.prism/` state after an update, not about the normal steady-state workflow.
10. fails clearly instead of creating merge commits or overwriting local changes

## `scpp runtime-build` behavior

`scpp runtime-build` rebuilds the reusable runtime cache explicitly without compiling the current project graph.
This command is the explicit maintenance path for shared reusable runtime artifacts, alongside the automatic refresh that happens during `scpp update`.

The command:

1. resolves the active `scpp` repository root
2. optionally discovers the current project config to inherit runtime language/module/build settings when run inside a project
3. defaults to debug runtime mode
4. accepts `--release` to build the release runtime variant
5. accepts `--force` to delete and rebuild the selected runtime artifact even if it already exists

## `scpp docs` behavior

`scpp docs` prints curated local documentation without requiring network access.

The command:

1. resolves the active Simple C++ repository root
2. maps a short doc name to a checked-in Markdown source
3. prints the requested name, human-readable title, source path, and document content
4. lists known doc names when no name, `list`, `-h`, or `--help` is supplied
5. fails clearly when the requested doc name is unknown or the mapped source is missing

The initial registry includes:

- `strict`
- `php-strict`
- `quick-learn`
- `build`
- `getting-started`
- `diagnostics`
- `profiles`
- `modules`
- `dependencies`

## `scpp last-run`, `scpp full-last-run`, and `scpp explain-build` behavior

`scpp last-run` prints a short human-readable summary of the most recent saved build-oriented command.

`scpp full-last-run` prints the complete saved `.prism/last_run.json` payload.

`scpp explain-build` reads `.prism/last_run.json` and explains:

- whether runtime and dependencies were rebuilt or reused
- which source files were transpiled versus reused
- why a transpile happened when a source file rebuilt
- which outputs changed in the most recent saved build
- rebuild fanout for the most recent saved build, including changed objects,
  changed/removed project-unit packs, and Ninja no-work status
- the active build grouping policy, deterministic group membership, changed
  groups, and object fanout
- which generated project unit force-include headers were assigned, including
  unit counts, header size, pack-change counts, and report-only per-source
  dependency summaries

`scpp explain-build` also accepts focused view arguments so users do not need to scan the full summary when they only want one answer:

- `scpp explain-build files-transpiled`
- `scpp explain-build files-reused`
- `scpp explain-build outputs-rebuilt`
- `scpp explain-build rebuild-fanout`
- `scpp explain-build grouping`
- `scpp explain-build project-units`
- `scpp explain-build project-unit <source>`
- `scpp explain-build entrypoint`
- `scpp explain-build final-output`
- `scpp explain-build generated-files`
- `scpp explain-build ninja-target`

The default `scpp explain-build` summary should also print the direct Ninja target name for the current executable and warn that the built executable path, such as `.prism/build/main`, is not itself a valid Ninja target name.

The saved `.prism/last_run.json` payload should include build explanation details gathered during `execute_build()` so the explanation command does not need to reverse-engineer the build after the fact.

Saved successful build details include a `rebuild_fanout` summary. The summary
counts changed outputs, changed object files by generated/native/runtime owner,
changed and removed project-unit pack headers, and whether Ninja observed a
no-work build. The same normalized fanout is stored under
`details.build_explanation.rebuild_fanout` so `scpp explain-build
rebuild-fanout` can report it without recomputing mtimes or build ownership.

Saved successful and failed build details include
`details.build_explanation.build_grouping`. The grouping report stores the
active policy, policy source, report-only status, current compile-unit strategy,
deterministic group rows, changed groups, and object fanout. The focused
`grouping` view renders this data, including group membership. In current v1,
grouping policy selection does not change the Ninja compile graph; it exposes
the planned grouping boundary and measured fanout for incremental and release
workflow tuning.

The `project-units` view reports the current project unit force-include fanout
and per-source dependency summaries. These summaries may use STAN's stored
dependency keys when available. When `--no-stan` is used, the build writes a
fresh build-owned lightweight project-unit dependency state from
frontend/source summaries so diagnostics can still show direct dependency
evidence and safe generated units can still use scoped packs. Current v1 builds
activate scoped project unit packs for generated PHS units whose dependency
summaries are classified as `candidate_scoped`; blocked generated units,
sources without complete dependency state, and native C++ units keep using
broad-equivalent project unit packs.

Native C++ units are intentionally reported under a separate native policy.
Current v1 policy is `broad_fallback_without_dependency_manifest`: native C++
files are force-included with the project's broad-equivalent pack because the
build does not yet have a native dependency manifest format. The report includes
`native_units`, `native_broad_fallback_units`, and `native_policy` so this
fallback is visible instead of being mixed into generated-source scoped
candidate counts.

The same diagnostic payload reports the scoped-pack candidate for each
generated PHS unit. Candidate rows include the scoped header list, the stable
candidate hash/path, a candidate status, and any blocking reasons that kept the
active compile edge on the broad-equivalent pack.

Each successful build also writes a build-owned per-source dependency summary
artifact at `.prism/cache/project_unit_dependency_summary.php`. The artifact
stores the normalized project-unit planning row for each generated source:
direct source keys, direct local generated headers, transitive scoped local
headers, dependency export headers, candidate pack status/path, candidate
blockers, dependency categories, and per-source freshness inputs. The saved
project-unit report includes a compact `dependency_summary_artifact` pointer so
diagnostics can find the artifact without embedding every freshness row in
`.prism/last_run.json`.

Current lightweight source dependency summaries derive direct dependency keys
from inheritance and interface declarations, `use` declarations, class property
types, class constant value expressions, function/method parameter and return
types, and conservative function body evidence such as direct function calls,
static-call class names, static calls in control-flow conditions, layout-probe
type operands, typed locals, constructed locals, and typed return descriptors.
These keys are used to report direct source dependencies and direct generated
local headers for scoped-pack candidates.

Scoped candidate packs direct-include same-project generated headers for the
owning source unit's direct project-header dependencies. Transitive closure then
follows only public/header dependencies of those direct dependencies, rather
than their body-only implementation dependencies. This keeps a unit that
directly depends on a derived class from missing that class's base header without
pulling unrelated implementation-only headers through every consumer. The scoped
candidate header list preserves that dependency walk order, so public/header
dependencies are emitted before the generated headers that require them.

Current v1 scoped activation remains conservative. Files with class properties
or compact-layout struct/union fields may activate scoped packs when their
direct property type dependencies resolve to generated headers, and those
headers are placed before the owning header in the scoped pack. Enum-typed value
fields use the same property-layout dependency evidence, while fixed-width enum
backing types do not add generated-header dependencies. Files with class
constants may activate scoped packs only when their initializer descriptors are
simple scalar expressions, recursively simple arithmetic/conditional/string-concat
expressions, or resolved class-constant references whose generated headers are
included before the owning header. Files with top-level constants use the same
initializer safety rule under `constant_value` dependency rows; unmodeled
initializer evidence, such as array literals or calls, keeps the unit on broad
fallback.
Top-level helper function bodies may activate scoped packs only when their body
evidence is limited to resolved direct function/static calls and resolved type
references. Class method bodies may also activate scoped packs when their
method-body dependency evidence is resolved or points only at core runtime
shallow symbols, and their call sites remain in the locally modeled
function/static-method forms. Layout probes count their type-name operand as a
body type dependency because the generated C++ applies `sizeof`, `alignof`, or
field layout operations to the complete type. Executable bodies, unresolved
non-runtime calls/types, unsupported body call shapes, local invalidation
evidence, and unmodeled body evidence keep broad fallback.

Saved per-source dependency summary rows also include compact dependency
category evidence. The current categories distinguish inheritance, direct type
references, function signatures, method signatures, function bodies, method
bodies, executable bodies, property layout, class constant values, constant
values, unresolved symbols, unresolved dependency keys, and missing source
summaries. The focused `scpp explain-build project-units` view renders a
compact overview for large projects: fanout counters, capped header rows,
blocker histograms, and one compact row per source up to a display cap. The
`scpp explain-build project-unit <source>` view renders the verbose candidate
pack, direct dependency, header, category, blocker, and reason details for one
source row.

The project-unit report also includes scoped fanout counters: active scoped
generated units, active broad-fallback generated units, scoped candidates, and
blocked candidates. These counters are intended to make rebuild-fanout changes
measurable as dependency precision expands.

The project-unit report also records `pack_changes` for the current build:
changed project-unit pack headers and removed build-owned stale pack headers.
The broad alias, broad hash packs, and scoped hash packs are included in this
measurement; custom non-build-owned files in `.prism/generated/__project_units/`
remain outside cleanup and reporting.

Blocked candidate reports include blocker histograms so the most common reasons
for broad fallback can be tracked across a project without scanning every unit
row.

Generated source rows in the saved build explanation also carry the active
project-unit status, force-include header, and header mode so focused views such
as `generated-files` can show which pack each generated object compiles with.
- `examples`
- `authoring`
- `gotchas`
- `skill`
- `agents`

This command is a documentation discoverability helper. It does not change language semantics or document authority.

## Project dependencies

Project composition is controlled by `scpp build`, not by source-language `require`, `require_once`, `include`, or `include_once`.

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
- source files inside the same project may refer to same-project declarations discovered by `scpp build` without source-language `require`, `require_once`, `include`, or `include_once` statements
- source files in the root project do not need `require`, `require_once`, `include`, or `include_once` statements to activate dependency projects
- in the current strict-project model, those source-language file-inclusion forms should not be used for project composition
- dependency projects may declare their own `dependencies`, and `scpp build` must resolve that graph transitively
- duplicate dependency visits should be deduplicated by normalized project root
- symbol collisions across participating projects must fail clearly during build or link
- shared-library packaging is a later build mode and is not the semantic meaning of `dependencies` in v1
- dependency resolution remains active even when dependency compilation is not requested; reuse only suppresses recompilation of already-built dependency units

`scpp build` generates internal project unit headers under `.prism/generated/`.
The compatibility broad header remains `.prism/generated/__project_units.hpp`.
Current compile edges force-include deterministic project unit pack headers
under `.prism/generated/__project_units/<hash>.hpp` or
`.prism/generated/__project_units/scoped-<hash>.hpp`. The non-scoped packs are
broad-equivalent fallbacks; scoped packs are used only for safe generated PHS
units. These headers are build artifacts only. PHP++ source must not name
generated `.hpp` files.

The project unit headers include:

- `__project_fwd.hpp`, a namespace-correct forward declaration header for same-project classes
- all generated headers for the same project
- generated `__project.hpp` export headers from transitive Prism project dependencies

When same-project generated headers contain inheritance relationships discovered from the generated class declarations, base-class headers are emitted before derived-class headers in the project unit headers.

Dependency project export headers are included before the consuming project's local generated headers so local headers may reference exported dependency types without source-level generated-header includes. The dependency export header uses the same discovered base-before-derived ordering for its generated headers.
Scoped packs for consuming project units use dependency project `__project.hpp`
export headers for cross-project declarations; they do not direct-include
dependency generated unit headers as local headers. Scoped packs may still
direct-include generated headers from the same owning project.

Generated source cache entries distinguish standalone entrypoint emission from dependency-unit emission. A file generated with a program `main` for standalone execution must be regenerated when reused as a dependency unit so dependency objects do not export duplicate executable entrypoints.

## Project exports

Cross-project declaration visibility in v1 is controlled by explicit source-level export markers, not by exporting every declaration by default.

The current export marker is:

- `/** @lib-export */`

The v1 export contract is:

- `@lib-export` marks a declaration as part of the dependency-visible project surface
- dependency projects compose exported declarations into a generated project header under `.prism/generated/__project.hpp`
- consuming projects receive dependency project headers through `scpp build`; they do not activate dependencies through source-language `require`, `require_once`, `include`, or `include_once`
- unexported declarations remain project-internal by default for cross-project use

The current supported exported declaration kinds are:

- top-level functions
- top-level classes
- top-level interfaces
- top-level constants

Minimal examples:

```php
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
echo shared_value() + SHARED_OFFSET, "\n";
```

Interface/class example:

`contracts/lib.phs`

```php
/** @lib-export */
interface NamedThing {
    public function getName(): string;
}
```

`models/lib.phs`

```php
/** @lib-export */
class NamedBox implements NamedThing {
    public function getName(): string { return "box"; }
}
```

`app/main.phs`

```php
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
    "modules": ["json", "filesystem", "datetime"]
  }
}
```

Legacy list-style `runtime.languages` remains accepted as a compatibility shape and defaults PHP to profile `legacy`.

Current default behavior enables the `json`, `filesystem`, and `datetime` runtime modules. `mysqli` and `regex` remain opt-in. Unsupported language or module names must fail clearly during build configuration.

The normative meaning of PHP profile selection itself is defined in:

- `specs/php/library_profiles.md`
