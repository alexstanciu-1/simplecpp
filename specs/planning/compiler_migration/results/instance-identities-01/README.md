# Instance identity allocation proof
Doc Status: derived

23 PHP/native checks pass against clean immutable
`9b4b33f35f053b487e018c94d6a4a7888d77c64a`; first native build passed. 60 direct calls
against the retained JSON-key allocator agree with the migrated length-framed key ledger.
Checks cover exact typed arguments, order, null/value distinction, retained reuse,
private candidates, lineage mismatch, watermark limits and exhaustion exception ancestry.

```
python3 compiler/tests/instance_identities/run.py --results /tmp/scpp-identities-native-01 --target-checkout /tmp/scpp-json-240-probe
python3 compiler/tests/instance_identities/run.py --results /tmp/scpp-identities-final-php-01
python3 tools/php_portability/validate.py --results /tmp/scpp-identities-final-fast-01
```

The checker exposed missing OverflowException mapping before PHP-ready; it was added to
the portability framework, with specific and parent catches proved natively. No target
change or native correction. The retained oracle was added afterward without changing
compiled source/probe. Instance registry permission retention/publication remains pending.
Summary contains source hashes and command durations; timing/cycles record effort.

Authoring to PHP-ready: 94.732s. PHP-ready to observed native-ready: 134.665s (includes retained oracle work). Total through consolidation: 311.143s.
