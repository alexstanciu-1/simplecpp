# Source discovery records and nullable lists
Doc Status: supporting

The remaining `read_sources/data/structures.php` declarations are portable:
`source_folder`, `source_scan_task`, `file_change`, `MAX_SOURCE_FILE_ID` and
`source_file`. The ready set now contains fourteen production files. Only managed
imports and two explicit vector annotations were added to this source file;
removing them restores the previous declaration bytes.

## Local conversion additions

- Nullable list property: `public ?array $file_names /** vector<string> */ = null;`.
- Nullable promoted list parameter: `public readonly ?array $file_names /** vector<string> */ = null`.
- Nullable scalar/named properties initialized to null, including `?Source_Buffer`.
- File-scope integer-literal constant declarations, preserving the source-ID limit.

PHP's `?` supplies nullability and the existing adjacent vector annotation supplies
element type. The converter emits an explicit `nullable<vector<T>>` annotation;
it never infers elements from values or documentary `@var` comments. Documentary
comments before promoted parameters are retained in source but do not select types.
Missing/unsupported vector annotations, non-null nullable defaults and nonliteral
constant expressions are rejected. Nonnullable promoted lists, nested container
annotations, maps and general nullable method signatures remain outside this slice.

The native target's initialization-only readonly usage contract is unchanged.

## Behavioral proof

The cumulative PHP/native proof distinguishes null (recursive scan) from an empty
present list (select no immediate files). It checks supplied task coordinates,
string-list contents, independence from later input-list edits, extraction and
folder assignment copies, and resetting a selection to null.

Source metadata starts with absent buffer, unchanged classification and pending
compilation. Assigning/extracting the buffer retains its exact object identity.
Deletion classification and folder index `-1` remain independent of pending work.
No filesystem reads or worker execution are added by these tests.

`python3 tests/portability/nullable_fields.py` covers accepted local forms and
rejections with unchanged generated output after failure.
[Recorded evidence](../planning/compiler_migration/results/discovery-records-01/summary.json)
includes strict v0.1.76 execution, sixteen retained compiler fixtures, source
provenance and confirmation that final rejection tightening reproduces the exact
native-tested output. An obsolete rejection of `?bool = null` was replaced with
the unsupported non-null default case.

## Next concrete boundary: Source_Set indexes

Later update: the [Source_Set slice](compiler_source_set_slice.md) completes this
owner and its operation/snapshot/export proofs. The following records the earlier
boundary, not the current ready-set status.

Later update: [recursive container annotations](container_annotations.md) now support
explicit nested vectors and typed maps. The type-spelling concern below is resolved;
actual lookup/iteration/copy operations still need adaptation and proofs.

`read_sources/data/store.php` is not yet portable. It needs typed integer-to-row
and path-to-ID maps, nested folder membership lists, and explicit snapshot-row
copying. Its algorithms distinguish missing keys, duplicate IDs/paths, tombstones
and logical IDs from row offsets. Treating all its PHP arrays as vectors would
change those semantics.

Before porting this owner, establish the local PHP spellings and framework/native
contract for typed map lookup/missing-state/insertion and nested membership, then
prove index rebuilding and old-snapshot independence. Existing Simple C++ hash
support is not automatically a portable-PHP map contract. Clone/implements/private
field/foreach coverage also needs bounded converter work. This is a concrete
representation and converter boundary, independent of issue #231; no Source_Set
rewrite or silent representation substitution is included here.

Later update: [manifest snapshot export](compiler_manifest_record_slice.md) adds
matching bool/int/string literal defaults for nullable scalar fields. Nullable
container defaults remain null-only; earlier slice evidence is unchanged.
