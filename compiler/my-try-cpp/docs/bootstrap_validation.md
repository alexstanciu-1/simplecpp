# Build foundation validation — 2026-10-26
Doc Status: supporting

Local Linux/WSL environment, Clang 18.1.3, Mold 2.40.4, CMake-generated Ninja,
C++23/O2/NDEBUG. These are single local trials for the tiny bootstrap executable,
not measurements of compiler functionality or a forecast for the full rewrite.

| Operation | Elapsed seconds |
| --- | ---: |
| Explicit runtime package build (core + JSON, four jobs) | 18.677 |
| Explicit middleware package build | 3.180 |
| Compiler configure/build/smoke after selecting the O2 package | 0.431 |
| Main translation-unit rebuild and link, including template scan | 0.188 |
| Unchanged direct Ninja invocation | 0.007 |

The dependency cold builds are outside the routine compiler edit loop. A previous
initial configure/build/smoke trial using CMake's default Release optimization took
1.223 seconds; the committed recipe explicitly selects O2 instead.

`tests/build_boundary.py` passed:

- Runtime and middleware source/header timestamp changes trigger no compiler work.
- A main source timestamp change recompiles its object and relinks successfully.
- Published dependency archives remain unchanged through those checks.
- The runtime-backed builder smoke executable succeeds.
- Active templates in a transitive header and through macro expansion are rejected;
  template text in comments/inactive preprocessing branches is accepted.

Additional bounded publication checks passed with temporary middleware source edits
restored afterward: a compile failure preserves the prior selected package; an
unpublished content change leaves the normal compiler build idle; an explicit
middleware update publishes a new package that the compiler adopts successfully;
restoring the source selects the original cached package again. Configuring with
a missing middleware package fails with the setup instruction.

The compiler compilation database contains only the published middleware include
directory, not runtime headers. The executable's ELF comment section identifies
Clang and Mold. The runtime build reports an existing redundant explicit hash
instantiation warning; it does not prevent compilation.

No full runtime suite, sanitizer suite, compiler semantic test, concurrency test or
incremental semantic test was run or is claimed by this build-foundation slice.
