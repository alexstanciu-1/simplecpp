# Mixed int64/float64 equality

Doc Status: supporting

The subsequent [mixed numeric family slice](mixed_numeric_families.md) extends this
coverage to byte/float and replaces concrete promotion metadata with family rules.
The implementation and verification below record the original int64/float64 slice.

The [scalar contract](../../../specs/compiler_scalars_v0_2.md#mixed-int64float64-equality--2026-11-06)
and [ordinary equality specification](../../../specs/expression_evaluation.md#mixed-numeric-ordinary-equality-for-nullable-and-result--2026-10-20)
own this slice. All flavors admit plain int64/float64 `== !=` in either operand
order. Integer operands convert to float64 before comparison; the result is bool.
Rounding above ±2^53 and at int64 limits is observable. Signed zeros compare equal;
NaN is unequal, including to itself; `!=` negates `==`. No operand storage is changed.
Strict identity, carriers and bool/numeric mixed equality remain deferred. Byte/float
equality is now covered by the family slice; string equality has its own implemented slice.

## Implementation and structure

The existing int64-to-float64 conversion permission and both directional comparison
promotion rows add `equal` and `not_equal`. The generator's bounded supported-operator
validation admits these names and still validates permission, execution operation
and bool result through the shared binary plan. Missing permissions or float equality
operations are generator errors. Runtime metadata remains the admission source.

Generated int64/float64 preparation handlers use the existing execution/conversion
facts and float comparison lowering. No changes to handwritten preparation or
emission, no AST fields/classes, enums, runtime helpers or retained structures.
Each operand is prepared once, with no synthetic casts, metadata lookup, conversion
search or constant folding. Existing sequencing and incremental ownership remain.

## Verification — 2026-11-06

All three flavors cover both operators/directions, rounding above ±2^53, int64
limits, fractions, zeros, nonfinite native inputs and once-only source calls.
Assertions check bool results, float execution, conversion direction, exact visit
counts, reset/reprepare and emission purity. Bool/byte/null/string combinations
remain rejected. Generator negatives check missing permission/execution support.

Incremental cases cover provider body edits, int/float signature transitions, invalid
bool signatures, overflow diagnostics, retries without edits, declaration deletion
and re-addition, caller edits and no-op reuse. Unchanged caller ASTs survive;
successful output equals fresh C++/headers byte for byte. Fresh runs diagnose the
same errors. Four recovered checkpoints and one caller edit run natively per flavor.
All 21 new native fixtures and 12 selected CTests passed. Style, whitespace and
runtime freshness checks passed. No native threading claim is made.

```sh
XDEBUG_MODE=off php v0.2/compiler/tests/mixed_equality.php
XDEBUG_MODE=off python3 -B v0.2/compiler/tests/mixed_equality_native.py --output v0.2/build/mixed-equality-proof-new
```

## Clang timings

O2/g0, libc++, Ninja8, mold, no PCH/LTO; prebuilt runtime and fresh instrumented
application objects. Single correctness samples, not averages or evidence of a
flavor performance difference. Frontend/LLVM trace work is not additive parallel
wall time; LLVM includes optimizer/code generation.

| Main fixture | Native wall ms | Frontend ms | LLVM backend ms | Link ms |
| --- | ---: | ---: | ---: | ---: |
| cpp | 165.53 | 17.55 | 17.66 | 72 |
| php | 127.25 | 9.04 | 6.80 | 69 |
| js | 197.50 | 18.13 | 21.69 | 73 |

Recovered checkpoint artifacts, built fresh natively: wall 118.52–155.70 ms,
frontend 10.90–21.25 ms, LLVM 15.58–25.01 ms, link 56–76 ms.
Native nonfinite observers: wall 147.24–158.77 ms, frontend 28.61–38.65 ms,
LLVM 11.25–14.20 ms, link 59–62 ms. Test headers are not emitted app dependencies.
PHP fixture generation plus semantic/incremental checks took 290.11 ms; this is
not an isolated preparation measurement. Exact visit assertions verify one traversal.

Raw build/results: `v0.2/build/mixed-equality-proof-2026-11-06/`.
