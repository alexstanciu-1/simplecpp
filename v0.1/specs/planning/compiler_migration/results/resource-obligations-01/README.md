# Resource obligations proof
Doc Status: derived

39 outcomes pass PHP/native against clean immutable
`9b4b33f35f053b487e018c94d6a4a7888d77c64a`. 90 host allocation-effect combinations
agree with the retained prototype. Covers effect codecs/positions, path snapshot
ownership, uniqueness and allocation/aggregate storage-copy-assignment rejection.

```
python3 compiler/tests/resource_obligations/run.py --results /tmp/scpp-resources-native-02 --target-checkout /tmp/scpp-json-240-probe
python3 compiler/tests/scalar_catalog/run.py --results /tmp/scpp-resources-scalar-native-01 --target-checkout /tmp/scpp-json-240-probe
python3 tools/php_portability/validate.py --results /tmp/scpp-resources-final-fast-01
```

First native build ran but rejection tests failed: standalone new expressions were
omitted from generated C++. Assignment to a local fixed the fixture; the second build
passed. No production native correction. Exact target, hashes and command times are in
summary.json; timing.json and cycles.json record effort/corrections. Typed storage-family
bindings and normalized record/array producers remain pending.

Authoring to PHP-ready: 70.676s. PHP-ready to observed native-ready: 139.666s (includes oracle work, fixture correction and native rerun). Total through consolidation: 270.641s.
