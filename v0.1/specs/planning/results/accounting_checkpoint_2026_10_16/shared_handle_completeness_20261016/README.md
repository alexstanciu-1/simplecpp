# Shared-handle refinement evidence
Doc Status: supporting

See the [report](../shared_handle_completeness_20261016.md).

- `summary.json`: all 54 runs, stages, memory, exact object sets, dependency checks.
- `comparison.json`: actual median-total trials and worst observed memory versus previous matrix.
- `verification.json`: parity, source hashes, unchanged runtime and predicted rebuild-set checks.
- `experiment-identity.json`, `mold-provenance.json`: native image/source/runtime/tool identities and host settings.
- `implementation.patch`, `native-source-hashes.json`, `driver.php`: candidate relative to compiler base `85c2e2e8`; only four backend implementation files change.
- `native-build-*`: successful O2 native conversion/build commands, flags, resource use. The recorded compiler wrapper replaces legacy `-O0`/`-O3` with `-O2` and preserves debug settings.
- `focused-proof/`: two compiled/generated executions and five PHP regression suites.
- `emission-shape-proof.json`: same artifact paths; changes only to includes/forward declarations.

Scripts preserve the existing experimental protocol with only the four application
cases selected and a 20-object assertion added for AccountingCheck layout edits.
They use experiment-specific paths. Stage them in a fresh equivalent directory;
do not run them directly from this docs folder. `rebuild_compiler.py` uses the
existing native source/project directory and expects source updates to be copied
there first. It is not a general compiler installer.

Local full evidence and native image:
`/home/alexv/__AI/simple_cpp/simple_cpp_01/sandbox/accounting-recovery-20261016/native-shared-handles-20261016/`.

Remote isolated matrix:
`/home/alex-ai/accounting-shared-handles-20261016/bench/`.
Separate runtime copy: adjacent `repo/runtime`. All source changes were reverted.
Full raw trial logs, Clang calls, RSS samples and marker files are in `edit-results/`
remotely and local `dev002-results/edit-results/`. Compiler native build source and
logs remain in sibling local `native-ninja-accounting-20261016/`.
