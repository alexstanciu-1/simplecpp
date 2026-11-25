# Runtime package records and queries
Doc Status: planning

160 PHP/native cases: all 32 combinations of lifecycle operations against no
external bindings, accepted native binding, ordinary name binding, source binding
and nullable package bindings. Exact operation identity and stable type/role order
are asserted. Retained prototype enumeration agrees in all cases.

Every case also checks type/storage/catalog identity, construction/query container
snapshot isolation, protected paths, module variants, manifest text and errors for
missing types/modules. Final proof includes project/binding identity and uses the
source export's exact definition for the source-bound row.

Target: clean 9b4b33f35f053b487e018c94d6a4a7888d77c64a, strict clang++-18. First
native build passes without correction; one final verification build after fixture
strengthening also passes. Timing records distinguish the first PHP checkpoint.

Package reuse, exact semantic comparison, lease ownership, diagnostic projection,
artifact checks and receipt authorization remain unfinished. Constructed fixtures
prove normalized-data queries, not provider or source-export production.

Run python3 compiler/tests/runtime_package/run.py --results FRESH
--target-checkout /tmp/scpp-json-240-probe; cumulative validation uses
python3 tools/php_portability/validate.py --results FRESH.

Cumulative fast validation passes with 122 registered production files. The fast
run is PHP/tooling evidence; focused native evidence is in proof/summary.json.
