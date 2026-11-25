# Fixed source observations and read tasks
Doc Status: supporting

`read_sources\scanned_source_file` and `read_sources\source_read_task` now live in
`compiler/src/01_prepare_inputs/read_sources/data/read_tasks.php`, beside their
existing discovery/storage owner. They were extracted unchanged from `structures.php`;
bootstrap loads the new file before the remaining declarations. A repository search
found bootstrap to be the sole direct include of that original file.

The extraction separates already distinct fixed worker records from discovery
metadata that still needs nullable-container support. It changes no class names,
constructor arguments, field semantics or caller APIs. Removing the extracted
declarations reproduces the remaining original file exactly; the new file retains
those declarations byte-for-byte after its new prologue/imports.

Only existing promoted-constructor, readonly-usage and scalar-field conversion
paths are used. There is no converter/runtime extension, filesystem API mapping,
worker scheduling change or new compiler functionality. The portability manifest
now contains thirteen production files.

## Proof

The cumulative PHP/native harness checks UTF-8/space-containing paths, file ID,
mtime and size, same-object aliases, separate equal-valued requests and a later
request for the same logical file. The later request does not change the earlier
request's observed fields. Empty path and zero metadata remain accepted by these
records, preserving their original lack of constructor validation.

A separate bootstrap-loaded PHP check confirms readonly writes fail. Native
readonly enforcement is not claimed; the existing initialization-only usage
contract still applies.

[Recorded evidence](../planning/compiler_migration/results/read-tasks-01/summary.json)
retains the validation workflow result, source provenance, generated native files
and cumulative proof. The strict v0.1.76 run also exercises the sixteen retained
compiler fixtures, including source discovery, scan tasks, snapshots and parsing.

The folder/scan-task nullable lists, mutable `source_file` metadata, source sets,
filesystem workers and their joins remain outside the ready set. This slice proves
the fixed observations and read requests only.
