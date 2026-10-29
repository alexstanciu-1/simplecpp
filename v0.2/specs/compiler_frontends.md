# Compiler flavor and shared syntax contract

Doc Status: supporting
Date: 2026-10-29
Scope: approved rewrite direction and implemented first parser subset. This is not
an assertion of complete C++, PHP or JavaScript compatibility.

## Language and policy boundary

Simple C++ is one language with C++, PHP and JS syntax flavors. There is one PHP
flavor, not separate PHP/strict-PHP++/PHS modes. Historical strict-PHS donor guidance
does not establish a second mode in this rewrite. Project settings will own strictness
and configured defaults, enforced by common compiler analysis. Parsers retain syntax
and intent regardless of whether a later profile permits it.

Mixed flavors in a project are allowed, though consistent project style is encouraged.
Flavor is per file, never global. Initially the semantic profile is project-wide.
Project-manifest loading and strictness enforcement are not implemented in this
parser slice. The CLI requires explicit cpp/php/js; it does not guess from extension.
The driver accepts a flavor for each source operation, and token generations retain
that selection. Parsing dispatches from that retained flavor, not a second argument.

Integer literal 3 is intended to acquire the project-configured default integer type
through analysis; the parser neither selects int64 nor converts the spelling. Unknown
names/types, duplicate declarations, overflow, conversions and inference restrictions
remain semantic responsibilities. Successful parsing does not mean a valid program.

## First common subset

| Intent | C++ | PHP | JS |
| --- | --- | --- | --- |
| Explicit local | `int count = 3;` | `$count int = 3;` | `let count: int = 3;` |
| Inferred local / unresolved write | `auto count = 3;` | `$count = 3;` | `let count = 3;` |
| Name reference | `count` | `$count` | `count` |
| Existing-local assignment | `count = 4;` | `$count = 4;` | `count = 4;` |

The shared AST distinguishes an explicit/inferred Local from an Assignment.
C++/JS assignments carry ExistingBinding intent. PHP bare assignments carry
DeclareOrAssign intent; analysis decides introduction versus update. No parser builds
or consults a symbol table. All locals in this first subset are mutable; immutable
binding syntax is deferred rather than silently mapped to mutable let/auto.

Implemented statements: initialized local declarations, simple-name assignments,
expression statements and brace blocks. Semicolons are mandatory; no JS automatic
semicolon insertion. Expressions: decimal integer tokens, names, parentheses, unary
plus/minus, multiplication/division/remainder, addition/subtraction and shifts.
Precedence descends in that order; binary operators associate left. Assignments are
statements, not expressions in this slice. There are no functions, calls, member
access, comparisons, strings/floats in expressions, const declarations, PHP tags/HTML
modes, C++ preprocessing, JS prototypes or JS template literals yet.

Type syntax preserves a name and nested type arguments, including vector<vector<int>>.
This is syntax admission, not container construction, type lookup or generic lowering.
Every closing angle consumes one token. Shift expressions consume two byte-adjacent
angles and produce one AST operation; whitespace/comments between angles prevent
combination. Tokens are never rewritten or marked ignored. <<= and >>= retain their
existing lexical tokens but compound assignment parsing remains deferred.

## Lexical boundary

Source bytes, spans, compact tokens, token publication and diagnostics are shared.
The current scanner shares decimal/quote/trivia/punctuation machinery with explicit
flavor rules: PHP admits $-prefixed variable tokens and # comments (except #[);
JS admits $ as an identifier character; C++ rejects $ names in this bounded subset.
C++/JS reject # constructs for now. Identifier spelling is ASCII. Keyword
classification is flavor-specific; PHP-only words such as echo remain ordinary
identifiers in C++/JS. auto and let have flavor-specific tags. The initial keyword
sets are intentionally bounded, not full host-language reservation tables.

Lexing whole quote/numeric spellings does not admit them in the current expression
parser or establish escape/conversion semantics. Additional literal families require
review of their flavor rules before parser support. Strings, interpolation and
literal decoding must preserve source intent without importing host-language behavior.

## Owners and representation

- language.rs owns syntax flavor identity, independent of the driver and profile policy.
- 02_tokenize owns lexical scanning and immutable flavor-associated generations.
- 03_parse/cpp.rs, php.rs and js.rs own their declaration/assignment grammars.
- 03_parse/parser.rs provides shared cursor, type/block parsing and the current common
  expression grammar. Shared machinery avoids cloning identical grammar algorithms;
  each frontend can add syntax-specific normalization without inventing semantic rules.
- 03_parse/ast.rs owns one source-level AST. Names normalize PHP's leading $ away while
  retaining the full source span. Types retain syntax. Integer values retain raw spans.
  Expression nodes occupy a file-local arena, avoiding recursively boxed binary chains;
  statement blocks and type arguments remain bounded trees. Source/tokens stay retained.
- The driver sequences load, scan and parse, returning a complete ParsedFile or error.
  The CLI prints a success summary only after parsing succeeds. There is no partial AST
  publication or error recovery in this first slice.

Recursive parser calls have a 128-level resource guard, separate from strictness.
No arbitrary keyword-driven semantics, runtime bindings or backend code live in the
AST. The future common analysis must resolve names/types, apply the project profile,
establish evaluation order and produce settled facts for the common backend.

## Review and proof boundary

Reviewed donor: my-try/03_parse/parser.php expression precedence/adjacent-angle handling
and structures/structures_declarations.php syntax/semantic separation. Reused the
concepts, not its retained scope machinery, parser-driven collection callbacks,
incremental identity reconciliation or worker scheduling.

Tests compare equivalent flavor programs against independent expected AST meaning,
check original spans and source retention, distinguish inference/write intent, verify
precedence and immutable angle handling, reject malformed/wrong-flavor input, and
exercise one driver across multiple flavors. They also cover EOF diagnostics, nesting
limits and a 10,000-operation flat expression without recursive expression storage.
Semantic equivalence and executable-program parity remain future analysis/backend gates.
