# E2R: structured-error Result equality — 2026-10-19
Doc Status: supporting

Implemented and verified on `alex-ai@dev002.travelfuse.ro`, isolated snapshot
`/home/alex-ai/result-equality/20261019-01`. All **48 signatures** pass:
`== != === !==` over `result<T>/result<T>`, `result<T>/T`, `T/result<T>` for canonical
int, float, bool and string. No blocker remains within this slice. No runtime
implementation changes, full matrix rerun or native compiler rebuild.

## Contract and owner

`Wrapper_Equality` replaces the nullable-only comparison owner. Its bounded payload
query recognizes nullable and structured-error Result semantic roles. Two carrier
operands must have the same canonical type; a carrier/plain pair must have the exact
same admitted payload. Cross-family, mixed-payload and sentinel Result pairs do not
enter this path. No source-spelling recognition or implicit extraction is introduced.

The four `wrapper_equal`, `wrapper_not_equal`, `wrapper_identical` and
`wrapper_not_identical` decisions replace their nullable-specific names. Their shape
is unchanged: identity conversions of complete operands, plain bool result and no
extraction facts. The existing eager renderer saves left then right before calling
ordinary equality or the strict-identity helper. This is a local ownership/name
refactor, not a new retained protocol or runtime normalization implementation.

| Operand states | `==` | `===` |
| --- | --- | --- |
| Success / success or matching plain payload | Payload equality | Payload identity |
| Failure / success or plain payload | false | false |
| Failure / failure | true | Structured error contents match |

`!=` and `!==` invert their respective results. In the supported payload domains,
success comparisons agree: exact integer/bool values, complete string bytes and
float value equality (signed zeros equal; NaN unequal even to itself). Two errors
with different metadata compare equal but not identical. Error identity compares
status, message, file and line under `runtime/specs/spec.md` §7a; failure-state
equality follows §6.10 and the existing runtime equality operator.

Valid Result failures always contain a present error. `error::$no_error` construction
rejects, so error-status variation is represented by success/failure state differences,
not an invented failure containing an absent error. Empty error message/file and
zero line are still valid when error status is present.

Stored failures neither throw nor short-circuit comparisons. Source exceptions stop
later evaluation. Local/property RHS replacement cannot alter the saved left value
or error metadata. Existing guarded operators still require a present payload.
Evaluation order and snapshots follow `specs/expression_evaluation.md`.

## Verification on dev002

`tests/result_equality.py` runs four payload fixtures through the shared runner.
`tests/wrapper_equality_fixture.php` replaces the nullable-only fixture, preserving
its prior controls and adding independent equality/identity state oracles. Float
storage checks retain exact bits independently of value equality.

Five valid errors cover baseline metadata, independently changed message, line and
file, and an empty-message/file zero-line error. Every error pairs with every other
error and every success. Success values retain the existing integer boundaries,
bool values, float NaN/signed-zero/infinity/subnormal cases and string byte cases.

| Payload | Eager binary/error cases | Local replacement/error cases | Property replacements | Recovery sequences | Negative source checks |
| --- | ---: | ---: | ---: | ---: | ---: |
| int | 4,992 | 26,592 | 13,296 | 12 | 140 |
| float | 12,672 | 104,688 | 52,344 | 12 | 142 |
| bool | 1,232 | 3,752 | 1,876 | 12 | 140 |
| string | 21,712 | 233,128 | 116,564 | 12 | 140 |
| Total | 40,608 | 368,160 | 184,080 | 48 | 562 |

Each binary pair checks once-only left/right calls, trace order and all source-error
combinations. Left division errors precede right modulo errors. Stored failures
remain ordinary comparison inputs. Local RHS replacement also throws after changing
the destination; successful property replacement checks the saved old state and
final destination. Error storage observers check presence and all metadata fields.
No-error failure construction rejects for every payload.

Preparation checks enforce complete-state identity conversions and zero extraction.
Each signature exercises cleanup, failed edit/retry with empty artifacts, repair,
no-op and fresh compilation equivalence. Negative controls retain sentinel families,
mixed payloads, nullable/Result cross-family pairs, fixed widths, direct error-value
comparisons and ordinary float identity admission.

All **15 regression suites** pass: nullable-int equality, nullable bool/string
equality, nullable-float equality, nullable, guarded int, guarded float, guarded bool,
guarded string, guarded cross-family, guarded bool XOR, guarded spaceship, numeric
XOR, scalar string comparisons, string identity and eager binary. All nullable
payload fixtures were also rerun against the final shared fixture.

The first focused attempt failed during fixture parsing: declaration text joined
`result<T>` directly to `=`, creating a `>=` token. Adding declaration whitespace
fixed the fixture; no parser change. The initial failure is preserved in the archive.
The second attempt passed all four payloads (3.84–8.75 seconds per task). CLI fallback
was used due PHP CLI/FPM version mismatch. PHP 8.5.11, Clang 18.1.3, Python 3.12.3.

Only newly admitted negative expectations were retired in guarded/string fixtures;
other gaps remain negative. Historical reports and old proof artifacts are unchanged.
The renamed owner/fixture and operation names are recorded in the completion audit.

[Results](result_equality_results.json), [evidence](result_equality_evidence.json.gz),
[initial source manifest](result_equality_source.json),
[final source manifest](result_equality_source_final.json) and
[completion audit](result_equality_completion_audit.json) retain provenance.
All 6,218 final snapshot files matched dev002; 451 commands in successful runs had
expected exit codes. Initial failed commands remain separate. Local executable
sources match tested hashes; documentation/inventory closeout is recorded separately.

## Remaining scope and progress

V-L07 remains partial: **14 completed slices, 1,584 binary and 32 unary signatures**.
The [inventory](vl07_inventory/README.md) marks E2R's 48 rows verified and leaves E2's
96 sentinel-family rows pending. Core support targets fall from 512 to **464**;
**1,872 design/scope candidates** remain unchanged. Proposed core coverage is
**1,616 / 2,080 = 77.7%**, not all-V-L07 completion or an effort estimate.
Overall issue closure remains 18/64.

`result_or_false` and `result_or_bool` equality require their own normalization
review. Mixed payloads, cross-family equality, explicit error-sentinel comparisons,
updates, wider payload domains and table semantics remain separate. No follow-on
implementation is authorized by this closeout.
