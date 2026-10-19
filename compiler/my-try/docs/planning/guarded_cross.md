# Cross-family guarded operators — 2026-10-19
Doc Status: supporting

Implemented and verified on `alex-ai@dev002.travelfuse.ro`. All **660 approved
signatures** pass. V-L07 remains partially implemented; no blockers remain within
this slice. There were no runtime changes, full matrix rerun or native compiler rebuild.

## Scope and implementation

The four families are nullable, `result`, `result_or_false` and `result_or_bool`.
Each of the 12 ordered distinct-family pairs now supports the existing bounded
payload operations:

| Payloads | Spellings per pair | Signatures |
| --- | ---: | ---: |
| int/int: arithmetic, ordering, bitwise/shift, AND/OR aliases | 19 | 228 |
| float/float: arithmetic, ordering, AND/OR aliases | 12 | 144 |
| int/float and float/int: arithmetic, AND/OR aliases | 8 each | 192 |
| bool/bool: AND/OR aliases | 4 | 48 |
| string/string: ordering | 4 | 48 |
| Total | | 660 |

`Guarded_Operators` owns independent checked extraction. Its obsolete family
comparison and helper were removed; it still delegates actual payload/operator
admission to the existing scalar owners. The fallback diagnostic now describes
independent extractability. There are no new retained structures, emitter paths,
or runtime implementations. Compound context remains explicitly rejected.

## Verification

The shared runner executes the five existing payload fixtures in cross mode,
with one shared snippet runtime object prepared before parallel compilation.
A manifest records every signature; closeout compares the complete set against
an independently enumerated 660-signature expectation, not just its count.

| Payload domain | Local snapshot/state cases | Property snapshot cases | Cross-family retained edit sequences |
| --- | ---: | ---: | ---: |
| int | 7,611 | 4,314 | 12 |
| float | 12,384 | 8,862 | 12 |
| mixed numeric | 12,528 | 8,577 | 24 |
| bool | 648 | 312 | 12 |
| string | 40,128 | 34,992 | 12 |
| Total | 73,299 | 57,057 | 72 |

These supplement the signature value/state/source-error products. Independent
oracles check numeric values, signed zero, NaN/infinities, mixed precision, exact
unsigned-byte string ordering, and distinct bool payload/sentinel states.
Integer power and shift observers restrict inputs to the existing valid domain.

For eager operations, the left source runs and extracts before the right source.
Distinct nullable/Result/falseable/boolean-sentinel extraction errors make left
precedence observable. Source-call failures, unavailable payloads and arithmetic
zero failures are combined independently. Source counters and traces prove
once-only order; all input states, including structured error fields, survive.
Lazy AND/OR aliases skip RHS calls and extraction when appropriate, including both
boolean sentinel states. Division preserves `division_by_zero`.

RHS replacement of actual LHS storage proves that checked payload values were
saved first. Local tests include failing left/right extraction and visible effects;
property tests cover successful operations, including skipped RHS and byte strings.
The 72 retained sessions prove invalid edits, repeated failure with empty artifacts,
repair by operand reversal, no-op stability and fresh-build equivalence. Excluded
operators and statically invalid skipped expressions still reject.

All ten companion suites passed: `results`, `nullable`, `string_comparisons`,
`string_identity`, `guarded_operators`, `guarded_float`, `guarded_mixed`,
`guarded_bool`, `guarded_string`, and `scalar_admission`. Existing guarded fixture
negatives were retired only where this slice admits them; original dated evidence
and genuine gaps remain. Ordinary modes preserve their prior positive coverage.

## Provenance and attempts

Remote snapshot: `/home/alex-ai/guarded-cross/20261019-01`.
Toolchain: PHP 8.5.11, Clang 18.1.3, Python 3.12.3. Shared runner used CLI fallback
because installed PHP CLI/FPM versions differ. Five focused tasks and two bounded
12-slot regression runners stayed below 32 concurrent verification tasks.

Commands from the remote snapshot:

```sh
PATH=/home/alex-ai/language-verification/20261018/verification/modern-bin:$PATH \
  python3 compiler/my-try/tests/guarded_cross.py --results verification/focused-02
# Each companion suite uses its own fresh verification/<suite> directory.
# scalar_admission additionally receives --jobs 12.
```

The first attempt exposed a runner setup error: parallel fixture constructors
registered the same runtime-build command. Bool completed; the other tasks failed
at setup. Moving the shared build before scheduling corrected the harness.
All five domains then passed; the slowest task took 22.28 seconds. No additional
compiler changes were needed. Both attempts' command logs are preserved.

Evidence: [results](guarded_cross_results.json), [compressed logs](guarded_cross_evidence.json.gz),
[baseline](guarded_cross_baseline.json), [initial snapshot](guarded_cross_source.json),
[final tested snapshot](guarded_cross_source_final.json), and
[completion audit](guarded_cross_completion_audit.json). Remote integrity verified
6,205 source files; the archive contains 2,063 evidence records. Documentation
closeout is separate from the tested snapshot.

## Remaining work

Equality/identity and sentinel comparison rules, mixed numeric ordering,
mutation/compounds, spaceship, logical xor and other payload/operator domains
remain separate. Bool/numeric mixing and string logical operators stay excluded;
those exclusions do not imply promises of future admission. V-R10 runtime mutation
debt remains independent. Overall issue closure is still 18/64 (28.1%), with
V-L07 partial; see [overall progress](language_repair_progress.md).
