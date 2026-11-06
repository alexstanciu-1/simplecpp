# Mixed int64/float64 arithmetic

Doc Status: supporting

The subsequent [mixed numeric family slice](mixed_numeric_families.md) extends this
coverage to byte/float and replaces concrete promotion metadata with family rules.
The implementation and verification below record the original int64/float64 slice.

The [scalar contract](../../../specs/compiler_scalars_v0_2.md#mixed-int64float64-arithmetic--2026-11-06)
owns the semantics. All flavors admit `+ - * /` in either operand order, converting
int64 to float64 before execution and producing float64. Above 2^53 the conversion
can round: `9007199254740993 - 9007199254740992.0` produces 0.0. Division rejects
integer zero and both floating zero signs; other floating results retain the existing
NaN/infinity/underflow/signed-zero contract. Integer-only division is unchanged.
Float-destination mixed compounds are now covered by [their own permission](mixed_compounds.md).
[Mixed equality](mixed_equality.md) also uses this path; remainder and bool arithmetic remain deferred; byte/float is now covered by the family slice.

## Ownership and structure

`compiler.promotions` adds two arithmetic rows, one per direction, with float64
execution/result. The existing int64-to-float64 conversion row authorizes the four
additional operators independently of its boundary-conversion contexts.

The API generator's `compilerBinaryPlan()` now validates comparison and arithmetic
execution operations once. Binary-handler rendering and promotion validation consume
that same generation-time index. A promotion needs both a permitted conversion and
an existing execution operation with the declared result; missing operations fail
generation. Existing checked-division bindings and lowering safety remain authoritative.

No compiler AST classes, properties, enums, preparation records, watchers or queues
were added. Existing binary nodes retain result/execution identities and left/right
conversion facts. Generated handlers dispatch directly; common preparation visits each
operand once. There is no preparation-time metadata lookup, conversion search,
synthetic cast AST, new folding or execution-order mechanism. Runtime code is unchanged.

## Verification — 2026-11-06

`tests/mixed_arithmetic.php` covers all operators/directions and flavors, exact
expression-visit counts, result/conversion facts, reset/reprepare and emission purity.
Native cases cover rounding above 2^53, int64 extremes, integer-only truncation,
nonfinite values, subnormals, signed zero and checked division. Calls run once with
the existing sequencing behavior. Unsupported operands, remainder, ordinary
assignment conversions and mixed compounds retain their restrictions.

Incremental proofs exercise body changes, signature transitions between float/int,
invalid bool signatures, literal overflow, retries without edits, declaration deletion,
re-addition, caller expression edits and no-op reuse. Unchanged caller ASTs survive;
recovered native artifacts match fresh compilation byte for byte. Four successful
incremental checkpoints per flavor and the caller edit are executed natively.
Fresh compilation also diagnoses the invalid signatures, overflow and missing function.

All 21 native fixtures passed. The 15 selected generation/arithmetic/comparison/
compound CTests passed after updating obsolete mixed-arithmetic rejection assertions.
Compiler style and diff whitespace checks passed. No native MT claim is made.

```sh
XDEBUG_MODE=off php v0.2/compiler/tests/mixed_arithmetic.php
XDEBUG_MODE=off python3 -B v0.2/compiler/tests/mixed_arithmetic_native.py --output v0.2/build/mixed-arithmetic-proof-new
```

## Clang timings

O2/g0, libc++, Ninja8, mold, no PCH/LTO; prebuilt runtime and fresh instrumented
application objects. Single correctness samples, not comparative averages. Frontend
and LLVM times are accumulated trace work, not additive parallel wall-time components.
LLVM includes optimizer/code generation.

| Main fixture | Native wall ms | Frontend ms | LLVM backend ms | Link ms |
| --- | ---: | ---: | ---: | ---: |
| cpp | 133.36 | 9.69 | 10.84 | 68 |
| php | 188.67 | 19.96 | 19.49 | 98 |
| js | 133.17 | 12.29 | 10.49 | 62 |

Incremental checkpoint artifacts, rebuilt fresh natively: 129.93–180.12 ms wall,
11.77–25.33 ms frontend, 16.29–32.52 ms LLVM and 62–86 ms link.
The separate standard-library-heavy native observers took 957–1183 ms wall,
761–977 ms frontend, 66–82 ms LLVM and 62–66 ms link; their test headers are not
part of ordinary emitted application includes.
PHP fixture generation plus semantic/incremental checks took 186.13 ms; this is not
an isolated preparation benchmark. Exact visit assertions prove no operand revisits.

Raw timing/build artifacts: `v0.2/build/mixed-arithmetic-proof-2026-11-06-final/`.
