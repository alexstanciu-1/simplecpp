# V-L06 scalar float identity and S04 reconciliation
Doc Status: supporting

Completed on dev002, 2026-10-20. V-L06 and S04 are now verified and closed within
their recorded scope. **Two scalar signatures** are newly admitted: `float ===
float` and `float !== float`. The original twelve carrier cells and unchanged S04
fixture already work through previous repairs. No runtime implementation changed.

## Contract and ownership

`Scalar_Identity` now admits matching canonical floats alongside its existing bool,
string and distinct-kind scalar candidates. It publishes the existing identity
operation and identity operand conversions. The existing emitter snapshots left,
then right, and invokes the existing runtime identity helper. No retained structure,
conversion path or emitter change was needed. Integer identity keeps its existing
owner; this change does not widen fixed-width identity.

Equal finite values and matching infinities are identical. Signed zeros are
identical. NaN is non-identical to every value, including itself, without throwing;
`!==` negates `===`. This is numeric value identity, not bit-pattern identity.
Stored float bits are preserved. Distinct int/float kinds remain non-identical and
no promotion occurs. NaN spaceship still throws under its separate contract.
See the [normative amendment](../../../../specs/expression_evaluation.md#canonical-scalar-float-strict-identity--2026-10-20).

## Verification

Host: `alex-ai@dev002.travelfuse.ro`; snapshot
`/home/alex-ai/float-identity/20261020-01`; starting commit `3c17c75e`.
PHP 8.5.11 CLI fallback because FPM differs, Clang 18.1.3 and Python 3.12.3.
All compiler/native tests ran remotely through the shared 12-slot runner; independent
suites overlapped. No full matrix or native compiler rebuild ran.

- **Runtime first:** 256 float pairs passed before compiler admission changed,
  using independent expected numeric equality and bit-preserving state checks.
- **Scalar source proof:** both forms pass preparation/cleanup, eager evaluation,
  distinct left/right source exceptions, local and property snapshots, invalid-edit
  artifact cleanup, repaired/fresh equivalence and no-op recovery. Values include
  signed zeros, infinities, fractions, integer-conversion boundaries, subnormal/max
  values and two distinct NaNs.
- **Original V-L06 carrier cells:** nullable/int, Result/int and falseable/int each
  pass homogeneous `==`, `!=`, `===`, `!==` with independent state oracles, including
  Result failure equality versus structured-error identity. These twelve existing
  cells are reconciliation controls, not newly implemented signatures.
- **S04:** the original strict `shell_exec` fixture compiles and executes with exact
  stdout `strict_shell_exec_ok\n` and empty stderr. Its source SHA-256 is
  `7a4c1dabc5d2b735e3c7c06cfbf3040b7d6ea65faa45b652cfd383419930c749`, identical to the
  pinned v0.1 revision `75de94c71e37060c564b6ade19850b7686bd457a`. Four deterministic
  identity/inequality forms in both operand orders check false sentinel versus
  successful string payloads, including empty, numeric-looking and binary strings.
  No process/provider/output implementation or fixture migration was necessary.
- **Ten regression families:** original carriers, float operators, nullable-float
  equality, Result equality, non-bool sentinel equality, mixed identity, scalar
  admission, string identity, mixed equality and the 75-row float/mixed spaceship
  slice all pass. The spaceship proof retains NaN errors while identity does not throw.

There are **13 accepted suites and 895 expected command exits**, with 3,493,542
recorded observations across new proofs and regressions. Observations are not
unique signatures. V-L07's carrier total remains **3,376**; these two new forms are
plain scalar operations.

## Evidence and attempts

[Exact results](float_identity_results.json), [evidence archive](float_identity_evidence.json.gz),
[initial manifest](float_identity_source_initial.json), [candidate delta](float_identity_source_candidate.json.gz)
and [final tested manifest](float_identity_source_final.json) preserve source and
attempt provenance. The [closeout audit](float_identity_audit.json) independently
checks source hashes, exact signatures, accepted command exits and pinned S04 bytes.
All 6,264 remote source hashes match; all 218 included runtime
files are unchanged. Documentation closeout follows the tested snapshot.

The first source run exposed a fixture bug: its shared exclusion helper treated
already-supported plain float ordinary equality as unsupported. The helper now
recognizes matching canonical plain pairs. Three wrapper suites also shared an
obsolete rejection of scalar float strict identity; only that exclusion was removed
and all three suites were rerun. Earlier failed logs remain in the archive. No
runtime or new language blocker was hidden by those fixture repairs.

## Closeout and boundaries

V-L06's original carrier failures are resolved by earlier V-L07 work; this batch
finishes its scalar float gap. S04 closes by replay of its unchanged original case.
Original findings remain historical evidence in the issue ledger. Overall closure
is **22/64 (34.375%)** by original issue count, not an engineering-effort estimate.

No newly exposed implementation blockers remain in this batch. Fixed-width identity,
other process APIs, text conversions, indexed mutation, selectors and wider
payload domains remain with their existing separate owners and issues. V-L07's
explicit W1-W4 boundaries remain unchanged.
