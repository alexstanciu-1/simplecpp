# Module manifests and application compilation

Doc Status: supporting

```sh
XDEBUG_MODE=off php v0.2/compiler/main.php project /path/to/app \
  v0.2/build/clang/native-layout/facts.json /path/to/output [--force]
```

`Project_Compiler` owns loading, change checks, reuse, compilation and publication.
It returns an ordinary report array and writes no host output. The CLI owns argument
parsing, JSON formatting and exit codes. The explicit-file `program` command uses
the same compilation/publication worker; it always compiles its input inventory.
The panel's stage-specific frontend path remains unchanged.

The command emits C++ and a link manifest, not an executable. **Only the selected
application's sources are compiled.** Dependency modules of either type contribute
metadata and manually built artifacts. Runtime/module version checks, artifact
validation, and Simple C++ declaration export/import are [deferred debt](../../specs/planning/dependency_artifacts_debt.md).

## Manifest

A directory means its `module.json`; an explicit manifest path is also accepted.
A minimal application manifest is `{}`: discover its sources, select a single
root-level `main.cpp`, `main.php` or `main.js`, and include the runtime's defaults.

```json
{
  "dependencies": {
    "runtime": {"modules": ["json"]},
    "shared": {"path": "../shared"}
  }
}
```

| Optional field | Meaning/default |
| --- | --- |
| `name` | Folder name when omitted; unique within the selected graph. |
| `type` | `default` or native `c++`; defaults to `default`. The executable app must be `default`. |
| `cpp_namespace` | Selected app’s native namespace. Omit for `App`, use a qualified path such as `acme::ledger`, or JSON `null` for global emission. |
| `entry` | Application-relative entry; otherwise find exactly one root-level `main`. |
| `sources` | Application source list or path-to-flavor map; omission discovers, `[]` selects none. |
| `dependencies` | Named object of dependency requests; defaults to empty, plus implicit runtime for the application. |
| `modules` | Activation aliases mapped to submodule directories/manifest paths. |
| `defaults` | Aliases activated alongside the module; defaults to empty. |
| `artifacts` | Paths to manually built dependency libraries/objects; defaults to empty. No existence/version checks. |

Dependency entries accept `path` and `modules`. Their keys must match the loaded
module names. Paths are relative to the declaring manifest, or absolute. Only the
compiler-known `runtime` can omit its path. `config` and other unknown fields reject.
Names use letters, digits, `_`, `-`, `.` and start with a letter or underscore.

Dependency manifests' `sources` and `entry` are not consumed in an application
build. These modules have no loaded source units/ASTs. Their source or header files
need not be present. Missing or invalid manually declared artifacts do not prevent
bundle generation; the caller's linker may subsequently reject them.

## Activation and graph ownership

Defaults, explicit requests and transitive requests merge idempotently. Canonical
manifest paths identify modules; conflicting names and actual dependency cycles
reject. Registering a submodule alone does not activate it. Activation is separate
from dependency edges, so sibling defaults can both depend on their parent runtime.
A child's own manifest declares its actual dependencies.

The session owns selected modules; modules own their loaded source units. On the
manifest path only the selected app has loaded source units. `module.artifacts`
contains dependency link paths. The emitted manifest's `link_inputs` passes these
paths to the native build, with consumers before dependencies. No artifact is read
or scanned to produce this list, and no declaration import is implied.

The bundled runtime manifests point at the existing `build/clang` static archives.
Build the desired runtime modules manually with the existing CMake options. The
application compiler does not run CMake or the API generator. Do not additionally
link the same runtime implementation from source when using the declared archive.

## Application discovery and inventory

Application extensions are `.cpp`, `.php`, `.js`. Discovery is recursive and stable,
skipping hidden entries, symlinks, build/generated/vendor/node_modules directories,
nested module roots, known dependency directories, and the current output directory.
Application files may use different flavors in the same module.

An explicit list replaces discovery; a flavor map also permits custom extensions:

```json
{"entry":"src/main.scpp", "sources":{"src/main.scpp":"cpp"}}
```

Source paths must be canonical relative paths within the application root. Keep
application sources and dependencies in distinct directory trees; explicit source
selection identifies application files where directory ownership would be ambiguous.

Only the application's `module.sources.json` is published. Existing identical JSON
is left untouched, preserving its timestamp and inode. Changed inventories use a
staged file and atomic rename. Collision and symlink checks happen before the
comparison. Dependency inventories are never created, read or overwritten.
The inventory remains inspection output, not a configuration input or cache.

## Incremental boundary

The project command reloads graph metadata and discovers application membership.
It compares mtime (seconds) and size of application inputs, target facts and compiler
PHP code. It does not scan dependency trees or stat dependency artifacts. Known
nested dependency directories are excluded from application scans. Application
build directories, output, VCS state and generated inspection inventories are also
excluded. Explicit app sources remain tracked even under excluded build directories.

The saved input layout is version 4: module selection metadata, one source record
under its owning module, and `other_inputs`. Dependency source inventories are empty;
artifact paths are recorded as configuration, without filesystem metadata. Older
saved layouts cause one full rebuild. The previous output manifest is read once and
shared across baseline extraction, reuse checks and publication.

`source_unit` retains the general `change_status` enum and its mtime/size. Removed
app sources remain records for the change pass without entering active compilation.
Failures do not advance the baseline. All detected app changes trigger full compiler
preparation/emission; unchanged inputs reuse intact output by mtime/size checks.
A fresh final check rejects detected edits during compilation. There is no partial
AST reuse or concurrency synchronization.

Same-size edits preserving second-resolution mtime may go unnoticed. `--force`
requests a full application rebuild. Manually rebuilt dependencies require the
caller to relink; automatic invalidation for them is deferred.

## Checks

```sh
XDEBUG_MODE=off php v0.2/compiler/tests/project_compiler.php
XDEBUG_MODE=off php v0.2/compiler/tests/manifests.php
XDEBUG_MODE=off python3 -B v0.2/compiler/tests/incremental.py
XDEBUG_MODE=off python3 -B v0.2/compiler/tests/manifests_native.py
```
