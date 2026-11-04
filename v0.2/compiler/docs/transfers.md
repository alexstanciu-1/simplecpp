# Bare loop transfers

Doc Status: supporting

The [loop contract](../../../specs/loop_statements_v0_2.md) defines bare `break;` and
`continue;` for all three flavors. Both target the nearest loop in the current
function. Break exits without another test; continue reaches the next test,
including do-while's trailing test. Numbered/labelled exits, switch, foreach
and unbraced loops remain deferred.
For the bounded `for` loop, continue reaches the update before the next test; see [for loops](for.md).

## Owners and preparation

`ast_break_statement` and `ast_continue_statement` are leaf statements retaining
only inherited token positions/parent ownership. Collection adds no symbols;
inspection exposes their kind and syntax span. Lowering emits direct C++ statements.
There are no added runtime APIs, generated handlers or sequencing lambdas.

Preparation owns invocation-local loop depth, reset for each function and restored
on both success and failure. A small local refactor replaces its return-only boolean
with uint8 completion flags: fallthrough, return, break and continue. Constants live
in `statement_completion`; no instances, AST caches or separate prepared records
are allocated. Sequences compose only the fallthrough paths, alternatives union
their possible exits, and each loop consumes its own transfers. A while retains a
possible zero-iteration exit; a do-while proves return only when its body exclusively
returns. Existing unreachable-after-return rejection extends to unconditional
transfers. All reachable syntax and non-pruned branches/tests are checked once.

Value barriers on branches and loops remain unchanged. No fixed point, additional
traversal, branch-map copy or callee-body scan is introduced. The counter-based
nearest-loop validation is sufficient while native output preserves the same loop
nesting. Revisit target identity before switch, labelled exits, finally, or control
flow transformations that alter that nesting.

## Donor review

Reviewed `compiler/my-try/04_analyze/prepare/semantics/{loops,transfers,completion}.php`
and its control-flow catalog: nearest targets, do-continue tests and composition of
distinct exit kinds are useful concepts. Its weak target facts, per-exit local maps,
switch/finally handling and prepared transfer records are not adopted. Current
v0.2 needs only the transient loop context and flags described above.

## Proofs

`tests/transfers.php` covers all flavors, nested loops/conditionals, continue test
execution, break test suppression, mixed transfer/return completion, rejected
outside-loop/numbered/labelled transfers and unreachable trailing statements.
It checks exact visit counts, inspection, emission purity, reset/reprepare and
incremental callee-signature rejection/recovery with caller AST/token reuse.
Caller edits between continue/break/outside-loop/error recovery retain the unchanged
callee declaration; no-op reuse is verified. `tests/transfers_native.py` executes
the behavioral cases and bounded next-iteration range failures. Existing loop
native proofs remain regression coverage for value barriers and cross-file calls.

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
