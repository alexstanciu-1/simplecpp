# Typed export identity encoding
Doc Status: planning

61 PHP/native outcomes and 215 exact key comparisons with the retained identity
record. Cases cover all five tags, nested arrays/arguments, declared constant types,
source flags, all JSON control characters, slash/backslash combinations, Unicode
including supplementary characters, normalized arbitrary-width integer literals,
INT64_MAX array count and invalid/uninitialized construction. Mutating the caller's
argument vector after construction does not alter a previously accepted key.

The record stores canonical encoded keys rather than heterogeneous parts trees.
Nested keys are embedded, not string-escaped again. Source projection can replace
its two parts-array composition sites with typed factories. It still must enforce
nominal provenance, declaration paths and canonical lineage; this proof does not
claim that projection is migrated.

Strict clang++-18 target: clean
`9b4b33f35f053b487e018c94d6a4a7888d77c64a`. One checker correction replaced a
string cast. First native command stopped in STAN on a probe variable scoped to a
loop; moving it outward made the second command pass. One actual C++ compilation,
no production correction after the first passing PHP checkpoint.

Run `python3 compiler/tests/export_identity/run.py --results FRESH` with optional
`--target-checkout /tmp/scpp-json-240-probe`; cumulative validation uses
`python3 tools/php_portability/validate.py --results FRESH`. Logs preserve failed
attempts and timings; cases include independent expected exact encodings.
