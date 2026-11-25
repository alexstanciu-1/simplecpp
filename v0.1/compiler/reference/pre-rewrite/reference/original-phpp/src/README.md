# Reading the compiler source

This is the retained PHP++ reference. Active development temporarily uses the
[PHP prototype](../prototype/README.md), with the same process organization.

Start with [compile/update.phs](compile/update.phs). The [compile/](compile/)
folder coordinates the update, selects work, retains the session, and publishes
results. [main.phs](main.phs) reads one manifest and recursively discovers its
source files; `--debug=json` prints their JSON snapshots on stdout. The resident
update driver remains future work.

For two input refreshes in one process, use
`--simulate-increment <edited-project-folder>`. It swaps the manifest directory
with the edited copy via renames, then restores both. `--debug=json` prints both
runs and their early rebuild decisions. A recovery journal beside the project
records progress if the process crashes. See [simulation and recovery](../docs/details/increment_simulation.md).

Build and run from the repository root:

```sh
python3 tools/build_compiler.py --no-stan
src/.prism/build/main examples/three_files/project.json
src/.prism/build/main --debug=json examples/three_files/project.json
src/.prism/build/main --help
```

The build uses [the configured custom toolchain](../tools/toolchain.json).
[prism.json](prism.json) makes `src/` the build project, keeping sample/test
programs outside it. `--no-stan` is explicit because the unfinished pipeline
still has the analyzer limitation described in the [test notes](../tests/README.md).
The executable inspects the manifest and source membership; it does not compile
the sample. Debug JSON contains `manifest` and `sources` objects, with source
paths, module ownership, mtime, size, and change state.
Without a debug flag, successful execution produces no snapshot output and does
not run the exporter. The flag may precede or follow the manifest path. Only JSON
is supported currently; unknown options/formats are rejected. TSV can be enabled
when a real TSV exporter exists.

Discovery scans `.phs` files recursively under each configured root. It rejects
overlapping roots and symbolic links inside roots, and checks entry membership.
Changes use whole-second `fs_mtime` **or** `fs_size` differences. Current limitation:
wait at least one second after the last edit before compiling, and at least one
second after the scan before editing again. Same-size edits with unchanged mtime
can go undetected. `--help` prints this limitation; the compiler does not sleep.

The process folders participate in this order:

1. [read_manifest/](read_manifest/) — read and validate project configuration.
2. [load_runtime/](load_runtime/) — load provider metadata and ABI contracts.
3. [read_sources/](read_sources/) — discover participating files and read snapshots.
4. [tokenize/](tokenize/) — turn source bytes into tokens.
5. [parse/](parse/) — produce each file's defined entities and implicit entry body over flat syntax storage.
6. [collect_symbols/](collect_symbols/) — collect/compare declarations and update the project index.
7. [resolve_symbols/](resolve_symbols/) — bind names to declarations.
8. [resolve_types/](resolve_types/) — resolve types, constants, and generic instances.
9. [check_bodies/](check_bodies/) — check operations and construct typed bodies.
10. [analyze_lifetimes/](analyze_lifetimes/) — check dataflow, ownership, and cleanup.
11. [lower/](lower/) — produce explicit execution/ABI operations and backend plans.
12. [emit_llvm/](emit_llvm/) — emit and verify LLVM.
13. [build_native/](build_native/) — produce objects and link the final artifact.

Control returns to `compile/` for publication. [diagnostics/](diagnostics/)
supports every stage.

[compile/inputs.phs](compile/inputs.phs) owns the shared manifest/discovery prefix
used by the command and update coordinator. [simulate_increment/](simulate_increment/)
owns the optional CLI folder swap; compiler stages contain no simulation branches.

This is the intended flow. Valid-manifest reading, source discovery, and JSON
inspection and malformed-JSON recovery work. Complete manifest type validation
is still unfinished. Source content reading
and later stages remain a skeleton. Stages can
interleave or be revisited as dependencies require. Full and incremental builds
use the same path. See the [pipeline](../docs/compiler_pipeline.md) for the rules.
