# Global helpers and expression parser proof
Doc Status: derived

The active portable sources now use plain global helpers and q_-prefixed PHP
counterparts, with no function imports. The converter, runtime facade, maintained
proofs, authoring documentation and skill use the same fixed catalog. Frozen
reference/evidence trees and src-runtime-preparation were not migrated.

Expression parsing resumed on the existing parser owner. Statements and declarations
remain the next slice; this does not claim complete parsing or compiler migration.

## Reproduction and results

Exact clean native target: `9b4b33f35f053b487e018c94d6a4a7888d77c64a`,
checked out at `/tmp/scpp-json-240-probe`.

```sh
python3 tools/php_portability/validate.py --results /tmp/scpp-global-functions-native-01 --native compiler --target-checkout /tmp/scpp-json-240-probe
python3 tests/portability/utf8.py --results /tmp/scpp-global-utf8-02 --target-checkout /tmp/scpp-json-240-probe
python3 compiler/tests/expressions/run.py --results /tmp/scpp-expressions-native-02 --target-checkout /tmp/scpp-json-240-probe
python3 tools/php_portability/validate.py --results /tmp/scpp-global-expressions-final-fast-02
```

All passed. Use fresh result directories for reproduction. The first command proves
the five previously registered stages after the naming change; the separate expression
proof adds 132 PHP/native outcomes. UTF-8 exercises 308 matching assertions. Final
cumulative PHP/tool validation covers all 20 active production files and checks the
generated global facade. Trait regressions and the skill validator also passed.
Saved summaries retain original command paths; logs are copied alongside them.

## Cost and corrective cycles

The convention required five registered-stage native builds and one UTF-8 native
build, with zero native corrective cycles. Host test expectations needed adaptation
for import-free sources and generated q_ search calls before their successful runs.
Expression parsing took two native build attempts and one corrective cycle: locals
named operator/template collided with C++ keywords, so source locals were renamed
operation_kind/is_template. No target compiler or generated output was patched.
The first attempt stopped at compilation; only the second reached native behavior,
and its 132 outcomes matched immediately. A prior checker correction supplied named
object dependencies through constructors instead of uninitialized properties.

Successful expression timings: PHP 0.320 s, conversion 0.195 s, unchanged reuse
0.060 s, native build 29.304 s, native execution 0.058 s. These are observed wall
timings, not benchmarks. timing.json records 26.5 minutes of combined task elapsed
time, including the convention discussion/interruption; it cannot isolate expression
authoring from helper migration. cycles.json records attempt accounting; provenance.json
records source/oracle hashes. No further native builds were needed after documentation
and evidence consolidation.
