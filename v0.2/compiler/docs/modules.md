# Module manifests

Doc Status: supporting

The PHP compiler accepts a directory containing `module.json`, or an explicit
manifest filename. This is the bounded v0.2 loader; it does not replace the older
PRJ format or implement profiles, package resolution, or incremental reuse.

```sh
XDEBUG_MODE=off php v0.2/compiler/main.php project /path/to/app \
  v0.2/build/clang/native-layout/facts.json /path/to/output
```

This emits a bundle. Like `program`, it does not invoke Clang. The old explicit-file
command and panel input adapter remain available with their existing behavior.

## Minimal application

`module.json` may contain just `{}`. The compiler discovers supported sources,
selects the single root-level `main.cpp`, `main.php`, or `main.js` as entry, and adds
the runtime with its defaults. More than one candidate or no candidate is an error.
Library modules do not need an entry; their `main`-named sources are ordinary
secondary files, not additional executable entries.

```json
{
  "dependencies": {
    "runtime": {"modules": ["json"]},
    "shared": {"path": "../shared"}
  }
}
```

Optional manifest properties:

| Field | Meaning when present / default when absent |
| --- | --- |
| `name` | Unique graph name; defaults to the folder name. Names use letters, digits, `_`, `-`, `.` and start with a letter or underscore. |
| `type` | `default` for compiler sources, `c++` for native translation units. Defaults to `default`. |
| `entry` | Relative source path; executable selection otherwise finds root-level `main`. |
| `sources` | Explicit list or path-to-flavor object; omission discovers sources. An empty list selects none. |
| `dependencies` | Object of named dependencies; defaults to empty, with the runtime implicitly selected for the application. |
| `modules` | Object mapping public submodule names to directories or manifest paths. Registration alone does not activate them. |
| `defaults` | List of registered submodules activated alongside this module; defaults to empty. |

A dependency supports only `path` and `modules`. Its key must match the target
manifest's name. Paths are relative to the declaring manifest (absolute paths are
also accepted); directory paths resolve to `module.json`. Only the compiler-known
`runtime` may currently omit its path. Unknown fields, including `config`, reject.

A submodule's registered name is its activation alias; its manifest still has a
unique graph name. For example, runtime's `json` alias selects `runtime-json`.
Each submodule has its own manifest and dependencies.

## Activation and ownership

Activation is additive and idempotent. Defaults, explicit requests, and transitive
requests can select the same module without creating duplicate records or sources.
Canonical manifest paths identify loaded definitions; the same name at different
paths is an error. Real dependency cycles reject.

Activating a catalog child does not create a parent-to-child dependency edge.
The named dependency defines the dependency edge; its `modules` requests add
activation roots to the session. Defaults also add activation roots. This lets
children depend on their parent runtime without inventing sibling cycles merely
from selection. Each child declares its actual dependencies in its own manifest.
All selected modules use the existing session/module/source-unit owners. Source
membership and semantic visibility remain distinct; the current compiler still
publishes one flat global declaration scope.

## Discovery and inventory

Compiler-source extensions are `.cpp`, `.php`, `.js`. Native modules discover
`.cpp`, `.cc`, `.cxx`; these never enter tokenization, parsing or preparation.
Discovery is recursive and deterministic. It skips hidden entries, symlinks,
`build`, `generated`, `vendor`, `node_modules` directories (case-insensitive), and
nested directories containing `module.json`. Headers are not translation units.

An explicit list replaces discovery:

```json
{"entry": "src/main.cpp", "sources": ["src/main.cpp", "src/helpers.php"]}
```

An explicit flavor map allows custom extensions for compiler sources:

```json
{"entry": "main.scpp", "sources": {"main.scpp": "cpp"}}
```

All source paths must be canonical module-relative paths. They must exist within
the module root; duplicate physical ownership across selected modules rejects.
Explicit lists can include generated sources omitted by automatic discovery.

After successful graph validation the loader writes `module.sources.json` in each
selected root, using a temporary file and rename. It groups source paths/flavors by
module name, allowing multiple named manifests in one folder. Native inputs have
label `c++`. This file is generated inspection output, ignored by Git, never read
as configuration or a cache. Inventory publication errors fail loading. Configuration
and source inputs cannot be overwritten by the inventory. Files in separate roots
are published independently, not as a filesystem transaction.

## Native/runtime boundary

Native modules are libraries for this slice; the selected executable module must
be `default`. The bundle's `translation_units` contains generated compiler output;
`native_sources` separately lists absolute external native input paths. Native
inputs are never subject to generated-output cleanup. Native build consumers must
compile both lists, supplying the required headers/toolchain flags. No native ABI
bindings or additional source-language functions are introduced by loading a module.

The bundled runtime manifest selects its existing core generated implementation,
output and entry adapters. JSON and datetime are registered optional activations;
their manifests own the additional native sources and dependency lists. Generate
the matching runtime publication first using the existing runtime build options:
`SCPP_ENABLE_JSON` / `SCPP_ENABLE_DATETIME`. Missing generated sources reject; the
loader does not change generator settings or silently drop requested modules.
Private native include directories and toolchain/link flags remain the existing
native build's responsibility; no generic build/config option system is added.

The default CMake runtime build remains independent of per-application selection.
Native module inputs are consumed directly in the focused executable proof rather
than also linking the same prebuilt runtime archive (which would duplicate symbols).

Every manifest load rescans and every compilation rebuilds source ASTs. There is
no manifest cache, change detector, or incremental reuse yet.

## Checks

```sh
XDEBUG_MODE=off php v0.2/compiler/tests/manifests.php
XDEBUG_MODE=off python3 -B v0.2/compiler/tests/manifests_native.py
```

The native proof uses the default generated runtime and existing native facts. It
checks a mixed-flavor application, a separately loaded native source, runtime inputs,
program output/exit, and preservation of previous output after a graph error.
