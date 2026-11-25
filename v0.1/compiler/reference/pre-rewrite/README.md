# Simple C++ compiler
Doc Status: supporting

This is the sole development home of the adopted compiler, from source revision
`75e9b0f7c3f6420255b3b126429afbfa0e13ccb1`. The original checkout is preserved as
reference. Complete implementation portability before adding compiler functionality.
The implementation is currently PHP. [`portability.json`](portability.json) explicitly
lists 36 ready production files, proved in PHP and generated PHP++. See the
[current frontier](../specs/planning/compiler_migration/current_frontier.md) for
coverage and the remaining migration work.

The complete numbered pipeline, runtime preparation, shared process service, tests,
benchmarks and examples are retained. Lowering and LLVM emission remain part of this
compiler. The future C++ backend will consume the same semantic model.

From the repository root:

```bash
php compiler/src/main.php --help
python3 compiler/tests/run.py --jobs 10 --timeout 180
```

Commands in imported subsystem documentation are relative to `compiler/` unless
stated otherwise. Start with [architecture](docs/README.md),
[current capabilities](docs/planning/compiler_foundations.md), and
[runtime preparation](src-runtime-preparation/README.md).
The original [entry guide](reference/source-repository/prototype_readme.md) is
preserved verbatim as historical context.

## Dependencies and authority

The baseline uses Clang 18 and the external Simple C++ runtime pinned at
`fc20d73d040c4e69758bcec0b1caf40c26755f72`. Configuration paths are relative:
preparation includes resolve from `src-runtime-preparation/`; `tools/toolchain.json`
selects the historical PHP++ toolchain relative to this compiler directory.
Do not silently substitute this repository's runtime during migration.
Generated packages and benchmark build outputs are ignored derived artifacts.

The PHP++ implementation target is selected separately in
[`tools/portability_target.json`](tools/portability_target.json): unreleased tested
candidate `2f0d667f38a35ff02ef77e813f409189cba2d032`, based on v0.1.76. Latest
cumulative evidence is `specs/planning/compiler_migration/results/preparation-symbols-01/`
from the repository root. This does not upgrade the LLVM provider baseline above.

Repository `specs/` and root working rules remain authoritative. Imported docs are
supporting descriptions of the adopted implementation and its research extensions;
they do not silently change Simple C++ semantics. Preserve process ownership,
fixed worker inputs/outputs and joins, retained identities and snapshot behavior.
Use [formatting and navigation guidance](docs/code_formatting.md) for compiler PHP.
The [original working rules](reference/source-repository/working_rules.md) are
historical reference, not independently active instructions.

## Preservation and migration

[Migration records](../specs/planning/compiler_migration/README.md) contain file
provenance, relocation differences and validation. All original tracked files have
an inventory entry, including historical PHP++ under `reference/original-phpp/`.
The Git bundle in `reference/history/` preserves all original refs and reachable
history; it has been restored into a fresh bare repository and checked with fsck.
Neither the original checkout nor its history was changed.

Before native portability edits, perform the agreed target probes for struct fields
containing strings, vectors, hashes and class handles, including copy/reference
behavior. Missing target support is a blocker to discuss, not permission to change
the compiler data model. The portability converter remains a separate local-syntax
conversion tool under `tools/php_portability/` in the repository root.
