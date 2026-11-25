# Guarded result_or_bool<int> operators
Doc Status: supporting

Completed on 2026-10-19 on dev002. The approved extension is implemented and
verified with no remaining blocker in this slice. V-L07 remains partially repaired.
The [first-slice report](guarded_wrappers_01.md) and original verification evidence
remain historical and unchanged.

## Scope and ownership

`Guarded_Operators::integer_extraction` now accepts the boolean-sentinel Result
role with canonical-int payloads through the same `result_extract` decision used
by the other Result families. This removes the explicit family exclusion; no new
retained model, emitter branch, runtime code or configuration is needed.

The added forms are unary `+ - ~ !` and binary `+ - * / % ** < <= > >= & | ^ << >>
&& || and or`. Binary admission covers the same carrier and plain canonical int
in both orders: 57 new binary signatures and four unary signatures. Cumulative
coverage is 228 binary and 16 unary signatures across nullable, Result, falseable
Result and boolean-sentinel Result canonical-int carriers.

Both false and true sentinels have no integer payload. A reached guarded operation
uses existing `require_value` and throws its existing `std::runtime_error` surface.
The true sentinel does not become integer one. Results remain ordinary int/bool,
selected by the payload operator. Eager operations snapshot the checked left
payload before evaluating the right operand. Logical operations only evaluate and
extract the right operand when required. Division and modulo retain distinct
zero-error codes.

Direct conditions, ternary, Elvis, presence and take retain their own semantics:
true sentinel is truthy/present; take returns true and writes the boolean flag but
leaves the integer output untouched. False sentinel returns false from take.
Payload take leaves the boolean output unchanged. `result_or_bool` coalescing
remains statically rejected on either side, as required by
[the conditional matrix](../../../../specs/conditional_expression_matrix.md).

## Verification

Only dev002 executed tests, in `/home/alex-ai/guarded-02/20261019-01`.
The shared runners used CLI fallback because available PHP CLI/FPM versions differ.
Two regression suites ran concurrently, at most 24 task slots; the focused suite
adds one task. Toolchain: PHP 8.5.11, Clang 18.1.3, Python 3.12.3.

The focused fixture now models payload, absence, error, false sentinel and true
sentinel explicitly. Negative integers are payloads, not missing-state markers.
Independent native observers cover:

- 20,140 binary value/state/error cases, including 6,088 for the new carrier;
  exact scalar result types, once-only effects and left-to-right traces.
- 1,390 RHS replacement cases; either boolean sentinel can replace the LHS after
  its payload/truth snapshot. Lazy skip/reach is checked for both symbolic and
  word aliases, with independent source-call and extraction failures.
- Unary payloads -2, 0, 2, 3 and all unavailable states; independent native
  extraction controls for both sentinels. Existing nullable 7 and Result -7 probes remain.
- Condition/selector/presence preservation and take with both initial flag values;
  unchanged source states and original Result error fields.
- 206 invalid sources, preparation cleanup, and 16 retained failure/retry/repair/
  no-op/fresh edit sequences across all four carriers.

Negative exponents and negative shift domains are excluded from the independent
native arithmetic oracle, to avoid conflating unrelated scalar semantics with
carrier extraction. Both sentinel guards are covered with valid scalar partners.

| Suite | Result |
| --- | --- |
| Focused final | Pass, 10.94 seconds |
| Results/extraction/take | Pass: 11 PHP suites, 166 generated programs, two runtime controls |
| Nullable | Pass: 11 PHP suites, 99 generated programs |
| Control flow | Pass: 12 PHP suites, 135 generated programs |
| Scalar admission | Pass |
| Numeric logical | Pass |

Reproduce with `python3 compiler/my-try/tests/guarded_operators.py --results FRESH`
and the corresponding `results.py`, `nullable.py`, `control_flow.py`,
`scalar_admission.py --jobs 12`, and `numeric_logical.py` runners, using the modern
PHP PATH on dev002. No full matrix or native compiler rebuild was performed.

The first focused attempt reached native execution and exited 43: the new take
observer incorrectly expected true sentinel to return false. Existing take tests
and semantics confirmed the expectation error. Only the fixture changed. The
second attempt passed; final strengthened preservation checks cover both initial
flag values. All attempts are retained in the archive.

## Evidence and remaining work

[Results](guarded_wrappers_02_results.json) record summaries, counts and checksums.
[Evidence](guarded_wrappers_02_evidence.json.gz) contains 2,582 log/integrity records;
6,195 source files match the tested snapshot. The
[baseline](guarded_wrappers_02_baseline.json),
[initial source](guarded_wrappers_02_source.json),
[final tested source](guarded_wrappers_02_source_final.json), and
[completion audit](guarded_wrappers_02_completion_audit.json) separate inherited
work, this implementation and local documentation closeout. Only the guarded
admission file and two focused test files changed among executable source files.
Runtime implementation and specification hashes are unchanged from this goal's baseline.

Equality/identity and sentinel comparison extensions, mutation, compounds,
cross-carrier pairs, other payload types, spaceship and xor remain separate.
V-R10 runtime mutation debt remains independent. No follow-on implementation is
part of this goal.
