# Bounded scalar operators
Doc Status: supporting

Updated and checked 2026-10-02. This guide records the current my-try frontend
and C++ S2S slice for [catalog chapter 02](catalog/02_expressions.md). Root language
and runtime specifications remain authoritative. Imported legacy examples do not
activate additional operand types or coercions.

## Current contracts

| Source operation | Exact operand types | Result | Selected C++ lowering |
| --- | --- | --- | --- |
| `**` | canonical `int`, `int` | canonical `int` | Checked shared `scpp::pow` |
| `+`, `-`, `*`, `/`, `%` | canonical `int`, `int` | canonical `int` | Existing runtime wrapper arithmetic |
| `==`, `!=`, `<`, `<=`, `>`, `>=` | canonical `int`, `int` | canonical `bool` | Existing runtime wrapper comparisons |
| `===`, `!==` | canonical `int`, `int` | canonical `bool` | `scpp::php::identical` / `not_identical` |
| `<=>` | canonical `int`, `int` | canonical `int`, exactly -1/0/1 | Snapshot each operand once, compare with `<` and `>` |
| `.` | canonical `string`, `string` | canonical `string` | Existing string-wrapper `+` |
| `&&`, `\|\|`, `and`, `or` | canonical `bool`, `bool` | canonical `bool` | Convert each operand explicitly to native bool around native lazy operators, wrap result |
| `xor` | canonical `bool`, `bool` | canonical `bool` | Snapshot native bools left-to-right, compare with `!=`, wrap result |

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

`&&`, `||`, `and` and `or` evaluate the RHS lazily; `xor` evaluates both sides
left-to-right. Both sides are still prepared and typechecked;
an invalid RHS is rejected even when a constant left side would skip it at runtime.
The explicit native-bool bridge follows runtime spec section 6.2 and avoids invoking
overloaded wrapper logical operators, which would evaluate both sides.

The imported spaceship card mentions `scpp::cmp`, which is absent from the current
runtime. The selected lowering instead uses an immediately invoked lambda with two
existing backend temporaries, initialized left-to-right, then compares their values.
It neither subtracts potentially extreme values nor returns a C++ ordering category.

## Grammar and ownership

Precedence from weakest to strongest is:

1. `or`
2. `xor`
3. `and`
4. `=`, `+=`, `-=`, `*=`, `/=`, `%=`, `.=` and bitwise/shift compound assignments
5. `||`
6. `&&`
7. `|`
8. `^`
9. `&`
10. `==`, `!=`, `===`, `!==`, `<=>`
11. `<`, `<=`, `>`, `>=`
12. `.`
13. `<<`, `>>`
14. `+`, `-`
15. `*`, `/`, `%`

Power `**` binds above prefix unary operators and associates right-to-left. Its
RHS accepts a unary expression, so signed exponents parse before domain checking.
Assignments associate right-to-left; the binary levels listed above otherwise associate left-to-right;
unsupported comparison chains fail the exact operand-type policy. Parentheses control the same binary tree without retained
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
including `$a + 1;`, grouped expressions and concatenation. Plain assignment
statements use the same expression grammar; explicitly typed declarations remain
a separate statement form.

Calls, mutations and compound updates remain rejected as binary operands. Logical
operators also admit assignments to existing locals/parameters. Eager operators
inspect nested logical trees too, so casts and grouping cannot hide writes. Casts
and nested admitted binaries are otherwise supported. Eager runtime operand
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

Prefix operators nest right-to-left and bind above ordinary binary levels,
but below exponentiation.
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

## Compound assignment

Added 2026-10-02 with the agreed `compound_assignment_expression_node`. It owns the
target, operator token and RHS. Attached facts retain the existing binary
`operator_decision` plus a `conversion_decision` in assignment context for write-back.
The result type is the target type; the result is a non-addressable value snapshot.

`+=`, `-=`, `*=`, `/=` and `%=` require canonical `int` on both sides. `.=` requires
canonical `string` on both sides; other scalar values need an explicit `(string)`
cast. Targets are existing locals or parameters. Target validation is shared with
increment/decrement through `Mutation_Preparation::prepare_target`; neither form
implicitly declares storage. Fields, indexes, calls/casts as targets, mixed widths
and implicit string coercion remain rejected.

The parser recognizes the six compound tokens with longest matching and places
compound assignment below symbolic binary precedence and above keyword logic. It retains nested syntax
right-to-left, but preparation rejects effectful RHS expressions, including another
assignment, compound update, mutation or call. Pure nested binary/unary expressions
and explicit casts retain their existing permissions. A compound update itself
remains excluded from binary operands. Assignment initializers, returns and existing
sequenced value-argument handling can consume its snapshot; reference arguments cannot.

