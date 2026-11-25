# E4S mixed numeric sentinel ordinary equality
Doc Status: supporting

Verified on 2026-10-20 using isolated snapshot
`/home/alex-ai/sentinel-mixed-equality/20261020-01` on `alex-ai@dev002.travelfuse.ro`.

## Scope and semantics

24 carrier signatures: `==` and `!=` × int/float in both orders × three
same-family/carrier-plain/plain-carrier arrangements × `result_or_false` and
`result_or_bool`. Four scalar forms are rerun controls already implemented by
E4NR, not new prerequisites or additional V-L07 coverage.

Matching sentinel states compare equal; false and true sentinels differ.
Sentinel versus numeric payload/plain is unequal, including zero and one.
Present values compare by int-to-float numerical equality, including rounding
near ±2^53 and int64 extrema, signed zeros, infinities, subnormals and NaNs.
`!=` negates `==`. No truth conversion or guarded extraction is involved.

Whole operands are snapshotted left-to-right. Both sources evaluate eagerly;
sentinel states do not suppress RHS effects. Source exceptions stop evaluation,
and earlier effects survive a later failure. Saved values and original carrier
states remain distinct even when the RHS replaces the left source.

## Runtime prerequisite and ownership

The baseline runtime probe passed the eight same-family carrier signatures and
four scalar controls. Eight carrier/plain pair probes failed compilation, blocking
16 signature targets across both operators. The instantiated sentinel branch
incorrectly fell through to unsupported bool/numeric equality. These failures are
preserved in the evidence archive, separately from successful acceptance runs.

The authorized runtime repair introduces `sentinel_plain_nonbool_pair` inside the
operator generator's existing sentinel/plain dispatch helper. It admits canonical
int/float pairings and matching strings; sentinel/plain comparison returns false.
Existing bool, fixed-width and boxed dispatch paths remain unchanged. Runtime
configuration and contract prose now describe that numeric pairing explicitly.
The generated header was regenerated on dev002 twice and matches after normalizing
only its existing timestamp line. No generated output was patched by hand.

`Wrapper_Equality` owns compiler admission using semantic Result-family roles.
The same-family/plain mixed numeric rule now covers the two sentinel carriers.
Existing conversion facts retain complete snapshots, so no backend or retained
model changes are needed. Cross-family ordinary equality remains rejected.

The shared mixed-equality harness now selects nullable/Result or sentinel families.
Its independent value/state oracle models numerical equality and sentinel states;
existing storage observers check full source preservation. The old runtime negative
control for int-carrier/float is replaced by the still-invalid int-carrier/string
pair. Fixed-width, bool, matching-string and boxed controls remain.

## Verification

Run on dev002:
`python3 compiler/my-try/tests/mixed_equality.py --group sentinel --results FRESH`.
Use `--runtime-only` for the prerequisite gate. `--probe-runtime` additionally
records expected baseline failures; it is not a passing acceptance mode.

The final source proof covers all 24 carrier signatures and four scalar controls,
preparation facts, independent native value/state checks, local/reference and
property snapshots, once-only effects, source failures and retained recovery.
The remaining 48 cross-family mixed ordinary signatures are negative controls.

No full matrix rerun or native compiler rebuild. Cross-family equality, other
payload domains, spaceship and updates remain separate. The test toolchain uses
PHP 8.5.11 (CLI fallback for CLI/FPM version mismatch), Clang 18.1.3 and Python
3.12.3. All compiler/native execution ran on dev002.

All **25 focused regression suites** pass on their first attempts, including
identity, existing equality, ordering, XOR, guarded operators, eager binary
snapshots, scalar admission and sentinel runtime boundaries. The selected proof
records **236,862 observations**, **28 retained recovery sequences** and **130
negative source checks**, plus two existing integer-width positive preparation
controls. All **2,385 selected runner commands** returned expected exits. The
baseline compilation failures are archived but excluded from that passing total.
No blocker remains in the approved slice.

The final source manifest verifies 6,245 source files. Report and inventory
closeout follows that tested snapshot without changing the tested implementation.

## Evidence and progress

[Results and exact signatures](sentinel_mixed_equality_results.json),
[baseline and acceptance evidence](sentinel_mixed_equality_evidence.json.gz),
[baseline revision](sentinel_mixed_equality_baseline.json),
[baseline source](sentinel_mixed_equality_source.json),
[repaired runtime source](sentinel_mixed_equality_source_runtime.json),
[final tested source](sentinel_mixed_equality_source_final.json),
[completion audit](sentinel_mixed_equality_completion_audit.json).

V-L07 reaches **31 completed slices: 2,592 binary, 64 unary and 432 compound
signatures — 3,088 total**. The original core remains 2,080/2,080, with 1,008 approved
signatures beyond that denominator. **864 design/scope candidates** remain,
including **48 E4 cross-family mixed ordinary comparisons**. Overall issue closure
remains 19/64 (29.7%); V-L07 remains partial.
