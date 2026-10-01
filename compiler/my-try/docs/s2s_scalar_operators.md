# Bounded scalar operators
Doc Status: supporting

Implemented and checked 2026-10-01. This guide records the current my-try frontend
and C++ S2S slice for [catalog chapter 02](catalog/02_expressions.md). Root language
and runtime specifications remain authoritative. Imported legacy examples do not
activate additional operand types or coercions.

## Current contracts

| Source operation | Exact operand types | Result | Selected C++ lowering |
| --- | --- | --- | --- |
| `+`, `-`, `*`, `/`, `%` | canonical `int`, `int` | canonical `int` | Existing runtime wrapper arithmetic |
| `==`, `!=`, `<`, `<=`, `>`, `>=` | canonical `int`, `int` | canonical `bool` | Existing runtime wrapper comparisons |
| `===`, `!==` | canonical `int`, `int` | canonical `bool` | `scpp::php::identical` / `not_identical` |
| `<=>` | canonical `int`, `int` | canonical `int`, exactly -1/0/1 | Snapshot each operand once, compare with `<` and `>` |
| `.` | canonical `string`, `string` | canonical `string` | Existing string-wrapper `+` |
| `&&`, `\|\|` | canonical `bool`, `bool` | canonical `bool` | Convert each operand explicitly to native bool around native lazy operators, wrap result |

Integer division truncates toward zero; remainder has the dividend's sign. Zero
raises the existing runtime errors `division_by_zero` / `modulo_by_zero`.
This follows [runtime arithmetic semantics](../../../runtime/specs/spec.md), not
standard PHP's division result type. Signed overflow and minimum-int divided by -1
retain the runtime's native C++ limitations; no wrapping or checked arithmetic is promised.

Concatenation requires an explicit `(string)` cast for a non-string operand. The
legacy catalog's broad coercion wording is not implemented. Strict comparison
helpers preserve the distinction from ordinary comparison, even though the current
exact same-type integer slice produces the same truth values. No cross-type equality
or implicit numeric promotion is admitted.

Logical RHS evaluation is lazy. Both sides are still prepared and typechecked;
an invalid RHS is rejected even when a constant left side would skip it at runtime.
The explicit native-bool bridge follows runtime spec section 6.2 and avoids invoking
overloaded wrapper logical operators, which would evaluate both sides.

The imported spaceship card mentions `scpp::cmp`, which is absent from the current
runtime. The selected lowering instead uses an immediately invoked lambda with two
existing backend temporaries, initialized left-to-right, then compares their values.
It neither subtracts potentially extreme values nor returns a C++ ordering category.

## Grammar and ownership

Precedence from weakest to strongest is:

1. `||`
2. `&&`
3. `==`, `!=`, `===`, `!==`, `<=>`
4. `<`, `<=`, `>`, `>=`
5. `.`
6. `+`, `-`
7. `*`, `/`, `%`

The current levels associate left-to-right; unsupported comparison chains fail the
exact operand-type policy. Parentheses control the same binary tree without retained
grouping nodes. A read-only call lookahead distinguishes a constant followed by `<`
from a generic call. Longest-token recognition preserves strict comparisons, `<=>`,
`->`, floating exponents and leading-dot floats.

`Parser_Run` owns syntax; `Operator_Preparation` owns source normalization, operand
preparation and candidate dispatch. `Integer_Operators::decide_binary` owns the exact
integer pair policy. Bounded string/boolean candidates remain private preparation
methods. All use existing `operator_decision` records and two identity conversions
in `operator_operand` context. Only enum cases were added; no retained structures
or fields changed. C++ generation consumes the selected operation and conversions.

Existing `expression_statement_node` now accepts ordinary scalar expressions,
including `$a + 1;`, grouped expressions and concatenation. Existing binding/member/
index statement paths remain separate; this is not a general assignment-expression
or mutation grammar extension.

Calls and other effectful binary operands remain rejected by the existing shape
restriction. Casts and nested admitted binaries are supported. Eager runtime operand
error ordering is not newly specified; this slice does not introduce effect analysis.

## Legacy review and proof

Legacy recursive arithmetic/comparison lowering and strict identity helpers informed
the target forms. The important departures are bounded prepared operand types,
explicit string conversion, genuinely lazy logical lowering and the replacement of
the unavailable spaceship helper. Nested and chained catalog examples use the same
recursive implementation and need no special cases.

[Operator tests](../tests/operators.php) check decisions, result types, conversions,
precedence, rejected operand families, cleanup and incremental preparation.
[Tokenizer tests](../tests/tokenizer.php) cover punctuation boundaries.
[PHP-host S2S fixtures](../tests/s2s.php) check generation and syntax purity, with
additional C++ probes for exact values/types and runtime exceptions.

The 2026-10-01 batch compiled and executed 62 new generated programs/probes with
Clang C++20: division/remainder signs, truncation and zero guards; concatenated string
contents/types; integer comparison truth values and limits; exact spaceship -1/0/1;
logical truth tables, precedence and skipped/executed throwing RHS; nested/chained
expressions and expression statements. Focused tokenizer, grouping, operator,
conversion, AST, parse-collection, token-cleanup and incremental-C++ PHP tests pass.
The complete PHP-host S2S generation/purity suite also passes. This is generated
program execution, not native execution of the compiler. The earlier 105-fixture
[native compiler checkpoint](portability/conversion_review.md) predates these changes.
LLVM and legacy STAN remain deferred.

## Remaining decisions

- Unary `+`, `-`, `!`, `~` require an agreed unary AST shape.
- Prefix/postfix mutation and compound assignment need target identity, evaluation
  count and old/new result policy; they are not ordinary value-only binary operators.
- Word `and` / `or` / `xor` interact with assignment precedence; the imported examples
  cannot be implemented truthfully by merely aliasing the symbolic operators.
- Exponentiation references an unavailable `scpp::pow` helper. Result type, negative
  exponents and overflow/domain policy must be settled before adding a runtime path.
- Mixed numeric promotion, other comparison types, interpolation and broader string
  indexing remain outside this bounded slice.

These remain open under the user's requirement to discuss new structures. The
remaining catalog rows retain pending status; this batch does not complete chapter 02.
