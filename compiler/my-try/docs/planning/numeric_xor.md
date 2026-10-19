# Canonical numeric XOR — 2026-10-19
Doc Status: supporting

Implemented and verified on `alex-ai@dev002.travelfuse.ro`: all **100 signatures**
pass, comprising four plain and 96 guarded signatures. No blockers remain within
this slice. V-L07 remains partial. No runtime changes, full matrix rerun or native
compiler rebuild were involved.

## Contract and implementation

The [numeric XOR amendment](../../../../specs/expression_evaluation.md#canonical-numeric-logical-xor--2026-10-19)
admits int/int, float/float, int/float and float/int. For each operand, the surface
may be plain, nullable, result, result_or_false or result_or_bool. Every result is
plain bool. Numeric zero, including both signed floating zeros, is false; nonzero,
NaN and infinities are true. Each operand converts independently: there is no
common numeric promotion and no float-to-int conversion.

Preparation selects the named `condition_xor` operation and retains the existing
per-operand truthiness decisions with their actual payload types. Guarded extraction
already delegates these payloads to scalar preparation. The emitter converts each
reached payload to bool, then uses the common eager materialization path to snapshot
left before right. Computation compares those saved truth values. AND/OR remain
lazy; bool XOR keeps its existing exact-bool decision.

Either left truth value reaches the RHS after successful evaluation/extraction.
A failure stops subsequent work and preserves prior effects. Carrier failures keep
their existing error surfaces, including both unavailable boolean sentinel states.
XOR retains lower-than-assignment precedence. Bool/numeric mixing, strings,
fixed-width types, equality and updates remain outside this admission.

## Verification

The dedicated `numeric_xor.py` runner uses `guarded_mixed.php --numeric-xor` and
checks the exact 10-by-10 operand-type signature set, not only the count. Preparation
proofs require the named eager decision, independent truth conversion source types,
plain bool result and the correct presence/absence of extraction facts.

The native observer passed:

| Proof | Cases |
| --- | ---: |
| Binary values, carrier states and independently injected source failures | 19,600 |
| Local replacement/state snapshots | 10,360 |
| Successful property snapshots | 8,040 |
| Assignment precedence and explicit grouping (each case checks both) | 3,600 |
| Retained edit/recovery sequences | 100 |

The independent value oracle uses `(a != 0) != (b != 0)` on the original native
integer/float values. Values include zero, both floating signed zeros, positive and
negative values, NaN, infinities, and integers beyond exact double precision.
Counters/traces prove eager once-only left-to-right evaluation, including left
false reaching RHS. Failure products prove left-error precedence and distinguish
source failures from family-specific extraction errors. State observers preserve
structured Result errors and both boolean sentinel identities.

RHS replacement tests cover actual left local/reference storage and object
properties. Local cases include unavailable operands and observable replacement
before a right extraction failure; successful property cases prove the old left
truth survives replacement. Plain operands and every ordered wrapper-family pair
participate. Source assignment/grouping checks preserve word-operator precedence.

Each of the 100 retained sessions checks successful preparation, an unresolved-call
edit, repeated failure with empty artifacts, operand-reversal repair, no-op
stability and fresh equivalence. Shared recovery uses an unresolved call because
plain scalar equality is valid; it must not be treated as a universal invalid edit.
An additional 240 focused exclusions cover bool/numeric mixing, strings and selected
fixed-width forms in both orders. Existing mixed-ordering, equality and update
exclusions remain where applicable.

All nine final companion suites passed: `numeric_logical`, `guarded_bool_xor`,
`guarded_cross` (660 signatures), `eager_binary`, `guarded_operators`, `guarded_float`,
`guarded_mixed`, `guarded_spaceship` and `scalar_admission`. Newly valid numeric-XOR
negatives were retired from numeric-logical and guarded fixtures; original reports
and evidence remain unchanged.

## Attempts and provenance

Remote snapshot: `/home/alex-ai/numeric-xor/20261019-01`.
Toolchain: PHP 8.5.11, Clang 18.1.3, Python 3.12.3. Shared-runner CLI fallback was
used because installed CLI/FPM versions differ. All executable tests ran on dev002.

```sh
PATH=/home/alex-ai/language-verification/20261018/verification/modern-bin:$PATH \
  python3 compiler/my-try/tests/numeric_xor.py --results verification/focused-02
```

The initial focused run and initial regressions stopped in shared operator tests:
a revised rejection diagnostic had changed their expected prefix. Restoring the
existing `canonical bool operands` prefix while updating the numeric clause fixed
that compatibility issue. Admission and emission were unchanged by the retry.
The focused retry passed in 16.88 seconds. Final companion runs use fresh
`verification/<suite>-final` directories; `scalar_admission` received `--jobs 12`.
Two bounded regression runners use at most 24 tasks. Failed-attempt evidence is kept.

Remote integrity verified 6,208 source files; 400 evidence records were archived.
See [results](numeric_xor_results.json), [evidence](numeric_xor_evidence.json.gz),
[baseline](numeric_xor_baseline.json), [initial source](numeric_xor_source.json),
[final source](numeric_xor_source_final.json), and
[completion audit](numeric_xor_completion_audit.json). Documentation and the approved
semantic amendment were finalized separately from the tested executable snapshot.

## Remaining work

Bool/numeric mixing, string logical operators, fixed-width expansion, equality/state
normalization, mixed numeric ordering, writable updates and spaceship outside
canonical-int/string pairs remain separate. These exclusions are not promises of
future admission. V-R10 runtime mutation debt remains independent. Overall issue
closure remains 18/64 (28.1%), with V-L07 partial.
