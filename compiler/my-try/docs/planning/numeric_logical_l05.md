# V-L05: numeric logical admission
Doc Status: planning

Completed and verified on 2026-10-19. No remaining blockers in the approved scope.
This report records implementation evidence; top-level specs remain semantic authority.

## Implemented scope and ownership

Canonical int/float unary `!`; AND/OR for int/int, float/float and int/float in
both orders, including `&&`, `||`, `and` and `or`. Results are bool. Word aliases
retain their distinct source precedence. The aliases and mixed numeric pairs
explicitly extend the original six audited V-L05 cells; historical counts remain intact.

`Operator_Preparation` now admits the bounded numeric domain and uses existing
`condition_not`, `condition_and` and `condition_or` decisions. Each operand gets its
own `Condition_Truthiness` decision. Existing emission converts to native bool and
uses native lazy operators, completing LHS truth conversion before reaching RHS.
No emitter or runtime implementation change was needed. The obsolete unary-int
rejection fixture was replaced by positive coverage, and the logical rejection
diagnostic now describes the admitted numeric domain.

Numeric xor, bool/numeric pairs, fixed-width expansion, wrappers/lifting and V-L08
remain separate. No full matrix rerun or native compiler rebuild was performed.

## Acceptance evidence

The permanent runner is `compiler/my-try/tests/numeric_logical.py`, with preparation
and generated native proofs in `numeric_logical.php` beside it.

| Requirement | Passing proof |
| --- | --- |
| Values and exact bool types | Two unary types; four numeric pairs and four binary spellings; zero/nonzero, signed zero, infinity and NaN |
| Short-circuiting and order | 1,600 value/error combinations; independent call counters and traces 1 for skipped RHS, 12 for reached RHS |
| Exceptions and earlier effects | Skipped/reached throwing RHS and throwing LHS; exact `division_by_zero` code and recorded effects |
| LHS truth snapshot | RHS mutates LHS storage but returns an independently chosen value, distinguishing saved truth from a later re-read; int and float |
| Composition and precedence | Nested traces, word aliases with assignment, and grouped assignment |
| Static validation of skipped RHS | Missing names, wrong arity and inadmissible operand types still reject during preparation |
| Domain boundaries | Negative controls for numeric xor, bool/numeric pairs, fixed-width types and nullable/Result binary operands |
| Preparation lifecycle | Cleanup, repeated failure, repair, no-op retry and fresh-output equivalence; rejected preparation leaves no artifacts |

## dev002 verification

All executable checks ran in `/home/alex-ai/numeric-l05/20261019-01` on
`alex-ai@dev002.travelfuse.ro`, using shared runners. Peak requested concurrency
was 25, below the approved 32-worker limit. No executable tests ran on WSL.
Toolchain: PHP 8.5.11, Clang 18.1.3 and Python 3.12.3.

Commands below ran from the snapshot root, with its recorded PHP toolchain on PATH.

```sh
python3 compiler/my-try/tests/numeric_logical.py --results verification/focused-final
python3 compiler/my-try/tests/mixed.py --results verification/mixed
python3 compiler/my-try/tests/control_flow.py --results verification/control-flow
python3 compiler/my-try/tests/scalar_admission.py --results verification/scalar --jobs 12
```

| Suite | Result |
| --- | --- |
| Numeric logical focused proof | Pass; final run 6.61 seconds |
| Existing mixed/boxed regressions | Pass; 15 PHP suites, 324 native programs |
| Existing control-flow regressions | Pass; 12 PHP suites, 135 native programs |
| Existing scalar admission/sequencing | Pass; 10 PHP suites, 7 native programs |
| Supplemental existing bool logical fixtures | Pass; 11 native fixtures |

The supplemental harness uses the shared runner and fixture compiler; its exact
executed source is archived under `verification/bool-logical/executed-harness.py`.
All recorded command exits match their expected outcomes. The regression suites
precede only the final rejection-diagnostic wording amendment; `focused-final`
rechecks the final compiler and strengthened truth-snapshot assertions.

## Provenance and remaining work

- [Results](numeric_logical_l05_results.json): suite outcomes, toolchain, scope and evidence checksum.
- [Evidence archive](numeric_logical_l05_evidence.json.gz): exact commands, outputs, summaries and supplemental harness.
- [Final source manifest](numeric_logical_l05_source_final.json): tested source hashes and snapshot revision notes.
- [Starting baseline](numeric_logical_l05_baseline.json): pre-existing dirty work distinguished from this goal.
- [Completion audit](numeric_logical_l05_completion_audit.json): local code matches tested source and runtime matches the starting baseline.

Remote integrity verification checked 6,189 source files with no mismatches.
Only operator preparation changed in compiler implementation during this goal.
Pre-existing runtime changes were preserved. V-L05 is implemented and verified;
V-L08 and all excluded domains retain their separate status. No follow-on work
was started.
