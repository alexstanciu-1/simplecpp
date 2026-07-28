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
`isolated`, `package`, `folder`, `manual`, `module`, `release`, `auto`, or
`none`.
Current v1 builds record the selected deterministic grouping policy and group
membership in build reports. By default, the Ninja graph compiles one
generated/native object per source. Explicit `build.grouping_compile` opt-ins
can enable grouped generated-object edges for manual groups or release-mode
folder/package/module/release grouping.

When `build.grouping_policy` is `manual`, `build.grouping` must be an object
mapping manual group names to project-relative source lists:

```json
{
  "build": {
    "grouping_policy": "manual",
    "grouping": {
      "domain": ["src/domain/base.phs", "src/domain/model.phs"],
      "native-tools": ["native_cpp/probe.cpp"]
    }
  }
}
```

Manual grouping validates path escapes, duplicate assignments, unknown sources,
empty groups, and non-list group values. Without `build.grouping_compile`,
manual groups are report-only: assigned root-project generated/native sources
are reported under their manual groups, unassigned root-project sources stay
isolated, and dependency-project sources stay under their deterministic
policy-derived groups.

When `build.grouping_compile` is `true`, manual groups with at least two
root-project generated sources emit a grouped generated object edge. The build
writes a generated group source under `.prism/generated/__build_groups/`,
compiles one grouped object under `.prism/build/__build_groups/`, and links that
object once. Native C++ sources, unassigned sources, dependency-project sources,
and manual groups with fewer than two generated sources continue using
per-source object edges in this first slice.

For non-manual policies, `build.grouping_compile = true` is accepted only in
release build mode and only with `folder`, `package`, `module`, `release`, or
`auto` grouping. These release-mode grouped generated edges use the selected
deterministic policy to form root-project generated-source groups, skip
entrypoint generated sources, and keep singleton groups, dependency-project
sources, and native C++ sources as per-source object edges. The `module` policy
uses explicit `project_modules` membership, excludes entrypoints from grouped
objects, and leaves unassigned or singleton module sources isolated. This keeps
debug isolation as the default while allowing explicit release/O3 unity-style
generated objects.

When `build.grouping_policy` is `auto`, the build records deterministic
auto-grouping evidence in the grouping report. Debug auto builds select
per-source `incremental` grouping. Release auto builds read the previous
`.prism/last_run.json` when available: no prior evidence starts with `folder`,
narrow prior generated-object fanout preserves `folder` granularity, large
release evidence selects `release`, and moderate evidence selects `package`.
Auto source decisions then isolate entrypoints, sources whose previous object
generated artifacts changed, sources whose previous object is at least 8 MiB,
and singleton would-be groups. The report stores the selected policy, evidence
source, prior timing/fanout values, and the per-source grouped/isolated reason.

`build.object_cache = true` opts into the local object action cache prototype.
The cache stores generated and native object outputs under
`.prism/cache/object_actions/` by the recorded object action key. Before Ninja
runs, a matching cached object may be restored into the build directory and its
object edge is rendered as a phony edge for that invocation so Ninja can link
the restored object without re-running the compiler. After a successful build,
object outputs are stored or preserved in the cache. The cache is project-local,
uses only local action-key identity, and is not a remote cache.

`scpp build` also writes a build planner warm-state snapshot under
`.prism/cache/build_planner_state.json`. The snapshot records the resolved
project graph, source metadata rows, native source rows, module summaries, and
the available STAN summary fingerprint. On later invocations, source content
hashes may be reused from this state when size, mtime, and ctime match and the
previous timestamp observation is safely settled. The report is observable in
`.prism/last_run.json` and `scpp explain-build build-planner`. This is a
process-to-process warm-state snapshot, not a live resident daemon or remote
cache.

Project-local compile-time modules may be declared with `project_modules`.
These are distinct from `runtime.modules` and describe build/report boundaries
inside one project:

```json
{
  "project_modules": [
    {
      "name": "domain",
      "sources": ["src/domain/base.phs"],
      "source_roots": ["src/domain"],
      "dependencies": []
    },
    {
      "name": "app",
      "source_roots": ["src/app"],
      "dependencies": ["domain"]
    }
  ]
}
```

