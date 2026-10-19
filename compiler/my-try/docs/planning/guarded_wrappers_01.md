# First guarded-wrapper operator slice
Doc Status: planning

Implemented and verified on 2026-10-19. V-D13 is resolved; V-L08 is corrected for
nullable canonical int; V-L07 is partially implemented. No blockers remain within
the approved first slice. This report records implementation evidence rather than
replacing the semantic authorities.

## Approved scope

Carriers: nullable canonical int (`?int`), `result<int>`, `result_or_false<int>`.
Binary pairs: same carrier with itself, carrier/plain int, and plain int/carrier.

| Family | Forms |
| --- | --- |
| Unary | `+`, `-`, `~`, `!` |
| Arithmetic | `+`, `-`, `*`, `/`, `%`, `**` |
| Ordering | `<`, `<=`, `>`, `>=` |
| Bitwise/shift | `&`, `|`, `^`, `<<`, `>>` |
| Lazy logical | `&&`, `||`, `and`, `or` |

Results are ordinary payload-operation results: canonical int or bool, not wrappers.
Absent nullable, failed Result and false-sentinel operands throw when a required
payload is reached. Empty nullable negation no longer becomes true. Present 0/7
negate to true/false. Logical RHS extraction is skipped whenever LHS determines
the answer. Both sides are still statically prepared.

Equality/identity and sentinel comparison extensions, mutation and compound
assignment, cross-carrier pairs, `result_or_bool`, other payload types, spaceship
and xor remain separate. Other nullable payload negation behavior is neither
broadened nor certified by the nullable-int V-L08 repair. V-R10 runtime mutation
debt remains independent; the broad V-L07 issue must not be marked fully closed.

## Design and configuration

`Guarded_Operators` in `04_analyze/operators/guarded.php` recognizes registered
semantic carrier roles and exact canonical-int payloads. It selects an extraction
stage, then delegates to the existing ordinary operator owners. No source-name
special cases, implicit conversion expansion or runtime overload fallback is used.

The existing `operator_decision` adds `operand_extractions`: empty for ordinary
operations, otherwise one extraction or identity decision per source operand.
The existing payload conversions/truthiness decisions remain responsible for the
second stage. This is a bounded composition, not a general conversion-chain model.
C++ consumes these facts without deciding admission. The extraction expression
copies the required payload before an eager RHS can mutate its source. Lazy
AND/OR leave both RHS evaluation and extraction inside the native-bool branch.

`Mutation_Preparation` now passes a distinct `compound_assignment` context for
compound computation. Guarded value admission rejects that context, preventing
accidental wrapper write-back. Existing ordinary mutations and compounds remain
covered by regression tests.

V-D13 removes the stale `nullable_arithmetic` forbidden group from
`runtime/specs/config.json` and reconciles `config.review.md`. The enabled
`nullable_lifted_ops` rule and normative checked-extraction policy remain intact.
This is a specification/configuration correction, not a runtime implementation edit.

Authorities: [runtime nullable contract](../../../../runtime/specs/spec.md),
[condition/operator distinction](../../../../specs/conditional_expression_matrix.md),
[eager sequencing](../../../../specs/expression_evaluation.md).

## Focused acceptance

`tests/guarded_operators.py` runs the permanent preparation/generated-C++ proof.

| Requirement | Passing evidence |
| --- | --- |
| Admission and plain result types | 171 binary signatures and 12 unary signatures; retained extraction facts and native static type checks |
| Values, states, order and exceptions | 9,120 binary value/state/error combinations; independent LHS/RHS counts and traces 1 or 12 |
| Unary guards | 48 state/operator cases plus explicit present 7 negation and negative Result payload negation |
| Saved payloads | 495 RHS-reset cases; arithmetic/order/bitwise computation uses the saved LHS, logical guards preserve completed LHS truth |
| Source preservation | Native observers check original payload/absence/sentinel; structured failures preserve message, line and file |
| Error distinction | Nullable invalid_nullable_unwrap_empty, Result/falseable extraction messages, division_by_zero and modulo_by_zero checked independently |
| Runtime controls | Direct require_value calls independently establish all three existing carrier extraction failures |
| Condition/selection preservation | Direct if, ternary, Elvis, coalescing and presence over every carrier state; explicit nullable null comparison remains valid |
| Lazy composition and precedence | Nested guard traces, word-alias assignment and grouped RHS; skipped invalid RHS still rejects during preparation |
| Exclusions | 150 negative sources covering equality, updates, compounds on both sides, mixed payload domains, cross-carrier pairs and invalid skipped RHS |
| Retained-session behavior | Unary/binary cleanup; 12 binary edit sequences spanning arithmetic, ordering and both logical directions, repeated failure, empty artifacts, repair, no-op and fresh equality |

