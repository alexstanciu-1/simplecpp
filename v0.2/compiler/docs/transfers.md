# Loop and switch transfers

Doc Status: supporting

The [loop contract](../../../specs/loop_statements_v0_2.md#exit-destinations) defines
eligible targets. In `foreach → switch`, `break 2;` exits the foreach.
A `continue 2;` there requires another enclosing loop. Suspended producers support
bare exits.

## Owners and preparation

The two transfer leaves retain the written depth in their existing token span and
the resolved destination in their existing non-owning target field. Bare exits
use the active loop or switch. Only a written depth needs a parent walk.
The preparation worker tracks reachable nonlocal exits by destination and direction
until each target consumes its own exits. Protected regions isolate these facts
so a terminating finally can cancel its incoming transfers. Constructor field states
join at the destination switch. All facts stay local to the body.

Lowering emits native break or continue for the nearest target. A nonlocal break
lands after its target. A nonlocal continue lands just beyond the loop body's local
scope, before the native update or test. The emitter creates labels only for jumps
it emits. When a jump crosses a finally, the protected region records the destination
in its existing exit discriminator and replays it after cleanup. An enclosing finally
can forward the same destination.

## Proofs

`tests/transfers.php` covers all flavors, nested loops/conditionals, continue test
execution, break test suppression, mixed transfer/return completion, rejected
outside-loop, invalid-depth and labelled transfers and unreachable trailing statements.
It checks exact visit counts, inspection, emission purity, reset/reprepare and
incremental callee-signature rejection/recovery with caller AST/token reuse.
Caller edits between continue/break/outside-loop/error recovery retain the unchanged
callee declaration; no-op reuse is verified. `tests/transfers_native.py` executes
the behavioral cases and bounded next-iteration range failures. Existing loop
native proofs remain regression coverage for value barriers and cross-file calls.

The native finally suite exercises two destinations through nested cleanup, numbered
continue with a for update, switch and foreach counting, throwing cleanup, and
resident callee edits, failed edits and recovery versus a fresh build. The switch
suite also checks readonly initialization through a break to an outer switch.
Run `php v0.2/compiler/tools/measure_transfer_preparation.php` to time repeated body
preparation after symbol and layout publication.

### Numbered exit preparation measurements

Local PHP with Xdebug off, pinned to one logical CPU. Five runs per compiler
revision alternated in order. Each figure is the median of the run medians, using
21 batches of 250 preparations of an already-collected function. The previous
revision is `d5f510482`. All non-exit statements in each nesting comparison match.

| Prepared body | Previous µs | Current µs |
| --- | ---: | ---: |
| Two loops, no exit | 7.98 | 8.06 |
| Two loops, bare break | 8.35 | 8.60 |
| Two loops, break 2 | — | 10.88 |
| Four loops, bare break / break 4 | 13.63 / — | 13.84 / 17.00 |
| Eight loops, bare break / break 8 | 24.32 / — | 24.59 / 29.67 |
| Bare break and continue through finally | 18.73 | 19.56 |
| Break and break 2 through finally | — | 22.38 |

## Verification results — 2026-11-04

46 compiler/generator/freshness checks passed, plus six transfer executables and
19 existing loop native regression executables. Style checks passed. PHP fixture
generation, including incremental checks, took 78.194 ms.

O2/g0, libc++, mold, Ninja8, no PCH/LTO, prebuilt runtime and fresh instrumented
application objects. These are single correctness builds, not repeated-run benchmarks.
Trace totals overlap and must not be added to wall time; optimizer/codegen are parts
of LLVM backend work.

| Fixture | Build ms | Frontend ms | LLVM backend ms | Optimizer ms | Codegen ms | Link ms |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| cpp | 122.501 | 7.452 | 9.874 | 4.376 | 4.115 | 61.000 |
| cpp_continue_range | 126.888 | 13.699 | 7.693 | 3.410 | 2.950 | 62.000 |
| php | 117.959 | 7.875 | 9.386 | 4.067 | 3.808 | 57.000 |
| php_continue_range | 122.229 | 13.756 | 7.491 | 3.384 | 2.714 | 54.000 |
| js | 118.957 | 7.703 | 8.902 | 3.816 | 3.740 | 57.000 |
| js_continue_range | 133.065 | 15.502 | 8.252 | 3.616 | 3.182 | 59.000 |

Raw transfer artifacts/results and regression summaries are retained locally in
`v0.2/build/transfers-proof-2026-11-04/` (ignored build output).
