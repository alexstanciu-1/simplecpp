# Project manifest snapshot export
Doc Status: supporting

`read_manifest/data/result.php` retains its fields, defaults and Step_Result marker.
Folder/file membership now has explicit `vector<string>` annotations. The JSON
schema is authored directly rather than assembled as a heterogeneous PHP array.

Field order and spelling remain path, content, directory, source_folder_paths,
optional source_file_paths, then entry_path. An empty explicit-file list is omitted;
an empty folder list remains present. Default content is the present empty string,
while virtual manifests carry null. The exporter does not normalize paths, parse
content, mutate its input or substitute configuration for source bytes.

## Shared scalar encoding

`scpp\json_quote(string): string` owns exact JSON string encoding in the framework.
Write `json_quote($text)` under uniform imports. PHP uses json_encode with
JSON_THROW_ON_ERROR. Native assembly owns the existing explicit string encoder,
including escaped slashes, control characters and UTF-16 surrogate-pair JSON
spelling. Malformed UTF-8 throws JsonException with the original message and code 5.

Source_Json::quote delegates to this shared operation, preserving existing source,
token and syntax export callers. Source-specific change vocabulary stays on
Source_Json. Manifest export consequently needs no dependency on source discovery.
This is a local extraction of the existing encoder, not a new generic JSON object
framework, automatic schema reflection or JSON parser.

The manifest retains its outer Exception message/code and previous JsonException.
List order and dense membership are authoring contracts; arbitrary associative PHP
arrays are not reinterpreted as vectors.

## Nullable scalar defaults

The converter now preserves matching literal defaults on nullable bool/int/string
fields as well as null. This keeps `public ?string $content = "";` unchanged in PHP.
Zero, false and empty string are present values. Named nullable fields still require
null, and nullable container defaults are unchanged. No constructor/default inference
or new wrapper payload family is introduced.

## Proof scope

A frozen original manifest record is the oracle for 88 snapshot/error cases,
including null versus empty content, optional file membership, Unicode/control
escaping, malformed input in each field and the full exception chain. Serialization
of inputs before/after checks nonmutation. The cumulative witness has independent
expected JSON text and checks control bytes, cause codes, virtual manifests and
nullable scalar defaults on PHP and native.

Manifest parsing, filesystem reading and phase lifecycle remain unmigrated. This
slice does not implement generic JSON ingestion or claim the manifest stage ready.

The exporter builds the protected result then returns it after the catch, matching
the existing Source_Set pattern. The selected STAN cannot prove return completeness
for the equivalent return-inside-try/throwing-catch form; no fallback result or
disabled analysis is used.

Evidence: `specs/planning/compiler_migration/results/manifest-record-01/summary.json`.
PHP/native expectations, strict build/STAN, fast converter/framework regressions
and twenty-one retained compiler fixtures pass on
`2f0d667f38a35ff02ef77e813f409189cba2d032`. The retained fixtures include manifest
export, parsing and recovery. Thirty-four production files are ready.
