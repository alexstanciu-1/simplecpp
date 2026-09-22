# Initial project manifest
Doc Status: supporting

The active [PHP prototype](../../README.md) validates the current
schema, rejects malformed JSON and wrong-typed settings, and preserves retained
inputs after failure. It uses PHP's checked `json_decode` boundary in
[Manifest_Reader](../../src/01_prepare_inputs/read_manifest/main_read_manifest.php). The retained PHP++
implementation has different validation limits, recorded below.

The current shared schema is:

```json
{
  "source_folders": ["src"],
  "entry": "src/main.phs"
}
```

Both fields are required. `source_folders` is a nonempty ordered list of root
paths; `entry` selects the file whose entry body starts the program. Paths must
be nonempty strings without NUL bytes. Missing settings, unknown fields, empty
paths/lists, repeated roots, and non-list root keys have explicit diagnostics.
JSON syntax and decoding follow the runtime's rules. The prototype rejects
wrong-typed values before constructing its typed snapshot.

Relative paths use the manifest directory, including `../` roots. Path syntax
follows the host filesystem; a backslash is a literal name byte on POSIX. Absolute
paths are allowed but discouraged. Discovery recursively scans `.phs` files under
these roots, rejects equal/overlapping canonical roots and symbolic links inside
them, and verifies that the entry belongs to the live source set. Reading the
manifest alone does not establish a valid source project.

The reader returns a separate typed snapshot containing the requested manifest
path, exact file bytes, resolved manifest directory, roots, and entry. It does
not retain dynamic JSON in compiler state. Initial updates or changes to the
manifest path, resolved directory, or exact content select full rebuild;
whitespace-only changes count. Failed decoding leaves retained inputs intact.

Target/ABI configuration, dependencies, startup ordering beyond this entry, and
output options will extend the schema when their owning stages are introduced.
The [three-file sample](../../examples/three_files/project.json) can now be
compiled to a native executable by the prototype with `--output`; see its README.

## Virtual one-file projects

A `.phs` input prepares a `Project_Manifest` entirely in memory. Its `path` names
the actual source input, `content` is null (there are no manifest bytes),
`directory` is the canonical input directory, `source_folder_paths` is empty,
and `source_file_paths` selects the one source. `entry_path` selects that same
file. No JSON file or copied source is created. Source bytes remain owned by
the ordinary source-reading stage, so body edits do not become configuration
changes. Existing JSON schema and manifest behavior are unchanged.

Discovery represents an explicit selection as a directory context with fixed
`file_names`; null means the existing recursive directory selection. Both use
`source_scan_task`, `Source_Scanner` and `Source_Scan_Join`, then the same
source reading, tokenization and later stages. Explicit selection never lists
neighboring files or follows a nearby manifest. Debug exports include
`source_file_paths` and folder `file_names` only for explicit selections,
preserving existing manifest export fields.

Relative CLI input paths use the invocation directory. Canonicalization follows
the existing input-path policy; a source alias resolves to its actual file and
directory. The existing directory reservation covers virtual projects too.
Folder-swap simulation remains a manifest-only CLI operation.

The CLI builds single-file input by default, using the source stem beside the
source and `.exe` for a Windows target. An explicit `--output` is used exactly;
`--check` requests inspection through LLVM without native building and cannot
be combined with `--output`. The resident API keeps null output as inspection;
`compile(source_path, default_output: true)` requests automatic native naming.
Native building retains its current Linux-host requirements; Windows suffix
proofs do not establish Windows-host execution support.

[Session proof](../../tests/integration/single_file.php) covers native
execution, an incremental body edit, retained-input purity and naming from
actual Clang Windows-target probes. [CLI proof](../../tests/integration/single_file_cli.py)
covers isolated membership, paths, defaults, input protection and manifest compatibility.

## Prototype checks and debug export

The prototype's [schema checks](../../tests/01_prepare_inputs/read_manifest/project_manifest.php),
[recovery checks](../../tests/01_prepare_inputs/read_manifest/manifest_recovery.php), and
[export checks](../../tests/01_prepare_inputs/read_manifest/manifest_export.php) exercise validation,
failed-update preservation, repair, and debug snapshots.

`Project_Manifest::to_json()` exports `path`, `content`, `directory`,
`source_folder_paths`, and `entry_path`, preserving escaping, root order, and
exact original content. This inspection snapshot is distinct from the input
schema; import is deferred. `--debug=json` includes the manifest and discovered
sources in the compile result's `inputs`; simulation includes both runs.

## Retained PHP++ JSON boundary

The following records the original `src/` implementation and its toolchain
checks. It does not describe a blocker in the active PHP prototype. Recheck
these contracts when the port back is authorized.

The custom toolchain includes [fc20d73d](https://github.com/alexstanciu-1/simplecpp/commit/fc20d73d),
which changes `json_decode` to `result<mixed>` and `json_encode` to
`result<string>`. Every call checks its result through `take`. The reader turns
parse errors into a PHP++ exception with the manifest path and runtime byte-position
message. A subsequent request in the same process can read repaired input.

The [manifest recovery check](../../reference/original-phpp/tests/manifest_recovery.phs) exercises this
through the real input-refresh function and verifies preservation of retained
state. The [JSON contract check](../../reference/original-phpp/tests/toolchain_json.phs) also exercises
encoding failure/recovery, unchanged outputs on failure, and successful `null`
and `false` values. These checks do not require a replacement parser.

After updating an older toolchain, rebuild its runtime for the project's strict
profile before rebuilding the compiler:

```sh
(cd src && php ../../simple_cpp_compiler/vendor/simple_cpp/bin/scpp.php runtime-build --force)
python3 tools/build_compiler.py --no-stan
```

### Retained PHP++ debug export

`Project_Manifest::to_json()` returns a JSON snapshot with `path`, `content`,
`directory`, `source_folder_paths`, and `entry_path`. It checks encoding before
returning text and preserves escaping, root order, and exact original content.
This is an inspection snapshot, not the input schema; import is deferred.
`--debug=json` emits it beside the discovered `sources` snapshot. Simulation
emits the two input snapshots under `runs`. Export failures become explicit
exceptions; no unchecked JSON result is printed or written to the recovery journal.

### Current toolchain blocker

This is the last recorded status of the retained PHP++ implementation and its
configured toolchain, not a restriction on the prototype's PHP decoder.

The old malformed-JSON blocker in [issue #226](https://github.com/alexstanciu-1/simplecpp/issues/226)
is resolved for this compiler through checked decoding. The complete
[manifest fixture](../../reference/original-phpp/tests/project_manifest.phs) still fails at the
wrong-type cases: a numeric root can be coerced to a string instead of rejected.
Typed extraction and schema validation are separate from JSON syntax recovery.

The checked decoder produces `mixed`. Direct extraction of `vector<string>`
from that carrier fails native compilation in the configured version, so the
reader normalizes root entries into its owned vector in a loop. The current PHS
surface does not expose `is_string`/`is_array` guards; attempting those free
functions fails compilation. Complete type rejection needs a supported boundary,
not coercion-based validation or a list of special cases. The failing fixture is
retained and must not be described as passing.

The previously observed `"\0"` literal issue is independent. Path validation
continues checking byte zero through the byte-access API.