Preparation normalizes the compound token to its base binary kind, then uses the
same exact operand policy as ordinary computation. The backend's shared
`render_binary_decision` consumes operand conversions for both forms; it does not
rediscover source semantics. Compound emission binds the target once to a local
reference in an immediately invoked lambda, computes the result once, applies the
retained write-back conversion, assigns it, and returns a copy using the explicit
result type. RHS text appears once. Failed computation occurs before the store.

This gives `$x /= 0` and `$x %= 0` the existing runtime error codes while leaving
`$x` unchanged. Native integer truncation, signed remainder and overflow limitations
are inherited from the ordinary binary operators. String self-concatenation computes
before assignment, and a captured concatenation result remains independent of later
updates. No runtime helper was added. The local lambda introduces a target reference
but no heap allocation; compile-time cost is unbenchmarked.

Legacy emission uses direct arithmetic compound operators and expands concatenation
into assignment plus string addition. This slice instead shares prepared computation
and write-back uniformly, and requires explicit non-string conversion. It does not
inherit the legacy generator's broad coercion permissions.

[Compound tests](../tests/compound_assignments.php) prove target restrictions,
selected operations, operand/write-back conversions, result non-addressability,
child order, cleanup, rejection-before-publication and incremental/fresh equivalence.
[Tokenizer tests](../tests/tokenizer.php) cover all six tokens. PHP-host S2S generation
and syntax-purity checks pass; 26 generated Clang C++20 programs/probes cover all
seven catalog rows, precedence, updated-value snapshots, self-use, signed arithmetic,
value/reference parameters, argument sequencing, exact strings and zero-divisor
errors with unchanged caller storage. Existing generated fixtures retain identical
C++ after factoring the shared renderer. Native compiler execution was not rerun;
LLVM remains deferred.

## Bitwise and shift operators

Added 2026-10-02: canonical `int` pairs now support `&`, `|`, `^`, `<<`, `>>` and
compound forms `&=`, `|=`, `^=`, `<<=`, `>>=`. Result types remain canonical `int`.
Existing binary/compound nodes, operator decisions and write-back conversions are
reused; no AST structure or field was added. Non-int operands and effectful operands
retain the existing rejections. Compound targets remain existing locals/parameters.

