# Scalar admission follow-up — 2026-10-17
Doc Status: planning

Implemented the agreed first slice: exact bool `==` / `!=`; canonical float/float
and int/float `+`, `-`, `*`, `/`; float unary `+` / `-`. Arithmetic results are
float, equality results bool. Integer-only arithmetic retains its existing rules.

`Boolean_Operators` and `Floating_Point_Operators` own the new candidates.
`Floating_Point_Conversions` permits canonical int-to-float conversion specifically
at operator operands; assignment, argument and return conversion rules are not
broadened. Existing decision records retain semantic operations, result types and
ordered conversions. C++ consumes these facts through the existing eager sequencing
path. Native float compound updates remain explicitly deferred; boxed operations
retain their existing path. No runtime implementation changes were made.

## Evidence on dev002

Remote root: `/home/alex-ai/operator-audits/20261017-scalar`, 32 workers,
PHP 8.2.34 CLI fallback, Clang 18.1.3. No tests ran on WSL and the compiler itself
was not rebuilt natively. LLVM/STAN remain deferred.

- `results-06`: ten PHP suites pass, including operator decisions, conversions,
  integer widths, mutations, compound updates, logical and mixed regressions.
- Seven generated programs pass. New proofs cover bool truth tables, float result
  types/values, both numeric operand orders, unary signed zero, left-to-right calls,
  snapshots, current zero-divisor failures and unchanged integer truncation/errors.
- `originals-01`: all **48 unchanged ordinary strict operator fixtures compile**.
  This resolves the five original S03 failures; these fixtures remain compile-only.
- `matrix-01`: all **60 affected direct scalar rows** meet existing matrix oracles:
  **54 positive value matches and six structured runtime-error matches**.
  The strict observation adapter is unchanged. The full 22,816-case audit was not
  repeated; its dated totals remain historical evidence.

[Machine-readable results and source hashes](v0_1_compatibility_scalar_admission_results.json).
Complete command logs/artifacts remain in the isolated server directory.

## Resolved contract: floating zero division — 2026-10-18

Agreed: preserve `division_by_zero` for float/float and both int/float operand
orders, including positive and negative floating zero. Runtime behavior is
unchanged. The stale IEEE-style division statement in
[runtime semantics](../../../../runtime/specs/spec.md) is corrected.

The first proposal incorrectly promised IEEE infinity/NaN. `results-04` retains
the failed probe that exposed this mismatch; `results-06` and the six negative
matrix cases verify the preserved error behavior. The dated machine-readable
ledger records the then-pending decision; this resolution supersedes that status.
The optional `--ieee-zero` flag is retained only as a historical counterfactual
probe: its failure is expected and is not an outstanding implementation blocker.

This closes the scalar admission slice. Further operator implementation is stopped
pending a separate planning discussion.

```sh
python3 -B compiler/my-try/tests/scalar_admission.py \
  --php /usr/bin/php8.2 --jobs 32 --results /path/to/FRESH_RESULTS
# Historical counterfactual probe (expected to fail; not an acceptance check):
python3 -B compiler/my-try/tests/scalar_admission.py \
  --php /usr/bin/php8.2 --jobs 32 --ieee-zero --results /path/to/FRESH_IEEE_RESULTS
```

Initial iterations corrected a PHP syntax error, test-only encoded C++ names and
stale rejection expectations. The eager suite's old `!mixed_call() === false`
rejection also contradicted its already-supported bool identity/condition path;
it is now an accepted preparation check. No additional compiler fix was needed.

## Deferred next slices

Float comparisons, native compound updates and increment/decrement, fixed-width /
float pairs, lifted nullable/Result operators and conditional selection require
the next discussion. Fixture migration and table semantics remain separate.
