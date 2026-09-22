# First scalability benchmark
Doc Status: supporting

Standalone performance workload, separate from the ordinary regression suite.
It calls the real `Compiler_Session::compile()` and configured native backend;
no production process or selection policy is modified.

Requirements: Linux, Python 3, 64-bit PHP 8.2+ (for resetting peak memory), and
the working Clang/linker from `tools/backend.json`, PHP POSIX support and `setsid`. Run from the repository root:

```sh
python3 benchmarks/scalability/generate.py
python3 benchmarks/scalability/run.py
# Optional selection; timing results default to ignored build/scalability/results.json.
python3 benchmarks/scalability/run.py --sizes 64kb 1mb --trials 3
# Larger samples, with the same limits and three-trial method.
python3 benchmarks/scalability/run.py --sizes 5mb 10mb \
  --results build/scalability/large_run.json
# Separate diagnostic with tool time/CPU/peak RSS; do not mix with baseline timings.
python3 benchmarks/scalability/run.py --sizes 1mb --trace-tools \
  --results build/scalability/tool_diagnostic.json
python3 benchmarks/scalability/run.py --sizes 5mb 10mb --trace-tools \
  --results build/scalability/large_tool_resources.json
```

Each size has three trials by default. Each trial gets a fresh PHP process and
a disposable project copy. In one resident session it performs:

1. A fresh full native compile; verify executable exit status 42.
2. An unchanged native compile; verify zero frontend/body/lowering replacements,
   reuse of the exact artifact, and exit status 42.
3. One body edit in the last group file; verify selective compilation, exactly
   one replaced frontend and native object, a new native artifact, and exit status 43.

The edit adds an assignment, preserving the function definition. Only the
temporary copy changes. Size changes and mtime is advanced by two seconds to
make detection deterministic under the prototype's timestamp limitation.

`hrtime()` surrounds only `compile()`. It includes reading, compiler stages,
target probes, native object compilation/linking and publication. Bootstrap,
copying/editing fixtures, counters, JSON output and executing the result are
outside the timer. Cold means a new compiler session, not flushed OS caches.
The request times are complete compiler times, not Clang-only times. The runner
also records changes in PHP GC run/collected counters and collector time during
each request (`gc_seconds`, or null when PHP does not expose collector time).
Collection before the request timer remains benchmark preparation; these counters
do not change the production GC policy.
Debug exports, Xdebug, CLI OPcache and JIT are disabled in the recorded run.
The runner sets a 2 GiB PHP memory limit and a 180-second trial timeout;
the existing compiler also has its own per-tool timeout.

Peak PHP memory is reset before each compile. `php_peak_allocated_bytes` is the
allocator's reserved peak, including resident snapshots; `php_peak_bytes` is
its used peak. Neither includes external Clang/linker memory or measures full
process RSS. The auxiliary `php_retained_bytes` is captured while the previous
snapshot is still held for reuse comparisons, not after releasing old readers.
No large compiler debug snapshot is serialized during timing.

`--trace-tools` uses a temporary backend configuration and forwarding wrapper
around the same real Clang. Each phase records tool invocations, elapsed time,
user/system CPU time and peak RSS. Each wrapper starts just one child invocation;
Linux `RUSAGE_CHILDREN` supplies CPU usage including waited descendants and
`ru_maxrss` in KiB (converted to bytes in JSON). This is the largest per-process
RSS high-water mark within the invocation, not the sum of simultaneous process
memory. Linking includes the Clang driver and its real linker; it is not an
isolated linker-only measurement. PHP and the parent Python wrapper are excluded.
These RSS figures cannot be added to PHP allocator peaks to claim a combined
memory peak. The earlier diagnostic JSON predates the CPU/RSS fields.
Wrapper startup adds overhead, so these are separate diagnostic measurements.
For small modules the observer effect is substantial: a later unwrapped native
pool used about 10.4 logical-CPU equivalents, versus about 4.2 in wrapper runs.
Do not use wrapper-run CPU utilization as the normal compiler's utilization.
The wrapper classifies operations for reporting only; arguments, input, output
and exit status are forwarded to the real tool.

