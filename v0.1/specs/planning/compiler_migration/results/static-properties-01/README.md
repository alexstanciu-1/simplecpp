# Static field conversion evidence
Doc Status: derived

Target: 9b4b33f35f053b487e018c94d6a4a7888d77c64a, clean checkout at
/tmp/scpp-json-240-probe. Native profile strict; clang++-18. No target edits.

Three native build attempts, first pass on attempt 3. Attempts 1 and 2 overlapped:
both exposed a fixture field/method naming collision and the static empty-hash
capturing-lambda target defect. One corrective cycle renamed the fixture method
and lowered static empty hashes through native default construction. No separate
verification build after the passing build. Additional checker/map/method tests
are regression verification, not additional native builds.

All attempts reached a passing PHP behavioral checkpoint before native compilation.
Command: php -r 'foreach (array_slice($argv,1) as $path) { require $path; }'
followed by tools/php_portability/runtime/bootstrap.php and the fixture files in
model.php, worker.php, main.php order. First checkpoint source SHA-256 values:

- model.php: `89da8406e4ee438639ed9245ed70261b122d6ed0792307c1b042fe355a3bba9f`
- worker.php: `a19384b6d03b4cd81a5252789be05d13496515ae1bbd32d9b98f048d790827bb`
- main.php: `0cbf76e7bf649b18a43ab2c4b7f2cb97b1028afda4bb52e0af46fe3b0a95f85c`

Elapsed command times (seconds, includes failed commands):

| Attempt | PHP/check/conversion/setup and rejection checks | Native build/run | Outcome |
| --- | ---: | ---: | --- |
| 1 | 2.457 | 29.880 | failed |
| 2 | 2.396 | 25.446 | failed |
| 3 | 2.406 | 31.016 | passed |

Authoring wall time was not separately measured. Command durations are not a claim
about total authoring effort. Native stdout on the passing attempt ended with:

```text
2:2:1:loaded:yes
9:same:ready
present:removed
0:empty:9:1:idle
1:0
```

Reproduce with tests/portability/static_properties.py --results FRESH
--target-checkout /tmp/scpp-json-240-probe. The runner saves command logs, fixture
sources, first PHP checkpoint hashes and summary. Converter foundation/checker,
method-signature and map-iteration regressions were also run. No compiler-ready
files were added; required fields and Storage bindings remain separate blockers.
