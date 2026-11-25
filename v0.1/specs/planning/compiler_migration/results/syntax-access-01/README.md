# Syntax access and comparison checkpoint
Doc Status: derived

Five files complete read-only role views, metaprogramming views, lazy member traversal
and logical comparison over the rewritten parser arena. Eleven role views and the
comparison frame use scalar value structs. This reuses existing converter support;
no framework or target modification was required. Project parser selection/join/reuse
and semantic stages remain pending. src-runtime-preparation was not changed.

## Proof

Exact clean target: `9b4b33f35f053b487e018c94d6a4a7888d77c64a`, checked before and
after native validation. Both executions pass 134 outcomes: role queries over 35
accepted parser-corpus inputs, 73 independently specified comparisons also checked
against the retained comparer, and 26 independent malformed-role/cursor/boundary
checks. Comparisons include changed IDs/positions, root-sibling exclusion, changed
spelling and absence; views compare referenced node kinds/spans rather than IDs.

The role corpus traverses the retained deep/wide file-parser inputs. Original complete
semantic/session unit bodies are not claimed to pass. The tests exercise independent
returned views and cursor terminal/stable-read behavior. Memory/performance savings
are not inferred from PHP execution or the choice of value-struct representation.

```sh
python3 compiler/tests/syntax_access/run.py --results /tmp/scpp-syntax-access-php-01
python3 compiler/tests/syntax_access/run.py --results /tmp/scpp-syntax-access-native-01 --target-checkout /tmp/scpp-json-240-probe
python3 tools/php_portability/validate.py --results /tmp/scpp-syntax-access-final-fast-01
```

All commands passed. The final cumulative fast proof covers 30 registered files;
native evidence comes from this component checkpoint and earlier unchanged-stage
proofs. Use fresh result directories to reproduce. Saved summaries contain commands,
timings and source hashes; repetitive full output trees are represented by hashes and
can be regenerated with the committed corpus/oracle. Diagnostic log trailing whitespace
is trimmed. Source hashes at PHP-ready and native-ready are identical.

## PHP-ready versus native-ready

- Authoring/PHP stabilization: **232.246 seconds (3m52s)**.
- Subsequent conversion/native stabilization: **80.283 seconds (1m20s)**.
- One native build, one native behavior run, **zero corrective cycles**.

The PHP-ready marker was recorded immediately after the dedicated PHP-ready runner
passed; that suite also includes cheap checker/conversion/reuse gates. The native-ready
marker was recorded immediately after the native runner passed. Both record the source
hashes and exact proof command. Initial orientation is excluded. These are elapsed
wall times including tool/model waits, not isolated active typing or CPU time.

Native-run command costs: PHP behavior 2.238 s, conversion 0.464 s, unchanged reuse
0.072 s, build 46.115 s, native behavior 0.576 s. The 1m20s stabilization interval also
includes the rerun of PHP/oracle/checks and launch overhead. No source rewrite was
needed after PHP-ready. This slice gives no evidence of excessive conversion debugging;
compilation is the largest measured tool cost. These observations are not a benchmark
comparison with the smaller previous build closure.

Regression and consolidation milestones are separate in timing.json. Documentation
work overlapped the cumulative PHP regression run; those intervals cannot be treated
as exclusive effort allocation. Raw timestamps and source checkpoint hashes are kept
rather than replacing them with estimates. Final commit time is outside the recorded
finish boundary. See cycles.json and provenance.json for the remaining accounting.
