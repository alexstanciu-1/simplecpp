# Parallel accounting application compilation
Doc Status: supporting

Measured production output from `15612367` with Clang 18.1.3, C++23, O0,
debug disabled and the existing eager PCH. Runtime, middleware and layout artifacts
are already built. Source preparation/emission and those setup costs are excluded.

Three alternating-order rounds freshly compile the same 16 application translation
units, then link and run the executable. The parallel run allows **up to 12 Clang
processes**, refilling slots as units finish. Hardware: AMD Ryzen 5 PRO 4650G,
6 physical cores / 12 logical CPUs, WSL2 Linux.

| Maximum simultaneous compilers | App compilation wall time | Link time | App compilation + link |
| --- | ---: | ---: | ---: |
| 1 | 7.447 s | 0.494 s | 7.941 s |
| 12 | 1.653 s | 0.481 s | 2.137 s |

Each column reports its own median. Parallel compilation is **4.50x faster** in
elapsed time; application compilation plus linking is **3.72x faster**. These are
wall times, not sums of per-process CPU or elapsed times. All six executables pass
998 checks with zero failures. The three parallel compilation samples are
1.634, 1.658 and 1.653 seconds. No O2 work was performed.

The benchmark uses the shared bounded task runner and invokes Clang directly with
the saved build arguments. There is no Ninja invocation or production scheduler
change. Each round recompiles every application unit; filesystem/PCH pages may be
warm. The contemporaneous serial baseline is used rather than the earlier 7.39 s.

`summary.json` contains all samples. `run.py` records the procedure; it requires
`/tmp/accounting-header-fix` generated output, compiler action log and cache.
Raw command logs are in `/tmp/accounting-parallel/logs`. See the preceding
[production header fix](../prelower_header_fix_2026_10_19/README.md).
