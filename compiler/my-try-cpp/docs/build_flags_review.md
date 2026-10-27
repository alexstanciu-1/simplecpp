# Build flags review — 2026-10-26
Doc Status: supporting

Searched the `my-try` Markdown documentation and build implementations, then
reviewed the native command owner, build guide and referenced performance evidence.
The relevant sources are:

- [`Native_CPP_Commands`](../../my-try/06_native/cpp_commands.php): production
  compile/PCH/link recipes, debug opt-in and explicit linker threads.
- [Native build guide](../../my-try/docs/architecture/native_cpp_build.md): cache
  identity, dependency tracking and linker configuration.
- [Ninja/Mold measurements](../../my-try/docs/planning/native_ninja_mold_20261016.md):
  eight Mold threads and parallel object compilation.
- [Compile performance](../../my-try/docs/planning/compile_performance.md): repeated
  runtime-header cost and PCH measurements.
- [Unit/layout plan](../../my-try/docs/planning/clang_units_and_mold.md): stable small
  translation units, dependency isolation and deferred experiments.

## Applied configuration

The build driver now defaults to `--jobs 8`, including explicit dependency builds.
Compiler executable links use Mold with `-Xlinker --threads=8`. Linker threads are
independent of Ninja's compile-job limit. Direct Ninja commands must pass `-j8`.
No dependency package was rebuilt to make these changes.

Retained: `-O2 -DNDEBUG`, C++23, static dependency packages, no debug information,
no PCH and no LTO. The old runtime PCH and `-fpch-instantiate-templates` address
runtime-template parsing; the compiler's current public include closure deliberately
excludes those templates. The old `-Wshadow` is a diagnostic, `-pthread` supports
threaded programs, and `-ftime-trace` is profiling instrumentation, not a normal
build acceleration flag. None was added as a speculative speed optimization.

## Retest

Forced recompilation of all five `.cpp` files in `src/`, then archive and executable
link, using `ninja -C build/compiler -j8 my-try-cpp`:

| Trial | Seconds |
| --- | ---: |
| 1 | 0.603 |
| 2 | 0.660 |
| 3 | 0.591 |
| Median | 0.603 |

Tests were not run. The executable's `--help` smoke check passed, actual link
commands include the eight-thread setting, and dependency archive timestamps and
sizes were unchanged. Source timestamps were restored after the measurements.
Raw summary: `build/compiler/jobs8-evidence.json` (local build artifact).

These are warm-filesystem local trials, with uncontrolled host load. The earlier
0.584 s automatic-parallelism trial is in the same range; this does not establish
a speedup. Five independent source files cannot occupy all eight compile slots.
