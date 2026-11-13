# Float-destination/int64-RHS compounds

Doc Status: supporting

The subsequent [mixed numeric family slice](mixed_numeric_families.md) extends this
coverage to byte/float and replaces concrete promotion metadata with family rules.
The implementation and verification below record the original int64/float64 slice.

The [scalar contract](../../../specs/compiler_scalars_v0_2.md#float-destinationint64-rhs-compounds--2026-11-06)
and [compound semantics](../../../specs/expression_evaluation.md#float-destination-mixed-compounds--2026-10-19)
own this slice. All flavors admit float locals/value parameters/reference parameters
with int64 RHS for `+= -= *= /=`, including existing for-header positions.
Integer destinations with float RHS, global/field/indexed writes, carriers and
value-producing assignments remain outside this slice.

## Metadata, ownership and structures

The float64/int64 arithmetic promotion row now has explicit `compound_operators`.
Omission means no mixed compound permission. The generator requires each listed
operator to be admitted by the promotion and arithmetic execution definitions,
with destination = execution = result. Existing conversion permission and behavior
are reused; binary promotion alone never grants compound permission.

`type_definition::prepare_compound(ast_assignment_statement): void`
owns the preparation query. Its default preserves same-type admission/diagnostics.
The generated float64 override admits the metadata-approved RHS and attaches the
existing numeric_floating conversion with identity left/write-back conversions,
still gated by `supports_compound($operator)`.
Shared preparation resolves the destination once, visits the RHS once and calls
that query. There is no metadata lookup, synthetic binary node or constant folding.

The original float slice added no AST properties/classes or enums. The subsequent
integer extension adds the calculation/write-back facts described below; there is
still no separate preparation record.
The existing assignment conversion fact now describes the approved RHS conversion
for either ordinary or compound assignment. Reset still clears it; compound results
remain unknown. Lowering consumes that conversion through existing generated handlers
before the selected arithmetic operation. The old destination snapshot and final
write remain in the same emission path, shared with for headers. Runtime code,
watch ownership, cleanup and incremental queues are unchanged.

The integer converts before arithmetic, with observable rounding above 2^53.
Division by integer zero throws before the final write, even for NaN/infinite old
values. Earlier RHS mutations remain visible after failure. Other operations keep
native floating overflow/NaN/underflow/signed-zero behavior.

## Verification — 2026-11-06

All three flavors cover all four operators, local/value/reference storage, for
headers, RHS destination mutation, precision above 2^53 and integer limits. Native
observers check zero-division storage preservation, RHS failure/mutation effects,
NaN/infinity and signed underflow. Preparation asserts one RHS visit, retained
conversion/binding, reset/reprepare, unknown post-write constant and emission purity.
Negative tests preserve reverse-direction, bool/byte/null and remainder restrictions.
Generator tests reject invalid direction, missing promotion operation and duplicates;
removing compound permission removes its generated handler.

Incremental tests cover body-only edits, int/float signature transitions, invalid
bool signatures, literal overflow, retries without edits, declaration deletion and
re-addition, caller edits and no-op reuse. Unchanged caller ASTs survive; successful
output matches fresh compilation byte for byte and fresh runs diagnose the same
failures. Four successful incremental checkpoints plus a caller edit run natively
per flavor. All 21 new native fixtures and 12 selected regression CTests passed.
Compiler style, diff whitespace and runtime freshness checks passed.

```sh
XDEBUG_MODE=off php v0.2/compiler/tests/mixed_compounds.php
XDEBUG_MODE=off python3 -B v0.2/compiler/tests/mixed_compounds_native.py --output v0.2/build/mixed-compounds-proof-new
```

## Clang timings

O2/g0, libc++, Ninja8, mold, no PCH/LTO; prebuilt runtime; fresh instrumented app
objects. Single correctness samples, not averages. Frontend/LLVM totals are trace
work and are not additive components of parallel wall time. LLVM includes optimizer
and code generation.

| Main fixture | Native wall ms | Frontend ms | LLVM backend ms | Link ms |
| --- | ---: | ---: | ---: | ---: |
| cpp | 194.13 | 18.45 | 17.92 | 99 |
| php | 169.63 | 13.94 | 13.81 | 73 |
| js | 147.98 | 11.92 | 11.98 | 69 |

Recovered checkpoint artifacts, built fresh natively: wall 117.26–185.23 ms,
frontend 9.77–22.56 ms, LLVM 14.35–30.71 ms, link 58–102 ms.
Standard-library-heavy native observers: wall 670–1669 ms, frontend 509–1429 ms,
LLVM 37–53 ms, link 63–78 ms. Their math/exception test headers are not ordinary
emitted application dependencies. These noisy single samples do not establish a
flavor performance difference.
PHP fixture generation and semantic/incremental checks: 255.50 ms; not an isolated
preparation timing. Exact visit assertions verify the single RHS traversal.

Raw results/build traces: `v0.2/build/mixed-compounds-proof-2026-11-06/`.

The [integer write-back extension](integer_compounds.md) generalizes prepared compound
facts to separate calculation and destination identities; float admission is unchanged.
