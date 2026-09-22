# Project resolution proof
Doc Status: derived

182 outcomes passed in PHP and strict native execution against clean
`9b4b33f35f053b487e018c94d6a4a7888d77c64a`. Coverage includes cold/warm/full
resolution, arbitrary worker arrival order, deletions, repair, ordinary versus
template edits, exact catalog replacement, negative lookup dependencies, malformed
output rejection, ordered template visibility and deep/diagnostic source fixtures.
25 host checks cover complete binding-fact export equality and snapshot preservation.

Three native commands were needed: STAN stopped the first on a test-local identity
comparison; the second reached C++ and found a same-name field/method collision;
the third passed. The typed test helper and distinct private field name preserve
behavior. An independent concept review also strengthened ordered template visibility
before final proof. No converter or target implementation changed.

Commands:

```
python3 compiler/tests/resolution_project/run.py --results /tmp/scpp-resolution-project-native-03 --target-checkout /tmp/scpp-json-240-probe
python3 tools/php_portability/validate.py --results /tmp/scpp-resolution-project-final-fast-01
```

`summary.json` records exact target, individual command times and source hashes.
`timing.json` records wall-clock milestones. `cycles.json` separates harness/source
corrections from the acceptance-model refinement. Retained source/tests are identified
in `provenance.json`; original session suites were not run, and provider import/type
preparation/whole-compiler integration are not claimed by this proof.

Authoring through first PHP-ready: **424.203s**. PHP-ready through native-ready: **366.047s** (includes acceptance refinement, host proof work, builds and documentation). Total through consolidation: **872.902s**. Orientation preceded authoring-start.
