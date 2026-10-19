# E2FB: false-sentinel bool equality — 2026-10-19
Doc Status: supporting

Implemented and verified on `alex-ai@dev002.travelfuse.ro`, isolated snapshot
`/home/alex-ai/false-bool-equality/20261019-01`. All **12** `== != === !==`
signatures pass over `result_or_false<bool>` with itself or plain bool in either
order. No blocker remains within this slice. No runtime implementation changes,
full matrix rerun or native compiler rebuild.

## Contract and ownership

`Wrapper_Equality` now recognizes the false-sentinel semantic role only when its
payload matches canonical bool. Its existing exact carrier/payload checks exclude
cross-family and mixed-payload pairs. Whole-state identity conversions, zero
extraction facts and four distinct comparison operations remain unchanged. Existing
eager emission and runtime helpers preserve the following semantics:

| Operands | `==` | `===` |
| --- | --- | --- |
| Sentinel / sentinel | true | true |
| Sentinel / wrapped false | false | true |
| Sentinel / wrapped true | false | false |
| Sentinel / plain bool false | true | true |
| Sentinel / plain bool true | false | false |
| Two payloads | Bool equality | Bool equality |

For two carriers, equality preserves the sentinel/payload distinction. Identity
normalizes the sentinel to visible false. Wrapper/plain comparisons also compare
visible bool values. These rules apply symmetrically; negated forms invert their
respective result. Stored sentinel and false payload states remain distinct after
comparison. Semantic references: `runtime/specs/spec.md` §§6.10/7a and
`specs/expression_evaluation.md`; matrix bool-sentinel rows corroborate the two
carrier normalization rules.

An unavailable payload does not throw or skip the RHS during comparison. Source
exceptions still stop later evaluation. Guarded bool negation still requires a
payload and throws on the sentinel.

Source `false` is a bool payload, whereas `false_sentinel` explicitly constructs the
sentinel. Comparing with literal bool `false` has the plain-bool behavior above.
Direct comparisons with the explicit `false_sentinel` token remain rejected and
were not added by this goal. This clarifies the earlier discussion's reference to
keeping the literal-sentinel path separate; it did not imply an existing admitted
explicit-token comparison surface.

## Verification on dev002

`tests/false_bool_equality.py` uses the shared wrapper fixture. Its independent
oracle distinguishes two-carrier state equality from visible-bool identity and
wrapper/plain equality. All three carrier states and both plain bool values are
crossed with source error and replacement combinations.

| Check | Count |
| --- | ---: |
| Distinct signatures returning bool | 12 |
| Eager binary/source-error cases | 336 |
| Local replacement/error cases | 456 |
| Property replacement cases | 228 |
| Retained invalid-edit/repair/no-op/fresh sequences | 12 |
| Negative source executions | 184 |

Counters and trace prove eager left-to-right evaluation, including sentinel LHS.
Left division errors precede right modulo errors. Local snapshots include throwing
RHS replacement; property snapshots cover successful replacement. Observers check
both payload bits and carrier state, so normalized comparison results cannot mask
loss of the sentinel/payload distinction. Source construction controls distinguish
`return false` from `return false_sentinel`; four literal-false comparison controls
check every carrier state. These are extra proof dimensions, not extra signatures.

Preparation verifies complete-state identity conversions and no extraction. Cleanup
and failed edits clear artifacts; repair/no-op/fresh compilation agree. Exclusions
include mixed payloads, cross-family pairs, structured errors, boolean-sentinel
Result, all 36 non-bool false-result comparison signatures, and eight direct
explicit-sentinel comparisons.

All **ten regression suites** pass: nullable-int equality, nullable bool/string
equality, nullable-float equality, structured-error Result equality, nullable,
guarded bool, guarded bool XOR, guarded cross-family, numeric XOR and eager binary.
Only newly admitted bool-negative expectations were retired. Other gaps remain
negative and all earlier reports/evidence retain their historical counts.

The first focused attempt prepared successfully but its native observation code
used incorrect generated names for two fixture functions containing underscores.
Renaming the fixture functions and observers consistently fixed the proof; no
compiler naming or runtime change. That failure is retained. The second attempt
passed in 7.89 seconds. The final correction affects only construction controls in
this carrier's fixture branch; other carrier regression branches are unchanged.
CLI fallback was used due PHP CLI/FPM version mismatch; PHP 8.5.11, Clang 18.1.3,
Python 3.12.3.

[Results](false_bool_equality_results.json), [evidence](false_bool_equality_evidence.json.gz),
[initial source manifest](false_bool_equality_source.json),
[final source manifest](false_bool_equality_source_final.json) and
[completion audit](false_bool_equality_completion_audit.json) retain provenance.
All 6,220 final snapshot files matched dev002; 387 commands in successful runs had
expected exits. Initial failed commands remain separate. Local executable files
match final tested hashes; documentation and inventory closeout are separate.

## Progress and remaining scope

V-L07 remains partial: **15 completed slices, 1,596 binary and 32 unary signatures**.
E2FB moves 12 inventory rows to verified. E2 has **84** signatures left: 36
non-bool false-sentinel and 48 boolean-sentinel signatures. Core support targets
fall to **452**, with **1,872 design/scope candidates** unchanged. Proposed core
coverage is **1,628 / 2,080 = 78.3%**, not all-V-L07 completion or engineering effort.
Overall issue closure remains 18/64. See the [inventory](vl07_inventory/README.md).

Non-bool false-result wrapper/plain equality needs separate runtime/contract review:
source inspection finds a false-to-non-bool delegation that the bool proof does
not validate. Other payloads, `result_or_bool`, cross-family/mixed comparisons,
explicit sentinel comparisons, writable updates and table semantics remain separate.
No follow-on implementation is authorized by this closeout.
