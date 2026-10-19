# Full generated matrix on v0.2 — 2026-10-17
Doc Status: planning

Later [scalar admission follow-up](v0_1_compatibility_scalar_admission.md): all 48
ordinary strict operator fixtures now compile, resolving S03; 60 affected matrix
rows pass their existing oracles. Counts below retain their dated audit meaning.

The full emitted matrix was executed on `alex-ai@dev002.travelfuse.ro`, in the
isolated `/home/alex-ai/operator-audits/20261017-v02` snapshot, using **32 workers**.
No matrix tests ran on WSL. The snapshot is based on `v0.2/develop` at
`d675aca8251374555c9191067793175b43d8a79c`, including the existing S01 header fix.
No compiler or runtime fixes were made for this audit.

## Results

Of **11,813 positive cases**, **7,782 match their value/output oracle**;
**3,879 stop during generation** and **152 reject at runtime**. There are no
positive output mismatches after successful execution and no generated-C++ build
failures. This is an adapted strict-source result, not unchanged legacy-source
compatibility.

The 152 runtime differences split into **56 intentional compound-assignment
changes** and **96 table-comparison contract/deferred-support cases**. The 3,879
generation blockers include missing operator admission and prerequisites which
prevent the operator itself from being tested. They must not all be called
confirmed v0.1 regressions.

## Corpus and authority

The current generator produces **22,816 enabled source tests** from **22,855
semantic rows**, covering 12 families. There are 11,813 positive cases, 10,963
expected runtime failures and 40 expected compile failures. Generator and seed
validation report zero errors and warnings. No native runtime-matrix tests are
emitted by this generator.

The 39 rows without seeds are supported language-probe rows: 14 `empty_keyed`,
13 `empty_value`, and 12 `count_value`. They remain explicit coverage gaps, not
passes. This is the complete *emitted* corpus, not every possible operand/type pair.
The 1,038 checked-in matrix fixtures cover only equality/inequality; they are not
used as a proxy for the full matrix. The type universe contains canonical scalars,
`mixed`, and scalar nullable/result families; it does not cover v0.2 `dynamic` or
fixed-width integer pairs. Their separate focused proofs remain in the operator
checkpoint. Power (`**`), concatenation (`.` / `.=`), spaceship (`<=>`),
logical `xor`, and word-form logical aliases are not distinct generated matrix
items either; this run makes no new exhaustive claim for them.

Matrix expectations come from this snapshot's generator/data. v0.1 is not freshly
executed for these 22,816 cases, so a positive matrix failure is not automatically
a newly baseline-proved regression. The [ordinary strict operator checkpoint](v0_1_compatibility_operators.md)
separately proves S03 against pinned v0.1.

## Test lanes and adapters

The original lane uses every generated source unchanged, with its original
metadata. Legacy comment declarations and the missing source-level `var_dump`
binding can mask the underlying operation. Such results are source-admission
failures, not evidence that every tested operator is missing.

The strict lane retains each seed, operands, expression and expected result. It
converts local and property comment declarations into strict declarations.
Observation is test-only: a source echo site lets the compiler prepare and lower
the expression; its generated `echo_one` call is replaced by the existing native
`scpp::php::var_dump` helper. The uninstrumented and instrumented artifacts are
both retained. This preserves result types and wrapper observations without adding
a `var_dump` binding or changing compiler/runtime implementation. It proves the
operator through an observation adapter, not unchanged-source compatibility.

The first sweep used a plain boolean observer and discarded non-boolean results.
Follow-up replays supersede those limited observations. Previously reached runtime
failures are checked using the existing executables with `SCPP_ERROR_FORMAT=json`,
avoiding redundant compilation. The existing metadata's structured error fields
and error fragments must match; when metadata requires only runtime failure, that
weaker oracle remains explicit. Signals/timeouts are not counted as matching
normal runtime-error oracles. Earlier generation/compile rejection of an expected
runtime-failure case remains a separate semantic-review result.