The agreed [runtime contract](../../../runtime/specs/spec.md) delegates to native
C++20 integer behavior. Counts 0 through 63 are valid for the canonical signed
64-bit representation. Right shift sign-extends and rounds toward negative infinity:
`-3 >> 1` is `-2`. Left shift follows congruence modulo 2^64, so `1 << 63` is the
minimum signed integer and `PHP_INT_MAX << 1` is `-2`. This is the shift rule, not
a new wrapping guarantee for addition or multiplication. Negative counts and counts
at least 64 remain undefined behavior; no masking, runtime check or compile-time
rejection was added. See [C++20 N4861](https://timsong-cpp.github.io/cppwp/n4861/expr.shift)
and [checked-count debt](planning/operators.md#debt-checked-shift-counts).

The lexer retains individual `<` and `>` tokens so nested generic applications,
casts and calls keep their existing grammar. `token_list::operator_text_at` exposes
physically adjacent matching angle tokens as a shift spelling in expression context.
The parser consumes both, and preparation reads the same spelling from retained
tokens. Spaced `< <` or `> >` are not shifts. Compound `<<=`/`>>=` are longest-match
single tokens. Incremental body comparison includes source bytes, so inserting
whitespace between shift characters cannot reuse the previous valid expression.

Shift precedence is below addition and above concatenation. Bitwise precedence is
`&` above `^` above `|`, all below comparisons and above `&&`. Ordinary reference
parameter `&` and logical `&&`/`||` retain their grammar. Compound normalization
removes the trailing `=` instead of assuming a one-character base operator.

The existing runtime bitwise/shift helpers and legacy direct operator forms are
reused with prepared exact types. No runtime changes or special backend arithmetic
were introduced. Compound writes continue to use one target binding, one computation
and an updated-value snapshot.

[Operator tests](../tests/operators.php) cover selected operations/conversions and
operand rejections; [compound tests](../tests/compound_assignments.php) cover all five
new write-back decisions. [Bitwise tests](../tests/bitwise.php) cover retained
multi-token shift spellings, incremental binary/compound changes, compaction,
whitespace invalidation and recovery. Invalid counts are prepared/emitted only,
never executed. Grouping regressions retain nested generic parsing.

PHP-host S2S generation/purity passes. The 37 new Clang C++20 programs/probes cover
precedence/associativity, all five compound forms, snapshots/reference updates,
counts 0 and 63, negative operands, sign-bit changes, and exact native value/type
assertions. Prior generated fixtures are byte-identical. Native execution of the
compiler was not rerun; LLVM remains deferred.

## Assignment expressions and keyword logic

Added 2026-10-02: `and`, `or` and `xor` accept canonical bools. `and`/`or` share
prepared semantic operations with `&&`/`||`, but bind below assignment. For example,
with `$a` already declared, `$a = true and false;` stores `true`; `$a = true && false;`
stores `false`. Parentheses around the whole logical RHS select that logical result.
`(true) and ...` is grouping, not cast syntax. Legacy catalog cards reject the word
operators; v0.2 deliberately admits them under these explicit precedence/type rules.

The existing assignment node and `prepared_assignment` carry this behavior. A nested
assignment evaluates its RHS once, converts to the target type, stores once and returns
a non-addressable copy of the stored value. C++ uses an immediately invoked lambda
with an explicit value return type. Failed RHS computation leaves the target unchanged.
Return values, typed initializers, casts and sequenced value arguments can consume the
snapshot; reference arguments cannot. Assignment targets in these contexts must be
existing locals or parameters. Nested field/index writes remain deferred.

Standalone first assignments and their direct right-associative chains retain their
existing declaration behavior and statement-owned lowering. Grouping normalizes away,
so `$a = ($b = 3);` has the same chain as `$a = $b = 3;`. An invocation-local preparation
flag permits declarations only along that chain, never through a logical operand,
call argument, typed initializer or field base. Thus `$a = true and false;` requires
an already established `$a`, even though its left operand always executes. This slice
does not introduce branch-sensitive initialization or conditional declarations.

Logical operands are prepared left-to-right and both must be valid, including a
statically skipped RHS. `and`/`or` use native bool short circuiting. `xor` snapshots the
left bool, then the right bool, and compares those snapshots. This preserves results
when both operands write the same local. Assignment inside ordinary arithmetic,
comparison, concatenation, unary operators or compound RHS remains rejected, including
writes hidden inside a nested logical expression. Calls, mutation and compound-update
operands retain their earlier binary restrictions; general effect analysis is deferred.

[Logical tests](../tests/logical.php) cover AST precedence, assignment facts, grouped
write collection, diagnostics, cleanup, incremental edits, token compaction and failed
edit recovery. [S2S tests](../tests/s2s.php) preserve syntax/scopes during preparation
and generation. The 38 new Clang C++20 programs/probes cover the three catalog rows,
lazy writes and exceptions, XOR snapshots/order, assignment conversions, reference
parameter writes, sequenced arguments, single evaluation and failed stores. Existing
operator, mutation, compound, grouping, tokenizer and collection PHP checks pass.
Prior generated fixtures remain byte-identical. Native execution
of the compiler was not rerun; LLVM remains deferred.

## Integer exponentiation

Added 2026-10-02 under the [normative contract](../../../specs/integer_power.md):
canonical `int ** int -> int`, right-associative and above prefix unary precedence.
The existing binary AST and decision records are reused. The lexer recognizes `**`;
a dedicated parser step between unary and postfix parsing handles both `-2 ** 2`
and `2 ** -2`. Ordinary arithmetic effect restrictions remain in force.

`Integer_Operators` selects `integer_power`; C++ lowering consumes that decision,
includes `operators/arithmetic/power.hpp` and calls registered shared `scpp::pow`.
The runtime computes exact power by squaring, with bounded unsigned-magnitude
products checked before multiplication. This represents the magnitude of the signed
minimum safely. It skips unused final squaring, preserving `MAX ** 1` and `MIN ** 1`.
The loop takes logarithmic time in the exponent, including large exponents for 0/±1.

Zero exponent returns 1, including `0 ** 0`. Negative exponents throw
`power_negative_exponent`; out-of-range results throw `power_overflow`. Neither
failure uses floating-point approximation, promotion or signed-overflow behavior.
The imported legacy helper requirement is now fulfilled for canonical ints only;
legacy generator support is unchanged. Floating power and `**=` are deferred.

[Runtime tests](../../../tests/runtime/native/test_pow.cpp) pass with Clang C++20
undefined-behavior sanitization. They exercise both signed boundaries, exact and
out-of-range powers, huge exponents, stable error metadata, API type restrictions,
and an independent repeated-multiplication oracle for small inputs.
[Power tests](../tests/power.php) cover AST precedence, decisions, cleanup, incremental
header addition/removal, compaction and error recovery. PHP-host S2S generation and
syntax-purity checks pass, as do the affected operator/tokenizer/grouping/logical/
compound/mutation checks. All 25 new generated C++ programs/probes compile and run,
including negative-exponent/overflow errors and unchanged assignment storage on failure.
All 346 prior generated fixtures remain byte-identical. Native execution of the
compiler itself was not rerun; LLVM remains deferred.

## String interpolation

Added 2026-10-02 under the [scalar interpolation contract](../../../specs/string_interpolation.md).
Double-quoted `$name` and `{$name}` insert existing scalar locals/parameters using
the same conversion rules as explicit string casts. Single quotes and escaped dollars
remain literal. Fields, indexes, calls, writes and arbitrary expressions inside
insertions are deferred; explicit braces disambiguate literal suffixes.

The lexer keeps its complete string token. `Parser_Run` splits an interpolated token
into `interpolation_text_node` and `interpolation_value_node` parts owned by
`interpolated_string_node`. Each part retains its byte offset and length relative to
that token. Value parts own normal variable references and distinct collected read
occurrences, even for repeated names within one token. This preserves collection
order and source provenance without manufacturing tokens. Ordinary strings retain
their existing literal representation.

Preparation owns escape decoding and attached per-insertion conversion decisions.
The new `interpolation` conversion context admits the existing scalar string-cast
policy without widening assignment or concatenation. C++ emits a value-returning
lambda with one local string and sequential `append` statements. Each insertion is
read and converted once, and the returned string remains independent of later writes.
The existing runtime supplies all conversion, append and binary-safe literal behavior;
no new runtime API is introduced.

[Interpolation tests](../tests/interpolation.php) prove parts/children, source ranges,
read identities, conversion decisions, rejection before publication, recursive cleanup,
incremental edits, retained-body compaction and recovery from parser/semantic errors.
PHP-host S2S generation and syntax purity pass. All 20 new generated Clang C++20
programs pass exact byte comparisons covering scalar types, braced/unbraced names,
name boundaries, escapes, embedded NULs, UTF-8, self-reassignment, snapshots,
parameters, concatenation and compound concatenation. Focused grouping, operator,
tokenizer and collection PHP checks pass. All 371 prior generated fixtures remain
byte-identical. Native compiler execution was not rerun; LLVM remains deferred.

## General bracket operator

Added 2026-10-02: `base[expression]` and `base[]` share the
[normative bracket model](../../../specs/index_operator.md). The existing index node
now has an optional argument; absence means arity zero and carries no append/read
policy. Postfix parsing applies suffixes uniformly to primary expressions, including
call results and literals. Normal expression grammar handles a present argument.

`Operator_Preparation::prepare_index` owns both forms, preparing the receiver before
any explicit argument and entering the existing operator-decision boundary with
`operator_kind::index`. No overload candidate is implemented yet. String receivers
receive a specific deferred-contract diagnostic; other receivers receive a deferred
resolution diagnostic. Both report arity. Assignment and mutation target paths enter
that same preparation boundary. Result types, effects and writability await actual
overload contracts; no placeholder facts or C++ lowering are published.

[Indexing tests](../tests/indexing.php) prove both arities, ordinary expression
arguments, postfix bases, optional-child traversal, parser-only retention/compaction,
shared read/write/update diagnostics and failure recovery. Focused grouping, mutation,
compound, operator, interpolation and collection checks pass, as does PHP-host S2S
generation/purity. All 391 prior generated programs remain byte-identical.
There are no new generated bracket programs because lowering is
intentionally deferred. LLVM only gains an absent-argument guard on its parked
fixed-array path. Native compiler validation was deferred at implementation time;
the subsequent checkpoint below includes both bracket arities as rejection cases.

## Native chapter checkpoint

The subsequent 2026-10-02 native validation passes: 341 source fixtures produce
identical PHP/native C++, all generated base programs execute, 74 supplementary
instrumented programs pass, and 37 rejection/recovery cases agree. Type proofs,
15 float-spelling assertions and the final incremental native rebuild also pass.
Two portability-only source adaptations were required; see the
[checkpoint, timings and evidence](portability/conversion_review.md).
LLVM and legacy STAN remain skipped.

## Remaining decisions

- Checked shift counts are [explicit debt](planning/operators.md#debt-checked-shift-counts).
  Broader mutation targets and mutation inside binary expressions remain deferred.
- Floating-point power, other carrier types and `**=` remain deferred.
- Mixed numeric promotion, other comparison types, complex interpolation and concrete
  bracket overload contracts remain outside this bounded slice.

These remain open under the user's requirement to discuss new structures. All six
imported prose notes are now reconciled and agreed.
Chapter 02 has no pending discussion rows; the explicit deferred capabilities above
remain deferred, including bracket overload resolution and lowering.
