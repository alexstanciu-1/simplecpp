# Project-manifest reading
Doc Status: supporting

The first active rewrite stage has three production files under
`compiler/src/01_prepare_inputs/read_manifest/`: `data/project_manifest.php`,
`utilities/manifest_syntax.php`, and `manifest_reader.php`. Its owner is the synchronous
`read_manifest\Manifest_Reader::read(string $path): Project_Manifest` API.
Host PHP callers load `compiler/bootstrap.php`; there is no compiler CLI yet.

## Input and result

A JSON project requires exactly `source_folders` and `entry`. Folders must be a
nonempty array of distinct, nonempty strings; entry must be a nonempty string.
Paths cannot contain NUL. Unknown fields, wrong JSON shapes and malformed JSON are
rejected. Paths retain their spelling and order. An object such as `{"0":"src"}`
is not a source-folder list. Duplicate JSON fields reject under the
[v0.2 JSON unique-name contract](../builtins/json/json_decode.md#unique-object-names--2026-10-06).

The result owns original input path/content, canonical containing directory,
source-folder and source-file vectors, entry, and an explicit single-source flag.
A `.phs` input creates a virtual single-source project with the resolved basename
as entry/source-file, empty content and no source folders. Separate reads create
independent results. Missing inputs and directory inputs fail as I/O errors.

Non-goals: directory scanning, resolving/checking declared roots or entry existence,
source snapshots, incremental publication, full pipeline scheduling and diagnostic
message byte parity. This stage performs an ordinary manifest read; verified source
reading is a later owner. It does not claim atomic path/read consistency.

## Portable framework boundary

`scpp\json_read` returns an immutable `scpp\Json_View` schema view. Available methods:
`kind`, `size`, `has`, `key`, `member`, `at`, `text`; [scalar catalog adoption](scalar_catalog.md) adds strict `integer` and `boolean` accessors. Missing members, invalid indices
and wrong-kind access throw. Null presence, object/list identity, numeric-looking
keys, duplicate-key rejection and retained child handles are explicit behavior.
PHP uses object-mode `json_decode` with exceptions; native wraps the checked #240
JSON document/node API. The converter adds no type resolution or JSON syntax rules.
The PHP adapter additionally scans original key tokens after grammar validation,
before publishing a view, because PHP's decoded objects have lost duplicates.
This host-only check is not a second JSON grammar implementation. Native value
decoders and the integrated v0.2 document parser enforce the rule while parsing.
The latter reports `duplicate_key`, the repeated key and its opening-quote byte
offset; no last-value-wins candidate behavior is retained.
Install native support with `--json --filesystem` and enable those modules.

This is a bounded schema adapter, not full lossless JSON interoperability. It exposes
neither raw number-token access, configurable parse depth, structured
parse diagnostics or generic serialization. PHP rejects NUL-prefixed object keys
which native documents can retain; manifests reject such fields in either path.
The PHP decoder may round noninteger numeric values; strict integer extraction rejects those carriers rather than exposing rounded integers. Manifest paths must still be strings. Do not extend this adapter's claims to arbitrary
JSON documents without a concrete contract and proof.

The filesystem facade adds checked realpath/text reads and basename/dirname helpers.
Uniform imports include these names in every maintained portable source. No converter
grammar change was needed. The manifest is one class owning strings/vectors; forcing
it into a scalar record would not suit its data. Compact layout remains relevant to
future high-volume compiler records.

## Validation and timing

The historical proof below predates duplicate rejection. The updated manifest
fixture now rejects repeated fields and checks the adapter's rejection separately
from key ordering and retained-child behavior. Its v0.2 PHP/conversion recheck does
not establish a new native document-API pass.

On 2026-10-06, the updated runner passes 36 PHP outcomes and conversion/reuse
checks (`/tmp/scpp-json-unique-manifest-20261006-2/`). The shared 23-case corpus
in `tests/portability/json_unique_keys.tsv` passes through the PHP schema adapter
and both native value-decoder families (`test_json`, CTest under
`/tmp/scpp-v02-process-lock-native-20261006/`). It covers decoded escape equality,
Unicode, embedded NUL, empty keys, per-object scope, exact duplicate byte offsets,
and distinct case/numeric-looking/non-normalized names. Native tests also prove
unchanged destinations on failure and successful subsequent parsing.

The registered stage proof specifies 35 structured outcomes independently of the
implementation: valid/invalid schemas, object/list separation, original content,
Unicode and numeric strings, duplicate fields/roots, malformed bytes, relative and
symlink paths, single-source inputs, I/O failures, independent results and JSON
access/retention behavior. It compares decoded results, not incidental stdout bytes.
The second converter run proves all five staged source files are reused.

Combined validation passed on exact clean, unreleased candidate
`9b4b33f35f053b487e018c94d6a4a7888d77c64a`:

```sh
python3 tools/php_portability/validate.py \
  --results /tmp/scpp-manifest-stage-final-01 \
  --native compiler --target-checkout /tmp/scpp-json-240-probe
```

For the PHP authoring loop, omit the native flags and use a fresh result directory.
The stage alone is `python3 compiler/tests/run.py --results FRESH`.
Evidence and elapsed phase/command timings live in
[manifest-rewrite-01](../planning/compiler_migration/results/manifest-rewrite-01/).
Times include tool waits and are observations from this machine, not CPU times or
an estimate for the whole compiler. Failed attempts remain recorded. Other target
APIs and operating systems are not revalidated by this Linux stage proof.