The first pilot exposed a server environment issue: default PHP lacked mbstring.
It is excluded from compiler results. The tested compiler host is installed
`/usr/bin/php8.2` with mbstring. PHP-FPM/CLI version mismatch causes the shared
runner's normal CLI fallback. The compiler is PHP-hosted; generated programs and
runtime objects are compiled, not the compiler itself. PCH is disabled.

## Full result ledger

| Strict adapted outcome | Cases |
| --- | ---: |
| Positive output oracle matches | 7,782 |
| Positive generation blockers | 3,879 |
| Positive runtime differences | 152 |
| Expected runtime rejection matches | 6,020 |
| Negative cases rejected during generation, requiring disposition | 4,971 |
| Expected compile rejection instead reaches runtime rejection | 11 |
| Expected compile rejection now valid under v0.2 | 1 |
| **Total** | **22,816** |

The 6,020 runtime matches contain **102 structured diagnostic matches** and
**5,918 coarse failure-only matches**. The latter prove normal nonzero termination,
not a particular error code. No signal/timeout is credited as a match.
Of the 4,971 generation rejections, **793 are specifically confirmed approved
early rejection of `result_or_bool` coalesce**. The other 4,178 need semantic
review; an earlier unrelated blocker is not a passed negative test. The 11
compile-versus-runtime differences are keyed `unset` on invalid active mixed
kinds; their correct rejection stage needs reconciliation with the current
value-dependent diagnostic policy.

| Family | Total | Positive pass | Positive blocked/different | Negative runtime match | Other negatives |
| --- | ---: | ---: | ---: | ---: | ---: |
| `casts_explicit` | 160 | 83 | 27 | 32 | 18 |
| `condition_truthiness` | 34 | 32 | 0 | 2 | 0 |
| `language_probes_and_reset` | 95 | 55 | 0 | 0 | 40 |
| `operators_binary_arithmetic` | 1,435 | 74 | 162 | 926 | 273 |
| `operators_binary_bitwise` | 1,215 | 20 | 100 | 0 | 1,095 |
| `operators_binary_logical` | 690 | 320 | 128 | 80 | 162 |
| `operators_comparison_equality` | 1,038 | 304 | 734 | 0 | 0 |
| `operators_comparison_ordering` | 1,292 | 80 | 276 | 720 | 216 |
| `operators_compound_assignment` | 6,500 | 320 | 572 | 3,704 | 1,904 |
| `operators_conditional_selection` | 9,175 | 5,984 | 1,434 | 494 | 1,263 |
| `operators_strict_identity` | 1,038 | 458 | 580 | 0 | 0 |
| `operators_unary` | 144 | 52 | 18 | 62 | 12 |

## Grouped causes and disposition

These are disjoint **first observed blockers**, not a promise that fixing one
owner clears every affected case. Counts below cover the 4,031 positive failures.

| Group | Cases | Disposition / next owner |
| --- | ---: | --- |
| Integer-only binary candidate admission | 1,164 | Missing admitted scalar/operator combinations, including bool equality S03 and float arithmetic; shared operator preparation. |
| Canonical-int-only binary candidates | 592 | Lifted nullable/Result integer combinations blocked; operator preparation and wrapper operand policy. |
| Canonical-bool-only logical candidates | 128 | Non-bool scalar combinations blocked; reconcile admission with truthiness semantics. |
| Boxed operators outside numeric slice | 42 | Unsupported boxed operator combinations; shared boxed preparation. |
| Canonical unary operands / mutation storage | 16 | Eight unary and eight mutation cases; operand/storage preparation. |
| Result extraction at selection sites | 360 | Unsupported extraction context masks the selected operation; wrapper/selection preparation. |
| Coalesce type matching / supported scalar restriction | 432 | 299 exact-type and 133 scalar-domain blockers; conditional selection preparation. |
| Boxed selection family/branch restriction | 206 | Unsupported mixed/wrapper branch combinations; selection preparation. |
| Nullable payload boundaries | 193 | 166 scalar/record-boundary and 27 same-payload restrictions; boundary preparation. |
| Bare `error` constant | 326 | Deferred source syntax/migration prerequisite; not evidence the underlying operator is absent. |
| Result construction | 240 | Exact payload / wrapper / sentinel construction prerequisite; review against current wrapper contract before proposing operator changes. |
| Object default construction | 180 | Typed property adaptation still leaves non-default-constructible fields; fixture initialization / construction contract prerequisite. |
| Mixed int-to-float compound write-back | 56 | **Intentional v0.2 behavior**, not missing arithmetic support. |
| Mixed table equality/inequality | 96 | Deferred/current-contract discrepancy; separate table-comparison review. |

