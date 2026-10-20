# Accounting: remaining gap from the handmade concrete experiment
Doc Status: planning

Date: 2026-10-19. Current revision `0da6b327`. New profiling/probes are **O0 only**.
User direction: defer further O2 measurement until optimization work is complete.

## Comparable baseline

The relevant baseline is the whole-accounting handmade concrete C++ conversion,
not the earlier C money slice. Its [initial measurements](../accounting_template_free_2026_10_18/RESULTS.md)
reported 2.677 s application compilation and 3.518 s application/support/link total
with debug enabled. The later [no-debug probe](../accounting_clang_profile_2026_10_18/README.md)
reported median application compilation of 2.472 s, and all application/support
compilation of 3.017 s. Current application compilation is 10.034 s without debug:
roughly 4.1 times the historical no-debug application cost.

This is a genuine gap from the experimental target. It is not a measured regression
of the same compiler implementation: the earlier converter operated on legacy S2S
C++ and the current implementation uses my-try's different prepared emitter.
Both execute all 998 accounting checks. Full runtime equivalence was not claimed
for the handmade representation, which used custom strings, containers, shared-owner
control blocks and reduced diagnostic/ABI support. Current prelowering preserves the
production runtime implementations behind concrete interfaces.

Do not compare today's 49.51 s first native build directly to the historical 3.518 s:
today's total includes runtime provider compilation, PCH creation, layout probes and
build validation. The old concrete experiment needed no runtime PCH or original
runtime library compilation; its own bounded support compilation was included.
These scope differences do not explain away the application-only 2.47 versus 10.03 gap.

## Current Clang work

All 16 prepared app units were profiled at O0 with the same flags/layout headers/PCH
as the latest benchmark, adding `-ftime-trace -ftime-trace-granularity=100`.
Three ordinary units (ledger, money, checks) provide a current reference.

Across prepared app units, Clang reports 9.815 s ExecuteCompiler, 7.217 s Frontend
and 2.389 s Backend. Diagnostic totals include 2.222 s InstantiateClass, 2.812 s
InstantiateFunction, 2.604 s PerformPendingInstantiations, 1.728 s Source and
0.966 s CodeGen Function. These categories overlap; **do not add them** or assign
exclusive percentages from their sum. These are instrumented single-run diagnostics,
not replacements for the repeated uninstrumented benchmark.

The remaining work is visible in specific traces:

- Ledger still instantiates `scpp::shared_p<record_AccountingCheck>` and runtime
  dynamic packed-hash operations (`set`, `insert_or_assign_int`, `read_or_default`),
  with std::vector/std::variant internals. Prepared vector/hash names alone do not
  eliminate templates brought in by ordinary operation headers.
- Checks and entry spend about 0.30 s each processing `php_string.hpp`, including
  strings/source module headers, source-buffer classes and Result/std::variant
  machinery. This is broad runtime-header exposure in app units.
- The recorded ReadAST aggregate is 0.034 s. It does not isolate every lazy PCH cost;
  it is not evidence that copying/loading the PCH explains the several-second gap.

## Header-only probe

For each app unit, retain exactly its emitted include lines and remove all function
bodies. Compile this temporary probe with the same O0/PCH/layout configuration and
trace flags. No production or published app files are modified.

Across 16 probes, ExecuteCompiler is 4.383 s, Frontend 4.112 s and Backend 0.089 s.
This is already greater than the historical handmade full-app compilation time.
It demonstrates significant declaration/header exposure before app body work.
It is not an exact removable-cost estimate: dropping bodies changes instantiation
and code-generation demand. The 4.383 s is traced compiler time, not harness wall time.

## Generated body differences

Handmade app text: 15 units, 157,738 bytes, 2,939 lines and three `[&]` occurrences.
Current app text: 16 units, 725,357 bytes, 7,974 lines and 1,557 `[&]` occurrences.
These counts exclude headers and support units. Long generated symbols contribute
to byte growth; byte/lambda counts alone cannot attribute compilation seconds.

The handmade integer expression can use a direct concrete operator. Current emission
often creates a sequencing lambda, materializes operand snapshots, obtains addresses
and calls a concrete runtime-operation bridge. Prepared sequencing/lifetime facts
must remain authoritative: do not remove snapshots or lambdas indiscriminately to
imitate the old text. This is a separate measurable optimization opportunity after
runtime-header exposure is reduced.

## Priorities

1. Narrow app-facing operation declarations and stop exposing full runtime
   implementation headers when concrete operation interfaces suffice. Existing
   backend header-dependency and runtime-operation binding owners should own this.
   For example, nonopaque fallback in `CPP_Concrete_Index::reference` can select
   `keyed_path.hpp`, while ordinary output pulls `php_string.hpp`.
2. Complete concrete source ownership/factory interfaces, preserving production
   identity/lifetime behavior. Native shared wrappers remain a known exposure.
3. Measure residual body cost and use prepared facts to simplify only provably
   redundant expression sequencing. No additional AST or semantics duplication.
4. Keep O0 as the measurement target until these optimizations are complete.

The immediate target is the handmade experiment's narrow native compilation boundary,
not wholesale adoption of its replacement runtime. No production implementation
changes were made in this assessment.

## Evidence

[Structured trace summary and top events](summary.json). Local raw traces and exact
commands: `/tmp/accounting-current-profile/`, including `run.py`, `headers.py`,
`logs/` and `header-logs/`. Both probes use the shared runner with one sequential task.
The full profile passed in 15.56 s and the header-only probe in 5.17 s. No O2 commands,
application edits or native compiler rebuild were run. Historical inputs and timings
remain in their original evidence directories rather than being relabeled as current.
