# Guarded canonical-float operators
Doc Status: supporting

Completed and verified on dev002 on 2026-10-19. No blocker remains in the approved
slice. V-L07 remains partially repaired; the original audit and previous
[integer](guarded_wrappers_01.md) and [boolean-sentinel](guarded_wrappers_02.md)
reports remain historical evidence.

## Scope and implementation

All four carriers (`?float`, `result<float>`, `result_or_false<float>`,
`result_or_bool<float>`) admit unary `+ - !`, arithmetic `+ - * /`, ordering
`< <= > >=`, and lazy `&& || and or`. Binary pairs use the exact same carrier or
that carrier with plain canonical float in either order: 144 binary signatures
and 12 unary signatures. Arithmetic/sign results are float; logical/ordering
results are bool. No payload promotion is added at this boundary.

`Guarded_Operators` now discovers an explicitly admitted numeric extraction and
retains its actual target type. Its former integer-only helper becomes
`numeric_extraction`; `is_admitted_payload` bounds it to canonical int and float.
Same-carrier/exact-plain-payload admission remains centralized, and the existing
scalar owners decide which operators apply. Inapplicable float remainder, power
and bitwise operators reject through their existing scalar rules. No new retained
structure, emitter branch or runtime change is required.

Nullable-float `!` now requires a payload: absence throws the existing
`invalid_nullable_unwrap_empty` error. Result error/false/true sentinel states throw
the existing required-value extraction error when reached. Neither boolean sentinel
becomes a float value. Eager operations extract and snapshot the left payload before
right effects. Logical operations use the existing lazy emitter and only extract
the right operand when reached. Both signed zero divisors preserve
`division_by_zero`, also with NaN or infinite numerators.

Direct conditions, ternary, Elvis, presence and take preserve their separate
contracts. Boolean-sentinel Result true remains truthy/present and writes only the
boolean take output, leaving the float output unchanged. Payload take preserves the
boolean output. Result take writes its required error output. Coalescing preserves
existing behavior for the first three carriers and rejects boolean-sentinel Result
on either side. These are preservation controls, not new selection semantics.

Authoritative contracts remain [expression evaluation](../../../../specs/expression_evaluation.md),
[conditional selection](../../../../specs/conditional_expression_matrix.md),
[take](../../../../specs/error_values_and_take.md), and the existing runtime lifting
and zero-divisor specifications. Compiler tests do not redefine these contracts.

## Verification

Tests ran only in `/home/alex-ai/guarded-float/20261019-01` on
`alex-ai@dev002.travelfuse.ro`, with PHP 8.5.11, Clang 18.1.3 and Python 3.12.3.
Shared runners used CLI fallback because PHP CLI/FPM versions differ. Two regression
suites ran concurrently (up to 24 task slots), plus the single focused task.

The independent native observers use explicit payload/absent/error/false/true states
and float values -2.5, +0.0, -0.0, 1.5, +infinity, -infinity and NaN. They compare
NaNs by classification and preserve zero signs for payload values and snapshots.

- 35,280 binary value/state/source-failure cases verify exact scalar result types,
  payload results, unwrap errors, zero errors and ordered once-only effects.
- 2,870 RHS replacement cases verify captured left payload/truth before source
  storage is replaced by an unavailable state; both boolean sentinels are covered.
- 99 unary state cases cover all three forms and carriers, with additional
  ordinary-value controls. Independent native required-value controls cover each
  missing-state family; the runtime division helper is checked for both signed zeros.
- All four logical spellings check skipped/reached states and effects. Nested
  nullable logic and word-alias assignment precedence remain covered.
- Condition, ternary, Elvis, presence, take and applicable coalescing controls
  preserve payloads, both sentinel states and structured error fields.
- 1,082 invalid sources cover excluded operators and mixed/cross-carrier pairs,
  with no published/prepared artifacts. Preparation cleanup and 16 retained
  failure/retry/repair/no-op/fresh sequences cover all four carriers.

| Suite | Result |
| --- | --- |
| Guarded float, final | Pass, 12.31 seconds; 144 binary and 12 unary signatures |
| Guarded int | Pass; 228 binary and 16 unary signatures |
| Ordinary float | Pass; six comparisons and four mutations |
| Numeric logical | Pass; all four numeric pairings and aliases |
| Result/extraction/take | Pass; 11 PHP suites, 166 generated programs, two runtime controls |
| Nullable | Pass; 11 PHP suites, 99 generated programs |
| Scalar admission | Pass |

Run `python3 compiler/my-try/tests/guarded_float.py --results FRESH` on dev002 with
`PATH=/home/alex-ai/language-verification/20261018/verification/modern-bin:$PATH`.
Companion runners are `guarded_operators.py`, `float_operators.py`,
`numeric_logical.py`, `results.py`, `nullable.py`, and
`scalar_admission.py --jobs 12`, each with its own fresh results directory.
Generated-program execution does not establish a native compiler rebuild.

Two fixture-authoring failures are retained: the initial take control omitted the
Result error output, and an added independent division control used an unavailable
overloaded operator in its minimal header environment. The latter now includes and
calls the existing runtime division helper directly. The intermediate focused run
passed before that strengthening; the final run passes all controls. The production
compiler file was unchanged across all attempts.

## Evidence and remaining scope

[Results](guarded_float_results.json) and the [evidence archive](guarded_float_evidence.json.gz)
record successful and failed attempts, command exits, toolchain and integrity.
The archive contains 1,947 records and verifies 6,197 source files. The
[baseline](guarded_float_baseline.json), [initial source](guarded_float_source.json),
[final tested source](guarded_float_source_final.json), and
[completion audit](guarded_float_completion_audit.json) distinguish inherited work,
the tested implementation and local documentation closeout. Among existing
executable sources only `04_analyze/operators/guarded.php` changed; the two
`tests/guarded_float` files are new. Runtime implementation and specification hashes
remain identical to this goal's baseline.

Mixed int/float pairs (including plain operands), cross-carrier pairs, equality/
identity/sentinel comparisons, mutation, compounds, other payload types, float
remainder/power/bitwise, spaceship and xor remain separate. V-R10 runtime mutation
debt remains independent. No runtime changes, full matrix rerun or follow-on
implementation were performed.
