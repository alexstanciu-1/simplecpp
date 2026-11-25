# Aggregate lifecycle proof
Doc Status: derived

36 outcomes pass in PHP and native against immutable
`9b4b33f35f053b487e018c94d6a4a7888d77c64a`. Eight supplementary host checks cover
input purity and invalid identities/member definitions. Scalar-catalog regression:
116 PHP outcomes still pass after widening the shared definition model.

Commands:

```
python3 compiler/tests/aggregate_lifecycles/run.py --results /tmp/scpp-aggregate-native-02 --target-checkout /tmp/scpp-json-240-probe
python3 compiler/tests/aggregate_lifecycles/run.py --results /tmp/scpp-aggregate-final-php-01
python3 tools/php_portability/validate.py --results /tmp/scpp-aggregate-final-fast-01
```

Two native commands: first rejected tightly spaced generic assignment in the fixture
before C++ compilation; second passed. Production composition needed no native correction.
The host-only checks were added afterward without changing the compiled sources.
`summary.json` records tested source hashes, revision and command durations; `timing.json`
records milestone times. The retained algorithm was inspected, not executed as an oracle
in this slice. Normalized record/resource/layout producers remain future work.

Authoring through PHP-ready: **352.230s**.
PHP-ready through observed native-ready: **118.601s**
(includes fixture correction and supplementary review). Total through consolidation:
**583.003s**.
