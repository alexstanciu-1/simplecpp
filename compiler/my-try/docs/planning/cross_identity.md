# E3I cross-family matching-payload strict identity
Doc Status: supporting

Completed 2026-10-19 on `alex-ai@dev002.travelfuse.ro`, snapshot
`/home/alex-ai/cross-identity/20261019-01`.

## Scope and contract

96 signatures: `===` and `!==` × four canonical payloads (int, float, bool, string)
× 12 ordered distinct-carrier pairs. Carriers are nullable, result, result_or_false
and result_or_bool. No new scalar prerequisites. Ordinary cross-family equality
and mixed payloads remain outside compiler admission.

The [source contract](../../../../specs/expression_evaluation.md#cross-family-matching-payload-strict-identity--2026-10-19)
uses whole-state eager left-to-right snapshots and the existing runtime normalized
identity rules. Empty nullable represents null; sentinels represent bool values;
successful carriers represent their payload; Result errors retain structured
identity. False sentinels can match successful bool false payloads, but not integer
zero or empty strings. Float signed zeros compare identically, NaN is non-identical
even to itself, and string identity compares complete bytes. `!==` negates `===`.
No stored state is normalized in place and no absent payload is extracted.

## Runtime prerequisite and repair

[The historical runtime-first probe](cross_identity_runtime.md) recorded 96 native
compilation failures from ambiguous overloads, alongside 32 compiling same-family
controls. The user subsequently authorized the bounded runtime repair before
resuming this compiler slice; original failure evidence is preserved.

The runtime strict-identity owner now classifies the four identity carriers once.
Right-carrier generic overloads require a non-carrier left operand, giving left
normalization precedence for distinct families. Existing same-family overloads and
scalar/error identity remain in their current owner. This resolves competing
normalizers without per-pair overloads, runtime boxing, state mutation, generator
changes or changes to the public PHP adapter.

Compiler preparation admits distinct families only for strict identity with matching
canonical payloads. The prepared decision retains identity conversions of complete
operands and contains no guarded extractions. Existing ordered emission supplies
snapshots. No emitter or retained-model changes were needed. Stale negative tests
were adjusted only for the newly approved strict-identity combinations.

## Verification

The runtime gate passed first, before compiler admission. An independent state oracle
checks all 16 ordered family arrangements per payload, including 32 same-family
operator controls in addition to the 96 cross-family signatures. It verifies both
operators, bool payload/sentinel distinctions, nullable absence, structured errors,
int64 limits, signed zero, infinities, distinct NaN bit patterns and byte strings.
Storage observations preserve float bits and structured error metadata.

All 96 source signatures passed on their first attempt and again after fixture
consolidation. The proof checks:

- Exact signature/result types, whole-state identity conversions, absence of payload
  extraction, and cleanup of prepared facts.
- All state/value pairs and both source-failure flags. Unavailable or unequal left
  states still evaluate RHS. Left source failure suppresses RHS; right source failure
  occurs after left evaluation. Counters and traces prove once-only source order.
- RHS replacement of the left local/reference or property, preserved saved-left
  snapshots, retained RHS effects on failure and unchanged other operands. Identity
  performs no write-back.
- 96 retained failure/retry/repair/no-op/fresh recovery sequences.
- 480 source rejection controls for ordinary cross-family equality and mixed or
  unsupported payloads.

The final runtime/source proof records **423,252 observations**. All **728 selected
runner commands** across runtime gates, both focused attempts, baseline diagnostic
checks and **16 compiler regression suites** return expected exits. The existing
native shared-pointer strict-identity program additionally compiles and executes.

The optional existing `test_strict_identity_null_dynamic.cpp` control fails to compile
because it constructs mixed from dynamic, a removed v0.2 conversion. A separate
baseline/current comparison proves identical normalized diagnostics before and after
this repair. That fixture remains unchanged and is not counted as passing. Its
failure and baseline evidence are retained separately from the approved slice.

Toolchain: PHP 8.5.11 (CLI fallback for CLI/FPM mismatch), Clang 18.1.3 and Python
3.12.3. All compiler/native execution ran on dev002 through the shared runner.
No full matrix rerun or native compiler rebuild. No blocker remains in this slice.

## Evidence and progress

[Results/signatures](cross_identity_results.json), [runner archive](cross_identity_evidence.json.gz),
[final source manifest](cross_identity_source_final.json), [initial runtime-gate manifest](cross_identity_runtime_source.json),
[baseline](cross_identity_baseline.json), [completion audit](cross_identity_completion_audit.json).
Remote integrity checks all 6,241 manifest files. Historical prerequisite evidence
and earlier slices remain separate.

Reproduce on dev002:
`python3 compiler/my-try/tests/cross_identity.py --results FRESH`.
Use `--runtime-only` for the prerequisite gate.

V-L07 now has **26 completed slices: 1,968 binary, 64 unary and 432 compound
signatures — 2,464 total**. The original core remains **2,080/2,080 (100%)**, plus
384 approved O1/C2W/E3I signatures. There are **1,488 design/scope candidates** left,
including 96 ordinary cross-family equality signatures. Overall issue closure
remains 19/64 (29.7%); V-L07 is still partial.
