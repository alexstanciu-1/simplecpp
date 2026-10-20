# U1 and V-R10: canonical numeric carrier updates — 2026-10-19
Doc Status: supporting

Implemented and verified on `alex-ai@dev002.travelfuse.ro`, final snapshot
`/home/alex-ai/carrier-updates/20261019-02`. All **32** prefix/postfix `++`/`--`
signatures pass across nullable, result, result_or_false and result_or_bool with
canonical int/float payloads. **U1 is complete and V-R10 is repaired.** No blocker
remains in this slice. Indexed updates, compounds, new writable-target forms and
other payloads remain separate; no full matrix rerun or native compiler rebuild.

## Contract and ownership

The writable destination is evaluated once. A present/success payload is updated
once through checked mutable access, preserving the carrier's successful state.
An empty nullable, failed Result, false sentinel or true sentinel raises its existing
unwrap error before mutation. The complete destination state remains unchanged,
including structured error message, file and line.

Native prefix returns the original carrier by reference; native postfix returns
an independent old carrier value. Source prefix and postfix both return carrier
value snapshots: updated for prefix, old for postfix. Neither source result is
addressable or aliases storage. Later replacement of the destination cannot change
the result. A successful update remains visible if a later expression fails.

`specs/expression_evaluation.md` records this source contract; runtime spec/config
record the bounded native participation. Integer overflow policy is unchanged:
proofs avoid overflowing updates rather than inventing checked/wrapping semantics.
Float updates retain native numeric behavior, including rounding and nonfinite values.

`Mutation_Preparation` recognizes the four semantic carrier roles and requires exact
canonical numeric payloads. Four wrapper-specific operation variants retain truthful
mutation intent and carrier result types. The existing writable-target protocol and
C++ value-snapshot emission are reused; no new storage or lifetime protocol was added.

The runtime generator emits one bounded participation concept and four overloads.
They reuse the existing shared wrapper traits and checked mutable access, then
scalar increment/decrement. Prefix returns the same carrier; postfix validates
presence before copying and updating. The generated header was produced on dev002.
No hand patch to generated output or implicit unwrap bridge is used.

## Verification

Each carrier/payload group verifies four signatures and four retained recovery
sequences. Final focused tasks passed in 4.09–4.19 seconds each.

| Carrier / payload | Source cases | Native cases | Static cases | Error-order cases | Replacement combinations |
| --- | ---: | ---: | ---: | ---: | ---: |
| Nullable / int | 28 | 28 | 28 | 28 | 192 |
| Nullable / float | 36 | 36 | 36 | 36 | 288 |
| Result / int | 32 | 32 | 32 | 32 | 216 |
| Result / float | 40 | 40 | 40 | 40 | 320 |
| False-sentinel / int | 28 | 28 | 28 | 28 | 192 |
| False-sentinel / float | 36 | 36 | 36 | 36 | 288 |
| Two-sentinel / int | 32 | 32 | 32 | 32 | 216 |
| Two-sentinel / float | 40 | 40 | 40 | 40 | 320 |

Independent state/value observers check every unavailable state, two distinct
structured errors, safe integer boundary-adjacent updates, zero/negative values,
floating signed zero, fractions, a large value where adding one rounds away,
infinities and a quiet NaN. Float storage observation checks bits for saved values
and failed-state preservation. The oracle computes numeric results independently.

Source checks cover locals, reference parameters, instance-property aliases and
static properties. Prefix/postfix results survive later replacement by every
carrier state. Property aliases observe the updated payload and subsequent
replacement. Static qualifiers increment a counter exactly once, including when
payload extraction fails. Null instance owners throw before subsequent effects.
Existing target admission is preserved: temporary instance receivers are not newly
admitted. No new owner-lifetime guarantee is inferred beyond these existing targets.

Ordered source probes place updates before and after a division failure. Failed
payload extraction suppresses the later operand; an earlier source failure prevents
the update; an update completed before a later failure remains committed. There is
no RHS within unary mutation, so compound transaction/alias behavior is not claimed.

Native observers assert prefix reference type and original-object address, postfix
value type and independent old-value storage. Runtime errors preserve unavailable
states. Source preparation checks carrier result type, non-addressability, identity
conversion, no eager extraction facts and cleanup. **32** invalid-const-edit,
retry, repair, no-op and fresh sequences pass. **448 source rejection executions**
retain const/non-addressable/private/indexed/fixed-width/nested/non-numeric boundaries.

The original `tests/runtime/native/test_nullable.cpp` now compiles and executes,
including the previously failing `++value` and `value++`. **80 native rejection
checks** cover all four forms on all four carriers with bool, string, fixed-width,
nested and const payload/storage boundaries.

All **17 final regression suites** pass: float operators, scalar admission, guarded
integer, guarded float, guarded bool, guarded cross-family, guarded mixed, guarded
string, non-bool sentinel equality, two-sentinel bool equality, false-sentinel bool
equality, Result equality, nullable-int equality, nullable-float equality, nullable
bool/string equality, eager binary and numeric XOR. The scalar suite also runs its
existing mutation/compound checks. All **14 existing property-mutation programs**
and their preparation rejections pass, including class/struct and boxed controls.

Two runtime generator runs match after excluding the pre-existing timestamp line.
PHP 8.5.11, Clang 18.1.3, Python 3.12.3; CLI fallback due PHP CLI/FPM mismatch.

## Corrections and provenance

The first fixture attempt failed because generic declarations joined `>` and `=`
without whitespace, and native observers lacked the generated-operator include.
Those fixture issues were corrected without compiler/runtime redesign. The next
focused run passed. Guarded-mixed and guarded-cross regressions then exposed stale
negative expectations for the newly admitted numeric carrier updates. Those rows
were retired; affected mixed/cross/XOR suites passed again. Other boundaries remain
negative. Both failed attempts and successful reruns are retained.

[Results](carrier_updates_results.json), [final evidence](carrier_updates_evidence.json.gz),
[final source manifest](carrier_updates_source_final.json),
[completion audit](carrier_updates_completion_audit.json),
[first fixture evidence](carrier_updates_first_attempt_evidence.json.gz) and
[first source manifest](carrier_updates_source_first_attempt.json) record provenance.
The final archive keeps the initial regression failures and final reruns separately.
Only the mixed fixture's negative list changed after the successful focused proof;
its dependent suites were reverified. No executable implementation changed afterward.
All **6,229** final source hashes matched dev002. All **403 final selected runner
commands** had expected exits. Local executable/contract hashes match the tested
manifest; report/inventory closeout is separate.

## Progress

V-L07 remains partial: **19 completed slices, 1,680 binary + 64 unary signatures**.
Proposed-core coverage is **1,744 / 2,080 = 83.8%**, with **336 C1 compounds** remaining.
The **1,872 design/scope candidates** remain separate. V-R10 closes independently,
raising overall issue closure to **19/64 = 29.7%**; signature coverage is not an
engineering-effort estimate. See the [inventory](vl07_inventory/README.md).

Next discuss C1 destination snapshots, extraction order and write-back. Indexed
updates, mixed compounds, power assignment and other payload domains are outside
this goal and are not silently enabled by the runtime mutation repair.
