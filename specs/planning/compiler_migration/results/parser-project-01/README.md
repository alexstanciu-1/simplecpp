# Project parser reuse and joining checkpoint
Doc Status: derived

Four files implement parser membership storage, fixed selection, atomic segmented
joining and sequential composition. Current snapshots are keyed by supplied paths,
not invented persistent IDs. Unchanged bytes share syntax; fresh token snapshots get
new result wrappers rather than mutating retained results. Removed paths are absent,
current order/entry are preserved, and failed parsing publishes diagnostics without
partial file membership. The prototype responsibilities and update guarantees are
preserved while its unmigrated Source_Set/Step interface model is not copied back.

## Results

Exact clean native target: `9b4b33f35f053b487e018c94d6a4a7888d77c64a`.
66 independent PHP/native checks pass for cold/warm/full updates, byte-stable reuse,
rebinding, changed/added/removed/reordered paths, worker identity, segmented and
out-of-order joins, atomic rejection, invalid ranges/plans, stale/unselected results,
lexical/syntax failures and repair. Nine host serialization assertions pass for
snapshot purity and rejected-segment accumulator stability.

Tests adapt the guarantees of retained parse_updates.php and step_lifecycle.php;
they do not claim to execute the old full compiler session or generic Step lifecycle.
The current sequential entry and explicit independent workers share the same plan
and join. No multithreaded execution proof is claimed.

```sh
python3 compiler/tests/parser_project/run.py --results /tmp/scpp-parser-project-php-01
python3 compiler/tests/parser_project/run.py --results /tmp/scpp-parser-project-php-02
python3 compiler/tests/parser_project/run.py --results /tmp/scpp-parser-project-native-01 --target-checkout /tmp/scpp-json-240-probe
python3 tools/php_portability/validate.py --results /tmp/scpp-parser-project-final-fast-01
```

The first PHP run failed one expected-offset assertion (the fixture's EOF is byte
32, not 31). Subsequent runs passed. A preceding checker correction moved the unsupported
negative property default into constructor assignment. Both belong to PHP authoring,
before the PHP-ready milestone. Cumulative PHP/tool validation passes with 34 active
production files. Current native evidence combines this component checkpoint with
earlier unchanged-stage proofs; the final fast run itself does not request native.
Use fresh directories when reproducing. Saved summaries contain exact commands,
per-command timings, source hashes and target revision; logs are saved with trailing
whitespace trimmed. Source/retained-test provenance is recorded separately.

## Timing and corrective cycles

- Authoring through the passing PHP-ready suite: **250.162 s (4m10s)**.
- PHP-ready through the passing native-ready suite: **91.727 s (1m32s)**.
- One post-PHP source-review correction: range rejection now precedes subtraction,
  avoiding dependence on short-circuit behavior for invalid negative indices.
- **One native build, one native behavior run, zero native-failure corrective cycles.**

PHP-ready and native-ready milestones were recorded immediately after successful
runner commands, retaining their source hashes. Only join.php changed between them.
The PHP-ready suite includes cheap checker/conversion/reuse gates. No converter,
framework or target implementation was changed. Initial orientation and final commit
are outside the recorded interval; documentation overlapped native and regression
validation, so these are elapsed milestone latencies, not isolated active coding time.

Native-ready command costs: host purity 0.077 s, PHP portable proof 0.082 s, converter
0.424 s, unchanged reuse 0.068 s, native build **46.222 s**, native behavior 0.011 s.
These are observations, not benchmarks. Compilation dominates the tool costs; this
component required no diagnosis/fix cycle after a native failure. Keep the separate
source-review correction visible rather than claiming identical PHP/native sources.
Raw timestamps, regression/consolidation milestones and interval totals are in
timing.json; cycles.json distinguishes authoring, review and native corrections.

## Remaining work

This is parser-level reuse, not incremental discovery/tokenization: those stages
still produce fresh snapshots. Byte comparison and repeated boundary path indexing
are recorded optimization candidates if later measurements justify changing their
owners. Next are declaration collection and symbol records in 04_analyze. The general
compiler session/CLI and later semantic stages remain unimplemented in the rewrite.
`src-runtime-preparation` and retained reference files remain unchanged.
