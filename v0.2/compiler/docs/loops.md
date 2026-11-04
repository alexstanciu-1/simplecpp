# Braced while and do-while

Doc Status: supporting

The [loop contract](../../../specs/loop_statements_v0_2.md) owns semantics.
Bare [break/continue](transfers.md) target the nearest loop. Numbered/labelled transfers,
foreach, unbraced loops, constant-condition pruning and precise loop value analysis
remain deferred. No additional truthiness types or runtime APIs are introduced.
The [bounded for slice](for.md) adds one initializer, condition and assignment update.

## Structure and ownership

`ast_loop_statement` is a common syntax owner for a required condition and an `ast_block`.
`ast_while_statement` and `ast_do_while_statement` identify test order. No loop-specific
prepared object, alias graph, transfer target, retained completion flag or dependency
watcher is introduced. Conditions belong to the loop, bodies own their lexical scopes.
All blocks in one function share the existing unique storage-ID allocator.

The parser's braced-block routine now serves branches and loops. The body-local collector
never publishes body locals into its parent. Diagnostics/reset and syntax/preparation
inspection traverse the owned children. Generated conditions go through shared preparation
and C++ condition helpers. C++ output uses native while/do-while, so tests remain inside
the loop and execute at the correct frequency, including call arguments/string lifetimes.

## Preparation cost and completion

Each loop body and condition is prepared once regardless of runtime iterations. While:
forget values, prepare test, forget values, prepare child body, forget values. Do-while:
forget values, prepare child body, leave its scope, forget values, prepare test, forget
values. These barriers clear only the current write cache and prohibit fallback to stale
initializer constants. They preserve types, bindings and earlier occurrence snapshots.
Assignments/declarations can establish fresh facts within each region. No source-unit or
global-symbol scan, environment-map copy, dataflow merge or preparation fixed point occurs.

Transient completion flags now distinguish fallthrough, return, break and continue.
While may fall through. Do-while guarantees return only if its body guarantees return;
its test is still semantically checked. Functions remain the sole watcher/completion
owners. Callee signature edits invalidate loop consumers; body-only edits retain them.

## Proofs and limits

`tests/loops.php` covers all flavors, condition/body ordering, zero and repeated iterations,
nested branches/loops, shadowing and body-local exclusion, return completion, generated
truthiness, unsupported syntax/types, reset/purity/inspection and exact single-traversal
counts. Incremental tests cover nested dependencies, condition return-type changes,
rejection/recovery, unchanged AST reuse and no-op/body-only reuse. Edits deliberately
change file size: the existing mtime-plus-size same-size/timestamp limitation is unchanged.

`tests/loops_native.py` executes these cases, plus later-iteration body/test conversions
and post-loop range checks. Mixed-flavor files prove cross-unit reference predicates
and do-while returns. The CTest native proof has a 60-second timeout; bounded mutation
fixtures terminate even if a missing check incorrectly allows execution to continue.
Test-only C++ exception observation does not enable source exceptions.

## Native timings

Final proof on 2026-11-04: 19 executables passed with exact output and exit status.
`-O2 -g0`, libc++, mold, Ninja `-j8`, no PCH/LTO; prebuilt runtime archive and fresh
instrumented app objects. PHP fixture generation, including incremental checks,
took 132.148 ms. These are single correctness runs, not benchmark averages. An earlier
13-case run varied substantially (first C++ build 579 ms); no speedup/regression claim
is made. Frontend/backend traces are nested/cumulative and do not add to build wall time.
Raw scripts/artifacts/results remain under `v0.2/build/loops-proof-2026-11-04-final/`.

| Program | Native wall ms | Frontend ms | LLVM backend ms | Optimizer ms | Codegen ms | Link ms |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| cpp | 149.742 | 14.772 | 21.962 | 11.073 | 8.830 | 60.000 |
| cpp_while_backedge | 161.223 | 16.185 | 8.326 | 4.020 | 2.916 | 94.000 |
| cpp_do_backedge | 172.018 | 23.590 | 22.418 | 13.124 | 7.233 | 71.000 |
| cpp_while_test | 177.378 | 15.352 | 10.500 | 3.977 | 4.795 | 101.000 |
| cpp_do_test | 175.018 | 20.197 | 19.177 | 9.270 | 7.535 | 76.000 |
| cpp_join | 203.758 | 17.545 | 10.547 | 4.784 | 4.061 | 117.000 |
| php | 241.931 | 32.646 | 43.665 | 20.652 | 20.655 | 97.000 |
| php_while_backedge | 215.661 | 23.065 | 11.009 | 4.246 | 4.945 | 90.000 |
| php_do_backedge | 174.065 | 23.420 | 17.987 | 8.365 | 7.606 | 76.000 |
| php_while_test | 194.833 | 25.866 | 20.652 | 9.618 | 8.418 | 84.000 |
| php_do_test | 159.420 | 16.649 | 10.817 | 4.575 | 4.582 | 78.000 |
| php_join | 166.058 | 21.938 | 9.017 | 3.515 | 3.882 | 78.000 |
| js | 188.411 | 23.445 | 25.545 | 12.558 | 10.760 | 87.000 |
| js_while_backedge | 184.435 | 20.106 | 12.160 | 5.132 | 5.012 | 85.000 |
| js_do_backedge | 202.420 | 30.325 | 22.904 | 11.929 | 8.516 | 84.000 |
| js_while_test | 180.835 | 21.210 | 19.595 | 10.828 | 6.253 | 81.000 |
| js_do_test | 179.313 | 24.519 | 16.623 | 6.198 | 8.221 | 75.000 |
| js_join | 131.668 | 16.124 | 7.552 | 3.002 | 3.008 | 62.000 |
| multifile | 144.562 | 15.762 | 27.913 | 10.718 | 9.343 | 69.000 |

### Requested timing recheck

A fresh rerun with unchanged code/settings passed all 19 executables. PHP-flavor
native build wall time fell from 241.931 ms to 132.152 ms. This indicates run-to-run
variation, not a measured compiler change; its underlying cause was not established.
PHP fixture generation took 112.011 ms. Artifacts and raw results are retained under
`v0.2/build/loops-proof-2026-11-04-rerun/`. Same trace-overlap caveat applies.

| Program | Native wall ms | Frontend ms | LLVM backend ms | Optimizer ms | Codegen ms | Link ms |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| cpp | 158.336 | 16.829 | 20.287 | 9.910 | 8.494 | 72.000 |
| php | 132.152 | 12.445 | 17.980 | 9.291 | 7.206 | 58.000 |
| js | 159.523 | 17.176 | 23.968 | 11.933 | 9.808 | 66.000 |
| multifile | 121.742 | 13.572 | 21.952 | 9.249 | 6.413 | 59.000 |
