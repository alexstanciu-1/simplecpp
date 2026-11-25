# Native representation gate
Doc Status: supporting

These are hand-authored strict PHP++ target probes, not output from the PHP
portability converter. They establish prerequisites before adapting compiler data.
Each `.phs` file is an independent project entrypoint; the runner copies one at a
time into its own temporary strict project. Do not build this whole folder as one
project (the fixtures intentionally reuse declaration names).

From the repository root:

```bash
python3 tools/compiler_migration/probe_representations.py specs/planning/compiler_migration/results/NEW_RUN
```

That command reproduces the historical toolchain pin. For the selected v0.1.76
implementation target, use an isolated checkout of that release:

```bash
python3 tools/compiler_migration/probe_representations.py specs/planning/compiler_migration/results/NEW_RUN --target-checkout /path/to/simplecpp-v0.1.76
```

This mode reads `compiler/tools/portability_target.json` and requires its exact
commit. `--toolchain <checkout> --revision <commit>` probes an explicit alternative
without editing either selection. All modes require a clean target checkout.

The runner resolves the CLI relative to `compiler/tools/toolchain.json`, verifies
its exact revision and clean Git state, initializes strict projects, selects
Clang 18, and builds the project-local runtime explicitly. Logs, fixture hashes,
project configurations, diagnostics and retained scratch paths are recorded.
It does not change the pinned checkout or fall back to the global CLI.

| Probe | Required observable behavior |
| --- | --- |
| Scalar control | Copying a struct and modifying its uint32 field preserves the original (`7:9`) |
| Vector of records | Modifying the copied vector's inline record preserves the original (`7:9`) |
| Hash of records | Modifying the copied hash's inline record preserves the original (`7:9`) |
| String field | Assignment to the copy preserves the original string (`before:after`) |
| Vector of strings | Indexed write to the copy preserves the original (`before:after`) |
| String-keyed hash | Value write to the copy preserves the original (`before:after`) |
| Integer-keyed hash | Value write to the copy preserves the original (`before:after`) |
| Class field | Struct copies share the object (`9:9`); replacing one handle preserves the other (`9:11`) |
| Struct construction | Inferred and typed `new Struct()` construct values; copying the inferred local preserves the original (`before:after:typed`) |

Failed normal runs are retried with `--no-stan` to distinguish legacy analyzer
coverage from generator/native capability. Both results are retained. A rejected
field is a failed portability prerequisite, not a passing negative test. The driver
exits unsuccessfully when a required behavior cannot be established.

Passing these probes would establish only this bounded behavior, not full compiler
portability, cross-file member metadata coverage, custom constructor support, deep cloning,
nullable field support or native multithreading.
