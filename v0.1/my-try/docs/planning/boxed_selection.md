# V-L09 / V-L10 boxed integer operators and wrapper coalescing
Doc Status: supporting

Completed 2026-10-20 on dev002: **35/64 original issues closed (54.6875%)**, up from 33/64
(51.5625%), a gain of 3.125 percentage points. The original denominator and
historical observations remain unchanged. See the verification and separate
shift-count finding below; completing admission is not a claim of safe invalid
primitive shift counts.

## Scope and approved runtime prerequisites

V-L09 admits six forms (`&`, `|`, `^`, `<<`, `>>`, `~`) for homogeneous mixed and
dynamic operands: 12 forms. Only integer payload kinds participate. Results retain
the boxed family. Binary operands evaluate left-to-right once, snapshot the left
value before RHS mutation and validate runtime kinds after both expressions run.
Cross-family/boxed-scalar peers, compounds and indexed updates remain excluded.

V-L10 admits homogeneous nullable, Result and falseable Result pairs with exact
canonical int/float/bool/string payloads: 12 forms. The result is an owned payload.
Presence is independent of truthiness: zero, false and empty text are usable.
A usable left skips the RHS; otherwise the RHS executes once. An unusable selected
fallback throws `coalesce_selected_branch_has_no_usable_value_domain`.
Boolable Result is invalid on either side, even when unselected. Widening,
cross-family wrappers, fixed-width payloads, records and ownership joins remain
separate.

The initial authorization excluded runtime changes. Runtime-first probes on dev002
found that the six mixed forms already worked, six dynamic forms were absent, and
all twelve homogeneous coalesces returned wrappers instead of payloads. The user
then explicitly authorized both bounded runtime repairs. They are confined to:

- `operators/arithmetic/boxed_numeric.hpp`: one integer-kind binary worker and six
  dynamic operations, reusing integer primitives without mixed-family conversion.
- `operators/coalesce/coalesce.hpp`: exclude approved wrappers from the generic
  same-type result specialization so the wrapper-pair payload specialization wins;
  include the cast definition used by selected-value normalization. A multi-file
  native test exposed the missing definition dependency after single-unit probes
  passed. No generic cast policy or primitive shift implementation was changed.

Shared `Boxed_Operators` owns distinct integer-expression admission and six
backend-neutral operations; operator context keeps compounds excluded. Existing
ordered expression emission remains responsible for snapshots. Coalesce preparation
owns exact selected-payload joining and attaches a `coalesce_selected_value`
fallback conversion. Its emitter uses the runtime's guarded normalization only in
the lazy fallback arm. This grants no general conditional-branch extraction rights.

Owning contracts are [boxed families](../../../../specs/dynamic_types.md) and
[coalescing](../../../../specs/conditional_expression_matrix.md). Five old mixed
binary bitwise/shift TSVs now describe mixed results, matching the approved rule;
980 expectation rows were reconciled without executing a full operator matrix.
The original verification JSON and snapshot evidence retain their earlier findings.

## Verification and provenance

All execution is on `alex-ai@dev002.travelfuse.ro`, snapshot
`/home/alex-ai/boxed-selection/20261020-01`, base `842276aa`. See
[results](boxed_selection_results.json), [audit](boxed_selection_audit.json),
[raw evidence](boxed_selection_evidence.json.gz), and
[initial](boxed_selection_source_initial.json) / [final](boxed_selection_source_final.json)
source manifests. All 6,319 snapshot files passed remote and local hash audits.
The final suites record 376 expected command outcomes and 107 native executions:
101 successful executions plus six expected UBSan boundary failures. The 58
native regression programs pass; 49 source rejections and two retained recovery
cycles pass. These counts exclude historical failed candidate attempts.

The baseline recorder completed 24 measurements: six capability passes and 18
compile failures, not 24 capability passes. The source and native checks cover:

- Exact family/payload result types for all 24 approved forms, with positive and
  negative integers, zero, extrema, complement, and valid shifts at counts 0/1/63.
- Runtime rejection of null, bool, float, string (including numeric text), and
  container payload kinds on either binary side and under complement.
- Eager binary snapshots, once-only effects, RHS mutation of the LHS source and
  kind failure after both operand effects. Unary effects execute once.
- All twelve scalar wrapper pairs, usable false-like values, absent-left fallback,
  both-unusable failures, preserved destinations after failure, skipped throwing
  RHS, executed throwing RHS, and independent selected string/value snapshots.
- Preparation rejection of wider peers, updates, cross-family wrappers, widening,
  records and boolable coalescing. Two retained producer-type edit cycles prove
  unedited consumer invalidation, failed/retried output withholding, repair/no-op
  stability and fresh-session equivalence; repaired programs execute natively.
- Production core publication and cold/warm native builds with exact `2:0` output;
  warm compilation and linking are both zero.
- Focused preparation and native regression programs for boxed arithmetic,
  comparisons, selections and existing wrapper-left coalescing into mixed/dynamic.
  Stale newly-supported rejection fixtures were migrated while retaining negative
  controls for wider peers, updates and mismatched wrapper payloads.

New source/native observers use ASan/UBSan; their shared runtime object uses the
ordinary fixture build. The separate invalid-shift probe instruments the runtime
object with UBSan as well. Existing regression programs retain their runner flags.
Shared runners use 12 slots and PHP CLI fallback because CLI/FPM versions differ.
No full matrix or native compiler rebuild was run. Intermediate failed attempts
remain in the archive, including the float-oracle narrowing mistake, missing cast
header dependency, stale tests/direct-call signatures and exporter scope mistake.

## BS-R01: invalid primitive shift counts — historical finding, subsequently closed

At this checkpoint, the integer shift primitives used native C++ shifts without validating
counts. The focused boundary left-shift probe exercises -1 and 64 on scalar int, mixed and
dynamic controls with an instrumented runtime. These are diagnostic reproductions,
not supported-operation passes. Valid 0–63 behavior is independently verified.

Owner: the shared primitive integer shift-count contract and runtime validation,
including its generated source owner. This was outside the two runtime repairs
authorized for this checkpoint. The later [checked-shift goal](compact_layout_shifts.md)
separately approved and verified `ValueError` for invalid counts. Do not infer safe
exceptions, masking or saturation for invalid counts. No such behavior is certified
by the V-L09 admission closeout. Keep this finding visible alongside the remaining
original issues; it does not alter the frozen 64-issue denominator.

## Commits

- `0de3db03`: approved runtime prerequisites, contracts and direct probes.
- `a56e17be`: shared compiler admission/conversions and focused source/regression proofs.

No blocker remains within this admission/normalization slice. BS-R01 subsequently
closed in [compact_layout_shifts.md](compact_layout_shifts.md); the original
reproduction and audit archive above remain unchanged.