The runner records exact source sizes and hashes, compiler-source fingerprint,
environment, every trial, and median/min/max times. It preserves error details
and fails if any trial fails. Generated source resides in
[examples/scalability](../../examples/scalability/README.md); temporary
projects and executables are removed when each trial exits.

Recorded results: [first report](../../docs/details/scalability_first_run.md),
[baseline raw data](results/2026-09-11-baseline.json),
[5/10 MiB raw data](results/2026-09-11-large.json),
[initial tool timing data](results/2026-09-11-tools.json), and
[5/10 MiB tool time/CPU/RSS data](results/2026-09-11-large-tools.json).

The subsequent file-module implementation records `emitted_modules` and
`replaced_objects` too. Results: [5 MiB file modules](results/2026-09-11-file-modules-5mb.json),
[GC scope experiment](results/2026-09-11-gc-scope.json), and
[analysis and tradeoffs](../../docs/details/incremental_native_investigation.md#implemented-proof-and-measurements).
The GC experiment is a diagnostic only; the production runner does not suspend GC.
Instrumented cold builds pay one wrapper startup per object, so report their
overhead separately from real tool invocation times and unwrapped runs.


Concurrent runs use `compile_jobs` from the recorded backend configuration.
Tool logs now include monotonic start/end timestamps. Per-invocation elapsed
values overlap: `tool_seconds` is their **sum**, not native-stage wall time.
Use object-call intervals to inspect overlap; sum child CPU time separately.
RSS peaks remain per-invocation process high-water values, not an aggregate
peak across concurrent jobs. The wrapper locks only its diagnostic log append.

Recorded 20-job runs: [unwrapped 5 MiB](results/2026-09-11-parallel20-5mb.json),
[tool intervals and CPU](results/2026-09-11-parallel20-tools-5mb.json), and
[interpretation](../../docs/details/incremental_native_investigation.md#twenty-concurrent-external-compilations).

Subsequent ordered repeat: [20-job totals](results/2026-09-11-retest20-5mb.json),
[40-job totals](results/2026-09-11-retest40-5mb.json),
[20-job tool measurements](results/2026-09-11-retest20-tools-5mb.json),
[40-job tool measurements](results/2026-09-11-retest40-tools-5mb.json), and
[comparison and unchanged GC costs](../../docs/details/incremental_native_investigation.md#repeat-with-20-and-40-jobs).
The repository setting remains 20; the repeat did not establish a reliable
benefit from 40 jobs. The raw request times include PHP processing and native
tools; diagnostic CPU equivalents describe Clang and its descendants only.

For the [native dispatch and batch investigation](../../docs/details/native_dispatch_investigation.md),
run these diagnostics sequentially from the repository root:

```sh
python3 benchmarks/scalability/diagnose_dispatch.py
python3 benchmarks/scalability/replay_batches.py
```

The first requires `pidstat`, `vmstat` and `iostat` (sysstat/procps), plus the
ordinary benchmark dependencies. It copies the compiler to a temporary directory,
adds guarded timing probes to that copy, then executes and verifies the existing
cold/unchanged/body-edit sequence. It does not wrap Clang or modify production
sources. It fixes the private configuration at 20 jobs. Stream/launch timings
are nested within the object pool, whose child CPU comes from `getrusage(1)`.
The copied implementation is intentionally guarded by exact source matches:
after compiler refactoring, review the probe placement if a guard fails.

Reports, system samples and cold LLVM files are written under ignored
`build/scalability/dispatch_probe/`. The second diagnostic consumes those files
and runs three isolated object-compilation trials each with 1/8/32 files per
Clang driver and at most 20 concurrent drivers. Each trial verifies all objects,
links and checks exit 42 outside the object timer. It is a tool experiment,
not a new compiler execution path. `diagnose_dispatch.py` must succeed before
replaying; generated inputs should not be edited between the commands.

PHP-only tool confirmation, using those existing unmodified LLVM exports:

```sh
XDEBUG_MODE=off php -d memory_limit=2048M -d opcache.enable_cli=0 \
  benchmarks/scalability/php_process_probe.php
```

This runs five direct single-command `proc_open()` probes, then three trials
each of 669 version commands and real object compilations through the production
`Tool_Process` at 20 parallel jobs. No Python is running in this test. It verifies
all objects and executable exit 42, and saves `build/scalability/php-only-processes.json`.
The existing LLVM export is input preparation, not part of its timers.

For a complete compiler trial without the Python runner, copy a fixture to a
disposable folder and invoke `measure.php` directly:

```sh
# Use the fixture's edit_file from examples/scalability/inventory.json.
XDEBUG_MODE=off php -d memory_limit=2048M -d opcache.enable_cli=0 \
  benchmarks/scalability/measure.php \
  /path/to/disposable/project/project.json /path/to/disposable/program \
  /path/to/disposable/project/src/EDIT_FILE.phs
```

The final path must identify the copied fixture's real edit file. `measure.php`
edits that file after the unchanged request; never pass the tracked fixture.
Results: [PHP-only confirmation](results/2026-09-11-php-only.json) and
[interpretation](../../docs/details/native_dispatch_investigation.md#php-only-confirmation).

For a PHP-only breakdown into seven groups:

```sh
XDEBUG_MODE=off php benchmarks/scalability/profile_groups.php
```

This creates a temporary compiler copy with guarded per-stage elapsed/GC timers,
then runs three fresh PHP processes against disposable copies of the 5 MiB
fixture. Clang is unwrapped, with 20 parallel jobs. Raw measurements and group
means are saved to `build/scalability/group-profile.json`. The native link is
measured separately and subtracted from native preparation; publication and
remaining coordinator time share the seventh group. Arithmetic means preserve
additivity across groups, and GC stays inside the stage where it occurs.
All executable/reuse checks from `measure.php` remain enabled. Production files
are never instrumented. If an exact source guard fails after a refactor, review
the probe placement instead of silently skipping that stage.

[Recorded seven-group breakdown](../../docs/details/native_dispatch_investigation.md#seven-group-compiler-breakdown)
and [raw timings](results/2026-09-11-grouped-stages.json).

The current probe additionally records native object selection, compilation,
join/path preparation, Clang/linker invocation, executable hashing, publication
and snapshot preparation. These nested scopes are included in the seven groups;
never add them again to the request total. Current data:
[new grouped timings](results/2026-09-11-reuse-mold-groups.json).

For a resident sequence without forced pre-request collection, use PHP 8.3+
(which exposes collector timing) and a disposable copy of the **5 MiB** fixture:

```sh
XDEBUG_MODE=off php -d memory_limit=2048M -d opcache.enable_cli=0 \
  benchmarks/scalability/resident_updates.php \
  /path/to/disposable/project/project.json /path/to/disposable/program \
  /path/to/disposable/project/src/EDIT_FILE.phs
```

This edits the supplied copy eight times, checks unchanged reuse after each,
then checks failure/repair. It verifies all executable results and selected
object counts; it never disables or forces GC. Release of previous result
readers is timed separately, including collection outside `compile()`. Reports
are saved under `build/scalability/resident-updates.json`.
[Recorded resident sequence](results/2026-09-11-resident-updates.json).

To compare linkers on the same retained objects, use a disposable 5 MiB copy and
explicit installed executable paths (the Clang path must match the configured
backend that generates the objects):

```sh
XDEBUG_MODE=off php -d memory_limit=2048M -d opcache.enable_cli=0 \
  benchmarks/scalability/linker_probe.php \
  /path/to/disposable/project/project.json /path/to/disposable/program \
  /path/to/clang /path/to/setsid /path/to/ld /path/to/ld.lld /path/to/mold
```

Five trials per linker rotate order. The timer includes Clang and the linker;
object preparation, output hashing/publication and verification are excluded.
Outputs must be private to this diagnostic. The script verifies exit 42 and
writes `build/scalability/linker-comparison.json`.
[Initial linker comparison](results/2026-09-11-linkers.json) used the same timing
method before the script accepted explicit tool paths.
[Analysis and remaining GC cost](../../docs/details/native_dispatch_investigation.md#linker-selection-and-unchanged-reuse).
