# E4NR mixed numeric nullable/Result ordinary equality
Doc Status: supporting

Completed 2026-10-20, using isolated snapshot
`/home/alex-ai/mixed-equality/20261020-01` on `alex-ai@dev002.travelfuse.ro`.
The runtime gate, final 28-signature source proof and all 23 focused regression
suites pass. No blocker remains within the approved slice.

## Scope and semantics

24 carrier signatures: `==` and `!=` × int/float in both orders × three
same-family/carrier-plain/plain-carrier arrangements × nullable and structured
Result. Four both-plain scalar prerequisites are counted separately.

Present payloads compare by int-to-float conversion and floating equality, including
rounding at large integer magnitudes. Signed zeros compare equal, NaNs unequal.
Two nullable absences compare equal; absence versus present/plain is unequal.
Two Result failures compare equal regardless of error metadata; failure versus
success/plain is unequal. `!=` negates `==`.

Strict identity remains unchanged: successful int and float are distinct kinds,
and Result failure identity compares structured error contents.

Snapshot complete carriers left-to-right before state comparison. Numeric
promotion occurs only for present payload comparison, without guarded extraction.
Scalar prerequisites use the existing conversion path. Unavailable or unequal
states do not skip RHS evaluation; source exceptions do. RHS replacement cannot
change the saved left operand; earlier RHS effects survive later source failures.

## Ownership and verification

`Floating_Point_Operators` owns the shared canonical mixed numeric pair and scalar
comparison promotion. `Wrapper_Equality` separates ordinary pair admission from
strict identity, preserving the existing matching-payload domain while adding
mixed numeric nullable and structured-Result domains. Family checks use semantic
roles, not source spelling. No new retained structures or backend path is needed.

The reusable comparison fixture has an explicit ordinary-numeric mode. Its
separate oracle models numeric conversion, nullable absence and Result failure
equality. Existing strict-identity oracles and storage observers are reused only
for their independent identity and state-preservation responsibilities.

Run on dev002:
`python3 compiler/my-try/tests/mixed_equality.py --results FRESH`.
Add `--runtime-only` for the prerequisite gate. Verify numeric boundaries around
±2^53 and int64 extrema, signed zeros, fractions, infinities, NaNs and every selected
state combination; preparation facts, once-only effects, source failures,
reference/property snapshots and retained repair. The proof includes negative controls for all
72 remaining mixed numeric carrier ordinary-equality signatures.

The initial source run passed all 24 carrier signatures and two scalar forms. The
other scalar fixture stopped on a wrongly assumed rejection of plain int/int16
ordinary equality. That established integer-family comparison is now an explicit
positive preparation control; float/int16 and carrier/int16 exclusions remain.
The failed attempt is retained separately from the repaired final proof.

No runtime implementation changes, full matrix rerun or native compiler rebuild.
Sentinel carriers, cross-family pairs, other payloads, spaceship, updates and
fixed-width/nested expansion remain separate.

## Final verification

All 24 carrier signatures and four scalar prerequisites pass. Preparation checks
confirm bool results, integer-to-float conversions for scalars, whole-state identity
conversions for carriers, no checked extractions and cleaned preparation facts.
Independent native expectations cover every selected value/state combination,
including distinct error metadata, numeric rounding, signed zeros and NaNs.
Reference/local and property replacements exercise preserved left snapshots,
once-only evaluation and RHS effects that survive a later source failure.

The selected runtime/source proof records **271,014 observations**, **28 retained
recovery sequences**, **154 negative controls**, and two preserved existing plain
integer-width positive preparation controls. All **2,213 selected runner commands**
return expected exits. All **23 focused regression suites** pass on their first
attempts, including heterogeneous string/bool/numeric and cross-family identity,
matching-payload equality, ordering, guarded operators, numeric XOR, scalar
admission and eager binary snapshots. Initial failed-attempt commands are archived
but excluded from these passing totals.

The compiler implementation required no correction after its first proof attempt.
The shared fixture's ordinary-only test correction was applied after identity
regressions finished; their executed branch was unchanged. The scalar float suite's
obsolete int/float equality rejection was changed to still-excluded bool/float
ordinary equality before that suite started. Other obsolete mixed-equality
rejections were narrowed through the shared test-domain helper, preserving all
remaining exclusions. No historical evidence was rewritten.

Toolchain: PHP 8.5.11 (CLI fallback for CLI/FPM version mismatch), Clang 18.1.3 and
Python 3.12.3. All compiler/native execution ran on dev002. Integrity checks cover
6,245 source files. The final source manifest identifies the selected final proof;
subsequent report closeout is documentation only.

## Evidence and progress

[Results and signatures](mixed_equality_results.json),
[initial and final evidence](mixed_equality_evidence.json.gz),
[runtime-gate source](mixed_equality_source.json),
[first source attempt](mixed_equality_source_first_attempt.json),
[final tested source](mixed_equality_source_final.json),
[baseline](mixed_equality_baseline.json),
[completion audit](mixed_equality_completion_audit.json).

V-L07 now has **30 completed slices: 2,568 binary, 64 unary and 432 compound
signatures — 3,064 total**. Four scalar prerequisites are counted separately.
The original proposed core remains 2,080/2,080, with 984 approved signatures beyond
that denominator. **888 design/scope candidates** remain, including 72 E4
sentinel-carrier/cross-family ordinary-equality signatures. Overall issue closure
remains 19/64 (29.7%); V-L07 is still partial.
