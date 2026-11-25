# Nullable canonical-int equality — 2026-10-19
Doc Status: supporting

Implemented and verified on `alex-ai@dev002.travelfuse.ro`: all **12 signatures**
pass. No blockers remain within this slice. V-L07 stays partially implemented.
No runtime changes, full matrix rerun or native compiler rebuild were involved.

## Contract and ownership

The four forms `==`, `!=`, `===`, `!==` accept `?int/?int`, `?int/int` and
`int/?int`, returning plain bool. Equality and strict identity agree for this
exact-int domain; their negated forms invert the result.

| Left state | Right state | Equality / identity |
| --- | --- | --- |
| Empty | Empty | true |
| Empty | Present integer, including zero | false |
| Present integer | Empty | false |
| Present integer | Present integer | Exact integer payload comparison |

The dedicated `Nullable_Equality` preparation owner admits only this bounded
candidate. Four named nullable comparison operations retain identity conversions
of complete operands; there are no guarded extraction facts or new retained record
shapes. Unsupported candidates fall through to existing preparation, preserving
unrelated boxed behavior. Literal-null presence dispatch remains earlier and separate.

The existing eager emitter saves the complete left value, then the complete right
value, before invoking the matching existing runtime equality/identity helper.
An empty left operand still evaluates the RHS. Source failures stop subsequent
evaluation and preserve prior effects. RHS changes cannot alter the saved left
presence state or payload. Runtime helper behavior was checked against an independent
state/value oracle; no helper implementation changed.

Guarded arithmetic and ordering continue requiring payloads and throwing on empty
states. Result/sentinel families, other payload types, cross-family equality and
writable updates remain separate.

## Verification

The shared-runner fixture validates the exact 12-signature set, bool result type,
matching named comparison operation, two identity conversions and zero extractions.
Preparation cleanup clears attached facts.

| Proof | Cases |
| --- | ---: |
| Binary states/values and independently injected source errors | 2,816 |
| Local/reference replacement, including RHS failure after replacement | 10,816 |
| Successful property state/value replacement | 5,408 |
| Retained edit/recovery sequences | 12 |
| Excluded sources | 136 |

The independent oracle compares presence first, then exact integer payloads.
Values include minimum/maximum int64, positive/negative integers beyond exact
double precision, -1, zero and 1. Every empty/present pairing participates.
Left source failures use `division_by_zero`; right source failures use
`modulo_by_zero`, making error precedence observable independently of counters.
Counters/traces prove once-only eager evaluation even with an empty left operand.
Input states remain unchanged unless the test explicitly replaces the left storage.

Replacement cases cover empty-to-present, present-to-empty and payload-to-payload
changes in local/reference storage and object properties. Local failure cases prove
that RHS replacement remains visible when the RHS subsequently throws. Property
cases cover successful comparison. Literal-null identity/presence remains correct;
explicit controls prove that empty nullable addition and ordering still throw
`invalid_nullable_unwrap_empty`.

All 12 retained sessions cover invalid edits, repeated failure with empty artifacts,
repair by operand reversal, no-op stability and fresh equivalence. Exclusions cover
other nullable payloads, fixed-width types, Result/sentinel families and mismatched
partners in both operand orders. Only newly valid nullable-int negatives were
retired from the integer and shared guarded fixtures.

All eight companion suites passed: `nullable`, `string_identity`,
`guarded_operators`, `guarded_cross` (660 signatures), `eager_binary`,
`guarded_spaceship`, `numeric_xor` and `scalar_admission`.

## Provenance

Remote snapshot: `/home/alex-ai/nullable-int-equality/20261019-01`.
Toolchain: PHP 8.5.11, Clang 18.1.3, Python 3.12.3. Shared-runner CLI fallback was
used because installed CLI/FPM versions differ. All executable verification ran on
dev002. Two bounded regression runners used at most 24 tasks.

```sh
PATH=/home/alex-ai/language-verification/20261018/verification/modern-bin:$PATH \
  python3 compiler/my-try/tests/nullable_int_equality.py --results verification/focused-01
```

Companion suites used fresh `verification/<suite>` directories; `scalar_admission`
received `--jobs 12`. The focused proof passed on its first attempt in 7.43 seconds.
Remote integrity verified 6,211 source files; 836 evidence records were archived.

See [results](nullable_int_equality_results.json), [evidence](nullable_int_equality_evidence.json.gz),
[baseline](nullable_int_equality_baseline.json), [source](nullable_int_equality_source.json),
[final source](nullable_int_equality_source_final.json), and
[completion audit](nullable_int_equality_completion_audit.json). Documentation
closeout is separate from the tested executable snapshot. Historical evidence and
inherited work are preserved.

## Remaining work

Equality/identity beyond nullable canonical-int pairs, sentinel comparisons,
Result families, other payloads, cross-family equality, mixed numeric ordering and
updates remain separate. V-R10 runtime mutation debt stays independent. Overall
issue closure remains 18/64 (28.1%), with V-L07 partial.
