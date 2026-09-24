# Nullable parameter conversion evidence
Doc Status: derived

Target: 9b4b33f35f053b487e018c94d6a4a7888d77c64a (clean
/tmp/scpp-json-240-probe), strict profile, clang++-18. No target or generated C++ edits.

Two native build attempts; first pass on attempt 2. Attempt 1 exposed unsupported
combined conversions: concrete class to nullable interface, and int to nullable
float. One fixture adaptation established each payload type explicitly before
nullable argument passing. No converter widening or native fix was needed for
that correction. No separate native verification build after the passing attempt.

First PHP behavior checkpoint command: php -r
'foreach (array_slice($argv,1) as $path) { require $path; }', followed by framework
bootstrap.php, model.php and main.php. Initial fixture source SHA-256 values:

- model.php: `d42a09d8d466af9286d57227a27b6375509299a61f16a6f60b294d704bbc94d1`
- main.php: `8bead504bd0a47e80a7ca4ed33bc3b83dcf93a2bf0366962f32ec7e6940230fb`

Command elapsed times (seconds):

| Attempt | PHP/check/conversion/setup/rejections | Native build/run | Outcome |
| --- | ---: | ---: | --- |
| 1 | 7.565 | 35.390 | failed |
| 2 | 6.213 | 31.352 | passed |

Authoring wall time was not measured separately. Original checkpoint is retained
above; the runner saves revised checkpoint hashes for each later run.

Expected and observed PHP/native output:

```text
empty:absent
same:present
cleared:7
cleared
99:0:5
absent:absent::yes
absent:false:true
absent:present
```

Reproduce: python3 tests/portability/nullable_parameters.py --results FRESH
--target-checkout /tmp/scpp-json-240-probe. The runner records all command outputs,
durations, generated sources and PHP checkpoint hashes.

Regression checks: portability foundation, method signatures and read-only checker;
small-compiler suite (38 lint files, Storage/view/model/AST tests, 19 LLVM and 28 call
execution fixtures, sample exit 9). The checker test's expected diagnostic for an
unsupported array default was updated to the specific nullable-default diagnostic;
this was a test expectation correction, not an acceptance change. No compiler files
were registered conversion-ready; required fields and Storage bindings remain pending.
