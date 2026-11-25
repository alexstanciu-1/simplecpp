# E2BB: two-sentinel bool equality — 2026-10-19
Doc Status: supporting

Implemented and verified on `alex-ai@dev002.travelfuse.ro`, isolated snapshot
`/home/alex-ai/bool-sentinel-equality/20261019-01`. All **12** `== != === !==`
signatures pass over `result_or_bool<bool>` with itself or plain bool in either
order. No blocker remains within this slice. No runtime implementation changes,
full matrix rerun or native compiler rebuild.

## Contract and ownership

`Wrapper_Equality` admits both boolean-sentinel carrier roles through its bounded
bool-payload rule. Existing exact carrier/payload checks, complete-state identity
conversions and zero extraction facts remain unchanged. The existing emitter and
runtime helpers implement the comparisons; neither needed changes.

| Operands | `==` | `===` |
| --- | --- | --- |
| Same sentinel / same sentinel | true | true |
| Opposite sentinels | false | false |
| Sentinel / wrapped matching bool | false | true |
| Sentinel / wrapped opposite bool | false | false |
| Sentinel / plain matching bool | true | true |
| Sentinel / plain opposite bool | false | false |
| Two payloads | Bool equality | Bool equality |

Rules apply symmetrically; negated forms invert their respective results. Two-carrier
equality distinguishes sentinel and payload states, while identity and carrier/plain
comparisons use visible bool values. Comparison never changes the stored state or
extracts an unavailable payload. Semantic references: `runtime/specs/spec.md`
§§6.10/7a and `specs/expression_evaluation.md`.

Comparisons are eager, including either sentinel on the left. Source exceptions
still stop later evaluation. Guarded negation continues to reject both sentinels.
Source `false` and `true` construct payloads; `false_sentinel` and `true_sentinel`
construct explicit sentinel states. Direct comparisons against explicit sentinel
tokens remain rejected and outside this slice.

## Verification on dev002

`tests/bool_sentinel_equality.py` uses the shared wrapper fixture. Its independent
bool-state oracle covers false sentinel, true sentinel, payload false and payload
true, plus both plain bool values. The same oracle also verifies the earlier
false-sentinel carrier without adding an impossible true-sentinel state to it.

| Check | Count |
| --- | ---: |
| Distinct signatures returning bool | 12 |
| Eager binary/source-error cases | 512 |
| Local replacement/error cases | 896 |
| Property replacement cases | 448 |
| Retained invalid-edit/repair/no-op/fresh sequences | 12 |
| Negative source executions | 192 |

Counters and traces prove eager left-to-right evaluation. Left division errors
precede right modulo errors. Local snapshots cover throwing RHS replacement;
property snapshots cover successful replacement. State observers distinguish all
four states after comparison. Additional controls verify all four source
constructors and comparisons with literal false/true for each operator and state.
These are proof dimensions, not additional signatures.

Preparation checks complete-state conversions and no extraction. Failed edits and
cleanup clear artifacts; repair/no-op/fresh compilation agree. Negative checks
retain non-bool payloads, cross-family comparisons and 16 direct sentinel-token
comparisons. The guarded negative-fixture helper now names the admitted matching
carrier/plain equality concept; it continues to exclude cross-family pairs.

All **eleven regression suites** pass: nullable-int equality, nullable bool/string
equality, nullable-float equality, structured-error Result equality, false-sentinel
bool equality, nullable, guarded bool, guarded bool XOR, guarded cross-family,
numeric XOR and eager binary. Only newly admitted equality expectations were
retired. Historical reports and evidence retain their original counts.

The focused proof passed on its first attempt in 7.59 seconds. CLI fallback was
used due PHP CLI/FPM version mismatch; PHP 8.5.11, Clang 18.1.3, Python 3.12.3.
All **393 commands** had expected exits. All **6,222 source files** matched the
remote manifest. Local executable files match tested hashes; documentation and
inventory closeout are separate from that snapshot.

[Results](bool_sentinel_equality_results.json),
[evidence](bool_sentinel_equality_evidence.json.gz),
[source manifest](bool_sentinel_equality_source_final.json) and
[completion audit](bool_sentinel_equality_completion_audit.json) retain provenance.

## Progress and remaining scope

V-L07 remains partial: **16 completed slices, 1,608 binary and 32 unary signatures**.
E2BB moves 12 rows to verified. E2 retains **72 non-bool signatures**, 36 per sentinel
family. Remaining core targets fall to **440**, with **1,872 design/scope candidates**
unchanged. Proposed core coverage is **1,640 / 2,080 = 78.8%** by signature count,
not engineering effort or all-V-L07 completion. Overall issue closure remains
18/64. See the [inventory](vl07_inventory/README.md).

Next discuss the remaining E2 non-bool runtime/contract routes before admission;
the bool proofs do not validate false-to-non-bool delegation. Cross-family/mixed
comparisons, explicit sentinel comparisons, writable updates and table semantics
remain separate. No follow-on implementation is authorized by this closeout.
