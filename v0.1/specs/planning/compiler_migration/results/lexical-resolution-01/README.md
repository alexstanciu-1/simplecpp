# Lexical/body resolution proof
Doc Status: derived

320 outcomes passed in PHP and strict native execution against clean
`9b4b33f35f053b487e018c94d6a4a7888d77c64a`. Coverage includes lexical ancestry,
read/write identities, initialization guards, parameter prefixes, constants,
member receivers, template argument roles, source byte diagnostics, 1,000 locals
and 128 nested scopes. 23 host checks cover input/result purity and invalid rows.
The retained generic-contract oracle supplies five permission expectations.

Two native build commands were needed. The first stopped at STAN before C++
generation; moving the index helper's return after its loop resolved the advisory.
The first actual C++ compile and native execution passed. No converter/target changes.

Commands:

```
python3 compiler/tests/lexical_resolution/run.py --results /tmp/scpp-lexical-native-02 --target-checkout /tmp/scpp-json-240-probe
python3 tools/php_portability/validate.py --results /tmp/scpp-lexical-final-fast-01
```

`summary.json` records command durations, exact target and tested source hashes.
`timing.json` records wall-clock milestones, including waits and consolidation.
`cycles.json` separates harness corrections from source/native corrections.
`provenance.json` identifies retained source/tests. The original compiler session
suites were not run: their local/template fixtures were adapted to direct workers.

This proves the worker and locally validated result model. Project selection,
structural completeness acceptance and dependency-aware reuse remain next.

Authoring through PHP-ready: **636.351s**. PHP-ready through native-ready: **176.034s** (includes host proof work, build waits and documentation). Total through consolidation: **890.967s**. Orientation preceded authoring-start.
