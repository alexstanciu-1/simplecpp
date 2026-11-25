# Package reuse context
Doc Status: planning

33 independently expected PHP/native cases: exact directory/manifest/catalog;
type/callable reference maps and membership; native provider/ID/target/layout and
accepted type identity; source/project export identity and membership; receipt bytes;
null selection combinations; new equivalent ordinary wrappers; map insertion order;
and unchanged accepted objects. Constructor fixtures prove a cache query only, not
provider artifact or project receipt acceptance.

Unlike the prototype's PHP == heuristic, distinct accepted native type/source export
objects intentionally prevent reuse even when structurally equal. This preserves
current accepted-owner associations. Rebuilt ordinary references/import wrappers
with the same accepted owner still match. This is not a claim of byte-for-byte
compatibility with the old cache heuristic.

Clean pinned target 9b4b33f35f053b487e018c94d6a4a7888d77c64a, strict clang++-18.
First native build passes with no correction cycle. Run
python3 compiler/tests/package_context/run.py --results FRESH
--target-checkout /tmp/scpp-json-240-probe; cumulative validation uses
python3 tools/php_portability/validate.py --results FRESH.

Affected existing runtime-package proof also passes 160 native outcomes with the
new context/reference dependencies; see runtime-package-regression/summary.json.

Cumulative fast validation passes with 128 registered production files.
