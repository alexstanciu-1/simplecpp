# Record and array materialization proof
Doc Status: derived

27 integrated outcomes pass PHP/native against clean immutable
`9b4b33f35f053b487e018c94d6a4a7888d77c64a`. Covers canonical array reuse, nested
records, field identity/writability, resource path propagation, rejection of owning
arrays, exact native measurement retention and malformed record inputs.

```
python3 compiler/tests/record_materialization/run.py --results /tmp/scpp-records-native-02 --target-checkout /tmp/scpp-json-240-probe
python3 tools/php_portability/validate.py --results /tmp/scpp-records-final-fast-01
```

Both native builds passed; the second expands the first 15 outcomes to 27. Before
PHP-ready, identifier validation was corrected to use integer byte values. No native
source/converter/target correction. Retained algorithms were inspected, not executed
as an oracle. Private candidates may be mutated before rejection and must be discarded.
Full concrete type resolution and typed storage-provider integration remain pending.
See summary.json for exact hashes/commands, timing.json and cycles.json for effort.

Authoring to PHP-ready: 119.058s. PHP-ready to observed final native-ready: 123.550s (includes expanded coverage). Total through consolidation: 309.094s.
