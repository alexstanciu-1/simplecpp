# Bound runtime type retention
Doc Status: planning

172 PHP/native outcomes: 121 complete-row comparisons, 48 binding/reuse cases and
three source-export cases. The 169 row/reuse cases compare with actual retained
prototype classes and Package_Types::retain_bound_types. Source cases independently
expect exact identity acceptance and rejection of equal-but-distinct/missing rows.

Checks cover changed storage/width/signedness/definition/record fields, changed or
absent bindings, nullable previous bindings, missing old rows, old-object reuse,
and late rejection after an earlier candidate was eligible for reuse. Input maps
and old package objects remain unchanged. This is adapter-internal normalization;
provider/directory/target/base-catalog acceptance remains the caller's precondition.

Clean pinned target 9b4b33f35f053b487e018c94d6a4a7888d77c64a, strict clang++-18.
First native build passes, no native corrections. A final verification build after
fixture consistency improvements also passes. Package acceptance integration is
not yet implemented.

Run python3 compiler/tests/type_retention/run.py --results FRESH
--target-checkout /tmp/scpp-json-240-probe; cumulative validation uses
python3 tools/php_portability/validate.py --results FRESH.

Cumulative fast validation passes with 127 registered production files. Focused
native evidence is saved separately in proof/summary.json.
