# Integer literals and instance contexts
Doc Status: derived

35 PHP/native outcomes pass against clean immutable
`9b4b33f35f053b487e018c94d6a4a7888d77c64a`. Covers exact 128-bit literal boundaries,
malformed decimal rejection, context provenance/identity bounds, nullable typed arguments,
argument snapshot ownership, receiver restrictions and malformed context rejection.
200 host cases independently generated with Python big integers cover widths 1..1024.
The host matrix was added after native execution without changing compiled source/probe.

```
python3 compiler/tests/instance_contexts/run.py --results /tmp/scpp-context-native-02 --target-checkout /tmp/scpp-json-240-probe
python3 compiler/tests/instance_contexts/run.py --results /tmp/scpp-context-final-php-01
python3 tools/php_portability/validate.py --results /tmp/scpp-context-final-fast-01
```

One checker correction (binary NUL source literal) and one PHP fixture correction
(template-member kind) preceded PHP-ready. An early target runner invocation stopped
at the same PHP error; only one actual native build occurred and passed. No native
source correction. The decimal algorithm is retained; this slice uses independent
expected boundaries rather than executing the original literal/instance classes.
Instance identity allocation, bindings and application preparation remain pending.
See summary.json for hashes/commands and timing.json/cycles.json for measurements.

Authoring to PHP-ready: 172.348s. PHP-ready to observed native-ready: 126.018s (includes host boundary matrix). Total through consolidation: 383.129s.
