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

Run the focused branch/body slice, its related PHP controls and generated C++ programs:

```bash
python3 compiler/my-try/tests/control_flow.py --results /tmp/FRESH_CONTROL_FLOW_RESULTS
```

This uses the same 12-slot FPM/OPcache pool, with CLI fallback. The ordinary full
runner also consumes the control-flow execution manifest. This does not rebuild
the compiler itself natively.

For the focused ordinary-function checks, run:

```bash
python3 compiler/my-try/tests/functions.py --results /tmp/FRESH_FUNCTION_RESULTS
```

This runs the S2S preparation/export suite and eleven related PHP suites, then compiles
and executes its `function_*` programs through the shared 12-slot pool. The full
S2S and requested native runners also consume these fixtures. It does not rebuild
the compiler natively.

For nullable values, conditional/coalescing results and single-value `isset`/`empty` probes:

```bash
python3 compiler/my-try/tests/nullable.py --results /tmp/FRESH_NULLABLE_RESULTS
```

This runs related PHP suites and compiles/executes `nullable_*` S2S exports, including
checked-failure probes, through the same 12-slot pool. It does not rebuild the compiler
natively. The S2S manifest also makes these programs available to requested native runs.

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
source comparison plus C++ compilation/execution for both `s2s.php` and
`control_flow.php`, supplementary probes and rejection/recovery cases. The suites
export their authored source cases so native coverage cannot silently omit the new
control-flow chapter. Runtime-error probes first compare uninstrumented PHP/native
output, then compile/run the instrumented program. `S2S_Proof::control_flow()` adds a
shared portable span-move identity and unselected-arm dependency failure/retry/repair
proof, invoked separately through the native driver. The optional LLVM comparison
path also uses the same pool.

Native evidence includes compiler source hashes, target toolchain hashes and
`harness_hashes.json` for the runner, fixture exporters and portable proof/driver.
When testing uncommitted adaptations, these hashes identify the tested overlay;
the candidate base revision alone is not the full tested source identity.

## Optimization and size measurements

Record the actual compiler command/build manifest, not an assumed optimization
default. The Chapter 04 native checkpoint used Clang 18 with `-O0 -g`; its later
`-O2 -g` size experiment is only a build plus smoke/parity proof, not a full optimized
regression checkpoint. Generated fixture flags are separate from compiler-build flags.

For comparisons, preserve the validated binary and use an isolated build directory
with the same sources, toolchain, runtime and flags except the intended variable.
Report exact bytes and MiB, debug/stripping status, LTO and shared-library boundaries.
Strip copies, not the proof binary. Shared-library compiler drivers can be tiny:
compare required compiler libraries as well, while distinguishing general system
dependencies and any external toolchain needed to finish source-to-native compilation.
Measured evidence belongs in the [portability review](portability/conversion_review.md),
not in a new default optimization policy or a performance claim.

## Isolation and evidence

Each native fixture writes `request.txt` in its own working directory. The single
native driver binary and PHP driver read that relative file; each invocation owns its
in-process compiler state. Recovery inputs are shared read-only. No shared mutable
request file serializes or races concurrent fixtures.

Each command gets a private temporary directory through `TMPDIR`, `TMP` and
`TEMP` (and request-specific `sys_temp_dir` under FPM), including PHP/native suites that inspect temporary-file cleanup. Output paths
and log names must be unique within a run. Stdout/stderr are retained per command;
`commands.json` is updated atomically under a lock and records command, working
directory, elapsed time, exit code, expected exit code, executor and selection reason. Timeouts kill the command's
process group, including compiler children, before collecting output and cleaning up.

The pool reports each completion immediately and collects all failures in the current
phase. Successful jobs do not hide failures; failed phases stop dependent phases.
Returned outcomes retain input order, while command journals show completion order.
The full runner writes `summary.json` even on failure and `php-summary.json` for PHP
suite outcomes. Native validation retains per-attempt command logs, source/toolchain
hashes and a success summary as described in the [portability review](portability/conversion_review.md).

## Generated C++ precompiled headers

`tests/run.py`, `tests/nullable.py`, `tests/functions.py` and `tests/control_flow.py`
use `tools/cpp_fixture.py` to build one runtime PCH before scheduling generated C++
programs. The PCH lives in the fresh results directory under `cpp-pch/`; its build
uses the same compiler and flags as the fixtures and is recorded as `cpp-pch-build`
in the command journal. A failed PCH build stops the phase. There is no persistent
cache or silent uncached retry. Workers share the completed artifact read-only.

