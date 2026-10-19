# Guarded-bool XOR — 2026-10-19
Doc Status: supporting

Implemented and verified on `alex-ai@dev002.travelfuse.ro`: all **24 signatures**
pass. No blockers remain within this slice. V-L07 remains partially implemented.
No runtime changes, full matrix rerun or native compiler rebuild were involved.

## Scope and ownership

Logical `xor` now accepts bool payloads in nullable, `result`, `result_or_false`
and `result_or_bool`: four same-family pairs, twelve ordered cross-family pairs,
and eight wrapper/plain-bool pairs. Every result is plain bool. The existing
plain-bool/plain-bool operation remains a control outside those 24 new signatures.

`Guarded_Operators` admits logical XOR to independent checked extraction. Existing
scalar preparation accepts exact bool operands and rejects numeric/string XOR;
the existing eager renderer computes from ordered bool snapshots. No new retained
structures, scalar decisions or emitter paths were needed.

Both payload false and payload true cause RHS evaluation after successful left
extraction. Absent nullable, Result error and either boolean sentinel are unavailable
payloads, retaining their existing family-specific errors. A left failure stops RHS
evaluation; a right failure preserves earlier effects. Source values are not consumed.
Logical XOR retains its lower-than-assignment precedence.

## Verification

The dedicated runner invokes `guarded_bool.php --xor` through the shared runner.
It validates the exact signature set, covering every ordered pair among plain bool
and the four carriers except the already-supported plain/plain pair.

The dev002 native observer passed:

- 884 binary value/state/source-failure cases: all payload and unavailable states,
  both injected source-call failure flags, exact bool results, once-only counters,
  left-to-right traces, error precedence and unchanged structured error fields.
- 307 local replacement/state cases, including early left failure and right
  extraction failure after a visible replacement.
- 132 successful property snapshot cases, plus 20 existing wrapper/plain local
  snapshot controls. RHS replacement cannot change the captured left bool.
- 13 unary controls preserving existing negation behavior.
- Assignment/XOR and explicitly grouped assignment controls over every bool payload
  pair for each carrier.
- 24 retained edit sequences: successful preparation, invalid equality edit, repeated
  failure with empty artifacts, operand-reversal repair, no-op stability and fresh
  equivalence. Existing AND/OR recovery and selector/take controls also remain.

Both sentinel states of `result_or_bool<bool>` remain distinct from payload false
and true. Eager reach is tested for either left payload value; it must not inherit
AND/OR short-circuit behavior. The property checks cover successful operations;
local checks provide the failing-replacement state observations.

All four companion suites passed: `guarded_bool`, `guarded_cross` (the existing 660
signatures), `eager_binary` and `scalar_admission`. The cross suite also exercises
numeric/string XOR exclusions. Bool/numeric pairs, equality/identity, mixed numeric
ordering, updates and spaceship remain rejected in the corresponding focused tests.

The shared snapshot helper now accepts plain operands for the new wrapper/plain
proofs. Its bool-XOR exclusion is retired only for exact bool payload types; family
names such as `result_or_bool<int>` do not qualify. The ordinary bool fixture retires
only its four newly valid same-family XOR negatives. Earlier evidence is unchanged.

## Provenance

Remote snapshot: `/home/alex-ai/guarded-bool-xor/20261019-01`.
Toolchain: PHP 8.5.11, Clang 18.1.3, Python 3.12.3. PHP used shared-runner CLI fallback
because installed CLI/FPM versions differ. Tests ran only on dev002.

```sh
PATH=/home/alex-ai/language-verification/20261018/verification/modern-bin:$PATH \
  python3 compiler/my-try/tests/guarded_bool_xor.py --results verification/focused-01
```

Each companion suite ran with its own fresh `verification/<suite>` directory;
`scalar_admission` received `--jobs 12`. Two bounded regression runners used at most
24 task slots. The focused proof passed on its first attempt in 9.07 seconds.
Remote integrity verified 6,206 source files; 226 evidence records were archived.

See [results](guarded_bool_xor_results.json), [evidence](guarded_bool_xor_evidence.json.gz),
[baseline](guarded_bool_xor_baseline.json), [source](guarded_bool_xor_source.json),
[final source](guarded_bool_xor_source_final.json), and
[completion audit](guarded_bool_xor_completion_audit.json). Documentation closeout
is separate from the tested executable snapshot.

## Remaining work

Numeric/string XOR, equality/identity and sentinel comparisons, mixed numeric
ordering, updates/compounds, spaceship and additional payload/operator domains
remain separate. V-R10 runtime mutation debt is independent. Overall issue closure
remains 18/64 (28.1%) with V-L07 partial; this slice does not close that whole issue.
