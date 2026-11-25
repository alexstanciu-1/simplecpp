# V-L03: ordinary string comparisons
Doc Status: planning

Implemented and verified on 2026-10-19. No remaining blockers in the approved scope.
This is implementation evidence, subordinate to the semantic specifications.

## Scope and implementation

All seven exact string/string forms: `==`, `!=`, `<`, `<=`, `>`, `>=`, `<=>`.
Equality compares complete bytes; ordering is lexicographic by unsigned byte value.
Embedded NUL does not terminate comparison. No numeric-string coercion, locale
collation or Unicode normalization applies. Six comparisons return canonical bool;
spaceship returns canonical int exactly -1, 0 or 1.

`04_analyze/operators/strings.php` owns exact admission and identity operand
conversions. It publishes named operations in the existing operator decision model;
`preparation.php` dispatches the exact pair, and `boot.php` loads the owner.
The C++ eager renderer first copies both source values in left-to-right order and
then compares their `native_value()` byte sequences. Existing normalized spaceship
emission is shared with integers. Source expressions are never repeated, and a
right-side write cannot change the saved left value.

This follows the [eager evaluation contract](../../../../specs/expression_evaluation.md)
and the configured string `lexicographic_compare_via_native_value` API in
[runtime configuration](../../../../runtime/specs/config.json). The same-type
string equality/ordering matrix supplies additional bounded evidence. The user
explicitly approved all seven forms and byte semantics, including spaceship.
Strict identity retains its existing path and focused regressions.

Two obsolete ordinary-string rejection cases were removed from `string_identity.php`
and replaced by the complete positive comparison proof. Cross-type strict identity
and bool ordering rejection controls remain. Runtime files were not changed.
Wrappers, mixed-type comparisons and table semantics remain separate.

## Acceptance

The permanent runner is `compiler/my-try/tests/string_comparisons.py`, with PHP
preparation and generated-source fixtures in `string_comparisons.php`.

| Requirement | Passing proof |
| --- | --- |
| All seven operations and exact types | Prepared identities/conversions and native static type assertions; six bool results and int spaceship |
| Byte semantics | 18 inputs: empty, equal/different, prefixes, case, numeric-looking strings, embedded NUL, 0x7f/0x80/0xff and distinct Unicode encodings; independent unsigned-byte loop supplies expected order |
| Once-only order and exceptions | 9,072 cases: 18 x 18 pairs x seven operations x four throwing combinations; independent LHS/RHS counts, traces 1 or 12, exact division_by_zero errors |
| Earlier effects persist | Each helper increments its count and trace before throwing; failure observations assert those values |
| Saved LHS value | 4,536 cases: each pair and operation under local/reference-parameter mutation and object-property mutation; result follows original LHS bytes |
| Source literals | Numeric-looking, case and Unicode distinction checks run through authored source |
| Excluded domains | 84 negative pair cases: int, float, bool, nullable string, Result string, falseable Result string in both orders for every operation; no artifacts on rejection |
| Retained preparation | Each operation checks cleanup, invalid type edit, repeated failure, repair with reversed operands, no-op and fresh-output equivalence |

## dev002 runs

Isolated source root: `/home/alex-ai/string-l03/20261019-01` on
`alex-ai@dev002.travelfuse.ro`. All executable tests ran there using shared runners;
CLI fallback was selected because PHP CLI/FPM versions differ. No WSL test run.
Concurrent runner limits stayed below 32 (at most 25 configured slots).

Commands from that root used
`PATH=/home/alex-ai/language-verification/20261018/verification/modern-bin:$PATH`:

```sh
python3 compiler/my-try/tests/string_comparisons.py --results verification/focused-03
python3 compiler/my-try/tests/string_identity.py --results verification/string-identity
python3 compiler/my-try/tests/scalar_admission.py --results verification/scalar --jobs 12
python3 compiler/my-try/tests/float_operators.py --results verification/float
python3 compiler/my-try/tests/numeric_logical.py --results verification/numeric-logical
```

| Suite | Outcome |
| --- | --- |
| V-L03 focused | Pass, 6.94 seconds; one generated native program |
| Strict string/bool identity | Pass; three PHP suites and two native programs |
| Scalar admission/sequencing | Pass; ten PHP suites and seven native programs |
| V-L04 float operators | Pass; ten forms and one native program |
| V-L05 numeric logical | Pass; aliases/mixed pairs/laziness and one native program |

The compiler implementation was identical throughout these runs. Initial focused
attempts are preserved as test-authoring failures, not compiler defects:

1. `focused-01` attempted a forbidden reference argument to a heap-backed property.
   The corrected source helper takes the object and mutates its property directly.
2. `focused-02` reached native compilation but the injected observer assumed
   unescaped underscores in emitted function names. The fixture now uses simple
   underscore-free source names. No generated compiler output was patched.
3. `focused-03` passes all assertions with those fixture corrections.

## Evidence and limits

- [Results](string_comparisons_l03_results.json): final suite outcomes and toolchain.
- [Evidence archive](string_comparisons_l03_evidence.json.gz): exact commands, stdout/stderr and all attempts.
- [Initial manifest](string_comparisons_l03_source.json) and [final manifest](string_comparisons_l03_source_final.json): tested source hashes and fixture revisions.
- [Starting baseline](string_comparisons_l03_baseline.json): preserves pre-existing changes.
- [Completion audit](string_comparisons_l03_completion_audit.json): local/tested code agreement and runtime unchanged from baseline.

No runtime implementation changes, full operator matrix rerun or native compiler
rebuild. V-L03 is closed only for the approved exact string domain. Other gaps and
historical audit evidence remain separate; no follow-on work was started.