Pass `--no-pch` to any of these runners to compile with the original header visibility.
Use that mode when validating emitted include completeness: preloading runtime headers
can hide missing includes or affect overload visibility. The summary records `cpp_pch`.
PHP-only runs do not prepare a PCH. LLVM, native compiler construction, PHP suites'
internal native commands and `tools/native_validate.py` are unchanged.

Reducing cold compilation cost through compiler-selected runtime headers remains
[explicit dependency debt](planning/incremental_strategy.md#debt-precise-runtime-includes-for-faster-c-compilation).

## Reusing the runner

Import from `tools/test_runner.py`; the scheduler is independent of test language:

```python
commands = CommandRunner(result_path / 'logs', repository_root, jobs=12)

def check_fixture(source, executable, expected):
    commands.run(source.stem + '-compile', ['clang++', source, '-o', executable])
    return commands.run(source.stem + '-execute', [executable], expected=expected)

outcomes = run_tasks([
    Task(name, lambda source=source, executable=executable, expected=expected:
         check_fixture(source, executable, expected))
    for name, source, executable, expected in fixtures
], jobs=12)
commands.close()  # Prefer a with block or try/finally in real callers.
```

`tests/runner_test.py` proves the default concurrency bound and continuous slot refill
using synchronization events, stable result ordering, aggregated failures, command
isolation, expected nonzero exits, launch-failure evidence and child-process timeout
cleanup.

## PHP execution

The shared `CommandRunner` defaults to **FPM with OPcache** for PHP scripts and
`php -r` snippets. Both test entrypoints accept `--php-executor auto|fpm|cli`:

- `auto` (default): try matching FPM, then use CLI if discovery/startup/health checks fail.
- `fpm`: require FPM for eligible commands; unavailable FPM is an error.
- `cli`: explicitly retain subprocess execution for all PHP.

Install matching `php`/`php-fpm` versions and `cgi-fcgi` to use FPM. The runner discovers
versioned FPM binaries via PATH or `/usr/sbin`, matches the exact PHP version, and uses
the CLI INI and scanned extension directories. Unix sockets and PHP's POSIX extension
must be available. Startup fallback is printed and recorded in the command journal;
failed requests are **never retried through CLI**.

Each runner lazily owns a private FPM master with `jobs` single-worker pools. A slot
is leased until its request finishes or is cancelled, preventing a timeout from
killing a worker reused by another command. Pools share OPcache; timestamp checks and
fresh-file protection remain enabled, JIT and Xdebug are disabled for FPM. Existing
system FPM services are untouched. `close()` / a context manager stops the private
master; both entrypoints close it in `finally`, with an exit cleanup as a backstop.

The host adapter supplies script arguments, working directory, binary stdin and separate
stdout/stderr capture and private temporary files. Every request starts fresh PHP
state. Returning normally means success; uncaught exceptions/fatal errors mean failure.
`exit()`/`die()` cannot provide process exit status through FPM and are rejected as
incomplete execution. This is host test infrastructure, not portable compiler source.

CLI-specific commands stay explicit: PHP interpreter options such as `-l`, compiler
CLI behavior checks, build/conversion tools using exit status, and `tests/native.php`
which launches `PHP_BINARY` as a child CLI. Call `run(..., cli_reason='...')` for such
contracts; the reason is recorded even in FPM-required mode. FPM does not emulate
`PHP_SAPI` or `PHP_BINARY`. The style checker also uses the shared executor (one persistent worker for its
sequential tokenizer requests). `MY_TRY_PHP_EXECUTOR=cli|fpm|auto` selects its backend;
the main runner passes its selection to child Python tools. Direct shell invocations
of `php` are unchanged.

Run the executor's focused integration proofs (private sockets required):

```bash
MY_TRY_TEST_FPM=1 python3 compiler/my-try/tests/php_executor_test.py
```

These prove parallel isolation, argument/byte preservation, worker-state reset,
OPcache refresh, unavailable-FPM fallback, no failed-request retries, and timeout
cleanup of PHP child processes followed by worker recovery. Ordinary runner tests
also cover the shared scheduler and subprocess paths.

The 2026-10-02 checkpoint retained evidence in `/tmp/my-try-fpm-default-final-20261002/`
and `/tmp/my-try-fpm-parity-final-20261002/`: 41/43 PHP suites passed, with the existing
`model.php` and `structure_access.php` failures unchanged; 341 source fixtures and
37 rejections matched CLI/native bytes, and 74 supplementary probes passed at 12 jobs.
This reused the existing native binary; it is not a new native portability checkpoint.

The [CLI/FPM comparison](portability/php_cli_fpm.md) records measured PHP-only benefits
and limitations. `tools/benchmark_php.py` preserves explicit benchmark modes independent
of the runner default.
