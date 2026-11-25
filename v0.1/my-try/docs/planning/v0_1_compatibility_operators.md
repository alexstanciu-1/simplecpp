# Strict operator audit — 2026-10-17
Doc Status: planning

Later [scalar admission follow-up](v0_1_compatibility_scalar_admission.md): all 48
ordinary strict operator fixtures now compile, resolving S03; 60 affected matrix
rows pass their existing oracles. Counts below retain their dated audit meaning.

The separate [full generated matrix audit](v0_1_compatibility_operator_matrix.md)
records 22,816 cases on dev002. Its counts do not change this earlier fixture ledger.

The confirmed original-source gap is **S03: boolean `==` / `!=`**. The focused
behavioral pass executes **29 generated programs successfully**. One broader PHP
suite retains a stale rejection expectation; that suite remains red, as detailed below.

## Original v0.1 fixtures

All **48 ordinary strict operator fixtures** were rerun unchanged against pinned
v0.1 `75de94c71e37060c564b6ade19850b7686bd457a` and the current v0.2 working tree.
**43 compile-compatible; five fail during v0.2 generation.** All 48 meet their v0.1
oracle. These are compile-only tests: they do not establish runtime values, even
where a fixture name mentions echo or return.

The five failures all reproduce **S03**, ordinary boolean equality/inequality:

- `strict_operators_029_bool_eq_return`
- `strict_operators_030_bool_neq_return`
- `strict_operators_035_bool_local_eq_return`
- `strict_operators_057_bool_local_eq_assign_return`
- `strict_operators_058_bool_local_neq_assign_return`

Every failure says `S2S integer binary operation requires integer operands`.
Boolean `==` / `!=` reach the integer candidate path. This is missing source
support, not evidence of an approved semantic removal. Boolean `===` / `!==`
are a separate implemented path; replacing operators is not an unchanged-source fix.
The other fixtures introduce no new cause groups.

Replay with the [48-case manifest](../../tests/legacy_compatibility_strict_operators.json):

```sh
python3 -B compiler/my-try/tests/legacy_compatibility.py --manifest compiler/my-try/tests/legacy_compatibility_strict_operators.json --results /tmp/compat-strict-operators-FRESH
```

## Current v0.2 behavioral suites

| Suite | Result | Proof boundary |
| --- | --- | --- |
| `integer_widths.py` | Pass | Four PHP suite invocations; two generated programs: same-signed widening, unsigned range, comparisons, ordering and zero-divisor errors |
| `string_identity.py` | Pass | Three PHP suite invocations; two generated programs: bool/string `===` and `!==`, truth pairs, ordered snapshots and embedded NUL bytes |
| `compound_effects.py` | Pass | Four PHP suite invocations; three generated programs: old-value snapshots, RHS writes, retained field owners and failure effects |
| `dynamic_families.py --no-pch` | Blocked by one failed expectation | 13 of 14 PHP tasks pass, including fixture export. `dynamic_boundaries.php:135` still expects `$v++ + $v` to reject. The runner stops before C++ execution. |

The boxed-suite failure is a **stale rejection expectation**, not a demonstrated
operator defect: the [2026-10-10 evaluation amendment](../../../../specs/expression_evaluation.md)
permits the ordered operation. Its negative test remains unchanged. Later assertions
in that PHP task were not reached, and the broad suite is not reported green.

The [independent boxed replay](../../tests/operator_boxed_replay.py) then compiled
and ran all **20 exported boxed-operator programs** (ten per family), without PCH.
All passed: arithmetic, compound updates, unary/mutation snapshots, failures,
comparison, text conversion and container semantics. Two extra strict-source
programs also pass: for both mixed and dynamic, starting with `$v = 1`,
`$out = $v++ + $v` produces 3 and leaves `$v` at 2. This supports classifying the
old rejection assertion as stale. No existing test expectation was edited.

Together with the seven programs from the three passing focused runners, this is
**29 executed programs**, not 29 exhaustive operator/type combinations. The other
40 programs exported by the broader family suite were not executed in this audit.

Reproduce the focused runs, each using a fresh directory:

```sh
python3 -B compiler/my-try/tests/integer_widths.py --results /tmp/operator-integer-FRESH
python3 -B compiler/my-try/tests/string_identity.py --results /tmp/operator-identity-FRESH
python3 -B compiler/my-try/tests/compound_effects.py --results /tmp/operator-compound-FRESH
python3 -B compiler/my-try/tests/dynamic_families.py --no-pch --results /tmp/operator-families-FRESH
```

The last command currently exits 1 at the stale expectation, after the independent
fixture-export task completes. Confirm that export succeeded and then run:

```sh
python3 -B compiler/my-try/tests/operator_boxed_replay.py --exports /tmp/operator-families-FRESH/programs --results /tmp/operator-boxed-FRESH
```

[Retained evidence](v0_1_compatibility_operators_results.json) records all original
fixture results, source hashes, manifests, focused command journals, failed
diagnostics and generated-program hashes. There were **375 commands** across the
compatibility and focused runs. Logs remain under `/tmp/compat-strict-operators`
and `/tmp/operator-audit-*`. All runs used the shared 12-slot pool sequentially,
with CLI fallback when FPM could not bind its socket. The existing S01 `<cstdint>`
fix was present; generated programs were built, not the compiler itself.

## Semantic distinctions

Keep three kinds of rejection separate:

| Category | Examples | Treatment |
| --- | --- | --- |
| Baseline-proved missing support | Same-type bool `==` / `!=` in the five original strict fixtures | S03 implementation gap |
| Explicit v0.2 contract | Signed/unsigned arithmetic or ordinary comparisons; unsigned operand with canonical signed literal | Rejection is required by [integer domains](../../../../specs/compact_layout_types.md#7-v02-integer-arithmetic-and-numeric-comparisons); explicit conversion is a migration |
| Current bounded implementation | Direct float arithmetic; ordinary string equality/ordering; fixed-width identity, power, shifts and compound updates | Existing negative tests document current rejection. They do not authorize permanent removal of every v0.1 spelling; reconcile baseline examples and owning contracts before classifying broader compatibility |

Mixed/dynamic runtime-kind dispatch is distinct from direct scalar admission.
Passing boxed float arithmetic does not prove direct float operands are accepted.
See [boxed contracts](../../../../specs/dynamic_types.md) and
[ordered evaluation](../../../../specs/expression_evaluation.md).

## Scope and remaining matrix work

This is an operator checkpoint, not completion of the old matrix or all operand
pairs. The checked-in `tests/php-matrix` manifest lists 1,038 equality/inequality
rows and still names `.php` paths while checked-in sources use `.phs`. Sampled
sources also use legacy comment declarations and `var_dump`. Blind replay would
mix syntax/API failures with operator failures. The historical matrix retrospective
mentions a larger generated corpus; that is not the checked-in execution proof here.

The old matrix and native runtime matrix were not run. A full second pass should
resolve actual pinned paths, preserve seed/profile identities, and retain explicit
strict-syntax/output-adapter companions separately from unchanged-source results.
Broader direct scalar pairs, wrapper-lifted operations, indexed compound updates
and overflow boundaries need their own supported/rejected/migration dispositions.
No compiler or runtime fixes were made for this audit. The earlier S01 header fix
remains in the tested working tree. LLVM, STAN and native compiler rebuilds were not run.
