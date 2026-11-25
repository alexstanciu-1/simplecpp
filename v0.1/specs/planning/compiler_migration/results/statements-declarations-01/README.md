# Statements and declarations checkpoint
Doc Status: derived

The single File_Parser owner now parses the prototype's file grammar: ordered entry
statements, blocks/control flow, functions/parameters, structs/methods, constants and
template/evaluation wrappers. No new compiler language feature was introduced.
Parse_Result replaces the expression-only result name and owns file root/entry/
definition indexes alongside the existing source snapshot and syntax arena.

## Results

All proofs passed on exact clean native target
`9b4b33f35f053b487e018c94d6a4a7888d77c64a` at `/tmp/scpp-json-240-probe`:

- File grammar: 82 PHP/native cases (35 accepted, 47 rejected), with canonical
  tree/span comparisons, reachability, span containment, definition-index consistency
  and no partial syntax on failure. The corpus harvests 35 literal/nowdoc inputs from
  four retained parser units while scanning five. This reuses inputs, not the whole
  original semantic/session unit harness.
- Nine host lifecycle assertions: 2,000 sequential expressions retain two continuation
  slots and return to depth zero; exact input snapshot identity/purity, independent
  arenas, determinism, rejected publication and repair are checked.
- Existing expression suite: all 132 PHP/native outcomes pass after the shared result
  change and root-continuation lifetime fix.
- Cumulative PHP/tool validation: 25 registered production files, all checks passed.
  This final fast command does not itself run native builds; the checkpoints above do.

```sh
python3 compiler/tests/statements/run.py --results /tmp/scpp-statements-php-01
python3 compiler/tests/statements/run.py --results /tmp/scpp-statements-native-01 --target-checkout /tmp/scpp-json-240-probe
python3 compiler/tests/statements/run.py --results /tmp/scpp-statements-native-02 --target-checkout /tmp/scpp-json-240-probe
python3 compiler/tests/expressions/run.py --results /tmp/scpp-parser-expressions-native-01 --target-checkout /tmp/scpp-json-240-probe
python3 tools/php_portability/validate.py --results /tmp/scpp-statements-final-fast-01
```

The first native command failed as recorded below; all other listed runs passed.
Use fresh result directories when reproducing. Summaries include exact commands,
source hashes and per-command timings. Repetitive full-tree outputs are represented
by hashes; regenerate them through the committed test corpus/oracle. Retained input
provenance includes original file/line locations. Saved diagnostic logs have trailing
whitespace trimmed; original logs remain in the stated result directories.

## Effort and corrective cycles

Two file-grammar native build attempts, one corrective cycle, one native behavior
attempt and zero behavior mismatches. Attempt 1 compiled for 33.037 s before rejecting
`result->definitions = []`: chained typed container assignment requires an explicit
typed local under the current S2S metadata contract. The source now creates an empty
vector<int> local and assigns it. This was a missed authoring requirement already in
the strict skill, not new v0.1 debt or a converter feature request.

Successful attempt 2: lifecycle proof 0.812 s, reference oracle 0.209 s, checker
0.511 s, PHP execution 0.576 s, conversion 0.242 s, unchanged reuse 0.054 s,
native build 30.828 s, native execution 0.140 s. Expression regression used one
additional native build with no corrections. These are observed wall timings, not
benchmarks; the regression build overlapped the cheap cumulative PHP checks.

`timing.json` records primary-activity elapsed time (including tools/waits), with
initial orientation excluded. `cycles.json` separates first-pass attempts, native
behavior and regression verification. `provenance.json` records touched parser/test
and retained-unit hashes. No target source or generated output was patched.

## Remaining scope

Syntax role access/comparison and project-wide parser selection/join/reuse remain
next. Semantic stages and the compiler CLI are not implemented. Recursive statement
nesting retains the prototype design; 128-level cases are proved, not unbounded depth.
Frame-slot storage is bounded by expression nesting, but frame objects are still
allocated on reuse; this is recorded for later measured optimization.
`src-runtime-preparation` and preserved reference code remain unchanged.

## PHP-ready versus native-ready timing

At the user's request, the existing activity measurements are grouped as follows:

| Development phase | Approximate elapsed time |
| --- | ---: |
| Convertible-PHP authoring and PHP stabilization | 4m17s |
| Conversion/native stabilization after the PHP checkpoint | 2m35s |
| Regression verification | 1m45s |
| Consolidation | 1m07s |

Total recorded wall time: 9m45s. These are reconstructed primary-activity buckets,
not exact first-pass latency: the timer recorded phase transitions rather than the
first passing PHP/native timestamps. The PHP-proof phase included lifecycle-test
preparation after the initial 82-case PHP pass; documentation overlapped native work.
Initial orientation and the final commit are excluded. Raw phases remain unchanged
in timing.json; checkpoint_accounting records the approximation and evidence links.
Future slices record the actual first passing PHP command and source snapshot at
that milestone, following the [timing contract](../../../../portability/validation_workflow.md#migration-timing-checkpoints).
