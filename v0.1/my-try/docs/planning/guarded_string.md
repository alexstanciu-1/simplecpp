# Guarded string ordering
Doc Status: supporting

Completed and verified on dev002 on 2026-10-19. No blocker remains in the approved
slice. V-L07 remains partial. Overall issue repair is still 18/64 fully closed
(28.1%), one partial and 45 unrepaired: approximately 30% by issue count allowing
for partial work, not an engineering-effort estimate. See
[overall repair progress](language_repair_progress.md) for the complete denominator.

## Scope and implementation

For `?string`, `result<string>`, `result_or_false<string>` and
`result_or_bool<string>`, `< <= > >=` now admit same-carrier and carrier/plain-string
pairs in either order: 48 binary signatures returning plain bool.

Guarded payload admission now receives the operator kind. Strings participate only
in the four approved ordering operations; numeric and boolean admission remains
unchanged. This prevents accidentally redirecting nullable-string ! through an
unsupported ordinary string negation path. Existing String_Operators provides the
comparison decisions and identity conversions. Existing extraction and eager
emission capture the checked left string value before evaluating the right operand.
No new retained model, emitter branch or runtime change is needed.

Ordering is complete-byte lexicographic ordering, with unsigned-byte expectations,
no numeric coercion, no Unicode normalization and no locale collation. Empty and
numeric-looking strings are valid payloads. Unavailable nullable/error/false/true
sentinel states retain existing extraction errors when reached. Result extraction
keeps its existing std::runtime_error surface; nullable extraction retains
invalid_nullable_unwrap_empty.

Direct conditions, ternary, Elvis, coalescing, presence and take preserve their
separate contracts. Boolean-sentinel Result coalescing still rejects on either
side. Existing nullable-string ! remains condition negation, including its
empty-state behavior; guarded string negation is a separate unresolved domain.

The [runtime lifting contract](../../../../runtime/specs/spec.md),
[operator semantics](../../../../specs/operator_matrix/profile_semantics_v1.md),
[expression evaluation](../../../../specs/expression_evaluation.md) and
[conditional matrix](../../../../specs/conditional_expression_matrix.md) remain
authoritative. Earlier string/scalar and guarded reports are historical evidence.

## Verification

All tests executed on `alex-ai@dev002.travelfuse.ro`, snapshot
`/home/alex-ai/guarded-string/20261019-01`. Toolchain: PHP 8.5.11, Clang 18.1.3,
Python 3.12.3. Shared runners used CLI fallback because PHP CLI/FPM versions differ.
Two regression suites ran concurrently (up to 24 tasks); dependencies within each
fixture remained ordered.

The independent oracle walks unsigned bytes and compares lengths after equal
prefixes. Eighteen payload inputs cover empty strings, equality/differences,
prefixes, case, numeric-looking strings, embedded NUL, 0x7f/0x80/0xff, and distinct
Unicode encodings. Explicit states represent absence, structured error and both
boolean sentinels.

The focused proof reports:

- 68,080 binary value/state/source-error cases: all signatures, exact bool types,
  string extraction/identity conversion facts, ordered once-only effects, error
  precedence, and unchanged source states/error fields.
- 23,328 local/reference and property snapshot cases. RHS replaces the left wrapper
  with another string payload or an unavailable state; ordering still uses the old
  bytes. Both boolean sentinels and final storage state are checked.
- Independent native extraction controls for every unavailable-state family;
  condition/selector/presence/take preservation, including output preservation and
  Result error fields. Nullable-string ! is explicitly checked on its old path.
- 642 rejected sources for excluded domains/operators and cross-family pairs;
  preparation cleanup and 16 retained failure/retry/repair/no-op/fresh sequences.
  Invalid sources leave neither prepared nor published artifacts.

| Suite | Result |
| --- | --- |
| Guarded string focused | Pass, 10.78s |
| Ordinary string comparisons | Pass; all seven forms |
| String identity | Pass |
| Guarded int | Pass |
| Guarded float | Pass |
| Guarded mixed numeric | Pass |
| Guarded bool | Pass |
| Result/extraction/take | Pass; 11 PHP suites, 166 generated programs, two runtime controls |
| Nullable | Pass; 11 PHP suites, 99 generated programs |

Reproduce with `python3 compiler/my-try/tests/guarded_string.py --results FRESH`,
using `PATH=/home/alex-ai/language-verification/20261018/verification/modern-bin:$PATH`
on dev002. Companion runners are `string_comparisons.py`, `string_identity.py`,
`guarded_operators.py`, `guarded_float.py`, `guarded_mixed.py`, `guarded_bool.py`,
`results.py` and `nullable.py`, each with a fresh results directory. Every attempt
passed; tested source remained unchanged throughout. No native compiler rebuild
is claimed from generated-program execution.

## Evidence and remaining scope

The ordinary string fixture retires only 24 newly admitted wrapper/plain ordering
negatives. Equality, identity, spaceship and other invalid combinations remain.
The new positive fixture covers these forms plus boolean-sentinel Result strings.
Historical counts/manifests/evidence are unchanged.

[Results](guarded_string_results.json) and [evidence](guarded_string_evidence.json.gz)
record commands, observed counts, source integrity and toolchain. The archive contains
1,897 records and verifies 6,203 source files. The
[baseline](guarded_string_baseline.json), [initial source](guarded_string_source.json),
[final tested source](guarded_string_source_final.json) and
[completion audit](guarded_string_completion_audit.json) separate inherited work,
tested code and documentation closeout. Existing executable changes are limited to
the guarded owner and ordinary string fixture; the guarded-string fixture/runner
are new. Runtime implementation and specification hashes match the baseline.

Equality/identity, logical/unary string operators, spaceship, concatenation,
cross-family pairs, mutation and compounds remain separate. V-R10 runtime mutation
debt remains independent. No runtime changes, full matrix rerun or follow-on
implementation occurred.
