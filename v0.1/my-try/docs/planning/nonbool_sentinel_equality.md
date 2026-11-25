# E2NB: non-bool sentinel equality — 2026-10-19
Doc Status: supporting

Implemented and verified on `alex-ai@dev002.travelfuse.ro`, isolated snapshot
`/home/alex-ai/nonbool-sentinel-equality/20261019-01`. All **48 approved signatures**
pass. No blocker remains within that implementation slice. Separate native probes
confirm **24 deferred runtime instantiation failures**; those signatures remain
rejected by compiler preparation. No runtime changes, full matrix rerun or native
compiler rebuild.

## Scope and contract

For each of `result_or_false<T>` and `result_or_bool<T>`, with canonical int, float
or string payloads:

| Arrangement | Operators | Total |
| --- | --- | ---: |
| Matching carrier / matching carrier | `== != === !==` | 24 |
| Carrier / exact plain payload, both orders | `=== !==` | 24 |
| Deferred carrier / exact plain payload, both orders | `== !=` | 24 |

Matching sentinels compare equal and identical. Opposite sentinels differ. A sentinel
is unequal and non-identical to every non-bool wrapped payload, including zero and
empty string. A sentinel is non-identical to a plain non-bool payload. Two present
payloads use scalar comparison semantics. Negated forms invert their corresponding
result. In this bounded slice equality and identity agree for matching non-bool
payloads; this does not generalize to mixed kinds or the completed bool slices.

Float comparisons preserve unordered NaN behavior and equality of signed zero;
storage observers independently check signed-zero and NaN bits. String comparison
is byte equality, including length and embedded NUL. Comparison never changes the
carrier state, extracts an absent payload or suppresses RHS evaluation because the
left operand is a sentinel. Source errors still stop later evaluation.

Semantic references: `runtime/specs/spec.md` §§6.10/7a and
`specs/expression_evaluation.md`. Existing generated runtime comparisons and shared
strict identity are implementation evidence, not substitutes for those contracts.

## Ownership and changes

`Wrapper_Equality` separates carrier recognition from operator/operand-arrangement
admission. The existing exact payload/carrier checks remain. A named admission
predicate excludes non-bool sentinel/plain ordinary equality while admitting
same-carrier comparisons and strict identity. It keeps the existing four operation
facts, whole-state identity conversions and zero extraction facts. No new retained
structure, emitter path or runtime helper was introduced.

The shared fixture adds an explicit non-bool sentinel state oracle. A separate bool
field distinguishes true and false sentinel states without overloading payload bits.
The existing bool-carrier oracle remains independent. Guarded fixtures retire only
newly supported same-carrier equality negatives; the shared pair predicate now takes
the operator so deferred ordinary wrapper/plain equality remains negative.

## Verification on dev002

All six carrier/payload proofs passed on their first attempt (3.66–5.85 seconds per
task). Each verifies eight distinct signatures and eight retained recovery sequences.

| Carrier / payload | Binary/error cases | Local snapshot cases | Property snapshot cases | Negative source checks |
| --- | ---: | ---: | ---: | ---: |
| False-sentinel / int | 1,920 | 7,456 | 3,728 | 160 |
| False-sentinel / float | 6,048 | 41,608 | 20,804 | 162 |
| False-sentinel / string | 11,248 | 105,488 | 52,744 | 160 |
| Two-sentinel / int | 2,304 | 9,864 | 4,932 | 168 |
| Two-sentinel / float | 6,720 | 48,840 | 24,420 | 170 |
| Two-sentinel / string | 12,160 | 118,720 | 59,360 | 168 |

Payloads include integer limits and values around double precision boundaries;
float zero, signed zero, finite extremes, subnormals, infinities and two NaN payloads;
strings include empty, numeric-looking, embedded-NUL, high-byte and distinct Unicode
byte sequences. All states cross with values, source failures and replacements.

Counters and traces prove eager left-to-right evaluation, including either sentinel
on the left. Division failure on the left precedes modulo failure on the right.
Local replacement includes throwing RHS effects; property replacement checks saved
left values and final state. Source construction distinguishes explicit sentinels
from zero/empty payloads. Guarded ordering still rejects each sentinel state.

Preparation asserts complete-state conversions and no extraction. Cleanup, failed
edits, repair, no-op and fresh compilation agree across 48 recovery sequences.
Negative checks retain the deferred 24, cross-family/mixed payloads, fixed-width
payloads and direct sentinel-token comparisons. Repeated negative executions across
fixtures are not additional distinct signatures.

All **15 regression suites** pass: nullable-int equality, nullable bool/string
equality, nullable-float equality, structured-error Result equality, false-sentinel
bool equality, two-sentinel bool equality, guarded float, guarded string, guarded
integer operators, nullable, guarded bool, guarded bool XOR, guarded cross-family,
numeric XOR and eager binary.

## Deferred 24: confirmed runtime blocker

`tests/nonbool_sentinel_deferred.py` instantiates each deferred signature separately
using the existing runtime headers. A same-carrier equality/plain strict-identity
positive control compiles. All **24** ordinary wrapper/plain comparisons fail with
`unsupported operator== combination` for bool versus int/float/string (or reversed).
Both `!=` directions delegate through `==` and encounter the same problem.

The sentinel branches in `runtime/include/scpp/generated/operators.hpp` delegate
to bool/non-bool scalar equality. Template instantiation requires that branch to be
valid regardless of whether a particular wrapper would contain a payload at runtime.
These are compile failures, not observed runtime exceptions or value results.
The observation tasks report success when diagnostics are captured; their success
must not be interpreted as successful comparison compilation.

No runtime implementation or semantic repair was attempted. Before admitting these
24 signatures, separately agree sentinel/plain equality semantics and repair the
runtime's owning generation path. Do not patch generated output or import implicit
PHP truth conversion as a compiler workaround. These remain core targets with a
confirmed prerequisite, not silently reclassified intentional incompatibilities.

## Evidence and progress

[Results](nonbool_sentinel_equality_results.json),
[evidence archive](nonbool_sentinel_equality_evidence.json.gz),
[tested source manifest](nonbool_sentinel_equality_source.json) and
[completion audit](nonbool_sentinel_equality_completion_audit.json) retain provenance.
All **6,225** remote source hashes matched. All **467 runner commands** had expected
exits; the 24 nested native failures are separately recorded observation results.
Local executable files match tested hashes; documentation closeout is separate.
PHP 8.5.11, Clang 18.1.3, Python 3.12.3; CLI fallback due PHP CLI/FPM version mismatch.

V-L07 remains partial: **17 completed slices, 1,656 binary + 32 unary signatures**.
E2NB moves 48 rows to verified, leaving **24 E2 signatures**, **392 core targets**
and **1,872 design/scope candidates**. Proposed core coverage is
**1,688 / 2,080 = 81.2%** by signature count, not engineering effort or all-V-L07
completion. Overall issue closure remains 18/64. See the
[remaining inventory](vl07_inventory/README.md).

Next discuss the 24 confirmed runtime blockers. Cross-family/mixed comparisons,
explicit sentinel comparisons, updates and table semantics remain separate.
