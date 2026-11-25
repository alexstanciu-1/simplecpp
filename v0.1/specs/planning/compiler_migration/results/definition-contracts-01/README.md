# Full semantic definition comparison
Doc Status: planning

1,513 independently expected PHP/native outcomes: all pairs among 30 definitions,
18 storage families and 17 record declarations. Coverage includes names/namespace,
integer capabilities, lifetime permissions, normalized resource absence and ordered
paths, exact native layouts, element type IDs/definitions/families, primitive ABI,
operation spellings, map membership/order, record field names/mutability/extents/
element contracts/order and lifecycle body IDs. Storage map snapshot mutations
cannot change the owning family. Existing leaf proofs cover representation and
lifecycle variant details.

Seven focused retained-prototype checks separately confirm critical equality
semantics (independent equal definitions, default/explicit empty resources, path
order, map order, changed permission, target layout and element ID). These are not
a claim of a retained oracle for every matrix pair.

Clean target 9b4b33f35f053b487e018c94d6a4a7888d77c64a, strict clang++-18: first
native build passes, no native correction cycles. One reserved test-local checker
fix precedes PHP readiness. Native source hashes include the changed storage owner.

Run python3 compiler/tests/definition_contracts/run.py --results FRESH
--target-checkout /tmp/scpp-json-240-probe; cumulative validation uses
python3 tools/php_portability/validate.py --results FRESH. Runtime type retention
and full package-context reuse remain unfinished.

Cumulative fast validation passes with 126 registered production files, including
the final runner and retained checks; native evidence is in proof/summary.json.