The first nine rows total **3,133 operator/boundary admission blockers**. The next
three total **746 source/construction prerequisites**. Current bounded compiler
support alone does not authorize permanent language incompatibilities. Except
for S03's earlier pinned-baseline proof, these are matrix support gaps requiring
contract review, not newly established unchanged-source v0.1 regressions.

### Intentional changes and contract review

- **56 positive compound cases:** the [separated mixed/dynamic contract](../../../../specs/dynamic_types.md)
  preserves the stored numeric kind for compound updates. An int payload cannot
  accept a float result, even if integral. Restore neither old widening nor an
  implicit kind change to make these matrix expectations green.
- **793 negative coalesce cases:** [conditional-expression policy](../../../../specs/conditional_expression_matrix.md)
  explicitly requires preparation rejection of statically known `result_or_bool`
  on either side. Another 422 cases with that matrix diagnostic class stop first
  at Result construction (390) or bare `error` (32); they do not prove the intended
  coalesce rejection yet.
- **One negative keyed-unset case:** [keyed removal](../../../../specs/array_semantics.md)
  makes a null parent a no-op. Manifest index 22782 now executes successfully and
  observes `bool(false)` for subsequent `isset`; its old compile-failure oracle
  is stale.
- **96 table comparisons:** eight table/table cases report missing deep comparison;
  88 table/scalar cases reject the active kind pair. The current mixed contract
  defers owned-table equality, so these need matrix/contract reconciliation and
  a separately agreed table-comparison slice, not an automatic operator patch.
- **326 bare-error prerequisites:** the [structured error contract](../../../../specs/error_values_and_take.md)
  provides `error::make(...)`; the audit does not silently rewrite the old bare
  sentinel or wrapper construction. Such migration is a separate probe.

## Retained evidence

The [machine-readable summary](v0_1_compatibility_operator_matrix_results.json)
contains family counts, exact first-blocker groups, representative seed IDs,
phase counts, toolchain and origin hashes. The [compressed case ledger](v0_1_compatibility_operator_matrix_evidence.json.gz)
retains all 22,816 original/adapted sources and hashes, expectations, results,
previous attempts, source snapshots' hashes and the manifest. Source hashes and
unique-case counts were checked when consolidating the evidence.

Complete generated artifacts, binaries and command journals remain under the
isolated remote directory. The four evidence phases are `full`,
`property-replay`, `observation-replay`, and `diagnostic-replay`; later attempts
supersede only their selected strict cases. Initial harness-only failures
(empty JSON arrays and the runtime JSON error envelope) were corrected and the
entire 6,020-case diagnostic replay repeated. They are not compiler failures.
The environment used PHP 8.2.34, Clang 18.1.3 and Python 3.12.3.

## Reproduction

Use an isolated snapshot of the current compiler, runtime, portability helpers,
matrix generator and semantic data. The generator writes its test directories;
do not regenerate over a checkout whose existing fixtures must be preserved.
Within that isolated snapshot:

```sh
php -d memory_limit=2G tools/operator_matrix/generator.php
python3 -B compiler/my-try/tests/operator_matrix_audit.py --php /usr/bin/php8.2 --jobs 32 --native-observer --results /path/to/FRESH_RESULTS
```

`--indices FILE` replays a JSON array of original manifest indices. Test selection
and declaration/output adaptations are retained per case. Exit zero means the
sweep recorded its results; it does not mean every matrix oracle passed.

The existing S01 `<cstdint>` header fix is present in the snapshot. No compiler
or runtime fixes are part of this matrix audit; original test expectations remain
unchanged. LLVM, STAN and native compiler rebuilding are outside this run.
