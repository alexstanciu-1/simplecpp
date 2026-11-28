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

PHP accepts an explicitly Stringable class or interface value and both published
string builders. It calls the formatter once and writes the resulting bytes.
Direct scalar echo retains its own output spelling, including the boolean rules.

Typed containers, other source records, enums and callable payloads are not admitted by
this bounded slice. They reject during preparation, including in unreachable code.
`print`, other user-defined formatting and new formatting policies are outside this slice.

The type-aware compiler retains one statement with ordered operands. The legacy
exporter's sibling `AST_ECHO` representation remains an implementation detail; both
lower to sequential runtime calls. See legacy output rules.

## PHP diagnostic output

With the structural Encoder published, `var_dump($value)` evaluates one operand
once and returns void. It writes bytes to the same standard output destination
as `echo`. The operation works with JSON disabled. Its scalar inputs are the
published Encoder leaves (int64, float64, bool, string and their published
nullable forms), plus null. Strings report byte length and include their raw
bytes between quotes.

Mixed tables print as `array(n)` and dynamic tables as `object(dynamic)(n)`.
Integer and string keys remain distinct in the output. A typed vector prints
numeric positions. A field-derived data struct or class prints its declared
name, field count, and fields in schema order. Preparation checks the fields and
vector elements against the same Encoder capabilities. A missing owner or
nullable value prints `NULL`. Revisiting an active ancestor prints
`*RECURSION*`; shared siblings are visited independently.

Multiple arguments, Result carriers, direct typed hashes and ordinary classes
remain outside this initial diagnostic surface. FastCGI applications still
choose their response body explicitly.
