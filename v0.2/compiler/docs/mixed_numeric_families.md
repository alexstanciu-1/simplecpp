# Mixed numeric operator families

Doc Status: supporting

The [scalar contract](../../../specs/compiler_scalars_v0_2.md#mixed-numeric-families--2026-11-06)
extends mixed integer/floating operators to every currently active integer representation:
int64/int and uint8/byte with float64/float, in either operand order.

| Group | Operators | Execution/result |
| --- | --- | --- |
| Arithmetic | + - * / | Floating operand representation / same floating type |
| Comparisons | == != < <= > >= | Floating operand representation / bool |
| Floating-destination compounds | += -= *= /= | Floating execution and destination; identity write-back |

Integer operands convert numerically before execution. All byte values are exactly
representable in float64; int64 conversion retains rounding above 2^53. Division
rejects either floating zero sign and integer zero. Existing floating overflow,
NaN/infinity, underflow and signed-zero behavior is reused unchanged. Failed compound
calculation prevents its store; effects already performed by RHS evaluation remain.

Remainder with floating operands, integer-destination floating compounds, bool numeric
operators, new byte truthiness/unary/update rules and optional representations are
outside this slice. Ordinary assignment remains same-canonical-type-only.

## Ownership and structures

`compiler.mixed_numeric_operators` replaces the concrete mixed `compiler.promotions`
metadata rows. It names the `floating_operand` selection policy and three operator
lists: arithmetic, comparisons and independently authorized compound operators.
`compilerMixedNumericOperations()` expands active integer/floating pairs before family
conversions derive promotion permissions. It generates transient directional rows for
the existing promotion validator and renderer. Comparison result identity comes from
the floating comparison contract; execution operations and conversion strategies still
must exist and validate. Raw family policy and concrete promotion rows cannot coexist.

The generated handlers use direct canonical identity/operator checks and existing
`settle_binary` / `settle_compound` facts. No metadata interpretation, new AST nodes,
synthetic casts, retained matrix, watcher/queue changes or extra preparation traversal.
The family expansion happens only during API generation. Runtime ABI/helpers and
lowering implementation remain unchanged. Existing normalizer tests can still supply
concrete rows internally to validate the renderer boundary.

## Verification

`tests/mixed_numeric_families.php` exercises every admitted operator in both directions,
alias spellings, result/execution identity, exact visit counts, reset/reprepare, emission
purity, reference writes and for-header compounds in cpp/php/js. It checks rejected
operands, remainder, narrowing compounds and cross-type ordinary assignment.

Incremental tests change provider bodies and signatures through float, byte, uint8,
int and invalid bool; exercise literal overflow, retry without edits, deletion,
re-addition and caller edits; retain unchanged caller ASTs; compare recovery artifacts
with fresh compilation and verify no-op preparation does no work.

The 24 native fixtures include all three flavors and recovered applications. Native
observers check all 256 byte values against finite extremes, fractional boundaries,
NaN, infinities, signed zero and subnormals, covering all arithmetic/comparisons and
compound operations, plus failed-store/RHS-side-effect behavior. Existing int64 mixed
native proofs retain coverage for rounding above 2^53 and signed extremes.

## Timings

O2/g0, libc++, Ninja8, mold, no PCH/LTO; prebuilt runtime, fresh instrumented application
objects. Single verification observations, not flavor performance comparisons. LLVM
includes optimization/code generation; frontend and LLVM trace work do not sum with
link time to parallel native wall time.

| Main fixture | Native wall ms | Frontend ms | LLVM backend ms | Link ms |
| --- | ---: | ---: | ---: | ---: |
| cpp | 134.64 | 10.51 | 7.97 | 71.00 |
| php | 142.85 | 11.25 | 8.41 | 66.00 |
| js | 180.86 | 16.88 | 12.59 | 84.00 |

PHP fixture generation, semantic and incremental checks: 230.73 ms total, not an isolated preparation benchmark.
Raw evidence: `v0.2/build/mixed-numeric-families-proof-2026-11-06/results.json`.

Generator checks passed: 19 accepted/537 rejected scalar cases, 130 accepted/247
rejected runtime-family cases, and 17 accepted/37 rejected CLI cases. Scalar artifact
hash sets and runtime-family snapshots stayed identical; only metadata-derived case
identities/input fingerprints changed. Rendering the previous metadata through the
new generator preserves every compiler artifact byte-for-byte except the intended
uint8 and float64 handlers. The registry, int64/bool/null/string handlers, entry
binding and inventory remain identical.
The focused semantic/style/generator set (11 tests) and existing mixed numeric native
regressions plus runtime freshness/family contracts (6 tests) all passed.
