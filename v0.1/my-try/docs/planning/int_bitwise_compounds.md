# Canonical-int remainder, bitwise and shift compounds (C1IB)
Doc Status: supporting

Completed 2026-10-19 on `alex-ai@dev002.travelfuse.ro`, snapshot
`/home/alex-ai/int-bitwise-compounds/20261019-01`.

## Scope and implementation

72 signatures: six operators (`%= &= |= ^= <<= >>=`) × four canonical-int carriers
(nullable, result, result_or_false, result_or_bool) × three arrangements
(same carrier/carrier, carrier/int, int/carrier). `^=` is bitwise XOR.

`Guarded_Operators` extends its named compound admission to the six existing integer
operations. Canonical payload and same-carrier/plain checks remain unchanged. Existing
mutation preparation, stable targets, ordered extraction, scalar operations and
assignment conversion own execution. No runtime, emitter or retained-model changes.
Indexed guarded compounds remain rejected. The existing guarded fixture removes
only newly obsolete negative expectations.

The shared `int_carrier_compounds.php` fixture has separate arithmetic and bitwise
modes. The arithmetic mode still verifies the earlier 48 signatures. The new mode
uses operator-specific valid execution pairs and a distinguishable RHS error.

## Semantics and verification

The contract remains `specs/expression_evaluation.md`, with runtime integer and
carrier semantics in `runtime/specs/spec.md`. Resolve the destination once, retain
its owner, snapshot and extract the old left value before RHS evaluation, evaluate
and extract RHS, then compute/convert before one write-back. Return an independent
destination-typed value snapshot. Failures prevent the compound commit but preserve
prior RHS effects. Rebinding a property handle does not redirect the original target.

All four carrier proofs passed on their first dev002 attempt:

- All 72 signatures have destination-typed non-addressable result facts, two ordered
  extraction decisions and checked write-back facts. Each has a retained lifecycle
  failure/retry/repair/no-op/fresh recovery sequence.
- Native observers verify absent/error/sentinel states (both bool sentinels and
  distinct structured errors), signed remainder, zero divisors, contrasting bit
  patterns, aliases, nested postfix updates, saved-result snapshots and RHS replacement.
- Locals, references, instance/static properties, null owners, once-only qualifiers
  and owner rebinding exercise the existing storage protocol.
- RHS deliberately throws division_by_zero, distinct from the compound remainder's
  modulo_by_zero. Left extraction takes precedence over RHS; RHS exceptions precede
  right extraction; right extraction precedes computation. Zero remainder divisors
  preserve earlier RHS replacement effects without a compound commit.
- Shift pairs use counts in [0,63]. Left shifts use nonnegative payloads whose result
  remains representable; right shifts include negative payloads under the existing
  C++20 semantics. Invalid-count pairs are not executed. Values include -4, -1, 0,
  1, 3, 10, 31, 62 and 63. This is not a new checked-shift or overflow contract.
- The focused native proof records 625,980 observations; each carrier has 133
  negative source checks. All 360 selected runner commands return expected exits.
- All 19 regression suites pass, including the prior 48 arithmetic compounds,
  carrier updates, scalar/float admission, guarded operators/float/bool/mixed/cross/
  string, sentinel and nullable/Result equality, eager binary and numeric XOR.
  All 14 existing property-mutation programs pass.

The shared runner used dev002 PHP CLI fallback because CLI/FPM versions differ;
PHP 8.5.11, Clang 18.1.3 and Python 3.12.3. This proves generated programs, not a
native compiler rebuild. No tests ran on WSL; no full matrix rerun or runtime changes.
No blocker remains within the approved slice. Cross-family, float, indexed,
mixed/fixed-width and power compounds remain separate.

## Evidence and remaining work

[Results and observation counts](int_bitwise_compounds_results.json),
[archived runner logs](int_bitwise_compounds_evidence.json.gz),
[tested source manifest](int_bitwise_compounds_source_final.json),
[pre-change baseline](int_bitwise_compounds_baseline.json), and
[completion audit](int_bitwise_compounds_completion_audit.json).
The manifest covers 6,231 source files. Earlier arithmetic evidence is unchanged.

Reproduce on dev002:
`python3 compiler/my-try/tests/int_carrier_compounds.py --group bitwise --results FRESH`.
Omit `--group` to run the previous arithmetic slice independently.

V-L07 has 21 completed slices: 1,680 binary, 64 unary and 120 compound signatures
(1,864/2,080 = 89.6% of the proposed core). The [inventory](vl07_inventory/README.md)
retains 216 support targets: 120 cross-family int and 96 float compounds. Another
1,872 signatures need design/scope decisions. Overall closure remains 19/64 (29.7%).
Further implementation requires the next discussion and authorization.
