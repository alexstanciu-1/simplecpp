# Function import proof — 2026-11-04

Doc Status: supporting

Implemented `use` for existing functions across cpp/php/js, following the
[namespace/import contract](../../../specs/namespaces_v0_2.md). Explicit globals are
the next slice. Ordinary top-level variables retain implicit-main locality;
visibility/shadowing configurability is documented as debt.

## Structure changes

- `ast_import`: source occurrence owning absolute target, local binding name and
  written `import_kind`, with ordinary token boundaries and file parent.
- `ast_file_node`: owns `imports` syntax inventory and a private first-reference
  `function_imports` lookup index. `function_key()` applies imports only to complete
  unqualified names; no namespace aliases or new published symbols.
- `Import_Syntax`: parses `use` through the existing flavor path adapter.
- `Import_Preparation`: validates imports after function contracts settle, before
  body work. Full and incremental coordinators use the same validation path.
- Calls still retain and observe the actual function's stable symbol entry. No new
  dependency model, prepared-function copy or runtime structure.

## Checks

25 compiler CTests passed (excluding service and native suites), including new
`compiler_imports`, namespace, scopes, dependency, incremental, CLI and style checks.
Import proofs cover textual order, groups, repeated bindings, file isolation,
namespace conflicts, unused failures, canonical identity, inspection, emission
purity, reused importing AST, body-only target changes, removal and reactivation.

Three native programs printed `ping\npong\n` and exited 8. Each compiled three
translation units with Clang, O2/g0, Ninja -j8, mold, libc++, no PCH/LTO and the
existing runtime archive. These are single-run correctness measurements, not a
benchmark comparison between flavors; the first build was noticeably slower.

| Flavor | Native elapsed ms | Frontend ms | LLVM backend ms | Link command ms |
| --- | ---: | ---: | ---: | ---: |
| cpp | 557.85 | 14.02 | 16.04 | 124 |
| php | 135.15 | 13.72 | 17.29 | 67 |
| js | 137.02 | 22.39 | 29.16 | 73 |

Frontend and LLVM totals are cumulative across units from a separate traced rebuild;
they cannot be added to elapsed time. LLVM includes optimization and code generation.
The PHP fixture/test process took 55.97 ms for all cases, not a per-program compile
measurement. Runtime rebuilding is excluded. Logs, complete Clang phase totals and
mold traces are retained under `v0.2/build/import-slice-proof-2026-11-04/`, with the
summary in `results.json`. Reproduce with:

```sh
XDEBUG_MODE=off python3 -B v0.2/compiler/tests/namespaces_native.py --imports
```
