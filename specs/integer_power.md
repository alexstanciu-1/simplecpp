# Integer exponentiation
Doc Status: normative

Agreed 2026-10-02. This defines the canonical integer power operation implemented
by the my-try v0.2 frontend and shared runtime. It does not assert support in the
legacy generator or other frontends.

## Source and result

`int ** int` returns canonical signed 64-bit `int`. Both operands must have that
exact canonical type; other integer widths, floats, bools, strings, mixed values
and nullable/sentinel carriers require a separately agreed operation. No implicit
promotion or value-dependent result type is introduced.

Power associates right-to-left and binds more tightly than prefix unary operators:
`2 ** 3 ** 2` is `2 ** (3 ** 2)`; `-2 ** 2` is `-(2 ** 2)`; `(-2) ** 2` is `4`.
A signed exponent is syntactically valid: `2 ** -2` reaches the runtime domain check.
Grouping overrides these defaults. The 2026-10-20 compound extension below admits `**=`.

Power follows the [v0.2 eager binary evaluation contract](expression_evaluation.md):
evaluate and convert the left operand, then the right, each once, before computing.
Calls and other admitted value expressions may be operands. A left-side failure
prevents right-side evaluation. Enclosing logical short circuiting may skip power.

## Values and errors

- A zero exponent returns `1`, including `0 ** 0`.
- A negative exponent throws `runtime_error` with code `power_negative_exponent`,
  regardless of the base, including `0`, `1` and `-1`.
- A nonnegative exponent produces the exact mathematical integer result if it lies
  between -9223372036854775808 and 9223372036854775807 inclusive.
- An out-of-range result throws `runtime_error` with code `power_overflow`.
- Both errors identify component `scpp::pow` and operator `**`.

Thus `(-2) ** 63` succeeds, `2 ** 63` fails, and either signed boundary raised to
`1` succeeds. Overflow must be detected before unsafe native arithmetic. Existing
arithmetic operators retain their own overflow contracts. If power fails while
computing an assignment RHS, that assignment does not store a result.

## Ownership

The shared runtime entrypoint is
`scpp::pow(const int_t<>&, const int_t<>&) -> int_t<>`, provided by
`operators/arithmetic/power.hpp`. The runtime owns calculation and error behavior;
frontends own syntax/type checks and lower their selected operation to this helper.
The helper is registered in [runtime config](../runtime/specs/config.json).

[Native runtime tests](../tests/runtime/native/test_pow.cpp) cover values, boundaries,
errors and the exact public signature. [Compiler tests](../compiler/my-try/tests/power.php)
and [generated-program fixtures](../compiler/my-try/tests/s2s.php) cover preparation,
precedence, incremental behavior and C++ lowering.

## Compound extension — 2026-10-20

Canonical `int **= int` and all four canonical-int carrier/plain arrangements use
the same checked power calculation. Guarded operands require present payloads.
The compound destination is resolved once and its old value is saved/extracted
before RHS evaluation. Only successful calculation and destination conversion
write back once; source/error/overflow failures preserve earlier RHS effects.
The expression returns the updated destination snapshot. Fixed-width, float,
boxed and wider target capabilities remain separate.
