# Int64 unary signs

Doc Status: supporting

The [scalar contract](../../../specs/compiler_scalars_v0_2.md#int64-unary-signs--2026-11-05)
owns admission and semantics. All three flavors support int/int64 unary `+`/`-`:

```php
$value int = 8;
$negative = -$value;
echo -($value + 2);
```

## Ownership and generated behavior

`unary_operator` supplies positive/negative identities with native uint8 intent.
`ast_unary_expression` owns its operator and one operand; inherited expression
facts hold only the approved type. Unary values are not reference storage.

The parser recursively consumes prefix operands at the existing primary level,
above binary precedence. Collection and preparation reset traverse that same
child. Syntax/prepared inspection report the operator and operand. Logical `!`
keeps its existing generated-truthiness path.

`compiler.unary.int64` metadata declares operators, int64 result, native overflow
and native lowering. The generator validates this bounded contract and emits
`prepare_unary()`, `lower_cpp_unary()` and `cpp_unary_reorderable()` on type_int64.
Unsupported types use the rejecting base methods. Generation inventory is 13;
removed capabilities disappear on regeneration. No metadata lookup occurs during
source preparation or lowering.

Preparation visits the operand once and calls its canonical generated type handler.
It does not fold values, scan bodies, allocate a prepared wrapper, copy syntax or
add listeners. Calls and symbol reads beneath the operand retain ordinary watches.
Lowering emits parenthesized native signs, evaluating the operand once. No runtime
helper/header or new lambda is needed. Reorderability requires both the generated
operation permission and the existing operand safety proof; calls stay effectful.

## Boundaries and donor review

Reviewed `compiler/my-try/docs/catalog/02_expressions.md` (EXPR-NEG/POS) and
`compiler/my-try/docs/s2s_scalar_operators.md#unary-operators`. Retained the useful
one-child model, right-to-left prefix nesting, value-only result, literal magnitude
boundary and unchanged native overflow contract. The donor's operator-decision
records and operand-conversion machinery are unnecessary for this same-type slice.

`- -8` is nested negation; `--8` remains a mutation token and rejects here.
Casts of signed integer expressions use grouping: `(int)(-8)`. Existing cast
lookahead and signed floating literals remain unchanged. General unary float
expressions, bitwise operators, value-producing updates, wider constant evaluation
and evaluation-order debt remain outside this slice.

Positive literal magnitudes stay within int64. Consequently an execution expression
`-9223372036854775808` rejects, while `-9223372036854775807 - 1` can produce the
minimum. Literal-only global/constant/default parsing keeps its existing signed
literal rules. Negating an already-minimum value has no new overflow guarantee.

## Verification — 2026-11-05

- `tests/unary.php`: all flavors; precedence/nesting; casts; locals, arguments and
  returns; effectful reference call evaluated once; conditions/loops; boundaries;
  unsupported types; non-addressability; parent ownership; exact preparation visit
  count; recursive reset/reprepare; lowering purity and both inspection surfaces.
- Retained sessions: int-to-bool callee signature invalidates and rejects the
  unchanged caller; restoration recovers without replacing caller tokens/AST;
  body-only and no-op edits reuse it; editing the caller's sign reuses the callee.
- Generator tests remove all unary support or one sign and reject unsupported
  type/operator/overflow/lowering/result metadata.
- 48 selected compiler/generator CTest checks passed, including style and scalar,
  cast, update, logical, arithmetic, symbol and incremental regressions.
- Six native fixtures passed: three flavors plus three multifile recovery outputs.
  Native recovery emission uses fresh full preparation; incremental native emission
  is still deferred.

PHP fixture generation plus its semantic/incremental assertions: 77.323 ms. This
includes test work and is not a pure compiler-stage timing.

| Flavor | Native wall ms | Frontend ms | LLVM backend ms | Optimizer ms | Code generation ms | Link ms |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| cpp | 166.132 | 16.089 | 20.406 | 9.742 | 7.872 | 64 |
| php | 119.887 | 8.883 | 9.074 | 4.143 | 3.459 | 57 |
| js | 116.499 | 8.522 | 8.869 | 3.797 | 3.661 | 56 |

Single focused samples, not evidence of a flavor performance difference. O2/g0,
libc++, Ninja -j8, mold, no PCH/LTO; prebuilt runtime; fresh instrumented application
objects. Frontend/LLVM totals accumulate across TUs; categories overlap and are not
additive wall time. Recovery builds took 109.770–128.514 ms.

Raw fixtures, emitted C++, logs, traces and results:
`v0.2/build/unary-proof-2026-11-05/`.

```sh
XDEBUG_MODE=off php v0.2/compiler/tests/unary.php
XDEBUG_MODE=off python3 -B v0.2/compiler/tests/unary_native.py --output v0.2/build/unary-proof-new
```
