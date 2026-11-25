# Guarded spaceship — 2026-10-19
Doc Status: supporting

Implemented and verified on `alex-ai@dev002.travelfuse.ro`: all **48 canonical-int
and string signatures** pass. No blockers remain within this slice. V-L07 remains
partially implemented. No runtime changes, full matrix rerun or native compiler
rebuild were involved.

## Scope and implementation

For each of canonical int/int and string/string, `<=>` admits four same-family,
twelve ordered cross-family and eight wrapper/plain signatures. Families are
nullable, `result`, `result_or_false` and `result_or_bool`. The result is plain
canonical int with exact value -1, 0 or 1. String ordering uses complete unsigned
bytes, without numeric-string coercion.

`Guarded_Operators` adds spaceship to its supported operations and bounds its
payload gate to canonical int or exact string. Each operand still undergoes
independent checked extraction; scalar preparation selects the existing integer
or string spaceship decision and rejects mismatched payloads. The existing eager
renderer snapshots the left value before RHS effects. No new retained structures,
scalar decisions, emitter paths or runtime implementations were needed.

The gate does not inherit the wider fixed-width integer domain of ordinary scalar
spaceship. Float, bool, fixed-width and mixed-payload guarded pairs remain excluded.
Absent/error/sentinel states retain their extraction errors rather than being
compared as values. Both boolean sentinel states lack payloads.

## Verification

`guarded_spaceship.py` invokes the integer and string fixtures in `--spaceship`
mode through the shared runner. It validates each complete 24-signature set against
an independent enumeration and checks plain canonical-int results at preparation
and native compile time.

| Domain | Binary value/state/source-error cases | Local snapshot/state cases | Successful property snapshot cases | Retained edit sequences |
| --- | ---: | ---: | ---: | ---: |
| Canonical int | 7,844 | 3,299 | 2,624 | 24 |
| String | 34,804 | 41,478 | 37,908 | 24 |
| Total | 42,648 | 44,777 | 40,532 | 48 |

Integer observers use comparisons, not subtraction, so the oracle cannot overflow
at minimum/maximum values. Dedicated spaceship arrays include both extremes and
positive/negative integers beyond exact double precision. These values do not enter
unary-minus or arithmetic controls. Boundary values also participate in local and
property snapshot checks.

String observers use an independent unsigned-byte loop and length comparison over
empty values, prefixes, numeric-looking strings, embedded NUL, high bytes and UTF-8
byte sequences. Exact integer comparison of results distinguishes -1 from +1; the
oracle does not coerce spaceship results to bool. The fixture additionally retains
5,832 earlier wrapper/plain local/property snapshot controls.

Both domains combine all payload and unavailable states with independently injected
left/right source failures. Counters and traces verify once-only eager order,
left-error precedence and no RHS evaluation after a left failure. Input state
observers preserve structured Result error fields and sentinel distinctions.
RHS replacement proves that the saved left payload survives local/property writes.
Local checks include failing extraction and visible prior effects; property checks
cover successful operations.

All 48 retained sessions verify invalid equality edits, repeated failures with
empty artifacts, operand-reversal repair, no-op stability and fresh equivalence.
An additional 672 exclusion probes cover all carrier/plain placements of selected
fixed-width, float, bool and mixed-payload combinations. Existing equality, numeric
XOR, mixed-ordering and update exclusions remain.

All seven companion suites passed: `guarded_operators`, `guarded_string`,
`guarded_cross` (the existing 660 signatures), `guarded_bool_xor`, `eager_binary`,
`scalar_admission`, and `string_comparisons`. Only newly valid spaceship negatives
were retired: same-family int/string, corresponding shared cross-family checks,
and six wrapper/plain checks in ordinary string comparisons. Original evidence
and unrelated exclusions remain intact.

## Provenance

Remote snapshot: `/home/alex-ai/guarded-spaceship/20261019-01`.
Toolchain: PHP 8.5.11, Clang 18.1.3, Python 3.12.3. PHP used shared-runner CLI fallback
because installed CLI/FPM versions differ. All executable verification ran on dev002.

```sh
PATH=/home/alex-ai/language-verification/20261018/verification/modern-bin:$PATH \
  python3 compiler/my-try/tests/guarded_spaceship.py --results verification/focused-01
```

Companion runners used fresh `verification/<suite>` directories; `scalar_admission`
received `--jobs 12`. Two bounded regression runners plus the focused tasks stayed
below 32 concurrent verification tasks. Both focused tasks passed on the first
attempt: int in 5.77 seconds, string in 7.12 seconds.

Remote integrity verified 6,207 files; the archive contains 283 evidence records.
See [results](guarded_spaceship_results.json), [evidence](guarded_spaceship_evidence.json.gz),
[baseline](guarded_spaceship_baseline.json), [source](guarded_spaceship_source.json),
[final source](guarded_spaceship_source_final.json), and
[completion audit](guarded_spaceship_completion_audit.json). Documentation closeout
is separate from the tested executable snapshot.

## Remaining work

Spaceship for other types, mixed ordering, numeric/string XOR, equality/identity
and sentinel comparisons, updates/compounds and additional payload/operator domains
remain separate. V-R10 runtime mutation debt is independent. Overall issue closure
remains 18/64 (28.1%), with V-L07 partial.
