# E5SI heterogeneous string strict identity
Doc Status: supporting

Completed 2026-10-20. Runtime gate and source verification used the isolated snapshot
`/home/alex-ai/string-other-identity/20261020-01` on `alex-ai@dev002.travelfuse.ro`.
The runtime gate, all 300 source signatures and all 22 focused regression suites
passed on their first attempts.

## Scope and semantics

288 carrier signatures: `===` and `!==` × string/int, string/float and string/bool
in both orders × 24 carrier/plain arrangements. Twelve both-plain scalar
prerequisites are separate. Nullable, result, result_or_false and result_or_bool
participate, including same-family and cross-family pairs.

Normalize operands independently under the shared strict-identity contract.
Successful string payloads never match int, float or bool payloads. Do not parse
numeric-looking strings, convert truth, promote numbers or extract absent payloads.
Matching null states, bool sentinels and structured errors can remain identical
across different declared payloads. A string carrier's bool sentinel can match
a successful bool payload; its successful string payload cannot. `!==` negates
`===`, and both return plain bool.

Snapshot complete operands eagerly from left to right. Unequal/unavailable states
do not suppress RHS evaluation; source exceptions do. RHS replacement cannot alter
the saved left state, and earlier RHS effects survive a later source failure.
Comparison preserves stored states and complete string bytes.

## Ownership and implementation

The runtime-only gate passed before compiler admission. `Scalar_Identity` now
expresses one distinct-kind capability for canonical bool, int, float and string.
Its private helper is named `is_canonical_scalar`, matching that domain. Wrapper
identity reuses this rule and its existing whole-state conversions. No retained
model, backend or runtime implementation changed.

The reusable identity driver adds a `string-other` group. Its independent
normalized-state oracle already supports string storage and heterogeneous kinds.
The test-domain admission helper now recognizes these newly legal strict pairs.
Three obsolete scalar string-identity rejection controls were changed to ordinary
equality controls; their intended coercion exclusion remains. No historical proof
archive was rewritten.

## Verification

All 288 carrier signatures and twelve scalar prerequisites pass, including:

- Exact signature/result types, unchanged operand kinds and identity conversions,
  no checked extraction and cleanup of preparation facts.
- Every state combination for the selected values: empty/numeric-looking strings,
  embedded NULs, high bytes and composed/decomposed Unicode byte sequences; numeric
  boundaries, signed zeros, infinities, subnormals and distinct NaNs; bool payloads
  versus sentinels; null and structured errors differing in message, line or file.
  Storage observations compare complete bytes, float bits and error details.
- Eager once-only source evaluation, left/right failures and exact traces;
  reference/local and property RHS replacement; saved-left snapshots; preserved
  earlier RHS effects when a later source operation fails.
- 300 retained failure/retry/repair/no-op/fresh recovery sequences and 850 negative
  controls for ordinary heterogeneous equality, fixed-width identity and selected
  bool/string logical combinations.

The selected runtime/source proof records **1,850,820 observations**. Across the
prerequisite gate, selected proof and regressions, all **2,146 runner commands**
return their expected exits. All **22 focused regression suites** pass, covering
previous numeric/bool and cross-family identity, matching-payload equality, scalar
string identity, guarded operators, numeric XOR, ordering and eager snapshots.

Toolchain: PHP 8.5.11 (CLI fallback because CLI/FPM versions differ), Clang 18.1.3
and Python 3.12.3. All compiler and native execution ran on dev002. Source integrity
checks cover 6,243 files. A syntax typo in the temporary evidence collector was
corrected before collection; it required no implementation change or test rerun.
No blocker remains within the approved slice.

Reproduce on dev002:
`python3 compiler/my-try/tests/mixed_identity.py --group string-other --results FRESH`.
Add `--runtime-only` for the prerequisite gate. Focused previous identity/equality,
guarded operator and eager snapshot suites are the regression boundary.

No runtime implementation changes, full matrix rerun, native compiler rebuild,
ordinary equality, concatenation, spaceship or update expansion are authorized.
Historical evidence remains unchanged.

## Evidence and progress

[Results and signatures](string_other_identity_results.json),
[proof archive](string_other_identity_evidence.json.gz),
[runtime-gate source manifest](string_other_identity_source.json),
[final tested source manifest](string_other_identity_source_final.json),
[baseline](string_other_identity_baseline.json),
[completion audit](string_other_identity_completion_audit.json).
The tested manifest identifies the executable snapshot; subsequent report closeout
is documentation only.

V-L07 now has **29 completed slices: 2,544 binary, 64 unary and 432 compound
signatures — 3,040 total**. Twelve new scalar prerequisites are counted separately.
The original proposed core remains 2,080/2,080, with 960 approved signatures beyond
that denominator. **912 design/scope candidates** remain. Overall issue closure
remains 19/64 (29.7%); V-L07 is still partial.
