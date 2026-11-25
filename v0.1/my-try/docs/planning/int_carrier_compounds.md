# Canonical-int arithmetic compounds (C1IA)
Doc Status: supporting

Completed 2026-10-19 on `alex-ai@dev002.travelfuse.ro`, snapshot
`/home/alex-ai/int-carrier-compounds/20261019-01`.

## Scope and ownership

48 signatures: four operators (`+= -= *= /=`) × four canonical-int carriers
(nullable, result, result_or_false, result_or_bool) × three arrangements
(carrier/carrier of the same family, carrier/int, int/carrier).

`Guarded_Operators` owns bounded compound admission and reuses its
existing extraction and scalar decision. Mutation preparation rejects indexed
guarded compounds before attaching write-back conversion. Existing stable-target,
ordered binary emission and assignment conversion implement the operation; no new
retained facts, emitter protocol or runtime implementation changes were required.
The existing guarded regression fixture drops only the four newly admitted negatives.

The semantic owner remains `specs/expression_evaluation.md`, with carrier extraction
contracts in `runtime/specs/spec.md` and `runtime/specs/config.json`.
Destination evaluation and owner retention happen once. The old destination is copied
and its payload extracted before RHS evaluation. RHS is evaluated once, then extracted.
Computation and destination conversion finish before one compound write-back.
The expression yields an independent destination-typed value snapshot.

A left extraction failure suppresses RHS evaluation. RHS exceptions, right extraction
failure and division_by_zero prevent compound write-back, but do not undo RHS effects.
Successful arithmetic overwrites an RHS replacement using the original left snapshot.
A rebound property handle does not redirect the commit away from the retained owner.

## Verification

The shared runner used PHP 8.5.11 (CLI fallback for the FPM version mismatch),
Clang 18.1.3 and Python 3.12.3 on dev002. No compiler tests ran on WSL.

- All 48 admitted signatures, destination result types and 48 retained recovery
  sequences pass. Four carrier fixtures each check 95 negative source forms.
- 57,184 native observations cover payloads -4, 0, 3 and 10; unavailable nullable,
  structured-error and both sentinel states; extraction/error precedence;
  division_by_zero; aliasing and nested postfix updates; RHS replacement and failure;
  local/reference/instance/static destinations; saved-result snapshots; property-owner
  rebinding; once-only static qualifiers and null-owner failures.
- All 18 regression suites pass: carrier updates, float operators, scalar admission,
  guarded operators/float/bool/cross/mixed/string, non-bool/bool/false-bool sentinel
  equality, Result equality, nullable int/float/bool-string equality, eager binary
  operators and numeric XOR. All 14 existing property-mutation programs pass.
- 339 selected runner commands returned their expected exit codes. The final remote
  manifest verified all 6,231 source files; non-documentation sources match locally.

The initial two fixture attempts failed during preparation because helper arguments
tried to borrow instance and then static heap properties by reference. Helpers now
receive the object or access the static slot directly. Both failed logs remain in
the evidence archive; borrowing support was not broadened. `focused-03` is the
successful selected proof. The initial and final source manifests are retained.

These small-value checks do not claim integer overflow-boundary coverage. There was
no native compiler rebuild, runtime implementation change or full operator matrix run.
Cross-family, float, indexed, mixed/fixed-width and other compounds remain separate.
No blocker remains within the approved 48-signature scope.

## Evidence and remaining work

[Results](int_carrier_compounds_results.json), [archived logs](int_carrier_compounds_evidence.json.gz),
[final source manifest](int_carrier_compounds_source_final.json),
[initial manifest](int_carrier_compounds_source.json),
[baseline](int_carrier_compounds_baseline.json), and
[completion audit](int_carrier_compounds_completion_audit.json).
Run `python3 compiler/my-try/tests/int_carrier_compounds.py --results FRESH` on dev002.

V-L07 now has 20 completed slices: 1,680 binary, 64 unary and 48 compound signatures
(1,792/2,080 = 86.2% of the proposed core). The [inventory](vl07_inventory/README.md)
retains 288 support targets: 72 same-carrier/plain int remainder/bitwise/shift
compounds, 120 cross-family int compounds and 96 float compounds. Another 1,872
signatures need design/scope decisions. Overall issue closure remains 19/64 (29.7%).
The next slice requires discussion; none is authorized by this closeout.
