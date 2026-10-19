# Guarded boolean logical operators
Doc Status: supporting

Completed and verified on dev002 on 2026-10-19. No blockers remain in this approved
slice. V-L07 remains partially repaired. Earlier numeric reports and original
verification findings remain historical evidence.

## Scope and implementation

All four bool carriers (`?bool`, `result<bool>`, `result_or_false<bool>` and
`result_or_bool<bool>`) admit unary `!` and binary `&& || and or`. Binary admission
covers the same carrier and carrier/plain bool in both orders: four unary and 48
binary signatures, all returning plain bool.

The existing guarded extraction helper is renamed from numeric to scalar and its
explicit payload admission set gains canonical bool. Existing scalar owners still
select valid operations; existing retained extraction decisions and the lazy
renderer are reused. No new retained model, emitter branch or runtime change.

Payload false produces true under `!`; payload true produces false. Absence,
structured error and either boolean sentinel throw existing required-value errors
when reached. This corrects absent nullable-bool negation, extending the earlier
nullable-int/float repair. False/true payloads never become sentinel branches just
because their truth values match those branches.

Source construction and assignment from plain bool create payloads. Explicit
`false_sentinel`/`true_sentinel` create sentinel states. Direct conditions, ternary,
Elvis, presence and take preserve their separate contracts. In particular, payload
false is present and take succeeds; a false sentinel has no payload and take fails.
For boolean-sentinel Result, true sentinel makes take return true and write the
flag while leaving the payload output unchanged. Payload take leaves the flag
unchanged. Boolean-sentinel Result coalescing remains statically rejected on either
side; other carriers retain their coalescing behavior.

These distinctions follow [Result/take semantics](../../../../specs/error_values_and_take.md),
[conditional selection](../../../../specs/conditional_expression_matrix.md) and the
[runtime bool-payload policy lock](../../../../runtime/specs/spec.md). Evaluation
continues to follow [the expression contract](../../../../specs/expression_evaluation.md).

## Verification on dev002

Snapshot: `/home/alex-ai/guarded-bool/20261019-01` on
`alex-ai@dev002.travelfuse.ro`. Toolchain: PHP 8.5.11, Clang 18.1.3, Python 3.12.3.
Shared runners used CLI fallback because PHP CLI/FPM versions differ. Two regression
suites ran concurrently (up to 24 tasks), plus the single focused task.

The fixture explicitly models payload false, payload true, absence, structured
error, false sentinel and true sentinel. The final native observer reports:

- 13 unary state cases and 1,520 binary value/state/source-error cases, proving
  exact bool result types, ordinary bool decisions and checked extraction.
- 80 RHS replacement cases proving capture of the old left truth value before
  replacement with unavailable states, including both boolean sentinels.
- Ordered once-only evaluation, left failures preventing RHS effects, reached
  right failures preserving earlier effects, lazy skip/reach for all four spellings,
  word-alias precedence, nested nullable logic and unchanged source states.
- Independent native extraction-error controls; source literal/dynamic payload
  construction, assignment over every prior state and explicit sentinel construction.
- Condition/selector/presence/take controls, including both initial payload outputs
  and both initial boolean flag values; original structured error fields retained.
- 642 rejected sources covering cross-family pairs, bool/numeric mixtures, equality,
  arithmetic/ordering, mutation, compounds, xor and skipped invalid expressions.
- Preparation cleanup and 16 retained failure/retry/repair/no-op/fresh sequences;
  invalid attempts leave neither prepared nor published artifacts.

| Suite | Result |
| --- | --- |
| Bool focused initial | Pass, 8.80s |
| Bool focused final | Pass, 8.83s |
| Guarded int | Pass; 228 binary, 16 unary signatures |
| Guarded float | Pass; 144 binary, 12 unary signatures |
| Guarded mixed numeric | Pass; 192 binary signatures |
| Ordinary/numeric logical | Pass; includes the ordinary logical PHP suite |
| Result/extraction/take | Pass; 11 PHP suites, 166 generated programs, two runtime controls |
| Nullable | Pass; 11 PHP suites, 99 generated programs |

Reproduce with `python3 compiler/my-try/tests/guarded_bool.py --results FRESH`, using
`PATH=/home/alex-ai/language-verification/20261018/verification/modern-bin:$PATH` on
dev002. Companion runners are `guarded_operators.py`, `guarded_float.py`,
`guarded_mixed.py`, `numeric_logical.py`, `results.py` and `nullable.py`, each with a
fresh results directory. Both focused attempts and all regressions passed; no
existing fixture migration was needed. Production code remained unchanged across
attempts. Generated-program execution does not prove a native compiler rebuild.

## Evidence and remaining scope

[Results](guarded_bool_results.json) and [evidence](guarded_bool_evidence.json.gz)
record command exits, observed counts, toolchain and checksums. The archive contains
1,870 records and verifies 6,201 source files. The
[baseline](guarded_bool_baseline.json), [initial source](guarded_bool_source.json),
[final tested source](guarded_bool_source_final.json), and
[completion audit](guarded_bool_completion_audit.json) separate inherited work,
tested implementation and local documentation closeout. Among existing executable
sources only `04_analyze/operators/guarded.php` changed; the bool fixture/runner are
new. Runtime implementation and specification hashes match the baseline.

Cross-family pairs, bool/numeric combinations, equality/identity/sentinel comparisons,
mutation/compounds, xor and other payload/operator domains remain outside this
admission. V-R10 runtime mutation debt remains independent. No runtime changes,
full matrix rerun, native compiler rebuild or follow-on implementation occurred.
