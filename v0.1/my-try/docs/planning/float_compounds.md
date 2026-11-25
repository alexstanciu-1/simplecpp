# Exact-float compounds and proposed-core completion
Doc Status: supporting

Completed 2026-10-19 on `alex-ai@dev002.travelfuse.ro`, snapshot
`/home/alex-ai/float-compounds/20261019-01`.

## Scope and implementation

Four scalar prerequisites (`float op= float`) and 96 carrier signatures for
`+= -= *= /=`: four same-carrier pairs, eight carrier/plain arrangements and twelve
ordered cross-family pairs, all with exact float payloads. Carriers are nullable,
result, result_or_false and result_or_bool. Plain prerequisites are separate from
the V-L07 carrier denominator.

`Floating_Point_Operators::require_compound_operands` owns the bounded exact-float
arithmetic rule, shared by scalar mutation and guarded payload admission. Mutation
preparation replaces blanket float rejection with this rule after deciding the
operation; it explicitly keeps plain indexed float compounds deferred. Guarded
preparation applies the same rule to independently extracted payloads. Mixed int/float
promotion remains available for ordinary arithmetic but not compounds.

Existing ordered extraction, stable destination binding, arithmetic, assignment
conversion and C++ emission perform the operation. Runtime and emitter are unchanged.
Exact float results use identity conversion or wrap into the destination carrier;
there is no narrowing-conversion failure contract introduced by this slice.

The int compound fixture moves into shared `numeric_compound_fixture.php`; its
original entry remains an int adapter. The float adapter/runner adds scalar-first
execution, independent carrier states and floating value comparison. Existing int
proof modes remain independent and are rerun. Only newly obsolete float negatives
and the mixed rejection's changed diagnostic expectation are updated.

## Semantics and verification

`specs/expression_evaluation.md` remains authoritative for order and write-back;
`runtime/specs/spec.md` defines existing float arithmetic and zero-divisor behavior.

The four scalar prerequisites passed first, then all 96 carrier signatures passed on
the first attempt. Final verification includes:

- All 100 signatures check preparation/result types, independent extraction where
  applicable, destination conversion, non-addressable result facts and retained
  failure/retry/repair/no-op/fresh recovery.
- Finite fractions, signed values, +0/-0, infinities and two quiet NaNs with different
  sign/payload bits. Arithmetic NaNs compare by classification; arithmetic zeros
  preserve sign. Untouched inputs and replacement/failure snapshots compare bits.
  Arithmetic is not required to produce a particular NaN payload.
- Both +0 and -0 divisors throw division_by_zero, including NaN/infinite numerators.
  Deliberate RHS modulo_by_zero failures distinguish RHS evaluation from division.
  Left extraction suppresses RHS; RHS exceptions precede right extraction; right
  extraction precedes computation. Carrier absence/error/both bool sentinel states
  remain distinct, with structured-error metadata preserved in storage.
- Old-left and returned snapshots, RHS replacement and failure effects, aliases,
  nested carrier updates, local/reference/instance/static destinations, null owners,
  once-only qualifiers and property-owner rebinding. Successful write-back uses
  the old left payload; later failures preserve RHS effects without compound commit.
- Mixed int/float in both directions, indexed destinations, fixed-width and
  non-arithmetic float compounds remain rejected. All 240 prior int compounds pass.
- The selected float proof records 746,088 native observations. All 507 selected
  runner commands return their expected exits.
- All 21 selected regression suites and all 14 existing property-mutation programs
  pass. This is a focused regression run, not the full operator matrix.

The initial scalar-admission and eager-binary suites failed stale expectations:
`float += float` was still listed as rejected, and a mixed int/float rejection
matched the old blanket diagnostic. The mixed operation still correctly rejected.
The obsolete negative and diagnostic string were corrected; both suites passed in
fresh `-final` directories. Initial failures remain in the evidence archive.
The float proof was rerun after removing unused runner setup; no compiler correction
was needed after its first successful proof.

Toolchain: PHP 8.5.11 (CLI fallback for CLI/FPM mismatch), Clang 18.1.3,
Python 3.12.3. All compiler tests ran on dev002. No native compiler rebuild or runtime
implementation change. No blocker remains within the approved slice. Mixed numeric,
indexed, fixed-width and non-arithmetic float compounds remain separate.

## Evidence and remaining work

[Results and exact signatures](float_compounds_results.json),
[archived runner evidence](float_compounds_evidence.json.gz),
[final source manifest](float_compounds_source_final.json),
[initial manifest](float_compounds_source.json),
[pre-change baseline](float_compounds_baseline.json), and
[completion audit](float_compounds_completion_audit.json).
Remote source integrity covers 6,234 files; earlier dated proofs are unchanged.

Reproduce on dev002:
`python3 compiler/my-try/tests/float_compounds.py --results FRESH`.
The runner completes scalar prerequisites before launching carrier tasks.

V-L07 now has 23 completed slices: 1,680 binary, 64 unary and 336 compound signatures.
The proposed core is **2,080/2,080 (100%)**. Four scalar float prerequisites are counted
separately. The [inventory](vl07_inventory/README.md) retains **1,872 design/scope
candidates** plus explicit wider-domain boundaries. V-L07 remains partial; overall
issue closure stays 19/64 (29.7%). This closes the bounded core support list, not all
language/operator issues, and does not authorize further implementation.
