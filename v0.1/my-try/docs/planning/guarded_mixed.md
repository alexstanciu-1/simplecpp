# Guarded mixed-numeric operators
Doc Status: supporting

Completed and verified on dev002 on 2026-10-19. No blockers remain in this approved
slice. V-L07 remains partially repaired; the original audit and prior
[int](guarded_wrappers_01.md), [boolean-sentinel](guarded_wrappers_02.md) and
[float](guarded_float.md) reports remain historical evidence.

## Scope and ownership

For each family W (nullable, result, result_or_false, result_or_bool), the slice
admits W<int>/W<float>, W<int>/plain float and W<float>/plain int, in both orders.
The eight forms are `+ - * / && || and or`: 192 new binary signatures. No new unary
forms or cross-family combinations are admitted.

`Guarded_Operators` now compares semantic wrapper-family roles separately from
payload identity. Each operand contributes its own extraction target (or unchanged
plain numeric type) to the ordinary scalar decision. Existing arithmetic preparation
selects int-to-float conversion and float results. Logical preparation retains two
independent truth conversions and bool results, without arithmetic promotion.

This is a local refinement of the existing owner. No new retained records, emitter
branches or runtime changes were needed. The emitter already composes extraction
inside operand conversion, materializes the converted left value before the right
expression, and keeps lazy RHS extraction inside its conditional evaluation.

All unavailable states retain existing required-value failures. Both boolean
sentinels lack payloads. Division preserves `division_by_zero` for integer zero and
both floating signed zeros, including special-value numerators. Direct conditions,
selectors and take are not changed by this admission.

Authoritative contracts remain [expression evaluation](../../../../specs/expression_evaluation.md)
and the [runtime operator specification](../../../../runtime/specs/spec.md), including
its lifting and zero-divisor rules. Mixed ordering is still an unimplemented scalar
admission gap, not a new intentional incompatibility.

## Focused verification

Only dev002 executed tests: `/home/alex-ai/guarded-mixed/20261019-01`, using
PHP 8.5.11, Clang 18.1.3 and Python 3.12.3. Shared runners used CLI fallback because
PHP CLI/FPM versions differ. Two regression suites ran concurrently (up to 24 task
slots), plus the single focused task.

The fixture keeps native integer and floating states separate. Integer payloads
include -3, 0, 2 and ±9007199254740993 (just beyond exact double integer precision).
Float payloads include -2.5, +0.0, -0.0, 1.5, ±infinity and NaN. Expected arithmetic
explicitly converts to double; expected logical results use each original numeric
value independently. Observers preserve signed zero and classify NaN results.

The final native observer reports 35,008 binary value/state/source-error cases and
2,715 RHS replacement cases. Proof includes:

- All 192 signatures, exact result types, per-operand extraction types, exact
  integer-to-float/identity conversion decisions and separate logical truth facts.
- Every absence/error/sentinel state; independent runtime extraction controls and
  signed-zero division controls through the existing runtime helper.
- Ordered once-only calls, left extraction failures preventing RHS effects,
  reached right failures preserving earlier effects, and unchanged source states.
- All symbolic/word logical spellings, skipped/reached RHS states, mixed alias
  assignment precedence, and checked left snapshots before RHS replacement.
- 872 rejected sources: mixed ordering, equality, unsupported operators, mutations,
  compounds, skipped invalid expressions and all cross-family numeric combinations.
- Preparation cleanup and 32 retained failure/retry/repair/no-op/fresh sequences;
  invalid attempts leave no prepared or published artifacts.

| Suite | Result |
| --- | --- |
| Mixed focused initial | Pass, 12.79s |
| Mixed focused final | Pass, 13.73s; strengthened conversion and alias checks |
| Guarded int | Pass; 228 binary and 16 unary signatures |
| Guarded float | Pass; 144 binary and 12 unary signatures |
| Numeric logical | Pass |
| Scalar admission/arithmetic | Pass |
| Result/extraction/take | Pass; 11 PHP suites, 166 generated programs, two runtime controls |
| Nullable | Pass; 11 PHP suites, 99 generated programs |

Run `python3 compiler/my-try/tests/guarded_mixed.py --results FRESH` on dev002 with
`PATH=/home/alex-ai/language-verification/20261018/verification/modern-bin:$PATH`.
Companion runners are `guarded_operators.py`, `guarded_float.py`,
`numeric_logical.py`, `scalar_admission.py --jobs 12`, `results.py`, and
`nullable.py`, each with a fresh results directory. Production code remained
unchanged across these attempts; none failed. No native compiler rebuild is claimed.

## Fixture migration, evidence and remaining scope

Fourteen newly admitted addition negatives were removed from the old int fixture.
Its remaining addition negatives now infer a local result, so rejection proves
invalid operand admission rather than an unrelated return-type mismatch. The float
fixture retires 128 arithmetic/logical negatives while retaining all mixed ordering
and cross-family rejection checks. New positive coverage includes those operand
shapes. Previous manifests, counts and evidence remain unchanged.

[Results](guarded_mixed_results.json) and [evidence](guarded_mixed_evidence.json.gz)
record command exits, observed counts, toolchain and checksums. The archive contains
1,935 records and verifies 6,199 source files. The
[baseline](guarded_mixed_baseline.json), [initial source](guarded_mixed_source.json),
[final tested source](guarded_mixed_source_final.json), and
[completion audit](guarded_mixed_completion_audit.json) separate inherited changes,
tested source and local documentation closeout. Existing executable changes are
limited to the guarded owner and the two earlier fixtures; the mixed fixture and
runner are new. Runtime implementation/specification hashes match the baseline.

Mixed ordering, equality/identity and sentinel comparisons, cross-family pairs,
mutation/compounds, other payloads, remainder/power/bitwise extensions, spaceship
and xor remain separate. V-R10 runtime mutation debt remains independent. No runtime
changes, full matrix rerun or follow-on implementation were performed.
