# O1 mixed canonical numeric ordering
Doc Status: supporting

Completed 2026-10-19 on `alex-ai@dev002.travelfuse.ro`, snapshot
`/home/alex-ai/mixed-ordering/20261019-01`.

## Approved semantics and ownership

Eight scalar prerequisites and 192 carrier signatures: `< <= > >=` × both ordered
int/float payload pairs × 24 carrier/plain arrangements. Both-plain signatures are
prerequisites outside the V-L07 carrier count. Results are plain bool.

The [language amendment](../../../../specs/expression_evaluation.md#mixed-canonical-numeric-ordering--2026-10-19)
selects canonical int-to-float conversion followed by floating comparison. Rounding
is observable: `9007199254740993 > 9007199254740992.0` is false and `<=` is true.
This agrees with existing runtime mixed comparison semantics; it is not exact
mathematical ordering of the original integer and float. NaN makes all four
comparisons false, signed zeros compare equally and infinities retain float ordering.
No new rounding-mode controls are introduced.

`Floating_Point_Operators::is_ordering` distinguishes ordering from equality. Its
comparison preparation permits canonical int operands only for ordering, retaining
explicit integer-to-floating conversion facts. Equality retains exact-float admission.
Guarded preparation already extracts operands independently and delegates to this
owner. Existing ordered emission snapshots, extracts and converts left before RHS
is evaluated. Unavailable carriers throw on extraction; left failure suppresses RHS.
No runtime, emitter or retained-model changes. Equality, identity, spaceship,
bool/numeric ordering and additional fixed-width domains remain separate.

## Verification

The focused runner executes the eight scalar signatures first, then 24 carrier-pair
tasks. All 200 signatures passed on the first dev002 attempt. The proof checks:

- Exact signature/result types, ordered extraction payloads, explicit identity versus
  int-to-float conversions, absence of truth conversions, and cleanup of prepared facts.
- Signed finite values, fractions, +0/-0, infinities and NaNs; ±2^53 and neighboring
  integers/floats; int64 extrema and nearby representable float values. Independent
  native float-domain expectations and explicit source rounding anchors verify the
  approved precision rule.
- Present/empty/error/sentinel combinations, both bool sentinels and two structured
  error metadata variants. Source failures use distinct left modulo_by_zero and
  right division_by_zero errors. Counters and trace verify once-only evaluation,
  left-before-right extraction and source exception precedence; inputs are unchanged.
- RHS mutation of the left local/property, replacement with unavailable states,
  preserved old-left snapshots, and property-owner rebinding. Owner observations
  verify original and replacement objects remain intact and RHS rebinding occurs
  only after successful left extraction. There is no comparison write-back.
- Fifty retained failure/retry/repair/no-op/fresh recovery sequences, one per ordered
  concrete type pair. All four spellings independently receive preparation/native
  coverage; recovery counts are not inflated to 200.
- Mixed equality/identity/spaceship and unsupported bool/string/fixed-width mixed
  ordering controls remain rejected. Existing int-width comparison capabilities
  are not incorrectly classified as O1 exclusions.

The selected focused proof records 745,768 native observations. All 633 selected
runner commands return their expected exits. All 22 selected regression suites and
14 existing property-mutation programs pass.
The initial float-operators recovery fixture treated `float < int` as an invalid
edit. That expression is now approved, so its invalid edit was changed to `float < bool`;
the final suite passes. The initial failure is retained in the archive. No compiler
correction was needed after the successful focused proof. Other stale mixed-ordering
negatives were retired while preserving equality and spaceship controls.

Toolchain: PHP 8.5.11 (CLI fallback for CLI/FPM mismatch), Clang 18.1.3,
Python 3.12.3. All compiler tests ran on dev002. No runtime implementation changes,
full matrix rerun or native compiler rebuild. No blocker remains within O1 scope.

## Evidence and accounting

[Results/signatures](mixed_ordering_results.json), [runner archive](mixed_ordering_evidence.json.gz),
[final source manifest](mixed_ordering_source_final.json), [initial manifest](mixed_ordering_source.json),
[baseline](mixed_ordering_baseline.json), [completion audit](mixed_ordering_completion_audit.json).
Remote integrity covers 6,236 source files; historical evidence is unchanged.

Reproduce: `python3 compiler/my-try/tests/mixed_ordering.py --results FRESH` on dev002.

O1 adds 192 approved carrier signatures beyond the original completed core. V-L07
now has 24 slices: 1,872 binary, 64 unary and 336 compound signatures, **2,272 total**.
The original proposed core remains **2,080/2,080 (100%)**; it is not retroactively
expanded. The eight scalar O1 prerequisites are counted separately.
The [remaining inventory](vl07_inventory/README.md) now has **1,680 design/scope
candidates**, plus explicit wider domains. V-L07 remains partial and overall issue
closure stays 19/64 (29.7%). Further work requires discussion and authorization.
