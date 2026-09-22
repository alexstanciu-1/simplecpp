# PHP compiler prototype

**Objective:** a Simple C++ compiler with first-class STAN (static analysis) and a
direct LLVM path targeting `-O0`/`-O1`, with optimization supplied by LLVM. Clang and
runtime dependencies are explicitly acceptable; S2S remains available for broader
compilation needs. These targets do not imply newly implemented modes. See the
[project objective](../docs/README.md#project-objective) and
[current capability status](docs/planning/compiler_foundations.md).

This is the active compiler implementation. The original [PHP++ source](../src/)
is retained as a reference. Porting back to Simple C++ requires an explicit request
and the [toolchain verification gate](../AGENTS.md#php-authoring-and-porting-gate).

## Compile and test

```sh
php prototype/src/main.php path/to/main.phs
php prototype/src/main.php path/to/main.phs --output /tmp/my-program
php prototype/src/main.php path/to/main.phs --check --debug=json
python3 prototype/tests/run.py
php prototype/src-runtime-preparation/tests/run.php
```

Direct source input creates a virtual one-file project and defaults to an executable
beside that source. `--output` selects its destination; `--check` stops after LLVM
emission. Compilation does not run the resulting program. See [input and manifest
contracts](../docs/details/project_manifest.md#virtual-one-file-projects) and the
[test guide](tests/README.md).

The separate [runtime preparation tool](src-runtime-preparation/README.md) produces
metadata and LLVM artifacts. Consume its configurable output directory with
`--runtime-package prototype/generated-runtime`. Repeat `--runtime-package` for
additional prepared packages. The session API keeps `runtime_package_path`, accepting
a directory, a nonempty directory list, or null. All packages use the same import,
validation and linking path; aliases are deduplicated.

The session API also accepts fixed normalized `family_declarations` from
`load_runtime\Family_Adapter::expose()`. This currently registers provider families
and checks source template permissions. With an optional `family_preparer`, concrete
type demands prepare native storage and construction/cleanup through the existing
pipeline. Demanded methods execute through shared call contracts, including const
integer borrowing for native copy-based append and compatible coverage growth on a
body increment. Expressions and conversions receive call storage; see the
[integration checkpoints](../docs/details/provider_family_compiler_integration.md).

## Source organization

```text
prototype/
├── src/
│   ├── 01_prepare_inputs/{read_manifest, load_runtime, read_sources}/
│   ├── 02_tokenize/
│   ├── 03_parse/
│   ├── 04_analyze/
│   │   ├── type_model/          shared definitions, representations and stores
│   │   ├── collect_symbols/
│   │   ├── resolve_symbols/
│   │   ├── instantiate/         concrete contexts, specialization workers and registry
│   │   ├── resolve_types/       selected preparation, materialization and joins
│   │   ├── check_bodies/
│   │   └── analyze_lifetimes/
│   ├── 05_generate_code/
│   │   ├── prepare_backend/    target, layout and ABI preparation; tools/
│   │   ├── lower/              body and native-entry plans
│   │   └── emit_llvm/
│   ├── 06_build_output/build_native/
│   ├── compile/
│   ├── diagnostics/
│   ├── simulate_increment/
│   └── main.php
├── tool_process/               independent external-process service
├── src-runtime-preparation/    provider package generation
├── language/                   authoritative language definitions
└── tests/
```

Numbered groups organize navigation; they do not appear in namespaces. `type_model`
is a shared contract owner, not an execution phase. `load_runtime` imports contracts;
`resolve_types` materializes source and provider requests into the shared model.
Backend preparation publishes fixed facts that lowering and emission consume.
The independent process service is used by both compiler tools and runtime preparation.

Start with the [compiler call map](src/compile/calls.md), then the adjacent group
README and process `calls.md`. [Code organization](../docs/code_organization.md)
owns the placement and ownership rules; [type model](../docs/type_model.md) owns
shared type meaning.

## Current scope and design

The [foundations tracker](docs/planning/compiler_foundations.md) is the authoritative
capability status and next-work list. Feature contracts and proofs live in linked
`docs/details/` documents; this entry guide does not duplicate their status.

The [pipeline](../docs/compiler_pipeline.md) defines processing dependencies and
incremental policy. Stages execute selected work with fixed inputs, private outputs
and explicit joins. Actual compiler-worker threading remains deferred. Current
required incremental proof is one full build followed by one incremental attempt;
unsupported changes use the same stages with full selection.
