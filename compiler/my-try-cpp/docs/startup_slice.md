# Startup catalog slice — 2026-10-26
Doc Status: supporting

The executable now performs configuration -> catalog loading/validation -> static
startup-model publication. When source roots are supplied, discovery, checked reading
and [compact tokenization](tokenization.md) complete before publication. Its state is `catalog_metadata_ready`, not semantic or
compiler readiness. Source parsing and resolved type/callable/scope installation
are not implemented yet.

## Run

From the repository root:

```sh
python3 compiler/my-try-cpp/tools/build.py runtime
python3 compiler/my-try-cpp/tools/build.py middleware
python3 compiler/my-try-cpp/tools/build.py test
compiler/my-try-cpp/build/compiler/my-try-cpp --catalogs runtime/metadata/modules --timings
```

Dependency updates remain explicit. Runtime packages select core, JSON, filesystem
and datetime providers. Catalog data is a separately supplied source-language input
directory, not the middleware naming registry or linked-library configuration.

`--catalogs DIRECTORY` is required. Repeated `--module NAME` selects an ordered
module list; defaults are core/json/strings/datetime/filesystem. `--jobs N` selects
a positive signed-32-range budget, default 12. Startup traversal is serial; storing
the budget does not create workers. `--help` performs no catalog IO. Unknown options,
missing values, duplicate selections and unsafe module names fail.

## Owners and publication

- `src/startup/configuration.*`: invocation parsing and module-name admission.
- `src/startup/catalog.*`: dependency traversal and private input inventory.
- `src/startup/validation.cpp`: bounded metadata validation described below.
- `src/startup/model.*`: the single static Model and lifecycle publication/reset.
- Middleware: concrete JSON views, byte strings, collection storage, filesystem,
  clock and terminal/argv adaptation. Compiler schema/graph rules stay in compiler code.

Each module is read once per initialization. Dependency traversal detects cycles and
limits dependency depth to 128. Catalogs are retained in dependency order. Immutable
JSON nodes own their document storage. The model retains these unresolved documents
and typed string identity inventories at the input boundary; they are not semantic
type/callable representations.

The lifecycle builds a private candidate inventory. Failure discards it and leaves
an uninitialized model unready. After all throwing work, noexcept swaps install the
configuration and inventory before readiness is published. This is a startup input
candidate, not a second complete compiler model.

Identical configuration reuses installed catalogs without rereading files. Changed
directory, module order or jobs reject while initialized; explicit reset is required.
Reset releases model roots; independently retained nodes remain valid. Initialization
and reset are coordinator-only and must not overlap readers/workers. Source revision
processing and semantic incrementality are future work.

## Exact validation boundary

Runtime JSON parsing checks syntax, duplicate properties, JSON depth and exact integer
forms. Compiler validation checks required envelope fields, version, filename/module
identity, allowed root fields, section kinds, dependency names/uniqueness/cycles;
required function signatures and passing modes; recursive unresolved type forms;
global function/type/property/C++-type identity uniqueness; per-module one-to-one
function/C++ bindings; base type-definition/storage/capability/generic-parameter
shapes; primary C++ representation fields; integer constants and owner-qualified
duplicates; property shape; and base build/non-function-binding shapes.

This is not full parity with my-try's reader plus semantic registration. Provider
extension payloads (default expressions, initialization/indexing/iteration recipes,
C++ argument recipes and boxed/shared/replacement relations) currently have outer
kinds checked and remain unresolved. Their complete nested schema and semantic
constraints must be checked before consumers use them. Unknown type references,
overload conflicts, capabilities, protocol prerequisites, collisions with language
builtins and backend layout/build policies require future semantic/backend owners.
No semantically-ready state is exposed by this slice.

## Evidence

The actual default catalogs load: 5 files, 76,752 bytes, 205 function contracts and
10 declared runtime types. The first local O2 trial reported about 6.3 ms internally:
3.7 ms loading/JSON parsing and 1.8 ms validation. Stage fields are emitted with
`--timings`; these exclude pre-main initialization, argv adaptation and teardown.
Publication can round to zero at microsecond resolution.

After consolidating directory validation in the lifecycle, a final local trial
reported 5,722 us total (109 us configuration, 3,617 us loading/parsing, 1,957 us
validation), with 5,072 KiB process peak RSS from `/usr/bin/time`. These are single
trials, not distributions. Main rebuild/link including the template check took
0.303 s; the four focused CTest groups took 0.32 s. A moved-project configure/build
and all tests passed with the existing dependency archives, including a path with
spaces. No routine compiler build recompiled runtime or middleware.

Focused tests cover invalid arguments, help without IO, shared dependencies read
once, cycles, malformed/duplicate JSON, missing/invalid fields, binding mismatches,
duplicate identities, failed initialization/retry, identical configuration reuse,
rejected reconfiguration, reset and node lifetime after reset. They use temporary
fixtures and do not write project catalogs.

This is not evidence of source-compilation performance, allocation counts, MT safety
or semantic incrementality. Facade implementation allocations and linear identity
inventories remain visible costs to measure as startup data grows.

Validation errors now retain field names and zero-based section/row indexes through
nested validation, in addition to the loader's catalog filename. For example, an
invalid parameter flag reports `functions[0]`, `parameters[0]` and `variadic`.
Context formatting occurs only on failures. Optional-section dispatch delegates to
named validators for constants, properties, build settings and non-function bindings.
See the [allocation baseline and follow-up](catalog_validation_profile.md) for
measured startup-validation costs after this cleanup.

The optional [source discovery slice](source_discovery.md) now follows catalog
validation before publication when `--source` roots are supplied. Discovery uses
the job budget; catalog loading remains serial. Source parsing is still absent.
