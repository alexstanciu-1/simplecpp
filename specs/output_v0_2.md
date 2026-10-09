# v0.2 output statements
Doc Status: normative

`echo expression;` and `echo expression, expression, ...;` write to standard
output without an implicit separator or newline. At least one operand is required;
a trailing comma is rejected. Echo is a statement, not an expression.

Each operand is evaluated exactly once and printed before evaluating the next.
If evaluation or formatting throws, later operands are not evaluated; output already
written is not rolled back. Ordinary expression restrictions still apply inside an
operand. Echo does not convert unsupported values merely because C++ can stream them.

The bounded compiler surface admits strings, booleans, integer widths, floats, null,
mixed/dynamic and nullable/Result wrappers with supported output payloads. It reuses
the existing PHP runtime output contract (`php::echo_one` / `php::to_string`):

- Strings are written as their bytes; integers use decimal text and floats retain
  the runtime formatter's behavior.
- True writes `1`; false and null write no bytes.
- Mixed/dynamic scalar payloads follow those rules. Their container labels remain
  `Array` and `Object`, respectively; echo is not JSON serialization.
- An absent nullable or falseable Result writes no bytes. A boolable true sentinel
  writes `1`. A structured Result error writes its message. Value states delegate
  to their payload's output contract.

Typed containers, source records, enums and callable payloads are not admitted by
this bounded slice. They reject during preparation, including in unreachable code.
`print`, user-defined formatting and new formatting policies are outside this slice.

The type-aware compiler retains one statement with ordered operands. The legacy
exporter's sibling `AST_ECHO` representation remains an implementation detail; both
lower to sequential runtime calls. See [legacy output rules](../generators/php/specs/rules.md#17-output-rules).
