# V-L04: canonical float comparisons and mutations
Doc Status: planning

Authorized and completed 2026-10-19. Exactly six float/float ordinary comparisons
(`==`, `!=`, `<`, `<=`, `>`, `>=`) and four canonical float prefix/postfix mutations
(`++`, `--`) are implemented and verified on dev002.

## Implementation and boundaries

Floating_Point_Operators owns exact float comparison selection, identity operand
conversions and bool result decisions. Arithmetic's int-to-float conversion does not
apply to comparisons. The existing C++ ordered binary renderer consumes the six
prepared floating operations.

Mutation_Preparation retains existing writable-target validation. The four
non-boxed operation names now describe canonical scalar mutations rather than
integers only; int and float share the established emission path, with their own
result type. Boxed decisions remain separate. The emitter's explicit result-value
cast preserves snapshots: prefix returns updated value, postfix returns previous
value, and neither result becomes addressable or reference-bindable.

No runtime implementation changed. No new target categories were admitted.
Strict float identity, spaceship, int/float comparisons, compounds, wrappers/lifting,
fixed-width mutations and indexed mutation targets remain outside this slice.
Source snapshot semantics do not inherit native C++ prefix-reference semantics.

## Acceptance evidence

| Requirement | Verified evidence |
| --- | --- |
| Six comparison forms, exact domains and bool results | Prepared identity conversions/result types; native static type assertions; 49 operand pairs per form (294 checks) over finite values, signed zero, infinities and NaN. |
| Ordered once-only comparison operands | Each comparison calls left/right effects and requires trace 12; a postfix comparison observes both snapshots and final storage. |
| Four float mutations, returned value and store | Prepared float/non-addressable facts and native static type assertions; each form tested via reference parameter and local copy over seven values (56 checks), including signed-zero result sign and NaN/infinity behavior. |
| Existing property targets | Class/struct fields and static properties exercise all four forms; effectful static qualifiers have a call-count observer; exact returned values and final storage asserted independently. |
| Rejection boundaries | Strict identity/spaceship, cross-type comparison, compounds, nullable/Result mutations, const/non-writable targets, reference binding to mutation results, private fields and indexed targets reject with no artifacts. |
| Cleanup and incremental recovery | Comparison and mutation facts clear on cleanup; invalid signature/const changes reject on first attempt and retry; repaired decisions match a fresh compilation and no-op output. |
| Existing scalar and sequencing behavior | scalar_admission.py: ten PHP suites and seven generated native programs passed. Includes existing mutation, eager binary, integer-width, logical, compound and control-flow checks. |
| Existing property behavior | All 14 property mutation programs and their preparation negatives passed. |
| division_by_zero | Exact-code observer passed six strengthened existing checks plus 18 additional zero cases, covering signed float zero and both int/float operand orders. |

No full matrix rerun or native compiler rebuild is claimed. Existing stale float
rejections were removed only where replaced by this positive proof; the cross-type
comparison negative now checks the exact-float admission diagnostic.

## Reproduction and provenance

Remote source: `/home/alex-ai/float-l04/20261019-01`. PHP 8.5.11, Clang 18.1.3,
Python 3.12.3. All executable tests used shared runners on dev002, with at most 25
concurrent tasks; none ran on WSL. Set the existing modern PHP launcher directory
`/home/alex-ai/language-verification/20261018/verification/modern-bin` on PATH.

```sh
python3 compiler/my-try/tests/float_operators.py --results FRESH_FLOAT
python3 compiler/my-try/tests/scalar_admission.py --results FRESH_SCALAR --jobs 12
python3 compiler/my-try/tests/records.py --suite property_mutations --results FRESH_PROPERTY
python3 compiler/my-try/tests/scalar_error_codes.py --scalar-results FRESH_SCALAR --results FRESH_ZERO
```

[Results](float_operators_l04_results.json) summarize acceptance and exact command
outcomes. [Evidence](float_operators_l04_evidence.json.gz) retains command logs and
outputs. [Final tested sources](float_operators_l04_source_final.json) verify 6,187
file hashes. The [initial transfer](float_operators_l04_source.json) precedes added
float recovery assertions and transfer of the existing zero-error observer. Its
first invocation found the observer absent; no compiler failure was involved.
[Baseline](float_operators_l04_baseline.json) preserves pre-existing changes.

The original language-verification findings and counts remain historical. V-L04
repair status is updated separately. No remaining blocker was exposed within this
scope, and no follow-on implementation is authorized by this closeout.