Each module may provide `sources`, `source_roots`, `dependencies`, and
`public_exports`. Source paths and roots are project-relative. Current v1 builds
write report/cache artifacts for module public surfaces and implementation
artifact sets, store module interface and implementation hashes, and report
consumer invalidation from dependency interface hash changes. The current Ninja
compile graph defaults to per-source objects, with opt-in grouped generated
object edges for manual groups and release-mode grouping policies, including
explicit module groups. Generated object compile edges for sources assigned to a
module take the owning module's public surface artifact and each depended
module's public surface artifact as implicit inputs. Private implementation
artifacts are not compile inputs for consumers.

Projects may set `project_module_dependency_policy` to `report`, `warn`, or
`fail`; the default is `report`. Module dependency validation compares explicit
`project_modules.*.dependencies` against direct source dependency evidence from
the project-unit dependency summaries. With STAN evidence, the report records
`evidence_source = "stan"`; with `--no-stan`, the build-owned dependency
summary is used and reported as `evidence_source = "build"`. Missing evidence is
reported as `unavailable` and does not invent violations. `warn` prints
undeclared dependency diagnostics, while `fail` stops the build on undeclared
module dependency evidence. Unused declared dependencies are reported but do not
fail by themselves.

Projects may set `project_module_public_policy` to `report`, `warn`, or
`fail`; the default is `report`. A non-empty `project_modules.*.public_exports`
list is treated as that module's public declaration allow-list. Empty
`public_exports` is unconstrained for compatibility. Public API validation uses
file-summary declaration evidence plus project-unit dependency category rows to
report unknown configured exports and cross-module references to declarations
outside the dependency module's non-empty public export list. Missing evidence
is reported as `unavailable` and does not invent violations. Current validation
is declaration-target based; it does not model method/property-level API
privacy as a separate module export surface.

