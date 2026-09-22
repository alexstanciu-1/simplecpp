# Compiler migration baseline runner
Doc Status: supporting

`run_baseline.py` runs the [baseline plan](../../specs/planning/compiler_migration/baseline.md)
against an isolated copy of the pinned tracked inventory. It does not adopt compiler
code into this repository or alter the original checkout.

```bash
python3 tools/compiler_migration/run_baseline.py specs/planning/compiler_migration/results/NEW_RUN
```

The evidence directory must not already exist. The runner verifies the source
revision/hashes and pinned runtime revision/clean state, copies tracked source into
a retained temporary workspace, and changes only two configuration paths to the
same pinned external dependency. Suite groups run sequentially at their configured
internal concurrency. Do not run concurrent baseline invocations on the same host
when comparing timing.

Evidence includes full stdout/stderr per gate, commands, versions, configuration
changes, elapsed times, statuses, compact proof reports, and final original/snapshot
integrity checks. Each run also saves its driver and driver hash, host and Clang
target. Runtime include paths remain relative to the preparation configuration:
some original tests concatenate that directory directly with the configured path.
The final process exits unsuccessfully if any gate or integrity
check fails. During execution `summary.json` shows the current gate; an interrupted
runner can leave a `running` record, which is not a pass.

Scratch is deliberately retained, but original fixture runners can remove their
own temporary workspaces. Thus complete failed-fixture binary retention is not
promised. Durable logs and copied reports remain in the selected evidence directory.
Large generated binaries/packages are not checked in. This driver uses host Python;
it is baseline infrastructure, not compiler production code to port into PHP++.

After adoption, run `python3 compiler/tests/run.py --jobs 10 --timeout 180` from
the repository root for B01. `run_adopted_preparation.py <new-results-directory>`
runs B02–B07 against the adopted implementation, retains logs/proof reports and
checks adopted/original source integrity. Keep runs sequential to avoid multiplying
native-build concurrency. Adoption evidence and the relocation patch are linked
from the migration entry guide.

`probe_representations.py <new-results-directory>` verifies the configured pinned
PHP++ target using independent strict projects and explicit local runtime builds.
See [probe contracts](../../tests/portability/native_representation/README.md).
An unsupported required type produces a failed prerequisite, not a passing test.
Use `--target-checkout <checkout>` for the selected release recorded in
`compiler/tools/portability_target.json`; the original provider/toolchain pin is
unchanged. Candidate comparison is also available through the paired
`--toolchain <checkout> --revision <commit>` options.
