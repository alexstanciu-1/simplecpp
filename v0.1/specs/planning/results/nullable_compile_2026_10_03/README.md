# Nullable C++ compile experiment — 2026-10-03
Doc Status: planning

All experiment files live in this directory. Compiler/runtime sources were read-only.
Non-goal: changing language semantics, compiler emission, runtime ownership, or the
production test harness. No commit or integration is part of this experiment.

## Reproduction

`probe.py` copies all 99 nullable generated C++ fixtures from
`/tmp/my-try-probes-20261003-1/programs/`, preserving source bytes and manifest exit
codes. It uses the existing CommandRunner/run_tasks pool, with 12 workers for suite
comparisons and one worker for isolated measurements. This reproduces the native
compile/execute phase, not the PHP fixture-export suites. The saved original exports
avoid reading changing compiler implementation as the other agent works.

Run from the repository root:

```sh
PYTHONDONTWRITEBYTECODE=1 python3 compiler/my-try/temp/compile-probe-20261003/probe.py
```

Rerunning replaces this experiment's evidence. Compiler: Ubuntu Clang 18.1.3.
Flags: `-std=c++20 -Werror=return-type -I <repo>/runtime/include`; no optimization or
debug flags, matching nullable.py. Sources, outputs, exact commands, times and exit
codes are retained in programs/, phase directories and logs/commands.json.
inputs.json records compiler identity and input hashes captured during the run.

## Results

| Measurement | Original | Precompiled header |
| --- | ---: | ---: |
| Exact example, isolated compile + execute | 3.03 s | 0.88 s |
| Exact example within 99-fixture / 12-worker run | 7.71 s | 1.93 s |
| All 99 fixtures, 12-worker wall time | 47.63 s | 16.92 s |
| Additional 12-fixture batch wall time | 5.50 s | 2.02 s |

All expected exit codes matched in both full passes and the additional batch.
The exact example exits 3, proving its division-by-zero fallback remains unselected.
Existing checked-failure fixtures were included, with their expected exit codes.

Building runtime.pch cost 2.81 s and produced 20,272,656 bytes (19.33 MiB).
Including this one-time cost, the 99-fixture phase took 19.74 s versus 47.63 s:
about 2.41x faster / 59% less elapsed time. Reusing it gave about 2.81x throughput.
These are local sequential experiments, not controlled machine-wide benchmarks;
other work and scheduling affect elapsed time. No filesystem-cache flushing was done.
The 12-fixture repeat uses a different workload and is not a second full-suite trial.

## What costs the time

profile.json is a Clang time trace of an isolated object compilation. It reports
3.117 s frontend and 0.049 s backend, within 3.189 s compiler execution.
The preprocessed translation unit is 3,424,631 bytes. Header Source events are
inclusive and nested, so their durations must not be summed:

- cast.hpp: 1.183 s including its dependencies.
- condition_truthiness.hpp: 0.665 s including php_common.hpp (0.663 s).
- int_t.hpp: 0.626 s, almost entirely detail.hpp dependencies.

The generated operator umbrella includes mixed/container/string/cast families.
The condition implementation includes lang/php/support/php_common.hpp, bringing in
PHP take, debugging, var_dump, dynamic helpers, chrono, streams and file support.
Simply removing the emitted cast.hpp include cannot remove its transitive inclusion
through generated/operators.hpp in this example.

## Concrete improvement and ownership

runtime.hpp contains the exact example's five includes, in original order. Build it
once using the same compiler and flags with `-x c++-header ... -o runtime.pch`, then
add `-include-pch <absolute runtime.pch>` to fixture compilation. Generated C++ remains
byte-for-byte unchanged. The script is the working prototype.

A production implementation belongs in generated-program compilation infrastructure:
a shared native-fixture compile helper called by the test entrypoints, with a PCH
preparation step completed before workers begin. Begin with a fresh per-run PCH to
avoid persistent invalidation complexity. A persistent cache must distinguish compiler,
target, flags, macros, include paths, header contents and transitive dependencies.
Keep an uncached validation path: a forced umbrella can hide missing source includes
or affect overload visibility in programs beyond this tested set. These 99 passing
fixtures do not prove correctness for every generated program.

Longer-term cold-compile work belongs to runtime header dependencies and generated
operator-family ownership: narrow condition truthiness's dependencies and expose
family-specific operator headers from the runtime generator before selecting them in
the emitter. That spans runtime generation and C++ emission; it should be an agreed
separate slice, not an ad hoc rewritten division/truthiness implementation in output.
A prebuilt runtime library alone would not eliminate repeated header parsing.
