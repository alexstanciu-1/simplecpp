# E1: nullable-float equality — 2026-10-19
Doc Status: supporting

Implemented and verified on `alex-ai@dev002.travelfuse.ro`, isolated snapshot
`/home/alex-ai/nullable-float-equality/20261019-01`. All **12** `== != === !==`
signatures over `?float/?float`, `?float/float`, `float/?float` pass. No blocker
remains in this slice. No runtime changes, full matrix rerun or native compiler rebuild.

## Implementation and semantics

`Nullable_Equality::is_admitted_payload` now admits matching canonical float payloads
alongside int, bool and string. Existing four nullable comparison decisions retain
identity conversions of complete operands with no guarded extraction. Existing eager
emission saves left then right before invoking runtime equality/identity helpers.
No retained-model, conversion-policy or emitter changes were required.

Empty/empty is equal and identical. Empty/present is unequal and non-identical,
including zero and NaN. Present floats use ordinary value equality: signed zeros
compare equal, equal-sign infinities compare equal, and NaN compares unequal even
to itself. Negated forms invert the result. Strict identity is value identity in
this matching-float domain, not bit identity.

An empty left state still evaluates the RHS. Source errors stop subsequent evaluation;
earlier effects remain. RHS replacement of local/property state cannot change the
saved left operand. Literal-null presence remains separate, and guarded ordering
still throws `invalid_nullable_unwrap_empty` on empty nullable operands.

Semantic references: `runtime/specs/spec.md` §§6.10/7a and
`specs/expression_evaluation.md`. Existing runtime helpers were reused without edits.

## dev002 proof

The new `tests/nullable_float_equality.py` entry point uses the existing shared
`nullable_equality_fixture.php`. Float result oracles use native `double` equality;
independent storage observers compare exact 64-bit representations. This prevents
NaN self-inequality from falsely reporting changed storage and detects lost zero
signs or altered quiet-NaN payload bits.

The 13 present values are ±0, ±1, 1.5, largest positive/negative finite values,
minimum positive normal/subnormal, ±infinity and two distinct quiet-NaN payloads.
Nullable adds an empty state. Signaling-NaN floating-environment behavior is outside
this proof.

| Check | Count |
| --- | ---: |
| Distinct signatures returning bool | 12 |
| Eager binary/error cases | 8,960 |
| Local replacement/error cases | 61,264 |
| Property replacement cases | 30,632 |
| Retained invalid-edit/repair/no-op/fresh sequences | 12 |
| Negative source executions | 102 |

All binary pairs exercise left/right source-error combinations, counters and ordered
trace. Left division errors take precedence over right modulo errors. Local snapshots
also test RHS throwing after replacement; property snapshots cover successful RHS
replacement. Preparation checks retain two identity conversions and zero extractions;
cleanup and failed edits clear artifacts. Unsupported mixed payloads, Result families,
fixed widths and ordinary float `===`/`!==` remain negative controls.

All nine regressions pass: nullable-int equality, nullable bool/string equality,
nullable, float operators, guarded float, guarded cross-family, numeric XOR,
eager binary and scalar admission. The E1 proof passed first attempt in 8.01 seconds.
The shared runner used CLI fallback due PHP CLI/FPM version mismatch. Toolchain:
PHP 8.5.11, Clang 18.1.3, Python 3.12.3.

Only superseded negative expectations changed: the shared nullable fixture replaces
its 12 homogeneous nullable-float rejections with this positive proof; guarded-float
and shared cross-pair fixtures skip those newly admitted comparisons. Previous int,
bool and string fixtures now execute 100 exclusions each; their historical 112-count
reports remain unchanged. E1 adds two ordinary float identity exclusions for 102.

[Results](nullable_float_equality_results.json),
[evidence](nullable_float_equality_evidence.json.gz),
[final source hashes](nullable_float_equality_source_final.json) and
[completion audit](nullable_float_equality_completion_audit.json) retain the proof.
All 6,216 transferred source files matched; all 399 command records have expected
exit codes. Local executable sources match the tested snapshot. Documentation and
the static inventory were updated separately after verification.

## Progress and remaining scope

V-L07 remains partial: **13 completed slices, 1,536 binary signatures and 32 unary
signatures**. E1 moves 12 rows from remaining to verified in the
[inventory](vl07_inventory/README.md). There are **512 support targets** and **1,872
design/scope candidates** left. The proposed core denominator stays 2,080 signatures:
1,568 verified gives **75.4%**. This is conditional core coverage, not overall V-L07
completion or engineering effort; overall issue closure remains 18/64.

Plain float identity admission, mixed numeric pairs, Result/sentinel equality,
writable updates, wider payload types and table semantics remain separate. The next
discussion is E2, split by carrier/state normalization. No follow-on implementation
is authorized by this closeout.
