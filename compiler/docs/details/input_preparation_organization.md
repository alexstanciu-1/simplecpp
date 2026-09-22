# Input preparation organization
Doc Status: supporting

The PHP processes in `01_prepare_inputs/` use the shared
[organization convention](../code_organization.md#controlled-feature-extension-in-the-php-prototype).
Their namespaces are unchanged; phase entries use the common lifecycle in
`main_<process>.php`. See the [step inventory](../../src/compile/steps.md).

```text
01_prepare_inputs/
    read_manifest/
        data/result.php
        main_read_manifest.php
        utilities/manifest_syntax.php
    load_runtime/
        data/{runtime.php, package.php}
        handlers/{type_definitions.php, package_types.php, records.php, lifecycle.php, callables.php, bindings.php, package_syntax.php}
        package_adapter.php
        main_load_runtime.php
        utilities/catalog_syntax.php
    read_sources/
        data/{structures.php, store.php, result.php}
        utilities/paths.php
        main_discover_sources.php
        main_read_sources.php
        scan.php
        read.php
        scan_join.php
        snapshot_join.php
```

## Manifest reading

`Manifest_Reader` owns path initialization, reading and completed output in
`main_read_manifest.php`. `Manifest_Syntax` in `utilities/manifest_syntax.php`
validates text and paths. `Project_Manifest` lives in `data/result.php`.
Source membership remains a discovery responsibility.

## Language catalog ingestion

[Language_Types](../../src/01_prepare_inputs/load_runtime/main_load_runtime.php)
owns catalog I/O, content-based reuse and completed catalog assembly.
`Catalog_Syntax` owns schema validation, definition iteration and literal/entry
defaults in `utilities/catalog_syntax.php`.

The syntax owner composes the private [Type_Definition_Loading](../../src/01_prepare_inputs/load_runtime/handlers/type_definitions.php)
trait accepts one decoded row. `definition()` validates the common name fields
and dispatches to `void_definition()`, `integer_definition()` or
`floating_definition()`. Each validates its representation fields and returns
a newly constructed definition. `finish_definition()` shares final assembly;
field and lifetime validation methods stay with the syntax owner. Handlers do no I/O,
do not append to a shared catalog and do not materialize semantic type IDs.
Validation order and errors retain the previous behavior.

A new admitted representation extends this dispatch and its schema handler.
The representation owner and semantic/backend consumers must independently
support its meaning; recognition by the catalog loader is not sufficient.
Small handlers stay grouped; a substantial representation can justify another
private trait when it is implemented.

Shared definition, capability and catalog records belong to
[type_model](../../src/04_analyze/type_model/calls.md). The importer
validates and produces those records; it does not own their common meaning.

`Package_Adapter` separately validates a prepared runtime package, holds its reader
lease and composes normalized definitions/callables with the catalog. Its private
handlers split storage, records, lifecycle, callable passing and language bindings.
Only this adapter reads the package JSON. See the
[import call map](../../src/01_prepare_inputs/load_runtime/calls.md) and
[package contract](runtime_package_consumption.md).

## Source discovery and reading

[Source_Reader](../../src/01_prepare_inputs/read_sources/main_read_sources.php)
owns selected snapshot reads. `Source_Discovery` in `main_discover_sources.php`
owns the separate source-discovery lifecycle.
`Source_Scanner` consumes one immutable directory or explicit-file selection task. The separate
[Snapshot_Reader](../../src/01_prepare_inputs/read_sources/read.php)
consumes one immutable file/version task and returns a private `Source_Buffer`.
`Source_Reader::run()` calls `Snapshot_Reader::read()` for each selected task.

The snapshot worker owns path/open-handle identity, version and size checks,
bounded reading and handle cleanup. It neither selects work nor edits a
`Source_Set`. The existing read-timing limitation is unchanged. Source-scan and
snapshot joins remain separate process-root classes; only complete accepted
results contribute to replacement state. Full and selective updates use these
same workers and joins.

`Source_Paths` lives in `utilities/paths.php`. Its `join()` concatenates paths;
it is unrelated to task-result joins. Records, buffers, datasets and completed
scan results are grouped under `data/` without changing their ownership
or copy/reuse contracts.

## Verification

The full prototype suite passed, including source-read races, catalog reuse,
fixed-worker and join checks, incremental recovery and native execution. A
temporary comparison matched 1,120 old/new catalog outcomes, including 986
diagnostics, with identical data and exports. Seven data/utility files moved
unchanged; snapshot reading and its version checks are exact method extractions.
Comparison helpers were removed after verification. No benchmarks were run.
