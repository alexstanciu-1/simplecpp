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

## Unary operators

Added 2026-10-02 following agreement on one `unary_expression_node`. It owns one
operand and the operator token location. Its prepared facts retain the existing
`operator_decision` with one identity operand conversion. Maintenance visits the
operand and remaps the operator token; inspection uses a lazy one-child iterator.
Preparation owns operand permission and selected operation; emission only renders
that selected operation through the existing runtime operators.

Unary `+`, `-`, `~` accept canonical `int` and return canonical `int`; `!` accepts
canonical `bool` and returns canonical `bool`. Results are values, not assignable
storage. Explicit casts can establish the required type. Other numeric widths,
floating operands and implicit truthiness remain rejected. The existing restriction
on effectful operands remains in force, including beneath nested unary expressions.

Prefix operators nest right-to-left and bind above the supported binary levels.
Grouping controls their operand: `-2 * 3` differs from `-(2 * 3)`. `++` and `--`
are distinct mutation tokens; repeated unary signs require
separation, as in `- -3`. Strict comparisons and arrow tokens retain longest matching.

The grammar keeps `(NAME) + expression` and `(NAME) - expression` as grouped-name
binary expressions. Casts of signed operands use explicit grouping, for example
`(int)(-2)`; `-(int)3.5` also works. This preserves syntax-only disambiguation of
casts from grouped constants without consulting symbol resolution. `!` and `~`
can follow a cast directly because they cannot be binary continuations.

Decimal literal magnitudes retain the existing signed-64-bit bound. Consequently
`-9223372036854775808` is rejected at literal preparation, while `-PHP_INT_MAX - 1`
and `~PHP_INT_MAX` produce the exact minimum integer. The runtime negates native
signed values; negating an already minimum-valued integer has no new overflow
guarantee. No constant folding or widened literal type was introduced.

Legacy `Generator::renderExpr` recursively emits parenthesized unary operations;
this slice keeps those target forms with explicit prepared type permission.
Focused operator tests verify decisions, result types, non-addressability, child
inspection, cleanup, grouping, rejections, incremental operator replacement and
fresh-output equivalence after token compaction. PHP-host S2S generation/purity and
19 generated C++ programs/probes cover values, nesting, casts, logical composition
and exact signed integer limits. Native compiler execution remains unverified for
this extension; LLVM is deferred.

## Increment and decrement

Added 2026-10-02 after agreement on a dedicated `mutation_expression_node`. It owns
one target expression, the operator token location and a postfix flag. A separate
prepared mutation record reuses `operator_decision`, with one identity operand
conversion and canonical `int` result. The target's existing prepared variable
reference owns resolved storage identity; mutation facts do not duplicate it.

`Mutation_Preparation` accepts only established canonical `int` locals or parameters.
It resolves the target through the normal read path, requires addressability, and
selects one of four integer operations through the shared operator decision entry.
Unlike plain assignment, mutation never introduces a declaration. Constants, literal
values, calls, cast targets, other numeric types, fields and indexes are rejected.
Grouping a variable does not change its target identity.

Prefix increment/decrement returns a snapshot of the updated value; postfix returns
a snapshot of the previous value. Results are not addressable or valid reference
arguments. Expression statements reuse these expressions and discard their results.
Postfix syntax binds tighter than prefix syntax; repeated mutations such as
`++$x++` are rejected because their target is another value-producing mutation.

C++ lowering uses the existing runtime `++`/`--` operators and explicitly copies
the result into the prepared integer result type, for example
`static_cast<scpp::int_t<>>(++local_x)`. The target occurs exactly once. This avoids
leaking the runtime prefix operator's reference result into the source value
contract. No mutation-specific lambda, runtime helper or semantic lookup in the
backend is needed. This refines legacy direct operator emission with an explicit
snapshot boundary. Runtime native signed overflow behavior remains unchanged;
proofs stay inside representable bounds.

Assignments and returns consume the snapshot normally. Existing call emission
already evaluates value arguments into temporaries left-to-right, so multiple
mutation arguments retain their individual values. Value parameters mutate only
the local copy; reference parameters update caller storage. Binary operators retain
their existing effect restriction, including mutations hidden under casts, and
value-only unary operators also continue to reject effectful operands.

[Mutation tests](../tests/mutations.php) verify selected operations/conversions,
non-addressability, spans, single-child inspection, cleanup, rejected targets/types,
reference-argument rejection, prefix/postfix incremental edits, token compaction and
fresh/incremental equivalence. The PHP-host [S2S suite](../tests/s2s.php) verifies
syntax purity and generates 20 mutation programs/probes for Clang C++20 execution:
all eight catalog forms, independent old/new values, later-update snapshots,
self-assignment, grouped targets, value/reference parameters, argument sequencing
and exact minimum/maximum representable results. Native execution of the compiler
was not rerun; LLVM remains deferred.

## Remaining decisions

- Compound assignment and broader mutation targets still need their target evaluation
  and write-back contracts; mutation inside binary expressions remains deferred.
- Word `and` / `or` / `xor` interact with assignment precedence; the imported examples
  cannot be implemented truthfully by merely aliasing the symbolic operators.
- Exponentiation references an unavailable `scpp::pow` helper. Result type, negative
  exponents and overflow/domain policy must be settled before adding a runtime path.
- Mixed numeric promotion, other comparison types, interpolation and broader string
  indexing remain outside this bounded slice.

These remain open under the user's requirement to discuss new structures. The
remaining catalog rows retain pending status; this batch does not complete chapter 02.
