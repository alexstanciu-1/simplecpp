# Canonical-int cross-family compounds (C1IX)
Doc Status: supporting

Completed 2026-10-19 on `alex-ai@dev002.travelfuse.ro`, snapshot
`/home/alex-ai/int-cross-compounds/20261019-01`.

## Scope and ownership

120 signatures: ten operators (`+= -= *= /= %= &= |= ^= <<= >>=`) × twelve
ordered pairs of distinct canonical-int carriers: nullable, result,
result_or_false and result_or_bool. The left carrier is the result and storage type.

`Guarded_Operators` removes only the same-family/plain restriction from its named
compound admission. Its now-unused original-operand argument is removed. Canonical
int payload and ten-operator bounds remain. Existing independent extraction,
integer decisions, mutation preparation, assignment conversion and ordered C++
emission implement the operation. No runtime/emitter changes or new retained facts.

Each operand extracts to int independently; the computed int wraps into the left
carrier. This is not a direct conversion between carrier families or normalization
of their unavailable states. `specs/expression_evaluation.md` remains the ordering
and write-back authority, with carrier/integer contracts in `runtime/specs/spec.md`.

## Verification

The shared compound fixture gains a cross-family mode, with independent left/right
carrier state construction, comparison and extraction-error expectations. The former
cross-family negatives are removed; their historical evidence remains intact.
Arithmetic and bitwise modes still independently verify the preceding 120 signatures.

All twelve carrier-pair proofs passed on the first dev002 attempt:

- All 120 exact signatures verify ordered extraction kinds, canonical-int computation,
  destination wrap conversion, destination-typed non-addressable result facts and
  preparation cleanup. Each has a retained failure/retry/repair/no-op/fresh sequence.
- The Cartesian state proofs include present zero and signed payloads, empty nullable,
  structured errors with distinct metadata, false sentinels and both bool sentinels.
  The RHS input and failed left state remain unchanged unless RHS deliberately
  replaces the destination. Successful results contain a payload in the left carrier.
- Resolve/retain the destination and snapshot/extract old left before RHS evaluation.
  Left failure suppresses RHS calls; RHS evaluation failure precedes right extraction;
  right extraction precedes computation. Distinct carrier error classifications and
  deliberately different RHS/computation errors make precedence observable.
- RHS replacement and failure exercise aliases, snapshots and commit boundaries.
  Successful computation overwrites RHS replacement using old-left payload; later
  extraction/computation failure preserves RHS effects without a compound commit.
  Returned snapshots survive a subsequent destination replacement.
- Local/reference/instance/static storage, null owners, once-only static qualifiers
  and property-handle rebinding reuse the stable destination protocol. A rebound
  handle does not redirect successful write-back away from the retained original owner.
- Division and remainder preserve division_by_zero and modulo_by_zero. Bitwise
  operators cover contrasting signed patterns. Shift counts are restricted to [0,63];
  left shifts use nonnegative values with representable results, and right shifts
  follow the existing C++20 semantics. No invalid-count or overflow policy is added.
- The focused proof records 1,247,372 native observations and 101 negative source
  checks per carrier pair. All 421 selected runner commands return expected exits.
- Both preceding compound modes and all 18 other selected regression suites pass;
  all 14 existing property-mutation programs pass. No runtime implementation changes,
  full matrix rerun or native compiler rebuild. All compiler tests ran on dev002.

Cross-family alias coverage uses differently typed RHS producers that mutate the
left destination. Direct self-alias and nested postfix cases remain in the preceding
same-carrier proofs; they are not counted as new cross-family signatures.

Toolchain: PHP 8.5.11, Clang 18.1.3, Python 3.12.3. The shared runner uses CLI fallback
because PHP CLI/FPM versions differ. No blocker remains within the approved scope.
Float, indexed, mixed/fixed-width and power compounds remain separate.

## Evidence and remaining work

[Results and observation counts](int_cross_compounds_results.json),
[archived runner logs](int_cross_compounds_evidence.json.gz),
[tested source manifest](int_cross_compounds_source_final.json),
[pre-change baseline](int_cross_compounds_baseline.json), and
[completion audit](int_cross_compounds_completion_audit.json).
The remote source-integrity check covers 6,231 files. Earlier proofs are retained.

Reproduce on dev002:
`python3 compiler/my-try/tests/int_carrier_compounds.py --group cross --results FRESH`.
Use `--group arithmetic` or `--group bitwise` for the preceding independent proofs.

V-L07 has 22 completed slices: 1,680 binary, 64 unary and 240 compound signatures
(1,984/2,080 = 95.4% of the proposed core). The [inventory](vl07_inventory/README.md)
retains 96 float compound support targets and 1,872 design/scope candidates. Overall
closure remains 19/64 (29.7%). Further implementation requires discussion and approval.
