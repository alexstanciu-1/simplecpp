# Explicit global proof — 2026-11-04

Doc Status: supporting

See [ownership and delivery](globals.md) for the implemented structures and boundaries.
The slice adds explicit integer storage and reads, not writes or visibility configuration.

26 compiler CTests passed, excluding the service/native suites, plus direct tokenizer
and parser checks. The new global proof covers all flavors and mixed-flavor sources,
literal limits, namespace lookup, local shadowing, duplicate chains, stage inspection,
lowering purity and incremental stable handles. Initializer edits preserve reader AST
and body facts; retirement invalidates readers and reintroduction reactivates the entry.
The panel's PHP stage reporter was also exercised directly with a two-file namespace
global/function example: its JSON was valid, no warnings were emitted, and publication,
prepared globals and generated C++ were present. This was not an HTTP-server test.

Native fixtures contain three source units plus the entry adapter. Each executable
printed `5\n10\n10\n20\n42\n` and exited 8. Declaration-only files supplied root globals,
and the emitted int64 minimum/maximum definitions also compiled successfully. Definitions
use `constinit`, so Clang enforces static initialization rather than a dynamic initializer.

Settings: Clang O2/g0, Ninja -j8, mold, libc++, no PCH/LTO, fresh object files,
existing runtime archive. Single-run correctness timings, not a flavor comparison:

| Flavor | Native elapsed ms | Frontend ms | LLVM backend ms | Link command ms |
| --- | ---: | ---: | ---: | ---: |
| cpp | 150.46 | 15.26 | 17.83 | 62 |
| php | 111.98 | 14.80 | 18.80 | 57 |
| js | 115.31 | 21.32 | 25.49 | 59 |

Frontend/LLVM are cumulative totals from separate traced rebuilds, not components to
add to the elapsed column. LLVM includes optimization and code generation. Their nested
optimizer/codegen-pass totals were cpp 5.50/6.14 ms, php 5.89/6.40 ms and js 7.70/9.33 ms.
Mold's internal traced wall times were 15/15/16 ms; link-command elapsed includes the
driver/process overhead. Runtime rebuilding is excluded. PHP test/fixture generation
took 53.32 ms across all cases, not a per-source compilation measurement.

Logs, raw Clang traces and full results remain under
`v0.2/build/global-slice-proof-2026-11-04/`. Reproduce with:

```sh
XDEBUG_MODE=off python3 -B v0.2/compiler/tests/namespaces_native.py --globals
```
