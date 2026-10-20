# E5BNI bool/numeric strict identity
Doc Status: supporting

Completed 2026-10-19 on `alex-ai@dev002.travelfuse.ro`, snapshot
`/home/alex-ai/bool-numeric-identity/20261019-01`.

## Scope and semantics

192 carrier signatures: `===` and `!==` × bool/int and bool/float in both orders
× 24 ordered carrier/plain arrangements. Eight both-plain scalar prerequisites are
counted separately. Nullable, result, result_or_false and result_or_bool participate,
including same-family and cross-family pairs.

The [language amendment](../../../../specs/expression_evaluation.md#boolnumeric-strict-identity--2026-10-19)
normalizes each operand independently. A bool payload can match a numeric carrier's
false/true sentinel, but never a successful numeric payload. False differs from
integer/floating zero; true differs from nonzero numbers, infinity and NaN. No truth
conversion or numeric promotion occurs. Nullable absence remains null; structured
Result errors compare complete identity metadata. `!==` negates `===`.

Complete operands are eagerly snapshotted left-to-right. An unavailable or unequal
state still evaluates RHS; a source exception stops later evaluation. RHS replacement
cannot alter the saved left state, and earlier RHS effects survive a later failure.
Identity does not mutate either stored state or perform write-back. Ordinary
bool/numeric equality, strings, fixed-width/nested extensions, logical mixing and
updates remain separate.

## Ownership and implementation

The runtime-first probe passed all 200 signatures without runtime changes.
`Scalar_Identity::is_distinct_kind_pair` now expresses the bounded distinct-kind
capability for canonical bool/int/float. Scalar and wrapper preparation use this
same rule, retaining each operand's original type and identity conversion. Wrapper
preparation retains whole states with no extraction. The rule is used only for
strict identity, so ordinary equality and logical admission remain unchanged.
No emitter, retained-model or runtime implementation changes were needed.

The shared fixture oracle now maps each operand independently to its visible kind.
Bool payload false/true maps to the same visible kind as the corresponding sentinel,
while storage observations still distinguish payload from sentinel. The oracle does
not call the runtime identity helper to compute expected results. The existing mixed
identity runner has a bounded `--group bool-numeric` mode; its default int/float
proof and the earlier cross-family proof were rerun as regressions.

## Verification

After the runtime prerequisite gate, all eight scalar prerequisites and 192 carrier
signatures pass. The source and native proof covers:

- Exact signature/result types, each operand's unchanged type and identity
  conversion, no guarded extraction, and cleanup of prepared facts.
- All state combinations for the selected values: false/true bool payloads, both
  bool sentinels, nullable absence, structured errors and numeric payloads. Numeric
  cases include int64 extrema, ±2^53 boundaries, signed zeros, fractions, infinities,
  subnormals and distinct NaN bit patterns. Error metadata differs independently in
  message, line and file. Storage checks preserve float bits and error details.
- Eager evaluation even for unequal or unavailable states, once-only counters and
  source-order traces, distinct left/right source failures, local/reference and
  property RHS replacement, saved-left snapshots and retained RHS effects on failure.
- 200 retained failure/retry/repair/no-op/fresh recovery sequences.
- 1,100 rejection controls, including ordinary bool/numeric equality, string/fixed-width
  identity exclusions and all five bool/numeric logical spellings.

The selected final proof records **354,048 runtime/source observations**. All **1,379
selected runner commands** return expected exits. All **21 focused regression suites**
pass in their final attempts, including previous mixed and cross-family identity,
matching-payload equality, scalar bool/string identity, numeric XOR, ordering,
guarded operations and eager binary snapshots.

Initial source preparation stopped at a shared stale negative asserting that
`1 === true` must be rejected. It was changed to ordinary `1 == true`, preserving
the intended remaining exclusion. That shared check also blocked the initial
regression suites. After its repair, 20 suites passed; the string-identity suite
contained another stale `true === 1` rejection. It was changed to ordinary
`true == 1`, and only that suite was rerun. Its native controls pass. No compiler
correction was needed after the first implementation. All failed attempts and final
passing logs remain in the archive; they are not relabeled as passing.

Toolchain: PHP 8.5.11 (CLI fallback for CLI/FPM mismatch), Clang 18.1.3 and Python
3.12.3. All compiler/native execution ran on dev002. No runtime changes, full matrix
rerun or native compiler rebuild. No blocker remains within this approved slice.

## Evidence and progress

[Results/signatures](bool_numeric_identity_results.json),
[runner archive](bool_numeric_identity_evidence.json.gz),
[final source manifest](bool_numeric_identity_source_final.json),
[baseline](bool_numeric_identity_baseline.json),
[completion audit](bool_numeric_identity_completion_audit.json).
Remote integrity verifies 6,243 source files. Historical evidence is preserved.

Reproduce on dev002:
`python3 compiler/my-try/tests/mixed_identity.py --group bool-numeric --results FRESH`.
Add `--runtime-only` for the prerequisite gate.

V-L07 now has **28 completed slices: 2,256 binary, 64 unary and 432 compound
signatures — 2,752 total**. Eight new scalar prerequisites are counted separately.
The original core remains **2,080/2,080 (100%)**, plus 672 approved signatures beyond
that core. There are **1,200 design/scope candidates** remaining. Overall issue
closure remains 19/64 (29.7%); V-L07 is still partial.
