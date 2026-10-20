# C2 float-destination mixed compounds
Doc Status: supporting

Completed 2026-10-19 on `alex-ai@dev002.travelfuse.ro`, snapshot
`/home/alex-ai/widening-compounds/20261019-01`.

## Scope and ownership

Four scalar prerequisites and 96 carrier signatures: `+= -= *= /=` with canonical
float destination and canonical int RHS, across 24 ordered carrier/plain arrangements.
The four carriers are nullable, result, result_or_false and result_or_bool.
Both-plain signatures are counted separately from V-L07 carrier coverage.

The [language amendment](../../../../specs/expression_evaluation.md#float-destination-mixed-compounds--2026-10-19)
permits int-to-float RHS conversion before float computation. Rounding is observable:
`0.0 += 9007199254740993` produces `9007199254740992.0`. Integer zero divisors throw
`division_by_zero`, including when the old float value is NaN or infinite.

The existing floating-compound preparation owner now admits a canonical int RHS
when the destination is canonical float. Guarded preparation independently extracts
payloads and delegates to this owner; existing prepared conversion, wrapping and
stable mutation paths perform the operation. No runtime, emitter or retained-model
implementation changes were needed. Ordinary assignment and parameter conversion
rules are unchanged. Int-destination/float-RHS narrowing, indexed destinations,
fixed-width and non-arithmetic mixed compounds remain separate.

## Verification

The focused runner executes four scalar prerequisites first, followed by 24 carrier
pair tasks. All 100 signatures passed on the first dev002 attempt. Checks cover:

- Prepared extraction types, explicit RHS integer-to-floating conversion, float
  computation and destination wrapping; expression results retain the destination type.
- Independent integer RHS representation through int64 extrema and values around
  ±2^53, signed float zero, finite fractions, infinities and NaNs. A source rounding
  anchor and native float-domain expectations verify promotion before computation.
- Present/empty/error/sentinel combinations, both bool sentinel states and structured
  error metadata. Old-left extraction precedes RHS evaluation; RHS source failure
  precedes its extraction; computation follows successful extraction/conversion.
- Old-left and returned-value snapshots, RHS destination replacement, retained
  property owners after RHS rebinding, static qualifier effects, reference mutation
  and once-only write-back. Failures prevent compound commit while retaining prior
  RHS effects; saved expression results do not alias subsequent destination changes.
- One hundred retained failure/retry/repair/no-op/fresh recovery sequences. Narrowing,
  unsupported payload/operator combinations and indexed controls remain rejected.

The focused proof records **1,168,616 native observations**. All **759 selected
runner commands** return their expected exits; **23 regression suites** and **14
property-mutation programs** pass. Regression coverage includes prior scalar/guarded
operators, O1 ordering, all previous compound slices, carrier updates and equality.
Stale negative fixtures were updated only where float-destination/int-RHS admission
is now intentional; reverse-direction narrowing controls remain.

Toolchain: PHP 8.5.11 (CLI fallback for CLI/FPM mismatch), Clang 18.1.3 and Python
3.12.3. All compiler/native execution ran on dev002. No runtime changes, full matrix
rerun or native compiler rebuild. No blocker remains within this approved slice.

## Evidence and accounting

[Results/signatures](widening_compounds_results.json),
[runner archive](widening_compounds_evidence.json.gz),
[final source manifest](widening_compounds_source_final.json),
[initial manifest](widening_compounds_source.json),
[baseline](widening_compounds_baseline.json),
[completion audit](widening_compounds_completion_audit.json).
Remote integrity verifies 6,238 source files. Historical evidence is preserved.

Reproduce on dev002:
`python3 compiler/my-try/tests/widening_compounds.py --results FRESH`.

C2W adds 96 carrier signatures beyond the original completed core. V-L07 now has
**25 completed slices: 1,872 binary, 64 unary and 432 compound signatures — 2,368
in total**. The original core remains **2,080/2,080 (100%)**, with 288 additional
approved O1/C2W signatures. Four new scalar prerequisites are counted separately.
The [remaining inventory](vl07_inventory/README.md) has **1,584 design/scope
candidates**, including 96 int-destination/float-RHS compounds. V-L07 remains
partial; overall issue closure stays 19/64 (29.7%). Further slices require discussion.
