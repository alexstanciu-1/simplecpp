# Runtime metadata / concrete demand audit
Doc Status: planning

Date: 2026-10-19. Base: `df056d90` plus the inventory-test extension.

All eight current runtime module catalogs compose. They declare 14 ordinary types,
including two templates (vector/hash), and 171 function contracts. This count excludes
compiler-interpreted scalar/wrapper definitions and unregistered runtime surfaces;
it is not a claim that the full runtime is represented in metadata.

The existing `runtime_inventory.php` proof now loads all catalogs, constructs an
empty concrete engine, and requests `hash<vector<int>,string>`. Exactly three
concrete type headers are generated: the hash, vector and integer. No unrelated
catalog type is generated. Repeated demand preserves the reference, emitted headers
and semantic type count. A fresh engine does not inherit requests from catalogs.
Fixed base support is emitted separately; the empty-engine assertion concerns
concrete type headers, not the absence of every support file. The native string
representation remains unchanged under current policy.

`python3 compiler/my-try/tests/runtime_inventory.py --results /tmp/prelower-runtime-metadata-inventory-final`
passed all three suites (`runtime_inventory`, `runtime_types`, `runtime_protocols`) through the
shared 12-slot runner with PHP CLI fallback. Logs are in
`/tmp/prelower-runtime-metadata-inventory-final/logs`. This is a host emission/metadata proof,
not a native execution, allocation or compilation-time result.

The first run exposed a stale inventory expectation: the imported JSON module now
lists `modules/json/json.cpp` alongside `document.cpp`. The test expectation was
updated to the actual catalog build contract; provider metadata was not changed.

[Machine-readable snapshot](summary.json) ·
[Current matrix and proposed binding extension](../../runtime_template_capability_matrix_2026_10_19.md)