Shift tests use valid nonnegative small counts. Arithmetic tests avoid undefined
signed-overflow cases; this slice adds no overflow or shift-range policy.
Result extraction currently raises its existing `std::runtime_error`; nullable
raises `scpp::runtime_error` with its existing code. This work does not standardize
those runtime exception surfaces.

## dev002 verification

All executable tests ran on `alex-ai@dev002.travelfuse.ro` in isolated snapshot
`/home/alex-ai/guarded-01/20261019-01`. Shared runners used CLI fallback because
PHP CLI/FPM versions differ. Concurrent runs allowed at most 25 active tasks; no WSL tests.
Toolchain: PHP 8.5.11, Clang 18.1.3, Python 3.12.3.

Commands ran from the snapshot root with
`PATH=/home/alex-ai/language-verification/20261018/verification/modern-bin:$PATH`:

```sh
python3 compiler/my-try/tests/guarded_operators.py --results verification/focused-final
python3 compiler/my-try/tests/results.py --results verification/results-02
python3 compiler/my-try/tests/nullable.py --results verification/nullable-02
python3 compiler/my-try/tests/control_flow.py --results verification/control-flow
python3 compiler/my-try/tests/scalar_admission.py --results verification/scalar-02 --jobs 12
python3 compiler/my-try/tests/numeric_logical.py --results verification/numeric-logical
```

| Suite | Outcome |
| --- | --- |
| Guarded operators | Pass; initial proof 8.70 seconds, final strengthened proof 9.29 seconds |
| Result/extraction/take | Pass; 11 PHP suites, 166 generated programs, two native runtime controls |
| Nullable | Pass; 11 PHP suites, 99 generated programs |
| Control flow | Pass; 12 PHP suites, 135 generated programs |
| Scalar admission/sequencing | Pass; ten PHP suites, seven generated programs |
| Numeric logical | Pass; existing aliases/mixed pairs and lazy behavior |

The compiler implementation was unchanged throughout these runs. Original failed
attempts are retained:

- Initial Result and nullable suites stopped at the shared S2S negative fixture
  requiring `?int + int` to reject. That obsolete rejection was removed; the new
  positive suite proves the expression and its failure-state behavior.
- Initial scalar suite stopped on an expected diagnostic substring for an invalid
  mixed/nullable pair. Three mixed/wrapper expectations now match the guarded
  admission owner; rejection and empty-output assertions remain intact.

Other replaced stale negatives were Result-int negation in `results.php` and two
wrapper logical cases in `numeric_logical.php`. New focused positives cover them.
No failed attempt was rewritten as an initial pass.

## Evidence and remaining work

- [Results](guarded_wrappers_01_results.json): commands, summaries, policy checks and checksum.
- [Evidence archive](guarded_wrappers_01_evidence.json.gz): all attempts and toolchain/source integrity.
- [Initial source](guarded_wrappers_01_source.json) and [final source](guarded_wrappers_01_source_final.json): hashes and fixture revision explanation.
- [Baseline](guarded_wrappers_01_baseline.json) and [completion audit](guarded_wrappers_01_completion_audit.json): pre-existing work preserved, tested code agreement and allowed configuration-only runtime-tree changes.

No runtime implementation changes, full matrix rerun or native compiler rebuild.
Future V-L07 work must discuss writable carrier updates and the remaining domains
separately. No follow-on goal was started.
