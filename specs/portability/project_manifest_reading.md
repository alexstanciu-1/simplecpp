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
is not a source-folder list. Duplicate JSON fields use the last value, as PHP does.

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
`kind`, `size`, `has`, `key`, `member`, `at`, `text`. Missing members, invalid indices
and wrong-kind access throw. Null presence, object/list identity, numeric-looking
keys, duplicate-key ordering and retained child handles are explicit behavior.
PHP uses object-mode `json_decode` with exceptions; native wraps the checked #240
JSON document/node API. The converter adds no type resolution or JSON syntax rules.
Install native support with `--json --filesystem` and enable those modules.

This is a bounded schema adapter, not full lossless JSON interoperability. It exposes
neither raw number access nor numeric conversion, configurable parse depth, structured
parse diagnostics or generic serialization. PHP rejects NUL-prefixed object keys
which native documents can retain; manifests reject such fields in either path.
The PHP decoder may round numeric values, but this facade exposes only their kind;
manifest paths must be strings. Do not extend this adapter's claims to arbitrary
JSON documents without a concrete contract and proof.

The filesystem facade adds checked realpath/text reads and basename/dirname helpers.
Uniform imports include these names in every maintained portable source. No converter
grammar change was needed. The manifest is one class owning strings/vectors; forcing
it into a scalar record would not suit its data. Compact layout remains relevant to
future high-volume compiler records.

## Validation and timing

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
