# Metadata-driven int64 arithmetic

Doc Status: supporting

The bounded slice implements binary `+ - * / %` on canonical int64 operands in all
three flavors. `int` and `int64` share one identity. Results are int64. The owning
contracts are [integer arithmetic](../../../specs/compact_layout_types.md#7-v02-integer-arithmetic-and-numeric-comparisons)
and [ordered evaluation](../../../specs/expression_evaluation.md).

Integer division truncates toward zero; nonzero remainder has the dividend's sign.
Zero divisors throw the existing runtime domain errors `division_by_zero` or
`modulo_by_zero`. Native signed-overflow limitations remain, including the
unrepresentable `INT64_MIN / -1` division and corresponding native remainder case.
There is no new checked-overflow, wrapping or saturation guarantee. Tests exercise
the defined domain and do not assign expected results to undefined native overflow.

Unary signs on general expressions, increments, power, bitwise/shifts,
float arithmetic, mixed numeric arithmetic and other operand types remain deferred.
Negative integer test operands use subtraction or parameters; this slice does not
expand signed integer literal syntax. Floating signed-literal syntax is unchanged.

## One binary path

`comparison_operator` is now `binary_operator`; its existing six IDs remain stable
and five arithmetic IDs are appended. `ast_binary_expression` still owns only its
left/right expressions and operator. Its inherited prepared type fact is enough to
identify the approved operation with the operand type: no new prepared record,
operand list, per-occurrence handler, or copied syntax is introduced.

The parser's existing precedence traversal now orders multiplication/division/
remainder above addition/subtraction, then relational comparisons, then equality.
Each level associates left. Parentheses override that tree shape.

Runtime metadata's `compiler.arithmetic.int64` defines same-type operands, int64
result, native overflow policy and each operator's lowering strategy. Existing
comparison metadata remains separate, but both feed one generated binary handler
on each canonical type definition. Unsupported policies fail generation.

`binary_result()` supplies generated admission/result identity shared by ordinary
expressions and [compound writes](compounds.md). `prepare_binary()` attaches
ordinary expression facts through that query.
`lower_cpp_binary()` consumes the approved operator. `binary_headers()` reports
only required public headers. These replace the comparison-specific methods;
`settle_binary()` replaces the comparison-specific AST setter. Inventory version
is 11. The compiler does not interpret metadata while preparing source expressions.

Preparation visits both children once, in source order, then makes one generated
handler call. There is no constant folding, fixed point, callee-body scan, extra
symbol table or dependency listener. Calls/global references retain their existing
enclosing-body watches. Recursive reset and collection already handle this AST.

Lowering retains ordered by-value operand snapshots where needed; [direct scalar lowering](direct_lowering.md) removes wrappers only for proven-safe expressions. `+ - *` use native C++
operators without a runtime header. `/ %` call `scpp::int64_divide` and
`scpp::int64_remainder`, declared in the generated `scpp/arithmetic.hpp`, whose only
include is `<stdint.h>`. The compiled runtime source delegates to existing private
scalar semantics, preserving their errors. Exception templates/headers do not enter
application includes. A failure in a left subexpression suppresses later RHS work.

## Verification

- `tests/arithmetic.php`: all three flavors; all five operations; precedence and
  associativity; negative quotient/remainder; representable limits; function
  arguments/returns; assignments and loops; metadata-type rejection; one-visit
  preparation; reset/reprepare and lowering purity.
- Retained-session tests change a callee from int to float and back, rejecting then
  recovering without reparsing the caller; body-only and no-op changes reuse it.
  Incremental native emission remains deferred.
- `tests/arithmetic_native.py`: 16 executables, exact output and exit checks,
  including ordered calls, RHS reference mutation, zero-divisor errors, suppression
  of later calls after failure, and a mixed-flavor multifile build.
- `../tests/arithmetic_runtime.py`: standalone public include, template-free closure
  and linked runtime behavior. Generator tests remove capabilities and reject
  unsupported policies. Existing comparison tests remain regression coverage.

Commands:

```sh
XDEBUG_MODE=off php v0.2/compiler/tests/arithmetic.php
XDEBUG_MODE=off python3 -B v0.2/compiler/tests/arithmetic_native.py --output v0.2/build/arithmetic-proof-2026-11-04
python3 -B v0.2/tests/arithmetic_runtime.py
```

Focused proof on 2026-11-04: PHP fixture generation, including semantic/incremental
assertions, took 79.824 ms. This is not a pure compiler benchmark.

| Fixture | Native wall ms | Clang frontend ms | LLVM backend ms | Optimizer ms | Codegen ms | Link ms |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| cpp | 158.987 | 18.227 | 20.806 | 13.231 | 5.739 | 67 |
| php | 156.615 | 17.031 | 21.895 | 13.005 | 7.004 | 66 |
| js | 156.536 | 16.227 | 25.467 | 16.598 | 7.034 | 70 |
| multifile | 120.057 | 13.172 | 15.717 | 6.155 | 5.038 | 60 |

O2/g0, libc++, mold, Ninja -j8, no PCH/LTO, fresh instrumented app objects, prebuilt
runtime archive. Trace categories overlap and accumulate across units; they are not
additive wall time. Exception-observer fixtures take 534–925 ms because test-only
catch code includes `<stdexcept>` and `<string_view>`; production emitted includes
remain minimal. Raw fixtures, logs, traces and results are in the output directory.

Regression results: 42 selected compiler/generator CTest checks passed, followed by
the newly registered arithmetic/public-header checks. Runtime-family generation
passed 130 accepted and 247 rejected cases. Frozen runtime artifact expectations
were updated only for the added header and inventory membership; every other
artifact hash was preserved. Legacy G1 input hashes were already stale before this
slice (54/54 differ even after removing the new arithmetic metadata); those input
hashes were not recaptured, and no complete legacy parity-suite pass is claimed.
