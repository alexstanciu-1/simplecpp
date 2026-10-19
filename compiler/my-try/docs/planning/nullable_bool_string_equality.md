# Nullable bool/string equality — 2026-10-19
Doc Status: supporting

Implemented and verified on `alex-ai@dev002.travelfuse.ro`, snapshot
`/home/alex-ai/nullable-bool-string-equality/20261019-01`. This extends
[nullable-int equality](nullable_int_equality.md) by 24 signatures: `== != === !==`
over `?T/?T`, `?T/T`, `T/?T` for matching `bool` or `string` payloads. No runtime
implementation changes, full matrix rerun or native compiler rebuild.

## Contract and ownership

`Nullable_Equality` remains the preparation owner. Its payload admission now checks
that the two normalized payload types match and are canonical int, bool or string.
The same four named decisions retain identity conversions of whole operands and
no guarded extraction facts. Existing eager lowering saves left then right and
calls the existing runtime comparison helpers. No emitter or retained-model change.

Two empty states compare equal; empty versus any present value compares unequal,
including false, empty string and zero string. Present bools compare exact values;
strings compare complete byte sequences without numeric interpretation or Unicode
normalization. Equality and identity agree in these matching-payload domains.
Negated forms invert their result. Empty left operands still evaluate the RHS;
left source errors stop the RHS, and right errors retain preceding effects.
Local/property replacement cannot change the saved left state or payload.

Literal-null presence stays on its existing path. Guarded bool negation, string
ordering and integer arithmetic/ordering still report unavailable-payload errors.
The normative owners remain `specs/expression_evaluation.md`,
`runtime/specs/spec.md` sections 6 and 7a (wrapper equality and strict identity); this report is
implementation evidence, not new language semantics.

## Verification

The shared `tests/nullable_equality_fixture.php` serves the existing int runner and
the new `tests/nullable_bool_string_equality.py` runner. Each payload checks all 12
signatures, no-extraction preparation facts, cleanup, invalid-edit artifact clearing,
repair/no-op/fresh equivalence and 112 negative-source executions. The native oracle
checks presence first, then independent bool equality or a length/byte loop.

| Payload | Eager binary/error cases | Local snapshot/error cases | Property snapshots | Recovery sequences |
| --- | ---: | ---: | ---: | ---: |
| Bool | 336 | 456 | 228 | 12 |
| String | 16,720 | 156,104 | 78,052 | 12 |
| Total | 17,056 | 156,560 | 78,280 | 24 |

Bool tests cover false, true and absent states. Strings include empty, numeric-looking,
ASCII/prefix/case differences, embedded NUL, high bytes and distinct Unicode byte
sequences, plus absence. Every source failure combination checks eager order and
distinct `division_by_zero` / `modulo_by_zero` precedence. Snapshot probes replace
payloads and presence states; local probes also throw after replacement.

All eleven regressions passed: nullable-int equality, nullable, scalar string
comparisons, string identity, guarded bool, guarded string, cross-family guarded
operators, guarded bool XOR, eager binary, guarded spaceship and scalar admission.
Both new payloads passed first attempt and again after restoring the shared integer
guarded-add control; the final integer fixture also passed. The shared runner used
CLI fallback because the server's PHP CLI/FPM versions differ.

The old nullable-int fixture's 136 exclusion executions become 112: its 24 now-admitted
bool/string cases move to this positive proof. Related bool/string/cross-family
fixtures retire only negatives admitted by this scope. Mixed payloads, floats,
fixed-width integers and Result/sentinel comparisons remain negative. Historical
reports and evidence retain their original counts and results.

[Results](nullable_bool_string_equality_results.json),
[evidence archive](nullable_bool_string_equality_evidence.json.gz),
[final source manifest](nullable_bool_string_equality_source_final.json) and
[completion audit](nullable_bool_string_equality_completion_audit.json) record the
proof. All 6,214 snapshot files matched on dev002; local executable files match the
final tested hashes. Documentation closeout is separate from that source snapshot.

## Remaining scope

No blocker remains inside the approved 24 signatures. V-L07 stays partial: float,
Result/sentinel and mixed/cross-family equality need separate discussion, as do
writable updates/compounds, mixed numeric ordering and additional payload/operator
domains. V-R10 runtime mutation debt remains independent.

V-L07 now has **12 completed slices, 1,524 binary signatures and 32 unary signatures**.
This excludes four plain numeric-XOR prerequisites. There is no settled remaining
signature denominator for a meaningful V-L07 percentage. See the
[progress breakdown](language_repair_progress.md#inside-v-l07); the overall issue
ledger remains 18/64 fully closed, with V-L07 one partial issue.
