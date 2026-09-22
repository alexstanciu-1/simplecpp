# Lifetime and recursive lifecycle comparison
Doc Status: planning

842 PHP/native and retained-prototype equality cases: all pairs among 21 operation
variants (441), self/baseline checks for all 198 valid permission configurations
(396), and a changed implementation for each of five roles. Source plans cover
member order/count/type/index/role, null/imported/nested-source members, repeat,
custom body, linkage and convention. Equal role sets supplied in reverse order
still compare equal. Independent objects and shared-identity early returns are
both exercised. The oracle constructs actual retained prototype records.

Clean target 9b4b33f35f053b487e018c94d6a4a7888d77c64a, strict clang++-18. First
checker, PHP, oracle and native attempts pass; no correction cycles. No object
execution/lifetime behavior is claimed: this proves immutable contract equality.
Comparisons of local type/body IDs require the same accepted lineage.

Full definition/resource/layout/storage equality and package-context acceptance
remain unfinished. Run python3 compiler/tests/lifecycle_contracts/run.py
--results FRESH --target-checkout /tmp/scpp-json-240-probe; cumulative validation:
python3 tools/php_portability/validate.py --results FRESH.

Cumulative fast validation passes with 125 registered production files; focused
native evidence is recorded in proof/summary.json.
