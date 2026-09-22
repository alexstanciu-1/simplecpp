# Declaration lookup proof
Doc Status: derived

40 independent outcomes passed in PHP and strict native execution against clean
`9b4b33f35f053b487e018c94d6a4a7888d77c64a`. First native build succeeded; no native
correction cycle. The retained constructor oracle separately passed 15 numeric cases.

Commands:

```
python3 compiler/tests/name_lookup/run.py --results /tmp/scpp-lookup-native-01 --target-checkout /tmp/scpp-json-240-probe
php compiler/tests/name_lookup/oracle.php
python3 tools/php_portability/validate.py --results /tmp/scpp-lookup-final-fast-01
```

The oracle was added after launching native validation and does not change compiled
source. Final fast validation exercises the integrated runner including that oracle.
`timing.json` records wall-clock milestones; orientation preceded authoring-start.
`summary.json` records individual command durations and tested source hashes.
`cycles.json` records two pre-PHP-ready corrections and zero post-ready corrections.
This proves declaration lookup, not full lexical resolution or compiler execution.

Measured authoring to PHP-ready: 117.064s. PHP-ready to native-ready: 91.861s (includes proof/oracle work and waits). Total through consolidation: 268.264s.
