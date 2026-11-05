# Retained incremental compilation through executable builds

Doc Status: supporting

The boundary is body preparation, source-unit lowering/emission, and native object
rebuilding. The existing emitter and Ninja build writer remain the implementations;
there is no second lowering pipeline or function-level output cache.

## Ownership and use

```php
$project = new Incremental_Project($application, $output_directory, new entry_target(31));
$report = $project->run();
// Host builds manifest.json translation_units with the existing Ninja writer, then runs the executable.
// Edit source files; retain $project and the native build directory.
$report = $project->run();
// Host updates the graph if membership changed and invokes Ninja again.
```

`Incremental_Project` owns one current session and the last successfully published
`cpp_program_output`. App path, output directory and native target are fixed for its
lifetime. This is an in-process API; the existing frontend-only socket service and
full-build CLI/panel routes are not silently changed. Changing configuration/target
for a new build context should create a new coordinator. Dependency artifacts still
follow the existing manually-built boundary.

`preparation_work.changed_sources` is one deduplicated source-key set, separate from
pending bodies. Enqueuing a body marks its source. Successful frontend publication
also marks reparsed roots, including files containing only constants or globals.
The set survives successful body preparation, subsequent analysis generations and
publication failures. Only successful C++ bundle publication clears it. Removed
sources disappear from the output inventory; they do not need a body or emission job.

After successful incremental preparation, touched current roots settle their existing
file-completion state. The coordinator creates the existing `prepared_entry` and
`prepared_program` handles. Old program handles reject retired sessions or pending
preparation. Facts remain on the AST; this does not create parallel prepared records.

`Cpp_Program::emit()` accepts the prior bundle and changed-source set. It invokes the
existing unit emitter only for those sources (or a source with no prior output), and
reuses prior text for the rest. It still assembles the complete current manifest,
entry adapter and dependency link inputs. Regenerating one unit includes all its
functions, storage definitions, required forward declarations and includes.

`Cpp_Publication` preserves files whose text is identical. `Cpp_Bundle` retains its
previous ownership manifest until the replacement is committed. On an ordinary
exception it removes newly created, not-yet-owned artifacts from that attempt, so a
retry does not mistake them for unrelated files. Existing artifacts can have been
updated before a write error: publication is atomic per file, not an all-files
transaction. The host must not build after a failed publication. Process-crash recovery
and filesystem failure during rollback are not guaranteed by this bounded protocol.

Native object names derive from stable generated source paths, so adding/removing a
source does not renumber surviving objects. The existing Ninja writer preserves an
identical graph and uses depfiles as before. The proof uses Ninja8, O2/g0, mold,
libc++, no PCH/LTO and the prebuilt runtime archive. Native compilation/link errors
are host failures, not preparation failures; retry Ninja or repair generated output
through the same coordinator. Do not report an old executable as a successful build.

## Declaration placement

[Generated source-unit headers](unit_headers.md) now hold function/global declarations
with external watchers. Local-only declarations stay in `.cpp`. Header planning follows
successful preparation and can make an unchanged provider eligible for emission when
its first/last external watcher changes. Linkage and language export rules are unchanged.

## Verification

`tests/incremental_executable.php` is a test-only line protocol around the production
coordinator. `tests/incremental_executable_native.py` keeps that PHP process resident,
edits files externally, reuses Ninja's objects and executes each successful result.
It checks all three flavors, exact preparation/emission/object counts, refreshed output,
no-op/no-link, constants, global initialization/types, function signatures, addition,
removal, declaration-only files, stale handles, parse/preparation errors, native failure,
failed publication with a newly added source, and recovery without lost emission work.

No-op still performs input metadata checks and existing declaration/import validation.
Emitted-text comparison may read generated files; source input change detection remains
mtime+size. Function-level emission, output
transactions across process crashes and service/CLI integration remain separate work.

## Recorded proof

The 2026-11-05 run passed 50 selected compiler regressions and the incremental, scalar
and unary native suites. The retained executable fixture verified
48 successful executable runs across cpp/php/js plus the expected failure/recovery
cases. Detailed timings, raw results and native logs are retained at
`v0.2/build/incremental-executable-complete-proof/report.md`.

## Retained-state inspection

The separate [cleanup slice 1 inventory and verification tool](retained_state.md)
measures repeated successful edits without detaching session history. It changes no
production cleanup behavior and distinguishes intentional caches from retired syntax.

Successful output publication now calls [generation cleanup](generation_cleanup.md).
It releases previous-session and removed-source roots without changing valid caches.

[Failure cleanup](failure_cleanup.md) releases obsolete history after published-generation
failures while preserving current symbols, watches and pending retry work.
