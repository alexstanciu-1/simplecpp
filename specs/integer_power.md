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
Grouping overrides these defaults. `**=` is outside this contract.

The current compiler admits the same order-independent operand shapes as its other
integer arithmetic. Writes, mutations and calls cannot become power operands through
grouping or casts. Each operand is evaluated once; relative ordering of independent
operand failures is unspecified. Enclosing logical short circuiting may skip power.

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
