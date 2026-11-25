# Integer exponentiation

Doc Status: normative

## Source and result

`int ** int` returns canonical signed 64-bit `int`. Both operands must have that
exact canonical type; other integer widths, floats, bools, strings, mixed values
and nullable/sentinel carriers require a separately agreed operation. No implicit
promotion or value-dependent result type is introduced.

Power associates right-to-left and binds more tightly than prefix unary operators:
`2 ** 3 ** 2` is `2 ** (3 ** 2)`; `-2 ** 2` is `-(2 ** 2)`; `(-2) ** 2` is `4`.
A signed exponent is syntactically valid: `2 ** -2` reaches the runtime domain check.
Grouping overrides these defaults. The compound form admits `**=`.

## Values and errors

- A zero exponent returns `1`, including `0 ** 0`.
- A negative exponent throws `runtime_error` with code `power_negative_exponent`,
  regardless of the base, including `0`, `1` and `-1`.
- A nonnegative exponent produces the exact mathematical integer result if it lies
  between -9223372036854775808 and 9223372036854775807 inclusive.
- An out-of-range result throws `runtime_error` with code `power_overflow`.
- The runtime reports these names through `std::runtime_error::what()`.

Thus `(-2) ** 63` succeeds, `2 ** 63` fails, and either signed boundary raised to
`1` succeeds. Overflow must be detected before unsafe native arithmetic. Existing
arithmetic operators retain their own overflow contracts. If power fails while
computing an assignment RHS, that assignment does not store a result.

Operand evaluation follows the [expression contract](expression_evaluation.md). A compound power update retains its destination and previous value and writes back only after successful checked calculation and destination conversion. See [compiler power coverage](../compiler/docs/power.md).
