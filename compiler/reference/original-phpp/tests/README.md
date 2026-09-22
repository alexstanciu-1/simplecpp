# Compiler checks

From the repository root:

```sh
python3 tools/check_compiler.py --no-stan --fixture project_manifest
python3 tools/check_compiler.py --no-stan --fixture frontend_storage
python3 tools/check_compiler.py --no-stan --fixture manifest_export
python3 tools/check_compiler.py --no-stan --fixture source_discovery
python3 tools/check_compiler.py --no-stan --fixture manifest_recovery
python3 tools/check_compiler.py --no-stan --fixture toolchain_json
```

The runner copies all compiler source into a temporary strict-PHP++ project,
builds/runs the selected fixture, and removes generated files afterward. The
compiler's command entry and its project config are omitted from these copies;
only the selected test program runs. Generated `.prism` files are excluded too.
The runner selects the custom instance in [toolchain.json](../tools/toolchain.json),
with paths relative to the repository root. Its `codex/compiler-vector-runtime`
checkout is at `fc20d73d`, the checked JSON API fix; its strict runtime was rebuilt.
`--scpp` is an
explicit override; there is no automatic fallback to the global install.
The installed toolchain on the initial development host
failed its storage precheck; the sibling compiler's vendored toolchain passed
that original check. With the update skeleton added, the vendored STAN reports
missing returns in non-void placeholders and the coordinator loop. The explicit `--no-stan`
option permits native validation of the skeleton without inserting dummy
returns. Omit it to reproduce the current analyzer limitation; it is not silently
disabled by the runner.

The manifest fixture reads the sample JSON from disk, checks typed settings and
the manifest-relative path base, requests rejection of invalid configuration, and exercises
initial/unchanged/changed manifest decisions against a retained baseline. It
also checks that valid input reaches the source-reading placeholder without
publication. It seeds a manifest-only comparison baseline; this does not prove
resident compilation or incremental reuse. Sample sources stay outside the
bootstrap toolchain's source tree and are not compiled by it. The complete
manifest check now passes its malformed-input recovery cases but still fails
on wrong-type validation: numeric roots can be coerced to strings. It must not
be described as passing. See the [remaining blocker](../docs/details/project_manifest.md#current-toolchain-blocker).

`--fixture toolchain_json` checks the new JSON contracts: malformed input returns
an error with byte position, failed operations leave outputs unchanged, repaired
requests succeed, and valid `null`/`false` values count as success. It also checks
non-finite encoding failure and subsequent successful encoding. The runner prints
saved `scpp error` and `scpp full-error` reports on failure.

`--fixture manifest_recovery` verifies malformed input and repaired requests
through the real reader/discovery prefix in one process, with path/byte diagnostics
and preservation of retained state. It separates this now-working behavior from
the remaining schema/type-validation failures.

The export fixture reads valid manifests and checks that the exported JSON
preserves every snapshot field, string escaping, and root order. It also checks
an empty result and repeated export without mutation. Decode and encode results
are checked explicitly with `take`.
The native export check passed on the configured custom toolchain with `--no-stan`.

The storage fixture proves value-copy independence, token lengths above 65,535 bytes,
separate file tables, sibling/nested traversal after vector growth, and compact
native layout across process namespaces. Generated C++ must use inline row elements in the vectors.
It also exercises a parsed file containing multiple definitions and entry
statements, entity IDs after vector growth, shared block representation for
named functions and file entry, and empty entries in empty/function-only
files. Entity lists and syntax storage remain separate between files.
Observed native sizes: `source_span` 8, `token` 12, `syntax_node` 20 bytes.
The missing-manifest request must fail without publishing or
modifying the session's sources.

This constructs AST rows directly to test storage. It is not a tokenizer/parser
test, the three-file compiler sample, or a claim of working compilation/MT/reuse.

The source-discovery fixture performs repeated real filesystem scans in one
process. It checks recursion, folder indexes, stable IDs, mtime-only and size-only
changes, additions, removals, recreation, folder reordering/removal, and input
purity. It also inspects JSON exports and rejects overlapping roots without
modifying the previous dataset. Physical cleanup, frontend reuse, and resident
compilation are not claimed. The size-only probe deliberately edits within one
timestamp second to isolate that check; normal usage follows the documented
[timing assumption](../src/README.md).

The real [command entry](../src/README.md) builds with the custom toolchain and
explicit `--no-stan`. `--debug=json` exposes the manifest and discovered sources;
successful default execution stays quiet. `--help` prints the current mtime/size
limitation. Source contents are not yet read or compiled by this command.

The native discovery fixture and CLI checks passed with `--no-stan`. CLI checks
compared the sample's exported metadata with filesystem stat results and covered
working-directory independence, quiet/debug/help modes, invalid options, missing
roots, an entry outside the source set, overlapping roots, and symlink rejection.

For folder-swap simulation, build the CLI first, then run:

```sh
python3 tools/build_compiler.py --no-stan
python3 tests/increment_simulation.py --no-stan
```

This checks two real refreshes at stable paths, early full-rebuild decisions,
quiet/debug modes, and exact content/mtime restoration on success and a failed
second run, including malformed JSON followed by a repaired simulation. It builds
an isolated native probe using the production swap owner,
kills it at each of five boundaries, verifies that the journal blocks further
runs, and restores the folders using the note's rules. An occupied restoration
destination must be preserved with the journal left for recovery. All renamed
projects are temporary fixtures; no crash switches are added to compiler code.
