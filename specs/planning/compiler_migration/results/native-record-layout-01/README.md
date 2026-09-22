# Native record layout proof
Doc Status: derived

18 outcomes pass PHP/native against clean immutable
`9b4b33f35f053b487e018c94d6a4a7888d77c64a`; the first build passed.
441 host combinations of size/alignment/offsets match the retained constructor.
The oracle was added after the native run without changing production or native probe
sources. Its initial class-name collision was fixed by using separate PHP processes.

```
python3 compiler/tests/native_record_layout/run.py --results /tmp/scpp-native-layout-native-01 --target-checkout /tmp/scpp-json-240-probe
python3 compiler/tests/native_record_layout/run.py --results /tmp/scpp-native-layout-final-php-02
python3 tools/php_portability/validate.py --results /tmp/scpp-native-layout-final-fast-01
```

See summary.json for revision, source hashes and command durations, timing.json for
milestones, and cycles.json for corrective iterations. This proves the provider layout
contract, not full resource-aware record normalization or target layout generation.

PHP-ready: 48.326s; PHP-ready to observed native-ready: 84.182s (includes oracle work); total through consolidation: 165.251s.
