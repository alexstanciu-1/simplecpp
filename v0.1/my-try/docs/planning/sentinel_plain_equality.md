# E2: sentinel/plain equality repair — 2026-10-19
Doc Status: supporting

Implemented and verified on `alex-ai@dev002.travelfuse.ro`, final isolated snapshot
`/home/alex-ai/sentinel-plain-equality/20261019-02`. The final **24** matching
sentinel/plain non-bool equality signatures now compile and execute. No blocker
remains within this slice. Runtime contract/generator/header changes were explicitly
authorized; no full matrix rerun or native compiler rebuild.

## Agreed semantics and scope

For `F = result_or_false` or `result_or_bool`, and canonical `T = int`, `float`,
or `string`, admit `F<T> == T`, `T == F<T>`, and both corresponding `!=` forms.

| Wrapper state | `==` matching plain T | `!=` matching plain T |
| --- | --- | --- |
| False sentinel | false | true |
| True sentinel (two-sentinel carrier) | false | true |
| Present payload | Scalar equality | Negated scalar equality |

Sentinels differ from zero, signed zero, empty string and numeric-looking strings.
Present float NaN remains unequal, including to itself. Present strings compare
bytes, including length and embedded NUL. No truth conversion or absent-payload
extraction occurs. States are preserved; source evaluation remains eager and
left-to-right with whole-state snapshots.

The approved clarification is recorded in `runtime/specs/spec.md` §6.10 and the
carrier entries in `runtime/specs/config.json`. Bool-payload contracts remain
unchanged: same-carrier equality distinguishes sentinels from matching wrapped bool
values, while strict identity and carrier/plain bool comparisons use visible bool.
Existing mixed/boxed delegation is preserved.

## Ownership and implementation

`runtime/tools/generate_operators.php` owns the repair. Its generated internal
`detail::sentinel_plain_equal<Payload>` helper centralizes the canonical non-bool
sentinel rule for both carriers and operand orders. It requires the plain operand
to exactly match the wrapper payload. Other routes retain their prior delegation;
ordinary scalar bool/non-bool equality is not enabled. `!=` still negates `==`.
The checked-in `runtime/include/scpp/generated/operators.hpp` was regenerated on
dev002, not patched manually. The generated diff contains the helper, four call
sites and the pre-existing timestamp update.

`Wrapper_Equality` no longer needs its temporary operator/arrangement restriction.
The same-carrier/exact-payload boundaries remain; existing complete-state conversions,
comparison facts and emission are reused. The shared negative-fixture predicate is
simplified accordingly. No retained structures, emitter paths or storage models changed.

The first candidate passed focused/runtime/regression checks, but review identified
that its helper also needed exact-payload scoping. A fresh final snapshot verified
that refinement plus explicit mixed-payload and fixed-width negative controls.
Initial passing evidence is retained separately; only the final snapshot supports
acceptance. There was no failed acceptance run in this goal.

## Verification on dev002

The expanded shared source fixture verifies **72 signatures and 72 recovery
sequences**: the preceding 48 plus the newly admitted 24. Progress counts only the
24 new signatures. Final focused tasks passed in 3.87–7.14 seconds each.

| Carrier / payload | Binary/error cases | Local snapshots | Property snapshots | Negative source checks |
| --- | ---: | ---: | ---: | ---: |
| False-sentinel / int | 2,816 | 10,816 | 5,408 | 148 |
| False-sentinel / float | 8,960 | 61,264 | 30,632 | 150 |
| False-sentinel / string | 16,720 | 156,104 | 78,052 | 148 |
| Two-sentinel / int | 3,312 | 13,896 | 6,948 | 156 |
| Two-sentinel / float | 9,840 | 70,680 | 35,340 | 158 |
| Two-sentinel / string | 17,920 | 173,440 | 86,720 | 156 |

The independent state oracle crosses sentinel/payload states with integer boundaries,
float signed zero/extremes/subnormals/infinities/NaN bits, and empty/numeric-looking/
binary strings. Counters and traces check eager ordering and division-before-modulo
source-error precedence. Local snapshots include throwing RHS replacement; property
snapshots verify saved left values and final state. Construction checks distinguish
sentinels from zero/empty payloads. Guarded ordering still rejects sentinel states.
Preparation checks no extraction, cleanup, failed edits and repair/no-op/fresh parity.
Cross-family/mixed source pairs and direct sentinel tokens remain rejected.

The direct runtime proof adds **1,096 comparison checks** across the 24 signatures,
bool-state preservation controls and boxed-route comparisons against the original
scalar-bool delegation, including exception outcomes. **12 ordinary scalar bool/
non-bool comparisons** remain compile failures. **Eight wrapper boundary controls**
retain mixed-payload and fixed-width compile failures in both operand orders.

All **24 historical instantiation probes now compile**. Their old failures remain
in [the preceding report and archive](nonbool_sentinel_equality.md). The observation
runner's metadata means that the runner itself makes no admission change; the
compiler admission change is part of this repair.

All **15 regression suites** pass: nullable-int equality, nullable bool/string
equality, nullable-float equality, structured-error Result equality, false-sentinel
bool equality, two-sentinel bool equality, guarded float, guarded string, guarded
integer operators, nullable, guarded bool, guarded bool XOR, guarded cross-family,
numeric XOR and eager binary.

Two generator runs reproduce identical content after excluding the generator's
existing timestamp line. No timestamp behavior change was introduced. PHP 8.5.11,
Clang 18.1.3, Python 3.12.3; CLI fallback due PHP CLI/FPM version mismatch.

## Provenance and progress

[Results](sentinel_plain_equality_results.json),
[final evidence](sentinel_plain_equality_evidence.json.gz),
[final source manifest](sentinel_plain_equality_source_final.json),
[completion audit](sentinel_plain_equality_completion_audit.json),
[initial candidate evidence](sentinel_plain_equality_first_attempt_evidence.json.gz)
and [initial candidate manifest](sentinel_plain_equality_source_first_attempt.json)
retain the development and verification history.
All **6,226** final source hashes matched dev002, and all **492 runner commands**
had expected exits. The 24 observation subprocesses separately report code zero.
Local executable/contract files match the tested hashes; report/inventory closeout
is separate from the tested snapshot.

Matching-payload sentinel equality E2 is complete. V-L07 remains partial:
**18 slices, 1,680 binary + 32 unary signatures**. The proposed core has
**1,712 / 2,080 = 82.3%** verified coverage, with **368 targets** remaining:
32 numeric carrier updates and 336 matching numeric compounds. The **1,872 design/
scope candidates** remain separate. Overall issue closure remains 18/64; signature
coverage does not estimate engineering effort. See the [inventory](vl07_inventory/README.md).

Next discuss U1 with the separate V-R10 runtime mutation prerequisite, then C1
compounds. Cross-family/mixed equality, explicit sentinel-token source comparisons,
other payload domains and table semantics remain separate.