Each configured project module also writes a module analysis summary artifact
from the same project-unit dependency summaries. These artifacts live under
`.prism/cache/project_modules/*.stan-summary.json`, record whether their
evidence came from STAN or the build-owned no-STAN summary, and store a stable
per-module dependency summary hash. The hash is based on module-local dependency
evidence and analyzer signature inputs rather than the whole-project source
fingerprint, so private implementation edits that do not change dependency
evidence should not churn every module summary artifact. Current v1 builds
report module analysis cache hit/new/changed/unavailable status, but this data
does not yet skip planner or STAN work.

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
8. recursively scans the root project tree and all dependency project trees for `*.phs` files and compatible `*.php` files (pruning each project's `.prism/` working tree and VCS metadata directories)
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
- both commands accept `--timings` to print the internal `execute_build()` timing breakdown for that invocation only, including source inventory discovery, pre-Ninja diagnostics/cache work, Ninja subprocess time, post-Ninja diagnostics/cache work, and saved-report writing

### STAN pre-build check

`scpp build` and `scpp run` also consult the current STAN project state before generation/compilation unless the caller explicitly passes `--no-stan`.

Current v1 behavior:

- if a fresh matching STAN report already exists for the current project source fingerprint, the build reuses it and may start a background STAN worker when none is alive
- if a live STAN worker exists but its report is stale, the build requests a refresh and waits briefly for a matching ready result; if the worker does not publish in time, the build falls back to an inline compile-gating STAN check
- if no live STAN worker exists and the current report is stale, the build performs an inline compile-gating STAN check, starts a worker for the full advisory/status/report refresh, and continues only if the compile-gating diagnostics are clean
- while only the compile-gating check is fresh, or when `--no-stan` bypasses the STAN pre-build check, the build writes or reuses a build-owned dependency summary so diagnostics can still show direct dependency evidence
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
- `examples`
- `authoring`
- `gotchas`
- `skill`
- `agents`

This command is a documentation discoverability helper. It does not change language semantics or document authority.

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
- generated artifact write counters for content-aware generated headers and
  generated sources, including written, preserved, first-recorded, and
  interface/implementation-changed counts
- captured Ninja explain output when `SCPP_NINJA_EXPLAIN=1` or an internal
  explain probe is enabled, including normalized object-to-cause mappings
- generated/native object action identity capture status; full action keys,
  command/input/output hashes, primary inputs, member sources, implicit inputs,
  module surface inputs, and per-input fingerprints are captured only when
  explicitly requested, when object cache is enabled, for Ninja explain/probe
  diagnostics, or for compile/link failure diagnostics
- local object action cache restore/store counts when `build.object_cache` is
  enabled
- build planner warm-state status, graph counts, source metadata hit/miss
  counts, hash reads/reuse, and load/source-scan/write timings
- the active build grouping policy, deterministic group membership, changed
  groups, and object fanout
- which generated project unit force-include headers were assigned, including
  unit counts, header size, pack-change counts, and report-only per-source
  dependency summaries
- configured project-local modules, their public surface artifacts, interface
  and implementation hashes, cache status, implementation artifact sets, and
  consumer invalidation from dependency interface hash changes

`scpp explain-build` also accepts focused view arguments so users do not need to scan the full summary when they only want one answer:

- `scpp explain-build files-transpiled`
- `scpp explain-build files-reused`
- `scpp explain-build outputs-rebuilt`
- `scpp explain-build rebuild-fanout`
- `scpp explain-build generated-artifacts`
- `scpp explain-build ninja-explain`
- `scpp explain-build action-identity`
- `scpp explain-build object-cache`
- `scpp explain-build build-planner`
- `scpp explain-build grouping`
- `scpp explain-build project-units`
- `scpp explain-build project-unit <source>`
- `scpp explain-build modules`
- `scpp explain-build module <name>`
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

Saved build details also include a `generated_artifact_writes` summary. The
summary counts generated headers and generated sources separately by
content-aware write result: written, preserved, first-recorded, and
interface/implementation changed. The same normalized summary is stored under
`details.build_explanation.generated_artifact_writes` so `scpp explain-build`,
`scpp explain-build generated-artifacts`, and generated-file focused views can
report generated write behavior without reading current generated file mtimes.

When the build runs Ninja with `-d explain`, saved build details also include a
`ninja_explain` summary. The summary records the trigger source, normalized
Ninja explain messages, and object-to-message mappings for known generated and
native object paths. The same normalized summary is stored under
`details.build_explanation.ninja_explain`, and generated source rows may carry
`object_rebuild_ninja_explain` when their object path was matched. Current
source-level hash, module, project-unit, runtime, and dependency reasons remain
preferred; Ninja explain messages are used as a fallback before the generic
"no source/interface/project-unit cause" text. `scpp explain-build
ninja-explain` renders the captured object explanations.

Saved successful build details also include an `object_action_identity` summary,
but ordinary successful builds keep this summary lightweight by default. The
lightweight summary records `capture_mode = "off"` and a capture reason instead
of hashing every object action input and output. Full generated/native object
compile action rows are captured when `build.object_action_identity = true`,
when `SCPP_OBJECT_ACTION_IDENTITY=full`, when `build.object_cache = true`, when
Ninja explain/probe diagnostics are enabled, or when compile/link failure
diagnostics are being saved. Full rows include a stable action key, command
hash, input hash, output hash, compiler identity, build mode, selected
environment values, primary input, member source labels, generated inputs,
implicit inputs, force-include headers, module surface inputs, and per-input
fingerprints. The same normalized summary is stored under
`details.build_explanation.object_action_identity`, and source rows may carry
`object_action_key` plus `object_action_kind` only when full capture matched
their object path. `scpp explain-build action-identity` renders either the
recorded full rows or the lightweight not-captured reason.

Saved successful build details also include an `object_cache` summary. The
summary records whether the local action cache was enabled, where the project
cache lives, per-action restore results before Ninja, and per-action store
results after a successful Ninja run. Restore rows distinguish misses,
restored hits, already-current hits, and skipped entries; store rows distinguish
stored, preserved, and skipped entries. The same normalized summary is stored
under `details.build_explanation.object_cache`. The focused
`scpp explain-build object-cache` view renders the cache policy and restore/
store rows.

Saved successful and failed build details include
`details.build_explanation.build_grouping`. The grouping report stores the
active policy, policy source, report-only status, current compile-unit strategy,
deterministic group rows, changed groups, and object fanout. The focused
`grouping` view renders this data, including group membership. For most
policies, grouping selection remains report-only and exposes the planned
boundary and measured fanout for incremental, manual, and release workflow
tuning. When `build.grouping_compile = true` emits grouped generated edges, the
report uses status `active_generated_edges`, records whether generated grouping
is enabled, and stores changed group reasons. Manual grouped edges use compile
strategy `manual_grouped_generated_objects`; release-mode module grouped edges
use `module_grouped_generated_objects`; release-mode folder/package/release/auto
grouped edges use `release_grouped_generated_objects`. For manual grouping, the
report also stores configured manual groups, assigned source count, unassigned
root source count, and unassigned root source names. For module grouping, the
report stores explicit module groups, assigned source count, and unassigned
non-entrypoint root generated source names. For auto grouping, the report also
stores `auto_evidence` and `auto_source_decisions`, and the focused `grouping`
view renders the selected auto policy plus source-level grouping/isolation
reasons.

Saved build details also include `details.build_explanation.project_modules`.
When `project_modules` is configured, each module row stores source membership,
module dependencies, public exports, interface hash, implementation hash,
surface artifact path, implementation artifact paths, module analysis summary
artifact path, module analysis summary hash/cache status, surface cache status,
and whether consumers must rebuild because a depended module's interface hash
changed.
The project module report also stores dependency validation policy, evidence
source, inferred dependencies, undeclared dependency violations, unused declared
dependencies, and per-module validation status.
Per-project module surface artifacts are written under
`.prism/cache/project_modules/*.surface.json`, private implementation metadata
is written under `.prism/cache/project_modules/*.implementation.json`, module
analysis summaries are written under
`.prism/cache/project_modules/*.stan-summary.json`, and a stable manifest is
written at `.prism/cache/project_modules/manifest.json`. The focused `modules`
view renders the module overview, while `module <name>` renders one module row.

The `project-units` view reports the current project unit force-include fanout
and per-source dependency summaries. These summaries may use STAN's stored
dependency keys when available. When `--no-stan` is used, the build writes a
fresh build-owned lightweight project-unit dependency state from
frontend/source summaries so diagnostics can still show direct dependency
evidence. Ordinary v1 builds keep generated and native units on
broad-equivalent project unit packs. Experimental scoped-pack activation is
opt-in with `build.project_unit_scoped_packs = true` or
`SCPP_PROJECT_UNIT_SCOPED_PACKS=1`; in that mode generated PHS units may use
scoped packs only when their dependency summaries are safe for activation, while
hard-blocked generated units, sources without complete dependency state, and
native C++ units keep using broad-equivalent project unit packs.

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

Current v1 scoped activation remains conservative and opt-in. Files with class properties
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

## `scpp build-benchmark` behavior

`scpp build-benchmark` records repeatable invalidation measurements for the
current project without mutating the project sources under test. The command
finds `prism.json`, copies the project into `.prism/build_benchmarks/<id>`,
excludes `.git` and the original `.prism` state from the initial copy, seeds a
debug baseline build, and writes `.prism/build_invalidation_benchmark.json`.
Like `scpp build`, it reuses existing runtime artifacts by default; pass
`--build-runtime` when the isolated benchmark work tree should seed a
project-local runtime artifact before measuring scenarios.
Unless `--keep-workdir` is supplied, the disposable benchmark work tree is
removed after the report is published.

The command always measures:

- `warm_no_change`

The following edit scenarios are measured only when the caller supplies an
explicit project-relative source selector:

- `--private-source=<path>` for `private_body_edit`
- `--public-source=<path>` for `public_surface_edit`
- `--coordinator-source=<path>` for `broad_coordinator_edit`
- `--release-source=<path>` for `release_o3_hot_edit`

Missing selectors produce `skipped` scenario rows. The command does not infer or
auto-propose module/private/public sources. Explicit selectors are required so
large-project benchmarks remain intentional and repeatable.

The report stores one row per scenario with `status`, `build_mode`,
`source_selector`, mutation metadata when an edit was applied, and metrics
extracted from the build's saved explanation:

- total wall time and saved build duration
- Ninja subprocess time
- transpiled and reused source counts
- generated header/source write counters
- object rebuild fanout
- grouping fanout and changed-group count
- project module cache status counts
- project module analysis summary cache status counts
- Ninja explain probe summary

Measured builds enable the internal Ninja explain probe so the benchmark report
can be compared with `.prism/last_run.json` rebuild provenance when a work tree
is retained for inspection.

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
broad-equivalent fallbacks and are the default for ordinary builds; scoped packs
are used only for safe generated PHS units when scoped activation is explicitly
enabled. These headers are build artifacts only. PHP++ source must not name
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

When the opt-in `tasks` module is enabled, `runtime.tasks.default_worker_pool_size`
may set the process-owned reusable tasks worker-pool keepalive target at runtime
startup:

```json
{
  "runtime": {
    "modules": ["json", "filesystem", "datetime", "tasks"],
    "tasks": {
      "default_worker_pool_size": 4
    }
  }
}
```

Non-positive values disable the startup pool default. A nonzero value is
project-specific runtime composition and must not be served from a shared runtime
module cache compiled for a different pool size.

The normative meaning of PHP profile selection itself is defined in:

- `specs/php/library_profiles.md`
