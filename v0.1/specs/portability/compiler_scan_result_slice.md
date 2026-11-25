# Source-scan result transfer record
Doc Status: supporting

`read_sources\Source_Scan_Result` preserves the worker-to-discovery-join boundary:
its exact originating task, ordered file observations and ordered child directories.
Only managed imports and explicit vector annotations change the production PHP.
No scan/reconciliation algorithm or filesystem behavior is added in this slice.

The concrete converter addition is required promoted container parameters:
`public readonly array $files /** vector<scanned_source_file> */` emits a native
vector parameter and field using the existing container annotation parser. Missing
annotations still fail. Container defaults remain rejected except the already
supported null default of nullable parameters. No call-site inference or general
method container parameter support is introduced.

The cumulative PHP/native proof checks exact task identity and shared observation
identity, string/numeric contents, empty results, independence from edits to the
constructor's input list membership and from edits to lists retrieved from the
record. Native readonly containers remain an initialization-only usage contract;
this slice does not claim deep immutability or identical enforcement to PHP.

This record is a dependency of source scanning and discovery joins. Those owners
still need their own adaptation and behavioral proofs. Completion of this record
does not establish that a complete source-discovery pipeline is portable.

Evidence: `specs/planning/compiler_migration/results/scan-result-01/summary.json`.
