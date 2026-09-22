# Accepted definition view proof
Doc Status: derived

14 PHP/native outcomes pass against clean immutable
`9b4b33f35f053b487e018c94d6a4a7888d77c64a`: provider precedence, namespace separation,
entry/catalog metadata identity, source fallback, missing versus incomplete definitions,
and candidate isolation. First native build passed, no corrective cycle.

```
python3 compiler/tests/definition_view/run.py --results /tmp/scpp-view-native-01 --target-checkout /tmp/scpp-json-240-probe
python3 tools/php_portability/validate.py --results /tmp/scpp-view-final-fast-01
```

The retained view implementation was inspected; no direct retained oracle was executed.
The view relies on accepted stores remaining read-only. Annotation/instance resolution
is not complete. Exact source hashes, revision and commands are in summary.json;
wall-clock milestones and corrective counts are in timing.json and cycles.json.

Authoring to PHP-ready: 11.422s. PHP-ready to observed native-ready: 97.041s (includes dependency review). Total through consolidation: 169.614s.
