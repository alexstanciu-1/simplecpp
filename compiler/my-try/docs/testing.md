# Test runners
Doc Status: supporting

Both `tests/run.py` and `tools/native_validate.py` use the reusable
[`tools/test_runner.py`](../tools/test_runner.py) worker pool. The default is **12
concurrent tasks**; `--jobs N` overrides it and `--jobs 1` runs serially. A completed
task immediately frees its slot for the next queued task. There is no batch barrier
between groups of 12.

A task owns its sequential dependencies: source comparison, compilation and execution
stay ordered inside one fixture task. Real phase dependencies still apply: suites
produce manifests before their generated programs run, and native compiler construction
precedes native fixture execution. Do not create nested worker pools inside a task.
The limit applies to tasks in one runner invocation, not compiler-internal threads or
separate simultaneous runner invocations. Assertions inside each PHP suite retain their
existing order; independent suites run concurrently.

## Commands

Run all PHP suites with retained evidence:

```bash
python3 compiler/my-try/tests/run.py --php-only --results /tmp/FRESH_PHP_RESULTS
```

Run lint, Python runner/style tests, the style gate, all PHP suites, generated LLVM/C++
programs and the host CLI checks:

```bash
python3 compiler/my-try/tests/run.py --results /tmp/FRESH_RESULTS --jobs 12
```

`--skip-style` explicitly omits the repository style gate and records that omission;
it does not suppress behavioral failures. `--php-only` omits the extra lint/style and
generated-program sweep (some PHP suites themselves execute native samples).

Native compiler validation remains an explicit checkpoint:

```bash
python3 compiler/my-try/tools/native_validate.py \
  --target-checkout /absolute/path/to/simplecpp \
  --candidate-revision FULL_COMMIT \
  --results /tmp/FRESH_NATIVE_RESULTS --no-stan --types-only --jobs 12
```

`--types-only` skips parked LLVM validation. Its default fixture pool covers PHP/native
source comparison plus C++ compilation/execution, supplementary probes and rejection/
recovery cases. The optional LLVM comparison path also uses the same pool.

## Isolation and evidence

Each native fixture writes `request.txt` in its own working directory. The single
native driver binary and PHP driver read that relative file; each invocation owns its
in-process compiler state. Recovery inputs are shared read-only. No shared mutable
request file serializes or races concurrent fixtures.

Each subprocess gets a private temporary directory through `TMPDIR`, `TMP` and
`TEMP`, including PHP/native suites that inspect temporary-file cleanup. Output paths
and log names must be unique within a run. Stdout/stderr are retained per command;
`commands.json` is updated atomically under a lock and records command, working
directory, elapsed time, exit code and expected exit code. Timeouts kill the command's
process group, including compiler children, before collecting output and cleaning up.

The pool reports each completion immediately and collects all failures in the current
phase. Successful jobs do not hide failures; failed phases stop dependent phases.
Returned outcomes retain input order, while command journals show completion order.
The full runner writes `summary.json` even on failure and `php-summary.json` for PHP
suite outcomes. Native validation retains per-attempt command logs, source/toolchain
hashes and a success summary as described in the [portability review](portability/conversion_review.md).

## Reusing the runner

Import from `tools/test_runner.py`; the scheduler is independent of test language:

```python
commands = CommandRunner(result_path / 'logs', repository_root)

def check_fixture(source, executable, expected):
    commands.run(source.stem + '-compile', ['clang++', source, '-o', executable])
    return commands.run(source.stem + '-execute', [executable], expected=expected)

outcomes = run_tasks([
    Task(name, lambda source=source, executable=executable, expected=expected:
         check_fixture(source, executable, expected))
    for name, source, executable, expected in fixtures
], jobs=12)
```

`tests/runner_test.py` proves the default concurrency bound and continuous slot refill
using synchronization events, stable result ordering, aggregated failures, command
isolation, expected nonzero exits, launch-failure evidence and child-process timeout
cleanup.
